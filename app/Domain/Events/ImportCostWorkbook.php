<?php

namespace App\Domain\Events;

use App\Enums\BookingStatus;
use App\Enums\CostStatus;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\RevenueCategory;
use App\Enums\RevenueStatus;
use App\Models\Artist;
use App\Models\Event;
use App\Models\EventTemplate;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;

final class ImportCostWorkbook
{
    public function __construct(private CreateEvent $events) {}

    public function handle(User $user, CostWorkbook $workbook): Event
    {
        OfficialCatalog::ensure();

        return DB::transaction(function () use ($user, $workbook): Event {
            $templateId = EventTemplate::query()
                ->where('slug', 'festa')
                ->whereNull('organization_id')
                ->value('id');

            $notes = $workbook->courtesyLists === []
                ? null
                : 'Listas da planilha ainda sem quantidade: '.implode(', ', $workbook->courtesyLists).'.';

            $event = $this->events->handle($user, [
                'name' => $workbook->name,
                'type' => EventType::Party,
                'status' => EventStatus::Planning,
                'starts_at' => $workbook->startsOn,
                'venue_name' => $workbook->venue,
                'capacity' => $workbook->capacity,
                'notes' => $notes,
                'template_id' => $templateId,
                'currency' => 'BRL',
            ]);

            $organizationId = (int) $user->current_organization_id;
            $sort = 0;

            foreach ($workbook->lines as $line) {
                $sort++;
                $status = $this->status($line['status']);
                $vendorId = $this->vendorId($organizationId, $line['section'], $line['detail']);

                $item = $event->budgetItems()->create([
                    'budget_id' => $event->budget?->id,
                    'cost_category_id' => OfficialCatalog::categoryId($this->category($line['section'], $line['item'])),
                    'vendor_id' => $vendorId,
                    'description' => $line['item'],
                    'quantity' => $line['quantity'],
                    'unit_amount' => $line['unit'] > 0 ? $line['unit'] : null,
                    'detail' => $line['detail'] !== '' ? $line['detail'] : null,
                    'estimated_amount' => $line['total'],
                    'contracted_amount' => $status === CostStatus::Contracted ? $line['total'] : null,
                    'status' => $status,
                    'responsible_name' => $line['responsible'] !== '' ? $line['responsible'] : null,
                    'pix' => $line['pix'] !== '' ? $line['pix'] : null,
                    'invoice_number' => $line['invoice'] !== '' ? $line['invoice'] : null,
                    'invoice_url' => $line['invoiceUrl'] !== '' ? $line['invoiceUrl'] : null,
                    'notes' => $line['comment'] !== '' ? $line['comment'] : null,
                    'sort_order' => $sort,
                ]);

                if ($line['section'] === 'artistico') {
                    $this->bookArtist($event, $organizationId, $line['item'], $line['total'], $item->id, $status);
                }
            }

            foreach ($workbook->tiers as $index => $tier) {
                $event->ticketTiers()->create([
                    'name' => $tier['name'],
                    'price' => $tier['price'],
                    'quantity' => $tier['quantity'],
                    'goal' => $tier['quantity'],
                    'sold_quantity' => 0,
                    'payout_basis_points' => $tier['payoutBasisPoints'],
                    'sort_order' => $index + 1,
                ]);
            }

            if ($workbook->barExpected > 0) {
                $event->revenues()->create([
                    'category' => RevenueCategory::Bar,
                    'description' => 'Consumo de bar',
                    'expected_amount' => $workbook->barExpected,
                    'actual_amount' => 0,
                    'status' => RevenueStatus::Planned,
                    'source' => 'Planilha de custos',
                    'notes' => $workbook->barNotes !== '' ? $workbook->barNotes : null,
                ]);
            }

            return $event;
        });
    }

    private function bookArtist(Event $event, int $organizationId, string $name, int $fee, int $budgetItemId, CostStatus $status): void
    {
        $artist = Artist::query()->firstOrCreate(
            ['organization_id' => $organizationId, 'stage_name' => $name],
            ['default_fee' => $fee],
        );

        $event->bookings()->create([
            'artist_id' => $artist->id,
            'budget_item_id' => $budgetItemId,
            'status' => match ($status) {
                CostStatus::Contracted, CostStatus::Paid => BookingStatus::Confirmed,
                CostStatus::Negotiating => BookingStatus::Negotiating,
                CostStatus::Cancelled => BookingStatus::Cancelled,
                default => BookingStatus::Inquiry,
            },
        ]);
    }

    private function vendorId(int $organizationId, string $section, string $detail): ?int
    {
        if ($section === 'artistico' || ! $this->isCounterparty($detail)) {
            return null;
        }

        $vendor = Vendor::query()->firstOrCreate([
            'organization_id' => $organizationId,
            'name' => $detail,
        ]);

        return $vendor->id;
    }

    private function isCounterparty(string $detail): bool
    {
        if ($detail === '') {
            return false;
        }

        if (str_contains($detail, '+') || str_contains($detail, ',') || mb_strlen($detail) > 40) {
            return false;
        }

        return true;
    }

    private function status(string $raw): CostStatus
    {
        $text = strtr(mb_strtolower(trim($raw)), ['ç' => 'c', 'ã' => 'a', 'á' => 'a']);

        return match (true) {
            $text === 'ok' => CostStatus::Contracted,
            str_contains($text, 'procur') => CostStatus::Seeking,
            str_contains($text, 'negoc') => CostStatus::Negotiating,
            default => CostStatus::Planned,
        };
    }

    private function category(string $section, string $item): string
    {
        $text = strtr(mb_strtolower($item), [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a',
            'é' => 'e', 'ê' => 'e', 'í' => 'i',
            'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ç' => 'c',
        ]);

        return match ($section) {
            'artistico' => 'artistas',
            'staff' => match (true) {
                str_contains($text, 'seguranca'), str_contains($text, 'bombeiro'), str_contains($text, 'ambulancia'), str_contains($text, 'policia') => 'seguranca',
                str_contains($text, 'frete') => 'transporte',
                default => 'staff',
            },
            'compras' => 'compras',
            'equipamentos' => str_contains($text, 'luz') ? 'luz' : 'som',
            'servicos' => match (true) {
                str_contains($text, 'fotog') => 'fotografia',
                str_contains($text, 'film'), str_contains($text, 'video') => 'video',
                str_contains($text, 'impress') => 'marketing',
                default => 'producao',
            },
            'material' => 'material',
            'divulgacao' => 'marketing',
            'bebidas' => 'bar',
            'logistica' => str_contains($text, 'aliment') ? 'alimentacao' : 'venue',
            default => 'outros',
        };
    }
}

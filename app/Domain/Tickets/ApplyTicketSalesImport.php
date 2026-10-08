<?php

namespace App\Domain\Tickets;

use App\Models\Event;
use App\Models\TicketTier;
use Illuminate\Support\Facades\DB;

final class ApplyTicketSalesImport
{
    /**
     * @param  list<ImportedTierSales>  $imports
     */
    public function apply(Event $event, array $imports): TicketImportResult
    {
        return DB::transaction(function () use ($event, $imports): TicketImportResult {
            $created = [];
            $updated = [];
            $totalTickets = 0;
            $maxSort = (int) $event->ticketTiers()->max('sort_order');

            foreach ($imports as $import) {
                $totalTickets += $import->soldCount;

                $tier = $event->ticketTiers()
                    ->get()
                    ->first(fn (TicketTier $candidate): bool => mb_strtolower($candidate->name) === mb_strtolower($import->name));

                if ($tier instanceof TicketTier) {
                    $tier->update([
                        'sold_quantity' => $import->soldCount,
                        'quantity' => max($tier->quantity, $import->soldCount),
                        'goal' => max($tier->goal, $import->soldCount),
                        'price' => $import->priceCents > 0 ? $import->priceCents : $tier->price,
                        'starts_at' => $import->startsAt ?? $tier->starts_at,
                        'ends_at' => $import->endsAt ?? $tier->ends_at,
                    ]);
                    $updated[] = $tier->name;

                    continue;
                }

                $maxSort++;

                $event->ticketTiers()->create([
                    'name' => $import->name,
                    'price' => $import->priceCents,
                    'quantity' => $import->soldCount,
                    'goal' => $import->soldCount,
                    'sold_quantity' => $import->soldCount,
                    'starts_at' => $import->startsAt,
                    'ends_at' => $import->endsAt,
                    'sort_order' => $maxSort,
                ]);

                $created[] = $import->name;
            }

            return new TicketImportResult(
                totalTickets: $totalTickets,
                tiersCreated: count($created),
                tiersUpdated: count($updated),
                tierNamesCreated: $created,
                tierNamesUpdated: $updated,
            );
        });
    }
}

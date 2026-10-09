<?php

namespace Tests\Feature;

use App\Domain\Events\ImportCostWorkbook;
use App\Domain\Events\OfficialCatalog;
use App\Domain\Events\ReadCostWorkbook;
use App\Domain\Finance\EventFinanceReader;
use App\Enums\BookingStatus;
use App\Enums\CostStatus;
use App\Enums\EventType;
use App\Livewire\Finance\BudgetBoard;
use App\Livewire\Tickets\TicketBoard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CruaCostImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_crua_sheet_becomes_a_party_with_costs_and_ticket_hypotheses(): void
    {
        OfficialCatalog::sync();
        $workbook = (new ReadCostWorkbook)->fromPath(base_path('tests/fixtures/crua-custos.xlsx'));

        $this->assertSame('Crua', $workbook->name);
        $this->assertSame('2026-11-28', $workbook->startsOn);
        $this->assertSame('CENTRO', $workbook->venue);
        $this->assertSame(440, $workbook->capacity);
        $this->assertCount(54, $workbook->lines);
        $this->assertCount(13, $workbook->tiers);
        $this->assertContains('Promoters', $workbook->courtesyLists);

        $user = User::factory()->create();
        $event = app(ImportCostWorkbook::class)->handle($user, $workbook);

        $this->assertSame(EventType::Party, $event->type);
        $this->assertSame(440, $event->capacity);
        $this->assertSame(54, $event->budgetItems()->count());
        $this->assertSame(5_840_900, (int) $event->budgetItems()->sum('estimated_amount'));
        $this->assertSame(5, $event->bookings()->count());

        $eli = $event->budgetItems()->where('description', 'Eli Iwasa')->first();
        $this->assertNotNull($eli);
        $this->assertSame(CostStatus::Seeking, $eli->status);
        $this->assertNull($eli->contracted_amount);
        $this->assertSame(BookingStatus::Inquiry, $eli->booking?->status);

        $cups = $event->budgetItems()->where('description', 'copos')->first();
        $this->assertNotNull($cups);
        $this->assertSame(1000, $cups->quantity);
        $this->assertSame(345, $cups->unit_amount);
        $this->assertSame('EcoCopo', $cups->detail);
        $this->assertSame('EcoCopo', $cups->vendor?->name);
        $this->assertSame('compras', $cups->category?->slug);

        $vodka = $event->budgetItems()->where('description', 'Vodka Smirnoff')->first();
        $this->assertSame(CostStatus::Negotiating, $vodka?->status);

        $forastiere = $event->budgetItems()->where('description', 'Forastiere')->first();
        $this->assertSame(100_000, $forastiere?->estimated_amount);

        $presale = $event->ticketTiers()->where('name', 'Pre-venda')->first();
        $this->assertSame(3_500, $presale?->price);
        $this->assertSame(30, $presale?->goal);
        $this->assertSame(0, $presale?->sold_quantity);
        $this->assertSame(9000, $presale?->payout_basis_points);

        $promo = $event->ticketTiers()->where('name', 'Promocional 1')->first();
        $this->assertSame(10000, $promo?->payout_basis_points);

        $this->assertSame(3_300_000, (int) $event->revenues()->where('category', 'bar')->value('expected_amount'));

        $statement = app(EventFinanceReader::class)->statement($event->fresh());
        $this->assertSame(2_232_000, $statement->expectedTicketRevenue);

        Livewire::actingAs($user)
            ->test(BudgetBoard::class, ['event' => $event])
            ->assertSee('Eli Iwasa')
            ->assertSee('EcoCopo')
            ->assertSee('Quantidade')
            ->assertSee('PIX / CPF')
            ->assertSee('À procura');

        Livewire::actingAs($user)
            ->test(TicketBoard::class, ['event' => $event])
            ->assertSee('Pre-venda')
            ->assertSee('repasse 90%')
            ->assertSee('Repasse da bilheteria');
    }
}

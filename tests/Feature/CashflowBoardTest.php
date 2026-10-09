<?php

namespace Tests\Feature;

use App\Domain\Events\CreateEvent;
use App\Enums\CostStatus;
use App\Enums\EventType;
use App\Enums\PaymentStatus;
use App\Livewire\Finance\CashflowBoard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CashflowBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashflow_map_places_payments_on_their_day(): void
    {
        $user = User::factory()->create();
        $event = app(CreateEvent::class)->handle($user, [
            'name' => 'Crua',
            'type' => EventType::Party,
            'starts_at' => now()->addDays(10),
        ]);
        $budget = $event->budget;

        $sound = $event->budgetItems()->create([
            'budget_id' => $budget->id,
            'description' => 'Som',
            'estimated_amount' => 100_000,
            'contracted_amount' => 100_000,
            'due_on' => now()->addDay()->toDateString(),
            'status' => CostStatus::Contracted,
        ]);
        $sound->payments()->create([
            'event_id' => $event->id,
            'amount' => 40_000,
            'paid_on' => now()->toDateString(),
            'status' => PaymentStatus::Paid,
        ]);
        $event->budgetItems()->create([
            'budget_id' => $budget->id,
            'description' => 'Luz',
            'estimated_amount' => 20_000,
            'status' => CostStatus::Planned,
        ]);

        Livewire::actingAs($user)
            ->test(CashflowBoard::class, ['event' => $event])
            ->assertSee('Mapa do fluxo')
            ->assertSee('Por tempo')
            ->assertSee('W-1')
            ->assertSee('W0')
            ->assertSee('Som')
            ->assertSee('Evolução dos gastos')
            ->set('grain', 'day')
            ->assertSee('D-10')
            ->assertSee('D0')
            ->assertSee('Som')
            ->set('window', '30')
            ->assertDontSee('Luz')
            ->set('window', 'all')
            ->assertSee('Luz')
            ->set('map', 'category')
            ->assertSee('Sem categoria')
            ->assertSee('Evolução dos gastos')
            ->set('map', 'time')
            ->set('grain', 'week')
            ->set('window', 'today')
            ->assertSee('Som')
            ->assertDontSee('Luz');
    }
}

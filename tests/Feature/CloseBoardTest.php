<?php

namespace Tests\Feature;

use App\Domain\Events\CreateEvent;
use App\Enums\CostStatus;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\RevenueStatus;
use App\Livewire\Events\CloseBoard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CloseBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_close_board_compares_plan_and_actual_on_the_grid(): void
    {
        $user = User::factory()->create();
        $event = app(CreateEvent::class)->handle($user, [
            'name' => 'Festa fechamento',
            'type' => EventType::Party,
        ]);

        Livewire::actingAs($user)
            ->test(CloseBoard::class, ['event' => $event])
            ->assertSee('Fechamento')
            ->assertSee('Previsto e real')
            ->assertSee('Extrato previsto')
            ->assertSee('Extrato real')
            ->assertDontSee('Linha')
            ->set('complete', true)
            ->assertSee('Linha')
            ->assertSee('Diferença')
            ->assertDontSee('Extrato previsto')
            ->set('complete', false)
            ->assertSee('Receita bruta')
            ->assertSee('Receita líquida')
            ->assertSee('Resultado no plano')
            ->assertSee('Ticket médio da meta')
            ->assertSee('Alterações recentes')
            ->assertSee('Marcar como finalizado')
            ->call('finish')
            ->assertSee('Finalizado')
            ->assertDontSee('Marcar como finalizado');

        $this->assertSame(EventStatus::Finished, $event->fresh()->status);
    }

    public function test_recent_changes_name_the_item_and_the_diff(): void
    {
        $user = User::factory()->create();
        $event = app(CreateEvent::class)->handle($user, [
            'name' => 'Festa auditoria',
            'type' => EventType::Party,
        ]);

        $event->revenues()->create([
            'category' => 'merch',
            'description' => 'Camisetas',
            'expected_amount' => 500_000,
            'actual_amount' => 0,
            'status' => RevenueStatus::Confirmed,
        ]);

        $item = $event->budgetItems()->create([
            'budget_id' => $event->budget->id,
            'event_id' => $event->id,
            'description' => 'Hostess',
            'estimated_amount' => 80_000,
            'status' => CostStatus::Contracted,
        ]);
        $item->update(['status' => CostStatus::Paid]);

        Livewire::actingAs($user)
            ->test(CloseBoard::class, ['event' => $event])
            ->assertSee('criou a receita Camisetas')
            ->assertSee('previsto')
            ->assertSee('R$ 5.000,00')
            ->assertSee('alterou o custo Hostess')
            ->assertSee('de Contratado para Pago');
    }
}

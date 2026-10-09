<?php

namespace Tests\Feature;

use App\Domain\Events\CreateEvent;
use App\Enums\EventType;
use App\Enums\RevenueStatus;
use App\Livewire\Finance\RevenueBoard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RevenueBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_extra_revenues_render_as_a_table_inside_the_grid(): void
    {
        $user = User::factory()->create();
        $event = app(CreateEvent::class)->handle($user, [
            'name' => 'Festa receitas',
            'type' => EventType::Party,
        ]);

        $event->revenues()->create([
            'category' => 'bar',
            'description' => 'Bar da noite',
            'expected_amount' => 150_000,
            'actual_amount' => 40_000,
            'status' => RevenueStatus::Confirmed,
        ]);
        $event->revenues()->create([
            'category' => 'sponsorship',
            'description' => 'Patrocínio cancelado',
            'expected_amount' => 500_000,
            'actual_amount' => 0,
            'status' => RevenueStatus::Cancelled,
        ]);

        Livewire::actingAs($user)
            ->test(RevenueBoard::class, ['event' => $event])
            ->assertSee('Receitas adicionais')
            ->assertSee('Origem')
            ->assertSee('Previsto')
            ->assertSee('Entrou')
            ->assertSee('Bar da noite')
            ->assertSee('Patrocínio cancelado')
            ->assertSee('R$ 1.500,00')
            ->assertSee('R$ 1.100,00')
            ->assertDontSee('R$ 6.500,00');
    }
}

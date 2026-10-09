<?php

namespace Tests\Feature;

use App\Domain\Events\CreateEvent;
use App\Enums\EventType;
use App\Livewire\Events\ScheduleBoard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScheduleBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_page_shows_the_party_timeflow(): void
    {
        $user = User::factory()->create();
        $event = app(CreateEvent::class)->handle($user, [
            'name' => 'Festa cronograma',
            'type' => EventType::Party,
            'starts_at' => '2026-11-28 00:00:00',
        ]);

        $event->scheduleItems()->create([
            'title' => 'Chegada da estrutura',
            'starts_at' => '2026-11-27 16:00:00',
            'duration_minutes' => 120,
            'sort_order' => 0,
        ]);
        $event->scheduleItems()->create([
            'title' => 'Abertura da casa',
            'starts_at' => '2026-11-28 00:00:00',
            'duration_minutes' => 120,
            'sort_order' => 1,
        ]);
        $event->scheduleItems()->create([
            'title' => 'Briefing solto',
            'starts_at' => null,
            'sort_order' => 2,
        ]);

        Livewire::actingAs($user)
            ->test(ScheduleBoard::class, ['event' => $event])
            ->assertSee('Cronograma')
            ->assertSee('Timeflow')
            ->assertSee('Portas')
            ->assertSee('00:00')
            ->assertSee('T-8h')
            ->assertSee('T0')
            ->assertSee('Véspera')
            ->assertSee('Dia da festa')
            ->assertSee('Chegada da estrutura')
            ->assertSee('Abertura da casa')
            ->assertSee('10h')
            ->assertSee('Briefing solto')
            ->assertSee('1 sem hora')
            ->call('create')
            ->assertSet('showForm', true);
    }
}

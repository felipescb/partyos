<?php

namespace Tests\Feature;

use App\Domain\Events\CreateEvent;
use App\Enums\BookingStatus;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Livewire\Artists\LineupBoard;
use App\Models\Artist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LineupBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_lineup_is_a_grid_of_statuses_and_a_table(): void
    {
        $user = User::factory()->create();
        $event = app(CreateEvent::class)->handle($user, [
            'name' => 'Festa da casa',
            'type' => EventType::Party,
            'status' => EventStatus::Planning,
        ]);
        $lia = Artist::query()->create([
            'organization_id' => $event->organization_id,
            'stage_name' => 'DJ Lia',
        ]);
        $nico = Artist::query()->create([
            'organization_id' => $event->organization_id,
            'stage_name' => 'Nico',
        ]);
        $event->bookings()->create([
            'artist_id' => $lia->id,
            'status' => BookingStatus::Confirmed,
        ]);
        $event->bookings()->create([
            'artist_id' => $nico->id,
            'status' => BookingStatus::Inquiry,
        ]);

        $this->actingAs($user)
            ->get(route('events.artists', $event))
            ->assertOk()
            ->assertSee('broker-grid-lineup', false)
            ->assertSee('broker-lineup-columns', false)
            ->assertSee('Relacionamento')
            ->assertSee('Todos')
            ->assertSee('Confirmado')
            ->assertSee('Consulta')
            ->assertDontSee("selectList('negotiating')", false)
            ->assertSee('DJ Lia')
            ->assertSee('Nico');

        Livewire::actingAs($user)
            ->test(LineupBoard::class, ['event' => $event])
            ->call('selectList', 'confirmed')
            ->assertSee('Editar DJ Lia', false)
            ->assertDontSee('Editar Nico', false)
            ->call('create')
            ->assertSet('status', 'confirmed');
    }
}

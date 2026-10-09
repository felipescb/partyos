<?php

namespace Tests\Feature;

use App\Domain\Events\CreateEvent;
use App\Enums\BookingStatus;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Livewire\Artists\ArtistDirectory;
use App\Models\Artist;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ArtistDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_artist_directory_filters_like_a_crm(): void
    {
        $user = User::factory()->create();
        $event = app(CreateEvent::class)->handle($user, [
            'name' => 'Festa da casa',
            'type' => EventType::Party,
            'status' => EventStatus::Planning,
        ]);
        $user->refresh();

        $lia = Artist::query()->create([
            'organization_id' => $user->current_organization_id,
            'stage_name' => 'DJ Lia',
            'agency' => 'Casa',
            'phone' => '11999990000',
        ]);
        Artist::query()->create([
            'organization_id' => $user->current_organization_id,
            'stage_name' => 'Nico',
        ]);
        $item = $event->budgetItems()->create([
            'budget_id' => $event->budget->id,
            'description' => 'Cachê',
            'estimated_amount' => 200000,
            'contracted_amount' => 200000,
        ]);
        $event->bookings()->create([
            'artist_id' => $lia->id,
            'budget_item_id' => $item->id,
            'status' => BookingStatus::Confirmed,
        ]);

        $this->actingAs($user)
            ->get(route('artists.index'))
            ->assertOk()
            ->assertSee('broker-grid-artists', false)
            ->assertSee('broker-artist-columns', false)
            ->assertSee('Já tocou')
            ->assertSee('Com telefone')
            ->assertSee('Casa')
            ->assertSee('DJ Lia')
            ->assertSee('Nico')
            ->assertSee('tile-dock', false)
            ->assertDontSee('Seções do evento', false);

        Livewire::actingAs($user)
            ->test(ArtistDirectory::class)
            ->set('list', 'Casa')
            ->assertSee('Editar DJ Lia', false)
            ->assertDontSee('Editar Nico', false)
            ->call('create')
            ->assertSet('agency', 'Casa')
            ->set('showForm', false)
            ->set('list', 'all')
            ->set('work', 'idle')
            ->assertDontSee('Editar DJ Lia', false)
            ->assertSee('Editar Nico', false)
            ->set('work', 'all')
            ->set('contact', 'missing')
            ->assertDontSee('Editar DJ Lia', false)
            ->assertSee('Editar Nico', false);
    }
}

<?php

namespace Tests\Feature;

use App\Domain\Events\CreateEvent;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\GuestCategory;
use App\Enums\RsvpStatus;
use App\Livewire\Guests\GuestBoard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class GuestBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_guest_page_is_a_grid_of_lists(): void
    {
        $user = User::factory()->create();
        $event = app(CreateEvent::class)->handle($user, [
            'name' => 'Festa da casa',
            'type' => EventType::Party,
            'status' => EventStatus::Planning,
        ]);
        $event->guests()->create([
            'name' => 'Lia',
            'category' => GuestCategory::Vip,
            'rsvp_status' => RsvpStatus::Confirmed,
            'plus_ones' => 1,
        ]);
        $event->guests()->create([
            'name' => 'Nico',
            'category' => GuestCategory::Press,
            'rsvp_status' => RsvpStatus::NotSent,
        ]);

        $this->actingAs($user)
            ->get(route('events.guests', $event))
            ->assertOk()
            ->assertSee('broker-grid-guests', false)
            ->assertSee('Todas')
            ->assertSee('VIP')
            ->assertSee('Imprensa')
            ->assertDontSee("selectList('artist')", false)
            ->assertSee('aria-label="Baixar CSV"', false)
            ->assertSee('aria-label="Subir CSV"', false)
            ->assertSee('Lia')
            ->assertSee('Nico')
            ->assertSee('tile-dock', false)
            ->assertSee('Seções do evento', false);

        Livewire::actingAs($user)
            ->test(GuestBoard::class, ['event' => $event])
            ->call('selectList', 'vip')
            ->assertSee('Lia')
            ->assertDontSee('Nico')
            ->assertSet('category', 'guest')
            ->call('create')
            ->assertSet('category', 'vip');
    }

    public function test_a_csv_updates_the_open_list_and_downloads_it(): void
    {
        $user = User::factory()->create();
        $event = app(CreateEvent::class)->handle($user, [
            'name' => 'Festa da casa',
            'type' => EventType::Party,
            'status' => EventStatus::Planning,
        ]);
        $event->guests()->create([
            'name' => 'Lia',
            'email' => 'lia@festa.test',
            'category' => GuestCategory::Guest,
            'rsvp_status' => RsvpStatus::NotSent,
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'lista.csv',
            "nome;email;lista;rsvp\nLia;lia@festa.test;VIP;Confirmado\nRui;rui@festa.test;;Enviado\n",
        );

        Livewire::actingAs($user)
            ->test(GuestBoard::class, ['event' => $event])
            ->set('importFile', $file)
            ->call('importList')
            ->assertHasNoErrors()
            ->call('exportList')
            ->assertFileDownloaded('convidados-'.$event->slug.'.csv');

        $this->assertDatabaseHas('guests', [
            'event_id' => $event->id,
            'email' => 'lia@festa.test',
            'category' => GuestCategory::Vip->value,
            'rsvp_status' => RsvpStatus::Confirmed->value,
        ]);
        $this->assertDatabaseHas('guests', [
            'event_id' => $event->id,
            'name' => 'Rui',
            'category' => GuestCategory::Guest->value,
            'rsvp_status' => RsvpStatus::Sent->value,
        ]);
    }
}

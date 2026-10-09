<?php

namespace Tests\Feature;

use App\Domain\Events\CreateEvent;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Livewire\Vendors\VendorDirectory;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VendorDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_vendor_directory_filters_like_a_crm(): void
    {
        $user = User::factory()->create();
        $event = app(CreateEvent::class)->handle($user, [
            'name' => 'Festa da casa',
            'type' => EventType::Party,
            'status' => EventStatus::Planning,
        ]);
        $user->refresh();

        $sound = Vendor::query()->create([
            'organization_id' => $user->current_organization_id,
            'name' => 'Casa de Som',
            'category' => 'Som',
            'whatsapp' => '11999990000',
        ]);
        Vendor::query()->create([
            'organization_id' => $user->current_organization_id,
            'name' => 'Foto Luz',
            'category' => 'Fotografia',
        ]);
        $event->budgetItems()->create([
            'budget_id' => $event->budget->id,
            'description' => 'PA',
            'vendor_id' => $sound->id,
            'estimated_amount' => 150000,
            'contracted_amount' => 150000,
        ]);

        $this->actingAs($user)
            ->get(route('vendors.index'))
            ->assertOk()
            ->assertSee('broker-grid-vendors', false)
            ->assertSee('broker-vendor-columns', false)
            ->assertDontSee('broker-guest-list', false)
            ->assertSee('Já trabalhou')
            ->assertSee('Com WhatsApp')
            ->assertSee('Som')
            ->assertSee('Fotografia')
            ->assertSee('Casa de Som')
            ->assertSee('Foto Luz')
            ->assertSee('tile-dock', false)
            ->assertDontSee('Seções do evento', false);

        Livewire::actingAs($user)
            ->test(VendorDirectory::class)
            ->set('list', 'Som')
            ->assertSee('Editar Casa de Som', false)
            ->assertDontSee('Editar Foto Luz', false)
            ->call('create')
            ->assertSet('category', 'Som')
            ->set('showForm', false)
            ->set('list', 'all')
            ->set('work', 'idle')
            ->assertDontSee('Editar Casa de Som', false)
            ->assertSee('Editar Foto Luz', false)
            ->set('work', 'all')
            ->set('contact', 'missing')
            ->assertDontSee('Editar Casa de Som', false)
            ->assertSee('Editar Foto Luz', false);
    }
}

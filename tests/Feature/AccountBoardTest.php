<?php

namespace Tests\Feature;

use App\Domain\Events\CreateEvent;
use App\Enums\AccountKind;
use App\Enums\EventRole;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\OrgRole;
use App\Livewire\Admin\AccountBoard;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AccountBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_the_platform_admin_opens_the_accounts_page_from_the_admin_menu(): void
    {
        $member = User::factory()->create();
        $admin = User::factory()->platformAdmin()->create();

        $this->actingAs($member)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('data-test="admin-accounts-link"', false);

        $this->actingAs($member)
            ->get(route('admin.accounts'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-test="admin-accounts-link"', false)
            ->assertSee('Contas');

        $this->actingAs($admin)
            ->get(route('admin.accounts'))
            ->assertOk()
            ->assertSee('Uma conta cria os próprios eventos e entra nos dos outros')
            ->assertSee($admin->email);
    }

    public function test_the_admin_creates_an_organizer_and_a_participant(): void
    {
        $admin = User::factory()->platformAdmin()->create();

        Livewire::actingAs($admin)
            ->test(AccountBoard::class)
            ->call('create')
            ->set('name', 'Casa Norte')
            ->set('email', 'Casa@Norte.test')
            ->set('password', 'senha-inicial-123')
            ->set('password_confirmation', 'senha-inicial-123')
            ->set('kind', AccountKind::Organizer->value)
            ->call('save')
            ->assertHasNoErrors();

        $organizer = User::query()->where('email', 'casa@norte.test')->first();

        $this->assertInstanceOf(User::class, $organizer);
        $this->assertSame(AccountKind::Organizer, $organizer->account_kind);
        $this->assertNotNull($organizer->email_verified_at);
        $this->assertTrue(Hash::check('senha-inicial-123', $organizer->password));
        $this->assertFalse($organizer->isPlatformAdmin());
        $this->assertSame(OrgRole::Owner, $organizer->organization?->roleFor($organizer));

        Livewire::actingAs($admin)
            ->test(AccountBoard::class)
            ->set('name', 'Lia')
            ->set('email', 'lia@partyos.test')
            ->set('password', 'senha-inicial-123')
            ->set('password_confirmation', 'diferente')
            ->set('kind', AccountKind::Participant->value)
            ->call('save')
            ->assertHasErrors('password');

        $this->assertNull(User::query()->where('email', 'lia@partyos.test')->first());

        Livewire::actingAs($admin)
            ->test(AccountBoard::class)
            ->set('name', 'Lia')
            ->set('email', 'lia@partyos.test')
            ->set('password', 'senha-inicial-123')
            ->set('password_confirmation', 'senha-inicial-123')
            ->set('kind', AccountKind::Participant->value)
            ->call('save')
            ->assertHasNoErrors();

        $participant = User::query()->where('email', 'lia@partyos.test')->first();

        $this->assertInstanceOf(User::class, $participant);
        $this->assertSame(AccountKind::Participant, $participant->account_kind);
        $this->assertNull($participant->current_organization_id);
        $this->assertSame(0, $participant->organizations()->count());
        $this->assertNotNull($participant->email_verified_at);

        Livewire::actingAs($admin)
            ->test(AccountBoard::class)
            ->set('name', 'Outra Lia')
            ->set('email', 'lia@partyos.test')
            ->set('password', 'senha-inicial-123')
            ->set('password_confirmation', 'senha-inicial-123')
            ->set('kind', AccountKind::Participant->value)
            ->call('save')
            ->assertHasErrors('email');
    }

    public function test_a_participant_sees_invited_events_and_cannot_open_a_producer_tool(): void
    {
        $organizer = User::factory()->create();
        $event = app(CreateEvent::class)->handle($organizer, [
            'name' => 'Festa da casa',
            'type' => EventType::Party,
            'status' => EventStatus::Planning,
        ]);
        $other = app(CreateEvent::class)->handle($organizer, [
            'name' => 'Outra festa',
            'type' => EventType::Party,
            'status' => EventStatus::Planning,
        ]);

        $participant = User::factory()->participant()->create();
        $event->members()->attach($participant->id, ['role' => EventRole::Staff->value]);

        $this->actingAs($participant)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Festa da casa')
            ->assertDontSee('Outra festa')
            ->assertDontSee('Novo evento')
            ->assertDontSee('Fornecedores')
            ->assertDontSee('Quando alguém te chamar para um evento, ele aparece aqui.');

        $this->actingAs($participant)
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('Festa da casa');

        $this->actingAs($participant)
            ->get(route('events.show', $other))
            ->assertNotFound();

        $this->actingAs($participant)->get(route('events.create'))->assertForbidden();
        $this->actingAs($participant)->get(route('vendors.index'))->assertForbidden();
        $this->actingAs($participant)->get(route('artists.index'))->assertForbidden();

        $this->assertSame(1, Organization::query()->count());
    }

    public function test_an_organizer_sees_events_they_create_and_events_they_join(): void
    {
        $producer = User::factory()->create();
        app(CreateEvent::class)->handle($producer, [
            'name' => 'Festa da casa',
            'type' => EventType::Party,
            'status' => EventStatus::Planning,
        ]);

        $host = User::factory()->create();
        $invited = app(CreateEvent::class)->handle($host, [
            'name' => 'Festa do vizinho',
            'type' => EventType::Party,
            'status' => EventStatus::Planning,
        ]);
        app(CreateEvent::class)->handle($host, [
            'name' => 'Festa fechada',
            'type' => EventType::Party,
            'status' => EventStatus::Planning,
        ]);
        $invited->members()->attach($producer->id, ['role' => EventRole::Production->value]);

        $this->actingAs($producer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Festa da casa')
            ->assertSee('Festa do vizinho')
            ->assertDontSee('Festa fechada')
            ->assertSee('Novo evento');

        $this->actingAs($producer)
            ->get(route('events.show', $invited))
            ->assertOk()
            ->assertSee('Festa do vizinho');
    }
}

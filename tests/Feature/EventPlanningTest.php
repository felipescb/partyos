<?php

namespace Tests\Feature;

use App\Domain\Events\CreateEvent;
use App\Domain\Events\DuplicateEvent;
use App\Domain\Events\DuplicateSelection;
use App\Domain\Events\OfficialCatalog;
use App\Enums\CostStatus;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\PaymentStatus;
use App\Livewire\Events\EventForm;
use App\Livewire\Events\EventOverview;
use App\Livewire\Tasks\TaskBoard;
use App\Livewire\Finance\BudgetBoard;
use App\Models\Event;
use App\Models\EventTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EventPlanningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        OfficialCatalog::sync();
    }

    public function test_a_producer_creates_an_event_from_a_template(): void
    {
        $user = User::factory()->create();
        $templateId = (string) EventTemplate::query()->where('slug', 'festa')->value('id');

        Livewire::actingAs($user)
            ->test(EventForm::class)
            ->set('name', 'Festa X — Outubro')
            ->set('type', EventType::Party->value)
            ->set('templateId', $templateId)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $event = Event::query()->where('name', 'Festa X — Outubro')->first();
        $this->assertNotNull($event);
        $this->assertTrue($event->tasks()->where('title', 'Definir o local ou venue')->exists());
        $this->assertNotNull($event->budget);
        $this->assertTrue($user->can('manageFinance', $event));
    }

    public function test_another_producer_cannot_open_the_event(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $event = app(CreateEvent::class)->handle($owner, [
            'name' => 'Festa fechada',
            'type' => EventType::Party,
            'status' => EventStatus::Planning,
        ]);

        $this->actingAs($intruder)->get(route('events.show', $event))->assertNotFound();
    }

    public function test_paid_amount_is_the_sum_of_payments_and_is_not_copied(): void
    {
        $user = User::factory()->create();
        $event = app(CreateEvent::class)->handle($user, [
            'name' => 'Festa X — Outubro',
            'type' => EventType::Party,
            'starts_at' => '2026-10-17 22:00:00',
        ]);

        $categoryId = OfficialCatalog::categoryId('fotografia');
        $item = $event->budgetItems()->create([
            'budget_id' => $event->budget->id,
            'event_id' => $event->id,
            'cost_category_id' => $categoryId,
            'description' => 'Fotografia',
            'estimated_amount' => 100000,
            'contracted_amount' => 80000,
            'status' => CostStatus::Contracted,
        ]);
        $item->payments()->create([
            'event_id' => $event->id,
            'amount' => 40000,
            'paid_on' => '2026-10-01',
            'status' => PaymentStatus::Paid,
        ]);
        $item->refreshPaymentStatus();
        $this->assertSame(CostStatus::PartiallyPaid, $item->fresh()->status);
        $this->assertSame(40000, $item->fresh()->paidAmount());

        $copy = app(DuplicateEvent::class)->handle($user, $event->fresh(), [
            'name' => 'Festa X — Novembro',
            'starts_at' => '2026-11-21 22:00:00',
        ], new DuplicateSelection(guests: false));

        $copied = $copy->budgetItems()->where('description', 'Fotografia')->first();
        $this->assertNotNull($copied);
        $this->assertSame(80000, $copied->contracted_amount);
        $this->assertSame(0, $copied->payments()->count());
        $this->assertSame(CostStatus::Contracted, $copied->status);
    }

    public function test_cost_screen_rejects_an_empty_description(): void
    {
        $user = User::factory()->create();
        $event = app(CreateEvent::class)->handle($user, [
            'name' => 'Show',
            'type' => EventType::Show,
        ]);

        Livewire::actingAs($user)
            ->test(BudgetBoard::class, ['event' => $event])
            ->call('create')
            ->set('description', '')
            ->set('estimated', '1000')
            ->call('save')
            ->assertHasErrors(['description']);
    }

    public function test_event_overview_ticket_grid_shows_sales_and_expands(): void
    {
        $user = User::factory()->create();
        $event = app(CreateEvent::class)->handle($user, [
            'name' => 'Festa ingressos',
            'type' => EventType::Party,
        ]);

        $event->ticketTiers()->create([
            'name' => '1º lote',
            'price' => 5000,
            'quantity' => 100,
            'goal' => 80,
            'sold_quantity' => 20,
            'sort_order' => 1,
        ]);

        Livewire::actingAs($user)
            ->test(EventOverview::class, ['event' => $event])
            ->assertSee('Financeiro')
            ->assertSee('20')
            ->assertSee('/80')
            ->call('toggleTicketsGrid')
            ->assertSet('ticketsGridExpanded', true)
            ->assertSee('Ir para o controle de ingressos')
            ->assertSee('1º lote');
    }

    public function test_owner_can_add_team_member_from_event_overview(): void
    {
        $owner = User::factory()->create();
        $collaborator = User::factory()->create(['email' => 'prod@example.com']);
        $event = app(CreateEvent::class)->handle($owner, [
            'name' => 'Festa equipe',
            'type' => EventType::Party,
        ]);

        Livewire::actingAs($owner)
            ->test(EventOverview::class, ['event' => $event])
            ->call('openTeamModal')
            ->set('teamInviteEmail', $collaborator->email)
            ->set('teamInviteRole', 'production')
            ->call('inviteTeamMember')
            ->assertHasNoErrors();

        $this->assertTrue(
            $event->members()->whereKey($collaborator->id)->wherePivot('role', 'production')->exists()
        );
    }

    public function test_task_notebook_supports_inline_create_and_edit(): void
    {
        $user = User::factory()->create();
        $event = app(CreateEvent::class)->handle($user, [
            'name' => 'Festa tarefas',
            'type' => EventType::Party,
        ]);

        Livewire::actingAs($user)
            ->test(TaskBoard::class, ['event' => $event])
            ->set('newTaskTitle', 'Reservar venue')
            ->call('createFromDraft')
            ->assertHasNoErrors();

        $task = $event->tasks()->where('title', 'Reservar venue')->first();
        $this->assertNotNull($task);

        Livewire::actingAs($user)
            ->test(TaskBoard::class, ['event' => $event])
            ->call('updateTaskTitle', $task->id, 'Reservar galpão')
            ->call('updateTaskStatus', $task->id, 'doing')
            ->call('toggleDone', $task->id);

        $task->refresh();
        $this->assertSame('Reservar galpão', $task->title);
        $this->assertSame('done', $task->status->value);
    }
}

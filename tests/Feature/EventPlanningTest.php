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
use App\Livewire\Finance\BudgetBoard;
use App\Livewire\Finance\DistributionBoard;
use App\Livewire\Tasks\TaskBoard;
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

    public function test_cost_sheet_edits_a_cell_in_place(): void
    {
        $user = User::factory()->create();
        $event = app(CreateEvent::class)->handle($user, [
            'name' => 'Show',
            'type' => EventType::Show,
        ]);
        $item = $event->budgetItems()->create([
            'budget_id' => $event->budget->id,
            'description' => 'Som',
            'quantity' => 1,
            'estimated_amount' => 10000,
            'status' => CostStatus::Planned,
        ]);

        Livewire::actingAs($user)
            ->test(BudgetBoard::class, ['event' => $event])
            ->assertSee('Quantidade')
            ->assertSee('PIX / CPF')
            ->assertSee('Tipo / nome')
            ->call('updateCost', $item->id, 'quantity', '3')
            ->call('updateCost', $item->id, 'detail', 'Core')
            ->call('updateCost', $item->id, 'status', CostStatus::Contracted->value);

        $item->refresh();
        $this->assertSame(3, $item->quantity);
        $this->assertSame('Core', $item->detail);
        $this->assertSame(CostStatus::Contracted, $item->status);
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

    public function test_event_overview_cashflow_grid_samples_upcoming_days_and_weeks(): void
    {
        $user = User::factory()->create();
        $event = app(CreateEvent::class)->handle($user, [
            'name' => 'Festa fluxo',
            'type' => EventType::Party,
            'starts_at' => now()->addDays(40),
        ]);
        $budget = $event->budget;

        $past = $event->budgetItems()->create([
            'budget_id' => $budget->id,
            'description' => 'Ja pago ontem',
            'estimated_amount' => 1_000,
            'contracted_amount' => 1_000,
            'due_on' => now()->subDay()->toDateString(),
            'status' => CostStatus::Paid,
        ]);
        $past->payments()->create([
            'event_id' => $event->id,
            'amount' => 1_000,
            'paid_on' => now()->subDay()->toDateString(),
            'status' => PaymentStatus::Paid,
        ]);

        foreach (range(1, 6) as $day) {
            $event->budgetItems()->create([
                'budget_id' => $budget->id,
                'description' => 'Dia perto '.$day,
                'estimated_amount' => 1_000,
                'due_on' => now()->addDays($day)->toDateString(),
                'status' => CostStatus::Planned,
            ]);
        }

        foreach (range(1, 6) as $week) {
            $event->budgetItems()->create([
                'budget_id' => $budget->id,
                'description' => 'Semana longe '.$week,
                'estimated_amount' => 2_000,
                'due_on' => now()->addDays(9 + ($week * 7))->toDateString(),
                'status' => CostStatus::Planned,
            ]);
        }

        Livewire::actingAs($user)
            ->test(EventOverview::class, ['event' => $event])
            ->assertSee('Fluxo')
            ->assertSee('Ampliar fluxo')
            ->assertDontSee('Ja pago ontem')
            ->assertDontSee('Dia perto 6')
            ->assertDontSee('Semana longe 6')
            ->call('toggleCashflowGrid')
            ->assertSet('cashflowGridExpanded', true)
            ->assertSee('Ver o fluxo todo')
            ->assertSee('Próximos dias')
            ->assertSee('Dia perto 1')
            ->assertSee('Próximas semanas')
            ->assertSee('Semana longe 1')
            ->assertDontSee('Dia perto 5')
            ->assertDontSee('Semana longe 5');
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

    public function test_event_overview_operation_module_shows_a_mini_timeflow(): void
    {
        $user = User::factory()->create();
        $event = app(CreateEvent::class)->handle($user, [
            'name' => 'Festa operacao',
            'type' => EventType::Party,
            'starts_at' => '2026-11-28 00:00:00',
        ]);

        foreach ([
            ['Chegada da estrutura', '2026-11-27 16:00:00', 120, 0],
            ['Montagem', '2026-11-27 18:00:00', 240, 1],
            ['Soundcheck', '2026-11-27 22:30:00', 60, 2],
            ['Abertura da casa', '2026-11-28 00:00:00', 120, 3],
            ['Primeiro artista', '2026-11-28 02:00:00', 75, 4],
            ['Pico da pista', '2026-11-28 04:00:00', 180, 5],
            ['Desmontagem', '2026-11-28 08:30:00', 90, 6],
        ] as [$title, $startsAt, $duration, $order]) {
            $event->scheduleItems()->create([
                'title' => $title,
                'starts_at' => $startsAt,
                'duration_minutes' => $duration,
                'sort_order' => $order,
            ]);
        }

        Livewire::actingAs($user)
            ->test(EventOverview::class, ['event' => $event])
            ->assertSee('Horários')
            ->assertSee('Ver todas · 7')
            ->assertSee('T-8h')
            ->assertSee('T0')
            ->assertSee('Chegada da estrutura')
            ->assertSee('Abertura da casa')
            ->assertSee('Primeiro artista')
            ->assertSee('Pico da pista')
            ->assertDontSee('Desmontagem');
    }

    public function test_edit_screen_opens_event_configuration_instead_of_the_new_event_flow(): void
    {
        $user = User::factory()->create();
        $event = app(CreateEvent::class)->handle($user, [
            'name' => 'Festa da casa',
            'type' => EventType::Party,
            'status' => EventStatus::Planning,
            'venue_name' => 'Galpão',
            'city' => 'São Paulo',
            'capacity' => 400,
        ]);

        $this->actingAs($user)
            ->get(route('events.edit', $event))
            ->assertOk()
            ->assertSee('Configuração')
            ->assertSee('Festa da casa')
            ->assertSee('broker-grid-config')
            ->assertSee('Essencial')
            ->assertSee('Quando')
            ->assertSee('Onde')
            ->assertSee('Duração da festa')
            ->assertSee('← Quadro')
            ->assertSee('aria-label="Salvar"', false)
            ->assertSee('aria-label="Excluir"', false)
            ->assertSee('Excluir este evento?')
            ->assertDontSee('Montar evento')
            ->assertDontSee('Escolha um modelo');

        Livewire::actingAs($user)
            ->test(EventForm::class, ['event' => $event])
            ->assertSet('name', 'Festa da casa')
            ->assertSet('city', 'São Paulo')
            ->assertSet('status', EventStatus::Planning->value)
            ->set('name', 'Festa da casa 2')
            ->set('city', 'Campinas')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('events.show', $event));

        $event->refresh();
        $this->assertSame('Festa da casa 2', $event->name);
        $this->assertSame('Campinas', $event->city);
    }

    public function test_fees_and_shares_are_configured_on_the_event_page(): void
    {
        $user = User::factory()->create();
        $event = app(CreateEvent::class)->handle($user, [
            'name' => 'Festa da casa',
            'type' => EventType::Party,
            'status' => EventStatus::Planning,
        ]);

        $this->actingAs($user)
            ->get(route('events.edit', $event))
            ->assertOk()
            ->assertSee('Taxas')
            ->assertSee('Quem fica com o quê')
            ->assertSee('Adicionar taxa')
            ->assertSee('Adicionar divisão');

        $this->actingAs($user)
            ->get(route('events.show', $event))
            ->assertOk()
            ->assertDontSee('Taxas e quem fica com o quê')
            ->assertDontSee(route('events.distribution', $event), false);

        $this->actingAs($user)
            ->get(route('events.distribution', $event))
            ->assertRedirect(route('events.edit', $event));

        Livewire::actingAs($user)
            ->test(DistributionBoard::class, ['event' => $event])
            ->set('feeName', 'Bilheteria')
            ->set('feeApplies', 'tickets')
            ->set('feeKind', 'percent')
            ->set('feeValue', '10')
            ->call('addFee')
            ->assertHasNoErrors()
            ->set('beneficiary', 'Casa')
            ->set('shareApplies', 'tickets')
            ->set('shareKind', 'percent')
            ->set('shareValue', '25')
            ->call('addShare')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('event_fees', [
            'event_id' => $event->id,
            'name' => 'Bilheteria',
        ]);
        $this->assertDatabaseHas('revenue_distributions', [
            'event_id' => $event->id,
            'beneficiary_name' => 'Casa',
        ]);
    }
}

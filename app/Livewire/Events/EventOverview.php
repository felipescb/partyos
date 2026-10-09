<?php

namespace App\Livewire\Events;

use App\Domain\Events\PlanningTimelineBuilder;
use App\Domain\Events\ScheduleFlow;
use App\Domain\Finance\CashflowPreview;
use App\Domain\Finance\EventAlerts;
use App\Domain\Finance\EventFinanceReader;
use App\Domain\Finance\StatementBuilder;
use App\Enums\EventRole;
use App\Enums\EventStatus;
use App\Enums\TaskStatus;
use App\Livewire\Concerns\InteractsWithEvent;
use App\Livewire\Concerns\ManagesEventTeam;
use App\Models\Event;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app.dashboard')]
class EventOverview extends Component
{
    use InteractsWithEvent;
    use ManagesEventTeam;

    public bool $ticketsGridExpanded = false;

    public bool $cashflowGridExpanded = false;

    public function mount(Event $event): void
    {
        $this->mountEvent($event);
    }

    public function toggleTicketsGrid(): void
    {
        if (! auth()->user()->can('viewFinance', $this->event)) {
            return;
        }

        $this->ticketsGridExpanded = ! $this->ticketsGridExpanded;
    }

    public function toggleCashflowGrid(): void
    {
        if (! auth()->user()->can('viewFinance', $this->event)) {
            return;
        }

        $this->cashflowGridExpanded = ! $this->cashflowGridExpanded;
    }

    public function render(
        EventFinanceReader $reader,
        StatementBuilder $builder,
        EventAlerts $alerts,
        PlanningTimelineBuilder $planningTimeline,
        CashflowPreview $cashflowPreview,
        ScheduleFlow $scheduleFlow,
    ): View {
        $input = $reader->read($this->event);
        $statement = $builder->statement($input);
        $canFinance = auth()->user()->can('viewFinance', $this->event);

        $payments = $this->event->payments()
            ->with('budgetItem')
            ->where('status', 'scheduled')
            ->orderBy('due_on')
            ->limit(8)
            ->get();

        $tasks = $this->event->tasks()
            ->where('status', '!=', TaskStatus::Done)
            ->orderByRaw("case priority when 'high' then 0 when 'medium' then 1 else 2 end")
            ->orderByRaw('due_on is null')
            ->orderBy('due_on')
            ->limit(12)
            ->get();

        $ticketTiers = $canFinance
            ? $this->event->ticketTiers()->orderBy('sort_order')->get()
            : collect();
        $ticketsSold = (int) $ticketTiers->sum('sold_quantity');
        $ticketsAvailable = (int) $ticketTiers->sum(fn ($tier): int => max(0, $tier->remaining()));

        $next = $this->event->scheduleItems()
            ->where(function ($query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '>=', now()->subHours(2));
            })
            ->orderByRaw('starts_at is null')
            ->orderBy('starts_at')
            ->first();

        $statusTone = match ($this->event->status) {
            EventStatus::Live, EventStatus::OnSale => 'up',
            EventStatus::Finished, EventStatus::Cancelled => 'down',
            default => 'flat',
        };

        return view('livewire.events.overview', [
            'statement' => $statement,
            'alerts' => $canFinance ? $alerts->make($statement, $input, $payments) : [],
            'canFinance' => $canFinance,
            'payments' => $payments,
            'tasks' => $tasks,
            'next' => $next,
            'pendingTasks' => $this->event->tasks()->where('status', '!=', TaskStatus::Done)->count(),
            'lineupCount' => $this->event->bookings()->count(),
            'statusTone' => $statusTone,
            'teamMembers' => $this->event->members()->orderBy('name')->limit(6)->get(),
            'teamMemberCount' => $this->event->members()->count(),
            'canManageTeam' => auth()->user()->can('manageTeam', $this->event),
            'teamRoles' => EventRole::cases(),
            'planningTimeline' => $planningTimeline->build($this->event),
            'ticketTiers' => $ticketTiers,
            'ticketsSold' => $ticketsSold,
            'ticketsAvailable' => $ticketsAvailable,
            'cashflowPreview' => $canFinance ? $cashflowPreview->forEvent($this->event) : null,
            'scheduleFlow' => $scheduleFlow->present($this->event, $scheduleItems = $this->event->scheduleItems()->with('vendor')->get()),
            'scheduleCount' => $scheduleItems->count(),
        ])->title($this->event->name);
    }
}

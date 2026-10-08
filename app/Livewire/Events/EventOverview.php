<?php

namespace App\Livewire\Events;

use App\Domain\Events\DuplicateEvent;
use App\Domain\Events\DuplicateSelection;
use App\Domain\Finance\EventAlerts;
use App\Domain\Finance\EventFinanceReader;
use App\Domain\Finance\StatementBuilder;
use App\Enums\TaskStatus;
use App\Livewire\Concerns\InteractsWithEvent;
use App\Models\Event;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class EventOverview extends Component
{
    use InteractsWithEvent;

    public bool $showDuplicate = false;

    public string $duplicateName = '';

    public string $duplicateStarts = '';

    /** @var list<string> */
    public array $copy = ['budget', 'artists', 'tasks', 'schedule', 'tickets'];

    public function mount(Event $event): void
    {
        $this->mountEvent($event);
        $this->duplicateName = $event->name;
    }

    public function duplicate(DuplicateEvent $duplicator): void
    {
        $this->authorize('manageOperations', $this->event);

        $this->validate([
            'duplicateName' => ['required', 'string', 'max:140'],
            'duplicateStarts' => ['nullable', 'date'],
            'copy' => ['array'],
        ], [
            'duplicateName.required' => 'A nova edição precisa de um nome.',
        ]);

        $copy = $duplicator->handle(auth()->user(), $this->event, [
            'name' => $this->duplicateName,
            'starts_at' => $this->duplicateStarts !== '' ? $this->duplicateStarts : null,
        ], new DuplicateSelection(
            budget: in_array('budget', $this->copy, true),
            artists: in_array('artists', $this->copy, true),
            tasks: in_array('tasks', $this->copy, true),
            schedule: in_array('schedule', $this->copy, true),
            guests: in_array('guests', $this->copy, true),
            tickets: in_array('tickets', $this->copy, true),
        ));

        Flux::toast(variant: 'success', text: 'Edição criada. Pagamentos realizados não foram copiados.');
        $this->redirectRoute('events.show', $copy, navigate: true);
    }

    public function render(EventFinanceReader $reader, StatementBuilder $builder, EventAlerts $alerts): View
    {
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
            ->orderByRaw('due_on is null')
            ->orderBy('due_on')
            ->limit(6)
            ->get();

        $next = $this->event->scheduleItems()
            ->where(function ($query): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '>=', now()->subHours(2));
            })
            ->orderByRaw('starts_at is null')
            ->orderBy('starts_at')
            ->first();

        return view('livewire.events.overview', [
            'statement' => $statement,
            'alerts' => $canFinance ? $alerts->make($statement, $input, $payments) : [],
            'canFinance' => $canFinance,
            'payments' => $payments,
            'tasks' => $tasks,
            'next' => $next,
            'pendingTasks' => $this->event->tasks()->where('status', '!=', TaskStatus::Done)->count(),
            'lineupCount' => $this->event->bookings()->count(),
        ])->title($this->event->name);
    }
}

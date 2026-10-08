<?php

namespace App\Livewire\Concerns;

use App\Domain\Events\DuplicateEvent;
use App\Domain\Events\DuplicateSelection;
use App\Models\Event;
use Flux\Flux;

trait DuplicatesEvents
{
    public bool $showDuplicate = false;

    public ?int $duplicateEventId = null;

    public string $duplicateName = '';

    public string $duplicateStarts = '';

    /** @var list<string> */
    public array $copy = ['budget', 'artists', 'tasks', 'schedule', 'tickets'];

    public function openDuplicate(int $eventId): void
    {
        $event = Event::query()
            ->where('organization_id', auth()->user()->current_organization_id)
            ->findOrFail($eventId);

        $this->authorize('manageOperations', $event);

        $this->duplicateEventId = $event->id;
        $this->duplicateName = $event->name;
        $this->duplicateStarts = $event->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->showDuplicate = true;
    }

    public function duplicate(DuplicateEvent $duplicator): void
    {
        abort_unless($this->duplicateEventId !== null, 404);

        $event = Event::query()
            ->where('organization_id', auth()->user()->current_organization_id)
            ->findOrFail($this->duplicateEventId);

        $this->authorize('manageOperations', $event);

        $this->validate([
            'duplicateName' => ['required', 'string', 'max:140'],
            'duplicateStarts' => ['nullable', 'date'],
            'copy' => ['array'],
        ], [
            'duplicateName.required' => 'A nova edição precisa de um nome.',
        ]);

        $copy = $duplicator->handle(auth()->user(), $event, [
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
}

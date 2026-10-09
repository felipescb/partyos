<?php

namespace App\Livewire\Events;

use App\Domain\Events\ScheduleFlow;
use App\Livewire\Concerns\InteractsWithEvent;
use App\Models\Event;
use App\Models\ScheduleItem;
use App\Models\Vendor;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app.dashboard')]
class ScheduleBoard extends Component
{
    use InteractsWithEvent;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $startsAt = '';

    public string $duration = '';

    public string $location = '';

    public string $vendorId = '';

    public string $notes = '';

    public function mount(Event $event): void
    {
        $this->mountEvent($event);
    }

    public function create(): void
    {
        $this->authorize('manageOperations', $this->event);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->authorize('manageOperations', $this->event);
        $item = $this->item($id);
        $this->editingId = $item->id;
        $this->title = $item->title;
        $this->startsAt = $item->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->duration = $item->duration_minutes ? (string) $item->duration_minutes : '';
        $this->location = (string) $item->location;
        $this->vendorId = $item->vendor_id ? (string) $item->vendor_id : '';
        $this->notes = (string) $item->notes;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('manageOperations', $this->event);
        $this->validate([
            'title' => ['required', 'string', 'max:140'],
            'startsAt' => ['nullable', 'date'],
            'duration' => ['nullable', 'regex:/^[0-9]*$/'],
            'location' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'title.required' => 'O que acontece nesse horário?',
        ]);

        $data = [
            'event_id' => $this->event->id,
            'title' => $this->title,
            'starts_at' => $this->startsAt !== '' ? $this->startsAt : null,
            'duration_minutes' => $this->duration !== '' ? (int) $this->duration : null,
            'location' => $this->location !== '' ? $this->location : null,
            'vendor_id' => $this->vendorId !== '' ? (int) $this->vendorId : null,
            'notes' => $this->notes !== '' ? $this->notes : null,
            'sort_order' => $this->editingId ? $this->item($this->editingId)->sort_order : ((int) $this->event->scheduleItems()->max('sort_order')) + 1,
        ];

        if ($this->editingId) {
            $this->item($this->editingId)->update($data);
        } else {
            $this->event->scheduleItems()->create($data);
        }

        $this->showForm = false;
        Flux::toast(variant: 'success', text: 'Horário salvo.');
    }

    public function destroy(int $id): void
    {
        $this->authorize('manageOperations', $this->event);
        $this->item($id)->delete();
        $this->showForm = false;
    }

    public function render(): View
    {
        $items = $this->event->scheduleItems()->with('vendor')->get();

        return view('livewire.events.schedule', [
            'flow' => app(ScheduleFlow::class)->forEvent($this->event, $items),
            'vendors' => Vendor::query()->where('organization_id', auth()->user()->current_organization_id)->orderBy('name')->get(),
            'canEdit' => auth()->user()->can('manageOperations', $this->event),
        ])->title('Cronograma · '.$this->event->name);
    }

    private function item(int $id): ScheduleItem
    {
        return $this->event->scheduleItems()->findOrFail($id);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->title = '';
        $this->startsAt = $this->event->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->duration = '';
        $this->location = '';
        $this->vendorId = '';
        $this->notes = '';
    }
}

<?php

namespace App\Livewire\Events;

use App\Domain\Events\CreateEvent;
use App\Domain\Events\OfficialCatalog;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;
use App\Models\EventTemplate;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class EventForm extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?Event $editing = null;

    public string $name = '';

    public string $description = '';

    public string $type = 'party';

    public string $status = 'draft';

    public string $startsAt = '';

    public string $endsAt = '';

    public string $venueName = '';

    public string $address = '';

    public string $city = '';

    public string $capacity = '';

    public string $currency = 'BRL';

    public string $notes = '';

    public string $templateId = '';

    public mixed $cover = null;

    public bool $confirmDelete = false;

    public function mount(): void
    {
        OfficialCatalog::ensure();

        $event = request()->route('event');

        if (! $event instanceof Event) {
            return;
        }

        $this->authorize('manageOperations', $event);
        $this->editing = $event;
        $this->name = $event->name;
        $this->description = (string) $event->description;
        $this->type = $event->type->value;
        $this->status = $event->status->value;
        $this->startsAt = $event->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->endsAt = $event->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->venueName = (string) $event->venue_name;
        $this->address = (string) $event->address;
        $this->city = (string) $event->city;
        $this->capacity = $event->capacity ? (string) $event->capacity : '';
        $this->currency = $event->currency;
        $this->notes = (string) $event->notes;
        $this->templateId = $event->template_id ? (string) $event->template_id : '';
    }

    public function save(CreateEvent $creator): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:140'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', Rule::enum(EventType::class)],
            'status' => ['required', Rule::enum(EventStatus::class)],
            'startsAt' => ['nullable', 'date'],
            'endsAt' => ['nullable', 'date', 'after_or_equal:startsAt'],
            'venueName' => ['nullable', 'string', 'max:160'],
            'address' => ['nullable', 'string', 'max:200'],
            'city' => ['nullable', 'string', 'max:120'],
            'capacity' => ['nullable', 'regex:/^[0-9]*$/'],
            'currency' => ['required', Rule::in(['BRL', 'USD', 'EUR'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'templateId' => ['nullable', 'integer'],
            'cover' => ['nullable', 'image', 'max:4096'],
        ], [
            'name.required' => 'Dê um nome para o evento.',
            'endsAt.after_or_equal' => 'O término precisa ser depois do início.',
        ]);

        $coverPath = $this->editing?->cover_path;

        if ($this->cover) {
            $coverPath = $this->cover->store('covers', 'public');
        }

        $attributes = [
            'name' => $validated['name'],
            'description' => $this->blank($validated['description'] ?? null),
            'type' => $validated['type'],
            'status' => $validated['status'],
            'starts_at' => $this->blank($validated['startsAt'] ?? null),
            'ends_at' => $this->blank($validated['endsAt'] ?? null),
            'venue_name' => $this->blank($validated['venueName'] ?? null),
            'address' => $this->blank($validated['address'] ?? null),
            'city' => $this->blank($validated['city'] ?? null),
            'capacity' => $validated['capacity'] !== null && $validated['capacity'] !== '' ? (int) $validated['capacity'] : null,
            'currency' => $validated['currency'],
            'notes' => $this->blank($validated['notes'] ?? null),
            'cover_path' => $coverPath,
            'template_id' => $this->editing ? $this->editing->template_id : ($this->templateId !== '' ? (int) $this->templateId : null),
        ];

        if ($this->editing instanceof Event) {
            $this->editing->update($attributes);
            Flux::toast(variant: 'success', text: 'Evento atualizado.');
            $this->redirectRoute('events.show', $this->editing, navigate: true);

            return;
        }

        $event = $creator->handle(auth()->user(), $attributes);
        Flux::toast(variant: 'success', text: 'Evento criado. O orçamento já está aberto.');
        $this->redirectRoute('events.show', $event, navigate: true);
    }

    public function delete(): void
    {
        $event = $this->editing;
        abort_unless($event instanceof Event, 404);
        $this->authorize('delete', $event);
        $event->delete();
        Flux::toast(variant: 'success', text: 'Evento excluído. Dá para recuperar pelo banco enquanto estiver na lixeira.');
        $this->redirectRoute('dashboard', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.events.form', [
            'types' => EventType::cases(),
            'statuses' => EventStatus::cases(),
            'templates' => EventTemplate::query()->where('is_official', true)->orderBy('name')->get(),
        ])->title($this->editing ? 'Editar evento' : 'Novo evento');
    }

    private function blank(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}

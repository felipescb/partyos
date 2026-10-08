<?php

namespace App\Livewire\Events;

use App\Domain\Events\CreateEvent;
use App\Domain\Events\OfficialCatalog;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Models\Event;
use App\Models\EventTemplate;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app.dashboard')]
class EventForm extends Component
{
    use WithFileUploads;

    public const STEP_BASICS = 1;

    public const STEP_WHEN_WHERE = 2;

    public const STEP_FINISH = 3;

    #[Locked]
    public ?Event $editing = null;

    public int $step = self::STEP_BASICS;

    public string $name = '';

    public string $description = '';

    public string $type = 'party';

    public string $status = 'draft';

    public string $planningStartsAt = '';

    public string $executionAt = '';

    public string $durationMinutes = '';

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
            $routeName = request()->route()?->getName();

            if ($routeName !== null && $routeName !== 'events.create.configure') {
                $this->redirectRoute('events.create', navigate: true);

                return;
            }

            if ($routeName === 'events.create.configure' && ! request()->has('template')) {
                $this->redirectRoute('events.create', navigate: true);

                return;
            }

            if ($routeName === null) {
                return;
            }

            $templateParam = (string) request('template');

            if ($templateParam === 'blank') {
                $this->templateId = '';

                return;
            }

            $template = EventTemplate::query()->find($templateParam);

            if ($template === null) {
                $this->redirectRoute('events.create', navigate: true);

                return;
            }

            $this->templateId = (string) $template->id;
            $this->type = $template->type->value;

            return;
        }

        $this->authorize('manageOperations', $event);
        $this->editing = $event;
        $this->name = $event->name;
        $this->description = (string) $event->description;
        $this->type = $event->type->value;
        $this->status = $event->status->value;
        $this->planningStartsAt = $event->planning_starts_at?->format('Y-m-d') ?? '';
        $this->executionAt = $event->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->durationMinutes = $this->resolveDurationMinutes($event);
        $this->venueName = (string) $event->venue_name;
        $this->address = (string) $event->address;
        $this->city = (string) $event->city;
        $this->capacity = $event->capacity ? (string) $event->capacity : '';
        $this->currency = $event->currency;
        $this->notes = (string) $event->notes;
        $this->templateId = $event->template_id ? (string) $event->template_id : '';
    }

    public function nextStep(): void
    {
        $validated = $this->validate($this->rulesForStep($this->step));

        if ($this->step === self::STEP_WHEN_WHERE) {
            $this->validatePlanningTimeline($validated);
        }

        if ($this->step < self::STEP_FINISH) {
            $this->step++;
        }
    }

    public function previousStep(): void
    {
        if ($this->step > self::STEP_BASICS) {
            $this->step--;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function rulesForStep(int $step): array
    {
        return match ($step) {
            self::STEP_BASICS => $this->editing instanceof Event
                ? [
                    'name' => ['required', 'string', 'max:140'],
                    'type' => ['required', Rule::enum(EventType::class)],
                    'status' => ['required', Rule::enum(EventStatus::class)],
                ]
                : [
                    'name' => ['required', 'string', 'max:140'],
                    'type' => ['required', Rule::enum(EventType::class)],
                    'templateId' => ['nullable', 'integer'],
                ],
            self::STEP_WHEN_WHERE => [
                'planningStartsAt' => ['nullable', 'date'],
                'executionAt' => ['nullable', 'date'],
                'durationMinutes' => ['nullable', 'integer', 'min:30', 'max:2880'],
                'venueName' => ['nullable', 'string', 'max:160'],
                'address' => ['nullable', 'string', 'max:200'],
                'city' => ['nullable', 'string', 'max:120'],
            ],
            default => [],
        };
    }

    protected function prepareForValidation($attributes): array
    {
        foreach (['planningStartsAt', 'executionAt', 'durationMinutes'] as $field) {
            if (array_key_exists($field, $attributes) && $attributes[$field] === '') {
                $attributes[$field] = null;
            }
        }

        return $attributes;
    }

    public function clearCover(): void
    {
        $this->reset('cover');
        $this->resetValidation(['cover', 'files.0']);
    }

    public function save(CreateEvent $creator): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:140'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', Rule::enum(EventType::class)],
            'status' => ['required', Rule::enum(EventStatus::class)],
            'planningStartsAt' => ['nullable', 'date'],
            'executionAt' => ['nullable', 'date'],
            'durationMinutes' => ['nullable', 'integer', 'min:30', 'max:2880'],
            'venueName' => ['nullable', 'string', 'max:160'],
            'address' => ['nullable', 'string', 'max:200'],
            'city' => ['nullable', 'string', 'max:120'],
            'capacity' => ['nullable', 'regex:/^[0-9]*$/'],
            'currency' => ['required', Rule::in(['BRL', 'USD', 'EUR'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'templateId' => ['nullable', 'integer'],
        ];

        if ($this->cover instanceof TemporaryUploadedFile) {
            $rules['cover'] = ['file', 'mimes:jpg,jpeg,png,webp', 'max:12288'];
        }

        try {
            $validated = $this->validate($rules, [
                'name.required' => 'Dê um nome para o evento.',
                'cover.max' => 'A capa pode ter no máximo 12 MB.',
                'cover.mimes' => 'Use JPG, PNG ou WebP na capa.',
            ]);

            $this->validatePlanningTimeline($validated);
        } catch (ValidationException $exception) {
            $this->focusStepForErrors($exception);

            throw $exception;
        }

        $timeline = $this->timelineFromForm($validated);

        $coverPath = $this->editing?->cover_path;

        if ($this->cover) {
            $coverPath = $this->cover->store('covers', 'public');
        }

        $attributes = [
            'name' => $validated['name'],
            'description' => $this->blank($validated['description'] ?? null),
            'type' => $validated['type'],
            'status' => $validated['status'],
            'planning_starts_at' => $timeline['planning_starts_at'],
            'starts_at' => $timeline['starts_at'],
            'ends_at' => $timeline['ends_at'],
            'duration_minutes' => $timeline['duration_minutes'],
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
            'templateLabel' => $this->selectedTemplateLabel(),
            'durationOptions' => self::durationOptions(),
        ])->title($this->editing ? 'Editar evento' : 'Novo evento');
    }

    /**
     * @return array<int, string>
     */
    public static function durationOptions(): array
    {
        return [
            240 => '4 horas',
            360 => '6 horas',
            480 => '8 horas',
            600 => '10 horas',
            720 => '12 horas',
            840 => '14 horas',
            960 => '16 horas',
            1440 => '24 horas',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function validatePlanningTimeline(array $validated): void
    {
        $planning = $this->blank($validated['planningStartsAt'] ?? null);
        $execution = $this->blank($validated['executionAt'] ?? null);
        if ($planning !== null && $execution !== null) {
            $planningDay = Carbon::parse($planning)->startOfDay();
            $executionDay = Carbon::parse($execution)->startOfDay();

            if ($planningDay->greaterThan($executionDay)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'planningStartsAt' => 'O início do planejamento deve ser antes da data da festa.',
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{planning_starts_at: ?string, starts_at: ?string, ends_at: ?string, duration_minutes: ?int}
     */
    private function timelineFromForm(array $validated): array
    {
        $planning = $this->blank($validated['planningStartsAt'] ?? null);
        $execution = $this->blank($validated['executionAt'] ?? null);
        $duration = $this->durationFromValidated($validated);

        $endsAt = null;

        if ($execution !== null && $duration !== null) {
            $endsAt = Carbon::parse($execution)->addMinutes($duration)->format('Y-m-d H:i:s');
        }

        return [
            'planning_starts_at' => $planning,
            'starts_at' => $execution,
            'ends_at' => $endsAt,
            'duration_minutes' => $duration,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function focusStepForErrors(ValidationException $exception): void
    {
        $keys = array_keys($exception->validator->errors()->messages());

        if (array_intersect($keys, ['planningStartsAt', 'executionAt', 'durationMinutes', 'venueName', 'address', 'city']) !== []) {
            $this->step = self::STEP_WHEN_WHERE;
        } elseif (array_intersect($keys, ['name', 'type', 'status', 'templateId']) !== []) {
            $this->step = self::STEP_BASICS;
        }
    }

    private function durationFromValidated(array $validated): ?int
    {
        $value = $validated['durationMinutes'] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        $minutes = (int) $value;

        return $minutes > 0 ? $minutes : null;
    }

    private function resolveDurationMinutes(Event $event): string
    {
        if ($event->duration_minutes !== null) {
            return (string) $event->duration_minutes;
        }

        if ($event->starts_at !== null && $event->ends_at !== null) {
            return (string) $event->starts_at->diffInMinutes($event->ends_at);
        }

        return '';
    }

    private function selectedTemplateLabel(): ?string
    {
        if ($this->editing instanceof Event) {
            return null;
        }

        if ($this->templateId === '') {
            return 'Do zero';
        }

        return EventTemplate::query()->find($this->templateId)?->name;
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

<?php

namespace App\Livewire\Artists;

use App\Domain\Events\OfficialCatalog;
use App\Domain\Finance\Money;
use App\Enums\BookingStatus;
use App\Enums\CostStatus;
use App\Livewire\Concerns\InteractsWithEvent;
use App\Models\Artist;
use App\Models\Booking;
use App\Models\Event;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class LineupBoard extends Component
{
    use InteractsWithEvent;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $artistId = '';

    public string $stageName = '';

    public string $fee = '';

    public string $startsAt = '';

    public string $endsAt = '';

    public string $status = 'inquiry';

    public string $notes = '';

    public function mount(Event $event): void
    {
        $this->mountEvent($event);
        OfficialCatalog::ensure();
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
        $booking = $this->booking($id);
        $this->editingId = $booking->id;
        $this->artistId = (string) $booking->artist_id;
        $this->stageName = '';
        $this->fee = $booking->budgetItem ? $this->money($booking->budgetItem->committedAmount()) : '';
        $this->startsAt = $booking->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->endsAt = $booking->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->status = $booking->status->value;
        $this->notes = (string) $booking->notes;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('manageOperations', $this->event);
        $this->validate([
            'artistId' => ['nullable', 'integer'],
            'stageName' => ['required_without:artistId', 'nullable', 'string', 'max:140'],
            'fee' => ['required', 'string'],
            'startsAt' => ['nullable', 'date'],
            'endsAt' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(BookingStatus::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'stageName.required_without' => 'Escolha um artista ou escreva o nome.',
            'fee.required' => 'Qual é o cachê?',
        ]);

        $artist = $this->resolveArtist();
        $fee = Money::parse($this->fee);
        $budget = $this->event->budget ?: $this->event->budget()->create(['name' => 'Orçamento']);
        $status = BookingStatus::from($this->status);
        $costStatus = match ($status) {
            BookingStatus::Confirmed => CostStatus::Contracted,
            BookingStatus::Cancelled => CostStatus::Cancelled,
            default => CostStatus::Negotiating,
        };

        $itemData = [
            'budget_id' => $budget->id,
            'event_id' => $this->event->id,
            'cost_category_id' => OfficialCatalog::categoryId('artistas'),
            'description' => 'Cachê — '.$artist->stage_name,
            'estimated_amount' => $fee,
            'contracted_amount' => $status === BookingStatus::Inquiry ? null : $fee,
            'status' => $costStatus,
            'notes' => $this->notes !== '' ? $this->notes : null,
        ];

        if ($this->editingId) {
            $booking = $this->booking($this->editingId);
            $booking->update([
                'artist_id' => $artist->id,
                'starts_at' => $this->startsAt !== '' ? $this->startsAt : null,
                'ends_at' => $this->endsAt !== '' ? $this->endsAt : null,
                'status' => $status,
                'notes' => $this->notes !== '' ? $this->notes : null,
            ]);
            $booking->budgetItem?->update($itemData);
        } else {
            $item = $this->event->budgetItems()->create($itemData);
            $this->event->bookings()->create([
                'artist_id' => $artist->id,
                'budget_item_id' => $item->id,
                'starts_at' => $this->startsAt !== '' ? $this->startsAt : null,
                'ends_at' => $this->endsAt !== '' ? $this->endsAt : null,
                'status' => $status,
                'notes' => $this->notes !== '' ? $this->notes : null,
            ]);
        }

        $this->showForm = false;
        Flux::toast(variant: 'success', text: 'Artista no lineup. O cachê entrou no orçamento.');
    }

    public function destroy(int $id): void
    {
        $this->authorize('manageOperations', $this->event);
        $booking = $this->booking($id);
        $booking->budgetItem?->delete();
        $booking->delete();
        $this->showForm = false;
        Flux::toast(variant: 'success', text: 'Artista removido do evento.');
    }

    public function render(): View
    {
        return view('livewire.artists.lineup', [
            'bookings' => $this->event->bookings()->with(['artist', 'budgetItem'])->get(),
            'artists' => Artist::query()->where('organization_id', auth()->user()->current_organization_id)->orderBy('stage_name')->get(),
            'statuses' => BookingStatus::cases(),
            'canEdit' => auth()->user()->can('manageOperations', $this->event),
        ])->title('Artistas · '.$this->event->name);
    }

    private function resolveArtist(): Artist
    {
        if ($this->artistId !== '') {
            return Artist::query()
                ->where('organization_id', auth()->user()->current_organization_id)
                ->findOrFail($this->artistId);
        }

        return Artist::query()->create([
            'organization_id' => auth()->user()->current_organization_id,
            'stage_name' => $this->stageName,
            'default_fee' => Money::parse($this->fee),
        ]);
    }

    private function booking(int $id): Booking
    {
        return $this->event->bookings()->with('budgetItem')->findOrFail($id);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->artistId = '';
        $this->stageName = '';
        $this->fee = '';
        $this->startsAt = '';
        $this->endsAt = '';
        $this->status = BookingStatus::Inquiry->value;
        $this->notes = '';
    }

    private function money(int $cents): string
    {
        return number_format(intdiv($cents, 100), 0, ',', '.').','.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}

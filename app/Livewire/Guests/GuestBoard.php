<?php

namespace App\Livewire\Guests;

use App\Enums\GuestCategory;
use App\Enums\RsvpStatus;
use App\Livewire\Concerns\InteractsWithEvent;
use App\Models\Event;
use App\Models\Guest;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class GuestBoard extends Component
{
    use InteractsWithEvent;
    use WithPagination;

    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $instagram = '';

    public string $category = 'guest';

    public string $rsvp = 'not_sent';

    public string $plusOnes = '0';

    public string $notes = '';

    public function mount(Event $event): void
    {
        $this->mountEvent($event, 'viewGuests');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
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
        $guest = $this->guest($id);
        $this->editingId = $guest->id;
        $this->name = $guest->name;
        $this->phone = (string) $guest->phone;
        $this->email = (string) $guest->email;
        $this->instagram = (string) $guest->instagram;
        $this->category = $guest->category->value;
        $this->rsvp = $guest->rsvp_status->value;
        $this->plusOnes = (string) $guest->plus_ones;
        $this->notes = (string) $guest->notes;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('manageOperations', $this->event);
        $this->validate([
            'name' => ['required', 'string', 'max:140'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:160'],
            'instagram' => ['nullable', 'string', 'max:80'],
            'category' => ['required', Rule::enum(GuestCategory::class)],
            'rsvp' => ['required', Rule::enum(RsvpStatus::class)],
            'plusOnes' => ['required', 'integer', 'min:0', 'max:20'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'name.required' => 'Qual é o nome do convidado?',
        ]);

        $status = RsvpStatus::from($this->rsvp);
        $data = [
            'event_id' => $this->event->id,
            'name' => $this->name,
            'phone' => $this->phone !== '' ? $this->phone : null,
            'email' => $this->email !== '' ? $this->email : null,
            'instagram' => $this->instagram !== '' ? $this->instagram : null,
            'category' => $this->category,
            'rsvp_status' => $status,
            'plus_ones' => (int) $this->plusOnes,
            'checked_in_at' => $status === RsvpStatus::CheckedIn ? now() : null,
            'notes' => $this->notes !== '' ? $this->notes : null,
        ];

        if ($this->editingId) {
            $guest = $this->guest($this->editingId);
            if ($guest->rsvp_status === RsvpStatus::CheckedIn && $status === RsvpStatus::CheckedIn) {
                $data['checked_in_at'] = $guest->checked_in_at;
            }
            $guest->update($data);
        } else {
            $this->event->guests()->create($data);
        }

        $this->showForm = false;
        Flux::toast(variant: 'success', text: 'Convidado salvo.');
    }

    public function checkIn(int $id): void
    {
        $this->authorize('manageOperations', $this->event);
        $this->guest($id)->update([
            'rsvp_status' => RsvpStatus::CheckedIn,
            'checked_in_at' => now(),
        ]);
    }

    public function destroy(int $id): void
    {
        $this->authorize('manageOperations', $this->event);
        $this->guest($id)->delete();
        $this->showForm = false;
        Flux::toast(variant: 'success', text: 'Convidado removido.');
    }

    public function render(): View
    {
        $guests = $this->event->guests()
            ->when($this->search !== '', function ($query): void {
                $term = '%'.$this->search.'%';
                $query->where(function ($inner) use ($term): void {
                    $inner->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('instagram', 'like', $term);
                });
            })
            ->orderBy('name')
            ->paginate(20);

        $confirmed = $this->event->guests()
            ->whereIn('rsvp_status', [RsvpStatus::Confirmed, RsvpStatus::CheckedIn])
            ->get()
            ->sum(fn (Guest $guest): int => $guest->headcount());

        return view('livewire.guests.board', [
            'guests' => $guests,
            'confirmed' => $confirmed,
            'categories' => GuestCategory::cases(),
            'statuses' => RsvpStatus::cases(),
            'canEdit' => auth()->user()->can('manageOperations', $this->event),
        ])->title('Convidados · '.$this->event->name);
    }

    private function guest(int $id): Guest
    {
        return $this->event->guests()->findOrFail($id);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->phone = '';
        $this->email = '';
        $this->instagram = '';
        $this->category = GuestCategory::Guest->value;
        $this->rsvp = RsvpStatus::NotSent->value;
        $this->plusOnes = '0';
        $this->notes = '';
    }
}

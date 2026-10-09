<?php

namespace App\Livewire\Guests;

use App\Domain\Guests\GuestListCsv;
use App\Domain\Guests\GuestListRow;
use App\Enums\GuestCategory;
use App\Enums\RsvpStatus;
use App\Livewire\Concerns\InteractsWithEvent;
use App\Models\Event;
use App\Models\Guest;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app.dashboard')]
class GuestBoard extends Component
{
    use InteractsWithEvent;
    use WithFileUploads;
    use WithPagination;

    public string $list = 'all';

    public string $search = '';

    public bool $showForm = false;

    public bool $showImport = false;

    public ?int $editingId = null;

    /** @var TemporaryUploadedFile|null */
    public $importFile = null;

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

    public function selectList(string $list): void
    {
        abort_unless(in_array($list, $this->listKeys(), true), 404);
        $this->list = $list;
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('manageOperations', $this->event);
        $this->resetForm();

        if ($this->list !== 'all') {
            $this->category = $this->list;
        }

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

    public function openImport(): void
    {
        $this->authorize('manageOperations', $this->event);
        $this->importFile = null;
        $this->showImport = true;
    }

    public function closeImport(): void
    {
        $this->showImport = false;
        $this->importFile = null;
    }

    public function importList(GuestListCsv $csv): void
    {
        $this->authorize('manageOperations', $this->event);
        $this->validate([
            'importFile' => ['required', 'file', 'mimes:csv,txt', 'max:8192'],
        ], [
            'importFile.required' => 'Escolha o CSV da lista.',
        ]);

        try {
            $rows = $csv->parse($this->importFile->getContent());
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'importFile' => $exception->getMessage(),
            ]);
        }

        if ($rows === []) {
            throw ValidationException::withMessages([
                'importFile' => 'Nenhum convidado nesse arquivo.',
            ]);
        }

        $fallback = $this->list !== 'all'
            ? GuestCategory::from($this->list)
            : GuestCategory::Guest;

        $created = 0;
        $updated = 0;

        foreach ($rows as $row) {
            $existing = $row->email !== null
                ? $this->event->guests()->where('email', $row->email)->first()
                : null;

            if ($existing instanceof Guest) {
                $existing->update($this->rowData($row, $fallback, $existing));
                $updated++;
            } else {
                $this->event->guests()->create($this->rowData($row, $fallback));
                $created++;
            }
        }

        $this->closeImport();
        Flux::toast(variant: 'success', text: $created.' novos · '.$updated.' atualizados.');
    }

    public function exportList(GuestListCsv $csv): StreamedResponse
    {
        $this->authorize('viewGuests', $this->event);
        $guests = $this->listedGuests()->orderBy('name')->get();
        $contents = $csv->render($guests);
        $filename = 'convidados-'.$this->event->slug.($this->list === 'all' ? '' : '-'.$this->list).'.csv';

        return response()->streamDownload(function () use ($contents): void {
            echo $contents;
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render(): View
    {
        $guests = $this->listedGuests()->orderBy('name')->paginate(20);

        return view('livewire.guests.board', [
            'guests' => $guests,
            'lists' => $this->lists(),
            'activeListLabel' => $this->activeListLabel(),
            'categories' => GuestCategory::cases(),
            'statuses' => RsvpStatus::cases(),
            'canEdit' => auth()->user()->can('manageOperations', $this->event),
        ])->title('Convidados · '.$this->event->name);
    }

    /**
     * @return HasMany<Guest, Event>
     */
    private function listedGuests(): HasMany
    {
        return $this->event->guests()
            ->when($this->list !== 'all', fn (Builder $query) => $query->where('category', $this->list))
            ->when($this->search !== '', function (Builder $query): void {
                $term = '%'.$this->search.'%';
                $query->where(function (Builder $inner) use ($term): void {
                    $inner->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('instagram', 'like', $term);
                });
            });
    }

    /**
     * @return list<array{key: string, label: string, count: int, heads: int}>
     */
    private function lists(): array
    {
        $guests = $this->event->guests()->get();
        $lists = [[
            'key' => 'all',
            'label' => 'Todas',
            'count' => $guests->count(),
            'heads' => $this->confirmedHeads($guests),
        ]];

        foreach (GuestCategory::cases() as $category) {
            $subset = $guests->where('category', $category);
            $lists[] = [
                'key' => $category->value,
                'label' => $category->label(),
                'count' => $subset->count(),
                'heads' => $this->confirmedHeads($subset),
            ];
        }

        return $lists;
    }

    /**
     * @param  Collection<int, Guest>  $guests
     */
    private function confirmedHeads(Collection $guests): int
    {
        return (int) $guests
            ->whereIn('rsvp_status', [RsvpStatus::Confirmed, RsvpStatus::CheckedIn])
            ->sum(fn (Guest $guest): int => $guest->headcount());
    }

    private function activeListLabel(): string
    {
        if ($this->list === 'all') {
            return 'Todas as listas';
        }

        return GuestCategory::from($this->list)->label();
    }

    /**
     * @return list<string>
     */
    private function listKeys(): array
    {
        return ['all', ...array_map(fn (GuestCategory $category): string => $category->value, GuestCategory::cases())];
    }

    /**
     * @return array<string, mixed>
     */
    private function rowData(GuestListRow $row, GuestCategory $fallback, ?Guest $existing = null): array
    {
        $status = $row->rsvp;
        $checkedInAt = $status === RsvpStatus::CheckedIn ? now() : null;

        if ($existing instanceof Guest && $existing->rsvp_status === RsvpStatus::CheckedIn && $status === RsvpStatus::CheckedIn) {
            $checkedInAt = $existing->checked_in_at;
        }

        return [
            'event_id' => $this->event->id,
            'name' => $row->name,
            'phone' => $row->phone,
            'email' => $row->email,
            'instagram' => $row->instagram,
            'category' => $row->category ?? $fallback,
            'rsvp_status' => $status,
            'plus_ones' => $row->plusOnes,
            'checked_in_at' => $checkedInAt,
            'notes' => $row->notes,
        ];
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

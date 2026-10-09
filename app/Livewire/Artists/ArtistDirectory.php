<?php

namespace App\Livewire\Artists;

use App\Domain\Finance\Money;
use App\Models\Artist;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app.dashboard')]
class ArtistDirectory extends Component
{
    public string $list = 'all';

    public string $work = 'all';

    public string $contact = 'all';

    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $stageName = '';

    public string $legalName = '';

    public string $instagram = '';

    public string $phone = '';

    public string $email = '';

    public string $agency = '';

    public string $defaultFee = '';

    public string $pix = '';

    public string $techRider = '';

    public string $hospitalityRider = '';

    public string $notes = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->canCreateEvents(), 403);
    }

    public function create(): void
    {
        $this->resetForm();

        if ($this->list !== 'all' && $this->list !== 'none') {
            $this->agency = $this->list;
        }

        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $artist = $this->artist($id);
        $this->editingId = $artist->id;
        $this->stageName = $artist->stage_name;
        $this->legalName = (string) $artist->legal_name;
        $this->instagram = (string) $artist->instagram;
        $this->phone = (string) $artist->phone;
        $this->email = (string) $artist->email;
        $this->agency = (string) $artist->agency;
        $this->defaultFee = $artist->default_fee ? $this->money($artist->default_fee) : '';
        $this->pix = (string) $artist->pix;
        $this->techRider = (string) $artist->tech_rider;
        $this->hospitalityRider = (string) $artist->hospitality_rider;
        $this->notes = (string) $artist->notes;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'stageName' => ['required', 'string', 'max:140'],
            'legalName' => ['nullable', 'string', 'max:140'],
            'instagram' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:160'],
            'agency' => ['nullable', 'string', 'max:140'],
            'defaultFee' => ['nullable', 'string'],
            'pix' => ['nullable', 'string', 'max:140'],
            'techRider' => ['nullable', 'string', 'max:5000'],
            'hospitalityRider' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'stageName.required' => 'Qual é o nome artístico?',
        ]);

        $data = [
            'organization_id' => auth()->user()->current_organization_id,
            'stage_name' => $this->stageName,
            'legal_name' => $this->blank($this->legalName),
            'instagram' => $this->blank($this->instagram),
            'phone' => $this->blank($this->phone),
            'email' => $this->blank($this->email),
            'agency' => $this->blank($this->agency),
            'default_fee' => trim($this->defaultFee) === '' ? null : Money::parse($this->defaultFee),
            'pix' => $this->blank($this->pix),
            'tech_rider' => $this->blank($this->techRider),
            'hospitality_rider' => $this->blank($this->hospitalityRider),
            'notes' => $this->blank($this->notes),
        ];

        if ($this->editingId) {
            $this->artist($this->editingId)->update($data);
        } else {
            Artist::query()->create($data);
        }

        $this->showForm = false;
        Flux::toast(variant: 'success', text: 'Artista salvo.');
    }

    public function destroy(int $id): void
    {
        $this->artist($id)->delete();
        $this->showForm = false;
        Flux::toast(variant: 'success', text: 'Artista removido. O histórico dos eventos permanece nos bookings.');
    }

    public function render(): View
    {
        $artists = Artist::query()
            ->where('organization_id', auth()->user()->current_organization_id)
            ->with(['bookings.event', 'bookings.budgetItem'])
            ->orderBy('stage_name')
            ->get();

        $scoped = $this->scoped($artists);

        return view('livewire.artists.directory', [
            'artists' => $this->visible($scoped),
            'lists' => $this->lists($scoped),
            'activeListLabel' => $this->activeListLabel(),
        ])->title('Artistas');
    }

    /**
     * @param  Collection<int, Artist>  $artists
     * @return Collection<int, Artist>
     */
    private function scoped(Collection $artists): Collection
    {
        return $artists
            ->when($this->work === 'booked', fn (Collection $rows): Collection => $rows->filter(fn (Artist $artist): bool => $artist->bookings->isNotEmpty())->values())
            ->when($this->work === 'idle', fn (Collection $rows): Collection => $rows->filter(fn (Artist $artist): bool => $artist->bookings->isEmpty())->values())
            ->when($this->contact === 'phone', fn (Collection $rows): Collection => $rows->filter(fn (Artist $artist): bool => filled($artist->phone))->values())
            ->when($this->contact === 'email', fn (Collection $rows): Collection => $rows->filter(fn (Artist $artist): bool => filled($artist->email))->values())
            ->when($this->contact === 'missing', fn (Collection $rows): Collection => $rows->filter(fn (Artist $artist): bool => blank($artist->phone) && blank($artist->email))->values());
    }

    /**
     * @param  Collection<int, Artist>  $artists
     * @return Collection<int, Artist>
     */
    private function visible(Collection $artists): Collection
    {
        return $artists
            ->when($this->list === 'none', fn (Collection $rows): Collection => $rows->filter(fn (Artist $artist): bool => blank($artist->agency))->values())
            ->when($this->list !== 'all' && $this->list !== 'none', fn (Collection $rows): Collection => $rows->where('agency', $this->list)->values())
            ->when($this->search !== '', function (Collection $rows): Collection {
                $term = mb_strtolower($this->search);

                return $rows->filter(function (Artist $artist) use ($term): bool {
                    $haystack = mb_strtolower(implode(' ', array_filter([
                        $artist->stage_name,
                        $artist->legal_name,
                        $artist->email,
                        $artist->phone,
                        $artist->instagram,
                        $artist->agency,
                    ])));

                    return str_contains($haystack, $term);
                })->values();
            });
    }

    /**
     * @param  Collection<int, Artist>  $artists
     * @return list<array{key: string, label: string, count: int}>
     */
    private function lists(Collection $artists): array
    {
        $lists = [[
            'key' => 'all',
            'label' => 'Todos',
            'count' => $artists->count(),
        ]];

        $agencies = $artists
            ->map(fn (Artist $artist): string => $artist->agency ?: '')
            ->unique()
            ->sort()
            ->values();

        foreach ($agencies as $agency) {
            if ($agency === '') {
                continue;
            }

            $lists[] = [
                'key' => $agency,
                'label' => $agency,
                'count' => $artists->where('agency', $agency)->count(),
            ];
        }

        $unassigned = $artists->filter(fn (Artist $artist): bool => blank($artist->agency));

        if ($unassigned->isNotEmpty()) {
            $lists[] = [
                'key' => 'none',
                'label' => 'Sem agência',
                'count' => $unassigned->count(),
            ];
        }

        return $lists;
    }

    private function activeListLabel(): string
    {
        return match ($this->list) {
            'all' => 'Todos os artistas',
            'none' => 'Sem agência',
            default => $this->list,
        };
    }

    private function artist(int $id): Artist
    {
        return Artist::query()
            ->where('organization_id', auth()->user()->current_organization_id)
            ->findOrFail($id);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->stageName = '';
        $this->legalName = '';
        $this->instagram = '';
        $this->phone = '';
        $this->email = '';
        $this->agency = '';
        $this->defaultFee = '';
        $this->pix = '';
        $this->techRider = '';
        $this->hospitalityRider = '';
        $this->notes = '';
    }

    private function blank(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function money(int $cents): string
    {
        return number_format(intdiv($cents, 100), 0, ',', '.').','.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}

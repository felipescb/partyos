<?php

namespace App\Livewire\Artists;

use App\Domain\Finance\Money;
use App\Models\Artist;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Artistas')]
class ArtistDirectory extends Component
{
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

    public string $search = '';

    public function create(): void
    {
        $this->resetForm();
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
    }

    public function render(): View
    {
        $artists = Artist::query()
            ->where('organization_id', auth()->user()->current_organization_id)
            ->with(['bookings.event', 'bookings.budgetItem'])
            ->when($this->search !== '', fn ($query) => $query->where('stage_name', 'like', '%'.$this->search.'%'))
            ->orderBy('stage_name')
            ->get();

        return view('livewire.artists.directory', [
            'artists' => $artists,
        ]);
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

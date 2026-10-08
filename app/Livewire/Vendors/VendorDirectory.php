<?php

namespace App\Livewire\Vendors;

use App\Domain\Events\OfficialCatalog;
use App\Models\Vendor;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Fornecedores')]
class VendorDirectory extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $company = '';

    public string $category = '';

    public string $phone = '';

    public string $whatsapp = '';

    public string $email = '';

    public string $instagram = '';

    public string $taxId = '';

    public string $pix = '';

    public string $address = '';

    public string $notes = '';

    public string $search = '';

    public function mount(): void
    {
        OfficialCatalog::ensure();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $vendor = $this->vendor($id);
        $this->editingId = $vendor->id;
        $this->name = $vendor->name;
        $this->company = (string) $vendor->company;
        $this->category = (string) $vendor->category;
        $this->phone = (string) $vendor->phone;
        $this->whatsapp = (string) $vendor->whatsapp;
        $this->email = (string) $vendor->email;
        $this->instagram = (string) $vendor->instagram;
        $this->taxId = (string) $vendor->tax_id;
        $this->pix = (string) $vendor->pix;
        $this->address = (string) $vendor->address;
        $this->notes = (string) $vendor->notes;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:140'],
            'company' => ['nullable', 'string', 'max:140'],
            'category' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:40'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:160'],
            'instagram' => ['nullable', 'string', 'max:80'],
            'taxId' => ['nullable', 'string', 'max:30'],
            'pix' => ['nullable', 'string', 'max:140'],
            'address' => ['nullable', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'name.required' => 'Qual é o nome do fornecedor?',
        ]);

        $data = [
            'organization_id' => auth()->user()->current_organization_id,
            'name' => $this->name,
            'company' => $this->blank($this->company),
            'category' => $this->blank($this->category),
            'phone' => $this->blank($this->phone),
            'whatsapp' => $this->blank($this->whatsapp),
            'email' => $this->blank($this->email),
            'instagram' => $this->blank($this->instagram),
            'tax_id' => $this->blank($this->taxId),
            'pix' => $this->blank($this->pix),
            'address' => $this->blank($this->address),
            'notes' => $this->blank($this->notes),
        ];

        if ($this->editingId) {
            $this->vendor($this->editingId)->update($data);
        } else {
            Vendor::query()->create($data);
        }

        $this->showForm = false;
        Flux::toast(variant: 'success', text: 'Fornecedor salvo.');
    }

    public function destroy(int $id): void
    {
        $this->vendor($id)->delete();
        $this->showForm = false;
        Flux::toast(variant: 'success', text: 'Fornecedor removido. O histórico dos eventos permanece nos custos.');
    }

    public function render(): View
    {
        $vendors = Vendor::query()
            ->where('organization_id', auth()->user()->current_organization_id)
            ->with(['budgetItems.event'])
            ->when($this->search !== '', fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'))
            ->orderBy('name')
            ->get();

        return view('livewire.vendors.directory', [
            'vendors' => $vendors,
            'categories' => OfficialCatalog::categories(),
        ]);
    }

    private function vendor(int $id): Vendor
    {
        return Vendor::query()
            ->where('organization_id', auth()->user()->current_organization_id)
            ->findOrFail($id);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->company = '';
        $this->category = '';
        $this->phone = '';
        $this->whatsapp = '';
        $this->email = '';
        $this->instagram = '';
        $this->taxId = '';
        $this->pix = '';
        $this->address = '';
        $this->notes = '';
    }

    private function blank(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }
}

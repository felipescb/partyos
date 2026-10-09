<?php

namespace App\Livewire\Vendors;

use App\Domain\Events\OfficialCatalog;
use App\Models\BudgetItem;
use App\Models\Vendor;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app.dashboard')]
class VendorDirectory extends Component
{
    public string $list = 'all';

    public string $work = 'all';

    public string $contact = 'all';

    public string $search = '';

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

    public function mount(): void
    {
        OfficialCatalog::ensure();
    }

    public function create(): void
    {
        $this->resetForm();

        if ($this->list !== 'all' && $this->list !== 'none') {
            $this->category = $this->list;
        }

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
            ->orderBy('name')
            ->get();

        $scoped = $this->scoped($vendors);

        return view('livewire.vendors.directory', [
            'vendors' => $this->visible($scoped),
            'lists' => $this->lists($scoped),
            'activeListLabel' => $this->activeListLabel(),
            'categories' => OfficialCatalog::categories(),
        ])->title('Fornecedores');
    }

    /**
     * @param  Collection<int, Vendor>  $vendors
     * @return Collection<int, Vendor>
     */
    private function scoped(Collection $vendors): Collection
    {
        return $vendors
            ->when($this->work === 'booked', fn (Collection $rows): Collection => $rows->filter(fn (Vendor $vendor): bool => $vendor->budgetItems->isNotEmpty())->values())
            ->when($this->work === 'idle', fn (Collection $rows): Collection => $rows->filter(fn (Vendor $vendor): bool => $vendor->budgetItems->isEmpty())->values())
            ->when($this->contact === 'whatsapp', fn (Collection $rows): Collection => $rows->filter(fn (Vendor $vendor): bool => filled($vendor->whatsapp))->values())
            ->when($this->contact === 'email', fn (Collection $rows): Collection => $rows->filter(fn (Vendor $vendor): bool => filled($vendor->email))->values())
            ->when($this->contact === 'missing', fn (Collection $rows): Collection => $rows->filter(fn (Vendor $vendor): bool => blank($vendor->phone) && blank($vendor->whatsapp) && blank($vendor->email))->values());
    }

    /**
     * @param  Collection<int, Vendor>  $vendors
     * @return Collection<int, Vendor>
     */
    private function visible(Collection $vendors): Collection
    {
        return $vendors
            ->when($this->list === 'none', fn (Collection $rows): Collection => $rows->filter(fn (Vendor $vendor): bool => blank($vendor->category))->values())
            ->when($this->list !== 'all' && $this->list !== 'none', fn (Collection $rows): Collection => $rows->where('category', $this->list)->values())
            ->when($this->search !== '', function (Collection $rows): Collection {
                $term = mb_strtolower($this->search);

                return $rows->filter(function (Vendor $vendor) use ($term): bool {
                    $haystack = mb_strtolower(implode(' ', array_filter([
                        $vendor->name,
                        $vendor->company,
                        $vendor->email,
                        $vendor->phone,
                        $vendor->whatsapp,
                        $vendor->instagram,
                        $vendor->category,
                    ])));

                    return str_contains($haystack, $term);
                })->values();
            });
    }

    /**
     * @param  Collection<int, Vendor>  $vendors
     * @return list<array{key: string, label: string, count: int, fee: int}>
     */
    private function lists(Collection $vendors): array
    {
        $lists = [[
            'key' => 'all',
            'label' => 'Todos',
            'count' => $vendors->count(),
            'fee' => $this->feeOf($vendors),
        ]];

        $categories = $vendors
            ->map(fn (Vendor $vendor): string => $vendor->category ?: '')
            ->unique()
            ->sort()
            ->values();

        foreach ($categories as $category) {
            if ($category === '') {
                continue;
            }

            $subset = $vendors->where('category', $category);
            $lists[] = [
                'key' => $category,
                'label' => $category,
                'count' => $subset->count(),
                'fee' => $this->feeOf($subset),
            ];
        }

        $uncategorized = $vendors->filter(fn (Vendor $vendor): bool => blank($vendor->category));

        if ($uncategorized->isNotEmpty()) {
            $lists[] = [
                'key' => 'none',
                'label' => 'Sem categoria',
                'count' => $uncategorized->count(),
                'fee' => $this->feeOf($uncategorized),
            ];
        }

        return $lists;
    }

    /**
     * @param  Collection<int, Vendor>  $vendors
     */
    private function feeOf(Collection $vendors): int
    {
        return (int) $vendors->sum(fn (Vendor $vendor): int => $vendor->budgetItems->sum(
            fn (BudgetItem $item): int => $item->committedAmount(),
        ));
    }

    private function activeListLabel(): string
    {
        return match ($this->list) {
            'all' => 'Toda a rede',
            'none' => 'Sem categoria',
            default => $this->list,
        };
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

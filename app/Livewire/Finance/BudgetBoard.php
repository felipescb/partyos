<?php

namespace App\Livewire\Finance;

use App\Domain\Finance\EventFinanceReader;
use App\Domain\Finance\Money;
use App\Domain\Finance\StatementBuilder;
use App\Enums\CostStatus;
use App\Enums\PaymentStatus;
use App\Livewire\Concerns\InteractsWithEvent;
use App\Models\BudgetItem;
use App\Models\CostCategory;
use App\Models\Event;
use App\Models\Vendor;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class BudgetBoard extends Component
{
    use InteractsWithEvent;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $description = '';

    public string $categoryId = '';

    public string $vendorId = '';

    public string $newVendorName = '';

    public string $estimated = '';

    public string $contracted = '';

    public string $dueOn = '';

    public string $status = 'planned';

    public string $paymentMethod = '';

    public string $notes = '';

    public string $paymentAmount = '';

    public string $paymentDate = '';

    public string $paymentStatus = 'paid';

    public function mount(Event $event): void
    {
        $this->mountEvent($event, 'viewFinance');
    }

    public function create(): void
    {
        $this->authorize('manageFinance', $this->event);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $itemId): void
    {
        $this->authorize('manageFinance', $this->event);
        $item = $this->item($itemId);
        $this->editingId = $item->id;
        $this->description = $item->description;
        $this->categoryId = $item->cost_category_id ? (string) $item->cost_category_id : '';
        $this->vendorId = $item->vendor_id ? (string) $item->vendor_id : '';
        $this->newVendorName = '';
        $this->estimated = $this->inputMoney($item->estimated_amount);
        $this->contracted = $item->contracted_amount === null ? '' : $this->inputMoney($item->contracted_amount);
        $this->dueOn = $item->due_on?->toDateString() ?? '';
        $this->status = $item->status->value;
        $this->paymentMethod = (string) $item->payment_method;
        $this->notes = (string) $item->notes;
        $this->paymentAmount = '';
        $this->paymentDate = '';
        $this->paymentStatus = 'paid';
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('manageFinance', $this->event);

        $this->validate([
            'description' => ['required', 'string', 'max:160'],
            'categoryId' => ['nullable', 'integer'],
            'estimated' => ['required', 'string'],
            'contracted' => ['nullable', 'string'],
            'dueOn' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(CostStatus::class)],
            'paymentMethod' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'newVendorName' => ['nullable', 'string', 'max:140'],
        ], [
            'description.required' => 'Descreva o custo.',
            'estimated.required' => 'Diga quanto você espera gastar.',
        ]);

        $vendorId = $this->vendorId !== '' ? (int) $this->vendorId : null;

        if ($this->newVendorName !== '') {
            $vendor = Vendor::query()->create([
                'organization_id' => auth()->user()->current_organization_id,
                'name' => $this->newVendorName,
            ]);
            $vendorId = $vendor->id;
        }

        $budget = $this->event->budget ?: $this->event->budget()->create(['name' => 'Orçamento']);

        $data = [
            'budget_id' => $budget->id,
            'event_id' => $this->event->id,
            'cost_category_id' => $this->categoryId !== '' ? (int) $this->categoryId : null,
            'vendor_id' => $vendorId,
            'description' => $this->description,
            'estimated_amount' => Money::parse($this->estimated),
            'contracted_amount' => trim($this->contracted) === '' ? null : Money::parse($this->contracted),
            'due_on' => $this->dueOn !== '' ? $this->dueOn : null,
            'status' => $this->status,
            'payment_method' => $this->paymentMethod !== '' ? $this->paymentMethod : null,
            'notes' => $this->notes !== '' ? $this->notes : null,
        ];

        if ($this->editingId) {
            $item = $this->item($this->editingId);
            $item->update($data);
            $item->refreshPaymentStatus();
        } else {
            $item = $this->event->budgetItems()->create($data);
            $item->refreshPaymentStatus();
        }

        $this->showForm = false;
        $this->reloadEvent();
        Flux::toast(variant: 'success', text: 'Custo salvo.');
    }

    public function addPayment(): void
    {
        $this->authorize('manageFinance', $this->event);
        $item = $this->item((int) $this->editingId);

        $this->validate([
            'paymentAmount' => ['required', 'string'],
            'paymentDate' => ['nullable', 'date'],
            'paymentStatus' => ['required', Rule::enum(PaymentStatus::class)],
        ], [
            'paymentAmount.required' => 'Informe o valor do pagamento.',
        ]);

        $amount = Money::parse($this->paymentAmount);

        if ($amount <= 0) {
            $this->addError('paymentAmount', 'O pagamento precisa ser maior que zero.');

            return;
        }

        $status = PaymentStatus::from($this->paymentStatus);

        $item->payments()->create([
            'event_id' => $this->event->id,
            'amount' => $amount,
            'due_on' => $this->paymentDate !== '' ? $this->paymentDate : null,
            'paid_on' => $status === PaymentStatus::Paid ? ($this->paymentDate !== '' ? $this->paymentDate : now()->toDateString()) : null,
            'status' => $status,
            'method' => $this->paymentMethod !== '' ? $this->paymentMethod : null,
            'recorded_by' => auth()->id(),
        ]);

        $item->refreshPaymentStatus();
        $this->paymentAmount = '';
        $this->edit($item->id);
        Flux::toast(variant: 'success', text: 'Pagamento registrado.');
    }

    public function removePayment(int $paymentId): void
    {
        $this->authorize('manageFinance', $this->event);
        $payment = $this->event->payments()->findOrFail($paymentId);
        $itemId = $payment->budget_item_id;
        $payment->delete();
        $this->item($itemId)->refreshPaymentStatus();
        $this->edit($itemId);
    }

    public function destroy(int $itemId): void
    {
        $this->authorize('manageFinance', $this->event);
        $item = $this->item($itemId);

        if ($item->booking()->exists()) {
            Flux::toast(variant: 'danger', text: 'Esse custo é o cachê de um artista. Remova o artista do lineup.');

            return;
        }

        $item->delete();
        $this->showForm = false;
        Flux::toast(variant: 'success', text: 'Custo removido.');
    }

    public function render(EventFinanceReader $reader, StatementBuilder $builder): View
    {
        $organizationId = auth()->user()->current_organization_id;
        $items = $this->event->budgetItems()->with(['category', 'vendor', 'payments', 'booking'])->orderBy('description')->get();
        $input = $reader->read($this->event);

        return view('livewire.finance.budget', [
            'items' => $items,
            'statement' => $builder->statement($input),
            'categories' => CostCategory::query()
                ->where(function ($query) use ($organizationId): void {
                    $query->where('is_system', true)->orWhere('organization_id', $organizationId);
                })
                ->orderBy('name')
                ->get(),
            'vendors' => Vendor::query()->where('organization_id', $organizationId)->orderBy('name')->get(),
            'statuses' => CostStatus::cases(),
            'canEdit' => auth()->user()->can('manageFinance', $this->event),
            'editingPayments' => $this->editingId
                ? $this->event->payments()->where('budget_item_id', $this->editingId)->orderBy('due_on')->get()
                : collect(),
        ])->title('Custos · '.$this->event->name);
    }

    private function item(int $id): BudgetItem
    {
        return $this->event->budgetItems()->findOrFail($id);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->description = '';
        $this->categoryId = '';
        $this->vendorId = '';
        $this->newVendorName = '';
        $this->estimated = '';
        $this->contracted = '';
        $this->dueOn = '';
        $this->status = CostStatus::Planned->value;
        $this->paymentMethod = '';
        $this->notes = '';
    }

    private function inputMoney(int $cents): string
    {
        $whole = intdiv(abs($cents), 100);
        $fraction = str_pad((string) (abs($cents) % 100), 2, '0', STR_PAD_LEFT);

        return number_format($whole, 0, ',', '.').','.$fraction;
    }
}

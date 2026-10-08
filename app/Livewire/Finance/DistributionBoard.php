<?php

namespace App\Livewire\Finance;

use App\Domain\Finance\Money;
use App\Enums\AdjustmentKind;
use App\Enums\RevenueCategory;
use App\Livewire\Concerns\InteractsWithEvent;
use App\Models\Event;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class DistributionBoard extends Component
{
    use InteractsWithEvent;

    public string $feeName = '';

    public string $feeApplies = 'tickets';

    public string $feeKind = 'percent';

    public string $feeValue = '';

    public string $beneficiary = '';

    public string $shareApplies = 'tickets';

    public string $shareKind = 'percent';

    public string $shareValue = '';

    public function mount(Event $event): void
    {
        $this->mountEvent($event, 'viewFinance');
    }

    public function addFee(): void
    {
        $this->authorize('manageFinance', $this->event);
        $this->validate([
            'feeName' => ['required', 'string', 'max:80'],
            'feeApplies' => ['required', 'string'],
            'feeKind' => ['required', Rule::enum(AdjustmentKind::class)],
            'feeValue' => ['required', 'string'],
        ], [
            'feeName.required' => 'Dê um nome para a taxa.',
            'feeValue.required' => 'Informe o percentual ou o valor.',
        ]);

        $this->event->fees()->create($this->adjustment($this->feeName, $this->feeApplies, $this->feeKind, $this->feeValue) + [
            'name' => $this->feeName,
        ]);
        $this->reset('feeName', 'feeValue');
        Flux::toast(variant: 'success', text: 'Taxa adicionada.');
    }

    public function addShare(): void
    {
        $this->authorize('manageFinance', $this->event);
        $this->validate([
            'beneficiary' => ['required', 'string', 'max:120'],
            'shareApplies' => ['required', 'string'],
            'shareKind' => ['required', Rule::enum(AdjustmentKind::class)],
            'shareValue' => ['required', 'string'],
        ], [
            'beneficiary.required' => 'Quem recebe essa parte?',
            'shareValue.required' => 'Informe o percentual ou o valor.',
        ]);

        $this->event->distributions()->create($this->adjustment($this->beneficiary, $this->shareApplies, $this->shareKind, $this->shareValue) + [
            'beneficiary_name' => $this->beneficiary,
        ]);
        $this->reset('beneficiary', 'shareValue');
        Flux::toast(variant: 'success', text: 'Divisão adicionada.');
    }

    public function deleteFee(int $id): void
    {
        $this->authorize('manageFinance', $this->event);
        $this->event->fees()->findOrFail($id)->delete();
    }

    public function deleteShare(int $id): void
    {
        $this->authorize('manageFinance', $this->event);
        $this->event->distributions()->findOrFail($id)->delete();
    }

    public function render(): View
    {
        return view('livewire.finance.distribution', [
            'fees' => $this->event->fees()->get(),
            'shares' => $this->event->distributions()->get(),
            'bases' => $this->bases(),
            'canEdit' => auth()->user()->can('manageFinance', $this->event),
        ])->title('Divisão · '.$this->event->name);
    }

    /**
     * @return array<string, mixed>
     */
    private function adjustment(string $label, string $applies, string $kind, string $value): array
    {
        $kindEnum = AdjustmentKind::from($kind);

        return [
            'event_id' => $this->event->id,
            'applies_to' => $applies === 'all' || $applies === 'tickets' ? $applies : 'category',
            'revenue_category' => in_array($applies, ['all', 'tickets'], true) ? ($applies === 'tickets' ? 'tickets' : null) : $applies,
            'kind' => $kindEnum,
            'basis_points' => $kindEnum === AdjustmentKind::Percent ? Money::parse($value) : null,
            'amount' => $kindEnum === AdjustmentKind::Fixed ? Money::parse($value) : null,
            'sort_order' => 0,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function bases(): array
    {
        $bases = ['all' => 'Toda a receita', 'tickets' => 'Ingressos'];

        foreach (RevenueCategory::cases() as $category) {
            if ($category->isManual()) {
                $bases[$category->value] = $category->label();
            }
        }

        return $bases;
    }
}

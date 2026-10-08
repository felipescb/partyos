<?php

namespace App\Livewire\Finance;

use App\Domain\Events\ScenarioPresets;
use App\Domain\Finance\EventFinanceReader;
use App\Domain\Finance\Money;
use App\Livewire\Concerns\InteractsWithEvent;
use App\Models\Event;
use App\Models\FinancialScenario;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ScenarioBoard extends Component
{
    use InteractsWithEvent;

    public ?int $scenarioId = null;

    public string $attendance = '';

    /** @var array<int, string> */
    public array $quantities = [];

    /** @var array<int, string> */
    public array $costAmounts = [];

    /** @var array<int, string> */
    public array $revenueAmounts = [];

    public bool $confirmReplace = false;

    public function mount(Event $event): void
    {
        $this->mountEvent($event, 'viewFinance');
    }

    public function generate(ScenarioPresets $presets, bool $replace = false): void
    {
        $this->authorize('manageFinance', $this->event);
        $presets->generate($this->event, $replace);
        $this->confirmReplace = false;
        $this->scenarioId = null;
        Flux::toast(variant: 'success', text: $replace ? 'Cenários refeitos a partir do evento de agora.' : 'Cenários criados.');
    }

    public function select(int $id): void
    {
        $scenario = $this->scenario($id);
        $this->scenarioId = $scenario->id;
        $this->attendance = (string) ($scenario->attendance ?? '');
        $this->quantities = [];
        $this->costAmounts = [];
        $this->revenueAmounts = [];

        foreach ($scenario->lines as $line) {
            if ($line->line_type === 'ticket' && $line->reference_id) {
                $this->quantities[$line->reference_id] = (string) ($line->quantity ?? 0);
            }

            if ($line->line_type === 'cost' && $line->reference_id) {
                $this->costAmounts[$line->reference_id] = $this->money((int) ($line->amount ?? 0));
            }

            if ($line->line_type === 'revenue' && $line->reference_id) {
                $this->revenueAmounts[$line->reference_id] = $this->money((int) ($line->amount ?? 0));
            }
        }
    }

    public function applyAttendance(ScenarioPresets $presets): void
    {
        $tiers = $this->event->ticketTiers()->get();
        $scaled = $presets->scale($tiers, max(0, (int) $this->attendance));

        foreach ($scaled as $id => $quantity) {
            $this->quantities[$id] = (string) $quantity;
        }
    }

    public function save(): void
    {
        $this->authorize('manageFinance', $this->event);
        $scenario = $this->scenario((int) $this->scenarioId);
        $scenario->update([
            'attendance' => $this->attendance !== '' ? (int) $this->attendance : null,
        ]);
        $scenario->lines()->delete();

        foreach ($this->event->ticketTiers as $tier) {
            $scenario->lines()->create([
                'line_type' => 'ticket',
                'reference_id' => $tier->id,
                'label' => $tier->name,
                'quantity' => max(0, (int) ($this->quantities[$tier->id] ?? 0)),
            ]);
        }

        foreach ($this->event->revenues as $revenue) {
            $scenario->lines()->create([
                'line_type' => 'revenue',
                'reference_id' => $revenue->id,
                'label' => $revenue->description,
                'amount' => Money::parse($this->revenueAmounts[$revenue->id] ?? '0'),
            ]);
        }

        foreach ($this->event->budgetItems as $item) {
            if ($item->status->value === 'cancelled') {
                continue;
            }

            $scenario->lines()->create([
                'line_type' => 'cost',
                'reference_id' => $item->id,
                'label' => $item->description,
                'amount' => Money::parse($this->costAmounts[$item->id] ?? '0'),
            ]);
        }

        Flux::toast(variant: 'success', text: 'Cenário atualizado.');
    }

    public function render(EventFinanceReader $reader): View
    {
        $scenarios = $this->event->scenarios()->with('lines')->get();
        $projections = $scenarios->mapWithKeys(fn (FinancialScenario $scenario): array => [
            $scenario->id => $reader->projectScenario($this->event, $scenario),
        ]);

        return view('livewire.finance.scenarios', [
            'scenarios' => $scenarios,
            'projections' => $projections,
            'tiers' => $this->event->ticketTiers,
            'costs' => $this->event->budgetItems()->where('status', '!=', 'cancelled')->get(),
            'revenues' => $this->event->revenues,
            'canEdit' => auth()->user()->can('manageFinance', $this->event),
        ])->title('Cenários · '.$this->event->name);
    }

    private function scenario(int $id): FinancialScenario
    {
        return $this->event->scenarios()->with('lines')->findOrFail($id);
    }

    private function money(int $cents): string
    {
        return number_format(intdiv($cents, 100), 0, ',', '.').','.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}

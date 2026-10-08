<?php

namespace App\Livewire\Finance;

use App\Enums\CostStatus;
use App\Enums\PaymentStatus;
use App\Livewire\Concerns\InteractsWithEvent;
use App\Models\Event;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CashflowBoard extends Component
{
    use InteractsWithEvent;

    public string $window = '30';

    public function mount(Event $event): void
    {
        $this->mountEvent($event, 'viewFinance');
    }

    public function render(): View
    {
        return view('livewire.finance.cashflow', [
            'rows' => $this->rows(),
        ])->title('Fluxo de caixa · '.$this->event->name);
    }

    /**
     * @return Collection<int, array{date: ?string, label: string, amount: int, state: string, detail: string}>
     */
    private function rows(): Collection
    {
        $items = $this->event->budgetItems()->with('payments')->get();
        $rows = collect();

        foreach ($items as $item) {
            if ($item->status === CostStatus::Cancelled) {
                continue;
            }

            $scheduled = 0;
            $paid = 0;

            foreach ($item->payments as $payment) {
                if ($payment->status === PaymentStatus::Cancelled) {
                    continue;
                }

                if ($payment->status === PaymentStatus::Paid) {
                    $paid += $payment->amount;
                } else {
                    $scheduled += $payment->amount;
                }

                $rows->push([
                    'date' => ($payment->status === PaymentStatus::Paid ? $payment->paid_on : $payment->due_on)?->toDateString(),
                    'label' => $item->description,
                    'amount' => $payment->amount,
                    'state' => $payment->status->label(),
                    'detail' => $payment->status === PaymentStatus::Paid ? 'Saiu' : 'Comprometido',
                ]);
            }

            $gap = $item->committedAmount() - $paid - $scheduled;

            if ($gap > 0) {
                $rows->push([
                    'date' => $item->due_on?->toDateString(),
                    'label' => $item->description,
                    'amount' => $gap,
                    'state' => $item->due_on ? 'A pagar' : 'Sem data',
                    'detail' => 'Ainda não virou pagamento',
                ]);
            }
        }

        $start = now()->startOfDay();
        $end = match ($this->window) {
            'today' => $start->copy(),
            '7' => $start->copy()->addDays(7),
            '30' => $start->copy()->addDays(30),
            default => null,
        };

        return $rows
            ->filter(function (array $row) use ($start, $end): bool {
                if ($this->window === 'all' || $row['date'] === null) {
                    return $this->window === 'all' || $row['date'] === null;
                }

                $date = Carbon::parse($row['date'])->startOfDay();

                if ($end === null) {
                    return true;
                }

                return $date->betweenIncluded($start, $end);
            })
            ->sortBy(fn (array $row): string => $row['date'] ?? '9999-99-99')
            ->values();
    }
}

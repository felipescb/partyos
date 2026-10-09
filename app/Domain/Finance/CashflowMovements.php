<?php

namespace App\Domain\Finance;

use App\Enums\CostStatus;
use App\Enums\PaymentStatus;
use App\Models\Event;
use Illuminate\Support\Collection;

class CashflowMovements
{
    /**
     * @return Collection<int, array{date: ?string, label: string, amount: int, state: string, detail: string, tone: string, category: ?string, categorySlug: ?string}>
     */
    public function rows(Event $event): Collection
    {
        $items = $event->budgetItems()->with(['payments', 'category'])->get();
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
                    'tone' => $payment->status === PaymentStatus::Paid ? 'paid' : 'scheduled',
                    'category' => $item->category?->name,
                    'categorySlug' => $item->category?->slug,
                ]);
            }

            $gap = $item->committedAmount() - $paid - $scheduled;

            if ($gap > 0) {
                $rows->push([
                    'date' => $item->due_on?->toDateString(),
                    'label' => $item->description,
                    'amount' => $gap,
                    'state' => 'A pagar',
                    'detail' => 'Ainda não virou pagamento',
                    'tone' => 'open',
                    'category' => $item->category?->name,
                    'categorySlug' => $item->category?->slug,
                ]);
            }
        }

        return $rows->values();
    }
}

<?php

namespace App\Domain\Finance;

use App\Models\Payment;

final class EventAlerts
{
    /**
     * @param  iterable<int, Payment>  $payments
     * @return list<Alert>
     */
    public function make(Statement $statement, FinanceInput $input, iterable $payments): array
    {
        $alerts = [];
        $gap = $statement->additionalTicketsToBreakEven();

        if ($gap !== null && $gap > 0) {
            $alerts[] = new Alert(
                'warning',
                'Você precisa vender mais '.$gap.' '.($gap === 1 ? 'ingresso' : 'ingressos').' para empatar.',
                'events.tickets',
            );
        }

        $unassigned = 0;

        foreach ($input->costs as $cost) {
            if ($cost->cancelled || $cost->hasVendor) {
                continue;
            }

            $unassigned += $cost->committed();
        }

        if ($unassigned > 0) {
            $alerts[] = new Alert(
                'warning',
                Money::format($unassigned, $statement->currency).' em custos ainda não têm fornecedor.',
                'events.costs',
            );
        }

        if ($statement->estimatedCosts > 0 && $statement->committedCosts > $statement->estimatedCosts) {
            $delta = intdiv(($statement->committedCosts - $statement->estimatedCosts) * 10000, $statement->estimatedCosts);

            if ($delta >= 1500) {
                $alerts[] = new Alert(
                    'warning',
                    'Seu custo subiu '.Money::formatPercent($delta).' em relação ao que você estimou.',
                    'events.costs',
                );
            }
        }

        $tomorrow = now()->addDay()->toDateString();
        $today = now()->toDateString();

        foreach ($payments as $payment) {
            if ($payment->status->value !== 'scheduled' || $payment->due_on === null) {
                continue;
            }

            $due = $payment->due_on->toDateString();

            if ($due < $today) {
                $alerts[] = new Alert(
                    'danger',
                    'O pagamento de '.$payment->budgetItem?->description.' ('.Money::format($payment->amount, $statement->currency).') está atrasado.',
                    'events.cashflow',
                );
            } elseif ($due <= $tomorrow) {
                $alerts[] = new Alert(
                    'warning',
                    'O pagamento de '.$payment->budgetItem?->description.' ('.Money::format($payment->amount, $statement->currency).') vence '.($due === $today ? 'hoje' : 'amanhã').'.',
                    'events.cashflow',
                );
            }
        }

        if ($statement->ticketsGoal === 0 && $statement->committedCosts > 0) {
            $alerts[] = new Alert(
                'info',
                'Defina a meta de cada lote para o PartyOS calcular quantas pessoas você precisa vender.',
                'events.tickets',
            );
        }

        return $alerts;
    }
}

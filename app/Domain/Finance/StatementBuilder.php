<?php

namespace App\Domain\Finance;

/**
 * Receita bruta - taxas - distribuições - custos = resultado.
 *
 * Percentuais incidem sobre a base configurada (bilheteria, uma receita ou tudo)
 * e não se acumulam uns sobre os outros. Valores fixos saem inteiros.
 * O ponto de empate usa o público da meta e o preço de cada lote.
 */
final class StatementBuilder
{
    public function statement(FinanceInput $input): Statement
    {
        $expectedTickets = 0;
        $actualTickets = 0;
        $goalHeads = 0;
        $soldHeads = 0;
        $capacityHeads = 0;

        foreach ($input->tickets as $ticket) {
            $expectedTickets += Money::portion($ticket->price * max(0, $ticket->goal), $ticket->payoutBasisPoints);
            $actualTickets += Money::portion($ticket->price * max(0, $ticket->sold), $ticket->payoutBasisPoints);
            $goalHeads += max(0, $ticket->goal);
            $soldHeads += max(0, $ticket->sold);
            $capacityHeads += max(0, $ticket->quantity);
        }

        [$expectedOther, $actualOther, $expectedOtherByCategory, $actualOtherByCategory] = $this->revenues($input->revenues);

        $estimated = 0;
        $contracted = 0;
        $committed = 0;
        $paid = 0;
        $remaining = 0;
        $nonArtist = 0;

        foreach ($input->costs as $cost) {
            if ($cost->cancelled) {
                continue;
            }

            $estimated += $cost->estimated;

            if ($cost->contracted !== null) {
                $contracted += $cost->contracted;
            }

            $itemCommitted = $cost->contracted ?? $cost->estimated;
            $committed += $itemCommitted;
            $paid += $cost->paid;
            $remaining += $itemCommitted - $cost->paid;

            if (! $cost->isArtistFee()) {
                $nonArtist += $itemCommitted;
            }
        }

        $expectedFees = $this->charges($expectedTickets, $expectedOther, $expectedOtherByCategory, $input->fees);
        $actualFees = $this->charges($actualTickets, $actualOther, $actualOtherByCategory, $input->fees);
        $expectedDistributions = $this->charges($expectedTickets, $expectedOther, $expectedOtherByCategory, $input->distributions);
        $actualDistributions = $this->charges($actualTickets, $actualOther, $actualOtherByCategory, $input->distributions);

        $expectedGross = $expectedTickets + $expectedOther;
        $actualGross = $actualTickets + $actualOther;
        $expectedNet = $expectedGross - $expectedFees - $expectedDistributions;
        $actualNet = $actualGross - $actualFees - $actualDistributions;

        $breakEven = $this->breakEven(
            $committed,
            $expectedOther,
            $expectedOtherByCategory,
            $input->fees,
            $input->distributions,
            $expectedTickets,
            $goalHeads,
        );

        return new Statement(
            expectedTicketRevenue: $expectedTickets,
            actualTicketRevenue: $actualTickets,
            expectedOtherRevenue: $expectedOther,
            actualOtherRevenue: $actualOther,
            expectedGross: $expectedGross,
            actualGross: $actualGross,
            expectedFees: $expectedFees,
            actualFees: $actualFees,
            expectedDistributions: $expectedDistributions,
            actualDistributions: $actualDistributions,
            expectedNet: $expectedNet,
            actualNet: $actualNet,
            estimatedCosts: $estimated,
            contractedCosts: $contracted,
            committedCosts: $committed,
            paidCosts: $paid,
            remainingCosts: $remaining,
            projectedProfit: $expectedNet - $committed,
            currentResult: $actualNet - $committed,
            ticketsSold: $soldHeads,
            ticketsGoal: $goalHeads,
            ticketsCapacity: $capacityHeads,
            averageTicket: $goalHeads > 0 ? intdiv($expectedTickets, $goalHeads) : null,
            breakEvenPeople: $breakEven['people'],
            breakEvenRevenue: $breakEven['revenue'],
            breakEvenAverageTicket: $breakEven['average'],
            spendingCeiling: $expectedNet,
            feeCeiling: $expectedNet - $nonArtist,
            confirmedGuestHeads: $input->confirmedGuestHeads,
            currency: $input->currency,
        );
    }

    public function project(FinanceInput $input): Projection
    {
        $ticketGross = 0;
        $heads = 0;

        foreach ($input->tickets as $ticket) {
            $sold = $ticket->projectedSold ?? $ticket->goal;
            $ticketGross += Money::portion($ticket->price * max(0, $sold), $ticket->payoutBasisPoints);
            $heads += max(0, $sold);
        }

        $other = 0;
        $otherByCategory = [];

        foreach ($input->revenues as $revenue) {
            if ($revenue->cancelled) {
                continue;
            }

            $amount = $revenue->projectedAmount ?? $revenue->expected;
            $other += $amount;
            $otherByCategory[$revenue->category] = ($otherByCategory[$revenue->category] ?? 0) + $amount;
        }

        $costs = 0;

        foreach ($input->costs as $cost) {
            if ($cost->cancelled) {
                continue;
            }

            $costs += $cost->projectedAmount ?? $cost->contracted ?? $cost->estimated;
        }

        $fees = $this->charges($ticketGross, $other, $otherByCategory, $input->fees);
        $distributions = $this->charges($ticketGross, $other, $otherByCategory, $input->distributions);
        $gross = $ticketGross + $other;
        $net = $gross - $fees - $distributions;

        return new Projection(
            attendance: $heads,
            gross: $gross,
            fees: $fees,
            distributions: $distributions,
            net: $net,
            costs: $costs,
            result: $net - $costs,
            averageTicket: $heads > 0 ? intdiv($ticketGross, $heads) : null,
        );
    }

    /**
     * @param  list<RevenueLine>  $revenues
     * @return array{0: int, 1: int, 2: array<string, int>, 3: array<string, int>}
     */
    private function revenues(array $revenues): array
    {
        $expected = 0;
        $actual = 0;
        $expectedByCategory = [];
        $actualByCategory = [];

        foreach ($revenues as $revenue) {
            if ($revenue->cancelled) {
                continue;
            }

            $expected += $revenue->expected;
            $actual += $revenue->actual;
            $expectedByCategory[$revenue->category] = ($expectedByCategory[$revenue->category] ?? 0) + $revenue->expected;
            $actualByCategory[$revenue->category] = ($actualByCategory[$revenue->category] ?? 0) + $revenue->actual;
        }

        return [$expected, $actual, $expectedByCategory, $actualByCategory];
    }

    /**
     * @param  array<string, int>  $otherByCategory
     * @param  list<Adjustment>  $adjustments
     */
    public function charges(int $ticketGross, int $otherGross, array $otherByCategory, array $adjustments): int
    {
        $total = 0;

        foreach ($adjustments as $adjustment) {
            if ($adjustment->kind === 'fixed') {
                $total += $adjustment->amount;

                continue;
            }

            $base = match ($adjustment->appliesTo) {
                'tickets' => $ticketGross,
                'all' => $ticketGross + $otherGross,
                'category' => $adjustment->category === 'tickets'
                    ? $ticketGross
                    : ($otherByCategory[$adjustment->category] ?? 0),
                default => 0,
            };

            $total += Money::portion($base, $adjustment->basisPoints);
        }

        return $total;
    }

    /**
     * @param  array<string, int>  $otherByCategory
     * @param  list<Adjustment>  $fees
     * @param  list<Adjustment>  $distributions
     * @return array{people: ?int, revenue: ?int, average: ?int}
     */
    private function breakEven(
        int $costs,
        int $otherRevenue,
        array $otherByCategory,
        array $fees,
        array $distributions,
        int $ticketGross,
        int $ticketHeads,
    ): array {
        $adjustments = [...$fees, ...$distributions];
        $otherNet = $otherRevenue;

        foreach ($adjustments as $adjustment) {
            if ($adjustment->kind !== 'percent') {
                continue;
            }

            if ($adjustment->appliesTo === 'all') {
                $otherNet -= Money::portion($otherRevenue, $adjustment->basisPoints);
            } elseif ($adjustment->appliesTo === 'category' && $adjustment->category !== null && $adjustment->category !== 'tickets') {
                $otherNet -= Money::portion($otherByCategory[$adjustment->category] ?? 0, $adjustment->basisPoints);
            }
        }

        $fixed = 0;

        foreach ($adjustments as $adjustment) {
            if ($adjustment->kind === 'fixed') {
                $fixed += $adjustment->amount;
            }
        }

        $burden = $costs + $fixed - $otherNet;

        if ($burden <= 0) {
            return ['people' => 0, 'revenue' => 0, 'average' => 0];
        }

        $keepBasisPoints = 10000;

        foreach ($adjustments as $adjustment) {
            if ($adjustment->kind !== 'percent') {
                continue;
            }

            $hitsTickets = $adjustment->appliesTo === 'tickets'
                || $adjustment->appliesTo === 'all'
                || ($adjustment->appliesTo === 'category' && $adjustment->category === 'tickets');

            if ($hitsTickets) {
                $keepBasisPoints -= $adjustment->basisPoints;
            }
        }

        if ($keepBasisPoints <= 0 || $ticketHeads <= 0 || $ticketGross <= 0) {
            return ['people' => null, 'revenue' => null, 'average' => null];
        }

        $grossNeeded = Money::ceilDiv($burden * 10000, $keepBasisPoints);

        return [
            'people' => Money::ceilDiv($grossNeeded * $ticketHeads, $ticketGross),
            'revenue' => $grossNeeded,
            'average' => Money::ceilDiv($grossNeeded, $ticketHeads),
        ];
    }
}

<?php

namespace App\Domain\Finance;

use App\Enums\PaymentStatus;
use App\Models\CostCategory;
use App\Models\Event;
use App\Models\EventFee;
use App\Models\FinancialScenario;
use App\Models\RevenueDistribution;

final class EventFinanceReader
{
    public function __construct(private StatementBuilder $builder) {}

    public function read(Event $event): FinanceInput
    {
        $event->loadMissing([
            'ticketTiers',
            'revenues',
            'fees',
            'distributions',
            'budgetItems.category',
            'budgetItems.payments',
            'budgetItems.vendor',
            'guests',
        ]);

        $tickets = [];

        foreach ($event->ticketTiers as $tier) {
            $tickets[] = new TicketLine(
                id: $tier->id,
                price: $tier->price,
                quantity: $tier->quantity,
                goal: $tier->goal,
                sold: $tier->sold_quantity,
            );
        }

        $revenues = [];

        foreach ($event->revenues as $revenue) {
            $revenues[] = new RevenueLine(
                id: $revenue->id,
                category: $revenue->category->value,
                expected: $revenue->expected_amount,
                actual: $revenue->actual_amount,
                cancelled: $revenue->status->value === 'cancelled',
            );
        }

        $costs = [];

        foreach ($event->budgetItems as $item) {
            $paid = (int) $item->payments
                ->where('status', PaymentStatus::Paid)
                ->sum('amount');

            $category = $item->category;

            $costs[] = new CostLine(
                id: $item->id,
                label: $item->description,
                categorySlug: $category instanceof CostCategory ? $category->slug : '',
                estimated: $item->estimated_amount,
                contracted: $item->contracted_amount,
                paid: $paid,
                cancelled: $item->status->value === 'cancelled',
                hasVendor: $item->vendor_id !== null,
                dueOn: $item->due_on?->toDateString(),
            );
        }

        return new FinanceInput(
            tickets: $tickets,
            revenues: $revenues,
            costs: $costs,
            fees: $this->adjustments($event->fees),
            distributions: $this->adjustments($event->distributions),
            confirmedGuestHeads: $event->confirmedGuestHeads(),
            capacity: $event->capacity ?? 0,
            currency: $event->currency,
        );
    }

    public function statement(Event $event): Statement
    {
        return $this->builder->statement($this->read($event));
    }

    public function projectScenario(Event $event, FinancialScenario $scenario): Projection
    {
        $input = $this->read($event);
        $scenario->loadMissing('lines');

        $ticketOverrides = [];
        $revenueOverrides = [];
        $costOverrides = [];

        foreach ($scenario->lines as $line) {
            if ($line->line_type === 'ticket' && $line->reference_id !== null) {
                $ticketOverrides[$line->reference_id] = $line->quantity ?? 0;
            }

            if ($line->line_type === 'revenue' && $line->reference_id !== null) {
                $revenueOverrides[$line->reference_id] = $line->amount ?? 0;
            }

            if ($line->line_type === 'cost' && $line->reference_id !== null) {
                $costOverrides[$line->reference_id] = $line->amount ?? 0;
            }
        }

        $tickets = array_map(function (TicketLine $ticket) use ($ticketOverrides): TicketLine {
            return new TicketLine(
                id: $ticket->id,
                price: $ticket->price,
                quantity: $ticket->quantity,
                goal: $ticket->goal,
                sold: $ticket->sold,
                projectedSold: $ticketOverrides[$ticket->id] ?? $ticket->goal,
            );
        }, $input->tickets);

        $revenues = array_map(function (RevenueLine $revenue) use ($revenueOverrides): RevenueLine {
            return new RevenueLine(
                id: $revenue->id,
                category: $revenue->category,
                expected: $revenue->expected,
                actual: $revenue->actual,
                cancelled: $revenue->cancelled,
                projectedAmount: $revenueOverrides[$revenue->id] ?? $revenue->expected,
            );
        }, $input->revenues);

        $costs = array_map(function (CostLine $cost) use ($costOverrides): CostLine {
            return new CostLine(
                id: $cost->id,
                label: $cost->label,
                categorySlug: $cost->categorySlug,
                estimated: $cost->estimated,
                contracted: $cost->contracted,
                paid: $cost->paid,
                cancelled: $cost->cancelled,
                hasVendor: $cost->hasVendor,
                dueOn: $cost->dueOn,
                projectedAmount: $costOverrides[$cost->id] ?? $cost->committed(),
            );
        }, $input->costs);

        return $this->builder->project(new FinanceInput(
            tickets: $tickets,
            revenues: $revenues,
            costs: $costs,
            fees: $input->fees,
            distributions: $input->distributions,
            confirmedGuestHeads: $input->confirmedGuestHeads,
            capacity: $input->capacity,
            currency: $input->currency,
        ));
    }

    /**
     * @template T of EventFee|RevenueDistribution
     *
     * @param  iterable<int, T>  $rows
     * @return list<Adjustment>
     */
    private function adjustments(iterable $rows): array
    {
        $adjustments = [];

        foreach ($rows as $row) {
            $adjustments[] = new Adjustment(
                kind: $row->kind->value,
                basisPoints: $row->basis_points ?? 0,
                amount: $row->amount ?? 0,
                appliesTo: $row->applies_to,
                category: $row->revenue_category,
            );
        }

        return $adjustments;
    }
}

<?php

namespace Tests\Unit\Finance;

use App\Domain\Finance\Adjustment;
use App\Domain\Finance\CostLine;
use App\Domain\Finance\FinanceInput;
use App\Domain\Finance\RevenueLine;
use App\Domain\Finance\StatementBuilder;
use App\Domain\Finance\TicketLine;
use PHPUnit\Framework\TestCase;

class StatementBuilderTest extends TestCase
{
    public function test_it_calculates_profit_and_break_even_from_tickets_fees_and_costs(): void
    {
        $statement = (new StatementBuilder)->statement(new FinanceInput(
            tickets: [
                new TicketLine(1, 2500, 15, 15, 10),
                new TicketLine(2, 3500, 20, 20, 0),
            ],
            revenues: [
                new RevenueLine(1, 'sponsorship', 50000, 0, false),
            ],
            costs: [
                new CostLine(1, 'Fotografia', 'fotografia', 100000, 80000, 40000, false, true),
            ],
            fees: [
                new Adjustment('percent', 1000, 0, 'tickets', null),
            ],
            distributions: [
                new Adjustment('percent', 2500, 0, 'tickets', null),
            ],
        ));

        $this->assertSame(107500, $statement->expectedTicketRevenue);
        $this->assertSame(25000, $statement->actualTicketRevenue);
        $this->assertSame(10750, $statement->expectedFees);
        $this->assertSame(26875, $statement->expectedDistributions);
        $this->assertSame(119875, $statement->expectedNet);
        $this->assertSame(16250, $statement->actualNet);
        $this->assertSame(80000, $statement->committedCosts);
        $this->assertSame(40000, $statement->paidCosts);
        $this->assertSame(40000, $statement->remainingCosts);
        $this->assertSame(39875, $statement->projectedProfit);
        $this->assertSame(-63750, $statement->currentResult);
        $this->assertSame(16, $statement->breakEvenPeople);
        $this->assertSame(46154, $statement->breakEvenRevenue);
        $this->assertSame(6, $statement->additionalTicketsToBreakEven());
    }

    public function test_break_even_is_zero_when_other_revenue_covers_the_event(): void
    {
        $statement = (new StatementBuilder)->statement(new FinanceInput(
            tickets: [new TicketLine(1, 5000, 100, 40, 0)],
            revenues: [new RevenueLine(1, 'sponsorship', 200000, 200000, false)],
            costs: [new CostLine(1, 'Local', 'venue', 80000, 80000, 0, false, true)],
            fees: [],
            distributions: [],
        ));

        $this->assertSame(0, $statement->breakEvenPeople);
        $this->assertSame(0, $statement->additionalTicketsToBreakEven());
    }

    public function test_break_even_is_unknown_without_a_ticket_mix(): void
    {
        $statement = (new StatementBuilder)->statement(new FinanceInput(
            tickets: [],
            revenues: [],
            costs: [new CostLine(1, 'Som', 'som', 50000, null, 0, false, false)],
            fees: [],
            distributions: [],
        ));

        $this->assertNull($statement->breakEvenPeople);
        $this->assertSame(50000, $statement->committedCosts);
    }

    public function test_cancelled_lines_do_not_affect_the_result(): void
    {
        $statement = (new StatementBuilder)->statement(new FinanceInput(
            tickets: [],
            revenues: [new RevenueLine(1, 'bar', 10000, 10000, true)],
            costs: [new CostLine(1, 'Cancelado', 'outros', 90000, 90000, 90000, true, true)],
            fees: [],
            distributions: [],
        ));

        $this->assertSame(0, $statement->expectedGross);
        $this->assertSame(0, $statement->committedCosts);
    }
}

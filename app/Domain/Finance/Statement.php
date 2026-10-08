<?php

namespace App\Domain\Finance;

final readonly class Statement
{
    public function __construct(
        public int $expectedTicketRevenue,
        public int $actualTicketRevenue,
        public int $expectedOtherRevenue,
        public int $actualOtherRevenue,
        public int $expectedGross,
        public int $actualGross,
        public int $expectedFees,
        public int $actualFees,
        public int $expectedDistributions,
        public int $actualDistributions,
        public int $expectedNet,
        public int $actualNet,
        public int $estimatedCosts,
        public int $contractedCosts,
        public int $committedCosts,
        public int $paidCosts,
        public int $remainingCosts,
        public int $projectedProfit,
        public int $currentResult,
        public int $ticketsSold,
        public int $ticketsGoal,
        public int $ticketsCapacity,
        public ?int $averageTicket,
        public ?int $breakEvenPeople,
        public ?int $breakEvenRevenue,
        public ?int $breakEvenAverageTicket,
        public int $spendingCeiling,
        public int $feeCeiling,
        public int $confirmedGuestHeads,
        public string $currency,
    ) {}

    public function additionalTicketsToBreakEven(): ?int
    {
        if ($this->breakEvenPeople === null) {
            return null;
        }

        return max(0, $this->breakEvenPeople - $this->ticketsSold);
    }
}

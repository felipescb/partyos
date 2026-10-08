<?php

namespace App\Domain\Finance;

final readonly class FinanceInput
{
    /**
     * @param  list<TicketLine>  $tickets
     * @param  list<RevenueLine>  $revenues
     * @param  list<CostLine>  $costs
     * @param  list<Adjustment>  $fees
     * @param  list<Adjustment>  $distributions
     */
    public function __construct(
        public array $tickets,
        public array $revenues,
        public array $costs,
        public array $fees,
        public array $distributions,
        public int $confirmedGuestHeads = 0,
        public int $capacity = 0,
        public string $currency = 'BRL',
    ) {}
}

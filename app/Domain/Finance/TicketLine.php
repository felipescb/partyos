<?php

namespace App\Domain\Finance;

final readonly class TicketLine
{
    public function __construct(
        public int $id,
        public int $price,
        public int $quantity,
        public int $goal,
        public int $sold,
        public ?int $projectedSold = null,
    ) {}
}

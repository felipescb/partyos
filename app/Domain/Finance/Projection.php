<?php

namespace App\Domain\Finance;

final readonly class Projection
{
    public function __construct(
        public int $attendance,
        public int $gross,
        public int $fees,
        public int $distributions,
        public int $net,
        public int $costs,
        public int $result,
        public ?int $averageTicket,
    ) {}
}

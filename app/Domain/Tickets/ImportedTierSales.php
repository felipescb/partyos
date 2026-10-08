<?php

namespace App\Domain\Tickets;

use Illuminate\Support\Carbon;

readonly class ImportedTierSales
{
    public function __construct(
        public string $name,
        public int $soldCount,
        public int $priceCents,
        public ?Carbon $startsAt = null,
        public ?Carbon $endsAt = null,
    ) {}
}

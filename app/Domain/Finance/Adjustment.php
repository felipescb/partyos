<?php

namespace App\Domain\Finance;

final readonly class Adjustment
{
    public function __construct(
        public string $kind,
        public int $basisPoints,
        public int $amount,
        public string $appliesTo,
        public ?string $category,
    ) {}
}

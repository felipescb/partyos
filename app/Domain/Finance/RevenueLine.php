<?php

namespace App\Domain\Finance;

final readonly class RevenueLine
{
    public function __construct(
        public int $id,
        public string $category,
        public int $expected,
        public int $actual,
        public bool $cancelled,
        public ?int $projectedAmount = null,
    ) {}
}

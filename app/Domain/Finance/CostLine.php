<?php

namespace App\Domain\Finance;

final readonly class CostLine
{
    public function __construct(
        public int $id,
        public string $label,
        public string $categorySlug,
        public int $estimated,
        public ?int $contracted,
        public int $paid,
        public bool $cancelled,
        public bool $hasVendor,
        public ?string $dueOn = null,
        public ?int $projectedAmount = null,
    ) {}

    public function committed(): int
    {
        if ($this->cancelled) {
            return 0;
        }

        return $this->projectedAmount ?? $this->contracted ?? $this->estimated;
    }

    public function isArtistFee(): bool
    {
        return in_array($this->categorySlug, ['artistas', 'caches'], true);
    }
}

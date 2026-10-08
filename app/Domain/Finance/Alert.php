<?php

namespace App\Domain\Finance;

final readonly class Alert
{
    public function __construct(
        public string $tone,
        public string $message,
        public ?string $route = null,
    ) {}
}

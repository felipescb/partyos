<?php

namespace App\Support;

readonly class PlanningTimelineMilestone
{
    public function __construct(
        public string $title,
        public string $kind,
        public ?string $slug = null,
        public ?string $statusLabel = null,
        public bool $isDone = false,
    ) {}
}

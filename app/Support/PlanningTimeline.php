<?php

namespace App\Support;

use Carbon\CarbonInterface;

readonly class PlanningTimeline
{
    /**
     * @param  list<PlanningTimelineWeek>  $weeks
     */
    public function __construct(
        public bool $ready,
        public array $weeks,
        public ?CarbonInterface $planningStartsOn = null,
        public ?CarbonInterface $eventOn = null,
        public ?float $progressRatio = null,
    ) {}

    public static function empty(): self
    {
        return new self(ready: false, weeks: []);
    }
}

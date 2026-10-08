<?php

namespace App\Support;

use Carbon\CarbonInterface;

readonly class PlanningTimelineWeek
{
    /**
     * @param  list<PlanningTimelineMilestone>  $milestones
     */
    public function __construct(
        public int $index,
        public CarbonInterface $startsOn,
        public CarbonInterface $endsOn,
        public bool $isEventWeek,
        public bool $isCurrent,
        public bool $isPast,
        public array $milestones,
    ) {}

    public function shortLabel(): string
    {
        if ($this->startsOn->month === $this->endsOn->month) {
            return $this->startsOn->translatedFormat('d').'–'.$this->endsOn->translatedFormat('d M');
        }

        return $this->startsOn->translatedFormat('d M').' – '.$this->endsOn->translatedFormat('d M');
    }
}

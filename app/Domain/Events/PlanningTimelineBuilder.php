<?php

namespace App\Domain\Events;

use App\Enums\TaskStatus;
use App\Models\Event;
use App\Support\PlanningTimeline;
use App\Support\PlanningTimelineMilestone;
use App\Support\PlanningTimelineWeek;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final class PlanningTimelineBuilder
{
    public function build(Event $event): PlanningTimeline
    {
        $eventOn = $event->starts_at?->copy()->startOfDay();
        $planningOn = $event->planning_starts_at?->copy()->startOfDay();

        if ($eventOn === null && $planningOn === null) {
            return PlanningTimeline::empty();
        }

        $defaultWeeks = (int) config('event_timeline.default_planning_weeks', 12);

        if ($planningOn === null && $eventOn !== null) {
            $planningOn = $eventOn->copy()->subWeeks($defaultWeeks)->startOfDay();
        }

        if ($eventOn === null && $planningOn !== null) {
            $eventOn = $planningOn->copy()->addWeeks($defaultWeeks);
        }

        $planningWeekStart = Carbon::parse($planningOn)->startOfWeek(Carbon::MONDAY);
        $eventWeekStart = Carbon::parse($eventOn)->startOfWeek(Carbon::MONDAY);

        if ($planningWeekStart->greaterThan($eventWeekStart)) {
            $planningWeekStart = $eventWeekStart->copy();
        }

        $today = now()->startOfDay();
        $weeks = [];
        $cursor = $planningWeekStart->copy();
        $index = 0;

        while ($cursor->lte($eventWeekStart)) {
            $endsOn = $cursor->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay();
            $isEventWeek = $cursor->equalTo($eventWeekStart);
            $isCurrent = $today->between($cursor, $endsOn);
            $isPast = $endsOn->lt($today) && ! $isCurrent;

            $weeks[] = new PlanningTimelineWeek(
                index: $index,
                startsOn: $cursor->copy(),
                endsOn: $endsOn,
                isEventWeek: $isEventWeek,
                isCurrent: $isCurrent,
                isPast: $isPast,
                milestones: [],
            );

            $cursor = $cursor->copy()->addWeek();
            $index++;
        }

        if ($weeks === []) {
            return PlanningTimeline::empty();
        }

        $weeks = $this->attachAutoMilestones($weeks, $eventWeekStart);
        $weeks = $this->attachTaskMilestones($weeks, $event);

        $totalDays = max(1, $planningOn->diffInDays($eventOn));
        $elapsedDays = max(0, min($totalDays, $planningOn->diffInDays($today)));
        $progressRatio = min(1, max(0, $elapsedDays / $totalDays));

        return new PlanningTimeline(
            ready: true,
            weeks: $weeks,
            planningStartsOn: $planningOn,
            eventOn: $eventOn,
            progressRatio: $progressRatio,
        );
    }

    /**
     * @param  list<PlanningTimelineWeek>  $weeks
     * @return list<PlanningTimelineWeek>
     */
    private function attachAutoMilestones(array $weeks, CarbonInterface $eventWeekStart): array
    {
        /** @var list<array{weeks_before_event: int, title: string, slug: string}> $definitions */
        $definitions = config('event_timeline.milestones', []);

        foreach ($definitions as $definition) {
            $targetStart = $eventWeekStart->copy()->subWeeks((int) $definition['weeks_before_event']);
            $weekIndex = $this->weekIndexFor($weeks, $targetStart);

            if ($weekIndex === null) {
                continue;
            }

            $weeks[$weekIndex] = $this->pushMilestone($weeks[$weekIndex], new PlanningTimelineMilestone(
                title: $definition['title'],
                kind: 'auto',
                slug: $definition['slug'],
            ));
        }

        return $weeks;
    }

    /**
     * @param  list<PlanningTimelineWeek>  $weeks
     * @return list<PlanningTimelineWeek>
     */
    private function attachTaskMilestones(array $weeks, Event $event): array
    {
        $tasks = $event->tasks()
            ->whereNotNull('due_on')
            ->orderBy('due_on')
            ->get();

        foreach ($tasks as $task) {
            $due = $task->due_on?->copy()->startOfDay();

            if ($due === null) {
                continue;
            }

            $weekIndex = $this->weekIndexContaining($weeks, $due);

            if ($weekIndex === null) {
                continue;
            }

            $weeks[$weekIndex] = $this->pushMilestone($weeks[$weekIndex], new PlanningTimelineMilestone(
                title: $task->title,
                kind: 'task',
                statusLabel: $task->status->label(),
                isDone: $task->status === TaskStatus::Done,
            ));
        }

        return $weeks;
    }

    /**
     * @param  list<PlanningTimelineWeek>  $weeks
     */
    private function weekIndexFor(array $weeks, CarbonInterface $weekStart): ?int
    {
        $target = $weekStart->copy()->startOfWeek(Carbon::MONDAY);

        foreach ($weeks as $index => $week) {
            if ($week->startsOn->equalTo($target)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param  list<PlanningTimelineWeek>  $weeks
     */
    private function weekIndexContaining(array $weeks, CarbonInterface $date): ?int
    {
        foreach ($weeks as $index => $week) {
            if ($date->between($week->startsOn, $week->endsOn)) {
                return $index;
            }
        }

        return null;
    }

    private function pushMilestone(PlanningTimelineWeek $week, PlanningTimelineMilestone $milestone): PlanningTimelineWeek
    {
        $milestones = $week->milestones;
        $milestones[] = $milestone;

        return new PlanningTimelineWeek(
            index: $week->index,
            startsOn: $week->startsOn,
            endsOn: $week->endsOn,
            isEventWeek: $week->isEventWeek,
            isCurrent: $week->isCurrent,
            isPast: $week->isPast,
            milestones: $milestones,
        );
    }
}

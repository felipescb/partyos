<?php

namespace Tests\Unit;

use App\Domain\Events\CreateEvent;
use App\Domain\Events\PlanningTimelineBuilder;
use App\Enums\EventType;
use App\Enums\TaskStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PlanningTimelineBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_builds_weeks_with_auto_milestones_and_tasks(): void
    {
        Carbon::setTestNow('2026-09-01 12:00:00');

        $user = User::factory()->create();
        $event = app(CreateEvent::class)->handle($user, [
            'name' => 'Timeline festa',
            'type' => EventType::Party,
            'planning_starts_at' => '2026-06-01',
            'starts_at' => '2026-11-28 22:00:00',
        ]);

        $event->tasks()->create([
            'title' => 'Fechar venue',
            'status' => TaskStatus::Todo,
            'priority' => 'medium',
            'due_on' => '2026-08-15',
        ]);

        $timeline = app(PlanningTimelineBuilder::class)->build($event->fresh());

        $this->assertTrue($timeline->ready);
        $this->assertGreaterThan(4, count($timeline->weeks));

        $eventWeek = collect($timeline->weeks)->first(fn ($week) => $week->isEventWeek);
        $this->assertNotNull($eventWeek);
        $this->assertTrue(
            collect($eventWeek->milestones)->contains(fn ($m) => $m->slug === 'event_week')
        );

        $withTask = collect($timeline->weeks)->first(
            fn ($week) => collect($week->milestones)->contains(fn ($m) => $m->title === 'Fechar venue')
        );
        $this->assertNotNull($withTask);

        Carbon::setTestNow();
    }
}

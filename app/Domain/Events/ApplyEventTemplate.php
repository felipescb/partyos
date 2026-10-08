<?php

namespace App\Domain\Events;

use App\Enums\TaskStatus;
use App\Models\Event;
use App\Models\EventTemplate;

final class ApplyEventTemplate
{
    public function apply(Event $event, EventTemplate $template): void
    {
        $payload = $template->payload;
        $start = $event->starts_at;

        foreach ($payload['tasks'] ?? [] as $task) {
            $event->tasks()->create([
                'title' => $task['title'],
                'category' => $task['category'] ?? 'producao',
                'priority' => $task['priority'] ?? 'medium',
                'status' => TaskStatus::Backlog,
            ]);
        }

        foreach ($payload['schedule'] ?? [] as $index => $item) {
            $startsAt = null;

            if ($start !== null && isset($item['offset_minutes'])) {
                $startsAt = $start->addMinutes((int) $item['offset_minutes']);
            }

            $event->scheduleItems()->create([
                'title' => $item['title'],
                'starts_at' => $startsAt,
                'duration_minutes' => $item['duration_minutes'] ?? null,
                'sort_order' => $index,
            ]);
        }
    }
}

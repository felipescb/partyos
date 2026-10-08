<?php

namespace App\Domain\Events;

use App\Enums\EventRole;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CreateEvent
{
    public function __construct(private ApplyEventTemplate $templates) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(User $user, array $attributes): Event
    {
        return DB::transaction(function () use ($user, $attributes): Event {
            $organizationId = (int) $user->current_organization_id;

            $event = Event::query()->create([
                'organization_id' => $organizationId,
                'name' => $attributes['name'],
                'slug' => $this->slug($organizationId, $attributes['name']),
                'description' => $attributes['description'] ?? null,
                'type' => $attributes['type'],
                'status' => $attributes['status'] ?? EventStatus::Draft,
                'starts_at' => $attributes['starts_at'] ?? null,
                'ends_at' => $attributes['ends_at'] ?? null,
                'venue_name' => $attributes['venue_name'] ?? null,
                'address' => $attributes['address'] ?? null,
                'city' => $attributes['city'] ?? null,
                'capacity' => $attributes['capacity'] ?? null,
                'cover_path' => $attributes['cover_path'] ?? null,
                'currency' => $attributes['currency'] ?? 'BRL',
                'notes' => $attributes['notes'] ?? null,
                'created_by' => $user->id,
                'template_id' => $attributes['template_id'] ?? null,
            ]);

            $event->budget()->create(['name' => 'Orçamento']);
            $event->members()->attach($user->id, ['role' => EventRole::Owner->value]);

            if (! empty($attributes['template_id'])) {
                $template = EventTemplate::query()->find($attributes['template_id']);

                if ($template instanceof EventTemplate) {
                    $this->templates->apply($event, $template);
                }
            }

            return $event;
        });
    }

    public function slug(int $organizationId, string $name): string
    {
        $base = Str::slug($name);
        $base = $base !== '' ? $base : 'evento';
        $slug = $base;
        $suffix = 2;

        while (Event::withTrashed()->where('organization_id', $organizationId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}

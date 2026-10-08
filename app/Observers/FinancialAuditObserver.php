<?php

namespace App\Observers;

use App\Models\Audit;
use App\Models\Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class FinancialAuditObserver
{
    public function created(Model $model): void
    {
        $this->write($model, 'created', null, $this->values($model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        $changes = Arr::except($model->getChanges(), ['updated_at']);

        if ($changes === []) {
            return;
        }

        $old = [];

        foreach (array_keys($changes) as $key) {
            $old[$key] = $model->getOriginal($key);
        }

        $this->write($model, 'updated', $this->values($old), $this->values($changes));
    }

    public function deleted(Model $model): void
    {
        $this->write($model, 'deleted', $this->values($model->getAttributes()), null);
    }

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    private function write(Model $model, string $action, ?array $old, ?array $new): void
    {
        $eventId = $model->getAttribute('event_id');

        Audit::query()->create([
            'user_id' => auth()->id(),
            'organization_id' => $this->organizationId($model),
            'event_id' => is_numeric($eventId) ? (int) $eventId : null,
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'action' => $action,
            'old_values' => $old,
            'new_values' => $new,
        ]);
    }

    private function organizationId(Model $model): ?int
    {
        $organizationId = $model->getAttribute('organization_id');

        if (is_numeric($organizationId)) {
            return (int) $organizationId;
        }

        $eventId = $model->getAttribute('event_id');

        if (is_numeric($eventId)) {
            $value = Event::query()->whereKey($eventId)->value('organization_id');

            return is_numeric($value) ? (int) $value : null;
        }

        $current = auth()->user()?->current_organization_id;

        return is_numeric($current) ? (int) $current : null;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function values(array $values): array
    {
        unset($values['updated_at'], $values['created_at']);

        foreach ($values as $key => $value) {
            if ($value instanceof \BackedEnum) {
                $values[$key] = $value->value;
            } elseif ($value instanceof \DateTimeInterface) {
                $values[$key] = $value->format('Y-m-d H:i:s');
            }
        }

        return $values;
    }
}

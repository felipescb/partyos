<?php

namespace App\Models;

use App\Enums\ScenarioKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $event_id
 * @property string $name
 * @property ScenarioKind $kind
 * @property int|null $attendance
 * @property string|null $notes
 */
#[Fillable(['event_id', 'name', 'kind', 'attendance', 'notes'])]
class FinancialScenario extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => ScenarioKind::class,
            'attendance' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return HasMany<ScenarioLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(ScenarioLine::class);
    }
}

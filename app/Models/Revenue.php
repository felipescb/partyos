<?php

namespace App\Models;

use App\Enums\RevenueCategory;
use App\Enums\RevenueStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $event_id
 * @property RevenueCategory $category
 * @property string $description
 * @property int $expected_amount
 * @property int $actual_amount
 * @property RevenueStatus $status
 * @property Carbon|null $occurred_on
 * @property string|null $source
 * @property string|null $notes
 */
#[Fillable([
    'event_id', 'category', 'description', 'expected_amount', 'actual_amount', 'status', 'occurred_on', 'source', 'notes',
])]
class Revenue extends Model
{
    use SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => RevenueCategory::class,
            'expected_amount' => 'integer',
            'actual_amount' => 'integer',
            'status' => RevenueStatus::class,
            'occurred_on' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}

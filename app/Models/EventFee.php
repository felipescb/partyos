<?php

namespace App\Models;

use App\Enums\AdjustmentKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $event_id
 * @property string $name
 * @property string $applies_to
 * @property string|null $revenue_category
 * @property AdjustmentKind $kind
 * @property int|null $basis_points
 * @property int|null $amount
 * @property int $sort_order
 */
#[Fillable(['event_id', 'name', 'applies_to', 'revenue_category', 'kind', 'basis_points', 'amount', 'sort_order'])]
class EventFee extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => AdjustmentKind::class,
            'basis_points' => 'integer',
            'amount' => 'integer',
            'sort_order' => 'integer',
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

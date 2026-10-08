<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $event_id
 * @property string $name
 * @property int $price
 * @property int $quantity
 * @property int $goal
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property int $sold_quantity
 * @property int $sort_order
 */
#[Fillable([
    'event_id', 'name', 'price', 'quantity', 'goal', 'starts_at', 'ends_at', 'sold_quantity', 'sort_order',
])]
class TicketTier extends Model
{
    use SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'quantity' => 'integer',
            'goal' => 'integer',
            'sold_quantity' => 'integer',
            'sort_order' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function remaining(): int
    {
        return $this->quantity - $this->sold_quantity;
    }

    public function revenue(): int
    {
        return $this->price * $this->sold_quantity;
    }
}

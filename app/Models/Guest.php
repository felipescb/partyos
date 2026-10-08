<?php

namespace App\Models;

use App\Enums\GuestCategory;
use App\Enums\RsvpStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $event_id
 * @property string $name
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $instagram
 * @property GuestCategory $category
 * @property RsvpStatus $rsvp_status
 * @property int $plus_ones
 * @property Carbon|null $checked_in_at
 * @property string|null $notes
 */
#[Fillable([
    'event_id', 'name', 'phone', 'email', 'instagram', 'category', 'rsvp_status', 'plus_ones', 'checked_in_at', 'notes',
])]
class Guest extends Model
{
    use SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => GuestCategory::class,
            'rsvp_status' => RsvpStatus::class,
            'plus_ones' => 'integer',
            'checked_in_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function headcount(): int
    {
        return 1 + $this->plus_ones;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $stage_name
 * @property string|null $legal_name
 * @property string|null $instagram
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $agency
 * @property int|null $default_fee
 * @property string|null $pix
 * @property string|null $tech_rider
 * @property string|null $hospitality_rider
 * @property string|null $notes
 */
#[Fillable([
    'organization_id', 'stage_name', 'legal_name', 'instagram', 'phone', 'email', 'agency',
    'default_fee', 'pix', 'tech_rider', 'hospitality_rider', 'notes',
])]
class Artist extends Model
{
    use SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_fee' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}

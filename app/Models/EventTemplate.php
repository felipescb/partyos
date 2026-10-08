<?php

namespace App\Models;

use App\Enums\EventType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $organization_id
 * @property string $name
 * @property string $slug
 * @property EventType $type
 * @property string|null $description
 * @property array<string, mixed> $payload
 * @property bool $is_official
 */
#[Fillable(['organization_id', 'name', 'slug', 'type', 'description', 'payload', 'is_official'])]
class EventTemplate extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => EventType::class,
            'payload' => 'array',
            'is_official' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}

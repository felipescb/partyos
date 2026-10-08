<?php

namespace App\Models;

use App\Enums\EventRole;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\OrgRole;
use App\Enums\RsvpStatus;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property EventType $type
 * @property EventStatus $status
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property string|null $venue_name
 * @property string|null $address
 * @property string|null $city
 * @property int|null $capacity
 * @property string|null $cover_path
 * @property string $currency
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $template_id
 */
#[Fillable([
    'organization_id',
    'name',
    'slug',
    'description',
    'type',
    'status',
    'starts_at',
    'ends_at',
    'venue_name',
    'address',
    'city',
    'capacity',
    'cover_path',
    'currency',
    'notes',
    'created_by',
    'template_id',
])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory, SoftDeletes;

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @param  mixed  $value
     * @param  string|null  $field
     */
    public function resolveRouteBinding($value, $field = null): ?self
    {
        $user = auth()->user();

        if (! $user instanceof User || $user->current_organization_id === null) {
            return null;
        }

        $event = $this->where('organization_id', $user->current_organization_id)
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->first();

        if (! $event instanceof self || $user->cannot('view', $event)) {
            return null;
        }

        return $event;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => EventType::class,
            'status' => EventStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'capacity' => 'integer',
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
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_members')->withPivot('role')->withTimestamps();
    }

    /**
     * @return HasOne<Budget, $this>
     */
    public function budget(): HasOne
    {
        return $this->hasOne(Budget::class);
    }

    /**
     * @return HasMany<BudgetItem, $this>
     */
    public function budgetItems(): HasMany
    {
        return $this->hasMany(BudgetItem::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<Revenue, $this>
     */
    public function revenues(): HasMany
    {
        return $this->hasMany(Revenue::class);
    }

    /**
     * @return HasMany<TicketTier, $this>
     */
    public function ticketTiers(): HasMany
    {
        return $this->hasMany(TicketTier::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<FinancialScenario, $this>
     */
    public function scenarios(): HasMany
    {
        return $this->hasMany(FinancialScenario::class);
    }

    /**
     * @return HasMany<EventFee, $this>
     */
    public function fees(): HasMany
    {
        return $this->hasMany(EventFee::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<RevenueDistribution, $this>
     */
    public function distributions(): HasMany
    {
        return $this->hasMany(RevenueDistribution::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<Guest, $this>
     */
    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * @return HasMany<ScheduleItem, $this>
     */
    public function scheduleItems(): HasMany
    {
        return $this->hasMany(ScheduleItem::class)->orderBy('starts_at')->orderBy('sort_order');
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class)->orderBy('starts_at');
    }

    /**
     * @return HasMany<Audit, $this>
     */
    public function audits(): HasMany
    {
        return $this->hasMany(Audit::class)->latest();
    }

    public function confirmedGuestHeads(): int
    {
        return (int) $this->guests
            ->filter(fn (Guest $guest): bool => in_array($guest->rsvp_status, [RsvpStatus::Confirmed, RsvpStatus::CheckedIn], true))
            ->sum(fn (Guest $guest): int => 1 + $guest->plus_ones);
    }

    public function roleFor(User $user): ?EventRole
    {
        $organizationRole = $this->organization?->roleFor($user);

        if (in_array($organizationRole, [OrgRole::Owner, OrgRole::Admin], true)) {
            return EventRole::Owner;
        }

        $membership = $this->members()->whereKey($user->id)->first();
        $pivot = $membership?->pivot;
        $role = $pivot instanceof Pivot ? $pivot->getAttribute('role') : null;

        return is_string($role) ? EventRole::tryFrom($role) : null;
    }

    public function whenLabel(): string
    {
        if ($this->starts_at === null) {
            return 'Data a definir';
        }

        $label = $this->starts_at->translatedFormat('d M Y, H:i');

        if ($this->ends_at !== null) {
            $label .= ' – '.$this->ends_at->translatedFormat('H:i');
        }

        return $label;
    }
}

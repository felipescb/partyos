<?php

namespace App\Models;

use App\Enums\CostStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $budget_id
 * @property int $event_id
 * @property int|null $cost_category_id
 * @property int|null $vendor_id
 * @property string $description
 * @property int $estimated_amount
 * @property int|null $contracted_amount
 * @property Carbon|null $due_on
 * @property CostStatus $status
 * @property string|null $payment_method
 * @property int|null $assignee_id
 * @property string|null $notes
 */
#[Fillable([
    'budget_id',
    'event_id',
    'cost_category_id',
    'vendor_id',
    'description',
    'estimated_amount',
    'contracted_amount',
    'due_on',
    'status',
    'payment_method',
    'assignee_id',
    'notes',
])]
class BudgetItem extends Model
{
    use SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estimated_amount' => 'integer',
            'contracted_amount' => 'integer',
            'due_on' => 'date',
            'status' => CostStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Budget, $this>
     */
    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<CostCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CostCategory::class, 'cost_category_id');
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasOne<Booking, $this>
     */
    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class);
    }

    public function paidAmount(): int
    {
        return (int) $this->payments()->where('status', PaymentStatus::Paid)->sum('amount');
    }

    public function committedAmount(): int
    {
        if ($this->status === CostStatus::Cancelled) {
            return 0;
        }

        return $this->contracted_amount ?? $this->estimated_amount;
    }

    public function refreshPaymentStatus(): void
    {
        if ($this->status === CostStatus::Cancelled) {
            return;
        }

        $paid = $this->paidAmount();
        $committed = $this->committedAmount();

        if ($committed > 0 && $paid >= $committed) {
            $this->status = CostStatus::Paid;
        } elseif ($paid > 0) {
            $this->status = CostStatus::PartiallyPaid;
        } elseif (in_array($this->status, [CostStatus::Paid, CostStatus::PartiallyPaid], true)) {
            $this->status = $this->contracted_amount !== null ? CostStatus::Contracted : CostStatus::Planned;
        }

        $this->save();
    }
}

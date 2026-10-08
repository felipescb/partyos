<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $event_id
 * @property int $budget_item_id
 * @property int $amount
 * @property Carbon|null $due_on
 * @property Carbon|null $paid_on
 * @property string|null $method
 * @property PaymentStatus $status
 * @property string|null $notes
 * @property int|null $recorded_by
 */
#[Fillable([
    'event_id', 'budget_item_id', 'amount', 'due_on', 'paid_on', 'method', 'status', 'notes', 'recorded_by',
])]
class Payment extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'due_on' => 'date',
            'paid_on' => 'date',
            'status' => PaymentStatus::class,
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
     * @return BelongsTo<BudgetItem, $this>
     */
    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class);
    }
}

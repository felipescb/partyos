<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $financial_scenario_id
 * @property string $line_type
 * @property int|null $reference_id
 * @property string $label
 * @property int|null $quantity
 * @property int|null $amount
 */
#[Fillable(['financial_scenario_id', 'line_type', 'reference_id', 'label', 'quantity', 'amount'])]
class ScenarioLine extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'amount' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<FinancialScenario, $this>
     */
    public function scenario(): BelongsTo
    {
        return $this->belongsTo(FinancialScenario::class, 'financial_scenario_id');
    }
}

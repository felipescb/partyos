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
 * @property string $name
 * @property string|null $company
 * @property string|null $category
 * @property string|null $phone
 * @property string|null $whatsapp
 * @property string|null $email
 * @property string|null $instagram
 * @property string|null $tax_id
 * @property string|null $pix
 * @property string|null $address
 * @property string|null $notes
 */
#[Fillable([
    'organization_id', 'name', 'company', 'category', 'phone', 'whatsapp', 'email',
    'instagram', 'tax_id', 'pix', 'address', 'notes',
])]
class Vendor extends Model
{
    use SoftDeletes;

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return HasMany<BudgetItem, $this>
     */
    public function budgetItems(): HasMany
    {
        return $this->hasMany(BudgetItem::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetItem extends Model
{
    protected $fillable = ['budget_category_id', 'code', 'description', 'amount', 'transfer', 'sort'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(BudgetCategory::class, 'budget_category_id');
    }

    public function adjustmentItems(): HasMany
    {
        return $this->hasMany(BudgetAdjustmentItem::class);
    }

    public function getAdjustmentAttribute(): float
    {
        return $this->relationLoaded('adjustmentItems')
            ? (float) $this->adjustmentItems->sum('amount')
            : (float) $this->adjustmentItems()->sum('amount');
    }

    public function getActualBudgetAttribute(): float
    {
        return $this->amount + $this->adjustment + (float) $this->transfer;
    }
}

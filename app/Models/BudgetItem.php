<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetItem extends Model
{
    protected $fillable = ['budget_category_id', 'code', 'description', 'amount', 'sort', 'vtp_auto'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(BudgetCategory::class, 'budget_category_id');
    }

    public function adjustmentItems(): HasMany
    {
        return $this->hasMany(BudgetAdjustmentItem::class);
    }

    public function transfersIn(): HasMany
    {
        return $this->hasMany(BudgetTransfer::class, 'to_budget_item_id');
    }

    public function transfersOut(): HasMany
    {
        return $this->hasMany(BudgetTransfer::class, 'from_budget_item_id');
    }

    public function getAdjustmentAttribute(): float
    {
        return $this->relationLoaded('adjustmentItems')
            ? (float) $this->adjustmentItems->sum('amount')
            : (float) $this->adjustmentItems()->sum('amount');
    }

    public function getTransferAttribute(): float
    {
        $in  = $this->relationLoaded('transfersIn')
            ? (float) $this->transfersIn->sum('amount')
            : (float) $this->transfersIn()->sum('amount');
        $out = $this->relationLoaded('transfersOut')
            ? (float) $this->transfersOut->sum('amount')
            : (float) $this->transfersOut()->sum('amount');
        return $in - $out;
    }

    public function getActualBudgetAttribute(): float
    {
        return $this->amount + $this->adjustment + $this->transfer;
    }

    public function vtpEntries(): HasMany
    {
        return $this->hasMany(BudgetItemVtp::class);
    }

    public function anticipatedEntries(): HasMany
    {
        return $this->hasMany(BudgetAnticipatedEntry::class);
    }
}

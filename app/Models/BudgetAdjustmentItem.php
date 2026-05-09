<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetAdjustmentItem extends Model
{
    protected $fillable = ['budget_adjustment_id', 'budget_item_id', 'amount'];

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(BudgetAdjustment::class, 'budget_adjustment_id');
    }

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class, 'budget_item_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetItemVtp extends Model
{
    protected $fillable = ['budget_item_id', 'date', 'description', 'amount'];

    protected $casts = ['date' => 'date'];

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class);
    }
}

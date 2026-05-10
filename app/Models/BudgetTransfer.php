<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetTransfer extends Model
{
    protected $fillable = ['budget_id', 'date', 'description', 'from_budget_item_id', 'to_budget_item_id', 'amount'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function fromItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class, 'from_budget_item_id');
    }

    public function toItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class, 'to_budget_item_id');
    }
}

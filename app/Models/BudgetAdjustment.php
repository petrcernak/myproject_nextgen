<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetAdjustment extends Model
{
    protected $fillable = ['budget_id', 'date', 'description'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BudgetAdjustmentItem::class);
    }

    public function getTotalAttribute(): float
    {
        return (float) $this->items->sum('amount');
    }
}

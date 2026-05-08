<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetCategory extends Model
{
    protected $fillable = ['budget_id', 'name', 'code', 'sort'];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BudgetItem::class)->orderBy('sort');
    }

    public function getTotalAttribute(): float
    {
        return $this->items()->sum('amount');
    }
}

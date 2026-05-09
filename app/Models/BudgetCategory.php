<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetCategory extends Model
{
    protected $fillable = ['budget_id', 'parent_id', 'name', 'code', 'sort'];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(BudgetCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(BudgetCategory::class, 'parent_id')->orderBy('sort');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BudgetItem::class)->orderBy('sort');
    }

    public function getTotalAttribute(): float
    {
        return (float) $this->items()->sum('amount')
            + ($this->relationLoaded('children') ? $this->children->sum('total') : 0.0);
    }
}

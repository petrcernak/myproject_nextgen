<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractCategory extends Model
{
    protected $fillable = ['contract_id', 'parent_id', 'name', 'code', 'sort'];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ContractCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ContractCategory::class, 'parent_id')->orderBy('sort');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ContractItem::class)->orderBy('sort');
    }

    public function getTotalAttribute(): float
    {
        return (float) $this->items()->sum('amount')
            + ($this->relationLoaded('children') ? $this->children->sum('total') : 0.0);
    }
}

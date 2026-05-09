<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Budget extends Model
{
    protected $fillable = ['project_id', 'code', 'name', 'date', 'currency', 'note'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(BudgetCategory::class)->orderBy('sort');
    }

    public function items(): HasManyThrough
    {
        return $this->hasManyThrough(BudgetItem::class, BudgetCategory::class);
    }

    public function getTotalAttribute(): float
    {
        return $this->items()->sum('amount');
    }
}

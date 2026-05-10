<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Budget extends Model
{
    use LogsActivity;

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

    public function adjustments(): HasMany
    {
        return $this->hasMany(BudgetAdjustment::class)->orderByDesc('date');
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(BudgetTransfer::class)->orderByDesc('date');
    }

    public function getTotalAttribute(): float
    {
        return $this->items()->sum('amount');
    }
}

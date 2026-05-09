<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChangeRequest extends Model
{
    protected $fillable = ['contract_id', 'code', 'name', 'date', 'note'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ChangeRequestItem::class)->orderBy('sort');
    }

    public function getTotalSupplierAttribute(): float
    {
        return (float) $this->items->sum(fn ($item) => $item->latestRevision?->amount_supplier ?? 0);
    }

    public function getTotalPmAttribute(): float
    {
        return (float) $this->items->sum(fn ($item) => $item->latestRevision?->amount_pm ?? 0);
    }

    public function getTotalReportAttribute(): float
    {
        return (float) $this->items->sum(fn ($item) => $item->latestRevision?->amount_report ?? 0);
    }
}

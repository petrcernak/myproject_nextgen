<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChangeOrder extends Model
{
    protected $fillable = ['contract_id', 'amendment_id', 'code', 'name', 'date', 'note'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function amendment(): BelongsTo
    {
        return $this->belongsTo(Amendment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ChangeOrderItem::class)->orderBy('sort');
    }

    public function getTotalAttribute(): float
    {
        return (float) $this->items()->sum('amount');
    }
}

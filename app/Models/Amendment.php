<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Amendment extends Model
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
        return $this->hasMany(AmendmentItem::class)->orderBy('sort');
    }

    public function changeOrders(): HasMany
    {
        return $this->hasMany(ChangeOrder::class)->orderBy('date')->orderBy('code');
    }

    public function getTotalAttribute(): float
    {
        $directItems = (float) $this->items()->sum('amount');
        $coItems     = (float) $this->changeOrders->sum('total');
        return $directItems + $coItems;
    }
}

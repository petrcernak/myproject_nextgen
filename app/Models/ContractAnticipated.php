<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractAnticipated extends Model
{
    use LogsActivity;

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
        return $this->hasMany(ContractAnticipatedItem::class)->orderBy('sort');
    }

    public function getTotalAttribute(): float
    {
        return (float) $this->items()->sum('amount');
    }

    public function getTotalDiffAttribute(): float
    {
        return $this->total - (float) $this->contract->total;
    }
}

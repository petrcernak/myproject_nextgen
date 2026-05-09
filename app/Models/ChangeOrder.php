<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\File;

class ChangeOrder extends Model
{
    use LogsActivity;

    protected $fillable = ['contract_id', 'amendment_id', 'change_request_id', 'code', 'name', 'date', 'note'];

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

    public function sourceChangeRequest(): BelongsTo
    {
        return $this->belongsTo(ChangeRequest::class, 'change_request_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ChangeOrderItem::class)->orderBy('sort');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable')->orderByDesc('created_at');
    }

    public function getTotalAttribute(): float
    {
        return (float) $this->items()->sum('amount');
    }
}

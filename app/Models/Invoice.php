<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    const STATUS_PENDING  = 1;
    const STATUS_PAID     = 2;
    const STATUS_DUE_SOON = 3;
    const STATUS_OVERDUE  = 4;

    protected $fillable = [
        'no', 'contract_id', 'sendby_id', 'sendto_id',
        'description', 'issued', 'taxdate', 'due', 'paid', 'status', 'note',
    ];

    protected function casts(): array
    {
        return [
            'issued'  => 'date',
            'taxdate' => 'date',
            'due'     => 'date',
            'paid'    => 'date',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'sendby_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'sendto_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort');
    }

    public function getTotalAttribute(): float
    {
        return $this->items()->sum('amount');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PAID     => __('Paid'),
            self::STATUS_DUE_SOON => __('Due soon'),
            self::STATUS_OVERDUE  => __('Overdue'),
            default               => __('Awaiting payment'),
        };
    }

    public function recalculateStatus(): void
    {
        if ($this->paid) {
            $this->status = self::STATUS_PAID;
        } elseif ($this->due && $this->due->isPast()) {
            $this->status = self::STATUS_OVERDUE;
        } elseif ($this->due && $this->due->diffInDays(now()) <= 7) {
            $this->status = self::STATUS_DUE_SOON;
        } else {
            $this->status = self::STATUS_PENDING;
        }
        $this->save();
    }
}

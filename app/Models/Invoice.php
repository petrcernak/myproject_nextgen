<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use App\Models\File;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Invoice extends Model
{
    use LogsActivity;

    const STATUS_PENDING  = 1;
    const STATUS_PAID     = 2;
    const STATUS_DUE_SOON = 3;
    const STATUS_OVERDUE  = 4;

    protected $fillable = [
        'no', 'contract_id', 'sendby_id', 'sendto_id',
        'description', 'issued', 'taxdate', 'due', 'paid', 'status', 'note',
        'is_advance', 'advance_amount',
    ];

    protected function casts(): array
    {
        return [
            'issued'         => 'date',
            'taxdate'        => 'date',
            'due'            => 'date',
            'paid'           => 'date',
            'is_advance'     => 'boolean',
            'advance_amount' => 'float',
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

    public function deductions(): HasMany
    {
        return $this->hasMany(InvoiceAdvanceDeduction::class);
    }

    public function advanceDeductionsReceived(): HasMany
    {
        return $this->hasMany(InvoiceAdvanceDeduction::class, 'advance_invoice_id');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable')->orderByDesc('created_at');
    }

    public function getItemsTotalAttribute(): float
    {
        if ($this->is_advance) return (float) ($this->advance_amount ?? 0);
        return (float) $this->items()->sum('amount');
    }

    public function getRetentionShortAmountAttribute(): float
    {
        if ($this->is_advance) return 0.0;
        $rate = $this->contract->retention_short ?? 0;
        if (!$rate) return 0.0;
        return round($this->items_total * $rate / 100, 2);
    }

    public function getRetentionLongAmountAttribute(): float
    {
        if ($this->is_advance) return 0.0;
        $rate = $this->contract->retention_long ?? 0;
        if (!$rate) return 0.0;
        return round($this->items_total * $rate / 100, 2);
    }

    public function getTotalAttribute(): float
    {
        if ($this->is_advance) {
            return (float) ($this->advance_amount ?? 0);
        }
        $itemsTotal = (float) $this->items()->sum('amount');
        $deducted   = (float) $this->deductions()->sum('amount');
        $retShort   = round($itemsTotal * ($this->contract->retention_short ?? 0) / 100, 2);
        $retLong    = round($itemsTotal * ($this->contract->retention_long ?? 0) / 100, 2);
        return $itemsTotal - $retShort - $retLong - $deducted;
    }

    public function getDeductedAmountAttribute(): float
    {
        return (float) $this->deductions()->sum('amount');
    }

    public function getRemainingAdvanceAttribute(): float
    {
        if (!$this->is_advance) return 0;
        $amortized = $this->advanceDeductionsReceived()->sum('amount');
        return (float) ($this->advance_amount ?? 0) - $amortized;
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

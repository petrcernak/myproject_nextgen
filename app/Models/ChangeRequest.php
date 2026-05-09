<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChangeRequest extends Model
{
    use LogsActivity;

    protected $fillable = ['contract_id', 'code', 'name', 'date', 'status', 'note'];

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

    public function convertedChangeOrder(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ChangeOrder::class, 'change_request_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'closed'    => __('Closed'),
            'rejected'  => __('Rejected'),
            'on_hold'   => __('On hold'),
            'converted' => __('Converted'),
            default     => __('Open'),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'closed'    => 'badge-green',
            'rejected'  => 'badge-red',
            'on_hold'   => 'badge-yellow',
            'converted' => 'badge-gray',
            default     => 'badge-gray',
        };
    }

    public function countsInReport(): bool
    {
        return in_array($this->status, ['open', 'closed']);
    }

    public function getTotalEffectiveReportAttribute(): float
    {
        return $this->countsInReport() ? $this->total_report : 0.0;
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

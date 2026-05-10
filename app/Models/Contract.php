<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\RetentionRelease;
use App\Models\RetentionBankGuarantee;

class Contract extends Model
{
    use LogsActivity;

    protected $fillable = [
        'code', 'name', 'project_id', 'company_id',
        'direction', 'currency', 'fx_rate', 'date', 'description', 'maturity',
        'retention_short', 'retention_long', 'note',
    ];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ContractItem::class)->orderBy('sort');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(ContractCategory::class)->orderBy('sort');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function amendments(): HasMany
    {
        return $this->hasMany(Amendment::class)->orderBy('date')->orderBy('code');
    }

    public function changeOrders(): HasMany
    {
        return $this->hasMany(ChangeOrder::class)->orderBy('date')->orderBy('code');
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(ChangeRequest::class)->orderBy('date')->orderBy('code');
    }

    public function anticipateds(): HasMany
    {
        return $this->hasMany(ContractAnticipated::class)->orderBy('date')->orderBy('code');
    }

    public function budgetLinks(): HasMany
    {
        return $this->hasMany(ContractBudgetLink::class);
    }

    public function isFullyBudgetLinked(): bool
    {
        $total = $this->items()->count();
        if ($total === 0) return false;
        return $this->items()->whereNotNull('budget_item_id')->count() >= $total;
    }

    public function standaloneChangeOrders(): HasMany
    {
        return $this->hasMany(ChangeOrder::class)->whereNull('amendment_id')->orderBy('date')->orderBy('code');
    }

    public function getTotalAttribute(): float
    {
        return (float) $this->items()->sum('amount');
    }

    public function getTotalCoChangesAttribute(): float
    {
        $coChanges = (float) ChangeOrderItem::whereHas(
            'changeOrder', fn ($q) => $q->where('contract_id', $this->id)
        )->sum('amount');

        $amendmentChanges = (float) AmendmentItem::whereHas(
            'amendment', fn ($q) => $q->where('contract_id', $this->id)
        )->sum('amount');

        return $coChanges + $amendmentChanges;
    }

    public function getRevisedTotalAttribute(): float
    {
        return $this->total + $this->total_co_changes;
    }

    public function getInvoicedAttribute(): float
    {
        return $this->invoices()->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->sum('invoice_items.amount');
    }

    public function retentionShort(float $amount): float
    {
        return round($amount * ($this->retention_short ?? 0) / 100, 2);
    }

    public function retentionLong(float $amount): float
    {
        return round($amount * ($this->retention_long ?? 0) / 100, 2);
    }

    public function retentionReleases(): HasMany
    {
        return $this->hasMany(RetentionRelease::class)->orderBy('release_date');
    }

    public function retentionBankGuarantees(): HasMany
    {
        return $this->hasMany(RetentionBankGuarantee::class)->orderBy('valid_from');
    }

    private function invoicedGross(): float
    {
        return (float) $this->invoices()
            ->where('is_advance', false)
            ->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->sum('invoice_items.amount');
    }

    public function getRetentionHeldAttribute(): float
    {
        $rate = ($this->retention_short ?? 0) + ($this->retention_long ?? 0);
        if (!$rate) return 0.0;
        return round($this->invoicedGross() * $rate / 100, 2);
    }

    public function getRetentionShortHeldAttribute(): float
    {
        $rate = $this->retention_short ?? 0;
        if (!$rate) return 0.0;
        return round($this->invoicedGross() * $rate / 100, 2);
    }

    public function getRetentionShortReleasedAttribute(): float
    {
        return (float) $this->retentionReleases()->where('type', 'short')->sum('amount');
    }

    public function getRetentionShortRemainingAttribute(): float
    {
        return max(0.0, $this->retention_short_held - $this->retention_short_released);
    }

    public function getRetentionLongHeldAttribute(): float
    {
        $rate = $this->retention_long ?? 0;
        if (!$rate) return 0.0;
        return round($this->invoicedGross() * $rate / 100, 2);
    }

    public function getRetentionLongReleasedAttribute(): float
    {
        return (float) $this->retentionReleases()->where('type', 'long')->sum('amount');
    }

    public function getRetentionLongRemainingAttribute(): float
    {
        return max(0.0, $this->retention_long_held - $this->retention_long_released);
    }

    public function isDeletable(): bool
    {
        return $this->invoices()->doesntExist();
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable')->orderByDesc('created_at');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    protected $fillable = [
        'code', 'name', 'project_id', 'company_id',
        'direction', 'currency', 'date', 'description', 'maturity',
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

    public function isDeletable(): bool
    {
        return $this->invoices()->doesntExist();
    }
}

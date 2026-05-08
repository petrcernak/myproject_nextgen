<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    protected $fillable = [
        'code', 'name', 'project_id', 'company_id',
        'direction', 'currency', 'date', 'description', 'maturity', 'note',
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

    public function getTotalAttribute(): float
    {
        return $this->items()->sum('amount');
    }

    public function getInvoicedAttribute(): float
    {
        return $this->invoices()->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->sum('invoice_items.amount');
    }

    public function isDeletable(): bool
    {
        return $this->invoices()->doesntExist();
    }
}

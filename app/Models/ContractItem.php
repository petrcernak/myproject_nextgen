<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractItem extends Model
{
    protected $fillable = ['contract_id', 'code', 'description', 'amount', 'sort'];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function changeOrderItems(): HasMany
    {
        return $this->hasMany(ChangeOrderItem::class);
    }

    public function amendmentItems(): HasMany
    {
        return $this->hasMany(AmendmentItem::class);
    }

    public function getInvoicedAmountAttribute(): float
    {
        return (float) $this->invoiceItems()->sum('amount');
    }

    public function getTotalChangeAttribute(): float
    {
        return (float) $this->changeOrderItems()->sum('amount')
             + (float) $this->amendmentItems()->sum('amount');
    }

    public function getEffectiveAmountAttribute(): float
    {
        return $this->amount + $this->total_change;
    }

    public function getRemainingAmountAttribute(): float
    {
        return $this->effective_amount - $this->invoiced_amount;
    }
}

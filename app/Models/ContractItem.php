<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractItem extends Model
{
    protected $fillable = ['contract_id', 'contract_category_id', 'budget_item_id', 'code', 'description', 'amount', 'sort'];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ContractCategory::class, 'contract_category_id');
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

    public function changeRequestItems(): HasMany
    {
        return $this->hasMany(ChangeRequestItem::class);
    }

    public function anticipatedItems(): HasMany
    {
        return $this->hasMany(ContractAnticipatedItem::class);
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

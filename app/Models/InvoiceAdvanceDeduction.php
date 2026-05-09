<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceAdvanceDeduction extends Model
{
    protected $fillable = ['invoice_id', 'advance_invoice_id', 'amount'];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function advanceInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'advance_invoice_id');
    }
}

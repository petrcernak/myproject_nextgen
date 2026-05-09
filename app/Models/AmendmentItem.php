<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AmendmentItem extends Model
{
    protected $fillable = ['amendment_id', 'contract_item_id', 'amount', 'description', 'sort'];

    public function amendment(): BelongsTo
    {
        return $this->belongsTo(Amendment::class);
    }

    public function contractItem(): BelongsTo
    {
        return $this->belongsTo(ContractItem::class);
    }
}

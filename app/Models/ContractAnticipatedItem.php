<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractAnticipatedItem extends Model
{
    protected $fillable = ['contract_anticipated_id', 'contract_item_id', 'amount', 'description', 'sort'];

    public function anticipated(): BelongsTo
    {
        return $this->belongsTo(ContractAnticipated::class, 'contract_anticipated_id');
    }

    public function contractItem(): BelongsTo
    {
        return $this->belongsTo(ContractItem::class);
    }
}

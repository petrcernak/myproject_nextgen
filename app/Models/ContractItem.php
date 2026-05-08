<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractItem extends Model
{
    protected $fillable = ['contract_id', 'code', 'description', 'amount', 'sort'];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}

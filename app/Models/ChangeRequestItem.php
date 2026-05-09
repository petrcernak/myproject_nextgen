<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChangeRequestItem extends Model
{
    protected $fillable = ['change_request_id', 'contract_item_id', 'description', 'sort'];

    public function changeRequest(): BelongsTo
    {
        return $this->belongsTo(ChangeRequest::class);
    }

    public function contractItem(): BelongsTo
    {
        return $this->belongsTo(ContractItem::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(ChangeRequestItemRevision::class)->orderByDesc('date')->orderByDesc('id');
    }

    public function latestRevision(): HasOne
    {
        return $this->hasOne(ChangeRequestItemRevision::class)->ofMany([
            'date' => 'max',
            'id'   => 'max',
        ]);
    }
}

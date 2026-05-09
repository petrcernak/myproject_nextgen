<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangeRequestItemRevision extends Model
{
    protected $fillable = [
        'change_request_item_id', 'date',
        'amount_supplier', 'amount_pm', 'amount_report', 'note',
    ];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(ChangeRequestItem::class, 'change_request_item_id');
    }
}

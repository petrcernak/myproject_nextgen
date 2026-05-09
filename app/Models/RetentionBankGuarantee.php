<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class RetentionBankGuarantee extends Model
{
    protected $fillable = ['contract_id', 'amount', 'valid_from', 'valid_until', 'note'];

    protected function casts(): array
    {
        return [
            'valid_from'  => 'date',
            'valid_until' => 'date',
            'amount'      => 'float',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable')->orderByDesc('created_at');
    }
}

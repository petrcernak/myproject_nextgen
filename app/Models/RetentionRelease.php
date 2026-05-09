<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class RetentionRelease extends Model
{
    protected $fillable = ['contract_id', 'type', 'amount', 'release_date', 'note'];

    protected function casts(): array
    {
        return [
            'release_date' => 'date',
            'amount'       => 'float',
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

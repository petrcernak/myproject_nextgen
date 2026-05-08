<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = [
        'code', 'name', 'regno', 'taxregno',
        'email', 'phone', 'url', 'id_group', 'active',
    ];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'id_group');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'id_company');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'company_id');
    }
}

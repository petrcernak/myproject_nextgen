<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Budget;

class Project extends Model
{
    protected $fillable = [
        'code', 'name', 'status', 'note', 'id_company', 'id_group', 'active',
    ];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'id_group');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'id_company');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function userRights(): HasMany
    {
        return $this->hasMany(UserRight::class);
    }

    public function isDeletable(): bool
    {
        return $this->contracts()->doesntExist();
    }
}

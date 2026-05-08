<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    protected $fillable = ['name', 'code'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'id_group');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'id_group');
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'id_group');
    }
}

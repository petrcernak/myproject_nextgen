<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username', 'firstname', 'surname', 'email',
        'password', 'level', 'id_group', 'active', 'locale',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'active'   => 'boolean',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->firstname} {$this->surname}";
    }

    public function isSuperAdmin(): bool
    {
        return $this->level >= 9;
    }

    public function isGroupAdmin(): bool
    {
        return $this->level >= 7;
    }

    public function canRead(Project $project): bool
    {
        if ($this->level >= 5) return true;
        return $this->rights()->where('project_id', $project->id)->exists();
    }

    public function canWrite(Project $project): bool
    {
        if ($this->level >= 5) return true;
        return $this->rights()->where('project_id', $project->id)->where('pright', 'w')->exists();
    }

    public function canCreateProject(): bool
    {
        return $this->level >= 5;
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'id_group');
    }

    public function rights(): HasMany
    {
        return $this->hasMany(UserRight::class);
    }
}

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
        'password', 'level', 'id_group', 'active', 'locale', 'dark_mode',
        'default_project_id', 'default_page_type', 'default_page_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password'  => 'hashed',
            'active'    => 'boolean',
            'dark_mode' => 'boolean',
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

    public function getDefaultUrl(): ?string
    {
        return match($this->default_page_type) {
            'projects_index' => route('projects.index'),
            'project_show'   => $this->default_project_id ? route('projects.show', $this->default_project_id) : route('projects.index'),
            'budget'         => $this->default_page_id ? route('budgets.show', $this->default_page_id) : null,
            'contract'       => $this->default_page_id ? route('contracts.show', $this->default_page_id) : null,
            default          => null,
        };
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

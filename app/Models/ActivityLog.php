<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'id_group', 'action',
        'subject_type', 'subject_id', 'subject_label',
        'changes', 'ip',
    ];

    protected function casts(): array
    {
        return ['changes' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Fields to exclude from change tracking
    private static array $skipFields = [
        'updated_at', 'created_at', 'status', 'password', 'remember_token',
    ];

    public static function record(string $action, Model $model, array $changes = [], array $original = []): void
    {
        $user = auth()->user();
        if (!$user) return;

        $filtered = [];
        foreach ($changes as $field => $newValue) {
            if (in_array($field, self::$skipFields)) continue;
            $filtered[$field] = [
                'from' => $original[$field] ?? null,
                'to'   => $newValue,
            ];
        }

        static::create([
            'user_id'       => $user->id,
            'id_group'      => $user->id_group,
            'action'        => $action,
            'subject_type'  => class_basename($model),
            'subject_id'    => $model->getKey(),
            'subject_label' => self::resolveLabel($model),
            'changes'       => $filtered ?: null,
            'ip'            => request()->ip(),
        ]);
    }

    private static function resolveLabel(Model $model): string
    {
        foreach (['name', 'no', 'code', 'title', 'username'] as $field) {
            if (!empty($model->$field)) return $model->$field;
        }
        return (string) $model->getKey();
    }
}

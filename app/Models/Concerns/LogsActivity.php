<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;

trait LogsActivity
{
    protected static function bootLogsActivity(): void
    {
        static::created(fn ($m) => ActivityLog::record('created', $m));

        static::updated(fn ($m) => ActivityLog::record(
            'updated', $m, $m->getChanges(), $m->getOriginal()
        ));

        static::deleted(fn ($m) => ActivityLog::record('deleted', $m));
    }
}

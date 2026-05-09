<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class File extends Model
{
    protected $fillable = [
        'fileable_type', 'fileable_id',
        'original_name', 'stored_name', 'path',
        'mime_type', 'size', 'uploaded_by',
    ];

    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(FileTag::class, 'file_tag_file');
    }

    public function getFormattedSizeAttribute(): string
    {
        $kb = $this->size / 1024;
        if ($kb < 1024) return round($kb, 1) . ' KB';
        return round($kb / 1024, 1) . ' MB';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FileTag extends Model
{
    protected $fillable = ['id_group', 'name'];

    public function files(): BelongsToMany
    {
        return $this->belongsToMany(File::class, 'file_tag_file');
    }
}

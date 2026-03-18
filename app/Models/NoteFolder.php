<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NoteFolder extends Model
{
    use HasUuids, SoftDeletes;

    protected $connection = 'mysql';

    protected $fillable = [
        'user_id',
        'name',
        'icon_name',
        'order_index',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workspaces(): HasMany
    {
        return $this->hasMany(NoteWorkspace::class, 'folder_id');
    }
}

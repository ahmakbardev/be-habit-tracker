<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NoteWorkspace extends Model
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

    public function folders(): HasMany
    {
        return $this->hasMany(NoteFolder::class, 'workspace_id');
    }
}

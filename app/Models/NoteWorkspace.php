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
        'folder_id',
        'name',
        'icon_name',
        'order_index',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(NoteFolder::class, 'folder_id');
    }

    /**
     * Cross-Database Relationship to MongoDB
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class, 'workspace_id');
    }
}

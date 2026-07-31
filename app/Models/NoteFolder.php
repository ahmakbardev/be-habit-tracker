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
        'workspace_id',
        'name',
        'icon_name',
        'order_index',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(NoteWorkspace::class, 'workspace_id');
    }

    /**
     * Cross-Database Relationship to MongoDB
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class, 'folder_id');
    }
}

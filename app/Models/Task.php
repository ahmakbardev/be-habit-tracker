<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasUuids;

    protected $connection = 'mysql';

    protected $fillable = [
        'project_id',
        'column_id',
        'title',
        'description',
        'priority',
        'start_date',
        'due_date',
        'tags',
        'linked_note_id',
        'order_index',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'due_date' => 'datetime',
        'tags' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(TaskProject::class, 'project_id');
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(TaskColumn::class, 'column_id');
    }

    /**
     * Get the user that owns the task (through project and folder)
     */
    public function getUserAttribute()
    {
        return $this->project?->folder?->user;
    }
}

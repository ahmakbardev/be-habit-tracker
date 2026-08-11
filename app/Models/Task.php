<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'progress',
        'order_index',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'due_date' => 'datetime',
        'tags' => 'array',
        'progress' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(TaskProject::class, 'project_id');
    }

    public function column(): BelongsTo
    {
        return $this->belongsTo(TaskColumn::class, 'column_id');
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(TaskSubtask::class)->orderBy('order_index');
    }

    /**
     * Raw pivot rows only — note_id points into MongoDB, so the actual
     * Note documents are resolved manually (see TaskController::resolveTaskNotes).
     */
    public function taskNotes(): HasMany
    {
        return $this->hasMany(TaskNote::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class)->latest();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->latest();
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TaskActivity::class)->latest();
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_assignees');
    }

    /**
     * Get the user that owns the task (through project and workspace)
     */
    public function getUserAttribute()
    {
        return $this->project?->workspace?->user;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskSubtask extends Model
{
    use HasUuids;

    protected $connection = 'mysql';

    protected $fillable = [
        'task_id',
        'title',
        'completed',
        'order_index',
    ];

    protected $casts = [
        'completed' => 'boolean',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}

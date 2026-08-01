<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskActivity extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $connection = 'mysql';

    protected $fillable = [
        'task_id',
        'message',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}

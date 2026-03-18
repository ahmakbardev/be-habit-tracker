<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskProject extends Model
{
    use HasUuids;

    protected $fillable = [
        'folder_id',
        'name',
        'description',
        'icon_name',
        'status',
        'start_date',
        'end_date',
        'metadata',
        'order_index',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'metadata' => 'array',
    ];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(TaskFolder::class, 'folder_id');
    }

    public function columns(): HasMany
    {
        return $this->hasMany(TaskColumn::class, 'project_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'project_id');
    }
}

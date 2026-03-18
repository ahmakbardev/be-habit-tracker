<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Habit extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'habits';

    protected $fillable = [
        'user_id',
        'name',
        'icon_type',
        'color',
        'schedules',
        'goal',
        'archived_at',
    ];

    protected $casts = [
        'schedules' => 'array',
        'goal' => 'integer',
        'archived_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function completions(): HasMany
    {
        return $this->hasMany(HabitCompletion::class, 'habit_id');
    }
}

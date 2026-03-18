<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HabitCompletion extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'habit_completions';

    protected $fillable = [
        'habit_id',
        'user_id',
        'date',
        'time_slot',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    public function habit(): BelongsTo
    {
        return $this->belongsTo(Habit::class, 'habit_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MongoDB\Laravel\Eloquent\SoftDeletes;

class Note extends Model
{
    use SoftDeletes;

    protected $connection = 'mongodb';
    protected $collection = 'notes';

    protected $fillable = [
        'workspace_id',
        'title',
        'content',
        'plain_text_preview',
        'highlight',
        'order_index',
        'is_public',
        'public_token',
    ];

    protected $casts = [
        'content' => 'array',
        'highlight' => 'boolean',
        'is_public' => 'boolean',
    ];

    /**
     * Cross-Database Relationship back to MySQL
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(NoteWorkspace::class, 'workspace_id');
    }
}

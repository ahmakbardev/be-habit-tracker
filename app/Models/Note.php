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
    ];

    /**
     * Cast JSON content for easier access
     */
    protected $casts = [
        'content' => 'array',
        'highlight' => 'boolean',
    ];

    /**
     * Cross-Database Relationship back to MySQL
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(NoteWorkspace::class, 'workspace_id');
    }
}

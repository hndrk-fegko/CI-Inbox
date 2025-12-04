<?php

namespace CiInbox\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ThreadAssignment Model
 * 
 * Pivot model for thread-user assignments.
 */
class ThreadAssignment extends Model
{
    protected $table = 'thread_assignments';
    
    public $timestamps = false; // Only assigned_at timestamp
    
    protected $fillable = [
        'thread_id',
        'user_id',
        'assigned_at'
    ];
    
    protected $casts = [
        'assigned_at' => 'datetime'
    ];
    
    /**
     * Get the thread
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class);
    }
    
    /**
     * Get the assigned user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

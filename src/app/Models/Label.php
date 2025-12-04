<?php

namespace CiInbox\App\Models;

/**
 * Label Model
 * 
 * Represents a custom label/tag for organizing threads.
 */
class Label extends BaseModel
{
    protected $table = 'labels';

    protected $fillable = [
        'name',
        'color',
        'is_system_label',
        'display_order',
    ];

    protected $casts = [
        'is_system_label' => 'boolean',
        'display_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get threads with this label
     */
    public function threads()
    {
        return $this->belongsToMany(Thread::class, 'thread_labels')
            ->withPivot('applied_at');
    }
}

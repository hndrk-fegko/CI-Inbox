<?php

namespace CiInbox\App\Models;

/**
 * Thread Model
 * 
 * Represents an email conversation thread.
 */
class Thread extends BaseModel
{
    protected $table = 'threads';

    protected $fillable = [
        'subject',
        'participants',
        'preview',
        'status',
        'last_message_at',
        'message_count',
        'has_attachments',
    ];

    protected $casts = [
        'participants' => 'array',
        'has_attachments' => 'boolean',
        'last_message_at' => 'datetime',
        'message_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get emails in this thread
     */
    public function emails()
    {
        return $this->hasMany(Email::class)->orderBy('sent_at');
    }

    /**
     * Get assigned users
     */
    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'thread_assignments')
            ->withPivot('assigned_at');
    }

    /**
     * Get labels applied to this thread
     */
    public function labels()
    {
        return $this->belongsToMany(Label::class, 'thread_labels')
            ->withPivot('applied_at');
    }

    /**
     * Get internal notes for this thread
     */
    public function notes()
    {
        return $this->hasMany(InternalNote::class)->orderBy('created_at', 'desc');
    }

    /**
     * Check if all emails in thread are read
     * This is a computed property based on email is_read status
     */
    public function getIsReadAttribute(): bool
    {
        // If emails not loaded, load them
        if (!$this->relationLoaded('emails')) {
            $this->load('emails');
        }

        // If no emails, consider it read
        if ($this->emails->isEmpty()) {
            return true;
        }

        // Thread is read if ALL emails are read
        return $this->emails->every(fn($email) => $email->is_read);
    }
}

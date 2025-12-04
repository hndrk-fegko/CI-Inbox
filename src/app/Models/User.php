<?php

namespace CiInbox\App\Models;

/**
 * User Model
 * 
 * Represents application users (admins and agents).
 */
class User extends BaseModel
{
    protected $table = 'users';

    protected $fillable = [
        'email',
        'password_hash',
        'name',
        'role',
        'is_active',
        'avatar_path',
        'avatar_color',
        'timezone',
        'language',
        'theme_mode',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get avatar color with fallback
     * 
     * @return int Color number (1-8)
     */
    public function getAvatarColorAttribute($value)
    {
        // Fallback to calculated color if not set
        return $value ?? (($this->id % 8) + 1);
    }

    /**
     * Get IMAP accounts for this user
     */
    public function imapAccounts()
    {
        return $this->hasMany(ImapAccount::class);
    }

    /**
     * Get thread assignments for this user
     */
    public function assignedThreads()
    {
        return $this->belongsToMany(Thread::class, 'thread_assignments')
            ->withPivot('assigned_at');
    }
}

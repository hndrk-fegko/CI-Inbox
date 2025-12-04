<?php

namespace CiInbox\App\Models;

/**
 * ImapAccount Model
 * 
 * Represents IMAP account configurations.
 */
class ImapAccount extends BaseModel
{
    protected $table = 'imap_accounts';

    protected $fillable = [
        'user_id',
        'email',
        'imap_host',
        'imap_port',
        'imap_username',
        'imap_password_encrypted',
        'imap_encryption',
        'is_default',
        'is_active',
        'last_sync_at',
        'last_error',
        'sync_count',
    ];

    protected $hidden = [
        'imap_password_encrypted',
    ];

    protected $casts = [
        'imap_port' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sync_count' => 'integer',
        'last_sync_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns this IMAP account
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get emails fetched from this account
     */
    public function emails()
    {
        return $this->hasMany(Email::class);
    }
}

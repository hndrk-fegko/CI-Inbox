<?php

namespace CiInbox\App\Models;

/**
 * Email Model
 * 
 * Represents an individual email message within a thread.
 */
class Email extends BaseModel
{
    protected $table = 'emails';

    protected $fillable = [
        'thread_id',
        'imap_account_id',
        'message_id',
        'in_reply_to',
        'from_email',
        'from_name',
        'to_addresses',
        'cc_addresses',
        'subject',
        'body_plain',
        'body_html',
        'has_attachments',
        'attachment_metadata',
        'direction',
        'is_read',
        'sent_at',
    ];

    protected $casts = [
        'to_addresses' => 'array',
        'cc_addresses' => 'array',
        'attachment_metadata' => 'array',
        'has_attachments' => 'boolean',
        'is_read' => 'boolean',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the thread this email belongs to
     */
    public function thread()
    {
        return $this->belongsTo(Thread::class);
    }

    /**
     * Get the IMAP account this email was fetched from
     */
    public function imapAccount()
    {
        return $this->belongsTo(ImapAccount::class);
    }
}

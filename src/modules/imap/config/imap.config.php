<?php

/**
 * IMAP Configuration
 * 
 * Default settings for IMAP connections.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default IMAP Connection
    |--------------------------------------------------------------------------
    |
    | Default connection settings for IMAP server.
    | Can be overridden per account in imap_accounts table.
    |
    */
    'default' => [
        'host' => env('IMAP_HOST', 'imap.gmail.com'),
        'port' => env('IMAP_PORT', 993),
        'encryption' => env('IMAP_ENCRYPTION', 'ssl'), // ssl, tls, or null
        'validate_cert' => env('IMAP_VALIDATE_CERT', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Connection Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout in seconds for IMAP operations.
    |
    */
    'timeout' => env('IMAP_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Fetch Limits
    |--------------------------------------------------------------------------
    |
    | Limits for fetching messages from IMAP server.
    |
    */
    'fetch_limit' => env('IMAP_FETCH_LIMIT', 100),
    'max_fetch_limit' => env('IMAP_MAX_FETCH_LIMIT', 500),

    /*
    |--------------------------------------------------------------------------
    | Default Folders
    |--------------------------------------------------------------------------
    |
    | Standard IMAP folder names.
    | May vary by provider (Gmail uses [Gmail]/Sent, etc.)
    |
    */
    'folders' => [
        'inbox' => 'INBOX',
        'sent' => 'Sent',
        'trash' => 'Trash',
        'drafts' => 'Drafts',
        'spam' => 'Spam',
        'archive' => 'Archive',
    ],

    /*
    |--------------------------------------------------------------------------
    | Gmail-specific Folders
    |--------------------------------------------------------------------------
    |
    | Gmail uses non-standard folder names.
    |
    */
    'gmail_folders' => [
        'inbox' => 'INBOX',
        'sent' => '[Gmail]/Sent Mail',
        'trash' => '[Gmail]/Trash',
        'drafts' => '[Gmail]/Drafts',
        'spam' => '[Gmail]/Spam',
        'archive' => '[Gmail]/All Mail',
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Settings
    |--------------------------------------------------------------------------
    |
    | Retry failed IMAP operations.
    |
    */
    'retry' => [
        'enabled' => true,
        'max_attempts' => 3,
        'delay_seconds' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Polling Settings
    |--------------------------------------------------------------------------
    |
    | Settings for webcron email polling.
    |
    */
    'polling' => [
        'enabled' => env('IMAP_POLLING_ENABLED', true),
        'interval_minutes' => env('IMAP_POLLING_INTERVAL', 5),
        'fetch_unread_only' => env('IMAP_FETCH_UNREAD_ONLY', true),
    ],
];

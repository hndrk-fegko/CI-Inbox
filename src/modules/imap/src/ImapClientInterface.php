<?php

declare(strict_types=1);

namespace CiInbox\Modules\Imap;

/**
 * IMAP Client Interface
 * 
 * Defines the contract for IMAP client implementations.
 * Wraps php-imap extension with clean OOP interface.
 */
interface ImapClientInterface
{
    /**
     * Connect to IMAP server
     * 
     * @param string $host IMAP hostname (e.g., imap.gmail.com)
     * @param int $port IMAP port (993 for SSL, 143 for non-SSL)
     * @param string $username IMAP username (usually email address)
     * @param string $password IMAP password
     * @param bool $ssl Use SSL/TLS encryption (default: true)
     * @return bool True on success
     * @throws ImapException On connection failure
     */
    public function connect(
        string $host,
        int $port,
        string $username,
        string $password,
        bool $ssl = true
    ): bool;

    /**
     * Disconnect from IMAP server
     * 
     * @return void
     */
    public function disconnect(): void;

    /**
     * Check if connected to IMAP server
     * 
     * @return bool
     */
    public function isConnected(): bool;

    /**
     * Get list of all folders/mailboxes
     * 
     * @return array Array of folder names ['INBOX', 'Sent', 'Trash', ...]
     * @throws ImapException If not connected
     */
    public function getFolders(): array;

    /**
     * Select a folder/mailbox
     * 
     * @param string $folder Folder name (e.g., 'INBOX')
     * @return void
     * @throws ImapException If folder doesn't exist
     */
    public function selectFolder(string $folder): void;

    /**
     * Get current selected folder
     * 
     * @return string|null Current folder name or null if none selected
     */
    public function getCurrentFolder(): ?string;

    /**
     * Get total message count in current folder
     * 
     * @return int Number of messages
     * @throws ImapException If no folder selected
     */
    public function getMessageCount(): int;

    /**
     * Get messages from current folder
     * 
     * @param int $limit Maximum number of messages to fetch (default: 100)
     * @param bool $unreadOnly Fetch only unread messages (default: false)
     * @return array Array of ImapMessageInterface objects
     * @throws ImapException If no folder selected
     */
    public function getMessages(int $limit = 100, bool $unreadOnly = false): array;

    /**
     * Get single message by UID
     * 
     * @param string $uid Message UID
     * @return ImapMessageInterface
     * @throws ImapException If message not found
     */
    public function getMessage(string $uid): ImapMessageInterface;

    /**
     * Move message to another folder
     * 
     * @param string $uid Message UID
     * @param string $targetFolder Target folder name
     * @return bool True on success
     * @throws ImapException On failure
     */
    public function moveMessage(string $uid, string $targetFolder): bool;

    /**
     * Delete message (move to Trash or mark for deletion)
     * 
     * @param string $uid Message UID
     * @return bool True on success
     * @throws ImapException On failure
     */
    public function deleteMessage(string $uid): bool;

    /**
     * Mark message as read
     * 
     * @param string $uid Message UID
     * @return bool True on success
     * @throws ImapException On failure
     */
    public function markAsRead(string $uid): bool;

    /**
     * Mark message as unread
     * 
     * @param string $uid Message UID
     * @return bool True on success
     * @throws ImapException On failure
     */
    public function markAsUnread(string $uid): bool;

    /**
     * Search messages by IMAP search criteria
     * 
     * @param string $criteria IMAP search criteria (e.g., 'UNSEEN', 'UNKEYWORD CI-Synced')
     * @return array Array of message UIDs matching criteria
     * @throws ImapException On failure
     */
    public function search(string $criteria): array;

    /**
     * Add keyword/flag to message
     * 
     * @param string $uid Message UID
     * @param string $keyword Keyword to add (e.g., 'CI-Synced')
     * @return bool True on success, false if keywords not supported
     * @throws ImapException On failure
     */
    public function addKeyword(string $uid, string $keyword): bool;

    /**
     * Remove keyword/flag from message
     * 
     * @param string $uid Message UID
     * @param string $keyword Keyword to remove
     * @return bool True on success
     * @throws ImapException On failure
     */
    public function removeKeyword(string $uid, string $keyword): bool;

    /**
     * Get keywords/flags for message
     * 
     * @param string $uid Message UID
     * @return array Array of keywords/flags
     * @throws ImapException On failure
     */
    public function getKeywords(string $uid): array;

    /**
     * Get last error message
     * 
     * @return string|null Last error or null if no error
     */
    public function getLastError(): ?string;
}

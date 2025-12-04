<?php

declare(strict_types=1);

namespace CiInbox\Modules\Imap;

/**
 * IMAP Message Interface
 * 
 * Represents a single email message with all its properties.
 */
interface ImapMessageInterface
{
    /**
     * Get message UID (unique identifier)
     * 
     * @return string
     */
    public function getUid(): string;

    /**
     * Get Message-ID header (RFC 822)
     * 
     * @return string
     */
    public function getMessageId(): string;

    /**
     * Get In-Reply-To header (for threading)
     * 
     * @return string|null Message-ID of parent message or null
     */
    public function getInReplyTo(): ?string;

    /**
     * Get References header (for threading)
     * 
     * @return array Array of Message-IDs
     */
    public function getReferences(): array;

    /**
     * Get email subject
     * 
     * @return string
     */
    public function getSubject(): string;

    /**
     * Get sender (From header)
     * 
     * @return array ['email' => 'sender@example.com', 'name' => 'Sender Name']
     */
    public function getFrom(): array;

    /**
     * Get recipients (To header)
     * 
     * @return array Array of ['email' => '...', 'name' => '...']
     */
    public function getTo(): array;

    /**
     * Get CC recipients
     * 
     * @return array Array of ['email' => '...', 'name' => '...']
     */
    public function getCc(): array;

    /**
     * Get BCC recipients (if available)
     * 
     * @return array Array of ['email' => '...', 'name' => '...']
     */
    public function getBcc(): array;

    /**
     * Get message date
     * 
     * @return \DateTime
     */
    public function getDate(): \DateTime;

    /**
     * Get plain text body
     * 
     * @return string
     */
    public function getBodyText(): string;

    /**
     * Get HTML body
     * 
     * @return string
     */
    public function getBodyHtml(): string;

    /**
     * Get attachments
     * 
     * @return array Array of ['filename' => '...', 'size' => bytes, 'mime_type' => '...']
     */
    public function getAttachments(): array;

    /**
     * Check if message has attachments
     * 
     * @return bool
     */
    public function hasAttachments(): bool;

    /**
     * Get raw email headers
     * 
     * @return string
     */
    public function getRawHeaders(): string;

    /**
     * Get specific header value
     * 
     * @param string $header Header name (e.g., 'X-Mailer')
     * @return string|null Header value or null if not found
     */
    public function getHeader(string $header): ?string;

    /**
     * Check if message is unread
     * 
     * @return bool
     */
    public function isUnread(): bool;

    /**
     * Check if message is flagged/starred
     * 
     * @return bool
     */
    public function isFlagged(): bool;

    /**
     * Get message size in bytes
     * 
     * @return int
     */
    public function getSize(): int;

    /**
     * Get all message data as array
     * 
     * @return array
     */
    public function toArray(): array;
}

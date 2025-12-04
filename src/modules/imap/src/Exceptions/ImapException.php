<?php

declare(strict_types=1);

namespace CiInbox\Modules\Imap\Exceptions;

/**
 * IMAP Exception
 * 
 * Thrown when IMAP operations fail.
 */
class ImapException extends \Exception
{
    /**
     * Create exception from IMAP error
     * 
     * @param string $message Error message
     * @param int $code Error code (default: 0)
     * @param \Throwable|null $previous Previous exception
     * @return static
     */
    public static function fromImapError(
        string $message,
        int $code = 0,
        ?\Throwable $previous = null
    ): static {
        // Get last IMAP error if available
        $imapErrors = imap_errors();
        if ($imapErrors && is_array($imapErrors)) {
            $message .= ' | IMAP Errors: ' . implode(', ', $imapErrors);
        }

        return new static($message, $code, $previous);
    }

    /**
     * Connection failed exception
     * 
     * @param string $host
     * @param int $port
     * @param string $reason
     * @return static
     */
    public static function connectionFailed(string $host, int $port, string $reason = ''): static
    {
        $message = "Failed to connect to IMAP server {$host}:{$port}";
        if ($reason) {
            $message .= " - {$reason}";
        }
        return static::fromImapError($message);
    }

    /**
     * Not connected exception
     * 
     * @return static
     */
    public static function notConnected(): static
    {
        return new static('Not connected to IMAP server');
    }

    /**
     * Folder not found exception
     * 
     * @param string $folder
     * @return static
     */
    public static function folderNotFound(string $folder): static
    {
        return new static("Folder not found: {$folder}");
    }

    /**
     * No folder selected exception
     * 
     * @return static
     */
    public static function noFolderSelected(): static
    {
        return new static('No folder selected. Call selectFolder() first.');
    }

    /**
     * Message not found exception
     * 
     * @param string $uid
     * @return static
     */
    public static function messageNotFound(string $uid): static
    {
        return new static("Message not found: {$uid}");
    }

    /**
     * Operation failed exception
     * 
     * @param string $operation
     * @param string $reason
     * @return static
     */
    public static function operationFailed(string $operation, string $reason = ''): static
    {
        $message = "IMAP operation failed: {$operation}";
        if ($reason) {
            $message .= " - {$reason}";
        }
        return static::fromImapError($message);
    }

    /**
     * Extension not available exception
     * 
     * @return static
     */
    public static function extensionNotAvailable(): static
    {
        return new static(
            'PHP IMAP extension is not available. Install with: sudo apt-get install php-imap'
        );
    }
}

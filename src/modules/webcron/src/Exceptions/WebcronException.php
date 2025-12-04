<?php
declare(strict_types=1);

namespace CiInbox\Modules\Webcron\Exceptions;

use Exception;

/**
 * Webcron Exception
 * 
 * Custom Exception für Webcron-Modul
 */
class WebcronException extends Exception
{
    /**
     * Account nicht gefunden
     */
    public static function accountNotFound(int $accountId): self
    {
        return new self("IMAP Account #{$accountId} not found.", 404);
    }
    
    /**
     * Account inaktiv
     */
    public static function accountInactive(int $accountId): self
    {
        return new self("IMAP Account #{$accountId} is inactive.", 403);
    }
    
    /**
     * Job bereits aktiv
     */
    public static function jobAlreadyRunning(): self
    {
        return new self("Polling job is already running.", 409);
    }
    
    /**
     * IMAP Connection failed
     */
    public static function imapConnectionFailed(string $host, string $error): self
    {
        return new self("IMAP connection to {$host} failed: {$error}", 500);
    }
    
    /**
     * Polling Timeout
     */
    public static function pollingTimeout(int $accountId, int $seconds): self
    {
        return new self("Polling for account #{$accountId} timed out after {$seconds} seconds.", 408);
    }
    
    /**
     * Config missing
     */
    public static function configMissing(string $key): self
    {
        return new self("Webcron config key '{$key}' is missing.", 500);
    }
    
    /**
     * No active accounts
     */
    public static function noActiveAccounts(): self
    {
        return new self("No active IMAP accounts found for polling.", 404);
    }
}

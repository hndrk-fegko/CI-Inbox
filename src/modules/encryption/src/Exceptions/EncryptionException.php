<?php

namespace CiInbox\Modules\Encryption\Exceptions;

use Exception;

/**
 * Encryption Exception
 * 
 * Custom exception for encryption-related errors.
 * 
 * @package CiInbox\Modules\Encryption\Exceptions
 */
class EncryptionException extends Exception
{
    /**
     * Create exception for invalid encryption key
     * 
     * @param string $message Additional error details
     * @return self
     */
    public static function invalidKey(string $message = ''): self
    {
        $baseMessage = 'Invalid encryption key configured';
        $fullMessage = $message ? "{$baseMessage}: {$message}" : $baseMessage;
        
        return new self($fullMessage, 1001);
    }

    /**
     * Create exception for encryption failure
     * 
     * @param string $message Additional error details
     * @return self
     */
    public static function encryptionFailed(string $message = ''): self
    {
        $baseMessage = 'Encryption operation failed';
        $fullMessage = $message ? "{$baseMessage}: {$message}" : $baseMessage;
        
        return new self($fullMessage, 1002);
    }

    /**
     * Create exception for decryption failure
     * 
     * @param string $message Additional error details
     * @return self
     */
    public static function decryptionFailed(string $message = ''): self
    {
        $baseMessage = 'Decryption operation failed';
        $fullMessage = $message ? "{$baseMessage}: {$message}" : $baseMessage;
        
        return new self($fullMessage, 1003);
    }

    /**
     * Create exception for invalid encrypted data format
     * 
     * @return self
     */
    public static function invalidFormat(): self
    {
        return new self('Invalid encrypted data format. Expected "iv::encrypted"', 1004);
    }
}

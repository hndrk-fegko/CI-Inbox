<?php
<?php

declare(strict_types=1);

namespace CiInbox\Modules\Encryption;

use RuntimeException;

/**
 * Setup Encryption Service
 * 
 * Lightweight encryption service for setup wizard only.
 * Uses AES-256-CBC without module dependencies (no Logger, Config, or ModuleHealthInterface).
 * 
 * Once setup completes, the production EncryptionService takes over.
 * 
 * @package CiInbox\Modules\Encryption
 */
class SetupEncryptionService
{
    private const CIPHER = 'AES-256-CBC';
    private const SEPARATOR = '::';
    private string $key;

    /**
     * Constructor with explicit key
     * 
     * @param string $encryptionKey 64 hex characters (256-bit key)
     * @throws RuntimeException If key is invalid
     */
    public function __construct(string $encryptionKey)
    {
        if (empty($encryptionKey)) {
            throw new RuntimeException('Encryption key cannot be empty');
        }

        // Convert hex to binary
        $this->key = hex2bin($encryptionKey);
        
        if ($this->key === false || strlen($this->key) !== 32) {
            throw new RuntimeException('Invalid encryption key: must be 64 hex characters (256-bit)');
        }
    }

    /**
     * Encrypt string data
     * 
     * @param string $data Data to encrypt
     * @return string Base64-encoded encrypted data with IV
     * @throws RuntimeException On encryption failure
     */
    public function encrypt(string $data): string
    {
        if (empty($data)) {
            throw new RuntimeException('Cannot encrypt empty data');
        }

        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        if ($ivLength === false) {
            throw new RuntimeException('Failed to get IV length for cipher');
        }

        $iv = openssl_random_pseudo_bytes($ivLength, $strong);
        if (!$strong) {
            throw new RuntimeException('Failed to generate strong random IV');
        }

        $encrypted = openssl_encrypt(
            $data,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encrypted === false) {
            throw new RuntimeException('Encryption failed: ' . openssl_error_string());
        }

        return base64_encode($iv) . self::SEPARATOR . base64_encode($encrypted);
    }

    /**
     * Decrypt encrypted data
     * 
     * @param string $encrypted Base64-encoded encrypted data with IV
     * @return string Decrypted plaintext
     * @throws RuntimeException On decryption failure
     */
    public function decrypt(string $encrypted): string
    {
        if (!str_contains($encrypted, self::SEPARATOR)) {
            throw new RuntimeException('Invalid encrypted data format');
        }

        [$ivBase64, $encryptedBase64] = explode(self::SEPARATOR, $encrypted, 2);

        $iv = base64_decode($ivBase64, true);
        $encryptedData = base64_decode($encryptedBase64, true);

        if ($iv === false || $encryptedData === false) {
            throw new RuntimeException('Invalid base64 encoding in encrypted data');
        }

        $decrypted = openssl_decrypt(
            $encryptedData,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decrypted === false) {
            throw new RuntimeException('Decryption failed: ' . openssl_error_string());
        }

        return $decrypted;
    }

    /**
     * Static method: Generate a new encryption key
     * 
     * @return string 64 hex characters (ready for ENCRYPTION_KEY)
     */
    public static function generateKey(): string
    {
        return bin2hex(random_bytes(32));
    }
}
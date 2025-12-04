<?php

namespace CiInbox\Modules\Encryption;

use CiInbox\Modules\Encryption\Exceptions\EncryptionException;

/**
 * Encryption Service Interface
 * 
 * Provides methods for encrypting and decrypting sensitive data.
 * Uses AES-256-CBC encryption with OpenSSL.
 * 
 * @package CiInbox\Modules\Encryption
 */
interface EncryptionInterface
{
    /**
     * Encrypt a string
     * 
     * @param string $data Plain text data to encrypt
     * @return string Encrypted data (format: "base64_iv::base64_encrypted")
     * @throws EncryptionException If encryption fails
     */
    public function encrypt(string $data): string;

    /**
     * Decrypt an encrypted string
     * 
     * @param string $encrypted Encrypted data (format: "base64_iv::base64_encrypted")
     * @return string Decrypted plain text
     * @throws EncryptionException If decryption fails or format is invalid
     */
    public function decrypt(string $encrypted): string;

    /**
     * Get the cipher algorithm used
     * 
     * @return string Cipher name (e.g., "AES-256-CBC")
     */
    public function getCipher(): string;

    /**
     * Verify if encryption key is properly configured
     * 
     * @return bool True if key is valid
     */
    public function isKeyValid(): bool;
}

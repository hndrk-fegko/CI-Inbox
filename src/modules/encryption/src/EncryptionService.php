<?php

namespace CiInbox\Modules\Encryption;

use CiInbox\Modules\Config\ConfigInterface;
use CiInbox\Modules\Encryption\Exceptions\EncryptionException;
use CiInbox\Modules\Logger\LoggerService;
use CiInbox\App\Interfaces\ModuleHealthInterface;
use CiInbox\App\DTOs\ModuleHealthDTO;

/**
 * Encryption Service
 * 
 * Provides AES-256-CBC encryption/decryption for sensitive data.
 * Uses OpenSSL extension with random IV per encryption.
 * 
 * Encrypted data format: "base64_iv::base64_encrypted"
 * 
 * Example usage:
 * ```php
 * $encrypted = $encryptionService->encrypt('my_password');
 * $decrypted = $encryptionService->decrypt($encrypted);
 * ```
 * 
 * @package CiInbox\Modules\Encryption
 */
class EncryptionService implements EncryptionInterface, ModuleHealthInterface
{
    /**
     * Cipher algorithm
     */
    private const CIPHER = 'AES-256-CBC';

    /**
     * Separator between IV and encrypted data
     */
    private const SEPARATOR = '::';

    /**
     * Encryption key (binary)
     * 
     * @var string
     */
    private string $key;

    /**
     * Config service
     * 
     * @var ConfigInterface
     */
    private ConfigInterface $config;

    /**
     * Logger service
     * 
     * @var LoggerService|null
     */
    private ?LoggerService $logger = null;

    /**
     * Constructor
     * 
     * @param ConfigInterface $config Config service for key retrieval
     * @param LoggerService|null $logger Logger service (optional)
     * @throws EncryptionException If key is invalid
     */
    public function __construct(ConfigInterface $config, ?LoggerService $logger = null)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->loadKey();
    }

    /**
     * Load and validate encryption key from config
     * 
     * @throws EncryptionException If key is missing or invalid
     */
    private function loadKey(): void
    {
        $this->logger?->debug('Loading encryption key');
        
        // Get key from config (should be in .env as ENCRYPTION_KEY)
        $keyString = $this->config->getString('ENCRYPTION_KEY');

        // Remove "base64:" prefix if present
        if (str_starts_with($keyString, 'base64:')) {
            $keyString = substr($keyString, 7);
        }

        // Decode base64 key
        $this->key = base64_decode($keyString, true);

        if ($this->key === false) {
            $this->logger?->error('Encryption key is not valid base64');
            throw EncryptionException::invalidKey('Key is not valid base64');
        }

        // Validate key length for AES-256 (32 bytes = 256 bits)
        $keyLength = strlen($this->key);
        if ($keyLength !== 32) {
            $this->logger?->error('Invalid encryption key length', [
                'expected' => 32,
                'actual' => $keyLength
            ]);
            throw EncryptionException::invalidKey(
                'Key must be 32 bytes (256 bits) for AES-256. Got: ' . $keyLength . ' bytes'
            );
        }
        
        $this->logger?->info('Encryption key loaded and validated', ['cipher' => self::CIPHER]);
    }

    /**
     * {@inheritDoc}
     */
    public function encrypt(string $data): string
    {
        // Handle empty string
        if ($data === '') {
            $this->logger?->warning('Attempted to encrypt empty string');
            throw EncryptionException::encryptionFailed('Cannot encrypt empty string');
        }

        $dataLength = strlen($data);
        $this->logger?->debug('Encrypting data', ['length' => $dataLength]);

        // Generate random IV
        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        if ($ivLength === false) {
            $this->logger?->error('Failed to get IV length');
            throw EncryptionException::encryptionFailed('Failed to get IV length for cipher');
        }

        $iv = openssl_random_pseudo_bytes($ivLength, $strong);
        if (!$strong) {
            $this->logger?->error('Failed to generate strong random IV');
            throw EncryptionException::encryptionFailed('Failed to generate strong random IV');
        }

        // Encrypt data
        $encrypted = openssl_encrypt(
            $data,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encrypted === false) {
            $error = openssl_error_string();
            $this->logger?->error('Encryption failed', ['error' => $error]);
            throw EncryptionException::encryptionFailed('OpenSSL encryption failed: ' . $error);
        }

        // Encode IV and encrypted data as base64 and combine
        $ivBase64 = base64_encode($iv);
        $encryptedBase64 = base64_encode($encrypted);

        $this->logger?->debug('Data encrypted successfully', [
            'original_length' => $dataLength,
            'encrypted_length' => strlen($encrypted)
        ]);

        return $ivBase64 . self::SEPARATOR . $encryptedBase64;
    }

    /**
     * {@inheritDoc}
     */
    public function decrypt(string $encrypted): string
    {
        $this->logger?->debug('Decrypting data');
        
        // Validate format
        if (!str_contains($encrypted, self::SEPARATOR)) {
            $this->logger?->error('Invalid encrypted data format (missing separator)');
            throw EncryptionException::invalidFormat();
        }

        // Split IV and encrypted data
        $parts = explode(self::SEPARATOR, $encrypted, 2);
        if (count($parts) !== 2) {
            $this->logger?->error('Invalid encrypted data format (wrong part count)');
            throw EncryptionException::invalidFormat();
        }

        [$ivBase64, $encryptedBase64] = $parts;

        // Decode base64
        $iv = base64_decode($ivBase64, true);
        $encryptedData = base64_decode($encryptedBase64, true);

        if ($iv === false || $encryptedData === false) {
            $this->logger?->error('Invalid base64 encoding in encrypted data');
            throw EncryptionException::decryptionFailed('Invalid base64 encoding');
        }

        // Validate IV length
        $expectedIvLength = openssl_cipher_iv_length(self::CIPHER);
        $actualIvLength = strlen($iv);
        if ($actualIvLength !== $expectedIvLength) {
            $this->logger?->error('Invalid IV length', [
                'expected' => $expectedIvLength,
                'actual' => $actualIvLength
            ]);
            throw EncryptionException::decryptionFailed(
                "Invalid IV length. Expected {$expectedIvLength} bytes, got {$actualIvLength}"
            );
        }

        // Decrypt data
        $decrypted = openssl_decrypt(
            $encryptedData,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decrypted === false) {
            $error = openssl_error_string();
            $this->logger?->error('Decryption failed', ['error' => $error]);
            throw EncryptionException::decryptionFailed('OpenSSL decryption failed: ' . $error);
        }

        $this->logger?->debug('Data decrypted successfully', ['length' => strlen($decrypted)]);

        return $decrypted;
    }

    /**
     * {@inheritDoc}
     */
    public function getCipher(): string
    {
        return self::CIPHER;
    }

    /**
     * {@inheritDoc}
     */
    public function isKeyValid(): bool
    {
        try {
            // Try to encrypt and decrypt a test string
            $test = 'test_encryption_key_validity';
            $encrypted = $this->encrypt($test);
            $decrypted = $this->decrypt($encrypted);
            
            return $decrypted === $test;
        } catch (EncryptionException $e) {
            return false;
        }
    }

    // ========================================
    // ModuleHealthInterface Implementation
    // ========================================

    /**
     * {@inheritDoc}
     */
    public function getModuleName(): string
    {
        return 'encryption';
    }

    /**
     * {@inheritDoc}
     */
    public function getHealthStatus(): ModuleHealthDTO
    {
        $keyValid = false;
        $opensslLoaded = extension_loaded('openssl');
        $keyLength = strlen($this->key);
        $errorMessage = null;

        try {
            $keyValid = $this->isKeyValid();
        } catch (\Exception $e) {
            $errorMessage = 'Key validation failed: ' . $e->getMessage();
        }

        $status = ModuleHealthDTO::STATUS_OK;
        
        if (!$opensslLoaded) {
            $status = ModuleHealthDTO::STATUS_CRITICAL;
            $errorMessage = 'OpenSSL extension not loaded';
        } elseif (!$keyValid) {
            $status = ModuleHealthDTO::STATUS_CRITICAL;
            $errorMessage = $errorMessage ?? 'Encryption key validation failed';
        } elseif ($keyLength !== 32) {
            $status = ModuleHealthDTO::STATUS_CRITICAL;
            $errorMessage = 'Invalid key length: ' . $keyLength . ' bytes (expected 32)';
        }

        return new ModuleHealthDTO(
            moduleName: $this->getModuleName(),
            status: $status,
            testPassed: $opensslLoaded && $keyValid && $keyLength === 32,
            metrics: [
                'cipher' => self::CIPHER,
                'openssl_loaded' => $opensslLoaded,
                'key_valid' => $keyValid,
                'key_length' => $keyLength,
                'key_length_valid' => $keyLength === 32
            ],
            errorMessage: $errorMessage
        );
    }

    /**
     * {@inheritDoc}
     */
    public function runHealthTest(): bool
    {
        try {
            // Test 1: OpenSSL loaded
            if (!extension_loaded('openssl')) {
                return false;
            }

            // Test 2: Key validation (encrypt/decrypt round-trip)
            if (!$this->isKeyValid()) {
                return false;
            }

            // Test 3: Encrypt/decrypt real data
            $testData = 'Health check test: ' . bin2hex(random_bytes(16));
            $encrypted = $this->encrypt($testData);
            $decrypted = $this->decrypt($encrypted);

            return $decrypted === $testData;
        } catch (\Exception $e) {
            $this->logger?->error('Encryption health test failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}


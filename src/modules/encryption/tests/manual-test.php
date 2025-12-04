<?php

/**
 * Manual Test for Encryption Module
 * 
 * Tests the EncryptionService standalone without the full application.
 * Run with: C:\xampp\php\php.exe src/modules/encryption/tests/manual-test.php
 */

// Bootstrap
require_once __DIR__ . '/../../../../vendor/autoload.php';

use CiInbox\Modules\Config\ConfigService;
use CiInbox\Modules\Encryption\EncryptionService;
use CiInbox\Modules\Encryption\Exceptions\EncryptionException;

echo "=== CI-Inbox Encryption Module - Manual Test ===" . PHP_EOL . PHP_EOL;

try {
    // Test 1: Create services
    echo "1. Creating ConfigService and EncryptionService..." . PHP_EOL;
    $config = new ConfigService(__DIR__ . '/../../../../');
    $encryption = new EncryptionService($config);
    echo "   ✅ EncryptionService created" . PHP_EOL;
    echo "   Cipher: " . $encryption->getCipher() . PHP_EOL . PHP_EOL;

    // Test 2: Basic encryption
    echo "2. Testing basic encryption..." . PHP_EOL;
    $original = 'Hello World';
    $encrypted = $encryption->encrypt($original);
    echo "   Original: '{$original}'" . PHP_EOL;
    echo "   Encrypted: '{$encrypted}'" . PHP_EOL;
    echo "   Encrypted length: " . strlen($encrypted) . " chars" . PHP_EOL;
    echo "   ✅ Encryption works" . PHP_EOL . PHP_EOL;

    // Test 3: Decryption
    echo "3. Testing decryption..." . PHP_EOL;
    $decrypted = $encryption->decrypt($encrypted);
    echo "   Decrypted: '{$decrypted}'" . PHP_EOL;
    echo "   ✅ Decryption works" . PHP_EOL . PHP_EOL;

    // Test 4: Round-trip verification
    echo "4. Testing round-trip..." . PHP_EOL;
    if ($original === $decrypted) {
        echo "   ✅ Original matches decrypted" . PHP_EOL . PHP_EOL;
    } else {
        echo "   ❌ Mismatch! Original: '{$original}', Decrypted: '{$decrypted}'" . PHP_EOL . PHP_EOL;
        exit(1);
    }

    // Test 5: Special characters
    echo "5. Testing special characters..." . PHP_EOL;
    $special = 'Ümlaut ñ 中文 🔐 @#$%^&*()';
    $encryptedSpecial = $encryption->encrypt($special);
    $decryptedSpecial = $encryption->decrypt($encryptedSpecial);
    echo "   Original: '{$special}'" . PHP_EOL;
    echo "   Decrypted: '{$decryptedSpecial}'" . PHP_EOL;
    if ($special === $decryptedSpecial) {
        echo "   ✅ Special characters work" . PHP_EOL . PHP_EOL;
    } else {
        echo "   ❌ Special character mismatch!" . PHP_EOL . PHP_EOL;
        exit(1);
    }

    // Test 6: IMAP password (real use case)
    echo "6. Testing IMAP password (real use case)..." . PHP_EOL;
    $password = 'my$ecret!Pass123';
    $encryptedPassword = $encryption->encrypt($password);
    $decryptedPassword = $encryption->decrypt($encryptedPassword);
    echo "   Password: '{$password}'" . PHP_EOL;
    echo "   Encrypted length: " . strlen($encryptedPassword) . " chars" . PHP_EOL;
    echo "   Encrypted format valid: " . (str_contains($encryptedPassword, '::') ? 'Yes' : 'No') . PHP_EOL;
    if ($password === $decryptedPassword) {
        echo "   ✅ Password encryption works" . PHP_EOL . PHP_EOL;
    } else {
        echo "   ❌ Password mismatch!" . PHP_EOL . PHP_EOL;
        exit(1);
    }

    // Test 7: Unique IVs (same data should produce different encrypted strings)
    echo "7. Testing unique IVs..." . PHP_EOL;
    $data = 'test_data';
    $enc1 = $encryption->encrypt($data);
    $enc2 = $encryption->encrypt($data);
    echo "   Encryption 1: " . substr($enc1, 0, 50) . "..." . PHP_EOL;
    echo "   Encryption 2: " . substr($enc2, 0, 50) . "..." . PHP_EOL;
    if ($enc1 !== $enc2) {
        echo "   ✅ Unique IVs (same data produces different ciphertext)" . PHP_EOL . PHP_EOL;
    } else {
        echo "   ❌ IVs not unique!" . PHP_EOL . PHP_EOL;
        exit(1);
    }

    // Test 8: Key validation
    echo "8. Testing key validation..." . PHP_EOL;
    $isValid = $encryption->isKeyValid();
    echo "   Key valid: " . ($isValid ? 'Yes' : 'No') . PHP_EOL;
    if ($isValid) {
        echo "   ✅ Key validation works" . PHP_EOL . PHP_EOL;
    } else {
        echo "   ❌ Key validation failed!" . PHP_EOL . PHP_EOL;
        exit(1);
    }

    // Test 9: Exception for invalid format
    echo "9. Testing exception for invalid format..." . PHP_EOL;
    try {
        $encryption->decrypt('invalid_format_no_separator');
        echo "   ❌ Should have thrown exception!" . PHP_EOL . PHP_EOL;
        exit(1);
    } catch (EncryptionException $e) {
        echo "   ✅ Exception thrown: " . $e->getMessage() . PHP_EOL . PHP_EOL;
    }

    // Test 10: Exception for empty string
    echo "10. Testing exception for empty string..." . PHP_EOL;
    try {
        $encryption->encrypt('');
        echo "    ❌ Should have thrown exception!" . PHP_EOL . PHP_EOL;
        exit(1);
    } catch (EncryptionException $e) {
        echo "    ✅ Exception thrown: " . $e->getMessage() . PHP_EOL . PHP_EOL;
    }

    echo "===========================================" . PHP_EOL;
    echo "✅ ALL TESTS PASSED" . PHP_EOL;
    echo "===========================================" . PHP_EOL;

} catch (Exception $e) {
    echo "❌ TEST FAILED: " . $e->getMessage() . PHP_EOL;
    echo "   File: " . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
    echo "   Trace:" . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
    exit(1);
}

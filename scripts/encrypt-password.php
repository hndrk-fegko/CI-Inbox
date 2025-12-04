<?php
/**
 * Encrypt Password Script
 * 
 * Usage: php scripts/encrypt-password.php <password>
 */

require __DIR__ . '/../vendor/autoload.php';

use CiInbox\Modules\Encryption\EncryptionService;
use CiInbox\Modules\Config\ConfigService;

// Check argument
if ($argc < 2) {
    echo "Usage: php scripts/encrypt-password.php <password>\n";
    exit(1);
}

$password = $argv[1];

// Load .env manually
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Setup services  
$config = new ConfigService(__DIR__ . '/../src/config');
$encryptionService = new EncryptionService($config);

// Encrypt password
$encrypted = $encryptionService->encrypt($password);

echo "Plaintext:  $password\n";
echo "Encrypted:  $encrypted\n";
echo "\nSQL Update:\n";
echo "UPDATE imap_accounts SET imap_password_encrypted = '$encrypted' WHERE id = 1;\n";

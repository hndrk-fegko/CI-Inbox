<?php
/**
 * Debug Script: Test .env Loading in HTTP Context
 * 
 * Tests if .env file is properly loaded via ConfigService in Apache context
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use CiInbox\Modules\Config\ConfigService;

// Create ConfigService manually (without Container)
$basePath = __DIR__ . '/../../';
echo "Base Path: $basePath\n";

$config = new ConfigService($basePath);

// Try to get ENCRYPTION_KEY
try {
    $encryptionKey = $config->getString('ENCRYPTION_KEY');
    echo "✅ SUCCESS: ENCRYPTION_KEY loaded\n";
    echo "Value: " . substr($encryptionKey, 0, 20) . "...\n";
} catch (Exception $e) {
    echo "❌ FAILED: " . $e->getMessage() . "\n";
    
    // Debug: Check if $_ENV contains the key
    echo "\n--- DEBUG INFO ---\n";
    echo ".env file exists: " . (file_exists($basePath . '.env') ? 'YES' : 'NO') . "\n";
    echo "\$_ENV['ENCRYPTION_KEY']: " . ($_ENV['ENCRYPTION_KEY'] ?? 'NOT SET') . "\n";
    echo "\$_SERVER['ENCRYPTION_KEY']: " . ($_SERVER['ENCRYPTION_KEY'] ?? 'NOT SET') . "\n";
    echo "getenv('ENCRYPTION_KEY'): " . (getenv('ENCRYPTION_KEY') ?: 'NOT SET') . "\n";
}

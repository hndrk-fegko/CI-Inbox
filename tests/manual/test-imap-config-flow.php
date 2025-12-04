<?php
/**
 * Manual Test: Complete IMAP Configuration Flow
 * 
 * Tests:
 * 1. Autodiscover IMAP settings
 * 2. Get current IMAP config
 * 3. Update IMAP config
 * 4. Verify encryption
 * 
 * Usage: php tests/manual/test-imap-config-flow.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use CiInbox\Modules\Config\ConfigService;
use CiInbox\Modules\Logger\LoggerService;

// Initialize system
$config = new ConfigService(__DIR__ . '/../../');
require_once __DIR__ . '/../../src/bootstrap/database.php';
initDatabase($config);

$logger = new LoggerService(__DIR__ . '/../../logs/');

echo "=== IMAP Configuration Flow Test ===" . PHP_EOL . PHP_EOL;

$baseUrl = 'http://ci-inbox.local/api/admin/settings';

// ============================================================================
// TEST 1: Autodiscover IMAP Settings
// ============================================================================
echo "TEST 1: Autodiscover IMAP Settings" . PHP_EOL;
try {
    $ch = curl_init("{$baseUrl}/imap/autodiscover");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => 'test@gmail.com']));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        if ($result['success']) {
            echo "✅ Autodiscover successful" . PHP_EOL;
            echo "   Host: {$result['config']['host']}" . PHP_EOL;
            echo "   Port: {$result['config']['port']}" . PHP_EOL;
            echo "   SSL: " . ($result['config']['ssl'] ? 'Yes' : 'No') . PHP_EOL;
            $discoveredConfig = $result['config'];
        } else {
            echo "❌ Autodiscover failed: {$result['error']}" . PHP_EOL;
            exit(1);
        }
    } else {
        echo "❌ HTTP Error: {$httpCode}" . PHP_EOL;
        echo "   Response: {$response}" . PHP_EOL;
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Exception: {$e->getMessage()}" . PHP_EOL;
    exit(1);
}

echo PHP_EOL;

// ============================================================================
// TEST 2: Get Current IMAP Config (should be empty/unconfigured)
// ============================================================================
echo "TEST 2: Get Current IMAP Config" . PHP_EOL;
try {
    $ch = curl_init("{$baseUrl}/imap");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        if ($result['success']) {
            echo "✅ Retrieved IMAP config" . PHP_EOL;
            echo "   Configured: " . ($result['data']['configured'] ? 'Yes' : 'No') . PHP_EOL;
            echo "   Host: " . ($result['data']['host'] ?: '(empty)') . PHP_EOL;
        } else {
            echo "❌ Failed: {$result['error']}" . PHP_EOL;
            exit(1);
        }
    } else {
        echo "❌ HTTP Error: {$httpCode}" . PHP_EOL;
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Exception: {$e->getMessage()}" . PHP_EOL;
    exit(1);
}

echo PHP_EOL;

// ============================================================================
// TEST 3: Update IMAP Config (with discovered settings)
// ============================================================================
echo "TEST 3: Update IMAP Config" . PHP_EOL;
try {
    $updateData = [
        'host' => $discoveredConfig['host'],
        'port' => $discoveredConfig['port'],
        'ssl' => $discoveredConfig['ssl'],
        'username' => 'test@gmail.com',
        'password' => 'test-password-123',
        'inbox_folder' => 'INBOX'
    ];
    
    $ch = curl_init("{$baseUrl}/imap");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updateData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        if ($result['success']) {
            echo "✅ IMAP config updated successfully" . PHP_EOL;
            echo "   Message: {$result['message']}" . PHP_EOL;
        } else {
            echo "❌ Update failed: {$result['error']}" . PHP_EOL;
            exit(1);
        }
    } else {
        echo "❌ HTTP Error: {$httpCode}" . PHP_EOL;
        echo "   Response: {$response}" . PHP_EOL;
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Exception: {$e->getMessage()}" . PHP_EOL;
    exit(1);
}

echo PHP_EOL;

// ============================================================================
// TEST 4: Verify Config Saved (should now be configured)
// ============================================================================
echo "TEST 4: Verify Config Saved" . PHP_EOL;
try {
    $ch = curl_init("{$baseUrl}/imap");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        if ($result['success']) {
            $data = $result['data'];
            echo "✅ Config verification successful" . PHP_EOL;
            echo "   Configured: " . ($data['configured'] ? 'Yes' : 'No') . PHP_EOL;
            echo "   Host: {$data['host']}" . PHP_EOL;
            echo "   Port: {$data['port']}" . PHP_EOL;
            echo "   Username: {$data['username']}" . PHP_EOL;
            echo "   Password: {$data['password']} (should be masked)" . PHP_EOL;
            
            if ($data['configured'] === false) {
                echo "❌ Config not marked as configured!" . PHP_EOL;
                exit(1);
            }
            if ($data['password'] !== '********') {
                echo "❌ Password not masked in response!" . PHP_EOL;
                exit(1);
            }
        } else {
            echo "❌ Failed: {$result['error']}" . PHP_EOL;
            exit(1);
        }
    } else {
        echo "❌ HTTP Error: {$httpCode}" . PHP_EOL;
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Exception: {$e->getMessage()}" . PHP_EOL;
    exit(1);
}

echo PHP_EOL;

// ============================================================================
// TEST 5: Verify Password Encryption in Database
// ============================================================================
echo "TEST 5: Verify Password Encryption in Database" . PHP_EOL;
try {
    $stmt = \Illuminate\Database\Capsule\Manager::table('system_settings')
        ->where('setting_key', 'imap.password')
        ->first();
    
    if ($stmt) {
        echo "✅ Password found in database" . PHP_EOL;
        echo "   Is Encrypted: " . ($stmt->is_encrypted ? 'Yes' : 'No') . PHP_EOL;
        echo "   Value Length: " . strlen($stmt->setting_value) . " chars" . PHP_EOL;
        
        if (!$stmt->is_encrypted) {
            echo "❌ Password is not encrypted!" . PHP_EOL;
            exit(1);
        }
        
        // Verify it's not the plain password
        if ($stmt->setting_value === 'test-password-123') {
            echo "❌ Password stored in plain text!" . PHP_EOL;
            exit(1);
        }
        
        echo "✅ Password is properly encrypted" . PHP_EOL;
    } else {
        echo "❌ Password not found in database" . PHP_EOL;
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Exception: {$e->getMessage()}" . PHP_EOL;
    exit(1);
}

echo PHP_EOL;
echo "=== ALL TESTS PASSED ===" . PHP_EOL;

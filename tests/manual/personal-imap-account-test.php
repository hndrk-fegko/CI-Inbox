<?php
/**
 * Personal IMAP Account API Test
 * 
 * Tests: /api/user/imap-accounts endpoints
 * 
 * Usage: php tests/manual/personal-imap-account-test.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

$baseUrl = 'http://localhost:8000';
$testAccountId = null;

function apiRequest($method, $path, $data = null) {
    global $baseUrl;
    
    $url = $baseUrl . $path;
    $ch = curl_init($url);
    
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($data))
        ]);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

function printResult($testName, $result, $expectedStatus = 200) {
    $success = $result['status'] === $expectedStatus;
    $icon = $success ? '✓' : '✗';
    $color = $success ? "\033[32m" : "\033[31m";
    $reset = "\033[0m";
    
    echo "{$color}{$icon}{$reset} {$testName}\n";
    echo "   Status: {$result['status']} (expected: {$expectedStatus})\n";
    
    if (!empty($result['body'])) {
        echo "   Response: " . json_encode($result['body'], JSON_PRETTY_PRINT) . "\n";
    }
    
    echo "\n";
    
    return $success;
}

echo "=== Personal IMAP Account API Test ===\n\n";

$passed = 0;
$failed = 0;

// Test 1: Create personal IMAP account
echo "Test 1: Create Personal IMAP Account\n";
$result = apiRequest('POST', '/api/user/imap-accounts', [
    'email' => 'test@gmail.com',
    'password' => 'test-password-123',
    'imap_host' => 'imap.gmail.com',
    'imap_port' => 993,
    'imap_username' => 'test@gmail.com',
    'imap_encryption' => 'ssl',
    'is_default' => false,
    'is_active' => true
]);
if (printResult('Create personal account', $result, 201)) {
    $passed++;
    $testAccountId = $result['body']['data']['id'] ?? null;
} else {
    $failed++;
}

// Test 2: List personal accounts
echo "Test 2: List Personal IMAP Accounts\n";
$result = apiRequest('GET', '/api/user/imap-accounts');
if (printResult('List personal accounts', $result, 200)) {
    $passed++;
} else {
    $failed++;
}

// Test 3: Get single account
if ($testAccountId) {
    echo "Test 3: Get Single Personal Account\n";
    $result = apiRequest('GET', "/api/user/imap-accounts/{$testAccountId}");
    if (printResult('Get account by ID', $result, 200)) {
        $passed++;
    } else {
        $failed++;
    }
}

// Test 4: Update account
if ($testAccountId) {
    echo "Test 4: Update Personal Account\n";
    $result = apiRequest('PUT', "/api/user/imap-accounts/{$testAccountId}", [
        'imap_host' => 'imap.gmail.com',
        'imap_port' => 993,
        'is_active' => true
    ]);
    if (printResult('Update account', $result, 200)) {
        $passed++;
    } else {
        $failed++;
    }
}

// Test 5: Test connection (will fail without real credentials)
if ($testAccountId) {
    echo "Test 5: Test IMAP Connection\n";
    $result = apiRequest('POST', "/api/user/imap-accounts/{$testAccountId}/test-connection");
    // Expected to fail (400) because test credentials are invalid
    if (printResult('Test connection', $result, 400)) {
        $passed++;
    } else {
        $failed++;
    }
}

// Test 6: Create duplicate (should fail)
echo "Test 6: Create Duplicate Account (should fail)\n";
$result = apiRequest('POST', '/api/user/imap-accounts', [
    'email' => 'test@gmail.com',
    'password' => 'different-password',
    'imap_host' => 'imap.gmail.com',
    'imap_port' => 993
]);
if (printResult('Create duplicate (expected error)', $result, 400)) {
    $passed++;
} else {
    $failed++;
}

// Test 7: Create with invalid email (should fail)
echo "Test 7: Create with Invalid Email (should fail)\n";
$result = apiRequest('POST', '/api/user/imap-accounts', [
    'email' => 'not-an-email',
    'password' => 'password123',
    'imap_host' => 'imap.example.com',
    'imap_port' => 993
]);
if (printResult('Invalid email (expected error)', $result, 400)) {
    $passed++;
} else {
    $failed++;
}

// Test 8: Create without password (should fail)
echo "Test 8: Create without Password (should fail)\n";
$result = apiRequest('POST', '/api/user/imap-accounts', [
    'email' => 'test2@gmail.com',
    'imap_host' => 'imap.gmail.com',
    'imap_port' => 993
]);
if (printResult('Missing password (expected error)', $result, 400)) {
    $passed++;
} else {
    $failed++;
}

// Test 9: Delete account
if ($testAccountId) {
    echo "Test 9: Delete Personal Account\n";
    $result = apiRequest('DELETE', "/api/user/imap-accounts/{$testAccountId}");
    if (printResult('Delete account', $result, 200)) {
        $passed++;
    } else {
        $failed++;
    }
}

// Test 10: Get deleted account (should fail)
if ($testAccountId) {
    echo "Test 10: Get Deleted Account (should fail)\n";
    $result = apiRequest('GET', "/api/user/imap-accounts/{$testAccountId}");
    if (printResult('Get deleted account (expected 404)', $result, 404)) {
        $passed++;
    } else {
        $failed++;
    }
}

// Summary
echo "\n=== Test Summary ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "Total: " . ($passed + $failed) . "\n";

if ($failed === 0) {
    echo "\n✓ All tests passed!\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed!\n";
    exit(1);
}

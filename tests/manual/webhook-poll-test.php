<?php
/**
 * Webhook Poll-Emails Test
 * 
 * Tests: POST /webhooks/poll-emails endpoint
 * 
 * Usage: php tests/manual/webhook-poll-test.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

$baseUrl = 'http://localhost:8000';
$secretToken = getenv('WEBCRON_SECRET_TOKEN') ?: 'your-secret-token-here';

function apiRequest($method, $path, $headers = [], $data = null) {
    global $baseUrl;
    
    $url = $baseUrl . $path;
    $ch = curl_init($url);
    
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    
    if (!empty($headers)) {
        $curlHeaders = [];
        foreach ($headers as $key => $value) {
            $curlHeaders[] = "{$key}: {$value}";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
    }
    
    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
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

echo "=== Webhook Poll-Emails Test ===\n\n";
echo "Secret Token: " . substr($secretToken, 0, 10) . "***\n\n";

$passed = 0;
$failed = 0;

// Test 1: Call webhook without authentication (should fail)
echo "Test 1: Call without authentication (should fail)\n";
$result = apiRequest('POST', '/webhooks/poll-emails');
if (printResult('No auth (expected 401)', $result, 401)) {
    $passed++;
} else {
    $failed++;
}

// Test 2: Call webhook with invalid token (should fail)
echo "Test 2: Call with invalid token (should fail)\n";
$result = apiRequest('POST', '/webhooks/poll-emails', [
    'X-Webhook-Token' => 'invalid-token-123'
]);
if (printResult('Invalid token (expected 401)', $result, 401)) {
    $passed++;
} else {
    $failed++;
}

// Test 3: Call webhook with valid token via header
echo "Test 3: Call with valid token (X-Webhook-Token header)\n";
$result = apiRequest('POST', '/webhooks/poll-emails', [
    'X-Webhook-Token' => $secretToken
]);
if (printResult('Valid token via header', $result, 200)) {
    $passed++;
} else {
    $failed++;
}

// Test 4: Call webhook with valid token via Authorization header
echo "Test 4: Call with valid token (Authorization: Bearer)\n";
$result = apiRequest('POST', '/webhooks/poll-emails', [
    'Authorization' => "Bearer {$secretToken}"
]);
if (printResult('Valid token via Bearer', $result, 200)) {
    $passed++;
} else {
    $failed++;
}

// Test 5: Call webhook with token in request body
echo "Test 5: Call with token in request body\n";
$result = apiRequest('POST', '/webhooks/poll-emails', [
    'Content-Type' => 'application/json'
], [
    'token' => $secretToken
]);
if (printResult('Valid token via body', $result, 200)) {
    $passed++;
} else {
    $failed++;
}

// Test 6: Call webhook with token in query parameter
echo "Test 6: Call with token in query parameter\n";
$result = apiRequest('POST', "/webhooks/poll-emails?token={$secretToken}");
if (printResult('Valid token via query', $result, 200)) {
    $passed++;
} else {
    $failed++;
}

// Summary
echo "\n=== Test Summary ===\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";
echo "Total: " . ($passed + $failed) . "\n";

if ($failed === 0) {
    echo "\n✓ All tests passed!\n";
    echo "\nWebhook URL for external cron services:\n";
    echo "{$baseUrl}/webhooks/poll-emails\n";
    echo "\nAuthentication methods:\n";
    echo "1. Header: X-Webhook-Token: {$secretToken}\n";
    echo "2. Header: Authorization: Bearer {$secretToken}\n";
    echo "3. Body: {\"token\": \"{$secretToken}\"}\n";
    echo "4. Query: ?token={$secretToken}\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed!\n";
    exit(1);
}

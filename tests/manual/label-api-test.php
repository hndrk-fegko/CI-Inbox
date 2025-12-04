<?php
/**
 * Label Management API Test
 * 
 * Tests the Label CRUD API endpoints.
 */

// Colors for output
define('COLOR_GREEN', "\033[32m");
define('COLOR_RED', "\033[31m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_BLUE', "\033[34m");
define('COLOR_RESET', "\033[0m");

// ============================================================
// Configuration
// ============================================================

$baseUrl = 'http://ci-inbox.local';

// ============================================================
// Helper Functions
// ============================================================

function output(string $message, string $color = COLOR_RESET): void
{
    echo $color . $message . COLOR_RESET . PHP_EOL;
}

function httpRequest(string $method, string $url, ?array $data = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ]
    ]);
    
    if ($data !== null && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $responseBody = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    return [
        'http_code' => $httpCode,
        'body' => $responseBody,
        'error' => $curlError,
        'data' => $responseBody ? json_decode($responseBody, true) : null
    ];
}

// ============================================================
// Main Test Script
// ============================================================

output("", COLOR_BLUE);
output("╔════════════════════════════════════════════════════════════╗", COLOR_BLUE);
output("║       CI-Inbox: Label Management API Test                 ║", COLOR_BLUE);
output("╚════════════════════════════════════════════════════════════╝", COLOR_BLUE);
output("");

$createdLabelIds = [];

// ============================================================
// TEST 1: Create Label
// ============================================================
output("TEST 1: Create Label", COLOR_YELLOW);
output("-----------------------------------------------------------");

$result = httpRequest('POST', "{$baseUrl}/api/labels", [
    'name' => 'Test Label',
    'color' => '#FF5733',
    'display_order' => 10
]);

if ($result['http_code'] === 201 && $result['data']['success']) {
    output("✓ HTTP 201 Created", COLOR_GREEN);
    $createdLabelIds[] = $result['data']['label_id'];
    output("  Label ID: {$result['data']['label_id']}", COLOR_GREEN);
} else {
    output("✗ HTTP {$result['http_code']}", COLOR_RED);
    output("Response: {$result['body']}", COLOR_RED);
}

output("");

// ============================================================
// TEST 2: Get All Labels
// ============================================================
output("TEST 2: Get All Labels", COLOR_YELLOW);
output("-----------------------------------------------------------");

$result = httpRequest('GET', "{$baseUrl}/api/labels");

if ($result['http_code'] === 200 && isset($result['data']['labels'])) {
    output("✓ HTTP 200 OK", COLOR_GREEN);
    output("  Total labels: {$result['data']['total']}", COLOR_GREEN);
    
    foreach ($result['data']['labels'] as $label) {
        output("  - {$label['name']} (ID: {$label['id']})", COLOR_BLUE);
    }
} else {
    output("✗ HTTP {$result['http_code']}", COLOR_RED);
    output("Response: {$result['body']}", COLOR_RED);
}

output("");

// ============================================================
// TEST 3: Get Single Label
// ============================================================
if (!empty($createdLabelIds)) {
    output("TEST 3: Get Single Label", COLOR_YELLOW);
    output("-----------------------------------------------------------");
    
    $labelId = $createdLabelIds[0];
    $result = httpRequest('GET', "{$baseUrl}/api/labels/{$labelId}");
    
    if ($result['http_code'] === 200 && isset($result['data']['label'])) {
        output("✓ HTTP 200 OK", COLOR_GREEN);
        output("  Name: {$result['data']['label']['name']}", COLOR_GREEN);
        output("  Color: {$result['data']['label']['color']}", COLOR_GREEN);
    } else {
        output("✗ HTTP {$result['http_code']}", COLOR_RED);
        output("Response: {$result['body']}", COLOR_RED);
    }
    
    output("");
}

// ============================================================
// TEST 4: Update Label
// ============================================================
if (!empty($createdLabelIds)) {
    output("TEST 4: Update Label", COLOR_YELLOW);
    output("-----------------------------------------------------------");
    
    $labelId = $createdLabelIds[0];
    $result = httpRequest('PUT', "{$baseUrl}/api/labels/{$labelId}", [
        'name' => 'Updated Test Label',
        'color' => '#00FF00'
    ]);
    
    if ($result['http_code'] === 200 && $result['data']['success']) {
        output("✓ HTTP 200 OK", COLOR_GREEN);
        output("  {$result['data']['message']}", COLOR_GREEN);
    } else {
        output("✗ HTTP {$result['http_code']}", COLOR_RED);
        output("Response: {$result['body']}", COLOR_RED);
    }
    
    output("");
}

// ============================================================
// TEST 5: Get Label Statistics
// ============================================================
output("TEST 5: Get Label Statistics", COLOR_YELLOW);
output("-----------------------------------------------------------");

$result = httpRequest('GET', "{$baseUrl}/api/labels/stats");

if ($result['http_code'] === 200 && isset($result['data']['statistics'])) {
    output("✓ HTTP 200 OK", COLOR_GREEN);
    
    foreach ($result['data']['statistics'] as $stat) {
        output("  - {$stat['label_name']}: {$stat['thread_count']} threads", COLOR_BLUE);
    }
} else {
    output("✗ HTTP {$result['http_code']}", COLOR_RED);
    output("Response: {$result['body']}", COLOR_RED);
}

output("");

// ============================================================
// TEST 6: Validation Test (Missing name)
// ============================================================
output("TEST 6: Validation Test (Missing name)", COLOR_YELLOW);
output("-----------------------------------------------------------");

$result = httpRequest('POST', "{$baseUrl}/api/labels", [
    'color' => '#FF5733'
    // Missing 'name'
]);

if ($result['http_code'] === 400) {
    output("✓ HTTP 400 Bad Request (Expected)", COLOR_GREEN);
    output("  Error: {$result['data']['error']}", COLOR_BLUE);
} else {
    output("✗ Expected 400, got {$result['http_code']}", COLOR_RED);
}

output("");

// ============================================================
// TEST 7: Validation Test (Invalid color format)
// ============================================================
output("TEST 7: Validation Test (Invalid color)", COLOR_YELLOW);
output("-----------------------------------------------------------");

$result = httpRequest('POST', "{$baseUrl}/api/labels", [
    'name' => 'Invalid Color Label',
    'color' => 'red' // Invalid format
]);

if ($result['http_code'] === 400) {
    output("✓ HTTP 400 Bad Request (Expected)", COLOR_GREEN);
    output("  Error: {$result['data']['error']}", COLOR_BLUE);
} else {
    output("✗ Expected 400, got {$result['http_code']}", COLOR_RED);
}

output("");

// ============================================================
// TEST 8: Delete Label
// ============================================================
if (!empty($createdLabelIds)) {
    output("TEST 8: Delete Label", COLOR_YELLOW);
    output("-----------------------------------------------------------");
    
    foreach ($createdLabelIds as $labelId) {
        $result = httpRequest('DELETE', "{$baseUrl}/api/labels/{$labelId}");
        
        if ($result['http_code'] === 200 && $result['data']['success']) {
            output("✓ Label {$labelId} deleted", COLOR_GREEN);
        } else {
            output("✗ Failed to delete label {$labelId}", COLOR_RED);
        }
    }
    
    output("");
}

// ============================================================
// TEST 9: Get Non-Existent Label (404)
// ============================================================
output("TEST 9: Get Non-Existent Label", COLOR_YELLOW);
output("-----------------------------------------------------------");

$result = httpRequest('GET', "{$baseUrl}/api/labels/99999");

if ($result['http_code'] === 404) {
    output("✓ HTTP 404 Not Found (Expected)", COLOR_GREEN);
} else {
    output("✗ Expected 404, got {$result['http_code']}", COLOR_RED);
}

output("");

// ============================================================
// Summary
// ============================================================
output("╔════════════════════════════════════════════════════════════╗", COLOR_BLUE);
output("║                    Test Complete                          ║", COLOR_BLUE);
output("╚════════════════════════════════════════════════════════════╝", COLOR_BLUE);
output("");

output("Label Management API Endpoints Tested:", COLOR_GREEN);
output("  ✓ POST /api/labels - Create label");
output("  ✓ GET /api/labels - List all labels");
output("  ✓ GET /api/labels/{id} - Get single label");
output("  ✓ PUT /api/labels/{id} - Update label");
output("  ✓ DELETE /api/labels/{id} - Delete label");
output("  ✓ GET /api/labels/stats - Get statistics");
output("  ✓ Validation tests");
output("");

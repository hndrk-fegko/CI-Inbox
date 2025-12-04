<?php
/**
 * Thread Bulk Operations Test
 * 
 * Tests the new bulk operations API endpoints.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use CiInbox\Core\Container;
use CiInbox\Modules\Logger\LoggerInterface;

// ============================================================
// Configuration
// ============================================================

$baseUrl = 'http://ci-inbox.local';

// Colors for output
define('COLOR_GREEN', "\033[32m");
define('COLOR_RED', "\033[31m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_BLUE', "\033[34m");
define('COLOR_RESET', "\033[0m");

// ============================================================
// Helper Functions
// ============================================================

function output(string $message, string $color = COLOR_RESET): void
{
    echo $color . $message . COLOR_RESET . PHP_EOL;
}

function httpPost(string $url, array $data): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_TIMEOUT => 60,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ]
    ]);
    
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
output("║       CI-Inbox: Thread Bulk Operations Test               ║", COLOR_BLUE);
output("╚════════════════════════════════════════════════════════════╝", COLOR_BLUE);
output("");

// Initialize container
$container = Container::getInstance();
$container->get('database');
$logger = $container->get(LoggerInterface::class);

$logger->info('=== Bulk Operations Test Started ===');

// ============================================================
// SETUP: Create Test Threads
// ============================================================
output("SETUP: Creating test threads", COLOR_YELLOW);
output("-----------------------------------------------------------");

$testThreadIds = [];

for ($i = 1; $i <= 5; $i++) {
    $result = httpPost("{$baseUrl}/api/threads", [
        'subject' => "Test Thread {$i} for Bulk Operations",
        'status' => 'open'
    ]);
    
    if ($result['http_code'] === 201 && isset($result['data']['thread']['id'])) {
        $testThreadIds[] = $result['data']['thread']['id'];
        output("✓ Created thread {$i} (ID: {$result['data']['thread']['id']})", COLOR_GREEN);
    } else {
        output("✗ Failed to create thread {$i}", COLOR_RED);
    }
}

output("");
output("Test thread IDs: " . implode(', ', $testThreadIds), COLOR_BLUE);
output("");

if (count($testThreadIds) < 3) {
    output("✗ Not enough test threads created. Aborting.", COLOR_RED);
    exit(1);
}

// ============================================================
// TEST 1: Bulk Update Status
// ============================================================
output("TEST 1: Bulk Update Status (3 threads → closed)", COLOR_YELLOW);
output("-----------------------------------------------------------");

$threadsToUpdate = array_slice($testThreadIds, 0, 3);
$result = httpPost("{$baseUrl}/api/threads/bulk/status", [
    'thread_ids' => $threadsToUpdate,
    'status' => 'closed'
]);

if ($result['http_code'] === 200 && $result['data']['success']) {
    output("✓ HTTP 200 OK", COLOR_GREEN);
    output("  Updated: {$result['data']['result']['updated']}", COLOR_GREEN);
    output("  Failed: {$result['data']['result']['failed']}", COLOR_BLUE);
    
    if ($result['data']['result']['updated'] === 3) {
        output("✓ All 3 threads updated successfully", COLOR_GREEN);
    } else {
        output("⚠ Expected 3 updates, got {$result['data']['result']['updated']}", COLOR_YELLOW);
    }
} else {
    output("✗ HTTP {$result['http_code']}", COLOR_RED);
    output("Response: {$result['body']}", COLOR_RED);
}

output("");

// ============================================================
// TEST 2: Bulk Assign
// ============================================================
output("TEST 2: Bulk Assign (3 threads → user_id 1)", COLOR_YELLOW);
output("-----------------------------------------------------------");

$result = httpPost("{$baseUrl}/api/threads/bulk/assign", [
    'thread_ids' => $threadsToUpdate,
    'user_id' => 1
]);

if ($result['http_code'] === 200 && $result['data']['success']) {
    output("✓ HTTP 200 OK", COLOR_GREEN);
    output("  Assigned: {$result['data']['result']['updated']}", COLOR_GREEN);
    output("  Failed: {$result['data']['result']['failed']}", COLOR_BLUE);
} else {
    output("✗ HTTP {$result['http_code']}", COLOR_RED);
    output("Response: {$result['body']}", COLOR_RED);
}

output("");

// ============================================================
// TEST 3: Bulk Update (Multiple Fields)
// ============================================================
output("TEST 3: Bulk Update (Multiple fields)", COLOR_YELLOW);
output("-----------------------------------------------------------");

$result = httpPost("{$baseUrl}/api/threads/bulk/update", [
    'thread_ids' => array_slice($testThreadIds, 3, 2),
    'updates' => [
        'status' => 'archived',
        'assigned_to' => 1
    ]
]);

if ($result['http_code'] === 200 && $result['data']['success']) {
    output("✓ HTTP 200 OK", COLOR_GREEN);
    output("  Updated: {$result['data']['result']['updated']}", COLOR_GREEN);
    output("  Failed: {$result['data']['result']['failed']}", COLOR_BLUE);
} else {
    output("✗ HTTP {$result['http_code']}", COLOR_RED);
    output("Response: {$result['body']}", COLOR_RED);
}

output("");

// ============================================================
// TEST 4: Bulk Add Label (Create label first)
// ============================================================
output("TEST 4: Bulk Add Label", COLOR_YELLOW);
output("-----------------------------------------------------------");

// Note: Assuming label ID 1 exists, or create one manually
$labelId = 1;

$result = httpPost("{$baseUrl}/api/threads/bulk/labels/add", [
    'thread_ids' => array_slice($testThreadIds, 0, 3),
    'label_id' => $labelId
]);

if ($result['http_code'] === 200 && $result['data']['success']) {
    output("✓ HTTP 200 OK", COLOR_GREEN);
    output("  Added: {$result['data']['result']['added']}", COLOR_GREEN);
    output("  Failed: {$result['data']['result']['failed']}", COLOR_BLUE);
} else {
    output("✗ HTTP {$result['http_code']}", COLOR_RED);
    output("Response: {$result['body']}", COLOR_RED);
}

output("");

// ============================================================
// TEST 5: Bulk Delete
// ============================================================
output("TEST 5: Bulk Delete (2 threads)", COLOR_YELLOW);
output("-----------------------------------------------------------");

$threadsToDelete = array_slice($testThreadIds, -2);
$result = httpPost("{$baseUrl}/api/threads/bulk/delete", [
    'thread_ids' => $threadsToDelete
]);

if ($result['http_code'] === 200 && $result['data']['success']) {
    output("✓ HTTP 200 OK", COLOR_GREEN);
    output("  Deleted: {$result['data']['result']['deleted']}", COLOR_GREEN);
    output("  Failed: {$result['data']['result']['failed']}", COLOR_BLUE);
    
    if ($result['data']['result']['deleted'] === 2) {
        output("✓ Both threads deleted successfully", COLOR_GREEN);
    }
} else {
    output("✗ HTTP {$result['http_code']}", COLOR_RED);
    output("Response: {$result['body']}", COLOR_RED);
}

output("");

// ============================================================
// TEST 6: Validation Test (Missing thread_ids)
// ============================================================
output("TEST 6: Validation Test (Missing thread_ids)", COLOR_YELLOW);
output("-----------------------------------------------------------");

$result = httpPost("{$baseUrl}/api/threads/bulk/update", [
    'updates' => ['status' => 'closed']
    // Missing thread_ids
]);

if ($result['http_code'] === 400 && isset($result['data']['error'])) {
    output("✓ HTTP 400 Bad Request (Expected)", COLOR_GREEN);
    output("  Error: {$result['data']['error']}", COLOR_BLUE);
} else {
    output("✗ Expected 400, got {$result['http_code']}", COLOR_RED);
}

output("");

// ============================================================
// CLEANUP: Delete remaining test threads
// ============================================================
output("CLEANUP: Deleting remaining test threads", COLOR_YELLOW);
output("-----------------------------------------------------------");

$remainingThreads = array_slice($testThreadIds, 0, -2);

if (!empty($remainingThreads)) {
    $result = httpPost("{$baseUrl}/api/threads/bulk/delete", [
        'thread_ids' => $remainingThreads
    ]);
    
    if ($result['http_code'] === 200) {
        output("✓ Cleaned up {$result['data']['result']['deleted']} test threads", COLOR_GREEN);
    }
}

output("");

// ============================================================
// Summary
// ============================================================
output("╔════════════════════════════════════════════════════════════╗", COLOR_BLUE);
output("║                    Test Complete                          ║", COLOR_BLUE);
output("╚════════════════════════════════════════════════════════════╝", COLOR_BLUE);
output("");

output("Bulk Operations Tested:", COLOR_GREEN);
output("  ✓ Bulk Update Status");
output("  ✓ Bulk Assign");
output("  ✓ Bulk Update (Multiple fields)");
output("  ✓ Bulk Add Label");
output("  ✓ Bulk Delete");
output("  ✓ Validation");
output("");

output("API Endpoints:", COLOR_BLUE);
output("  POST /api/threads/bulk/update");
output("  POST /api/threads/bulk/delete");
output("  POST /api/threads/bulk/assign");
output("  POST /api/threads/bulk/status");
output("  POST /api/threads/bulk/labels/add");
output("  POST /api/threads/bulk/labels/remove");
output("");

$logger->info('=== Bulk Operations Test Completed ===');

<?php
/**
 * Webcron Polling Test
 * 
 * Tests the webcron polling endpoint manually.
 * Simulates external cron service calling /webcron/poll
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use CiInbox\Core\Container;
use CiInbox\Modules\Logger\LoggerInterface;

// ============================================================
// Configuration
// ============================================================

$baseUrl = 'http://ci-inbox.local';
$apiKey = 'dev-secret-key-12345'; // From webcron.config.php

// Colors for output
define('COLOR_GREEN', "\033[32m");
define('COLOR_RED', "\033[31m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_BLUE', "\033[34m");
define('COLOR_RESET', "\033[0m");

// ============================================================
// Test Functions
// ============================================================

function output(string $message, string $color = COLOR_RESET): void
{
    echo $color . $message . COLOR_RESET . PHP_EOL;
}

function httpGet(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_HTTPHEADER => [
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
output("║          CI-Inbox: Webcron Polling Test                   ║", COLOR_BLUE);
output("╚════════════════════════════════════════════════════════════╝", COLOR_BLUE);
output("");

// Initialize container for database access
$container = Container::getInstance();
$container->get('database');
$logger = $container->get(LoggerInterface::class);

$logger->info('=== Webcron Polling Test Started ===');

// ============================================================
// TEST 1: Get Webcron Status (No Auth)
// ============================================================
output("TEST 1: Get Webcron Status", COLOR_YELLOW);
output("-----------------------------------------------------------");

$statusUrl = "{$baseUrl}/webcron/status";
output("GET {$statusUrl}");

$result = httpGet($statusUrl);

if ($result['error']) {
    output("✗ FAILED: cURL Error - {$result['error']}", COLOR_RED);
    exit(1);
}

if ($result['http_code'] === 200) {
    output("✓ HTTP 200 OK", COLOR_GREEN);
    
    if ($result['data']) {
        output("\nStatus Data:", COLOR_BLUE);
        echo json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
} else {
    output("✗ HTTP {$result['http_code']}", COLOR_RED);
    output("Response: {$result['body']}");
}

output("");

// ============================================================
// TEST 2: Poll Without API Key (Should Fail)
// ============================================================
output("TEST 2: Poll Without API Key (Should Fail)", COLOR_YELLOW);
output("-----------------------------------------------------------");

$pollUrl = "{$baseUrl}/webcron/poll";
output("GET {$pollUrl}");

$result = httpGet($pollUrl);

if ($result['http_code'] === 401) {
    output("✓ HTTP 401 Unauthorized (Expected)", COLOR_GREEN);
    
    if ($result['data'] && isset($result['data']['error'])) {
        output("  Error: {$result['data']['error']}", COLOR_BLUE);
    }
} else {
    output("✗ Expected 401, got {$result['http_code']}", COLOR_RED);
}

output("");

// ============================================================
// TEST 3: Poll With Valid API Key
// ============================================================
output("TEST 3: Poll With Valid API Key (Full Sync)", COLOR_YELLOW);
output("-----------------------------------------------------------");

$pollUrl = "{$baseUrl}/webcron/poll?api_key={$apiKey}";
output("GET {$pollUrl}");
output("(This may take 10-60 seconds depending on email count...)", COLOR_BLUE);

$startTime = microtime(true);
$result = httpGet($pollUrl);
$duration = microtime(true) - $startTime;

if ($result['error']) {
    output("✗ FAILED: cURL Error - {$result['error']}", COLOR_RED);
    exit(1);
}

if ($result['http_code'] === 200) {
    output("✓ HTTP 200 OK", COLOR_GREEN);
    output("  Duration: " . round($duration, 2) . " seconds", COLOR_BLUE);
    
    if ($result['data']) {
        output("\nPoll Result:", COLOR_BLUE);
        echo json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
        
        if (isset($result['data']['result'])) {
            $pollResult = $result['data']['result'];
            
            output("\nSummary:", COLOR_GREEN);
            output("  Accounts Processed: " . ($pollResult['accounts_processed'] ?? 0));
            output("  Emails Fetched: " . ($pollResult['emails_fetched'] ?? 0));
            output("  Errors: " . count($pollResult['errors'] ?? []));
            
            if (!empty($pollResult['errors'])) {
                output("\nErrors:", COLOR_RED);
                foreach ($pollResult['errors'] as $error) {
                    output("  - Account {$error['account_id']} ({$error['email']}): {$error['error']}");
                }
            }
        }
    }
} else {
    output("✗ HTTP {$result['http_code']}", COLOR_RED);
    output("Response: {$result['body']}");
}

output("");

// ============================================================
// TEST 4: Poll Single Account (If Available)
// ============================================================
output("TEST 4: Poll Single Account (Optional)", COLOR_YELLOW);
output("-----------------------------------------------------------");

// Get first active account
use CiInbox\App\Repositories\ImapAccountRepository;
$accountRepo = $container->get(ImapAccountRepository::class);
$accounts = $accountRepo->getActiveAccounts();

if (!empty($accounts)) {
    $accountId = $accounts[0]->id;
    $email = $accounts[0]->email;
    
    output("Polling account: {$email} (ID: {$accountId})", COLOR_BLUE);
    
    $pollUrl = "{$baseUrl}/webcron/poll?api_key={$apiKey}&account_id={$accountId}";
    output("GET {$pollUrl}");
    
    $startTime = microtime(true);
    $result = httpGet($pollUrl);
    $duration = microtime(true) - $startTime;
    
    if ($result['http_code'] === 200) {
        output("✓ HTTP 200 OK", COLOR_GREEN);
        output("  Duration: " . round($duration, 2) . " seconds", COLOR_BLUE);
        
        if ($result['data']) {
            output("\nSingle Account Result:", COLOR_BLUE);
            echo json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
        }
    } else {
        output("✗ HTTP {$result['http_code']}", COLOR_RED);
        output("Response: {$result['body']}");
    }
} else {
    output("⊘ No active accounts found - Skipping", COLOR_YELLOW);
}

output("");

// ============================================================
// Final Summary
// ============================================================
output("╔════════════════════════════════════════════════════════════╗", COLOR_BLUE);
output("║                    Test Complete                          ║", COLOR_BLUE);
output("╚════════════════════════════════════════════════════════════╝", COLOR_BLUE);
output("");

output("Next Steps:", COLOR_GREEN);
output("  1. Check logs: logs/app.log");
output("  2. Verify emails in database");
output("  3. Setup external cron service (e.g., cron-job.org)");
output("  4. Configure cron to call: {$baseUrl}/webcron/poll?api_key={$apiKey}");
output("");

$logger->info('=== Webcron Polling Test Completed ===');

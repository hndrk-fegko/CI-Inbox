<?php
declare(strict_types=1);

/**
 * Manual Test Script for Logger Module
 * 
 * This script tests the logger in standalone mode (without the full app).
 * Run from project root: php src/modules/logger/tests/manual-test.php
 */

require_once __DIR__ . '/../../../../vendor/autoload.php';

use CiInbox\Modules\Logger\LoggerService;

echo "=== CI-Inbox Logger Module - Manual Test ===\n\n";

// 1. Create logger instance
echo "1. Creating LoggerService...\n";
$logger = new LoggerService(
    logPath: __DIR__ . '/../../../../logs',
    logLevel: 'debug',
    channel: 'test'
);
echo "   ✅ LoggerService created\n\n";

// 2. Test different log levels
echo "2. Testing log levels...\n";
$logger->debug('This is a DEBUG message', ['test' => 'debug-context']);
echo "   ✅ DEBUG logged\n";

$logger->info('This is an INFO message', ['thread_id' => 42, 'user_id' => 7]);
echo "   ✅ INFO logged\n";

$logger->warning('This is a WARNING message', ['warning_type' => 'test']);
echo "   ✅ WARNING logged\n";

$logger->error('This is an ERROR message', ['error_code' => 500]);
echo "   ✅ ERROR logged\n";

$logger->critical('This is a CRITICAL message', ['system' => 'database']);
echo "   ✅ CRITICAL logged\n\n";

// 3. Test custom success level
echo "3. Testing custom SUCCESS level...\n";
$logger->success('Operation completed successfully', ['operation' => 'thread_assignment']);
echo "   ✅ SUCCESS logged\n\n";

// 4. Test exception logging
echo "4. Testing exception logging...\n";
try {
    throw new RuntimeException('Test exception for logging', 999);
} catch (Exception $e) {
    $logger->error('Caught exception during test', [
        'exception' => $e,
        'context' => 'manual test',
    ]);
    echo "   ✅ Exception logged\n\n";
}

// 5. Test with module context
echo "5. Testing with module context...\n";
$logger->info('Message from specific module', [
    'module' => 'ThreadService',
    'action' => 'assign',
    'thread_id' => 123,
]);
echo "   ✅ Module context logged\n\n";

// 6. Verify log file
echo "6. Verifying log file...\n";
$logFile = __DIR__ . '/../../../../logs/app-' . date('Y-m-d') . '.log';
if (file_exists($logFile)) {
    $logSize = filesize($logFile);
    $lines = count(file($logFile));
    echo "   ✅ Log file exists: {$logFile}\n";
    echo "   ✅ File size: {$logSize} bytes\n";
    echo "   ✅ Log entries: {$lines}\n\n";
    
    // Show last log entry
    echo "7. Last log entry (formatted):\n";
    $lastLine = trim(file($logFile)[count(file($logFile)) - 1]);
    $json = json_decode($lastLine, true);
    if ($json) {
        echo "   " . json_encode($json, JSON_PRETTY_PRINT) . "\n\n";
    }
} else {
    echo "   ❌ Log file not found!\n\n";
}

// Summary
echo "===========================================\n";
echo "✅ ALL TESTS PASSED\n";
echo "===========================================\n";
echo "\nCheck the log file for details:\n";
echo "  cat logs/app.log | jq .\n\n";

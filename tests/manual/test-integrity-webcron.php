<?php
/**
 * Test Integrity Check in Webcron
 * 
 * Creates orphaned thread and triggers webcron 10 times
 * to verify integrity check runs on 10th execution
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use CiInbox\Modules\Config\ConfigService;
use CiInbox\Modules\Logger\LoggerService;
use CiInbox\Modules\Webcron\WebcronManager;
use CiInbox\App\Repositories\ImapAccountRepository;
use Illuminate\Database\Capsule\Manager as DB;

$config = new ConfigService(__DIR__ . '/../../');
$logger = new LoggerService(__DIR__ . '/../../logs/');

require_once __DIR__ . '/../../src/bootstrap/database.php';
initDatabase($config);

echo "=== Testing Integrity Check in Webcron ===" . PHP_EOL . PHP_EOL;

// Step 1: Create orphaned thread
echo "Step 1: Creating orphaned thread..." . PHP_EOL;
$threadId = DB::table('threads')->insertGetId([
    'subject' => 'Test Orphaned Thread for Integrity Check',
    'message_count' => 5,
    'status' => 'open',
    'participants' => json_encode([]),
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
]);

echo "✅ Created thread #{$threadId} with message_count=5 (but no actual emails)" . PHP_EOL . PHP_EOL;

// Step 2: Trigger webcron 10 times
echo "Step 2: Triggering Webcron 10 times..." . PHP_EOL;

$accountRepo = new ImapAccountRepository($logger);
$webcronManager = new WebcronManager($accountRepo, $logger, [
    'api_base_url' => 'http://ci-inbox.local'
]);

for ($i = 1; $i <= 10; $i++) {
    echo "  Execution #{$i}... ";
    
    try {
        $result = $webcronManager->runPollingJob();
        
        if (isset($result['integrity_check'])) {
            echo "🔍 INTEGRITY CHECK RAN!" . PHP_EOL;
            echo "    Status: {$result['integrity_check']['status']}" . PHP_EOL;
            
            if (!empty($result['integrity_check']['issues'])) {
                echo "    Issues found:" . PHP_EOL;
                foreach ($result['integrity_check']['issues'] as $issue) {
                    echo "      - {$issue}" . PHP_EOL;
                }
            }
        } else {
            echo "Skipped (check runs every 10th)" . PHP_EOL;
        }
        
    } catch (Exception $e) {
        echo "⚠️  {$e->getMessage()}" . PHP_EOL;
    }
    
    usleep(100000); // 100ms delay
}

echo PHP_EOL;

// Step 3: Verify thread still exists
echo "Step 3: Verifying orphaned thread still exists..." . PHP_EOL;
$thread = DB::table('threads')->where('id', $threadId)->first();

if ($thread) {
    echo "✅ Thread #{$threadId} found: {$thread->subject}" . PHP_EOL;
    echo "   Status: {$thread->status}" . PHP_EOL;
    echo "   Message Count: {$thread->message_count}" . PHP_EOL;
} else {
    echo "❌ Thread #{$threadId} not found (was it deleted?)" . PHP_EOL;
}

echo PHP_EOL;

// Step 4: Check logs for integrity warnings
echo "Step 4: Checking logs for integrity warnings..." . PHP_EOL;
$logFile = __DIR__ . '/../../logs/app-' . date('Y-m-d') . '.log';

if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    $lines = explode("\n", $logContent);
    
    $foundWarnings = false;
    foreach (array_reverse($lines) as $line) {
        if (strpos($line, 'integrity') !== false && strpos($line, 'WARNING') !== false) {
            $foundWarnings = true;
            $data = json_decode($line, true);
            if ($data) {
                echo "  ⚠️  {$data['message']}" . PHP_EOL;
                if (isset($data['context']['issues'])) {
                    foreach ($data['context']['issues'] as $issue) {
                        echo "      - {$issue}" . PHP_EOL;
                    }
                }
            }
        }
        
        // Only check last 50 lines
        static $lineCount = 0;
        if (++$lineCount > 50) break;
    }
    
    if (!$foundWarnings) {
        echo "  ℹ️  No integrity warnings in last 50 log lines" . PHP_EOL;
    }
} else {
    echo "  ℹ️  Log file not found: {$logFile}" . PHP_EOL;
}

echo PHP_EOL;

// Step 5: Cleanup
echo "Step 5: Cleanup..." . PHP_EOL;
$choice = readline("Delete test thread #{$threadId}? (y/n): ");

if (strtolower(trim($choice)) === 'y') {
    DB::table('threads')->where('id', $threadId)->delete();
    echo "✅ Test thread deleted" . PHP_EOL;
} else {
    echo "⚠️  Test thread #{$threadId} kept (delete manually if needed)" . PHP_EOL;
}

echo PHP_EOL . "=== Test completed ===" . PHP_EOL;

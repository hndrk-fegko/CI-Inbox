<?php
/**
 * Fix Message Counts
 * 
 * Updates thread.message_count to match actual email count.
 * Run after data migrations or if counts become out of sync.
 * 
 * Usage: 
 *   php database/fix-message-counts.php --dry-run
 *   php database/fix-message-counts.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CiInbox\Modules\Config\ConfigService;
use CiInbox\Modules\Logger\LoggerService;
use Illuminate\Database\Capsule\Manager as DB;

$dryRun = in_array('--dry-run', $argv);

$config = new ConfigService(__DIR__ . '/../');
$logger = new LoggerService(__DIR__ . '/../logs/');

require_once __DIR__ . '/../src/bootstrap/database.php';
initDatabase($config);

echo "=== Fix Thread Message Counts ===" . PHP_EOL;
echo "Mode: " . ($dryRun ? "DRY RUN" : "LIVE") . PHP_EOL . PHP_EOL;

// Get all threads with actual email counts
$threads = DB::table('threads as t')
    ->leftJoin('emails as e', 't.id', '=', 'e.thread_id')
    ->select('t.id', 't.subject', 't.message_count', DB::raw('COUNT(e.id) as actual_count'))
    ->groupBy('t.id', 't.subject', 't.message_count')
    ->get();

$needsUpdate = 0;
$alreadyCorrect = 0;
$updated = 0;
$errors = 0;

foreach ($threads as $thread) {
    if ($thread->message_count != $thread->actual_count) {
        $needsUpdate++;
        
        echo "Thread #{$thread->id}: {$thread->subject}" . PHP_EOL;
        echo "  Current: {$thread->message_count}, Actual: {$thread->actual_count}" . PHP_EOL;
        
        if (!$dryRun) {
            try {
                DB::table('threads')
                    ->where('id', $thread->id)
                    ->update([
                        'message_count' => $thread->actual_count,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                
                echo "  ✅ Updated to {$thread->actual_count}" . PHP_EOL;
                
                $logger->info('Message count fixed', [
                    'thread_id' => $thread->id,
                    'old_count' => $thread->message_count,
                    'new_count' => $thread->actual_count
                ]);
                
                $updated++;
            } catch (Exception $e) {
                echo "  ❌ Error: {$e->getMessage()}" . PHP_EOL;
                
                $logger->error('Failed to fix message count', [
                    'thread_id' => $thread->id,
                    'error' => $e->getMessage()
                ]);
                
                $errors++;
            }
        } else {
            echo "  Would update to {$thread->actual_count}" . PHP_EOL;
        }
    } else {
        $alreadyCorrect++;
    }
}

echo PHP_EOL . "=== Summary ===" . PHP_EOL;
echo "Total threads: " . count($threads) . PHP_EOL;
echo "Already correct: {$alreadyCorrect}" . PHP_EOL;
echo "Need update: {$needsUpdate}" . PHP_EOL;

if (!$dryRun) {
    echo "Updated: {$updated}" . PHP_EOL;
    echo "Errors: {$errors}" . PHP_EOL;
    echo PHP_EOL . "✅ Message counts fixed!" . PHP_EOL;
} else {
    echo PHP_EOL . "DRY RUN complete. Run without --dry-run to apply changes." . PHP_EOL;
}

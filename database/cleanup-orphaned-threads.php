<?php
/**
 * Cleanup Orphaned Threads
 * 
 * Finds and handles threads that have message_count > 0 but no actual emails.
 * These threads are database inconsistencies that shouldn't occur in production.
 * 
 * Options:
 * - --archive: Archive orphaned threads (default)
 * - --delete: Permanently delete orphaned threads
 * - --fix-count: Fix message_count to 0 for orphaned threads
 * - --dry-run: Show what would be done without making changes
 * 
 * Usage: 
 *   php database/cleanup-orphaned-threads.php --archive --dry-run
 *   php database/cleanup-orphaned-threads.php --delete
 *   php database/cleanup-orphaned-threads.php --fix-count
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CiInbox\Modules\Config\ConfigService;
use CiInbox\Modules\Logger\LoggerService;
use Illuminate\Database\Capsule\Manager as DB;

// Parse command line options
$options = getopt('', ['archive', 'delete', 'fix-count', 'dry-run', 'help']);

if (isset($options['help'])) {
    echo "Cleanup Orphaned Threads" . PHP_EOL;
    echo "========================" . PHP_EOL . PHP_EOL;
    echo "Finds threads with message_count > 0 but no actual emails." . PHP_EOL . PHP_EOL;
    echo "Options:" . PHP_EOL;
    echo "  --archive    Archive orphaned threads (default)" . PHP_EOL;
    echo "  --delete     Permanently delete orphaned threads" . PHP_EOL;
    echo "  --fix-count  Set message_count to 0 for orphaned threads" . PHP_EOL;
    echo "  --dry-run    Show what would be done without changes" . PHP_EOL;
    echo "  --help       Show this help message" . PHP_EOL . PHP_EOL;
    echo "Examples:" . PHP_EOL;
    echo "  php database/cleanup-orphaned-threads.php --dry-run" . PHP_EOL;
    echo "  php database/cleanup-orphaned-threads.php --archive" . PHP_EOL;
    echo "  php database/cleanup-orphaned-threads.php --delete --dry-run" . PHP_EOL;
    exit(0);
}

// Determine action
$action = 'archive'; // Default
if (isset($options['delete'])) {
    $action = 'delete';
} elseif (isset($options['fix-count'])) {
    $action = 'fix-count';
}

$dryRun = isset($options['dry-run']);

// Initialize
$config = new ConfigService(__DIR__ . '/../');
$logger = new LoggerService(__DIR__ . '/../logs/');

require_once __DIR__ . '/../src/bootstrap/database.php';
initDatabase($config);

echo "=== Cleanup Orphaned Threads ===" . PHP_EOL . PHP_EOL;
echo "Action: " . strtoupper($action) . PHP_EOL;
echo "Mode: " . ($dryRun ? "DRY RUN (no changes)" : "LIVE (will make changes)") . PHP_EOL;
echo PHP_EOL;

// Find orphaned threads: message_count > 0 but no emails
$orphanedThreads = DB::table('threads as t')
    ->leftJoin('emails as e', 't.id', '=', 'e.thread_id')
    ->select('t.id', 't.subject', 't.message_count', 't.status', 't.created_at')
    ->where('t.message_count', '>', 0)
    ->whereNull('e.id')
    ->groupBy('t.id', 't.subject', 't.message_count', 't.status', 't.created_at')
    ->get();

if ($orphanedThreads->isEmpty()) {
    echo "✅ No orphaned threads found. Database is consistent!" . PHP_EOL;
    $logger->info('Orphaned thread cleanup: No issues found');
    exit(0);
}

echo "Found " . count($orphanedThreads) . " orphaned thread(s):" . PHP_EOL . PHP_EOL;

foreach ($orphanedThreads as $thread) {
    echo "Thread #{$thread->id}: {$thread->subject}" . PHP_EOL;
    echo "  Status: {$thread->status}" . PHP_EOL;
    echo "  Message Count: {$thread->message_count} (but 0 actual emails)" . PHP_EOL;
    echo "  Created: {$thread->created_at}" . PHP_EOL;
}

echo PHP_EOL;

if ($dryRun) {
    echo "DRY RUN: No changes will be made." . PHP_EOL;
    echo "What would happen:" . PHP_EOL . PHP_EOL;
}

$processed = 0;
$errors = 0;

foreach ($orphanedThreads as $thread) {
    try {
        if (!$dryRun) {
            DB::beginTransaction();
        }
        
        switch ($action) {
            case 'archive':
                if ($dryRun) {
                    echo "  Would ARCHIVE thread #{$thread->id}: {$thread->subject}" . PHP_EOL;
                } else {
                    DB::table('threads')
                        ->where('id', $thread->id)
                        ->update([
                            'status' => 'archived',
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    echo "  ✅ ARCHIVED thread #{$thread->id}: {$thread->subject}" . PHP_EOL;
                    
                    $logger->warning('Orphaned thread archived', [
                        'thread_id' => $thread->id,
                        'subject' => $thread->subject,
                        'claimed_count' => $thread->message_count
                    ]);
                }
                break;
                
            case 'delete':
                if ($dryRun) {
                    echo "  Would DELETE thread #{$thread->id}: {$thread->subject}" . PHP_EOL;
                } else {
                    // Delete related data first
                    DB::table('thread_labels')->where('thread_id', $thread->id)->delete();
                    DB::table('thread_assignments')->where('thread_id', $thread->id)->delete();
                    DB::table('internal_notes')->where('thread_id', $thread->id)->delete();
                    
                    // Delete thread
                    DB::table('threads')->where('id', $thread->id)->delete();
                    
                    echo "  ✅ DELETED thread #{$thread->id}: {$thread->subject}" . PHP_EOL;
                    
                    $logger->warning('Orphaned thread deleted', [
                        'thread_id' => $thread->id,
                        'subject' => $thread->subject,
                        'claimed_count' => $thread->message_count
                    ]);
                }
                break;
                
            case 'fix-count':
                if ($dryRun) {
                    echo "  Would FIX message_count for thread #{$thread->id}: {$thread->subject} ({$thread->message_count} → 0)" . PHP_EOL;
                } else {
                    DB::table('threads')
                        ->where('id', $thread->id)
                        ->update([
                            'message_count' => 0,
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                    echo "  ✅ FIXED thread #{$thread->id}: {$thread->subject} (message_count: {$thread->message_count} → 0)" . PHP_EOL;
                    
                    $logger->info('Orphaned thread message_count fixed', [
                        'thread_id' => $thread->id,
                        'subject' => $thread->subject,
                        'old_count' => $thread->message_count
                    ]);
                }
                break;
        }
        
        if (!$dryRun) {
            DB::commit();
        }
        
        $processed++;
        
    } catch (Exception $e) {
        if (!$dryRun) {
            DB::rollBack();
        }
        
        echo "  ❌ ERROR processing thread #{$thread->id}: {$e->getMessage()}" . PHP_EOL;
        
        $logger->error('Failed to process orphaned thread', [
            'thread_id' => $thread->id,
            'action' => $action,
            'error' => $e->getMessage()
        ]);
        
        $errors++;
    }
}

echo PHP_EOL . "=== Summary ===" . PHP_EOL;
echo "Found: " . count($orphanedThreads) . " orphaned thread(s)" . PHP_EOL;
echo "Processed: {$processed}" . PHP_EOL;
echo "Errors: {$errors}" . PHP_EOL;

if ($dryRun) {
    echo PHP_EOL . "This was a DRY RUN. Run without --dry-run to apply changes." . PHP_EOL;
} else {
    echo PHP_EOL . "✅ Cleanup completed!" . PHP_EOL;
}

// Show statistics after cleanup
echo PHP_EOL . "=== Database Statistics ===" . PHP_EOL;
$totalThreads = DB::table('threads')->count();
$totalEmails = DB::table('emails')->count();
$archivedThreads = DB::table('threads')->where('status', 'archived')->count();

echo "Total threads: {$totalThreads}" . PHP_EOL;
echo "Total emails: {$totalEmails}" . PHP_EOL;
echo "Archived threads: {$archivedThreads}" . PHP_EOL;

// Verify no orphans remain
$remainingOrphans = DB::table('threads as t')
    ->leftJoin('emails as e', 't.id', '=', 'e.thread_id')
    ->where('t.message_count', '>', 0)
    ->whereNull('e.id')
    ->count();

if ($remainingOrphans > 0) {
    echo PHP_EOL . "⚠️  Warning: {$remainingOrphans} orphaned thread(s) still remain" . PHP_EOL;
} else {
    echo PHP_EOL . "✅ No orphaned threads remaining" . PHP_EOL;
}

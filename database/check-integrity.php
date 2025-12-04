<?php
/**
 * Database Integrity Check
 * 
 * Validates database consistency and reports issues:
 * - Orphaned threads (message_count > 0 but no emails)
 * - Threads with wrong message_count
 * - Emails without valid thread_id
 * - Duplicate message_ids
 * 
 * Usage: php database/check-integrity.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CiInbox\Modules\Config\ConfigService;
use Illuminate\Database\Capsule\Manager as DB;

$config = new ConfigService(__DIR__ . '/../');
require_once __DIR__ . '/../src/bootstrap/database.php';
initDatabase($config);

echo "=== Database Integrity Check ===" . PHP_EOL . PHP_EOL;

$issues = [];

// Check 1: Orphaned threads (message_count > 0 but no emails)
echo "[1/5] Checking for orphaned threads..." . PHP_EOL;
$orphanedThreads = DB::table('threads as t')
    ->leftJoin('emails as e', 't.id', '=', 'e.thread_id')
    ->select('t.id', 't.subject', 't.message_count')
    ->where('t.message_count', '>', 0)
    ->whereNull('e.id')
    ->groupBy('t.id', 't.subject', 't.message_count')
    ->get();

if ($orphanedThreads->isNotEmpty()) {
    $issues[] = "❌ Found " . count($orphanedThreads) . " orphaned thread(s) (message_count > 0 but no emails)";
    foreach ($orphanedThreads as $thread) {
        echo "  - Thread #{$thread->id}: {$thread->subject} (claims {$thread->message_count} emails)" . PHP_EOL;
    }
} else {
    echo "  ✅ No orphaned threads" . PHP_EOL;
}

// Check 2: Threads with incorrect message_count
echo "[2/5] Checking message_count accuracy..." . PHP_EOL;
$wrongCounts = DB::table('threads as t')
    ->leftJoin('emails as e', 't.id', '=', 'e.thread_id')
    ->select('t.id', 't.subject', 't.message_count', DB::raw('COUNT(e.id) as actual_count'))
    ->groupBy('t.id', 't.subject', 't.message_count')
    ->havingRaw('t.message_count != COUNT(e.id)')
    ->get();

if ($wrongCounts->isNotEmpty()) {
    $issues[] = "❌ Found " . count($wrongCounts) . " thread(s) with incorrect message_count";
    foreach ($wrongCounts as $thread) {
        echo "  - Thread #{$thread->id}: {$thread->subject} (stored: {$thread->message_count}, actual: {$thread->actual_count})" . PHP_EOL;
    }
} else {
    echo "  ✅ All message_counts are accurate" . PHP_EOL;
}

// Check 3: Emails with invalid thread_id
echo "[3/5] Checking for emails with invalid thread_id..." . PHP_EOL;
$orphanedEmails = DB::table('emails as e')
    ->leftJoin('threads as t', 'e.thread_id', '=', 't.id')
    ->select('e.id', 'e.subject', 'e.thread_id')
    ->whereNull('t.id')
    ->get();

if ($orphanedEmails->isNotEmpty()) {
    $issues[] = "❌ Found " . count($orphanedEmails) . " email(s) with invalid thread_id";
    foreach ($orphanedEmails as $email) {
        echo "  - Email #{$email->id}: {$email->subject} (thread_id: {$email->thread_id} not found)" . PHP_EOL;
    }
} else {
    echo "  ✅ All emails have valid thread_id" . PHP_EOL;
}

// Check 4: Duplicate message_ids
echo "[4/5] Checking for duplicate message_ids..." . PHP_EOL;
$duplicates = DB::table('emails')
    ->select('message_id', DB::raw('COUNT(*) as count'))
    ->groupBy('message_id')
    ->having('count', '>', 1)
    ->get();

if ($duplicates->isNotEmpty()) {
    $issues[] = "❌ Found " . count($duplicates) . " duplicate message_id(s)";
    foreach ($duplicates as $dup) {
        echo "  - message_id '{$dup->message_id}' appears {$dup->count} times" . PHP_EOL;
        $emails = DB::table('emails')
            ->where('message_id', $dup->message_id)
            ->select('id', 'thread_id', 'subject')
            ->get();
        foreach ($emails as $email) {
            echo "    * Email #{$email->id} (thread #{$email->thread_id}): {$email->subject}" . PHP_EOL;
        }
    }
} else {
    echo "  ✅ No duplicate message_ids" . PHP_EOL;
}

// Check 5: Threads without any data (empty threads)
echo "[5/5] Checking for completely empty threads..." . PHP_EOL;
$emptyThreads = DB::table('threads as t')
    ->leftJoin('emails as e', 't.id', '=', 'e.thread_id')
    ->leftJoin('internal_notes as n', 't.id', '=', 'n.thread_id')
    ->select('t.id', 't.subject', 't.message_count', 't.status', 't.created_at')
    ->whereNull('e.id')
    ->whereNull('n.id')
    ->where('t.message_count', '=', 0)
    ->groupBy('t.id', 't.subject', 't.message_count', 't.status', 't.created_at')
    ->get();

if ($emptyThreads->isNotEmpty()) {
    $issues[] = "⚠️  Found " . count($emptyThreads) . " completely empty thread(s) (no emails, no notes)";
    foreach ($emptyThreads as $thread) {
        echo "  - Thread #{$thread->id}: {$thread->subject} (status: {$thread->status}, created: {$thread->created_at})" . PHP_EOL;
    }
} else {
    echo "  ✅ No completely empty threads" . PHP_EOL;
}

// Summary
echo PHP_EOL . "=== Summary ===" . PHP_EOL;

if (empty($issues)) {
    echo "✅ Database integrity check passed! No issues found." . PHP_EOL;
} else {
    echo "❌ Found " . count($issues) . " issue(s):" . PHP_EOL;
    foreach ($issues as $issue) {
        echo "  • {$issue}" . PHP_EOL;
    }
    
    echo PHP_EOL . "=== Recommended Actions ===" . PHP_EOL;
    
    if (!empty($orphanedThreads)) {
        echo "• Run: php database/cleanup-orphaned-threads.php --archive --dry-run" . PHP_EOL;
        echo "  (Archives threads with message_count > 0 but no emails)" . PHP_EOL;
    }
    
    if (!empty($wrongCounts)) {
        echo "• Run: php database/fix-message-counts.php" . PHP_EOL;
        echo "  (Corrects message_count based on actual email count)" . PHP_EOL;
    }
    
    if (!empty($orphanedEmails)) {
        echo "• Manually investigate emails with invalid thread_id" . PHP_EOL;
    }
    
    if (!empty($duplicates)) {
        echo "• Manually investigate duplicate message_ids (should not happen with unique constraint)" . PHP_EOL;
    }
    
    if (!empty($emptyThreads)) {
        echo "• Consider deleting empty threads or leave them if created intentionally" . PHP_EOL;
    }
}

echo PHP_EOL . "=== Database Statistics ===" . PHP_EOL;
$stats = [
    'threads' => DB::table('threads')->count(),
    'emails' => DB::table('emails')->count(),
    'notes' => DB::table('internal_notes')->count(),
    'archived_threads' => DB::table('threads')->where('status', 'archived')->count(),
];

echo "Threads: {$stats['threads']}" . PHP_EOL;
echo "Emails: {$stats['emails']}" . PHP_EOL;
echo "Internal Notes: {$stats['notes']}" . PHP_EOL;
echo "Archived Threads: {$stats['archived_threads']}" . PHP_EOL;

exit(empty($issues) ? 0 : 1);

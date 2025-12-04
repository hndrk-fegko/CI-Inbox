<?php
/**
 * Restore Test Emails for Existing Threads
 * 
 * Creates test emails for threads that have message_count > 0 but no emails
 * 
 * Usage: php database/restore-test-emails.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CiInbox\Modules\Config\ConfigService;
use Illuminate\Database\Capsule\Manager as DB;

// Initialize config
$config = new ConfigService(__DIR__ . '/../');

// Initialize database
require_once __DIR__ . '/../src/bootstrap/database.php';
initDatabase($config);

echo "=== Restoring Test Emails for Threads ===" . PHP_EOL . PHP_EOL;

// Get threads that should have emails (message_count > 0)
$threads = DB::table('threads')
    ->where('message_count', '>', 0)
    ->get();

if ($threads->isEmpty()) {
    echo "✅ No threads need email restoration" . PHP_EOL;
    exit(0);
}

echo "Found " . count($threads) . " threads with message_count > 0" . PHP_EOL;
echo "Creating test emails..." . PHP_EOL . PHP_EOL;

$emailsCreated = 0;
$now = new DateTime();

foreach ($threads as $thread) {
    echo "Thread {$thread->id}: {$thread->subject} (needs {$thread->message_count} emails)" . PHP_EOL;
    
    // Create emails based on message_count
    for ($i = 1; $i <= $thread->message_count; $i++) {
        $sentAt = (clone $now)->modify("-" . ($thread->message_count - $i) . " hours");
        
        $emailData = [
            'imap_account_id' => 4, // testuser@localhost account
            'thread_id' => $thread->id,
            'message_id' => "restored-email-{$thread->id}-{$i}@ci-inbox.test",
            'in_reply_to' => $i > 1 ? "restored-email-{$thread->id}-" . ($i - 1) . "@ci-inbox.test" : null,
            'subject' => $thread->subject,
            'from_email' => $thread->sender_email ?: 'test@example.com',
            'from_name' => $thread->sender_name ?: 'Test Sender',
            'to_addresses' => json_encode(['inbox@ci-inbox.test']),
            'cc_addresses' => json_encode([]),
            'sent_at' => $sentAt->format('Y-m-d H:i:s'),
            'body_plain' => "This is test email #{$i} for thread '{$thread->subject}'.\n\n" .
                           "Generated to restore missing email data.\n" .
                           "Thread ID: {$thread->id}\n" .
                           "Sent at: " . $sentAt->format('Y-m-d H:i:s'),
            'body_html' => "<div style='font-family: Arial, sans-serif;'>" .
                          "<h2>Test Email #{$i}</h2>" .
                          "<p>This is test email #{$i} for thread <strong>{$thread->subject}</strong>.</p>" .
                          "<p><small>Generated to restore missing email data.</small></p>" .
                          "<hr>" .
                          "<p style='color: #666;'>Thread ID: {$thread->id}<br>" .
                          "Sent at: " . $sentAt->format('Y-m-d H:i:s') . "</p>" .
                          "</div>",
            'has_attachments' => false,
            'attachment_metadata' => null,
            'direction' => 'incoming',
            'is_read' => $i < $thread->message_count ? true : false, // Mark all but last as read
            'created_at' => $now->format('Y-m-d H:i:s'),
            'updated_at' => $now->format('Y-m-d H:i:s'),
        ];
        
        try {
            DB::table('emails')->insert($emailData);
            echo "  ✅ Created email #{$i}: {$emailData['message_id']}" . PHP_EOL;
            $emailsCreated++;
        } catch (Exception $e) {
            echo "  ❌ Failed to create email #{$i}: " . $e->getMessage() . PHP_EOL;
        }
    }
    
    echo PHP_EOL;
}

echo "=== Summary ===" . PHP_EOL;
echo "Threads processed: " . count($threads) . PHP_EOL;
echo "Emails created: {$emailsCreated}" . PHP_EOL;
echo PHP_EOL;

// Verify emails were created
$totalEmails = DB::table('emails')->count();
echo "Total emails in database: {$totalEmails}" . PHP_EOL;

// Show sample thread details
echo PHP_EOL . "=== Sample Thread Details (First Thread) ===" . PHP_EOL;
$firstThread = $threads->first();
$firstThreadEmails = DB::table('emails')
    ->where('thread_id', $firstThread->id)
    ->orderBy('sent_at')
    ->get();

echo "Thread: {$firstThread->subject}" . PHP_EOL;
echo "Emails in thread: " . count($firstThreadEmails) . PHP_EOL;
foreach ($firstThreadEmails as $email) {
    echo "  - {$email->subject} (from: {$email->from_name}, sent: {$email->sent_at})" . PHP_EOL;
}

echo PHP_EOL . "✅ Test emails restored successfully!" . PHP_EOL;
echo "You can now test thread detail view in inbox.php" . PHP_EOL;

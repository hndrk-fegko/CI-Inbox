<?php

/**
 * Email Send Service Test
 * 
 * Tests complete email send workflow (Service Layer + Database)
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use CiInbox\Core\Container;
use CiInbox\App\Services\EmailSendService;
use CiInbox\Modules\Config\ConfigService;

// Initialize
$config = new ConfigService(__DIR__ . '/../../');
require_once __DIR__ . '/../../src/bootstrap/database.php';
initDatabase($config);

$container = Container::getInstance();
$emailSendService = $container->get(EmailSendService::class);

echo "=== Email Send Service Test ===" . PHP_EOL . PHP_EOL;

// Test 1: Send new email
echo "TEST 1: Send new email" . PHP_EOL;
try {
    $email = $emailSendService->sendEmail([
        'subject' => 'Test Email from EmailSendService',
        'body_text' => 'This is a test email body sent via EmailSendService.',
        'to' => 'info@feg-koblenz.de',
        'imap_account_id' => 4
    ]);
    echo "✅ Email sent successfully" . PHP_EOL;
    echo "   Email ID: {$email->id}" . PHP_EOL;
    echo "   Message-ID: {$email->message_id}" . PHP_EOL;
    echo "   Direction: {$email->direction}" . PHP_EOL;
    echo "   Sent At: " . $email->sent_at->format('Y-m-d H:i:s') . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . PHP_EOL;
}

// Test 2: Reply to thread
echo PHP_EOL . "TEST 2: Reply to thread" . PHP_EOL;
try {
    // Get first available thread
    $thread = \CiInbox\App\Models\Thread::first();
    
    if (!$thread) {
        echo "⚠️  No threads found - creating test thread first" . PHP_EOL;
        
        // Create a test thread
        $thread = new \CiInbox\App\Models\Thread();
        $thread->subject = "Test Thread for Reply";
        $thread->participants = json_encode([['email' => 'sender@localhost', 'name' => 'Test Sender']]);
        $thread->preview = "Test thread preview";
        $thread->status = 'open';
        $thread->message_count = 1;
        $thread->has_attachments = false;
        $thread->last_message_at = \Carbon\Carbon::now();
        $thread->save();
        
        // Create a test email in that thread
        $testEmail = new \CiInbox\App\Models\Email();
        $testEmail->thread_id = $thread->id;
        $testEmail->imap_account_id = 4;
        $testEmail->message_id = '<test-' . time() . '@localhost>';
        $testEmail->subject = $thread->subject;
        $testEmail->from_email = 'sender@localhost';
        $testEmail->from_name = 'Test Sender';
        $testEmail->to_addresses = json_encode([['email' => 'info@localhost', 'name' => 'CI-Inbox']]);
        $testEmail->body_plain = "Test email body";
        $testEmail->body_html = "<p>Test email body</p>";
        $testEmail->direction = 'incoming';
        $testEmail->sent_at = \Carbon\Carbon::now();
        $testEmail->save();
        
        echo "✅ Created test thread ID={$thread->id}" . PHP_EOL;
    }
    
    $email = $emailSendService->replyToThread(
        $thread->id,
        "This is a reply to the thread.",
        4
    );
    echo "✅ Reply sent successfully" . PHP_EOL;
    echo "   Email ID: {$email->id}" . PHP_EOL;
    echo "   Thread ID: {$email->thread_id}" . PHP_EOL;
    echo "   Subject: {$email->subject}" . PHP_EOL;
    echo "   In-Reply-To: {$email->in_reply_to}" . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . PHP_EOL;
}

// Test 3: Forward thread
echo PHP_EOL . "TEST 3: Forward thread" . PHP_EOL;
try {
    // Use the same thread from Test 2
    if (isset($thread)) {
        $email = $emailSendService->forwardThread(
            $thread->id,
            ['info@feg-koblenz.de'],
            "FYI - see thread below",
            4
        );
        echo "✅ Forward sent successfully" . PHP_EOL;
        echo "   Email ID: {$email->id}" . PHP_EOL;
        echo "   Thread ID: {$email->thread_id}" . PHP_EOL;
        echo "   Subject: {$email->subject}" . PHP_EOL;
        echo "   Recipients: " . json_encode($email->to_addresses) . PHP_EOL;
    } else {
        echo "⚠️  No thread available for forward test" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== All tests completed ===" . PHP_EOL;
echo PHP_EOL . "Check database table 'emails' for sent emails (direction='outgoing')" . PHP_EOL;

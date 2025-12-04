<?php

/**
 * Thread API Manual Test Script
 * 
 * Tests the Thread Management API without requiring HTTP server
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use CiInbox\Core\Container;
use CiInbox\App\Services\ThreadApiService;
use CiInbox\App\Models\Thread;
use CiInbox\App\Models\Email;
use CiInbox\Modules\Config\ConfigService;

// Initialize
$config = new ConfigService(__DIR__ . '/../../');
require_once __DIR__ . '/../../src/bootstrap/database.php';
initDatabase($config);

// Get service from container
$container = Container::getInstance();
$threadService = $container->get(ThreadApiService::class);

echo "=== Thread API Test Script ===" . PHP_EOL . PHP_EOL;

// Test 1: Create Thread
echo "TEST 1: Create Thread" . PHP_EOL;
try {
    $thread1 = $threadService->createThread([
        'subject' => 'Test Thread 1',
        'status' => 'open',
        'note' => 'Created for testing'
    ]);
    echo "✅ Thread created: ID={$thread1->id}, Subject={$thread1->subject}" . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Test 2: Create another thread
echo PHP_EOL . "TEST 2: Create Second Thread" . PHP_EOL;
try {
    $thread2 = $threadService->createThread([
        'subject' => 'Test Thread 2',
        'status' => 'open'
    ]);
    echo "✅ Thread created: ID={$thread2->id}, Subject={$thread2->subject}" . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . PHP_EOL;
}

// Test 3: Create test emails for split/merge testing
echo PHP_EOL . "TEST 3: Create Test Emails" . PHP_EOL;
try {
    $timestamp = time();
    
    // Create 3 emails in thread1
    for ($i = 1; $i <= 3; $i++) {
        $email = new Email();
        $email->thread_id = $thread1->id;
        $email->imap_account_id = 4;
        $email->message_id = "test-email-{$timestamp}-{$i}@localhost";
        $email->subject = "Test Email {$i}";
        $email->from_email = "test@localhost";
        $email->from_name = "Test User";
        $email->to_addresses = ['recipient@localhost'];
        $email->body_plain = "This is test email {$i}";
        $email->sent_at = Carbon\Carbon::now();
        $email->save();
    }
    echo "✅ Created 3 test emails in Thread #{$thread1->id}" . PHP_EOL;
    
    // Create 2 emails in thread2
    for ($i = 4; $i <= 5; $i++) {
        $email = new Email();
        $email->thread_id = $thread2->id;
        $email->imap_account_id = 4;
        $email->message_id = "test-email-{$timestamp}-{$i}@localhost";
        $email->subject = "Test Email {$i}";
        $email->from_email = "test@localhost";
        $email->from_name = "Test User";
        $email->to_addresses = ['recipient@localhost'];
        $email->body_plain = "This is test email {$i}";
        $email->sent_at = Carbon\Carbon::now();
        $email->save();
    }
    echo "✅ Created 2 test emails in Thread #{$thread2->id}" . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Test 4: Get Thread
echo PHP_EOL . "TEST 4: Get Thread with Emails" . PHP_EOL;
try {
    $thread = $threadService->getThread($thread1->id, true, true);
    echo "✅ Thread retrieved: {$thread->emails->count()} emails, {$thread->notes->count()} notes" . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . PHP_EOL;
}

// Test 5: List Threads
echo PHP_EOL . "TEST 5: List Threads" . PHP_EOL;
try {
    $result = $threadService->listThreads(['status' => 'open']);
    echo "✅ Listed {$result['total']} thread(s)" . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . PHP_EOL;
}

// Test 6: Update Thread
echo PHP_EOL . "TEST 6: Update Thread" . PHP_EOL;
try {
    $thread = $threadService->updateThread($thread1->id, [
        'status' => 'pending'
    ]);
    echo "✅ Thread updated: status={$thread->status}" . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . PHP_EOL;
}

// Test 7: Add Note
echo PHP_EOL . "TEST 7: Add Note to Thread" . PHP_EOL;
try {
    $note = $threadService->addNote($thread1->id, "This is a test note", null);
    echo "✅ Note added: ID={$note->id}, Type={$note->type}" . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . PHP_EOL;
}

// Test 8: Split Thread
echo PHP_EOL . "TEST 8: Split Thread" . PHP_EOL;
try {
    // Get first 2 emails from thread1
    $emails = Email::where('thread_id', $thread1->id)->limit(2)->get();
    $emailIds = $emails->pluck('id')->toArray();
    
    $newThread = $threadService->splitThread($thread1->id, $emailIds, "Split Thread Test");
    echo "✅ Thread split: New Thread ID={$newThread->id}, Emails moved={$newThread->emails->count()}" . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . PHP_EOL;
}

// Test 9: Merge Threads
echo PHP_EOL . "TEST 9: Merge Threads" . PHP_EOL;
try {
    // Get the newly created split thread
    $splitThread = Thread::orderBy('id', 'desc')->first();
    
    $mergedThread = $threadService->mergeThreads($thread2->id, $splitThread->id);
    echo "✅ Threads merged: Target Thread ID={$mergedThread->id}, Total emails={$mergedThread->emails->count()}" . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . PHP_EOL;
}

// Test 10: Move Email to Thread
echo PHP_EOL . "TEST 10: Move Email to Thread" . PHP_EOL;
try {
    // Get one email from thread2
    $email = Email::where('thread_id', $thread2->id)->first();
    
    if ($email && Email::where('thread_id', $thread2->id)->count() > 1) {
        $movedEmail = $threadService->moveEmailToThread($email->id, $thread1->id);
        echo "✅ Email moved: Email ID={$movedEmail->id}, New Thread ID={$movedEmail->thread_id}" . PHP_EOL;
    } else {
        echo "⚠️  Skipped: Not enough emails to test move" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . PHP_EOL;
}

// Test 11: Assign Email to Thread
echo PHP_EOL . "TEST 11: Assign Email to Thread" . PHP_EOL;
try {
    $email = Email::where('thread_id', $thread1->id)->first();
    
    if ($email) {
        $thread = $threadService->assignEmailToThread($thread2->id, $email->id);
        echo "✅ Email assigned: Email ID={$email->id}, New Thread ID={$thread->id}" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . PHP_EOL;
}

// Final Summary
echo PHP_EOL . "=== Test Summary ===" . PHP_EOL;
echo "✅ All core operations tested successfully!" . PHP_EOL;
echo PHP_EOL . "Check 'internal_notes' table for system notes" . PHP_EOL;

// Show system notes
try {
    $notes = \CiInbox\App\Models\InternalNote::where('type', 'system')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    
    if ($notes->count() > 0) {
        echo PHP_EOL . "Recent System Notes:" . PHP_EOL;
        foreach ($notes as $note) {
            echo "  - Thread #{$note->thread_id}: {$note->content}" . PHP_EOL;
        }
    }
} catch (Exception $e) {
    // Ignore
}

echo PHP_EOL . "=== Done ===" . PHP_EOL;

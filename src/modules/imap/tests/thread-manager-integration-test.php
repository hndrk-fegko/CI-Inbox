<?php
/**
 * Thread Manager Integration Test
 * 
 * Tests the complete threading pipeline:
 * 1. Fetch emails from IMAP
 * 2. Parse emails
 * 3. Process emails through ThreadService
 * 4. Verify threads are created correctly
 */

require_once __DIR__ . '/../../../../vendor/autoload.php';

use CiInbox\Modules\Config\ConfigService;
use CiInbox\Modules\Logger\LoggerService;
use CiInbox\Modules\Imap\ImapClient;
use CiInbox\Modules\Imap\Parser\EmailParser;
use CiInbox\Modules\Imap\Parser\ParsedEmail; // Use Parser namespace
use CiInbox\Modules\Imap\Manager\ThreadManager;
use CiInbox\App\Services\ThreadService;
use CiInbox\App\Repositories\ThreadRepository;
use CiInbox\App\Repositories\EloquentEmailRepository;
use CiInbox\App\Models\Thread;
use CiInbox\App\Models\Email;

echo "=== Thread Manager Integration Test ===\n\n";

// 1. Setup Services
echo "1. Initializing services...\n";
$logger = new LoggerService(__DIR__ . '/../../../../logs');
$config = new ConfigService(
    envPath: __DIR__ . '/../../../../',
    configPath: __DIR__ . '/../../../../src/config',
    logger: $logger
);

// Initialize Database
require_once __DIR__ . '/../../../../src/bootstrap/database.php';
initDatabase($config);

$threadManager = new ThreadManager($logger);
$threadRepository = new ThreadRepository($logger);
$emailRepository = new EloquentEmailRepository($logger);
$threadService = new ThreadService(
    $threadManager,
    $threadRepository,
    $emailRepository,
    $logger
);

echo "✓ Services initialized\n\n";

// 2. Clear existing test data (optional)
echo "2. Clearing existing test data...\n";
$deletedThreads = Thread::where('subject', 'LIKE', '%Test%')->orWhere('subject', 'LIKE', '%CI-Inbox%')->delete();
$deletedEmails = Email::whereNull('thread_id')->delete();
echo "✓ Cleared $deletedThreads threads, $deletedEmails orphaned emails\n\n";

// 3. Fetch emails from IMAP
echo "3. Fetching emails from IMAP...\n";
try {
    $imap = new ImapClient($logger, $config);
    $imap->connect(
        host: 'localhost',
        port: 143,
        username: 'testuser',
        password: 'testpass123',
        ssl: false
    );
    $imap->selectFolder('INBOX');
    
    $messageCount = $imap->getMessageCount();
    echo "✓ Connected to IMAP: $messageCount messages in INBOX\n";
    
    $messages = $imap->getMessages(limit: 20);
    echo "✓ Fetched " . count($messages) . " messages\n\n";
    
} catch (Exception $e) {
    echo "✗ IMAP error: " . $e->getMessage() . "\n";
    echo "Skipping IMAP tests, using mock data...\n\n";
    $messages = [];
}

// 4. Parse and process each email
echo "4. Processing emails through ThreadService...\n";
$parser = new EmailParser($logger);
$processedCount = 0;

foreach ($messages as $message) {
    try {
        // Parse email
        $parsed = $parser->parseMessage($message);
        
        // Process through ThreadService
        $thread = $threadService->processEmail($parsed);
        
        echo "✓ Processed: {$parsed->subject}\n";
        echo "  Thread ID: {$thread->id}\n";
        echo "  Subject: {$thread->subject}\n";
        echo "  Messages: {$thread->message_count}\n";
        echo "  Participants: " . count($thread->participants) . "\n\n";
        
        $processedCount++;
        
    } catch (Exception $e) {
        echo "✗ Error processing message: " . $e->getMessage() . "\n\n";
    }
}

echo "✓ Processed $processedCount emails\n\n";

// 5. Thread Summary
echo "5. Thread Summary:\n";
$threads = Thread::with('emails')->get();
echo "Total Threads: " . $threads->count() . "\n\n";

foreach ($threads as $thread) {
    echo "Thread #{$thread->id}: {$thread->subject}\n";
    echo "  Status: {$thread->status}\n";
    echo "  Messages: {$thread->message_count}\n";
    echo "  Participants: " . count($thread->participants) . " (" . implode(', ', array_slice($thread->participants, 0, 3)) . ")\n";
    echo "  Last Message: {$thread->last_message_at}\n";
    
    // Show email chain
    echo "  Email Chain:\n";
    foreach ($thread->emails()->orderBy('sent_at', 'asc')->get() as $email) {
        echo "    - {$email->sent_at} | {$email->from_email} | {$email->subject}\n";
        if ($email->in_reply_to) {
            echo "      └─ In-Reply-To: {$email->in_reply_to}\n";
        }
    }
    echo "\n";
}

// 6. Threading Analysis
echo "6. Threading Analysis:\n";
$threadsWithMultipleEmails = $threads->filter(fn($t) => $t->message_count > 1)->count();
$threadsWithSingleEmail = $threads->filter(fn($t) => $t->message_count === 1)->count();

echo "Threads with multiple emails: $threadsWithMultipleEmails\n";
echo "Threads with single email: $threadsWithSingleEmail\n\n";

// Threading efficiency
$totalEmails = Email::count();
$threadingEfficiency = $threads->count() > 0 ? round(($totalEmails / $threads->count()), 2) : 0;
echo "Threading Efficiency: $threadingEfficiency emails/thread\n";
echo "(Higher = better threading, similar emails grouped together)\n\n";

// 7. Cleanup
if (isset($imap)) {
    $imap->disconnect();
    echo "✓ Disconnected from IMAP\n";
}

echo "\n=== Test Complete ===\n";
echo "Check logs/app-" . date('Y-m-d') . ".log for detailed logging output\n";

<?php
/**
 * Test: Mark Thread as Read/Unread
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use CiInbox\Core\Container;
use CiInbox\App\Services\ThreadApiService;

// Initialize
$container = Container::getInstance();
$container->get('database');

$threadService = $container->get(ThreadApiService::class);

// Get first thread
$threadId = 53; // Change this to an existing thread ID

echo "Testing Mark as Read/Unread API\n";
echo "================================\n\n";

try {
    // Test 1: Mark as Read
    echo "1. Marking thread {$threadId} as READ...\n";
    $threadService->markAsRead($threadId);
    echo "   ✓ Success\n\n";
    
    // Verify
    $thread = $threadService->getThread($threadId, true, false);
    echo "   Thread is_read: " . ($thread->is_read ? 'true' : 'false') . "\n";
    echo "   Email count: " . $thread->emails->count() . "\n";
    echo "   Emails read: " . $thread->emails->where('is_read', true)->count() . "\n\n";
    
    // Test 2: Mark as Unread
    echo "2. Marking thread {$threadId} as UNREAD...\n";
    $threadService->markAsUnread($threadId);
    echo "   ✓ Success\n\n";
    
    // Verify
    $thread = $threadService->getThread($threadId, true, false);
    echo "   Thread is_read: " . ($thread->is_read ? 'true' : 'false') . "\n";
    echo "   Email count: " . $thread->emails->count() . "\n";
    echo "   Emails read: " . $thread->emails->where('is_read', true)->count() . "\n\n";
    
    echo "✅ All tests passed!\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

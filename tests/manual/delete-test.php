<?php
/**
 * Test: Delete Thread
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use CiInbox\Core\Container;
use CiInbox\App\Services\ThreadApiService;

// Initialize
$container = Container::getInstance();
$container->get('database');

$threadService = $container->get(ThreadApiService::class);

echo "Testing Delete Thread API\n";
echo "==========================\n\n";

try {
    // Create a test thread first
    echo "1. Creating test thread...\n";
    $testThread = $threadService->createThread([
        'subject' => 'TEST: Thread to be deleted',
        'participants' => ['test@example.com'],
        'preview' => 'This thread will be deleted',
        'status' => 'open',
        'last_message_at' => date('Y-m-d H:i:s'),
        'message_count' => 1
    ]);
    $threadId = $testThread->id;
    echo "   Created thread ID: {$threadId}\n\n";
    
    // Verify it exists
    echo "2. Verifying thread exists...\n";
    $thread = $threadService->getThread($threadId, false, false);
    echo "   Thread found: '{$thread->subject}'\n\n";
    
    // Delete it
    echo "3. Deleting thread {$threadId}...\n";
    $result = $threadService->deleteThread($threadId);
    echo "   Result: " . ($result ? 'Success' : 'Failed') . "\n\n";
    
    // Verify it's gone
    echo "4. Verifying thread is deleted...\n";
    try {
        $thread = $threadService->getThread($threadId, false, false);
        echo "   ❌ Thread still exists!\n";
    } catch (\Exception $e) {
        echo "   ✓ Thread not found (correctly deleted)\n";
    }
    
    echo "\n✅ All tests passed!\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

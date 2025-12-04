<?php
/**
 * Test: Archive Thread
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use CiInbox\Core\Container;
use CiInbox\App\Services\ThreadApiService;

// Initialize
$container = Container::getInstance();
$container->get('database');

$threadService = $container->get(ThreadApiService::class);

$threadId = 53;

echo "Testing Archive Thread API\n";
echo "===========================\n\n";

try {
    // Get thread before
    $thread = $threadService->getThread($threadId, false, false);
    echo "Before: Thread {$threadId} status = '{$thread->status}'\n\n";
    
    // Archive thread
    echo "Archiving thread {$threadId}...\n";
    $updated = $threadService->updateThread($threadId, ['status' => 'archived']);
    echo "✓ Success\n\n";
    
    // Get thread after
    $thread = $threadService->getThread($threadId, false, false);
    echo "After: Thread {$threadId} status = '{$thread->status}'\n\n";
    
    // Restore to open
    echo "Restoring to 'open'...\n";
    $threadService->updateThread($threadId, ['status' => 'open']);
    echo "✓ Restored\n\n";
    
    echo "✅ All tests passed!\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

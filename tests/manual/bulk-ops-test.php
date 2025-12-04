<?php
/**
 * Test: Bulk Operations
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use CiInbox\Core\Container;
use CiInbox\App\Services\ThreadBulkService;

// Initialize
$container = Container::getInstance();
$container->get('database');

$bulkService = $container->get(ThreadBulkService::class);

// Use existing thread IDs
$threadIds = [53, 54, 55];

echo "Testing Bulk Operations API\n";
echo "============================\n\n";

try {
    // Test 1: Bulk Set Status (Mark as Read)
    echo "1. Bulk Mark as Read (thread_ids: " . implode(', ', $threadIds) . ")...\n";
    $result = $bulkService->bulkSetStatus($threadIds, 'open', true); // is_read = true
    echo "   Updated: " . $result['updated'] . " threads\n";
    echo "   ✓ Success\n\n";
    
    // Test 2: Bulk Set Status (Mark as Unread)
    echo "2. Bulk Mark as Unread...\n";
    $result = $bulkService->bulkSetStatus($threadIds, 'open', false); // is_read = false
    echo "   Updated: " . $result['updated'] . " threads\n";
    echo "   ✓ Success\n\n";
    
    // Test 3: Bulk Archive
    echo "3. Bulk Archive...\n";
    $result = $bulkService->bulkSetStatus($threadIds, 'archived');
    echo "   Updated: " . $result['updated'] . " threads\n";
    echo "   ✓ Success\n\n";
    
    // Restore to open
    echo "4. Restoring to 'open'...\n";
    $result = $bulkService->bulkSetStatus($threadIds, 'open');
    echo "   Updated: " . $result['updated'] . " threads\n";
    echo "   ✓ Restored\n\n";
    
    // Test 4: Bulk Delete (create test threads first)
    echo "5. Creating test threads for bulk delete...\n";
    $testIds = [];
    for ($i = 1; $i <= 3; $i++) {
        $thread = \CiInbox\App\Models\Thread::create([
            'subject' => "TEST BULK DELETE {$i}",
            'participants' => ['test@example.com'],
            'preview' => 'Test',
            'status' => 'open',
            'last_message_at' => date('Y-m-d H:i:s'),
            'message_count' => 1
        ]);
        $testIds[] = $thread->id;
    }
    echo "   Created threads: " . implode(', ', $testIds) . "\n\n";
    
    echo "6. Bulk Delete...\n";
    $result = $bulkService->bulkDelete($testIds);
    echo "   Deleted: " . $result['deleted'] . " threads\n";
    echo "   ✓ Success\n\n";
    
    echo "✅ All bulk operations tests passed!\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

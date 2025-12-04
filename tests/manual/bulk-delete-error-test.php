<?php
/**
 * Test Bulk Delete with detailed error logging
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use CiInbox\Core\Container;
use CiInbox\App\Services\ThreadBulkService;

echo "=== Bulk Delete Error Test ===\n\n";

try {
    $container = Container::getInstance();
    $bulkService = $container->get(ThreadBulkService::class);
    
    // Create test threads first
    echo "Creating test threads...\n";
    $pdo = $container->get('database')->getConnection()->getPdo();
    
    for ($i = 1; $i <= 3; $i++) {
        $pdo->exec("INSERT INTO threads (subject, status, last_message_at, created_at, updated_at) 
                    VALUES ('Bulk Delete Test $i', 'open', NOW(), NOW(), NOW())");
    }
    
    $testIds = $pdo->query("SELECT id FROM threads WHERE subject LIKE 'Bulk Delete Test%' ORDER BY id DESC LIMIT 3")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Test thread IDs: " . implode(', ', $testIds) . "\n\n";
    
    // Try bulk delete
    echo "Attempting bulk delete...\n";
    $result = $bulkService->bulkDelete($testIds);
    
    echo "\n✅ Success!\n";
    echo "Result: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    
} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

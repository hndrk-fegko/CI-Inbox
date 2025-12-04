<?php
/**
 * Direct Bulk Delete Test - bypasses routing
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/bootstrap/app.php';

use App\Services\ThreadBulkService;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

echo "=== Direct Bulk Delete Test ===\n\n";

try {
    // Setup logger
    $logger = new Logger('test');
    $logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));
    
    // Get container
    $container = require __DIR__ . '/../../src/bootstrap/container.php';
    $bulkService = $container->get(ThreadBulkService::class);
    
    // Test with thread IDs that exist
    $threadIds = [53, 54, 55];
    
    echo "Testing bulk delete with threads: " . implode(', ', $threadIds) . "\n";
    
    $result = $bulkService->bulkDelete($threadIds);
    
    echo "\nResult: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    echo "\n✅ Direct bulk delete successful!\n";
    
} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

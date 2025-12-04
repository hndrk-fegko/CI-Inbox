<?php
/**
 * Test Search Functionality
 * 
 * Tests thread search functionality with German umlauts.
 * Usage: php tests/manual/test-search.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use CiInbox\Core\Container;

// Initialize
$container = Container::getInstance();
$container->get('database');

// Get service
$service = $container->get(CiInbox\App\Services\ThreadService::class);

// Test search
echo "=== Testing Search Functionality ===" . PHP_EOL . PHP_EOL;

$result = $service->listThreads(['search' => 'wichtige', 'limit' => 10]);

echo "Total returned: " . $result['total'] . PHP_EOL;
echo "Threads:" . PHP_EOL;

foreach ($result['threads'] as $thread) {
    echo "  [{$thread['id']}] {$thread['subject']}" . PHP_EOL;
}

echo PHP_EOL . "Expected: Only threads with 'wichtige' in subject" . PHP_EOL;

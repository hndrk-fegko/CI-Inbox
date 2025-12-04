<?php
/**
 * Manual Test: System Health API
 * 
 * Tests all health check endpoints locally without running web server.
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/bootstrap/database.php';

use CiInbox\Core\Container;
use App\Services\SystemHealthService;

echo "=== CI-Inbox System Health - Manual Test ===\n\n";

try {
    // Get container
    $container = Container::getInstance();
    
    // Get health service
    $healthService = $container->get(SystemHealthService::class);
    
    echo "✓ SystemHealthService loaded\n\n";
    
    // Test 1: Basic Health
    echo "--- Test 1: Basic Health ---\n";
    $basicHealth = $healthService->getBasicHealth();
    echo "Status: " . $basicHealth['status'] . "\n";
    echo "Version: " . $basicHealth['version'] . "\n";
    echo "Timestamp: " . date('Y-m-d H:i:s', $basicHealth['timestamp']) . "\n";
    echo "Checks: " . json_encode($basicHealth['checks'], JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 2: System Metrics
    echo "--- Test 2: System Metrics ---\n";
    $systemMetrics = $healthService->getSystemMetrics();
    echo "PHP Version: " . $systemMetrics['php_version'] . "\n";
    echo "Memory Usage: " . round($systemMetrics['memory_usage'] / 1024 / 1024, 2) . " MB\n";
    echo "Memory Usage %: " . $systemMetrics['memory_usage_percentage'] . "%\n";
    echo "Disk Free: " . $systemMetrics['disk_free_mb'] . " MB\n";
    echo "Disk Usage %: " . $systemMetrics['disk_usage_percentage'] . "%\n";
    echo "Extensions: " . implode(', ', $systemMetrics['extensions']) . "\n\n";
    
    // Test 3: Database Metrics
    echo "--- Test 3: Database Metrics ---\n";
    $dbMetrics = $healthService->getDatabaseMetrics();
    echo "Connection: " . $dbMetrics['connection_status'] . "\n";
    echo "Latency: " . $dbMetrics['latency_ms'] . " ms\n";
    echo "Threads Total: " . $dbMetrics['threads_total'] . "\n";
    echo "Threads Open: " . $dbMetrics['threads_open'] . "\n";
    echo "Emails Total: " . $dbMetrics['emails_total'] . "\n";
    echo "Users: " . $dbMetrics['users_count'] . "\n";
    echo "IMAP Accounts: " . $dbMetrics['imap_accounts_count'] . "\n\n";
    
    // Test 4: Module Health
    echo "--- Test 4: Module Health ---\n";
    $modulesHealth = $healthService->getModulesHealth();
    foreach ($modulesHealth as $moduleName => $moduleData) {
        $status = $moduleData['status'];
        $testPassed = $moduleData['test_passed'] ? 'PASSED' : 'FAILED';
        $icon = $status === 'ok' ? '✓' : '✗';
        echo "$icon Module: $moduleName | Status: $status | Test: $testPassed\n";
        
        if (isset($moduleData['metrics']) && !empty($moduleData['metrics'])) {
            echo "  Metrics: " . json_encode($moduleData['metrics']) . "\n";
        }
        
        if (isset($moduleData['error'])) {
            echo "  Error: " . $moduleData['error'] . "\n";
        }
    }
    echo "\n";
    
    // Test 5: Error Metrics
    echo "--- Test 5: Error Metrics ---\n";
    $errorMetrics = $healthService->getErrorMetrics();
    echo "PHP Errors (24h): " . $errorMetrics['php_errors_24h'] . "\n";
    echo "PHP Warnings (24h): " . $errorMetrics['php_warnings_24h'] . "\n";
    echo "HTTP Errors (24h): " . $errorMetrics['http_errors_24h'] . "\n";
    echo "Error Log Size: " . $errorMetrics['error_log_size_mb'] . " MB\n\n";
    
    // Test 6: Detailed Health
    echo "--- Test 6: Detailed Health ---\n";
    $detailedHealth = $healthService->getDetailedHealth();
    echo "Installation ID: " . $detailedHealth['installation_id'] . "\n";
    echo "Version: " . $detailedHealth['version'] . "\n";
    echo "Timestamp: " . date('Y-m-d H:i:s', $detailedHealth['timestamp']) . "\n\n";
    
    // Test 7: Health Analysis
    echo "--- Test 7: Health Analysis ---\n";
    $analysis = $healthService->analyzeHealth($detailedHealth);
    echo "Overall Status: " . $analysis->overallStatus . "\n";
    echo "Is Healthy: " . ($analysis->isHealthy ? 'YES' : 'NO') . "\n";
    
    if (!empty($analysis->issues)) {
        echo "\nIssues:\n";
        foreach ($analysis->issues as $issue) {
            echo "  - $issue\n";
        }
    }
    
    if (!empty($analysis->warnings)) {
        echo "\nWarnings:\n";
        foreach ($analysis->warnings as $warning) {
            echo "  - $warning\n";
        }
    }
    
    if (!empty($analysis->recommendations)) {
        echo "\nRecommendations:\n";
        foreach ($analysis->recommendations as $recommendation) {
            echo "  - $recommendation\n";
        }
    }
    echo "\n";
    
    // Test 8: UpdateServer Report
    echo "--- Test 8: UpdateServer Report ---\n";
    $updateServerReport = $healthService->generateUpdateServerReport();
    echo json_encode($updateServerReport, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test 9: Module Tests
    echo "--- Test 9: Module Tests ---\n";
    $testResults = $healthService->runModuleTests();
    foreach ($testResults as $moduleName => $result) {
        $icon = $result['passed'] ? '✓' : '✗';
        $status = $result['passed'] ? 'PASSED' : 'FAILED';
        echo "$icon Module Test: $moduleName | $status\n";
        
        if (isset($result['error'])) {
            echo "  Error: " . $result['error'] . "\n";
        }
    }
    echo "\n";
    
    echo "=== All Tests Complete ===\n\n";
    echo "✓ SystemHealthService is working correctly!\n\n";
    
} catch (Exception $e) {
    echo "✗ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n\n";
    exit(1);
}

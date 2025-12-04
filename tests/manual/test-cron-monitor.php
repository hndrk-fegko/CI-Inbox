<?php
/**
 * Test Cron Monitor API
 */

// Test status endpoint
$ch = curl_init('http://ci-inbox.local/api/admin/cron/status');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "=== Cron Status Endpoint ===\n";
echo "HTTP {$httpCode}\n";
echo $response . "\n\n";

// Insert test execution
echo "=== Inserting Test Execution ===\n";
require_once __DIR__ . '/../../vendor/autoload.php';
use CiInbox\Modules\Config\ConfigService;
$config = new ConfigService(__DIR__ . '/../../');
require_once __DIR__ . '/../../src/bootstrap/database.php';
initDatabase($config);

use CiInbox\App\Models\CronExecution;
CronExecution::create([
    'accounts_polled' => 3,
    'new_emails_found' => 5,
    'duration_ms' => 2500,
    'status' => 'success'
]);
echo "✅ Test execution created\n\n";

// Test status again
$ch = curl_init('http://ci-inbox.local/api/admin/cron/status');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
curl_close($ch);

echo "=== Status After Test Execution ===\n";
echo $response . "\n";

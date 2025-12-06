<?php
/**
 * Test Signature API endpoints
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/bootstrap/database.php';

use CiInbox\Modules\Config\ConfigService;

$config = new ConfigService(__DIR__ . '/../../');
initDatabase($config);

echo "=== Testing Signature API ===" . PHP_EOL . PHP_EOL;

// Get container
$containerConfig = require __DIR__ . '/../../src/config/container.php';
$container = new DI\Container($containerConfig);
$signatureService = $container->get(\App\Services\SignatureService::class);

// Test 1: Check SMTP status
echo "1. Check SMTP Status:" . PHP_EOL;
$smtpConfigured = $signatureService->isSmtpConfigured();
echo "   SMTP Configured: " . ($smtpConfigured ? 'Yes' : 'No') . PHP_EOL . PHP_EOL;

// Test 2: Create personal signature
echo "2. Create Personal Signature:" . PHP_EOL;
$result = $signatureService->createPersonalSignature(1, [
    'name' => 'Test Signature',
    'content' => "Best regards,\nJohn Doe\ntest@ci-inbox.local",
    'is_default' => true
]);
echo "   Success: " . ($result['success'] ? 'Yes' : 'No') . PHP_EOL;
if ($result['success']) {
    echo "   ID: " . $result['signature']['id'] . PHP_EOL;
    echo "   Name: " . $result['signature']['name'] . PHP_EOL;
}
echo PHP_EOL;

// Test 3: Get all personal signatures
echo "3. Get Personal Signatures:" . PHP_EOL;
$result = $signatureService->getPersonalSignatures(1);
echo "   Success: " . ($result['success'] ? 'Yes' : 'No') . PHP_EOL;
echo "   Count: " . count($result['signatures']) . PHP_EOL;
foreach ($result['signatures'] as $sig) {
    echo "   - " . $sig['name'] . ($sig['is_default'] ? ' (default)' : '') . PHP_EOL;
}
echo PHP_EOL;

// Test 4: Create global signature (admin)
echo "4. Create Global Signature:" . PHP_EOL;
$result = $signatureService->createGlobalSignature([
    'name' => 'Company Signature',
    'content' => "Best regards,\nCI-Inbox Team\nsupport@ci-inbox.local",
    'is_default' => true
]);
echo "   Success: " . ($result['success'] ? 'Yes' : 'No') . PHP_EOL;
if ($result['success']) {
    echo "   ID: " . $result['signature']['id'] . PHP_EOL;
    echo "   Name: " . $result['signature']['name'] . PHP_EOL;
}
echo PHP_EOL;

// Test 5: Get all global signatures
echo "5. Get Global Signatures:" . PHP_EOL;
$result = $signatureService->getGlobalSignatures();
echo "   Success: " . ($result['success'] ? 'Yes' : 'No') . PHP_EOL;
echo "   Count: " . count($result['signatures']) . PHP_EOL;
foreach ($result['signatures'] as $sig) {
    echo "   - " . $sig['name'] . ($sig['is_default'] ? ' (default)' : '') . PHP_EOL;
}
echo PHP_EOL;

echo "✅ All tests completed!" . PHP_EOL;

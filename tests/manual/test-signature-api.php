<?php
/**
 * Test Signature API Dependencies
 * 
 * Verifies that all Signature-related classes can be instantiated
 * Usage: php tests/manual/test-signature-api.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

// Load .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

use CiInbox\Modules\Config\ConfigService;
use CiInbox\Modules\Logger\LoggerService;

echo "=== Signature API Test ===" . PHP_EOL . PHP_EOL;

// Test 1: Initialize database
echo "TEST 1: Database initialization..." . PHP_EOL;
try {
    $config = new ConfigService(__DIR__ . '/../../');
    require_once __DIR__ . '/../../src/bootstrap/database.php';
    initDatabase($config);
    echo "✅ Database initialized" . PHP_EOL . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Database init failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Test 2: Model
echo "TEST 2: Signature Model loading..." . PHP_EOL;
try {
    if (!class_exists(\CiInbox\App\Models\Signature::class)) {
        throw new Exception("Signature Model class not found");
    }
    echo "✅ Signature Model class exists" . PHP_EOL . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Model error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Test 3: Logger
echo "TEST 3: Logger initialization..." . PHP_EOL;
try {
    $logger = new LoggerService(__DIR__ . '/../../logs/');
    echo "✅ Logger initialized" . PHP_EOL . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Logger error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Test 4: Repository
echo "TEST 4: SignatureRepository instantiation..." . PHP_EOL;
try {
    $repository = new \App\Repositories\SignatureRepository($logger);
    echo "✅ SignatureRepository instantiated" . PHP_EOL . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Repository error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Test 5: Service
echo "TEST 5: SignatureService instantiation..." . PHP_EOL;
try {
    $service = new \App\Services\SignatureService($repository, $logger);
    echo "✅ SignatureService instantiated" . PHP_EOL . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Service error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Test 6: Controller
echo "TEST 6: SignatureController instantiation..." . PHP_EOL;
try {
    $controller = new \App\Controllers\SignatureController($service);
    echo "✅ SignatureController instantiated" . PHP_EOL . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Controller error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Test 7: Fetch global signatures
echo "TEST 7: Fetching global signatures from database..." . PHP_EOL;
try {
    $signatures = $service->getGlobalSignatures();
    echo "✅ Fetched " . count($signatures) . " global signatures" . PHP_EOL;
    foreach ($signatures as $sig) {
        echo "  - ID: {$sig->id}, Name: {$sig->name}" . PHP_EOL;
    }
    echo PHP_EOL;
} catch (Exception $e) {
    echo "❌ Fetch error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Test 8: SMTP config check
echo "TEST 8: SMTP configuration check..." . PHP_EOL;
try {
    $isConfigured = $repository->isSmtpConfigured();
    echo "✅ SMTP configured: " . ($isConfigured ? 'Yes' : 'No') . PHP_EOL . PHP_EOL;
} catch (Exception $e) {
    echo "❌ SMTP check error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

echo "=== All tests passed! ===" . PHP_EOL;

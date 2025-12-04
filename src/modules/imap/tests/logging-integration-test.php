<?php
/**
 * Logging Integration Test
 * 
 * Tests logging integration across all modules:
 * - Config
 * - Encryption
 * - IMAP Client
 * - Email Parser
 */

require_once __DIR__ . '/../../../../vendor/autoload.php';

use CiInbox\Modules\Config\ConfigService;
use CiInbox\Modules\Logger\LoggerService;
use CiInbox\Modules\Encryption\EncryptionService;
use CiInbox\Modules\Imap\ImapClient;
use CiInbox\Modules\Imap\Parser\EmailParser;

echo "=== Logging Integration Test ===\n\n";

// 1. Initialize Logger
echo "1. Initializing Logger...\n";
$logger = new LoggerService(__DIR__ . '/../../../../logs');
$logger->info('Logging integration test started');
echo "✓ Logger initialized\n\n";

// 2. Test ConfigService with Logging
echo "2. Testing ConfigService with Logging...\n";
$config = new ConfigService(
    envPath: __DIR__ . '/../../../../',
    configPath: __DIR__ . '/../../../../src/config',
    logger: $logger
);

// Access some config values to trigger logging
try {
    $dbHost = $config->getString('database.host');
    echo "✓ Config loaded: database.host = $dbHost\n";
} catch (Exception $e) {
    echo "✗ Config error: " . $e->getMessage() . "\n";
}
echo "\n";

// 3. Test EncryptionService with Logging
echo "3. Testing EncryptionService with Logging...\n";
try {
    $encryption = new EncryptionService($config, $logger);
    
    $testData = "CI-Inbox Test Password 123";
    $encrypted = $encryption->encrypt($testData);
    echo "✓ Encrypted test data (length: " . strlen($encrypted) . ")\n";
    
    $decrypted = $encryption->decrypt($encrypted);
    echo "✓ Decrypted test data: " . ($decrypted === $testData ? "MATCH" : "MISMATCH") . "\n";
} catch (Exception $e) {
    echo "✗ Encryption error: " . $e->getMessage() . "\n";
}
echo "\n";

// 4. Test ImapClient with Logging
echo "4. Testing ImapClient with Logging...\n";
try {
    $imap = new ImapClient($logger, $config);
    
    // Try connecting to Mercury
    $imap->connect(
        host: 'localhost',
        port: 143,
        username: 'admin@ci-inbox.local',
        password: 'admin',
        ssl: false
    );
    echo "✓ Connected to IMAP server\n";
    
    // Select INBOX
    $imap->selectFolder('INBOX');
    echo "✓ Selected INBOX\n";
    
    // Get message count
    $count = $imap->getMessageCount();
    echo "✓ Message count: $count\n";
    
    // Disconnect
    $imap->disconnect();
    echo "✓ Disconnected from IMAP\n";
} catch (Exception $e) {
    echo "✗ IMAP error: " . $e->getMessage() . "\n";
}
echo "\n";

// 5. Test EmailParser with Logging
echo "5. Testing EmailParser with Logging...\n";
try {
    $imap = new ImapClient($logger, $config);
    $imap->connect('localhost', 143, 'admin@ci-inbox.local', 'admin', false);
    $imap->selectFolder('INBOX');
    
    $messages = $imap->getMessages(limit: 1);
    
    if (count($messages) > 0) {
        $parser = new EmailParser($logger);
        $parsed = $parser->parseMessage($messages[0]);
        
        echo "✓ Parsed email:\n";
        echo "  - Subject: " . $parsed->subject . "\n";
        echo "  - From: " . $parsed->from . "\n";
        echo "  - Attachments: " . count($parsed->attachments) . "\n";
    } else {
        echo "✗ No messages in INBOX\n";
    }
    
    $imap->disconnect();
} catch (Exception $e) {
    echo "✗ Parser error: " . $e->getMessage() . "\n";
}
echo "\n";

// 6. Summary
echo "=== Test Complete ===\n";
echo "Check logs/app-" . date('Y-m-d') . ".log for detailed logging output\n";

$logger->info('Logging integration test completed');

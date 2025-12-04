<?php

/**
 * IMAP Client Manual Test
 * 
 * Tests IMAP client with real IMAP server connection.
 * 
 * Usage:
 *   php src/modules/imap/tests/manual-test.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../../vendor/autoload.php';

use CiInbox\Modules\Imap\ImapClient;
use CiInbox\Modules\Imap\Exceptions\ImapException;
use CiInbox\Modules\Logger\LoggerService;
use CiInbox\Modules\Config\ConfigService;

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../../../');
$dotenv->load();

// Initialize services
$config = new ConfigService(__DIR__ . '/../../../../');
$logger = new LoggerService(
    $config->getString('logger.log_path', 'logs/'),
    $config->getString('logger.log_level', 'debug')
);

echo "\n=== CI-Inbox IMAP Client Test ===\n\n";

// Test 1: Check IMAP extension
echo "1. Checking PHP IMAP extension...\n";
if (!extension_loaded('imap')) {
    echo "   ❌ FAILED: PHP IMAP extension not available\n";
    echo "   Install with: sudo apt-get install php-imap (Linux) or enable in php.ini (Windows)\n";
    exit(1);
}
echo "   ✅ PASSED: IMAP extension available\n\n";

// Get IMAP credentials interactively
echo "Enter IMAP connection details:\n";
echo "Host (e.g., imap.gmail.com): ";
$host = trim(fgets(STDIN));

echo "Port (993 for SSL, 143 for non-SSL): ";
$port = (int)trim(fgets(STDIN));

echo "Username (usually email address): ";
$username = trim(fgets(STDIN));

echo "Password: ";
// Hide password input (Unix/Linux/Mac only)
if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
    system('stty -echo');
}
$password = trim(fgets(STDIN));
if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
    system('stty echo');
}
echo "\n\n";

echo "Use SSL? (y/n, default: y): ";
$sslInput = trim(fgets(STDIN));
$ssl = empty($sslInput) || strtolower($sslInput) === 'y';

echo "\n--- Testing IMAP Connection ---\n\n";

try {
    // Create IMAP client
    $client = new ImapClient($logger, $config);

    // Test 2: Connect
    echo "2. Connecting to {$host}:{$port}...\n";
    $client->connect($host, $port, $username, $password, $ssl);
    echo "   ✅ PASSED: Connected successfully\n\n";

    // Test 3: Get folders
    echo "3. Fetching folders...\n";
    $folders = $client->getFolders();
    echo "   ✅ PASSED: Found " . count($folders) . " folder(s)\n";
    foreach ($folders as $folder) {
        echo "      - {$folder}\n";
    }
    echo "\n";

    // Test 4: Select INBOX
    echo "4. Selecting INBOX...\n";
    $client->selectFolder('INBOX');
    echo "   ✅ PASSED: INBOX selected\n";
    echo "   Current folder: " . $client->getCurrentFolder() . "\n\n";

    // Test 5: Get message count
    echo "5. Getting message count...\n";
    $count = $client->getMessageCount();
    echo "   ✅ PASSED: {$count} message(s) in INBOX\n\n";

    if ($count > 0) {
        // Test 6: Fetch messages
        echo "6. Fetching last 5 messages...\n";
        $messages = $client->getMessages(5);
        echo "   ✅ PASSED: Fetched " . count($messages) . " message(s)\n\n";

        if (count($messages) > 0) {
            $firstMessage = $messages[0];

            echo "--- First Message Details ---\n";
            echo "UID:          " . $firstMessage->getUid() . "\n";
            echo "Message-ID:   " . $firstMessage->getMessageId() . "\n";
            echo "Subject:      " . $firstMessage->getSubject() . "\n";
            
            $from = $firstMessage->getFrom();
            echo "From:         " . $from['email'] . " (" . $from['name'] . ")\n";
            
            $to = $firstMessage->getTo();
            echo "To:           " . (count($to) > 0 ? $to[0]['email'] : 'N/A') . "\n";
            
            echo "Date:         " . $firstMessage->getDate()->format('Y-m-d H:i:s') . "\n";
            echo "Size:         " . number_format($firstMessage->getSize()) . " bytes\n";
            echo "Unread:       " . ($firstMessage->isUnread() ? 'Yes' : 'No') . "\n";
            echo "Flagged:      " . ($firstMessage->isFlagged() ? 'Yes' : 'No') . "\n";
            echo "Attachments:  " . ($firstMessage->hasAttachments() ? 'Yes (' . count($firstMessage->getAttachments()) . ')' : 'No') . "\n";
            
            echo "\nBody (first 200 chars):\n";
            $bodyText = $firstMessage->getBodyText();
            echo substr($bodyText, 0, 200) . (strlen($bodyText) > 200 ? '...' : '') . "\n";
            echo "\n";

            // Test 7: Get single message
            echo "7. Fetching single message (UID: " . $firstMessage->getUid() . ")...\n";
            $singleMessage = $client->getMessage($firstMessage->getUid());
            echo "   ✅ PASSED: Message fetched\n";
            echo "   Subject: " . $singleMessage->getSubject() . "\n\n";

            // Test 8: Mark as read (optional - ask user)
            echo "8. Mark message as read? (y/n, default: n): ";
            $markRead = trim(fgets(STDIN));
            if (strtolower($markRead) === 'y') {
                $client->markAsRead($firstMessage->getUid());
                echo "   ✅ PASSED: Message marked as read\n\n";
            } else {
                echo "   ⏭️  SKIPPED: Not marking as read\n\n";
            }

            // Test 9: Move message (optional - ask user)
            echo "9. Move message to another folder? (enter folder name or press Enter to skip): ";
            $targetFolder = trim(fgets(STDIN));
            if (!empty($targetFolder)) {
                try {
                    $client->moveMessage($firstMessage->getUid(), $targetFolder);
                    echo "   ✅ PASSED: Message moved to {$targetFolder}\n\n";
                } catch (ImapException $e) {
                    echo "   ❌ FAILED: " . $e->getMessage() . "\n\n";
                }
            } else {
                echo "   ⏭️  SKIPPED: Not moving message\n\n";
            }
        }
    } else {
        echo "   ℹ️  No messages in INBOX - skipping message tests\n\n";
    }

    // Test 10: Disconnect
    echo "10. Disconnecting...\n";
    $client->disconnect();
    echo "   ✅ PASSED: Disconnected\n\n";

    echo "===========================================\n";
    echo "✅ ALL TESTS PASSED\n";
    echo "===========================================\n\n";

} catch (ImapException $e) {
    echo "\n❌ IMAP ERROR: " . $e->getMessage() . "\n\n";
    exit(1);
} catch (\Throwable $e) {
    echo "\n❌ UNEXPECTED ERROR: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    exit(1);
}

<?php
/**
 * Mercury Quick Test
 * 
 * Schneller Test für Mercury SMTP + IMAP
 * Optimiert für XAMPP Mercury Server
 * 
 * Usage: php src/modules/imap/tests/mercury-quick-test.php
 */

declare(strict_types=1);
require_once __DIR__ . '/../../../../vendor/autoload.php';

use CiInbox\Modules\Imap\ImapClient;
use CiInbox\Modules\Imap\Exceptions\ImapException;
use CiInbox\Modules\Logger\LoggerService;
use CiInbox\Modules\Config\ConfigService;

// ANSI Colors
const C_GREEN = "\033[32m";
const C_RED = "\033[31m";
const C_YELLOW = "\033[33m";
const C_BLUE = "\033[34m";
const C_CYAN = "\033[36m";
const C_RESET = "\033[0m";

function ok(string $msg) { echo "   " . C_GREEN . "✅ " . $msg . C_RESET . "\n"; }
function fail(string $msg) { echo "   " . C_RED . "❌ " . $msg . C_RESET . "\n"; }
function info(string $msg) { echo "   " . C_CYAN . "ℹ️  " . $msg . C_RESET . "\n"; }
function warn(string $msg) { echo "   " . C_YELLOW . "⚠️  " . $msg . C_RESET . "\n"; }
function printHeader(string $title) { echo "\n" . C_BLUE . "=== {$title} ===" . C_RESET . "\n\n"; }

/**
 * Send email via SMTP (raw socket)
 */
function sendViaSMTP(string $from, string $to, string &$msgId, string &$uniqueSubject): bool {
    try {
        $msgId = '<test-' . uniqid() . '@mercury.local>';
        $uniqueSubject = 'CI-Inbox-Test-' . uniqid();
        $body = "Test email sent at " . date('Y-m-d H:i:s') . "\nMessage-ID: {$msgId}";
        
        // Connect (Mercury = no SSL on localhost)
        $socket = @fsockopen('tcp://localhost', 25, $errno, $errstr, 5);
        if (!$socket) {
            fail("SMTP Connect failed: {$errstr}");
            return false;
        }
        
        // Read greeting
        $response = fgets($socket);
        if (!str_starts_with($response, '220')) {
            fail("SMTP not ready: {$response}");
            fclose($socket);
            return false;
        }
        
        // EHLO
        fwrite($socket, "EHLO mercury.test\r\n");
        while ($line = fgets($socket)) {
            if (substr($line, 3, 1) === ' ') break;
        }
        
        // No AUTH needed for Mercury localhost
        
        // MAIL FROM
        fwrite($socket, "MAIL FROM:<{$from}>\r\n");
        $response = fgets($socket);
        if (!str_starts_with($response, '250')) {
            fail("MAIL FROM rejected: {$response}");
            fclose($socket);
            return false;
        }
        
        // RCPT TO
        fwrite($socket, "RCPT TO:<{$to}>\r\n");
        $response = fgets($socket);
        if (!str_starts_with($response, '250')) {
            fail("RCPT TO rejected: {$response}");
            fclose($socket);
            return false;
        }
        
        // DATA
        fwrite($socket, "DATA\r\n");
        $response = fgets($socket);
        if (!str_starts_with($response, '354')) {
            fail("DATA rejected: {$response}");
            fclose($socket);
            return false;
        }
        
        // Send message
        $headers = [
            "From: {$from}",
            "To: {$to}",
            "Subject: {$uniqueSubject}",
            "Message-ID: {$msgId}",
            "Date: " . date('r'),
            "Content-Type: text/plain; charset=UTF-8",
        ];
        
        fwrite($socket, implode("\r\n", $headers) . "\r\n\r\n");
        fwrite($socket, $body . "\r\n");
        fwrite($socket, ".\r\n");
        
        $response = fgets($socket);
        if (!str_starts_with($response, '250')) {
            fail("Message rejected: {$response}");
            fclose($socket);
            return false;
        }
        
        // QUIT
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        
        ok("Email sent via SMTP");
        info("Message-ID: {$msgId}");
        return true;
        
    } catch (Exception $e) {
        fail("SMTP Exception: " . $e->getMessage());
        return false;
    }
}

/**
 * Find email in IMAP by subject
 */
function findInIMAP(ImapClient $imap, string $uniqueSubject, int $maxWait = 10): ?string {
    $start = time();
    
    info("Waiting for email (max {$maxWait}s)...");
    
    while ((time() - $start) < $maxWait) {
        try {
            $messages = $imap->getMessages(50, false);
            
            foreach ($messages as $msg) {
                if ($msg->getSubject() === $uniqueSubject) {
                    ok("Email found after " . (time() - $start) . "s");
                    return (string)$msg->getUid();
                }
            }
            
            sleep(1);
            
        } catch (ImapException $e) {
            warn("IMAP search error: " . $e->getMessage());
            sleep(1);
        }
    }
    
    fail("Email not found after {$maxWait}s");
    return null;
}

// ============================================================================
// MAIN
// ============================================================================

printHeader('Mercury Quick Test');

echo C_YELLOW . "Testing Mercury Mail Server (XAMPP)\n" . C_RESET;
echo "Default credentials: testuser / testpass123\n\n";

// Configuration
$smtpHost = 'localhost';
$smtpPort = 25;
$imapHost = 'localhost';
$imapPort = 143;
$username = 'testuser';
$password = 'testpass123';
$emailAddress = 'testuser@localhost';

echo C_CYAN . "Config:\n" . C_RESET;
echo "  SMTP: {$smtpHost}:{$smtpPort}\n";
echo "  IMAP: {$imapHost}:{$imapPort}\n";
echo "  User: {$username}\n";
echo "  Email: {$emailAddress}\n";

$msgId = '';
$uniqueSubject = '';
$foundUid = null;

try {
    // TEST 1: Send via SMTP
    printHeader('Step 1: Send Email via SMTP');
    
    if (!sendViaSMTP($emailAddress, $emailAddress, $msgId, $uniqueSubject)) {
        throw new Exception('SMTP test failed');
    }
    
    // TEST 2: Connect to IMAP
    printHeader('Step 2: Connect to IMAP');
    
    $logger = new LoggerService(__DIR__ . '/../../../../logs');
    $config = new ConfigService(__DIR__ . '/../../../../');
    $imap = new ImapClient($logger, $config);
    
    $imap->connect($imapHost, $imapPort, $username, $password, false);
    ok("Connected to IMAP {$imapHost}:{$imapPort}");
    
    // TEST 3: List folders
    printHeader('Step 3: List Mailboxes');
    
    $folders = $imap->getFolders();
    ok("Found " . count($folders) . " folders");
    
    foreach ($folders as $folder) {
        echo "      📁 {$folder}\n";
    }
    
    // TEST 4: Try different folder names for INBOX
    printHeader('Step 4: Find INBOX');
    
    $inboxCandidates = ['INBOX', 'Inbox', 'inbox', '_INBOX_'];
    $inboxFound = false;
    $inboxName = '';
    
    foreach ($inboxCandidates as $candidate) {
        try {
            $imap->selectFolder($candidate);
            $count = $imap->getMessageCount();
            ok("Selected '{$candidate}' ({$count} messages)");
            $inboxFound = true;
            $inboxName = $candidate;
            break;
        } catch (ImapException $e) {
            info("'{$candidate}' not found");
        }
    }
    
    if (!$inboxFound) {
        throw new Exception('No INBOX folder found! Try: ' . implode(', ', array_map('strval', $folders)));
    }
    
    // TEST 5: Wait for email
    printHeader('Step 5: Search for Test Email');
    
    $foundUid = findInIMAP($imap, $uniqueSubject, 15);
    
    if (!$foundUid) {
        warn("Email not found yet. Checking folder contents...");
        
        $messages = $imap->getMessages(10, false);
        echo "\n   Last 10 messages in {$inboxName}:\n";
        foreach ($messages as $msg) {
            echo "      • " . $msg->getSubject() . " (ID: " . $msg->getMessageId() . ")\n";
        }
        
        throw new Exception('Test email not received');
    }
    
    // TEST 6: Read message
    printHeader('Step 6: Read Message Details');
    
    $message = $imap->getMessage($foundUid);
    
    $from = $message->getFrom();
    $fromStr = is_array($from) ? implode(', ', $from) : $from;
    
    echo "   📧 Subject: " . $message->getSubject() . "\n";
    echo "   👤 From: " . $fromStr . "\n";
    echo "   📅 Date: " . $message->getDate()->format('Y-m-d H:i:s') . "\n";
    echo "   🆔 Message-ID: " . $message->getMessageId() . "\n";
    
    ok("Message details retrieved");
    
    // TEST 7: Mark as read
    printHeader('Step 7: Mark as Read');
    
    $imap->markAsRead((string)$foundUid);
    ok("Message marked as read");
    
    // TEST 8: Delete message
    printHeader('Step 8: Delete Test Message');
    
    $imap->deleteMessage((string)$foundUid);
    ok("Message deleted");
    
    // TEST 9: Disconnect
    $imap->disconnect();
    ok("Disconnected from IMAP");
    
    // ============================================================================
    // SUCCESS
    // ============================================================================
    
    printHeader('✅ ALL TESTS PASSED');
    
    echo C_GREEN . "
🎉 Mercury Configuration is CORRECT! 🎉

✅ SMTP sending works
✅ IMAP connection works
✅ Folder access works ('{$inboxName}')
✅ Email delivery works
✅ Message reading works
✅ Operations (mark, delete) work

Your Mercury server is ready for CI-Inbox!
" . C_RESET . "\n";
    
    // Save config for installer
    $setupConfig = [
        'smtp' => [
            'host' => $smtpHost,
            'port' => $smtpPort,
            'ssl' => false,
            'auth' => false
        ],
        'imap' => [
            'host' => $imapHost,
            'port' => $imapPort,
            'ssl' => false,
            'inbox_folder' => $inboxName
        ],
        'test_user' => [
            'username' => $username,
            'password' => $password,
            'email' => $emailAddress
        ],
        'test_result' => 'success',
        'test_timestamp' => date('Y-m-d H:i:s')
    ];
    
    file_put_contents(__DIR__ . '/mercury-config.json', json_encode($setupConfig, JSON_PRETTY_PRINT));
    info("Config saved to mercury-config.json");
    
    exit(0);
    
} catch (ImapException $e) {
    printHeader('❌ IMAP Error');
    fail($e->getMessage());
    
    echo "\n" . C_YELLOW . "Debug Info:\n" . C_RESET;
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    if (isset($imap) && $imap->isConnected()) {
        $imap->disconnect();
    }
    
    exit(1);
    
} catch (Exception $e) {
    printHeader('❌ Test Failed');
    fail($e->getMessage());
    
    echo "\n" . C_YELLOW . "Debug Info:\n" . C_RESET;
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    exit(1);
}


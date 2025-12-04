<?php

/**
 * SMTP + IMAP Round-Trip Test
 * 
 * Tests complete mail flow:
 * 1. Send test email via SMTP
 * 2. Receive email via IMAP
 * 3. Move email to test folder
 * 4. Delete email
 * 
 * Perfect for setup wizards and health checks.
 * 
 * Usage:
 *   php src/modules/imap/tests/smtp-imap-roundtrip-test.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../../vendor/autoload.php';

use CiInbox\Modules\Imap\ImapClient;
use CiInbox\Modules\Imap\Exceptions\ImapException;
use CiInbox\Modules\Logger\LoggerService;
use CiInbox\Modules\Config\ConfigService;

// ANSI Colors
const COLOR_GREEN = "\033[32m";
const COLOR_RED = "\033[31m";
const COLOR_YELLOW = "\033[33m";
const COLOR_BLUE = "\033[34m";
const COLOR_RESET = "\033[0m";

/**
 * Print colored status message
 */
function printStatus(string $message, bool $success): void
{
    $icon = $success ? '✅' : '❌';
    $color = $success ? COLOR_GREEN : COLOR_RED;
    echo "   {$color}{$icon} " . ($success ? 'PASSED' : 'FAILED') . COLOR_RESET . ": {$message}\n";
}

/**
 * Print section header
 */
function printHeader(string $title): void
{
    echo "\n" . COLOR_BLUE . "--- {$title} ---" . COLOR_RESET . "\n\n";
}

/**
 * Get user input
 */
function prompt(string $question, string $default = ''): string
{
    $defaultText = $default ? " (default: {$default})" : '';
    echo COLOR_YELLOW . $question . $defaultText . ": " . COLOR_RESET;
    $input = trim(fgets(STDIN) ?: '');
    return $input === '' ? $default : $input;
}

/**
 * Send test email via SMTP
 */
function sendTestEmail(
    string $smtpHost,
    int $smtpPort,
    string $username,
    string $password,
    string $fromEmail,
    string $toEmail,
    bool $useSsl,
    string &$testMessageId
): bool {
    try {
        // Generate unique message ID
        $testMessageId = '<test-' . uniqid() . '@ci-inbox.test>';
        
        // Build email
        $boundary = uniqid('boundary_');
        $subject = 'CI-Inbox Test Mail - ' . date('Y-m-d H:i:s');
        $body = "This is an automated test email from CI-Inbox.\n\nMessage-ID: {$testMessageId}\n\nIf you receive this, SMTP is working correctly.";
        
        $headers = [
            "From: {$fromEmail}",
            "To: {$toEmail}",
            "Subject: {$subject}",
            "Message-ID: {$testMessageId}",
            "Date: " . date('r'),
            "MIME-Version: 1.0",
            "Content-Type: text/plain; charset=UTF-8",
            "Content-Transfer-Encoding: 8bit"
        ];
        
        // Connect to SMTP
        $protocol = $useSsl ? 'ssl' : 'tcp';
        $socket = @fsockopen("{$protocol}://{$smtpHost}", $smtpPort, $errno, $errstr, 10);
        
        if (!$socket) {
            throw new Exception("Failed to connect to SMTP server: {$errstr} ({$errno})");
        }
        
        // Read greeting
        $response = fgets($socket);
        if (substr($response, 0, 3) !== '220') {
            throw new Exception("SMTP server not ready: {$response}");
        }
        
        // EHLO
        fwrite($socket, "EHLO ci-inbox.test\r\n");
        while ($line = fgets($socket)) {
            if (substr($line, 3, 1) === ' ') break;
        }
        
        // AUTH LOGIN (if credentials provided)
        if ($username !== '' && $password !== '') {
            fwrite($socket, "AUTH LOGIN\r\n");
            fgets($socket);
            
            fwrite($socket, base64_encode($username) . "\r\n");
            fgets($socket);
            
            fwrite($socket, base64_encode($password) . "\r\n");
            $authResponse = fgets($socket);
            
            if (substr($authResponse, 0, 3) !== '235') {
                throw new Exception("SMTP authentication failed: {$authResponse}");
            }
        }
        
        // MAIL FROM
        fwrite($socket, "MAIL FROM:<{$fromEmail}>\r\n");
        $response = fgets($socket);
        if (substr($response, 0, 3) !== '250') {
            throw new Exception("MAIL FROM rejected: {$response}");
        }
        
        // RCPT TO
        fwrite($socket, "RCPT TO:<{$toEmail}>\r\n");
        $response = fgets($socket);
        if (substr($response, 0, 3) !== '250') {
            throw new Exception("RCPT TO rejected: {$response}");
        }
        
        // DATA
        fwrite($socket, "DATA\r\n");
        $response = fgets($socket);
        if (substr($response, 0, 3) !== '354') {
            throw new Exception("DATA command rejected: {$response}");
        }
        
        // Send headers and body
        fwrite($socket, implode("\r\n", $headers) . "\r\n\r\n");
        fwrite($socket, $body . "\r\n");
        fwrite($socket, ".\r\n");
        
        $response = fgets($socket);
        if (substr($response, 0, 3) !== '250') {
            throw new Exception("Message not accepted: {$response}");
        }
        
        // QUIT
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        
        return true;
        
    } catch (Exception $e) {
        echo COLOR_RED . "   ❌ SMTP Error: " . $e->getMessage() . COLOR_RESET . "\n";
        return false;
    }
}

/**
 * Wait for email to arrive in IMAP
 */
function waitForEmail(ImapClient $imap, string $messageId, int $maxWaitSeconds = 30): ?int
{
    $startTime = time();
    $attempts = 0;
    
    echo COLOR_YELLOW . "   ⏳ Waiting for email to arrive (max {$maxWaitSeconds}s)..." . COLOR_RESET . "\n";
    
    while ((time() - $startTime) < $maxWaitSeconds) {
        $attempts++;
        
        try {
            // Search for message by Message-ID
            $messages = $imap->getMessages(100, false); // Get last 100 messages
            
            foreach ($messages as $msg) {
                if ($msg->getMessageId() === $messageId) {
                    echo COLOR_GREEN . "   ✅ Email arrived after {$attempts} attempts (" . (time() - $startTime) . "s)" . COLOR_RESET . "\n";
                    return $msg->getUid();
                }
            }
            
            // Wait before retry
            sleep(1);
            
        } catch (ImapException $e) {
            echo COLOR_YELLOW . "   ⚠️  Retry {$attempts}: " . $e->getMessage() . COLOR_RESET . "\n";
            sleep(1);
        }
    }
    
    return null;
}

// ============================================================================
// MAIN SCRIPT
// ============================================================================

echo COLOR_BLUE . "=== CI-Inbox SMTP + IMAP Round-Trip Test ===" . COLOR_RESET . "\n\n";

// Check IMAP extension
echo "1. Checking PHP IMAP extension...\n";
if (!extension_loaded('imap')) {
    printStatus('PHP IMAP extension not available', false);
    echo "\n   Install with: sudo apt-get install php-imap (Linux) or enable in php.ini (Windows)\n";
    exit(1);
}
printStatus('IMAP extension available', true);

// ============================================================================
// GET CONNECTION DETAILS
// ============================================================================

printHeader('Connection Details');

echo COLOR_YELLOW . "Enter SMTP connection details:\n" . COLOR_RESET;
$smtpHost = prompt('SMTP Host (e.g., smtp.gmail.com)', 'localhost');
$smtpPort = (int)prompt('SMTP Port (25, 465 for SSL, 587 for TLS)', '25');
$smtpSsl = strtolower(prompt('Use SSL? (y/n)', 'n')) === 'y';

echo "\n" . COLOR_YELLOW . "Enter email addresses:\n" . COLOR_RESET;
$fromEmail = prompt('From Email', 'test@localhost');
$toEmail = prompt('To Email (IMAP inbox)', $fromEmail);

echo "\n" . COLOR_YELLOW . "Enter authentication (leave empty for no auth):\n" . COLOR_RESET;
$username = prompt('Username', '');
$password = $username ? prompt('Password', '') : '';

echo "\n" . COLOR_YELLOW . "IMAP settings (if different from SMTP):\n" . COLOR_RESET;
$useSmtpForImap = strtolower(prompt('Use same host/credentials for IMAP? (y/n)', 'y')) === 'y';

if ($useSmtpForImap) {
    $imapHost = $smtpHost;
    $imapPort = 143; // Default IMAP port
    $imapSsl = false;
    $imapUsername = $username ?: explode('@', $toEmail)[0];
    $imapPassword = $password;
} else {
    $imapHost = prompt('IMAP Host', 'localhost');
    $imapPort = (int)prompt('IMAP Port (993 for SSL, 143 for non-SSL)', '143');
    $imapSsl = strtolower(prompt('Use SSL? (y/n)', 'n')) === 'y';
    $imapUsername = prompt('IMAP Username', explode('@', $toEmail)[0]);
    $imapPassword = prompt('IMAP Password', '');
}

// ============================================================================
// START TESTS
// ============================================================================

printHeader('Testing Mail Flow');

$testMessageId = '';
$testUid = null;

try {
    // TEST 1: Send email via SMTP
    echo "2. Sending test email via SMTP...\n";
    $smtpSuccess = sendTestEmail(
        $smtpHost,
        $smtpPort,
        $username,
        $password,
        $fromEmail,
        $toEmail,
        $smtpSsl,
        $testMessageId
    );
    
    if (!$smtpSuccess) {
        throw new Exception('SMTP test failed - cannot continue');
    }
    
    printStatus("Test email sent (Message-ID: {$testMessageId})", true);
    
    // TEST 2: Connect to IMAP
    echo "\n3. Connecting to IMAP {$imapHost}:{$imapPort}...\n";
    
    // Create minimal logger and config for testing
    $logger = new LoggerService(__DIR__ . '/../../../../logs');
    $config = new ConfigService(__DIR__ . '/../../../../');
    
    $imap = new ImapClient($logger, $config);
    $imap->connect($imapHost, $imapPort, $imapUsername, $imapPassword, $imapSsl);
    printStatus('Connected to IMAP successfully', true);
    
    // TEST 3: Select INBOX
    echo "\n4. Selecting INBOX...\n";
    $imap->selectFolder('INBOX');
    printStatus('INBOX selected', true);
    
    // TEST 4: Wait for email to arrive
    echo "\n5. Checking for test email...\n";
    $testUid = waitForEmail($imap, $testMessageId, 30);
    
    if (!$testUid) {
        throw new Exception('Test email not received within 30 seconds');
    }
    
    printStatus("Test email received (UID: {$testUid})", true);
    
    // TEST 5: Get message details
    echo "\n6. Reading message details...\n";
    $message = $imap->getMessage((string)$testUid);
    
    echo COLOR_YELLOW . "   Subject: " . COLOR_RESET . $message->getSubject() . "\n";
    echo COLOR_YELLOW . "   From: " . COLOR_RESET . $message->getFrom() . "\n";
    echo COLOR_YELLOW . "   Date: " . COLOR_RESET . $message->getDate()->format('Y-m-d H:i:s') . "\n";
    
    printStatus('Message details retrieved', true);
    
    // TEST 6: Mark as read
    echo "\n7. Marking message as read...\n";
    $imap->markAsRead((string)$testUid);
    printStatus('Message marked as read', true);
    
    // TEST 7: Delete message
    echo "\n8. Deleting test message...\n";
    $imap->deleteMessage((string)$testUid);
    printStatus('Message deleted (moved to Trash)', true);
    
    // TEST 8: Disconnect
    echo "\n9. Disconnecting from IMAP...\n";
    $imap->disconnect();
    printStatus('Disconnected successfully', true);
    
    // ============================================================================
    // SUCCESS SUMMARY
    // ============================================================================
    
    printHeader('Test Summary');
    
    echo COLOR_GREEN . "🎉 ALL TESTS PASSED! 🎉\n\n" . COLOR_RESET;
    echo "✅ SMTP: Email sent successfully\n";
    echo "✅ IMAP: Connection established\n";
    echo "✅ IMAP: Folder selection working\n";
    echo "✅ IMAP: Email received\n";
    echo "✅ IMAP: Message reading working\n";
    echo "✅ IMAP: Mark as read working\n";
    echo "✅ IMAP: Message deletion working\n";
    echo "✅ IMAP: Disconnect working\n";
    
    echo "\n" . COLOR_BLUE . "Your mail server is configured correctly! ✨" . COLOR_RESET . "\n\n";
    
    exit(0);
    
} catch (ImapException $e) {
    printHeader('IMAP Error');
    echo COLOR_RED . "❌ " . $e->getMessage() . COLOR_RESET . "\n";
    
    if ($imap ?? null) {
        try {
            $imap->disconnect();
        } catch (Exception $e) {
            // Ignore disconnect errors
        }
    }
    
    exit(1);
    
} catch (Exception $e) {
    printHeader('Unexpected Error');
    echo COLOR_RED . "❌ " . $e->getMessage() . COLOR_RESET . "\n";
    echo COLOR_YELLOW . "   File: " . COLOR_RESET . $e->getFile() . ":" . $e->getLine() . "\n";
    
    exit(1);
}

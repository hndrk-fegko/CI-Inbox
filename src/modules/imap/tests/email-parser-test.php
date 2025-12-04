<?php
/**
 * Email Parser Test Script
 * 
 * Tests the email parser with various email types from Mercury/XAMPP.
 * 
 * Usage: php email-parser-test.php
 */

declare(strict_types=1);
require_once __DIR__ . '/../../../../vendor/autoload.php';

use CiInbox\Modules\Imap\ImapClient;
use CiInbox\Modules\Imap\Parser\EmailParser;
use CiInbox\Modules\Logger\LoggerService;
use CiInbox\Modules\Config\ConfigService;

// Initialize services
$logger = new LoggerService(__DIR__ . '/../../../../logs');
$config = new ConfigService(__DIR__ . '/../../../../');
$parser = new EmailParser();

// IMAP credentials (Mercury/XAMPP)
$imapHost = 'localhost';
$imapPort = 143;
$username = 'testuser';
$password = 'testpass123';

echo "═══════════════════════════════════════════════════════════\n";
echo "  E-MAIL PARSER TEST\n";
echo "═══════════════════════════════════════════════════════════\n\n";

try {
    // Connect to IMAP
    echo "→ Connecting to IMAP {$imapHost}:{$imapPort}...\n";
    $imap = new ImapClient($logger, $config);
    $imap->connect($imapHost, $imapPort, $username, $password, false);
    echo "✓ Connected successfully\n\n";
    
    // Select INBOX
    $imap->selectFolder('INBOX');
    
    // Get messages
    $messages = $imap->getMessages(100, false);
    $messageCount = count($messages);
    
    echo "Found {$messageCount} messages in INBOX\n";
    echo "───────────────────────────────────────────────────────────\n\n";
    
    if ($messageCount === 0) {
        echo "⚠ No messages found. Please send some test emails first.\n";
        echo "\nTest email types needed:\n";
        echo "  1. Plain text email\n";
        echo "  2. HTML email\n";
        echo "  3. Email with attachment\n";
        echo "  4. Reply email (with In-Reply-To)\n";
        echo "  5. Email with special characters (ä, ö, ü)\n\n";
        exit(0);
    }
    
    $testResults = [];
    
    // Parse each message
    foreach ($messages as $index => $message) {
        $num = $index + 1;
        $uid = $message->getUid();
        echo "═══════════════════════════════════════════════════════════\n";
        echo "  MESSAGE {$num} / {$messageCount} (UID: {$uid})\n";
        echo "═══════════════════════════════════════════════════════════\n\n";
        
        try {
            // Parse message
            echo "→ Parsing message...\n";
            $startTime = microtime(true);
            $parsed = $parser->parseMessage($message);
            $parseTime = round((microtime(true) - $startTime) * 1000, 2);
            echo "✓ Parsed in {$parseTime}ms\n\n";
            
            // Display results
            printHeader("BASIC INFO");
            echo "Message-ID: {$parsed->messageId}\n";
            echo "Subject:    {$parsed->subject}\n";
            echo "From:       {$parsed->from}\n";
            echo "To:         " . implode(', ', $parsed->to) . "\n";
            if (!empty($parsed->cc)) {
                echo "Cc:         " . implode(', ', $parsed->cc) . "\n";
            }
            echo "Date:       {$parsed->date->format('Y-m-d H:i:s')}\n";
            echo "\n";
            
            printHeader("BODY");
            echo "Has Text Body: " . ($parsed->hasTextBody() ? 'Yes' : 'No') . "\n";
            echo "Has HTML Body: " . ($parsed->hasHtmlBody() ? 'Yes' : 'No') . "\n";
            
            if ($parsed->hasTextBody()) {
                $preview = substr(str_replace("\n", " ", $parsed->bodyText), 0, 100);
                echo "Text Preview:  {$preview}...\n";
                echo "Text Length:   " . strlen($parsed->bodyText) . " bytes\n";
            }
            
            if ($parsed->hasHtmlBody()) {
                $htmlPreview = substr(strip_tags($parsed->bodyHtml), 0, 100);
                echo "HTML Preview:  {$htmlPreview}...\n";
                echo "HTML Length:   " . strlen($parsed->bodyHtml) . " bytes\n";
            }
            echo "\n";
            
            printHeader("ATTACHMENTS");
            echo "Count: {$parsed->getAttachmentCount()}\n";
            if ($parsed->hasAttachments()) {
                echo "Total Size: " . formatBytes($parsed->getTotalAttachmentSize()) . "\n\n";
                foreach ($parsed->attachments as $i => $attachment) {
                    echo "  [" . ($i + 1) . "] {$attachment->filename}\n";
                    echo "      Type: {$attachment->mimeType}\n";
                    echo "      Size: {$attachment->getFormattedSize()}\n";
                    echo "      Encoding: {$attachment->encoding}\n";
                    if ($attachment->isInline) {
                        echo "      Inline: Yes (CID: {$attachment->contentId})\n";
                    }
                    echo "\n";
                }
            } else {
                echo "None\n\n";
            }
            
            printHeader("THREADING");
            echo "Message-ID:  {$parsed->threadingInfo->messageId}\n";
            echo "In-Reply-To: " . ($parsed->threadingInfo->inReplyTo ?? 'None') . "\n";
            echo "References:  " . (empty($parsed->threadingInfo->references) ? 'None' : count($parsed->threadingInfo->references)) . "\n";
            echo "Thread-ID:   {$parsed->threadingInfo->getThreadId()}\n";
            echo "Is Reply:    " . ($parsed->threadingInfo->isReply() ? 'Yes' : 'No') . "\n";
            echo "Is Threaded: " . ($parsed->threadingInfo->isThreaded() ? 'Yes' : 'No') . "\n";
            echo "Thread Depth: {$parsed->threadingInfo->getThreadDepth()}\n";
            echo "\n";
            
            // Test results
            $testResults[] = [
                'uid' => $uid,
                'subject' => $parsed->subject,
                'success' => true,
                'parse_time' => $parseTime,
                'has_text' => $parsed->hasTextBody(),
                'has_html' => $parsed->hasHtmlBody(),
                'attachments' => $parsed->getAttachmentCount()
            ];
            
        } catch (\Exception $e) {
            echo "✗ ERROR: {$e->getMessage()}\n";
            echo "  {$e->getFile()}:{$e->getLine()}\n\n";
            
            $testResults[] = [
                'uid' => $uid,
                'subject' => 'Error',
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Summary
    echo "═══════════════════════════════════════════════════════════\n";
    echo "  TEST SUMMARY\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    
    $successCount = count(array_filter($testResults, fn($r) => $r['success']));
    $failCount = count($testResults) - $successCount;
    
    echo "Total Messages:    {$messageCount}\n";
    echo "Successful Parses: {$successCount}\n";
    echo "Failed Parses:     {$failCount}\n";
    echo "\n";
    
    if ($successCount > 0) {
        $avgParseTime = round(array_sum(array_column($testResults, 'parse_time')) / $successCount, 2);
        $totalAttachments = array_sum(array_column($testResults, 'attachments'));
        $withText = count(array_filter($testResults, fn($r) => $r['has_text'] ?? false));
        $withHtml = count(array_filter($testResults, fn($r) => $r['has_html'] ?? false));
        
        echo "Average Parse Time: {$avgParseTime}ms\n";
        echo "Messages with Text: {$withText}\n";
        echo "Messages with HTML: {$withHtml}\n";
        echo "Total Attachments:  {$totalAttachments}\n";
        echo "\n";
    }
    
    // Test verdict
    if ($failCount === 0) {
        echo "✓ ALL TESTS PASSED\n\n";
        exit(0);
    } else {
        echo "✗ {$failCount} TEST(S) FAILED\n\n";
        exit(1);
    }
    
} catch (\Exception $e) {
    echo "\n✗ FATAL ERROR: {$e->getMessage()}\n";
    echo "  {$e->getFile()}:{$e->getLine()}\n\n";
    exit(1);
}

// Helper functions

function printHeader(string $title): void {
    echo "┌─ {$title} " . str_repeat('─', 57 - strlen($title)) . "\n";
}

function formatBytes(int $bytes): string {
    $units = ['B', 'KB', 'MB', 'GB'];
    $size = $bytes;
    $unit = 0;
    
    while ($size >= 1024 && $unit < count($units) - 1) {
        $size /= 1024;
        $unit++;
    }
    
    return round($size, 2) . ' ' . $units[$unit];
}

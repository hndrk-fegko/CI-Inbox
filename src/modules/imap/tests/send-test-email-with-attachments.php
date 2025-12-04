<?php
/**
 * Send Test Email with Attachments
 * 
 * Sends a test email via Mercury SMTP with multiple attachments.
 * Tests: TXT, SVG, CSV, HTML files
 * 
 * Usage: php send-test-email-with-attachments.php
 */

declare(strict_types=1);
require_once __DIR__ . '/../../../../vendor/autoload.php';

// Configuration
$smtpHost = 'localhost';
$smtpPort = 25;
$from = 'testuser@localhost';
$to = 'testuser@localhost';

$attachmentDir = __DIR__ . '/test-attachments';
$attachments = [
    'test-document.txt' => 'text/plain',
    'test-image.svg' => 'image/svg+xml',
    'test-data.csv' => 'text/csv',
    'test-html.html' => 'text/html'
];

echo "═══════════════════════════════════════════════════════════\n";
echo "  SEND TEST EMAIL WITH ATTACHMENTS\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "Config:\n";
echo "  SMTP: {$smtpHost}:{$smtpPort}\n";
echo "  From: {$from}\n";
echo "  To:   {$to}\n";
echo "  Attachments: " . count($attachments) . "\n\n";

// Check if all files exist
echo "→ Checking attachment files...\n";
foreach ($attachments as $filename => $mimeType) {
    $filepath = $attachmentDir . '/' . $filename;
    if (!file_exists($filepath)) {
        die("✗ ERROR: File not found: {$filepath}\n");
    }
    $size = filesize($filepath);
    echo "  ✓ {$filename} ({$size} bytes)\n";
}
echo "\n";

// Connect to SMTP
echo "→ Connecting to SMTP {$smtpHost}:{$smtpPort}...\n";
$socket = @fsockopen($smtpHost, $smtpPort, $errno, $errstr, 10);
if (!$socket) {
    die("✗ ERROR: Cannot connect to SMTP: {$errstr} ({$errno})\n");
}

$response = fgets($socket);
if (!str_starts_with($response, '220')) {
    die("✗ ERROR: Unexpected SMTP response: {$response}\n");
}
echo "✓ Connected\n\n";

// EHLO
echo "→ Sending EHLO...\n";
fwrite($socket, "EHLO ci-inbox.test\r\n");
$response = fgets($socket);
if (!str_starts_with($response, '250')) {
    die("✗ ERROR: EHLO failed: {$response}\n");
}

// Skip additional EHLO responses
while ($line = fgets($socket)) {
    if (str_starts_with($line, '250 ')) {
        break;
    }
}
echo "✓ EHLO accepted\n\n";

// MAIL FROM
echo "→ Sending MAIL FROM...\n";
fwrite($socket, "MAIL FROM:<{$from}>\r\n");
$response = fgets($socket);
if (!str_starts_with($response, '250')) {
    die("✗ ERROR: MAIL FROM rejected: {$response}\n");
}
echo "✓ MAIL FROM accepted\n\n";

// RCPT TO
echo "→ Sending RCPT TO...\n";
fwrite($socket, "RCPT TO:<{$to}>\r\n");
$response = fgets($socket);
if (!str_starts_with($response, '250')) {
    die("✗ ERROR: RCPT TO rejected: {$response}\n");
}
echo "✓ RCPT TO accepted\n\n";

// DATA
echo "→ Sending DATA...\n";
fwrite($socket, "DATA\r\n");
$response = fgets($socket);
if (!str_starts_with($response, '354')) {
    die("✗ ERROR: DATA rejected: {$response}\n");
}
echo "✓ DATA accepted\n\n";

// Build message
echo "→ Building message with attachments...\n";

$boundary = "----=_Part_" . md5(uniqid());
$messageId = '<attachment-test-' . uniqid() . '@ci-inbox.test>';
$subject = 'CI-Inbox Attachment Test - ' . date('Y-m-d H:i:s');

// Headers
$headers = [
    "From: {$from}",
    "To: {$to}",
    "Subject: {$subject}",
    "Message-ID: {$messageId}",
    "Date: " . date('r'),
    "MIME-Version: 1.0",
    "Content-Type: multipart/mixed; boundary=\"{$boundary}\""
];

$message = implode("\r\n", $headers) . "\r\n\r\n";

// Body
$message .= "--{$boundary}\r\n";
$message .= "Content-Type: text/plain; charset=UTF-8\r\n";
$message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
$message .= "This is a test email with multiple attachments.\r\n\r\n";
$message .= "Attachments included:\r\n";
foreach ($attachments as $filename => $mimeType) {
    $message .= "- {$filename} ({$mimeType})\r\n";
}
$message .= "\r\nGenerated: " . date('Y-m-d H:i:s') . "\r\n";
$message .= "Message-ID: {$messageId}\r\n\r\n";

// Attachments
foreach ($attachments as $filename => $mimeType) {
    $filepath = $attachmentDir . '/' . $filename;
    $content = file_get_contents($filepath);
    $encoded = base64_encode($content);
    
    $message .= "--{$boundary}\r\n";
    $message .= "Content-Type: {$mimeType}; name=\"{$filename}\"\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n";
    $message .= "Content-Disposition: attachment; filename=\"{$filename}\"\r\n\r\n";
    $message .= chunk_split($encoded, 76, "\r\n");
    $message .= "\r\n";
    
    echo "  ✓ Attached: {$filename}\n";
}

$message .= "--{$boundary}--\r\n";

echo "✓ Message built (" . strlen($message) . " bytes)\n\n";

// Send message
echo "→ Sending message...\n";
fwrite($socket, $message . "\r\n.\r\n");
$response = fgets($socket);
if (!str_starts_with($response, '250')) {
    die("✗ ERROR: Message rejected: {$response}\n");
}
echo "✓ Message sent\n\n";

// QUIT
fwrite($socket, "QUIT\r\n");
fclose($socket);

echo "═══════════════════════════════════════════════════════════\n";
echo "  ✓ TEST EMAIL SENT SUCCESSFULLY\n";
echo "═══════════════════════════════════════════════════════════\n\n";
echo "Subject:    {$subject}\n";
echo "Message-ID: {$messageId}\n";
echo "Attachments: " . count($attachments) . "\n\n";
echo "→ Now run: php email-parser-test.php\n\n";

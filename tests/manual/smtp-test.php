<?php

/**
 * SMTP Test Script
 * 
 * Tests SMTP connection and basic email sending
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use CiInbox\Core\Container;
use CiInbox\Modules\Smtp\SmtpClientInterface;
use CiInbox\Modules\Smtp\SmtpConfig;
use CiInbox\Modules\Smtp\EmailMessage;
use CiInbox\Modules\Config\ConfigService;

// Initialize
$config = new ConfigService(__DIR__ . '/../../');
require_once __DIR__ . '/../../src/bootstrap/database.php';
initDatabase($config);

$container = Container::getInstance();
$smtpClient = $container->get(SmtpClientInterface::class);
$smtpConfig = $container->get('smtp.config');

echo "=== SMTP Test ===" . PHP_EOL . PHP_EOL;

echo "SMTP Configuration:" . PHP_EOL;
echo "  Host: {$smtpConfig->host}:{$smtpConfig->port}" . PHP_EOL;
echo "  Encryption: {$smtpConfig->encryption}" . PHP_EOL;
echo "  Username: {$smtpConfig->username}" . PHP_EOL;
echo "  From: {$smtpConfig->fromName} <{$smtpConfig->fromEmail}>" . PHP_EOL;
echo PHP_EOL;

// Test 1: Connection
echo "TEST 1: Connect to SMTP" . PHP_EOL;
try {
    $smtpClient->connect($smtpConfig);
    echo "✅ Connected to {$smtpConfig->host}:{$smtpConfig->port}" . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Connection failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Test 2: Send test email
echo PHP_EOL . "TEST 2: Send test email" . PHP_EOL;
try {
    $message = new EmailMessage(
        subject: "Test Email from CI-Inbox",
        bodyText: "This is a test email sent from CI-Inbox SMTP module.",
        bodyHtml: "<p>This is a <strong>test email</strong> sent from CI-Inbox SMTP module.</p>",
        to: [['email' => 'info@feg-koblenz.de', 'name' => 'FEG Koblenz']]
    );
    
    $smtpClient->send($message);
    echo "✅ Email sent successfully" . PHP_EOL;
    echo "   Subject: " . $message->subject . PHP_EOL;
    echo "   To: info@feg-koblenz.de" . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Send failed: " . $e->getMessage() . PHP_EOL;
}

// Test 3: Disconnect
echo PHP_EOL . "TEST 3: Disconnect" . PHP_EOL;
$smtpClient->disconnect();
echo "✅ Disconnected" . PHP_EOL;

echo PHP_EOL . "=== All tests completed ===" . PHP_EOL;

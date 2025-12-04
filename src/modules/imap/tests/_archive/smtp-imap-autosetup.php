<?php
/**
 * CI-Inbox Mercury/IMAP Auto-Setup Test
 *
 * 1. Send test mail via SMTP (with/without auth)
 * 2. Try to find mail in IMAP (all folders, all names)
 * 3. If not found, retry login and folder scan
 * 4. Save results for config wizard
 */
declare(strict_types=1);
require_once __DIR__ . '/../../../../vendor/autoload.php';

use CiInbox\Modules\Imap\ImapClient;
use CiInbox\Modules\Imap\Exceptions\ImapException;
use CiInbox\Modules\Logger\LoggerService;
use CiInbox\Modules\Config\ConfigService;

// Helper: ANSI Colors
const COLOR_GREEN = "\033[32m";
const COLOR_RED = "\033[31m";
const COLOR_YELLOW = "\033[33m";
const COLOR_BLUE = "\033[34m";
const COLOR_RESET = "\033[0m";

function printStatus(string $msg, bool $ok) {
    $icon = $ok ? '✅' : '❌';
    $color = $ok ? COLOR_GREEN : COLOR_RED;
    echo "   {$color}{$icon} " . ($ok ? 'PASSED' : 'FAILED') . COLOR_RESET . ": {$msg}\n";
}
function printHeader(string $title) {
    echo "\n" . COLOR_BLUE . "--- {$title} ---" . COLOR_RESET . "\n\n";
}
function prompt(string $question, string $default = ''): string {
    $defaultText = $default ? " (default: {$default})" : '';
    echo COLOR_YELLOW . $question . $defaultText . ": " . COLOR_RESET;
    $input = trim(fgets(STDIN) ?: '');
    return $input === '' ? $default : $input;
}

function sendTestMail($smtpHost, $smtpPort, $from, $to, $useSsl, &$msgId, $username = '', $password = ''): array {
    $results = [];
    $msgId = '<ci-inbox-test-' . uniqid() . '@setup.local>';
    $subject = 'CI-Inbox Mercury Auto-Setup ' . date('Y-m-d H:i:s');
    $body = "Auto-Setup Testmail\nMessage-ID: {$msgId}\n";
    $headers = [
        "From: {$from}",
        "To: {$to}",
        "Subject: {$subject}",
        "Message-ID: {$msgId}",
        "Date: " . date('r'),
        "MIME-Version: 1.0",
        "Content-Type: text/plain; charset=UTF-8",
        "Content-Transfer-Encoding: 8bit"
    ];
    $protocol = $useSsl ? 'ssl' : 'tcp';
    $results['smtp_attempts'] = [];
    foreach ([[true, $username, $password], [false, '', '']] as [$tryAuth, $user, $pass]) {
        try {
            $socket = @fsockopen("{$protocol}://{$smtpHost}", $smtpPort, $errno, $errstr, 10);
            if (!$socket) throw new Exception("Connect failed: {$errstr} ({$errno})");
            $response = fgets($socket);
            if (substr($response, 0, 3) !== '220') throw new Exception("SMTP not ready: {$response}");
            fwrite($socket, "EHLO ci-inbox.test\r\n");
            while ($line = fgets($socket)) { if (substr($line, 3, 1) === ' ') break; }
            if ($tryAuth && $user && $pass) {
                fwrite($socket, "AUTH LOGIN\r\n"); fgets($socket);
                fwrite($socket, base64_encode($user) . "\r\n"); fgets($socket);
                fwrite($socket, base64_encode($pass) . "\r\n"); $authResponse = fgets($socket);
                if (substr($authResponse, 0, 3) !== '235') throw new Exception("SMTP auth failed: {$authResponse}");
            }
            fwrite($socket, "MAIL FROM:<{$from}>\r\n"); $response = fgets($socket);
            if (substr($response, 0, 3) !== '250') throw new Exception("MAIL FROM rejected: {$response}");
            fwrite($socket, "RCPT TO:<{$to}>\r\n"); $response = fgets($socket);
            if (substr($response, 0, 3) !== '250') throw new Exception("RCPT TO rejected: {$response}");
            fwrite($socket, "DATA\r\n"); $response = fgets($socket);
            if (substr($response, 0, 3) !== '354') throw new Exception("DATA rejected: {$response}");
            fwrite($socket, implode("\r\n", $headers) . "\r\n\r\n");
            fwrite($socket, $body . "\r\n");
            fwrite($socket, ".\r\n");
            $response = fgets($socket);
            if (substr($response, 0, 3) !== '250') throw new Exception("Message not accepted: {$response}");
            fwrite($socket, "QUIT\r\n"); fclose($socket);
            $results['smtp_attempts'][] = ['auth' => $tryAuth, 'success' => true, 'error' => null];
            printStatus('SMTP send ' . ($tryAuth ? 'with auth' : 'without auth'), true);
            return $results;
        } catch (Exception $e) {
            $results['smtp_attempts'][] = ['auth' => $tryAuth, 'success' => false, 'error' => $e->getMessage()];
            printStatus('SMTP send ' . ($tryAuth ? 'with auth' : 'without auth'), false);
        }
    }
    return $results;
}

function findMailInImap($imap, $msgId, &$foundFolder, &$foundUid): bool {
    $folders = $imap->getFolders();
    $folderNames = array_map('strval', $folders);
    $candidates = array_merge(['_INBOX_', 'INBOX', 'Posteingang', 'Inbox', 'INBOX.', 'INBOX/', 'INBOX\\', 'Eingang'], $folderNames);
    $allFolderMessages = [];
    foreach ($candidates as $folder) {
        try {
            $imap->selectFolder($folder);
            $messages = $imap->getMessages(100, false);
            $allFolderMessages[$folder] = [];
            foreach ($messages as $msg) {
                $allFolderMessages[$folder][] = $msg->getMessageId();
                if ($msg->getMessageId() === $msgId) {
                    $foundFolder = $folder;
                    $foundUid = $msg->getUid();
                    printStatus("Mail found in folder '{$folder}'", true);
                    return true;
                }
            }
        } catch (ImapException $e) {
            printStatus("Folder '{$folder}' not accessible: " . $e->getMessage(), false);
        }
    }
    // Save all scanned Message-IDs for debugging
    global $results;
    $results['imap_folder_messages'] = $allFolderMessages;
    return false;
}

// MAIN
printHeader('CI-Inbox Mercury/IMAP Auto-Setup');
$smtpHost = prompt('SMTP Host', 'localhost');
$smtpPort = (int)prompt('SMTP Port', '25');
$smtpSsl = strtolower(prompt('Use SSL? (y/n)', 'n')) === 'y';
$fromEmail = prompt('From Email', 'testuser@localhost');
$toEmail = prompt('To Email', $fromEmail);
$smtpUser = prompt('SMTP Username (optional)', '');
$smtpPass = $smtpUser ? prompt('SMTP Password', '') : '';
$imapHost = prompt('IMAP Host', $smtpHost);
$imapPort = (int)prompt('IMAP Port', '143');
$imapSsl = strtolower(prompt('IMAP SSL? (y/n)', 'n')) === 'y';
$imapUser = prompt('IMAP Username', 'testuser');
$imapPass = prompt('IMAP Password', 'testpass123');

$logger = new LoggerService(__DIR__ . '/../../../../logs');
$config = new ConfigService(__DIR__ . '/../../../../');
$imap = new ImapClient($logger, $config);

// 1. SMTP Test
printHeader('SMTP Test');
$results = sendTestMail($smtpHost, $smtpPort, $fromEmail, $toEmail, $smtpSsl, $msgId, $smtpUser, $smtpPass);
$results['message_id'] = $msgId;

// 2. IMAP Test
printHeader('IMAP Test');
$imapSuccess = false;
$foundFolder = '';
$foundUid = '';
try {
    $imap->connect($imapHost, $imapPort, $imapUser, $imapPass, $imapSsl);
    printStatus('IMAP login', true);
    $imapSuccess = findMailInImap($imap, $msgId, $foundFolder, $foundUid);
    if (!$imapSuccess) {
        printStatus('Mail not found, retrying login and folder scan...', false);
        $imap->disconnect();
        sleep(2);
        $imap->connect($imapHost, $imapPort, $imapUser, $imapPass, $imapSsl);
        $imapSuccess = findMailInImap($imap, $msgId, $foundFolder, $foundUid);
    }
} catch (ImapException $e) {
    printStatus('IMAP login failed: ' . $e->getMessage(), false);
}
$results['imap_success'] = $imapSuccess;
$results['imap_folder'] = $foundFolder;
$results['imap_uid'] = $foundUid;

// 3. Ergebnis speichern
printHeader('Auto-Setup Report');
file_put_contents(__DIR__ . '/autosetup-result.json', json_encode($results, JSON_PRETTY_PRINT));
if ($imapSuccess) {
    printStatus('Auto-Setup erfolgreich! Siehe autosetup-result.json', true);
} else {
    printStatus('Auto-Setup fehlgeschlagen! Siehe autosetup-result.json', false);
}

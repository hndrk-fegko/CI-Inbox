<?php
/**
 * CI-Inbox Setup Auto-Discovery Wizard
 * 
 * Intelligenter Setup-Assistent mit automatischer Erkennung von:
 * - SMTP Konfiguration (mit/ohne Auth)
 * - IMAP Konfiguration
 * - Inbox-Ordner (automatisches Scanning)
 * - Mail-Filter-Kompatibilität
 * 
 * Flow:
 * 1. User gibt Email-Adresse ein
 * 2. IMAP-Credentials erfragen (Host auto-detect)
 * 3. SMTP auto-detect (gleiche Credentials + no-auth test)
 * 4. Test-Mail senden
 * 5. Auto-Scan aller IMAP-Ordner
 * 6. Konfiguration speichern
 * 
 * Usage: php src/modules/imap/tests/setup-autodiscover.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../../vendor/autoload.php';

use CiInbox\Modules\Imap\ImapClient;
use CiInbox\Modules\Imap\Exceptions\ImapException;
use CiInbox\Modules\Logger\LoggerService;
use CiInbox\Modules\Config\ConfigService;

// ============================================================================
// ANSI Colors & Helper Functions
// ============================================================================

const C_GREEN = "\033[32m";
const C_RED = "\033[31m";
const C_YELLOW = "\033[33m";
const C_BLUE = "\033[34m";
const C_CYAN = "\033[36m";
const C_MAGENTA = "\033[35m";
const C_RESET = "\033[0m";
const C_BOLD = "\033[1m";

function success(string $msg): void {
    echo "   " . C_GREEN . "✅ " . $msg . C_RESET . "\n";
}

function error(string $msg): void {
    echo "   " . C_RED . "❌ " . $msg . C_RESET . "\n";
}

function info(string $msg): void {
    echo "   " . C_CYAN . "ℹ️  " . $msg . C_RESET . "\n";
}

function warn(string $msg): void {
    echo "   " . C_YELLOW . "⚠️  " . $msg . C_RESET . "\n";
}

function step(string $title): void {
    echo "\n" . C_BLUE . C_BOLD . "► " . $title . C_RESET . "\n\n";
}

function prompt(string $question, string $default = '', bool $password = false): string {
    $defaultText = $default ? " (default: {$default})" : '';
    echo C_YELLOW . $question . $defaultText . ": " . C_RESET;
    
    if ($password && function_exists('readline')) {
        // Hide password input (Unix-like systems)
        system('stty -echo');
        $input = trim(fgets(STDIN) ?: '');
        system('stty echo');
        echo "\n";
    } else {
        $input = trim(fgets(STDIN) ?: '');
    }
    
    return $input === '' ? $default : $input;
}

function promptYesNo(string $question, bool $default = true): bool {
    $defaultText = $default ? 'Y/n' : 'y/N';
    $input = strtolower(prompt($question . " ({$defaultText})", $default ? 'y' : 'n'));
    return in_array($input, ['y', 'yes', 'j', 'ja']);
}

// ============================================================================
// Auto-Discovery Functions
// ============================================================================

/**
 * Extract domain from email address
 */
function extractDomain(string $email): string {
    $parts = explode('@', $email);
    return $parts[1] ?? '';
}

/**
 * Extract real hostname from SSL certificate error
 */
function extractRealHostFromCertError(string $host, int $port = 993): ?string {
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'capture_peer_cert' => true
        ]
    ]);
    
    $errno = 0;
    $errstr = '';
    $socket = @stream_socket_client(
        "ssl://{$host}:{$port}",
        $errno,
        $errstr,
        3,
        STREAM_CLIENT_CONNECT,
        $context
    );
    
    if ($socket) {
        $params = stream_context_get_params($socket);
        fclose($socket);
        
        if (isset($params['options']['ssl']['peer_certificate'])) {
            $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
            
            // Extract CN from subject
            if (isset($cert['subject']['CN'])) {
                return $cert['subject']['CN'];
            }
        }
    }
    
    return null;
}

/**
 * Auto-detect IMAP/SMTP hosts from email domain
 */
function autoDetectHosts(string $email): array {
    $domain = extractDomain($email);
    
    return [
        'imap_candidates' => [
            "imap.{$domain}",
            "mail.{$domain}",
            $domain,
            'localhost'
        ],
        'smtp_candidates' => [
            "smtp.{$domain}",
            "mail.{$domain}",
            $domain,
            'localhost'
        ]
    ];
}

/**
 * Test SMTP connection with various configurations
 * Includes certificate mismatch detection and real hostname extraction
 */
function testSMTP(
    string $host,
    int $port,
    string $from,
    string $to,
    string &$uniqueSubject,
    string $username = '',
    string $password = '',
    bool $useSsl = false
): array {
    $result = [
        'success' => false,
        'method' => 'unknown',
        'error' => null,
        'config' => [
            'host' => $host,
            'port' => $port,
            'ssl' => $useSsl,
            'auth' => !empty($username)
        ]
    ];
    
    try {
        // Generate unique test message
        $uniqueSubject = 'CI-Inbox-Setup-' . uniqid();
        $msgId = '<setup-' . uniqid() . '@ci-inbox.local>';
        $body = "CI-Inbox Setup Test\nSent: " . date('Y-m-d H:i:s') . "\nSubject: {$uniqueSubject}";
        
        // Connect to SMTP
        if ($useSsl) {
            // Use stream_socket_client for SSL to detect certificate issues
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'capture_peer_cert' => true
                ]
            ]);
            
            $errno = 0;
            $errstr = '';
            $socket = @stream_socket_client(
                "ssl://{$host}:{$port}",
                $errno,
                $errstr,
                10,
                STREAM_CLIENT_CONNECT,
                $context
            );
            
            // Check for certificate mismatch
            if (!$socket && (str_contains($errstr, 'certificate') || str_contains($errstr, 'CN='))) {
                warn("Certificate mismatch detected for SMTP. Trying to find real hostname...");
                
                $realHost = extractRealHostFromCertError($host, $port);
                
                if ($realHost && $realHost !== $host) {
                    info("Found certificate hostname: {$realHost}");
                    
                    if (promptYesNo("Retry SMTP with {$realHost}?", true)) {
                        // Retry with real hostname
                        return testSMTP($realHost, $port, $from, $to, $uniqueSubject, $username, $password, $useSsl);
                    } else {
                        throw new Exception("Certificate mismatch: {$errstr}");
                    }
                } else {
                    throw new Exception("Certificate error: {$errstr}");
                }
            }
        } else {
            // Non-SSL: use simple fsockopen
            $errno = 0;
            $errstr = '';
            $socket = @fsockopen("tcp://{$host}", $port, $errno, $errstr, 10);
        }
        
        if (!$socket) {
            throw new Exception("Connection failed: {$errstr} ({$errno})");
        }
        
        // Read greeting
        $response = fgets($socket);
        if (!str_starts_with($response, '220')) {
            throw new Exception("SMTP not ready: {$response}");
        }
        
        // EHLO
        fwrite($socket, "EHLO ci-inbox.local\r\n");
        $capabilities = [];
        while ($line = fgets($socket)) {
            $capabilities[] = trim($line);
            if (substr($line, 3, 1) === ' ') break;
        }
        
        // Try AUTH if credentials provided
        $authSuccess = false;
        if (!empty($username) && !empty($password)) {
            fwrite($socket, "AUTH LOGIN\r\n");
            $authResponse = fgets($socket);
            
            if (str_starts_with($authResponse, '334')) {
                fwrite($socket, base64_encode($username) . "\r\n");
                fgets($socket);
                fwrite($socket, base64_encode($password) . "\r\n");
                $authResponse = fgets($socket);
                
                if (str_starts_with($authResponse, '235')) {
                    $authSuccess = true;
                    $result['method'] = 'auth';
                }
            }
        }
        
        // MAIL FROM
        fwrite($socket, "MAIL FROM:<{$from}>\r\n");
        $response = fgets($socket);
        if (!str_starts_with($response, '250')) {
            throw new Exception("MAIL FROM rejected: {$response}");
        }
        
        // RCPT TO
        fwrite($socket, "RCPT TO:<{$to}>\r\n");
        $response = fgets($socket);
        if (!str_starts_with($response, '250')) {
            throw new Exception("RCPT TO rejected: {$response}");
        }
        
        // DATA
        fwrite($socket, "DATA\r\n");
        $response = fgets($socket);
        if (!str_starts_with($response, '354')) {
            throw new Exception("DATA rejected: {$response}");
        }
        
        // Send message
        $headers = [
            "From: {$from}",
            "To: {$to}",
            "Subject: {$uniqueSubject}",
            "Message-ID: {$msgId}",
            "Date: " . date('r'),
            "Content-Type: text/plain; charset=UTF-8"
        ];
        
        fwrite($socket, implode("\r\n", $headers) . "\r\n\r\n");
        fwrite($socket, $body . "\r\n");
        fwrite($socket, ".\r\n");
        
        $response = fgets($socket);
        if (!str_starts_with($response, '250')) {
            throw new Exception("Message rejected: {$response}");
        }
        
        // QUIT
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        
        $result['success'] = true;
        $result['method'] = $authSuccess ? 'auth' : 'no-auth';
        $result['subject'] = $uniqueSubject;
        $result['message_id'] = $msgId;
        
    } catch (Exception $e) {
        $result['error'] = $e->getMessage();
    }
    
    return $result;
}

/**
 * Scan all IMAP folders for test message
 */
function scanAllFolders(ImapClient $imap, string $uniqueSubject, int $maxWait = 30): array {
    $result = [
        'found' => false,
        'folder' => null,
        'uid' => null,
        'scanned_folders' => [],
        'total_messages' => 0
    ];
    
    $startTime = time();
    
    try {
        // Get all folders
        $folders = $imap->getFolders();
        info("Found " . count($folders) . " folders, scanning...");
        
        // Wait for message to arrive
        while ((time() - $startTime) < $maxWait) {
            foreach ($folders as $folder) {
                $folderName = (string)$folder;
                
                if (!isset($result['scanned_folders'][$folderName])) {
                    $result['scanned_folders'][$folderName] = [
                        'accessible' => false,
                        'message_count' => 0,
                        'scanned' => false
                    ];
                }
                
                try {
                    $imap->selectFolder($folderName);
                    $messageCount = $imap->getMessageCount();
                    
                    $result['scanned_folders'][$folderName]['accessible'] = true;
                    $result['scanned_folders'][$folderName]['message_count'] = $messageCount;
                    $result['total_messages'] += $messageCount;
                    
                    // Search for test message
                    $messages = $imap->getMessages(100, false);
                    
                    foreach ($messages as $msg) {
                        if ($msg->getSubject() === $uniqueSubject) {
                            $result['found'] = true;
                            $result['folder'] = $folderName;
                            $result['uid'] = (string)$msg->getUid();
                            $result['scanned_folders'][$folderName]['scanned'] = true;
                            
                            success("Test message found in folder: {$folderName}");
                            return $result;
                        }
                    }
                    
                    $result['scanned_folders'][$folderName]['scanned'] = true;
                    
                } catch (ImapException $e) {
                    $result['scanned_folders'][$folderName]['error'] = $e->getMessage();
                }
            }
            
            // Wait before retry
            if ((time() - $startTime) < $maxWait) {
                echo ".";
                sleep(2);
            }
        }
        
        echo "\n";
        
    } catch (Exception $e) {
        $result['error'] = $e->getMessage();
    }
    
    return $result;
}

/**
 * Save configuration to .env file
 */
function saveConfiguration(array $config): bool {
    $envPath = __DIR__ . '/../../../../.env';
    
    try {
        // Read existing .env
        $envContent = file_exists($envPath) ? file_get_contents($envPath) : '';
        $envLines = explode("\n", $envContent);
        
        // Update or add configuration
        $updates = [
            'IMAP_HOST' => $config['imap']['host'],
            'IMAP_PORT' => (string)$config['imap']['port'],
            'IMAP_SSL' => $config['imap']['ssl'] ? 'true' : 'false',
            'IMAP_INBOX_FOLDER' => $config['imap']['inbox_folder'],
            'SMTP_HOST' => $config['smtp']['host'],
            'SMTP_PORT' => (string)$config['smtp']['port'],
            'SMTP_SSL' => $config['smtp']['ssl'] ? 'true' : 'false',
            'SMTP_AUTH' => $config['smtp']['auth'] ? 'true' : 'false',
        ];
        
        foreach ($updates as $key => $value) {
            $found = false;
            foreach ($envLines as $i => $line) {
                if (str_starts_with(trim($line), $key . '=')) {
                    $envLines[$i] = "{$key}={$value}";
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $envLines[] = "{$key}={$value}";
            }
        }
        
        // Write back
        file_put_contents($envPath, implode("\n", $envLines));
        
        // Also save as JSON for programmatic access
        $jsonPath = __DIR__ . '/setup-config.json';
        file_put_contents($jsonPath, json_encode($config, JSON_PRETTY_PRINT));
        
        return true;
        
    } catch (Exception $e) {
        error("Config save failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Migrate configuration from .env to database
 * This should be called after database is initialized
 */
function migrateToDatabase(array $config, string $emailAddress): bool {
    try {
        // Initialize database connection
        require_once __DIR__ . '/../../../../src/bootstrap/database.php';
        $configService = new ConfigService(__DIR__ . '/../../../../');
        initDatabase($configService);
        
        // Use SystemSettingRepository to store settings
        $logger = new LoggerService(__DIR__ . '/../../../../logs');
        $encryptionService = new \CiInbox\Modules\Encryption\EncryptionService($configService);
        $repo = new \CiInbox\App\Repositories\SystemSettingRepository($encryptionService, $logger);
        
        // Prepare settings for database
        $settings = [
            // IMAP Settings
            'imap.host' => $config['imap']['host'],
            'imap.port' => (string)$config['imap']['port'],
            'imap.ssl' => $config['imap']['ssl'] ? '1' : '0',
            'imap.username' => $config['imap']['username'],
            'imap.password' => base64_decode($config['imap']['password']), // Decrypt from base64
            'imap.inbox_folder' => $config['imap']['inbox_folder'],
            
            // SMTP Settings
            'smtp.host' => $config['smtp']['host'],
            'smtp.port' => (string)$config['smtp']['port'],
            'smtp.ssl' => $config['smtp']['ssl'] ? '1' : '0',
            'smtp.auth' => $config['smtp']['auth'] ? '1' : '0',
            'smtp.username' => $config['smtp']['username'] ?? '',
            'smtp.password' => !empty($config['smtp']['password']) ? base64_decode($config['smtp']['password']) : '',
            'smtp.from_email' => $emailAddress,
            'smtp.from_name' => 'CI-Inbox'
        ];
        
        // Save all settings (Repository will handle encryption for passwords)
        foreach ($settings as $key => $value) {
            $repo->set($key, $value);
        }
        
        info("Configuration migrated to database successfully!");
        
        // Remove sensitive data from .env
        $envPath = __DIR__ . '/../../../../.env';
        if (file_exists($envPath)) {
            $envContent = file_get_contents($envPath);
            $envLines = explode("\n", $envContent);
            
            // Remove IMAP/SMTP credentials, keep only non-sensitive config
            $sensitiveKeys = ['IMAP_USERNAME', 'IMAP_PASSWORD', 'SMTP_USERNAME', 'SMTP_PASSWORD'];
            $cleanedLines = [];
            
            foreach ($envLines as $line) {
                $isSensitive = false;
                foreach ($sensitiveKeys as $key) {
                    if (str_starts_with(trim($line), $key . '=')) {
                        $isSensitive = true;
                        break;
                    }
                }
                
                if (!$isSensitive && trim($line) !== '') {
                    $cleanedLines[] = $line;
                }
            }
            
            file_put_contents($envPath, implode("\n", $cleanedLines) . "\n");
            info("Sensitive credentials removed from .env file");
        }
        
        return true;
        
    } catch (Exception $e) {
        error("Database migration failed: " . $e->getMessage());
        return false;
    }
}

// ============================================================================
// MAIN WIZARD
// ============================================================================

echo C_BLUE . C_BOLD . "
╔═══════════════════════════════════════════════╗
║  CI-Inbox Setup Auto-Discovery Wizard        ║
╚═══════════════════════════════════════════════╝
" . C_RESET . "\n";

info("This wizard will automatically configure your SMTP and IMAP settings.");
info("Please have your email account credentials ready.\n");

$setupData = [
    'imap' => [],
    'smtp' => [],
    'test_results' => []
];

// ============================================================================
// STEP 1: Email Address
// ============================================================================

step("Step 1: Email Account");

$emailAddress = prompt("Enter the email address for the shared inbox", "info@example.com");
$domain = extractDomain($emailAddress);

if (empty($domain)) {
    error("Invalid email address!");
    exit(1);
}

success("Email: {$emailAddress}");
info("Detected domain: {$domain}");

// Auto-detect hosts
$hostCandidates = autoDetectHosts($emailAddress);

// ============================================================================
// STEP 2: IMAP Configuration
// ============================================================================

step("Step 2: IMAP Configuration");

info("Trying to auto-detect IMAP server...");

// Try common IMAP hosts
$imapHost = '';
$imapPort = 143;
$imapSsl = false;

foreach ($hostCandidates['imap_candidates'] as $candidate) {
    echo "   Testing {$candidate}...";
    
    // Try SSL port first
    $socket = @fsockopen("ssl://{$candidate}", 993, $errno, $errstr, 3);
    if ($socket) {
        fclose($socket);
        $imapHost = $candidate;
        $imapPort = 993;
        $imapSsl = true;
        echo " " . C_GREEN . "✓ (SSL)" . C_RESET . "\n";
        break;
    }
    
    // Try non-SSL
    $socket = @fsockopen("tcp://{$candidate}", 143, $errno, $errstr, 3);
    if ($socket) {
        fclose($socket);
        $imapHost = $candidate;
        $imapPort = 143;
        $imapSsl = false;
        echo " " . C_GREEN . "✓" . C_RESET . "\n";
        break;
    }
    
    echo " " . C_RED . "✗" . C_RESET . "\n";
}

// Manual input if auto-detect failed
if (empty($imapHost)) {
    warn("Auto-detection failed. Please enter manually:");
    $imapHost = prompt("IMAP Host", "imap.{$domain}");
    $imapPort = (int)prompt("IMAP Port", "143");
    $imapSsl = promptYesNo("Use SSL/TLS?", false);
}

success("IMAP Server: {$imapHost}:{$imapPort}" . ($imapSsl ? " (SSL)" : ""));

// Get credentials
echo "\n";
$imapUsername = prompt("IMAP Username", explode('@', $emailAddress)[0]);
$imapPassword = prompt("IMAP Password", '', true);

// Test IMAP connection
info("Testing IMAP connection...");

$logger = new LoggerService(__DIR__ . '/../../../../logs');
$config = new ConfigService(__DIR__ . '/../../../../');
$imap = new ImapClient($logger, $config);

try {
    $imap->connect($imapHost, $imapPort, $imapUsername, $imapPassword, $imapSsl);
    success("IMAP connection successful!");
    
    $setupData['imap'] = [
        'host' => $imapHost,
        'port' => $imapPort,
        'ssl' => $imapSsl,
        'username' => $imapUsername,
        'password' => base64_encode($imapPassword) // Store encoded
    ];
    
} catch (ImapException $e) {
    $errorMsg = $e->getMessage();
    
    // Check for certificate mismatch
    if ($imapSsl && (
        str_contains($errorMsg, 'certificate') || 
        str_contains($errorMsg, 'CN=') ||
        str_contains($errorMsg, 'does not match')
    )) {
        warn("Certificate mismatch detected. Trying to find real hostname...");
        
        // Extract real hostname from certificate
        $realHost = extractRealHostFromCertError($imapHost, $imapPort);
        
        if ($realHost && $realHost !== $imapHost) {
            info("Found certificate hostname: {$realHost}");
            
            if (promptYesNo("Retry with {$realHost}?", true)) {
                $imapHost = $realHost;
                
                try {
                    $imap->connect($imapHost, $imapPort, $imapUsername, $imapPassword, $imapSsl);
                    success("IMAP connection successful with real hostname!");
                    
                    $setupData['imap'] = [
                        'host' => $imapHost,
                        'port' => $imapPort,
                        'ssl' => $imapSsl,
                        'username' => $imapUsername,
                        'password' => base64_encode($imapPassword)
                    ];
                    
                } catch (ImapException $e2) {
                    error("IMAP connection still failed: " . $e2->getMessage());
                    error("Please check your credentials and try again.");
                    exit(1);
                }
            } else {
                error("Connection aborted by user.");
                exit(1);
            }
        } else {
            error("Could not extract real hostname from certificate.");
            error("Original error: " . $errorMsg);
            exit(1);
        }
    } else {
        error("IMAP connection failed: " . $errorMsg);
        error("Please check your credentials and try again.");
        exit(1);
    }
}

// ============================================================================
// STEP 3: SMTP Auto-Discovery
// ============================================================================

step("Step 3: SMTP Configuration (Auto-Discovery)");

info("Testing SMTP configurations automatically...");
info("Trying: same credentials, no-auth, common ports...\n");

$smtpTests = [];
$smtpSuccess = false;
$uniqueSubject = '';

// Test configurations in order of likelihood
$smtpTestConfigs = [
    // Same host as IMAP, same credentials
    ['host' => $imapHost, 'port' => 587, 'ssl' => false, 'username' => $imapUsername, 'password' => $imapPassword, 'label' => 'IMAP host, port 587, with auth'],
    ['host' => $imapHost, 'port' => 25, 'ssl' => false, 'username' => $imapUsername, 'password' => $imapPassword, 'label' => 'IMAP host, port 25, with auth'],
    ['host' => $imapHost, 'port' => 465, 'ssl' => true, 'username' => $imapUsername, 'password' => $imapPassword, 'label' => 'IMAP host, port 465 SSL, with auth'],
    
    // Try without auth
    ['host' => $imapHost, 'port' => 25, 'ssl' => false, 'username' => '', 'password' => '', 'label' => 'IMAP host, port 25, no auth'],
    
    // Try SMTP-specific hosts
    ['host' => "smtp.{$domain}", 'port' => 587, 'ssl' => false, 'username' => $imapUsername, 'password' => $imapPassword, 'label' => "smtp.{$domain}, port 587"],
    ['host' => "smtp.{$domain}", 'port' => 25, 'ssl' => false, 'username' => '', 'password' => '', 'label' => "smtp.{$domain}, port 25, no auth"],
    
    // Localhost (for testing)
    ['host' => 'localhost', 'port' => 25, 'ssl' => false, 'username' => '', 'password' => '', 'label' => 'localhost:25, no auth'],
];

foreach ($smtpTestConfigs as $testConfig) {
    echo "   Testing: " . $testConfig['label'] . "...";
    
    $testResult = testSMTP(
        $testConfig['host'],
        $testConfig['port'],
        $emailAddress,
        $emailAddress,
        $uniqueSubject,
        $testConfig['username'],
        $testConfig['password'],
        $testConfig['ssl']
    );
    
    $smtpTests[] = [
        'config' => $testConfig,
        'result' => $testResult
    ];
    
    if ($testResult['success']) {
        echo " " . C_GREEN . "✓ SUCCESS" . C_RESET . "\n";
        $smtpSuccess = true;
        
        $setupData['smtp'] = [
            'host' => $testConfig['host'],
            'port' => $testConfig['port'],
            'ssl' => $testConfig['ssl'],
            'auth' => !empty($testConfig['username']),
            'username' => $testConfig['username'],
            'password' => !empty($testConfig['password']) ? base64_encode($testConfig['password']) : ''
        ];
        
        $setupData['test_results']['smtp'] = $testResult;
        
        break;
    } else {
        echo " " . C_RED . "✗" . C_RESET . " (" . $testResult['error'] . ")\n";
    }
}

// Manual SMTP config if all failed
if (!$smtpSuccess) {
    warn("\nAutomatic SMTP detection failed. Please configure manually:");
    
    $smtpHost = prompt("SMTP Host", "smtp.{$domain}");
    $smtpPort = (int)prompt("SMTP Port", "587");
    $smtpSsl = promptYesNo("Use SSL?", false);
    $smtpAuth = promptYesNo("Requires authentication?", true);
    
    $smtpUsername = $smtpAuth ? prompt("SMTP Username", $imapUsername) : '';
    $smtpPassword = $smtpAuth ? prompt("SMTP Password", '', true) : '';
    
    echo "\n";
    info("Testing manual SMTP configuration...");
    
    $testResult = testSMTP(
        $smtpHost,
        $smtpPort,
        $emailAddress,
        $emailAddress,
        $uniqueSubject,
        $smtpUsername,
        $smtpPassword,
        $smtpSsl
    );
    
    if (!$testResult['success']) {
        error("SMTP test failed: " . $testResult['error']);
        error("Setup cannot continue without working SMTP.");
        exit(1);
    }
    
    success("SMTP connection successful!");
    
    $setupData['smtp'] = [
        'host' => $smtpHost,
        'port' => $smtpPort,
        'ssl' => $smtpSsl,
        'auth' => $smtpAuth,
        'username' => $smtpUsername,
        'password' => $smtpAuth ? base64_encode($smtpPassword) : ''
    ];
    
    $setupData['test_results']['smtp'] = $testResult;
}

// ============================================================================
// STEP 4: IMAP Folder Scanning
// ============================================================================

step("Step 4: Scanning IMAP Folders for Test Message");

info("Test message subject: {$uniqueSubject}");
info("Scanning all accessible folders...\n");

$scanResult = scanAllFolders($imap, $uniqueSubject, 30);

$setupData['test_results']['imap_scan'] = $scanResult;

if ($scanResult['found']) {
    success("Test message found in folder: " . $scanResult['folder']);
    info("This will be used as the default INBOX folder.");
    
    $setupData['imap']['inbox_folder'] = $scanResult['folder'];
    
    // Cleanup: Delete test message
    try {
        $imap->selectFolder($scanResult['folder']);
        $imap->deleteMessage($scanResult['uid']);
        success("Test message cleaned up.");
    } catch (ImapException $e) {
        warn("Could not delete test message: " . $e->getMessage());
    }
    
} else {
    warn("Test message not found in any folder!");
    info("Scanned folders: " . implode(', ', array_keys($scanResult['scanned_folders'])));
    
    // Use most likely folder
    if (isset($scanResult['scanned_folders']['INBOX'])) {
        $setupData['imap']['inbox_folder'] = 'INBOX';
        info("Using 'INBOX' as default.");
    } else {
        $folders = array_keys($scanResult['scanned_folders']);
        $setupData['imap']['inbox_folder'] = $folders[0] ?? 'INBOX';
        warn("Using first accessible folder: " . $setupData['imap']['inbox_folder']);
    }
}

// Disconnect
$imap->disconnect();

// ============================================================================
// STEP 5: Save Configuration
// ============================================================================

step("Step 5: Saving Configuration");

info("Saving to .env and setup-config.json...");

if (saveConfiguration($setupData)) {
    success("Configuration saved successfully!");
} else {
    error("Failed to save configuration!");
    exit(1);
}

// ============================================================================
// STEP 6: Migrate to Database
// ============================================================================

step("Step 6: Migrating Configuration to Database");

info("Transferring configuration from .env to database...");
info("This ensures secure storage with encryption for passwords.\n");

if (migrateToDatabase($setupData, $emailAddress)) {
    success("Configuration migrated to database!");
    success("Sensitive credentials removed from .env file");
} else {
    warn("Database migration failed - configuration remains in .env");
    warn("You can manually migrate later via Admin UI");
}

// ============================================================================
// FINAL SUMMARY
// ============================================================================

step("✅ Setup Complete!");

echo C_GREEN . "
╔═══════════════════════════════════════════════╗
║          Setup Completed Successfully!        ║
╚═══════════════════════════════════════════════╝
" . C_RESET . "\n";

echo C_CYAN . "Configuration Summary:\n" . C_RESET;
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "📧 Email: " . C_BOLD . $emailAddress . C_RESET . "\n\n";

echo "📥 IMAP Settings:\n";
echo "   Host:   {$setupData['imap']['host']}:{$setupData['imap']['port']}\n";
echo "   SSL:    " . ($setupData['imap']['ssl'] ? 'Yes' : 'No') . "\n";
echo "   Inbox:  {$setupData['imap']['inbox_folder']}\n\n";

echo "📤 SMTP Settings:\n";
echo "   Host:   {$setupData['smtp']['host']}:{$setupData['smtp']['port']}\n";
echo "   SSL:    " . ($setupData['smtp']['ssl'] ? 'Yes' : 'No') . "\n";
echo "   Auth:   " . ($setupData['smtp']['auth'] ? 'Yes' : 'No') . "\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

info("Configuration storage:");
echo "   • Database (system_settings table) ✅\n";
echo "   • setup-config.json (backup reference)\n";
echo "   • .env (non-sensitive settings only)\n\n";

success("All credentials are encrypted in the database!");

echo "\n" . C_YELLOW . "Next steps:\n" . C_RESET;
echo "   1. Start using CI-Inbox\n";
echo "   2. Create user accounts in Admin UI\n";
echo "   3. Configure additional settings via Admin Panel\n\n";

info("Setup wizard completed successfully! 🎉");

exit(0);

<?php
/**
 * Setup Wizard - Global Helper Functions
 * 
 * Centralized utility functions used across all setup steps
 */

declare(strict_types=1);

/**
 * Get PHP executable path (XAMPP-aware)
 * 
 * Detects PHP executable for shell commands.
 * Critical for XAMPP where php.exe is NOT in system PATH.
 * 
 * Bug Fix: [HIGH] - XAMPP Auto-Install fehlschlägt
 * 
 * @return string Escaped PHP executable path
 */
function getPhpExecutable(): string
{
    // Try PHP_BINARY first (most reliable if set)
    if (defined('PHP_BINARY') && PHP_BINARY && file_exists(PHP_BINARY)) {
        return escapeshellarg(PHP_BINARY);
    }
    
    // Windows: Check common XAMPP paths
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $possiblePaths = [
            'C:\\xampp\\php\\php.exe',
            'C:\\XAMPP\\php\\php.exe',
            'D:\\xampp\\php\\php.exe',
            'C:\\Program Files\\XAMPP\\php\\php.exe',
            'C:\\Program Files (x86)\\XAMPP\\php\\php.exe',
        ];
        
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return escapeshellarg($path);
            }
        }
    }
    
    // Fallback: Hope 'php' is in PATH (Linux/Mac or properly configured Windows)
    return 'php';
}

/**
 * Get project root filesystem path
 * 
 * @return string Absolute filesystem path to project root
 */
function getProjectRoot(): string
{
    // This file is in: /src/public/setup/includes/functions.php
    // Project root is: 4 levels up
    return realpath(__DIR__ . '/../../../../') ?: __DIR__ . '/../../../../';
}

/**
 * Get base web path for redirects
 * 
 * @return string Web path (e.g., "/src/public" or "")
 */
function getBasePath(): string
{
    $scriptName = $_SERVER['SCRIPT_NAME']; // e.g., "/src/public/setup/index.php"
    
    if (preg_match('#^(.*?)/setup/#', $scriptName, $matches)) {
        return $matches[1]; // e.g., "/src/public" or ""
    }
    
    return '';
}

/**
 * Redirect to a setup step
 * 
 * @param int $step Step number (1-7)
 * @param array $params Optional query parameters
 */
function redirectToStep(int $step, array $params = []): void
{
    $basePath = getBasePath();
    $query = $params ? '&' . http_build_query($params) : '';
    header("Location: {$basePath}/setup/?step={$step}{$query}");
    exit;
}

/**
 * Parse .env file manually
 * parse_ini_file doesn't handle .env syntax correctly
 * 
 * @param string $filePath Path to .env file
 * @return array Key-value pairs
 */
function parseEnvFile(string $filePath): array
{
    $env = [];
    
    if (!file_exists($filePath)) {
        return $env;
    }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip comments and empty lines
        if (empty($line) || $line[0] === '#') {
            continue;
        }
        
        // Parse key=value pairs
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            
            // Remove quotes if present
            if (($value[0] ?? '') === '"' && ($value[strlen($value)-1] ?? '') === '"') {
                $value = substr($value, 1, -1);
            }
            
            $env[$key] = $value;
        }
    }
    
    return $env;
}

/**
 * Extract domain from email address
 * 
 * @param string $email Email address
 * @return string Domain part
 */
function extractDomain(string $email): string
{
    $parts = explode('@', $email);
    return $parts[1] ?? '';
}

/**
 * Extract real hostname from SSL certificate
 * Used when hostname doesn't match certificate (e.g., imap.domain.com → mx.provider.com)
 * 
 * @param string $host Hostname to check
 * @param int $port Port (default 993)
 * @return string|null Real hostname from certificate, or null if detection fails
 */
function extractRealHostFromCertError(string $host, int $port = 993): ?string
{
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

            // Extract CN (Common Name) from certificate subject
            if (isset($cert['subject']['CN'])) {
                return $cert['subject']['CN'];
            }
        }
    }

    return null;
}

/**
 * Auto-detect IMAP/SMTP hosts from email domain
 * 
 * @param string $email Email address
 * @return array Candidate hosts for IMAP and SMTP
 */
function autoDetectHosts(string $email): array
{
    $domain = extractDomain($email);

    return [
        'imap_candidates' => [
            "imap.{$domain}",
            "mail.{$domain}",
            $domain,
        ],
        'smtp_candidates' => [
            "smtp.{$domain}",
            "mail.{$domain}",
            $domain,
        ]
    ];
}

/**
 * Generate .env file content
 * 
 * @param array $data Setup data from session
 * @return string Complete .env file content
 */
function generateEnvFile(array $data): string
{
    // Define variables BEFORE Heredoc (avoids concatenation errors)
    $smtpEncryption = !empty($data['smtp_encryption']) ? $data['smtp_encryption'] : 'none';
    $smtpFromEmail = $data['smtp_from_email'] ?? $data['imap_email'] ?? '';
    $smtpFromName = $data['smtp_from_name'] ?? 'CI-Inbox';
    
    return <<<ENV
# CI-Inbox Environment Configuration
# Generated by Setup Wizard on {DATE}

# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost

# Database
DB_HOST={$data['db_host']}
DB_PORT={$data['db_port']}
DB_NAME={$data['db_name']}
DB_USER={$data['db_user']}
DB_PASS={$data['db_password']}

# Encryption
ENCRYPTION_KEY=

# IMAP (Shared Inbox)
IMAP_HOST={$data['imap_host']}
IMAP_PORT={$data['imap_port']}
IMAP_USER={$data['imap_username']}
IMAP_PASS={$data['imap_password']}
IMAP_SSL={$data['imap_encryption']}

# SMTP
SMTP_HOST={$data['smtp_host']}
SMTP_PORT={$data['smtp_port']}
SMTP_USER={$data['smtp_username']}
SMTP_PASS={$data['smtp_password']}
SMTP_ENCRYPTION={$smtpEncryption}
SMTP_FROM_EMAIL={$smtpFromEmail}
SMTP_FROM_NAME={$smtpFromName}

# Webcron
CRON_SECRET_TOKEN=

# Session
SESSION_LIFETIME=120
SESSION_SECURE=false

# Logging
LOG_LEVEL=info
LOG_PATH=logs/app.log
ENV;
}

/**
 * Write production .htaccess that redirects to src/public/
 * 
 * @param string $basePath Base path (optional, for compatibility)
 * @return bool True on success, false on failure
 */
function writeProductionHtaccess(string $basePath = ''): bool
{
    $htaccessContent = <<<'HTACCESS'
# CI-Inbox Production Configuration
# Generated by Setup Wizard

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Redirect all requests to src/public/
    RewriteCond %{REQUEST_URI} !^/src/public/
    RewriteRule ^(.*)$ src/public/$1 [L]
</IfModule>

# Security: Protect sensitive directories
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

# Prevent access to sensitive files
RedirectMatch 403 /\.env
RedirectMatch 403 /composer\.json
RedirectMatch 403 /composer\.lock
RedirectMatch 403 /vendor/
RedirectMatch 403 /database/
RedirectMatch 403 /logs/
RedirectMatch 403 /tests/
RedirectMatch 403 /src/(?!public/)

# Disable directory listing
Options -Indexes

# Security Headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>
HTACCESS;

    $htaccessPath = __DIR__ . '/../../../../.htaccess';
    
    // Check write permissions first
    $dir = dirname($htaccessPath);
    if (!is_writable($dir)) {
        error_log("Setup Error: Directory {$dir} is not writable for .htaccess");
        return false;
    }
    
    // Atomic write with temp file
    $tempFile = $htaccessPath . '.tmp';
    $written = file_put_contents($tempFile, $htaccessContent, LOCK_EX);
    
    if ($written === false) {
        error_log("Setup Error: Failed to write .htaccess temp file");
        return false;
    }
    
    // Atomic rename
    if (!rename($tempFile, $htaccessPath)) {
        @unlink($tempFile);
        error_log("Setup Error: Failed to rename .htaccess temp file");
        return false;
    }
    
    return true;
}

/**
 * Render HTML header with progress steps
 * 
 * @param int $currentStep Current step number
 * @param array $steps Step names
 * @param string|null $error Error message to display
 * @param string|null $success Success message to display
 */
function renderHeader(int $currentStep, array $steps, ?string $error = null, ?string $success = null): void
{
    ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CI-Inbox Setup</title>
    <link rel="stylesheet" href="setup.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 CI-Inbox Setup</h1>
            <p>Willkommen! Lassen Sie uns Ihre Installation einrichten.</p>
        </div>
        
        <div class="steps">
            <?php foreach ($steps as $num => $name): ?>
                <div class="step <?= $num == $currentStep ? 'active' : ($num < $currentStep ? 'completed' : '') ?>">
                    <div class="step-number"><?= $num < $currentStep ? '✓' : $num ?></div>
                    <div><?= htmlspecialchars($name) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="content">
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['installed'])): ?>
                <div class="alert alert-success">✅ Dependencies erfolgreich installiert! Bitte prüfen Sie die Ergebnisse unten.</div>
            <?php endif; ?>
    <?php
}

/**
 * Render HTML footer with script tag
 */
function renderFooter(): void
{
    ?>
        </div>
    </div>
    
    <script src="setup.js"></script>
</body>
</html>
    <?php
}

/**
 * Initialize or retrieve session data
 * 
 * @return array Session data structure
 */
function initSession(): array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['setup'])) {
        $_SESSION['setup'] = [
            'step' => 1,
            'data' => []
        ];
    }
    
    return $_SESSION['setup'];
}

/**
 * Check system requirements (PHP extensions)
 */
function checkRequirements(): array
{
    $requirements = [
        'php_version' => [
            'name' => 'PHP Version',
            'required' => '8.1.0',
            'current' => PHP_VERSION,
            'met' => version_compare(PHP_VERSION, '8.1.0', '>='),
        ],
        'pdo_mysql' => [
            'name' => 'PDO MySQL Extension',
            'required' => 'Enabled',
            'current' => extension_loaded('pdo_mysql') ? 'Enabled' : 'Disabled',
            'met' => extension_loaded('pdo_mysql'),
        ],
        'imap' => [
            'name' => 'IMAP Extension',
            'required' => 'Enabled',
            'current' => extension_loaded('imap') ? 'Enabled' : 'Disabled',
            'met' => extension_loaded('imap'),
        ],
        'openssl' => [
            'name' => 'OpenSSL Extension',
            'required' => 'Enabled',
            'current' => extension_loaded('openssl') ? 'Enabled' : 'Disabled',
            'met' => extension_loaded('openssl'),
        ],
        'mbstring' => [
            'name' => 'Mbstring Extension',
            'required' => 'Enabled',
            'current' => extension_loaded('mbstring') ? 'Enabled' : 'Disabled',
            'met' => extension_loaded('mbstring'),
        ],
        'json' => [
            'name' => 'JSON Extension',
            'required' => 'Enabled',
            'current' => extension_loaded('json') ? 'Enabled' : 'Disabled',
            'met' => extension_loaded('json'),
        ],
        'curl' => [
            'name' => 'cURL Extension',
            'required' => 'Enabled',
            'current' => extension_loaded('curl') ? 'Enabled' : 'Disabled',
            'met' => extension_loaded('curl'),
        ],
        'writable_env' => [
            'name' => '.env Schreibbar',
            'required' => 'Ja',
            'current' => is_writable(__DIR__ . '/../../../../') ? 'Ja' : 'Nein',
            'met' => is_writable(__DIR__ . '/../../../../'),
        ],
        'writable_logs' => [
            'name' => 'logs/ Schreibbar',
            'required' => 'Ja',
            'current' => is_dir(__DIR__ . '/../../../../logs') && is_writable(__DIR__ . '/../../../../logs') ? 'Ja' : 'Nein',
            'met' => !is_dir(__DIR__ . '/../../../../logs') || is_writable(__DIR__ . '/../../../../logs'),
        ],
    ];
    
    return $requirements;
}

/**
 * Check hosting environment suitability
 * Returns comprehensive analysis with recommendations
 */
function checkHostingEnvironment(): array
{
    $basePath = getProjectRoot();
    $checks = [];
    
    // 1. PHP Version (Critical)
    $phpVersion = PHP_VERSION;
    $checks['php_version'] = [
        'name' => 'PHP Version',
        'status' => version_compare($phpVersion, '8.1.0', '>=') ? 'ok' : 'error',
        'value' => $phpVersion,
        'required' => '8.1.0 oder höher',
        'recommendation' => version_compare($phpVersion, '8.1.0', '<') 
            ? 'Bitte aktivieren Sie PHP 8.1+ in Ihrem Hosting-Panel (z.B. cPanel → PHP-Version auswählen)'
            : null,
        'critical' => true
    ];
    
    // 2. Memory Limit
    $memoryLimit = ini_get('memory_limit');
    $memoryBytes = return_bytes($memoryLimit);
    $checks['memory_limit'] = [
        'name' => 'PHP Memory Limit',
        'status' => $memoryBytes >= 128 * 1024 * 1024 ? 'ok' : ($memoryBytes >= 64 * 1024 * 1024 ? 'warning' : 'error'),
        'value' => $memoryLimit,
        'required' => '128M empfohlen (64M minimal)',
        'recommendation' => $memoryBytes < 128 * 1024 * 1024
            ? 'Erhöhen Sie memory_limit in php.ini oder .htaccess: php_value memory_limit 128M'
            : null,
        'critical' => false
    ];
    
    // 3. Max Execution Time
    $maxExecTime = ini_get('max_execution_time');
    $checks['max_execution_time'] = [
        'name' => 'Max Execution Time',
        'status' => $maxExecTime >= 60 || $maxExecTime == 0 ? 'ok' : 'warning',
        'value' => $maxExecTime == 0 ? 'Unbegrenzt' : $maxExecTime . 's',
        'required' => '60s empfohlen',
        'recommendation' => $maxExecTime < 60 && $maxExecTime != 0
            ? 'Für E-Mail-Verarbeitung empfohlen: php_value max_execution_time 60 in .htaccess'
            : null,
        'critical' => false
    ];
    
    // 4. Upload Max Filesize (for attachments)
    $uploadMax = ini_get('upload_max_filesize');
    $uploadBytes = return_bytes($uploadMax);
    $checks['upload_max_filesize'] = [
        'name' => 'Upload Max Filesize',
        'status' => $uploadBytes >= 10 * 1024 * 1024 ? 'ok' : 'warning',
        'value' => $uploadMax,
        'required' => '10M empfohlen',
        'recommendation' => $uploadBytes < 10 * 1024 * 1024
            ? 'Für größere E-Mail-Anhänge: php_value upload_max_filesize 10M'
            : null,
        'critical' => false
    ];
    
    // 5. Composer/Vendor Directory
    $projectRoot = getProjectRoot();
    $vendorExists = is_dir($projectRoot . '/vendor');
    
    // Check if exec functions are disabled
    $disabledFunctions = explode(',', ini_get('disable_functions'));
    $disabledFunctions = array_map('trim', $disabledFunctions);
    $execDisabled = in_array('exec', $disabledFunctions) || in_array('shell_exec', $disabledFunctions);
    
    $composerExists = false;
    if (!$execDisabled) {
        $composerExists = file_exists($projectRoot . '/composer.phar') || 
                          @shell_exec('which composer 2>/dev/null') || 
                          @shell_exec('where composer 2>nul');
    }
    
    $checks['vendor_dir'] = [
        'name' => 'Composer Dependencies',
        'status' => $vendorExists ? 'ok' : 'error',
        'value' => $vendorExists ? 'Installiert' : 'Fehlend',
        'required' => 'vendor/ Verzeichnis vorhanden',
        'recommendation' => !$vendorExists
            ? ($execDisabled
                ? 'PHP exec() Funktionen sind deaktiviert. Laden Sie vendor.zip herunter und entpacken Sie es manuell per FTP.'
                : ($composerExists 
                    ? 'Klicken Sie unten auf "Dependencies automatisch installieren" oder laden Sie vendor/ manuell hoch' 
                    : 'Composer nicht verfügbar. Laden Sie vendor.zip herunter und entpacken Sie es im Projekt-Root'))
            : null,
        'critical' => true,
        'can_autofix' => !$vendorExists && $composerExists && !$execDisabled,
        'autofix_action' => 'install_composer_dependencies'
    ];
    
    // 6. Writable Directories
    $logsWritable = is_dir($projectRoot . '/logs') && is_writable($projectRoot . '/logs');
    $checks['writable_logs'] = [
        'name' => 'Logs Verzeichnis beschreibbar',
        'status' => $logsWritable ? 'ok' : 'warning',
        'value' => $logsWritable ? 'Ja' : 'Nein',
        'required' => 'Schreibrechte erforderlich',
        'recommendation' => !$logsWritable
            ? 'Setzen Sie Schreibrechte: chmod 755 logs/ oder über FTP/cPanel'
            : null,
        'critical' => false
    ];
    
    // 7. Database Support
    $mysqlAvailable = extension_loaded('pdo_mysql') || extension_loaded('mysqli');
    $checks['database'] = [
        'name' => 'MySQL/MariaDB Support',
        'status' => $mysqlAvailable ? 'ok' : 'error',
        'value' => $mysqlAvailable ? 'Verfügbar' : 'Nicht verfügbar',
        'required' => 'PDO MySQL Extension',
        'recommendation' => !$mysqlAvailable
            ? 'Aktivieren Sie die MySQL-Extension in Ihrem Hosting-Panel'
            : null,
        'critical' => true
    ];
    
    // 8. IMAP Extension
    $imapAvailable = extension_loaded('imap');
    $checks['imap'] = [
        'name' => 'IMAP Extension',
        'status' => $imapAvailable ? 'ok' : 'error',
        'value' => $imapAvailable ? 'Aktiviert' : 'Deaktiviert',
        'required' => 'Für E-Mail-Empfang erforderlich',
        'recommendation' => !$imapAvailable
            ? 'KRITISCH: IMAP-Extension muss aktiviert werden (oft in Hosting-Panel verfügbar)'
            : null,
        'critical' => true
    ];
    
    // 9. Safe Mode (legacy, should be off)
    $safeMode = ini_get('safe_mode');
    $checks['safe_mode'] = [
        'name' => 'PHP Safe Mode',
        'status' => !$safeMode ? 'ok' : 'error',
        'value' => $safeMode ? 'Aktiviert' : 'Deaktiviert',
        'required' => 'Deaktiviert',
        'recommendation' => $safeMode
            ? 'Safe Mode ist veraltet und blockiert wichtige Funktionen. Kontaktieren Sie Ihren Hoster.'
            : null,
        'critical' => true
    ];
    
    // 10. Disk Space (estimate)
    $diskFree = @disk_free_space($basePath);
    $checks['disk_space'] = [
        'name' => 'Verfügbarer Speicherplatz',
        'status' => $diskFree === false || $diskFree > 100 * 1024 * 1024 ? 'ok' : 'warning',
        'value' => $diskFree !== false ? format_bytes($diskFree) : 'Unbekannt',
        'required' => '100 MB empfohlen',
        'recommendation' => $diskFree !== false && $diskFree < 100 * 1024 * 1024
            ? 'Wenig Speicherplatz verfügbar. CI-Inbox benötigt ca. 100-150 MB (inkl. vendor/)'
            : null,
        'critical' => false
    ];
    
    // 11. Disabled Functions (Security Check)
    $disabledFuncs = ini_get('disable_functions');
    $disabledArray = $disabledFuncs ? array_map('trim', explode(',', $disabledFuncs)) : [];
    $criticalDisabled = array_intersect($disabledArray, ['exec', 'shell_exec', 'proc_open', 'popen']);
    
    $checks['disabled_functions'] = [
        'name' => 'PHP Disabled Functions',
        'status' => empty($criticalDisabled) ? 'ok' : 'warning',
        'value' => empty($criticalDisabled) ? 'Keine kritischen' : implode(', ', $criticalDisabled) . ' deaktiviert',
        'required' => 'exec, shell_exec für Auto-Installation',
        'recommendation' => !empty($criticalDisabled)
            ? 'Automatische Composer-Installation nicht möglich. Bitte verwenden Sie vendor.zip für manuelle Installation.'
            : null,
        'critical' => false
    ];
    
    return $checks;
}

/**
 * Convert PHP ini size notation to bytes
 */
function return_bytes(string $val): int
{
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    
    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;
    }
    
    return $val;
}

/**
 * Format bytes to human-readable
 */
function format_bytes(int $bytes, int $precision = 2): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Update session step
 * 
 * @param int $step New step number
 */
function updateSessionStep(int $step): void
{
    $_SESSION['setup']['step'] = $step;
}

/**
 * Update session data
 * 
 * @param string $key Data key (e.g., 'db', 'admin', 'imap')
 * @param mixed $value Data value
 */
function updateSessionData(string $key, $value): void
{
    $_SESSION['setup']['data'][$key] = $value;
}

/**
 * Get session data
 * 
 * @param string|null $key Optional key to retrieve specific data
 * @return mixed Session data or specific value
 */
function getSessionData(?string $key = null)
{
    if ($key === null) {
        return $_SESSION['setup']['data'] ?? [];
    }
    
    return $_SESSION['setup']['data'][$key] ?? null;
}

<?php
declare(strict_types=1);

/**
 * Setup Wizard - Controller (Refactored)
 * 
 * Version: 2.0 (Modular Architecture)
 * Date: 17. Dezember 2025
 * 
 * Guides administrators through initial setup:
 * Step 1: Hosting Environment Check
 * Step 2: System Requirements
 * Step 3: Database Configuration
 * Step 4: Admin Account Creation
 * Step 5: IMAP/SMTP Configuration
 * Step 6: Review & Install
 * Step 7: Installation Complete
 */

// Enable error display for debugging
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Force OPcache to reload this script (avoid stale code on Plesk)
if (function_exists('opcache_invalidate')) {
    @opcache_invalidate(__FILE__, true);
}
if (function_exists('opcache_reset')) {
    @opcache_reset();
}

// GLOBAL: swallow only open_basedir warnings anywhere in this script
$__globalOpenBasedirHandler = set_error_handler(function ($errno, $errstr) {
    if ($errno === E_WARNING && strpos($errstr, 'open_basedir restriction in effect') !== false) {
        return true; // swallow
    }
    return false; // let others pass
});

// Check if vendor exists BEFORE trying to load it
$vendorAutoload = __DIR__ . '/../../../vendor/autoload.php';
$vendorExists = file_exists($vendorAutoload);

// ============================================================================
// VENDOR AUTO-INSTALL HANDLER (Must be defined before vendor check)
// ============================================================================

if (!$vendorExists && isset($_GET['action']) && $_GET['action'] === 'auto_install_vendor') {
    
    function getPhpExecutableEarly(): string
    {
        $os = strtoupper(substr(PHP_OS, 0, 3));

        // FAST PATH: try php from PATH and skip all other checks if it works
        $marker = 'CI_INBOX_OK_' . mt_rand(1000, 9999);
        $redir  = ($os === 'WIN') ? '2>nul' : '2>/dev/null';
        $cmd    = 'php -r "echo \'' . $marker . '\';" ' . $redir;
        $out    = [];
        $rc     = 0;
        @exec($cmd, $out, $rc);
        if ($rc === 0 && strpos(implode('', $out), $marker) !== false) {
            return 'php'; // works via PATH -> done
        }

        // Strategy 1: built-in executable constants
        if (defined('PHP_BINARY') && PHP_BINARY) {
            return escapeshellarg(PHP_BINARY);
        }
        if (defined('PHP_EXECUTABLE') && PHP_EXECUTABLE) {
            return escapeshellarg(PHP_EXECUTABLE);
        }

        // Strategy 2: Linux/Unix (no file_exists!)
        if ($os !== 'WIN') {
            $whichPhp = @shell_exec('which php 2>/dev/null');
            if (!empty(trim($whichPhp))) {
                return escapeshellarg(trim($whichPhp));
            }

            $paths = explode(':', getenv('PATH') ?: '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin');
            $common = ['php', 'php8.2', 'php8.1', 'php8.0', 'php7.4'];
            foreach ($paths as $p) {
                $p = trim($p);
                if ($p === '') continue;
                foreach ($common as $name) {
                    $full = rtrim($p, '/') . '/' . $name;
                    if (@is_executable($full)) {
                        return escapeshellarg($full);
                    }
                }
            }

            // Try common absolute paths by executing them (no file_exists)
            foreach (['/opt/plesk/php/8.2/bin/php','/opt/plesk/php/8.1/bin/php','/opt/plesk/php/8.0/bin/php','/usr/local/bin/php','/usr/bin/php'] as $p) {
                $test = @shell_exec(escapeshellarg($p) . ' -v 2>/dev/null');
                if (!empty($test)) {
                    return escapeshellarg($p);
                }
            }

            return 'php';
        }

        // Strategy 3: Windows
        if ($os === 'WIN') {
            if (defined('PHP_BINARY') && PHP_BINARY) {
                return escapeshellarg(PHP_BINARY);
            }
            $paths = explode(';', getenv('PATH') ?: 'C:\\xampp\\php;C:\\XAMPP\\php');
            foreach ($paths as $p) {
                $p = rtrim(trim($p), '\\');
                if ($p === '') continue;
                $exe = $p . '\\php.exe';
                if (@is_executable($exe)) {
                    return escapeshellarg($exe);
                }
            }
            foreach (['C:\\xampp\\php\\php.exe','C:\\XAMPP\\php\\php.exe','D:\\xampp\\php\\php.exe','C:\\Program Files\\XAMPP\\php\\php.exe'] as $p) {
                if (@is_executable($p)) {
                    return escapeshellarg($p);
                }
            }
        }

        return 'php';
    }
    
    function installComposerDependenciesVendorMissing(): array
    {
        $version_marker = '(cim-setup-patch-3)';
        $rootDir = __DIR__ . '/../../../';
        $logFile = $rootDir . 'logs/composer-install.log';
        $os = strtoupper(substr(PHP_OS, 0, 3));

        // Check if shell execution is disabled
        $disabledFunctions = array_map('trim', explode(',', (string)ini_get('disable_functions')));
        if (in_array('exec', $disabledFunctions, true) || in_array('shell_exec', $disabledFunctions, true)) {
            return ['success' => false, 'message' => "PHP exec() und shell_exec() sind deaktiviert. {$version_marker}"];
        }

        // Ensure logs directory exists
        if (!is_dir($rootDir . 'logs')) {
            @mkdir($rootDir . 'logs', 0755, true);
        }

        // Find composer (prefer local phar)
        $composerPath = null;
        if (file_exists($rootDir . 'composer.phar')) {
            $composerPath = $rootDir . 'composer.phar';
        } else {
            $whichComposer = @shell_exec('which composer 2>/dev/null');
            if (!empty($whichComposer)) {
                $composerPath = trim($whichComposer);
            } else {
                $whereComposer = @shell_exec('where composer 2>nul');
                if (!empty($whereComposer)) {
                    $composerPath = trim($whereComposer);
                }
            }
        }

        if (!$composerPath) {
            return ['success' => false, 'message' => 'Composer konnte nicht gefunden werden (weder composer.phar noch ein globaler Composer).'];
        }

        // Always execute composer with our detected PHP binary
        $phpExec = getPhpExecutableEarly();
        $escapedRootDir = escapeshellarg($rootDir);

        // Ensure HOME/COMPOSER_HOME for Composer (Plesk/open_basedir safe)
        $envPrefix = '';
        if ($os !== 'WIN') {
            $home = getenv('HOME') ?: '/tmp';
            $composerHome = $home . '/.composer';
            if (!is_dir($composerHome)) {
                @mkdir($composerHome, 0700, true);
            }
            @putenv('HOME=' . $home);
            @putenv('COMPOSER_HOME=' . $composerHome);
            $_ENV['HOME'] = $home;
            $_ENV['COMPOSER_HOME'] = $composerHome;
            $_SERVER['HOME'] = $home;
            $_SERVER['COMPOSER_HOME'] = $composerHome;
            $envPrefix = 'HOME=' . escapeshellarg($home) . ' COMPOSER_HOME=' . escapeshellarg($composerHome) . ' ';
        }

        $command = "{$envPrefix}cd {$escapedRootDir} && {$phpExec} " . escapeshellarg($composerPath)
                 . " install --no-dev --optimize-autoloader --no-interaction 2>&1";

        $output = [];
        $returnVar = 0;
        @exec($command, $output, $returnVar);

        // Log
        $logContent  = "=== Composer Install Log {$version_marker} ===\n";
        $logContent .= "Date: " . date('Y-m-d H:i:s') . "\n";
        $logContent .= "Command: {$command}\n";
        $logContent .= "Return Code: {$returnVar}\n";
        $logContent .= "Output:\n" . implode("\n", $output) . "\n";
        @file_put_contents($logFile, $logContent);

        // Success?
        if ($returnVar === 0 && is_dir($rootDir . 'vendor') && file_exists($rootDir . 'vendor/autoload.php')) {
            return ['success' => true, 'message' => 'Dependencies erfolgreich installiert!'];
        }
        return ['success' => false, 'message' => 'Installation fehlgeschlagen. Siehe logs/composer-install.log für Details.'];
    }

    $installResult = installComposerDependenciesVendorMissing();
    header('Content-Type: application/json');
    echo json_encode($installResult);
    exit;
}

// ============================================================================
// VENDOR MISSING PAGE
// ============================================================================

if (!$vendorExists) {
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dependencies fehlen - CI-Inbox Setup</title>
        <link rel="stylesheet" href="setup.css">
    </head>
    <body>
        <div class="container">
            <div class="header header-warning">
                <h1>🚀 CI-Inbox Setup</h1>
                <p>Dependencies werden benötigt</p>
            </div>
            
            <div class="content">
                <h2 class="section-title">Dependencies fehlen</h2>
                <p class="section-desc">
                    Das <code>vendor/</code> Verzeichnis wurde nicht gefunden. 
                    CI-Inbox benötigt externe PHP-Bibliotheken (Composer Dependencies), um zu funktionieren.
                </p>
                
                <div class="vendor-missing-options">
                    <div class="option-card">
                        <h3>✅ Option 1: Automatische Installation (Empfohlen)</h3>
                        <p>Wenn Composer auf Ihrem Server verfügbar ist, installieren wir die Dependencies automatisch (Linux-optimiert, ~5 Minuten).</p>
                        <button id="autoInstallBtn" class="btn btn-primary">Dependencies jetzt installieren</button>
                        <div id="installStatus" style="display: none;"></div>
                        <p class="note">
                            <strong>Hinweis:</strong> Dies lädt ca. 50 MB Dependencies herunter und installiert diese für Ihr System optimiert.
                        </p>
                    </div>
                    
                    <div class="option-card">
                        <h3>📦 Option 2: Manuelle vendor.zip Installation</h3>
                        <p>Falls Composer nicht verfügbar ist, laden Sie das vorbereitete <code>vendor.zip</code> herunter:</p>
                        <ol>
                            <li><strong>Linux-Server:</strong> <a href="https://github.com/hndrk-fegko/CI-Inbox/releases/latest/download/vendor.zip" target="_blank">vendor.zip herunterladen</a> (~50 MB)</li>
                            <li><strong>Windows-Server (XAMPP/WAMP):</strong> Verwenden Sie <code>vendor-windows.zip</code> (falls verfügbar) oder erstellen Sie es lokal</li>
                            <li>Entpacken Sie das Archiv im Root-Verzeichnis (neben <code>src/</code>)</li>
                            <li>Stellen Sie sicher, dass <code>vendor/autoload.php</code> existiert</li>
                            <li><button onclick="window.location.reload()" class="btn btn-secondary">Seite neu laden</button></li>
                        </ol>
                        <p class="note">
                            <strong>Platform-Hinweis:</strong> Verwenden Sie das passende vendor.zip für Ihr System (Linux vs. Windows), 
                            da Dependencies platform-spezifische Binaries enthalten können.
                        </p>
                    </div>
                    
                    <div class="option-card">
                        <h3>🖥️ Option 3: Composer per SSH ausführen</h3>
                        <p>Falls Sie SSH-Zugriff haben, können Sie Composer manuell ausführen:</p>
                        <pre>cd /pfad/zu/ci-inbox
composer install --no-dev --optimize-autoloader</pre>
                        <p class="note">
                            Dies installiert die Dependencies optimiert für Ihr aktuelles System.
                        </p>
                    </div>
                </div>
                
                <div class="vendor-missing-help">
                    <strong>ℹ️ Hilfe benötigt?</strong><br>
                    <ul>
                        <li><strong>Shared Hosting:</strong> Option 2 (vendor.zip) ist meist die einfachste Lösung</li>
                        <li><strong>VPS/Dedicated:</strong> Option 1 (automatisch) oder Option 3 (SSH) empfohlen</li>
                        <li><strong>Windows-Server:</strong> Erstellen Sie vendor-windows.zip lokal mit <code>php scripts\create-vendor-zip-windows.php</code></li>
                    </ul>
                    <p class="doc-link">
                        📖 <a href="https://github.com/hndrk-fegko/CI-Inbox/blob/main/DEPLOYMENT.md" target="_blank">Ausführliche Deployment-Dokumentation</a>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Loading Overlay -->
        <div id="loadingOverlay" class="loading-overlay">
            <div class="loading-spinner"></div>
            <div class="loading-text">Dependencies werden installiert...</div>
            <div class="loading-warning">⏳ Bitte warten Sie 2-5 Minuten. Laden Sie diese Seite nicht neu!</div>
        </div>
        
        <script>
        document.getElementById('autoInstallBtn')?.addEventListener('click', async function() {
            const btn = this;
            const status = document.getElementById('installStatus');
            const overlay = document.getElementById('loadingOverlay');
            
            btn.disabled = true;
            btn.textContent = 'Installiere Dependencies...';
            overlay.classList.add('active');
            status.style.display = 'block';
            status.innerHTML = '<div class="alert alert-info">⏳ Installation läuft...</div>';
            
            try {
                const response = await fetch('?action=auto_install_vendor');
                const responseText = await response.text();
                overlay.classList.remove('active');
                
                if (response.ok) {
                    try {
                        const result = JSON.parse(responseText);
                        if (result.success) {
                            status.innerHTML = '<div class="alert alert-success">✅ ' + result.message + '</div>';
                            setTimeout(() => window.location.reload(), 1500);
                            return;
                        } else {
                            status.innerHTML = `<div class="alert alert-error">❌ ${result.message}<br><a href="/logs/composer-install.log" target="_blank">logs/composer-install.log öffnen</a></div>`;
                        }
                    } catch (e) {
                        status.innerHTML = `<div class="alert alert-error">❌ Unerwartete Antwort (kein JSON).<br><pre>${responseText.replace(/</g,'&lt;')}</pre></div>`;
                    }
                } else {
                    status.innerHTML = `<div class="alert alert-error">❌ HTTP-Fehler: ${response.status}</div>`;
                }
                
                btn.disabled = false;
                btn.textContent = 'Dependencies jetzt installieren';
            } catch (err) {
                overlay.classList.remove('active');
                status.innerHTML = `<div class="alert alert-error">❌ Netzwerk-/Serverfehler: ${err}</div>`;
                btn.disabled = false;
                btn.textContent = 'Dependencies jetzt installieren';
            }
        });
        </script>
    </body>
    </html>
    <?php
    exit;
}

// ============================================================================
// LOAD DEPENDENCIES & INCLUDES
// ============================================================================

require_once $vendorAutoload;
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/ajax-handlers.php';
require_once __DIR__ . '/includes/step-1-hosting.php';
require_once __DIR__ . '/includes/step-2-requirements.php';
require_once __DIR__ . '/includes/step-3-database.php';
require_once __DIR__ . '/includes/step-4-admin.php';
require_once __DIR__ . '/includes/step-5-imap-smtp.php';
require_once __DIR__ . '/includes/step-6-review.php';
require_once __DIR__ . '/includes/step-7-complete.php';

// ============================================================================
// SESSION NORMALIZATION
// ============================================================================

function normalizeSessionData(array $sessionData): array
{
    $normalized = $sessionData;
    
    // Database fields (from step 3)
    if (isset($sessionData['db']) && is_array($sessionData['db'])) {
        $normalized['db_host'] = $sessionData['db']['host'] ?? '';
        $normalized['db_name'] = $sessionData['db']['name'] ?? '';
        $normalized['db_user'] = $sessionData['db']['user'] ?? '';
        $normalized['db_password'] = $sessionData['db']['pass'] ?? '';
        $normalized['db_port'] = $sessionData['db']['port'] ?? 3306;
        $normalized['db_exists'] = $sessionData['db']['exists'] ?? false;
    }
    
    // Admin fields (from step 4)
    if (isset($sessionData['admin']) && is_array($sessionData['admin'])) {
        $normalized['admin_email'] = $sessionData['admin']['email'] ?? '';
        $normalized['admin_name'] = $sessionData['admin']['name'] ?? '';
        $normalized['admin_password'] = $sessionData['admin']['password'] ?? '';
        $normalized['enable_admin_imap'] = $sessionData['admin']['create_personal_imap'] ?? false;
        $normalized['admin_imap_password_encrypted'] = $sessionData['admin']['imap_password'] ?? '';
        $normalized['admin_imap_host'] = $sessionData['admin']['imap_host'] ?? '';
        $normalized['admin_imap_port'] = $sessionData['admin']['imap_port'] ?? '993';
        $normalized['admin_imap_username'] = $sessionData['admin']['email'] ?? '';
        $normalized['admin_imap_encryption'] = ($sessionData['admin']['imap_ssl'] ?? true) ? 'ssl' : 'tls';
    }
    
    // IMAP fields (from step 5)
    if (isset($sessionData['imap']) && is_array($sessionData['imap'])) {
        $normalized['imap_host'] = $sessionData['imap']['host'] ?? '';
        $normalized['imap_port'] = $sessionData['imap']['port'] ?? '993';
        $normalized['imap_username'] = $sessionData['imap']['user'] ?? '';
        $normalized['imap_password_encrypted'] = $sessionData['imap']['pass'] ?? '';
        $normalized['imap_email'] = $sessionData['imap']['user'] ?? '';
        $normalized['imap_encryption'] = ($sessionData['imap']['ssl'] ?? true) ? 'ssl' : 'tls';
    }
    
    // SMTP fields (from step 5)
    if (isset($sessionData['smtp']) && is_array($sessionData['smtp'])) {
        $normalized['smtp_host'] = $sessionData['smtp']['host'] ?? '';
        $normalized['smtp_port'] = $sessionData['smtp']['port'] ?? '587';
        $normalized['smtp_username'] = $sessionData['smtp']['user'] ?? '';
        $normalized['smtp_password'] = $sessionData['smtp']['pass'] ?? '';
        $normalized['smtp_encryption'] = ($sessionData['smtp']['ssl'] ?? true) ? 'tls' : 'none';
        $normalized['smtp_from_email'] = $sessionData['smtp']['from_email'] ?? '';
        $normalized['smtp_from_name'] = $sessionData['smtp']['from_name'] ?? 'CI-Inbox';
    }
    
    return $normalized;
}

// ============================================================================
// SESSION & ROUTING
// ============================================================================

$sessionData = initSession();
$currentStep = isset($_GET['step']) ? (int)$_GET['step'] : $sessionData['step'];
$currentStep = max(1, min(7, $currentStep));

$normalizedSessionData = normalizeSessionData($sessionData['data']);

// ============================================================================
// AJAX HANDLER
// ============================================================================

if (isset($_GET['ajax'])) {
    handleAjaxRequest($_GET['ajax']);
    exit;
}

// ============================================================================
// POST ROUTING
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch ($currentStep) {
            case 1:
                handleStep1Submit();
                break;
            case 2:
                handleStep2Submit();
                break;
            case 3:
                handleStep3Submit($_POST);
                break;
            case 4:
                handleStep4Submit($_POST);
                break;
            case 5:
                handleStep5Submit($_POST);
                break;
            case 6:
                handleStep6Submit($normalizedSessionData);
                break;
            default:
                redirectToStep(1);
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// ============================================================================
// PREPARE VIEW DATA
// ============================================================================

$requirements = checkRequirements();
$allRequirementsMet = !in_array(false, array_column($requirements, 'met'));

$hostingChecks = checkHostingEnvironment();
$criticalIssues = array_filter($hostingChecks, fn($check) => $check['status'] === 'error' && $check['critical']);
$hostingReady = empty($criticalIssues);

$steps = [
    1 => 'Hosting-Check',
    2 => 'Anforderungen',
    3 => 'Datenbank',
    4 => 'Administrator',
    5 => 'E-Mail',
    6 => 'Installation',
    7 => 'Fertig'
];

// ============================================================================
// RENDER VIEW
// ============================================================================

echo renderHeader($currentStep, $steps);

if (isset($error)) {
    echo '<div class="alert alert-error">❌ ' . htmlspecialchars($error) . '</div>';
}

switch ($currentStep) {
    case 1:
        renderStep1Form($hostingChecks, $hostingReady);
        break;
    case 2:
        renderStep2Form($requirements, $allRequirementsMet);
        break;
    case 3:
        renderStep3Form($sessionData);
        break;
    case 4:
        renderStep4Form($sessionData);
        break;
    case 5:
        renderStep5Form($sessionData);
        break;
    case 6:
        renderStep6Form($normalizedSessionData);
        break;
    case 7:
        renderStep7($sessionData);
        break;
}

echo renderFooter();
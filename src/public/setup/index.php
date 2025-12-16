<?php
/**
 * Setup Wizard - Controller (Refactored)
 * 
 * Version: 2.0 (Modular Architecture)
 * Date: 7. Dezember 2025
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

// Check if vendor exists BEFORE trying to load it
$vendorAutoload = __DIR__ . '/../../../vendor/autoload.php';
$vendorExists = file_exists($vendorAutoload);

// ============================================================================
// VENDOR AUTO-INSTALL HANDLER (Must be defined before vendor check)
// ============================================================================

if (!$vendorExists && isset($_GET['action']) && $_GET['action'] === 'auto_install_vendor') {
    
    // Helper function: Get PHP executable (XAMPP-aware)
    // Must be defined HERE because functions.php can't be loaded without vendor/
    function getPhpExecutableEarly(): string
    {
        if (defined('PHP_BINARY') && PHP_BINARY && file_exists(PHP_BINARY)) {
            return escapeshellarg(PHP_BINARY);
        }
        
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
        
        return 'php';
    }
    
    function installComposerDependenciesVendorMissing(): array
    {
        $rootDir = __DIR__ . '/../../../';
        $logFile = $rootDir . 'logs/composer-install.log';
        
        $disabledFunctions = explode(',', ini_get('disable_functions'));
        $disabledFunctions = array_map('trim', $disabledFunctions);
        
        if (in_array('exec', $disabledFunctions) || in_array('shell_exec', $disabledFunctions)) {
            return ['success' => false, 'message' => 'PHP exec() und shell_exec() sind deaktiviert.'];
        }
        
        if (!is_dir($rootDir . 'logs')) {
            @mkdir($rootDir . 'logs', 0755, true);
        }
        
        $composerCommand = null;
        if (file_exists($rootDir . 'composer.phar')) {
            $composerCommand = 'composer.phar';
        } else {
            $whichComposer = @shell_exec('which composer 2>/dev/null');
            if (!empty($whichComposer)) {
                $composerCommand = 'composer';
            } else {
                $whereComposer = @shell_exec('where composer 2>nul');
                if (!empty($whereComposer)) {
                    $composerCommand = 'composer';
                }
            }
        }
        
        if (!$composerCommand) {
            return ['success' => false, 'message' => 'Composer nicht verfügbar.'];
        }
        
        $escapedRootDir = escapeshellarg($rootDir);
        $phpExec = getPhpExecutableEarly(); // BUG FIX: Use XAMPP-aware PHP path
        $command = "cd {$escapedRootDir} && ";
        
        if ($composerCommand === 'composer.phar') {
            $command .= "{$phpExec} " . escapeshellarg($rootDir . 'composer.phar');
        } else {
            $command .= "composer";
        }
        
        $command .= " install --no-dev --optimize-autoloader --no-interaction 2>&1";
        
        $output = [];
        $returnVar = 0;
        @exec($command, $output, $returnVar);
        
        $logContent = "=== Composer Install Log ===\n";
        $logContent .= "Date: " . date('Y-m-d H:i:s') . "\n";
        $logContent .= "Command: {$command}\n";
        $logContent .= "Return Code: {$returnVar}\n";
        $logContent .= "Output:\n" . implode("\n", $output);
        file_put_contents($logFile, $logContent);
        
        if ($returnVar === 0 && is_dir($rootDir . 'vendor') && file_exists($rootDir . 'vendor/autoload.php')) {
            return ['success' => true, 'message' => 'Dependencies erfolgreich installiert!'];
        } else {
            return ['success' => false, 'message' => 'Installation fehlgeschlagen. Siehe logs/composer-install.log'];
        }
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
                <h1>📦 CI-Inbox Setup</h1>
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
                        <h3>🚀 Option 1: Automatische Installation (Empfohlen)</h3>
                        <p>Wenn Composer auf Ihrem Server verfügbar ist, installieren wir die Dependencies automatisch (Linux-optimiert, ~5 Minuten).</p>
                        <button id="autoInstallBtn" class="btn btn-primary">Dependencies jetzt installieren</button>
                        <div id="installStatus" style="display: none;"></div>
                        <p class="note">
                            <strong>Hinweis:</strong> Dies lädt ca. 50 MB Dependencies herunter und installiert diese für Ihr System optimiert.
                        </p>
                    </div>
                    
                    <div class="option-card">
                        <h3>📥 Option 2: Manuelle vendor.zip Installation</h3>
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
                        <h3>💻 Option 3: Composer per SSH ausführen</h3>
                        <p>Falls Sie SSH-Zugriff haben, können Sie Composer manuell ausführen:</p>
                        <pre>cd /pfad/zu/ci-inbox
composer install --no-dev --optimize-autoloader</pre>
                        <p class="note">
                            Dies installiert die Dependencies optimiert für Ihr aktuelles System.
                        </p>
                    </div>
                </div>
                
                <div class="vendor-missing-help">
                    <strong>💡 Hilfe benötigt?</strong><br>
                    <ul>
                        <li><strong>Shared Hosting:</strong> Option 2 (vendor.zip) ist meist die einfachste Lösung</li>
                        <li><strong>VPS/Dedicated:</strong> Option 1 (automatisch) oder Option 3 (SSH) empfohlen</li>
                        <li><strong>Windows-Server:</strong> Erstellen Sie vendor-windows.zip lokal mit <code>php scripts\create-vendor-zip-windows.php</code></li>
                    </ul>
                    <p class="doc-link">
                        📚 <a href="https://github.com/hndrk-fegko/CI-Inbox/blob/main/DEPLOYMENT.md" target="_blank">Ausführliche Deployment-Dokumentation</a>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Loading Overlay -->
        <div id="loadingOverlay" class="loading-overlay">
            <div class="loading-spinner"></div>
            <div class="loading-text">Dependencies werden installiert...</div>
            <div class="loading-warning">⚠️ Bitte warten Sie 2-5 Minuten. Laden Sie diese Seite nicht neu!</div>
        </div>
        
        <script>
        document.getElementById('autoInstallBtn')?.addEventListener('click', async function() {
            const btn = this;
            const status = document.getElementById('installStatus');
            const overlay = document.getElementById('loadingOverlay');
            
            btn.disabled = true;
            btn.textContent = 'Installiere Dependencies...';
            overlay.classList.add('active'); // Show loading overlay
            status.style.display = 'block';
            status.innerHTML = '<div class="alert alert-info">⏳ Installation läuft...</div>';
            
            try {
                const response = await fetch('?action=auto_install_vendor');
                const result = await response.json();
                
                overlay.classList.remove('active'); // Hide loading overlay
                
                if (result.success) {
                    status.innerHTML = '<div class="alert alert-success">✅ ' + result.message + '</div>';
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    status.innerHTML = '<div class="alert alert-error">❌ ' + result.message + '</div>';
                    btn.disabled = false;
                    btn.textContent = 'Erneut versuchen';
                }
            } catch (error) {
                overlay.classList.remove('active'); // Hide loading overlay
                status.innerHTML = '<div class="alert alert-error">❌ Fehler: ' + error.message + '</div>';
                btn.disabled = false;
                btn.textContent = 'Erneut versuchen';
            }
        });
        </script>
    </body>
    </html>
    <?php
    exit;
}

// ============================================================================
// LOAD DEPENDENCIES
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

/**
 * Normalize nested session structure to flat structure
 * Converts: ['db' => ['host' => 'x']] to ['db_host' => 'x']
 * 
 * @param array $sessionData Raw session data
 * @return array Normalized flat structure
 */
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
        $normalized['admin_imap_password'] = $sessionData['admin']['imap_password'] ?? '';
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
        $normalized['imap_password'] = $sessionData['imap']['pass'] ?? '';
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

// Normalize session data for steps that need flat structure (step 6)
$normalizedSessionData = normalizeSessionData($sessionData);

// ============================================================================
// AJAX HANDLER
// ============================================================================

if (isset($_GET['ajax'])) {
    handleAjaxRequest($_GET['ajax']);
    exit;
}

// ============================================================================
// POST ROUTING (Form Submissions)
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

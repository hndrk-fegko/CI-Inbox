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
        <style>
        /* Loading Overlay */
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        .loading-overlay.active {
            display: flex;
        }
        .loading-spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #10b981;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .loading-text {
            color: white;
            font-size: 18px;
            margin-top: 20px;
            text-align: center;
        }
        .loading-warning {
            color: #fbbf24;
            font-size: 14px;
            margin-top: 10px;
            max-width: 400px;
            text-align: center;
        }
        </style>
    </head>
    <body class="vendor-missing-page">
        <div class="vendor-missing-container">
            <div class="vendor-missing-icon">📦</div>
            <h1>Dependencies fehlen</h1>
            <p class="vendor-missing-desc">
                Das <code>vendor/</code> Verzeichnis wurde nicht gefunden. 
                CI-Inbox benötigt externe PHP-Bibliotheken (Composer Dependencies), um zu funktionieren.
            </p>
            
            <div class="vendor-missing-options">
                <div class="option-card">
                    <h3>🚀 Option 1: Automatische Installation</h3>
                    <p>Wenn Composer auf Ihrem Server verfügbar ist, können wir die Dependencies automatisch installieren.</p>
                    <button id="autoInstallBtn" class="btn btn-primary">Dependencies jetzt installieren</button>
                    <div id="installStatus" style="margin-top: 15px; display: none;"></div>
                </div>
                
                <div class="option-card">
                    <h3>📥 Option 2: Manuelle Installation</h3>
                    <p>Laden Sie das vorbereitete <code>vendor.zip</code> herunter und entpacken Sie es im Projekt-Root:</p>
                    <ol style="text-align: left; margin: 15px 0;">
                        <li>Download: <a href="https://github.com/your-repo/ci-inbox/releases/latest/vendor.zip">vendor.zip</a></li>
                        <li>Entpacken Sie das Archiv im Root-Verzeichnis (neben <code>src/</code>)</li>
                        <li>Stellen Sie sicher, dass <code>vendor/autoload.php</code> existiert</li>
                        <li>Laden Sie diese Seite neu</li>
                    </ol>
                </div>
                
                <div class="option-card">
                    <h3>💻 Option 3: Composer lokal ausführen</h3>
                    <p>Falls Sie lokalen Shell-Zugriff haben:</p>
                    <pre style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin: 10px 0;">cd /path/to/ci-inbox
composer install --no-dev</pre>
                </div>
            </div>
            
            <div class="vendor-missing-help">
                <strong>💡 Hinweis:</strong> Bei Shared-Hosting-Anbietern ist Option 2 (manuelle Installation) oft die einfachste Lösung.
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
// SESSION & ROUTING
// ============================================================================

$sessionData = initSession();
$currentStep = isset($_GET['step']) ? (int)$_GET['step'] : $sessionData['step'];
$currentStep = max(1, min(7, $currentStep));

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
                handleStep3Submit();
                break;
            case 4:
                handleStep4Submit();
                break;
            case 5:
                handleStep5Submit();
                break;
            case 6:
                handleStep6Submit();
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
    echo '<div class="alert alert-error" style="margin: 20px 0;">❌ ' . htmlspecialchars($error) . '</div>';
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
        renderStep6Form($sessionData);
        break;
    case 7:
        renderStep7($sessionData);
        break;
}

echo renderFooter();

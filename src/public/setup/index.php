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

// Force OPcache to reload this script (avoid stale code on Plesk)
if (function_exists('opcache_invalidate')) {
    @opcache_invalidate(__FILE__, true);
}
if (function_exists('opcache_reset')) {
    // Try full reset to evict stale cached copy
    @opcache_reset();
}

// GLOBAL: swallow only open_basedir warnings anywhere in this script
$__globalOpenBasedirHandler = set_error_handler(function ($errno, $errstr) {
    if ($errno === E_WARNING && strpos($errstr, 'open_basedir restriction in effect') !== false) {
        return true; // swallow those warnings
    }
    return false; // let others pass
});

// DEBUG: show lines around 53 to verify live code version
if (isset($_GET['__show_line53'])) {
    header('Content-Type: text/plain; charset=utf-8');
    $lines = @file(__FILE__);
    if ($lines) {
        for ($i = 48; $i <= 58; $i++) {
            $ln = $i + 1;
            echo sprintf('%4d: %s', $ln, $lines[$i] ?? '');
        }
    } else {
        echo "Cannot read file lines.";
    }
    exit;
}

// Check if vendor exists BEFORE trying to load it
$vendorAutoload = __DIR__ . '/../../../vendor/autoload.php';
$vendorExists = file_exists($vendorAutoload);

// ============================================================================
// VENDOR AUTO-INSTALL HANDLER (Must be defined before vendor check)
// ============================================================================
if (!$vendorExists && isset($_GET['action']) && $_GET['action'] === 'auto_install_vendor') {

    // Temporarily suppress open_basedir warnings to keep JSON clean
    $__prevHandler = set_error_handler(function ($errno, $errstr) {
        if ($errno === E_WARNING && strpos($errstr, 'open_basedir restriction in effect') !== false) {
            return true; // swallow
        }
        return false; // let others pass
    });

    // Helper function: Get PHP executable (PATH fast-path first)
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
        $rootDir = __DIR__ . '/../../..//';
        $logFile = $rootDir . 'logs/composer-install.log';

        // Check if shell execution is disabled
        $disabledFunctions = array_map('trim', explode(',', (string)ini_get('disable_functions')));
        if (in_array('exec', $disabledFunctions, true) || in_array('shell_exec', $disabledFunctions, true)) {
            return ['success' => false, 'message' => 'PHP exec() und shell_exec() sind deaktiviert.'];
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

        $command = "cd {$escapedRootDir} && {$phpExec} " . escapeshellarg($composerPath)
                 . " install --no-dev --optimize-autoloader --no-interaction 2>&1";

        $output = [];
        $returnVar = 0;
        @exec($command, $output, $returnVar);

        // Log
        $logContent  = "=== Composer Install Log ===\n";
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

    // Restore error handler before responding
    if ($__prevHandler !== null) {
        set_error_handler($__prevHandler);
    } else {
        restore_error_handler();
    }

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
                const responseText = await response.text();
                console.log("--- Raw Server Response ---");
                console.log(responseText);
                console.log("---------------------------");
                overlay.classList.remove('active'); // Hide loading overlay

                if (response.ok) {
                    try {
                        const result = JSON.parse(responseText);
                        if (result.success) {
                            status.innerHTML = '<div class="alert alert-success">✅ Dependencies erfolgreich installiert. Seite wird neu geladen…</div>';
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
} else {
    // Vendor vorhanden: zur App weiterleiten (anpassbar)
    require_once $vendorAutoload;
    header('Location: /');
    exit;
}

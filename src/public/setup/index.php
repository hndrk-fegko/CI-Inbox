<?php
/**
 * Setup Wizard - First-Run Installation
 * 
 * Guides administrators through initial setup:
 * 1. Requirements check
 * 2. Database configuration
 * 3. Admin account creation
 * 4. IMAP/SMTP configuration
 * 5. Optional: OAuth providers
 */

// Check if vendor exists BEFORE trying to load it
$vendorAutoload = __DIR__ . '/../../../vendor/autoload.php';
$vendorExists = file_exists($vendorAutoload);

// Handle auto-install request BEFORE showing error page
if (!$vendorExists && isset($_GET['action']) && $_GET['action'] === 'auto_install_vendor') {
    // Function must be defined here (can't use autoload without vendor!)
    function installComposerDependenciesVendorMissing(): array
    {
        $rootDir = __DIR__ . '/../../../';
        $logFile = $rootDir . 'logs/composer-install.log';
        
        // Check if exec functions are available
        $disabledFunctions = explode(',', ini_get('disable_functions'));
        $disabledFunctions = array_map('trim', $disabledFunctions);
        
        if (in_array('exec', $disabledFunctions) || in_array('shell_exec', $disabledFunctions)) {
            return [
                'success' => false,
                'message' => 'PHP exec() und shell_exec() sind deaktiviert.'
            ];
        }
        
        // Ensure logs directory exists
        if (!is_dir($rootDir . 'logs')) {
            @mkdir($rootDir . 'logs', 0755, true);
        }
        
        // Check if composer is available
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
            return [
                'success' => false,
                'message' => 'Composer nicht verfügbar.'
            ];
        }
        
        // Run composer install (with proper escaping)
        $escapedRootDir = escapeshellarg($rootDir);
        $command = "cd {$escapedRootDir} && ";
        
        if ($composerCommand === 'composer.phar') {
            $command .= "php " . escapeshellarg($rootDir . 'composer.phar');
        } else {
            $command .= "composer";
        }
        
        $command .= " install --no-dev --optimize-autoloader --no-interaction 2>&1";
        
        $output = [];
        $returnVar = 0;
        @exec($command, $output, $returnVar);
        
        // Log output
        $logContent = "=== Composer Install Log ===\n";
        $logContent .= "Date: " . date('Y-m-d H:i:s') . "\n";
        $logContent .= "Command: {$command}\n";
        $logContent .= "Return Code: {$returnVar}\n";
        $logContent .= "Output:\n" . implode("\n", $output);
        file_put_contents($logFile, $logContent);
        
        if ($returnVar === 0 && is_dir($rootDir . 'vendor') && file_exists($rootDir . 'vendor/autoload.php')) {
            return [
                'success' => true,
                'message' => 'Dependencies erfolgreich installiert!'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Installation fehlgeschlagen. Siehe logs/composer-install.log'
            ];
        }
    }
    
    // Execute installation
    $result = installComposerDependenciesVendorMissing();
    
    if ($result['success']) {
        // Redirect to setup wizard
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    } else {
        // Show error and vendor missing page
        $autoInstallError = $result['message'];
    }
}

if (!$vendorExists) {
    // Show minimal error page without dependencies
    showVendorMissingPage();
    exit;
}

require_once $vendorAutoload;

/**
 * Get base path for redirects
 * Detects if app is running in subdirectory (IONOS) or root (Plesk)
 * 
 * Examples:
 * - Plesk: /src/public/setup/index.php → returns ""
 * - IONOS: /src/public/setup/index.php → returns "/src/public"
 */
function getBasePath(): string
{
    // Get current script path relative to document root
    $scriptName = $_SERVER['SCRIPT_NAME']; // e.g., "/src/public/setup/index.php"
    
    // Extract base path (everything before /setup/)
    if (preg_match('#^(.*?)/setup/#', $scriptName, $matches)) {
        return $matches[1]; // e.g., "/src/public" or ""
    }
    
    return '';
}

/**
 * Show error page when vendor/ is missing
 * This page works WITHOUT any dependencies
 */
function showVendorMissingPage(): void
{
    // Check if auto-install is available
    $disabledFunctions = explode(',', ini_get('disable_functions'));
    $disabledFunctions = array_map('trim', $disabledFunctions);
    $execDisabled = in_array('exec', $disabledFunctions) || in_array('shell_exec', $disabledFunctions);
    
    $composerExists = false;
    if (!$execDisabled) {
        $composerExists = file_exists(__DIR__ . '/../../../composer.phar') || 
                          @shell_exec('which composer 2>/dev/null') || 
                          @shell_exec('where composer 2>nul');
    }
    
    // Check if there was an error from auto-install
    global $autoInstallError;
    
    ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CI-Inbox Setup - Dependencies fehlen</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            max-width: 700px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }
        .header {
            background: #dc2626;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 28px; margin-bottom: 8px; }
        .content { padding: 30px; }
        .alert {
            background: #fef2f2;
            border: 2px solid #fca5a5;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .alert h2 { color: #991b1b; margin-bottom: 10px; }
        .alert p { color: #7f1d1d; line-height: 1.6; }
        .steps {
            background: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .steps h3 { color: #1f2937; margin-bottom: 15px; }
        .steps ol { margin-left: 20px; }
        .steps li { margin: 10px 0; color: #374151; }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-right: 10px;
            margin-top: 10px;
        }
        .btn:hover { background: #2563eb; }
        .btn-secondary { background: #6b7280; }
        .btn-secondary:hover { background: #4b5563; }
        code {
            background: #1f2937;
            color: #10b981;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
        .info-box {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ CI-Inbox Setup</h1>
            <p>Composer Dependencies fehlen</p>
        </div>
        
        <div class="content">
            <?php if (isset($autoInstallError)): ?>
                <div class="alert" style="background: #fef2f2; border-color: #ef4444;">
                    <h2>❌ Automatische Installation fehlgeschlagen</h2>
                    <p><?= htmlspecialchars($autoInstallError) ?></p>
                </div>
            <?php endif; ?>
            
            <div class="alert">
                <h2>Installation kann nicht gestartet werden</h2>
                <p>
                    Das Verzeichnis <code>vendor/</code> fehlt oder ist unvollständig. 
                    CI-Inbox benötigt externe PHP-Bibliotheken (Dependencies), um zu funktionieren.
                </p>
            </div>
            
            <div class="info-box">
                <strong>💡 Was sind Dependencies?</strong><br>
                PHP-Bibliotheken wie Slim Framework, PHPMailer, Eloquent ORM, etc. 
                Diese werden normalerweise mit dem Tool "Composer" installiert.
            </div>
            
            <div class="steps">
                <h3>🔧 Lösung: Dependencies installieren</h3>
                
                <p><strong>Wählen Sie eine der folgenden Methoden:</strong></p>
                
                <?php if ($composerExists && !$execDisabled): ?>
                <!-- Auto-Fix Option as FIRST Option -->
                <div style="background: #f0f9ff; border: 2px solid #3b82f6; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                    <h4 style="color: #1e40af; margin-top: 0;">🚀 Automatische Installation (Empfohlen)</h4>
                    <p style="color: #1e3a8a; margin-bottom: 15px;">
                        Composer wurde auf diesem Server erkannt. Klicken Sie auf den Button für 
                        automatische Installation der Dependencies (dauert 2-5 Minuten).
                    </p>
                    <form method="GET" action="" style="margin: 0;">
                        <input type="hidden" name="action" value="auto_install_vendor">
                        <button type="submit" class="btn" style="background: #10b981; cursor: pointer; border: none; font-family: inherit;">
                            🚀 Jetzt automatisch installieren
                        </button>
                    </form>
                    <p style="color: #6b7280; font-size: 13px; margin-top: 10px;">
                        Nach erfolgreicher Installation werden Sie automatisch zum Setup-Wizard weitergeleitet.
                    </p>
                </div>
                <?php elseif ($execDisabled): ?>
                <div style="background: #fef2f2; border: 2px solid #ef4444; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                    <h4 style="color: #991b1b; margin-top: 0;">⚠️ Automatische Installation nicht möglich</h4>
                    <p style="color: #7f1d1d;">
                        Die PHP-Funktionen <code>exec()</code> und <code>shell_exec()</code> sind 
                        auf diesem Server deaktiviert. Bitte verwenden Sie eine der manuellen Optionen unten.
                    </p>
                </div>
                <?php endif; ?>
                
                <h4 style="margin-top: 20px;">📦 Option <?= ($composerExists && !$execDisabled) ? '2' : '1' ?>: vendor.zip herunterladen (Einfachste Methode)</h4>
                <ol>
                    <li>Laden Sie <strong>vendor.zip</strong> herunter (~50 MB)</li>
                    <li>Entpacken Sie die Datei</li>
                    <li>Laden Sie den Ordner <code>vendor/</code> per FTP ins Projekt-Root hoch</li>
                    <li>Laden Sie diese Seite neu</li>
                </ol>
                <a href="https://github.com/hndrk-fegko/CI-Inbox/releases/latest" class="btn" target="_blank">
                    📥 vendor.zip von GitHub herunterladen
                </a>
                <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?action=check_vendor" class="btn btn-secondary">
                    🔄 Erneut prüfen
                </a>
                
                <h4 style="margin-top: 20px;">💻 Option <?= ($composerExists && !$execDisabled) ? '3' : '2' ?>: Lokal mit Composer (Für Entwickler)</h4>
                <ol>
                    <li>Öffnen Sie ein Terminal auf Ihrem PC</li>
                    <li>Navigieren Sie zum Projekt-Verzeichnis</li>
                    <li>Führen Sie aus: <code>composer install --no-dev</code></li>
                    <li>Laden Sie das komplette Projekt inkl. <code>vendor/</code> per FTP hoch</li>
                </ol>
                
                <h4 style="margin-top: 20px;">🔌 Option <?= ($composerExists && !$execDisabled) ? '4' : '3' ?>: SSH-Zugang (Falls verfügbar)</h4>
                <ol>
                    <li>Verbinden Sie sich per SSH mit Ihrem Server</li>
                    <li>Navigieren Sie zum Projekt-Verzeichnis</li>
                    <li>Führen Sie aus: <code>composer install --no-dev --optimize-autoloader</code></li>
                    <li>Laden Sie diese Seite neu</li>
                </ol>
            </div>
            
            <div class="info-box">
                <strong>📖 Detaillierte Anleitung:</strong><br>
                Siehe <code>VENDOR-INSTALLATION.md</code> im Projekt-Root für eine Schritt-für-Schritt-Anleitung.
            </div>
            
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center; color: #6b7280; font-size: 14px;">
                <p>Benötigen Sie Hilfe? Kontaktieren Sie Ihren Hosting-Anbieter oder öffnen Sie ein Issue auf GitHub.</p>
            </div>
        </div>
    </div>
</body>
</html>
    <?php
}

// Check if user clicked "Erneut prüfen"
if (isset($_GET['action']) && $_GET['action'] === 'check_vendor') {
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Check if already configured
if (file_exists(__DIR__ . '/../../../.env') && !isset($_GET['force'])) {
    // Check if setup is complete by trying DB connection
    try {
        $env = parse_ini_file(__DIR__ . '/../../../.env');
        if (!empty($env['DB_HOST']) && !empty($env['DB_DATABASE'])) {
            $pdo = new PDO(
                "mysql:host={$env['DB_HOST']};dbname={$env['DB_DATABASE']};charset=utf8mb4",
                $env['DB_USERNAME'] ?? 'root',
                $env['DB_PASSWORD'] ?? ''
            );
            // Check if admin user exists
            $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
            if ($stmt && $stmt->fetchColumn() > 0) {
                $basePath = getBasePath();
                header("Location: {$basePath}/login.php");
                exit;
            }
        }
    } catch (Exception $e) {
        // Continue with setup
    }
}

session_start();

// Initialize setup state
if (!isset($_SESSION['setup'])) {
    $_SESSION['setup'] = [
        'step' => 1,
        'data' => []
    ];
}

$currentStep = $_GET['step'] ?? $_SESSION['setup']['step'];
$error = null;
$success = null;

// Handle auto-fix actions (via GET)
if (isset($_GET['action'])) {
    try {
        switch ($_GET['action']) {
            case 'install_composer_dependencies':
                $result = installComposerDependencies();
                if ($result['success']) {
                    $success = $result['message'];
                    // Refresh page to recheck
                    $basePath = getBasePath();
                    header("Location: {$basePath}/setup/?step=1&installed=1");
                    exit;
                } else {
                    $error = $result['message'];
                }
                break;
        }
    } catch (Exception $e) {
        $error = 'Auto-Fix fehlgeschlagen: ' . $e->getMessage();
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch ($currentStep) {
            case 1:
                // Hosting environment check - just proceed
                $_SESSION['setup']['step'] = 2;
                $basePath = getBasePath();
                header("Location: {$basePath}/setup/?step=2");
                exit;
                
            case 2:
                // Requirements check - just proceed
                $_SESSION['setup']['step'] = 3;
                $basePath = getBasePath();
                header("Location: {$basePath}/setup/?step=3");
                exit;
                
            case 3:
                // Database configuration
                $dbHost = $_POST['db_host'] ?? 'localhost';
                $dbName = $_POST['db_name'] ?? 'ci_inbox';
                $dbUser = $_POST['db_user'] ?? 'root';
                $dbPass = $_POST['db_pass'] ?? '';
                
                // Validate database name (only allow safe characters)
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
                    throw new Exception('Ungültiger Datenbankname. Nur Buchstaben, Zahlen und Unterstriche erlaubt.');
                }
                
                // Validate host (basic sanitization)
                $dbHost = preg_replace('/[^a-zA-Z0-9\.\-:]/', '', $dbHost);
                
                // Test connection
                $pdo = new PDO(
                    "mysql:host={$dbHost};charset=utf8mb4",
                    $dbUser,
                    $dbPass,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                
                // Create database if not exists (using backticks and validated name)
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                
                // Store configuration
                $_SESSION['setup']['data']['db'] = [
                    'host' => $dbHost,
                    'name' => $dbName,
                    'user' => $dbUser,
                    'pass' => $dbPass
                ];
                
                $_SESSION['setup']['step'] = 4;
                $basePath = getBasePath();
                header("Location: {$basePath}/setup/?step=4");
                exit;
                
            case 4:
                // Admin account
                $email = filter_var($_POST['admin_email'] ?? '', FILTER_VALIDATE_EMAIL);
                $name = trim($_POST['admin_name'] ?? '');
                $password = $_POST['admin_password'] ?? '';
                $passwordConfirm = $_POST['admin_password_confirm'] ?? '';
                
                if (!$email) {
                    throw new Exception('Bitte geben Sie eine gültige E-Mail-Adresse ein.');
                }
                if (empty($name)) {
                    throw new Exception('Bitte geben Sie einen Namen ein.');
                }
                if (strlen($password) < 8) {
                    throw new Exception('Das Passwort muss mindestens 8 Zeichen lang sein.');
                }
                if ($password !== $passwordConfirm) {
                    throw new Exception('Die Passwörter stimmen nicht überein.');
                }
                
                $_SESSION['setup']['data']['admin'] = [
                    'email' => $email,
                    'name' => $name,
                    'password' => $password
                ];
                
                $_SESSION['setup']['step'] = 5;
                $basePath = getBasePath();
                header("Location: {$basePath}/setup/?step=5");
                exit;
                
            case 5:
                // IMAP/SMTP configuration (optional)
                $_SESSION['setup']['data']['imap'] = [
                    'host' => $_POST['imap_host'] ?? '',
                    'port' => $_POST['imap_port'] ?? '993',
                    'user' => $_POST['imap_user'] ?? '',
                    'pass' => $_POST['imap_pass'] ?? '',
                    'ssl' => isset($_POST['imap_ssl']),
                ];
                
                $_SESSION['setup']['data']['smtp'] = [
                    'host' => $_POST['smtp_host'] ?? '',
                    'port' => $_POST['smtp_port'] ?? '587',
                    'user' => $_POST['smtp_user'] ?? '',
                    'pass' => $_POST['smtp_pass'] ?? '',
                    'ssl' => isset($_POST['smtp_ssl']),
                    'from_email' => $_POST['smtp_from_email'] ?? '',
                    'from_name' => $_POST['smtp_from_name'] ?? 'CI-Inbox',
                ];
                
                $_SESSION['setup']['step'] = 6;
                $basePath = getBasePath();
                header("Location: {$basePath}/setup/?step=6");
                exit;
                
            case 6:
                // Complete setup
                completeSetup($_SESSION['setup']['data']);
                $_SESSION['setup']['step'] = 7;
                $basePath = getBasePath();
                header("Location: {$basePath}/setup/?step=7");
                exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

/**
 * Complete the setup process
 */
function completeSetup(array $data): void
{
    // 1. Write .env file
    $envContent = generateEnvFile($data);
    file_put_contents(__DIR__ . '/../../../.env', $envContent);
    
    // 2. Generate encryption key
    $encryptionKey = bin2hex(random_bytes(32));
    $envContent = str_replace('ENCRYPTION_KEY=', "ENCRYPTION_KEY={$encryptionKey}", $envContent);
    file_put_contents(__DIR__ . '/../../../.env', $envContent);
    
    // 3. Run database migrations
    require_once __DIR__ . '/../../../vendor/autoload.php';
    
    // Initialize database with new config
    $capsule = new \Illuminate\Database\Capsule\Manager;
    $capsule->addConnection([
        'driver' => 'mysql',
        'host' => $data['db']['host'],
        'database' => $data['db']['name'],
        'username' => $data['db']['user'],
        'password' => $data['db']['pass'],
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
    ]);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    
    // Run migrations
    $migrationsPath = __DIR__ . '/../../../database/migrations';
    $migrations = glob($migrationsPath . '/.php');
    sort($migrations);
    
    foreach ($migrations as $migration) {
        require_once $migration;
    }
    
    // 4. Create admin user
    \CiInbox\App\Models\User::create([
        'email' => $data['admin']['email'],
        'password_hash' => password_hash($data['admin']['password'], PASSWORD_BCRYPT),
        'name' => $data['admin']['name'],
        'role' => 'admin',
    ]);
    
    // 5. Create IMAP account if configured
    if (!empty($data['imap']['host'])) {
        \CiInbox\App\Models\ImapAccount::create([
            'email' => $data['imap']['user'],
            'server' => $data['imap']['host'],
            'port' => (int)$data['imap']['port'],
            'username' => $data['imap']['user'],
            'password' => $data['imap']['pass'], // Will be encrypted by model
            'ssl' => $data['imap']['ssl'],
            'is_active' => true,
        ]);
    }
    
    // 6. Write production .htaccess in root
    writeProductionHtaccess();
}

/**
 * Attempt to install Composer dependencies
 */
function installComposerDependencies(): array
{
    $rootDir = __DIR__ . '/../../../';
    $logFile = $rootDir . 'logs/composer-install.log';
    
    // Check if exec functions are available
    $disabledFunctions = explode(',', ini_get('disable_functions'));
    $disabledFunctions = array_map('trim', $disabledFunctions);
    
    if (in_array('exec', $disabledFunctions) || in_array('shell_exec', $disabledFunctions)) {
        return [
            'success' => false,
            'message' => 'PHP-Funktionen exec() und shell_exec() sind auf diesem Server deaktiviert. ' .
                        'Bitte laden Sie vendor.zip manuell herunter und entpacken Sie es per FTP.'
        ];
    }
    
    // Ensure logs directory exists
    if (!is_dir($rootDir . 'logs')) {
        @mkdir($rootDir . 'logs', 0755, true);
    }
    
    // Check if composer is available
    $composerCommand = null;
    
    // Try composer.phar in project root first
    if (file_exists($rootDir . 'composer.phar')) {
        $composerCommand = 'composer.phar';
    } else {
        // Try global composer
        $whichComposer = @shell_exec('which composer 2>/dev/null');
        if (!empty($whichComposer)) {
            $composerCommand = 'composer';
        } else {
            $whereComposer = @shell_exec('where composer 2>nul');
            if (!empty($whereComposer)) {
                $composerCommand = 'composer';
            } else {
                // Try to download composer.phar
                try {
                    $composerInstaller = @file_get_contents('https://getcomposer.org/installer');
                    if ($composerInstaller) {
                        file_put_contents($rootDir . 'composer-setup.php', $composerInstaller);
                        $escapedRootDir = escapeshellarg($rootDir);
                        @exec('php ' . escapeshellarg($rootDir . 'composer-setup.php') . ' --install-dir=' . $escapedRootDir . ' --filename=composer.phar 2>&1', $output, $returnVar);
                        @unlink($rootDir . 'composer-setup.php');
                        
                        if ($returnVar === 0 && file_exists($rootDir . 'composer.phar')) {
                            $composerCommand = 'composer.phar';
                        }
                    }
                } catch (Exception $e) {
                    return [
                        'success' => false,
                        'message' => 'Composer konnte nicht heruntergeladen werden: ' . $e->getMessage() . 
                                   ' Bitte vendor.zip manuell installieren.'
                    ];
                }
            }
        }
    }
    
    if (!$composerCommand) {
        return [
            'success' => false,
            'message' => 'Composer ist auf diesem Server nicht verfügbar. ' .
                        'Bitte laden Sie vendor.zip herunter und entpacken Sie es im Projekt-Root.'
        ];
    }
    
    // Run composer install with timeout handling (with proper escaping)
    $escapedRootDir = escapeshellarg($rootDir);
    $command = "cd {$escapedRootDir} && timeout 300 ";
    
    if ($composerCommand === 'composer.phar') {
        $command .= "php " . escapeshellarg($rootDir . 'composer.phar');
    } else {
        $command .= "composer";
    }
    
    $command .= " install --no-dev --optimize-autoloader --no-interaction 2>&1";
    
    // Try with timeout, fallback without
    $output = [];
    $returnVar = 0;
    @exec($command, $output, $returnVar);
    
    // If timeout command doesn't exist, try without
    if ($returnVar === 127 || empty($output)) {
        $command = "cd {$escapedRootDir} && ";
        
        if ($composerCommand === 'composer.phar') {
            $command .= "php " . escapeshellarg($rootDir . 'composer.phar');
        } else {
            $command .= "composer";
        }
        
        $command .= " install --no-dev --optimize-autoloader --no-interaction 2>&1";
        @exec($command, $output, $returnVar);
    }
    
    // Log output
    $logContent = "=== Composer Install Log ===\n";
    $logContent .= "Date: " . date('Y-m-d H:i:s') . "\n";
    $logContent .= "Command: {$command}\n";
    $logContent .= "Return Code: {$returnVar}\n";
    $logContent .= "Output:\n" . implode("\n", $output);
    file_put_contents($logFile, $logContent);
    
    if ($returnVar === 0 && is_dir($rootDir . 'vendor') && file_exists($rootDir . 'vendor/autoload.php')) {
        $packageCount = count(glob($rootDir . 'vendor/*', GLOB_ONLYDIR));
        return [
            'success' => true,
            'message' => "✅ Composer Dependencies erfolgreich installiert! ({$packageCount} Pakete)"
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Composer Installation fehlgeschlagen (Exit-Code: ' . $returnVar . '). ' .
                        'Details siehe logs/composer-install.log. Bitte vendor.zip manuell installieren.'
        ];
    }
}

/**
 * Write production .htaccess that redirects to src/public/
 */
function writeProductionHtaccess(): void
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

    file_put_contents(__DIR__ . '/../../../.htaccess', $htaccessContent);
}

/**
 * Generate .env file content
 */
function generateEnvFile(array $data): string
{
    $smtpEncryption = !empty($data['smtp']['ssl']) ? 'tls' : 'none';
    
    return <<<ENV
# CI-Inbox Environment Configuration
# Generated by Setup Wizard

# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost

# Database
DB_HOST={$data['db']['host']}
DB_DATABASE={$data['db']['name']}
DB_USERNAME={$data['db']['user']}
DB_PASSWORD={$data['db']['pass']}

# Security
ENCRYPTION_KEY=

# Logging
LOG_LEVEL=info
LOG_PATH=logs

# Session
SESSION_LIFETIME=120

# SMTP (if configured)
SMTP_HOST={$data['smtp']['host']}
SMTP_PORT={$data['smtp']['port']}
SMTP_USERNAME={$data['smtp']['user']}
SMTP_PASSWORD={$data['smtp']['pass']}
SMTP_ENCRYPTION={$smtpEncryption}
SMTP_FROM_EMAIL={$data['smtp']['from_email']}
SMTP_FROM_NAME={$data['smtp']['from_name']}
ENV;
}

/**
 * Check system requirements
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
            'current' => is_writable(__DIR__ . '/../../../') ? 'Ja' : 'Nein',
            'met' => is_writable(__DIR__ . '/../../../'),
        ],
        'writable_logs' => [
            'name' => 'logs/ Schreibbar',
            'required' => 'Ja',
            'current' => is_dir(__DIR__ . '/../../../logs') && is_writable(__DIR__ . '/../../../logs') ? 'Ja' : 'Nein',
            'met' => !is_dir(__DIR__ . '/../../../logs') || is_writable(__DIR__ . '/../../../logs'),
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
    $vendorExists = is_dir(__DIR__ . '/../../../vendor');
    
    // Check if exec functions are disabled
    $disabledFunctions = explode(',', ini_get('disable_functions'));
    $disabledFunctions = array_map('trim', $disabledFunctions);
    $execDisabled = in_array('exec', $disabledFunctions) || in_array('shell_exec', $disabledFunctions);
    
    $composerExists = false;
    if (!$execDisabled) {
        $composerExists = file_exists(__DIR__ . '/../../../composer.phar') || 
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
    $logsWritable = is_dir(__DIR__ . '/../../../logs') && is_writable(__DIR__ . '/../../../logs');
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
    $diskFree = @disk_free_space(__DIR__ . '/../../../');
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

$requirements = checkRequirements();
$allRequirementsMet = !in_array(false, array_column($requirements, 'met'));

$hostingChecks = checkHostingEnvironment();
$criticalIssues = array_filter($hostingChecks, fn($check) => $check['status'] === 'error' && $check['critical']);
$hostingReady = empty($criticalIssues);

// Setup steps
$steps = [
    1 => 'Hosting-Check',
    2 => 'Anforderungen',
    3 => 'Datenbank',
    4 => 'Admin-Account',
    5 => 'E-Mail (IMAP/SMTP)',
    6 => 'Abschluss',
    7 => 'Fertig'
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CI-Inbox Setup</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container { 
            max-width: 700px; 
            margin: 0 auto; 
            background: white;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }
        .header {
            background: #1f2937;
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 28px; margin-bottom: 8px; }
        .header p { opacity: 0.8; font-size: 14px; }
        .steps {
            display: flex;
            padding: 20px 30px;
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            overflow-x: auto;
        }
        .step {
            flex: 1;
            text-align: center;
            padding: 10px;
            font-size: 12px;
            color: #9ca3af;
            position: relative;
        }
        .step.active { color: #3b82f6; font-weight: 600; }
        .step.completed { color: #10b981; }
        .step-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #6b7280;
            font-weight: 600;
            margin-bottom: 6px;
        }
        .step.active .step-number { background: #3b82f6; color: white; }
        .step.completed .step-number { background: #10b981; color: white; }
        .content { padding: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { 
            display: block; 
            margin-bottom: 6px; 
            font-weight: 500;
            color: #374151;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .form-row { display: flex; gap: 20px; }
        .form-row .form-group { flex: 1; }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .checkbox-group input { width: auto; }
        .btn {
            padding: 12px 24px;
            font-size: 15px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; }
        .btn-secondary { background: #f3f4f6; color: #374151; }
        .btn-secondary:hover { background: #e5e7eb; }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        .actions { 
            display: flex; 
            justify-content: space-between; 
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .requirements-table {
            width: 100%;
            border-collapse: collapse;
        }
        .requirements-table th,
        .requirements-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .requirements-table th { font-weight: 600; color: #374151; }
        .status-ok { color: #10b981; font-weight: 600; }
        .status-warning { color: #f59e0b; font-weight: 600; }
        .status-error { color: #ef4444; font-weight: 600; }
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 20px;
        }
        .section-desc {
            color: #6b7280;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background: #10b981;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .success-icon svg {
            width: 50px;
            height: 50px;
        }
    </style>
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
            
            <?php if ($currentStep == 1): ?>
                <!-- Step 1: Hosting Environment Check -->
                <h2 class="section-title">🌐 Hosting-Umgebung prüfen</h2>
                <p class="section-desc">
                    Wir überprüfen, ob Ihre Hosting-Umgebung für CI-Inbox geeignet ist.
                    <?php if (!$hostingReady): ?>
                        <strong style="color: #ef4444;">⚠️ Kritische Probleme gefunden - Installation kann fehlschlagen!</strong>
                    <?php elseif (count(array_filter($hostingChecks, fn($c) => $c['status'] === 'warning')) > 0): ?>
                        <strong style="color: #f59e0b;">⚠️ Einige Warnungen - Installation möglich, aber Performance könnte eingeschränkt sein.</strong>
                    <?php else: ?>
                        <strong style="color: #10b981;">✓ Ihre Umgebung ist für CI-Inbox geeignet!</strong>
                    <?php endif; ?>
                </p>
                
                <table class="requirements-table">
                    <thead>
                        <tr>
                            <th>Prüfpunkt</th>
                            <th>Aktuell</th>
                            <th>Empfohlen</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hostingChecks as $check): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($check['name']) ?></strong></td>
                            <td><?= htmlspecialchars($check['value']) ?></td>
                            <td><?= htmlspecialchars($check['required']) ?></td>
                            <td class="<?= $check['status'] === 'ok' ? 'status-ok' : ($check['status'] === 'warning' ? 'status-warning' : 'status-error') ?>">
                                <?= $check['status'] === 'ok' ? '✓ OK' : ($check['status'] === 'warning' ? '⚠ Warnung' : '✗ Fehler') ?>
                            </td>
                        </tr>
                        <?php if ($check['recommendation']): ?>
                        <tr style="background: #fef3c7; border-left: 4px solid #f59e0b;">
                            <td colspan="4" style="padding: 12px; font-size: 13px;">
                                <strong>💡 Empfehlung:</strong> <?= htmlspecialchars($check['recommendation']) ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if (!$hostingReady): ?>
                <div class="alert alert-error" style="margin-top: 20px;">
                    <strong>⛔ Installation blockiert</strong><br>
                    Bitte beheben Sie die kritischen Fehler oben, bevor Sie fortfahren. 
                    Kontaktieren Sie ggf. Ihren Hosting-Anbieter für Hilfe bei der PHP-Konfiguration.
                </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="actions">
                        <div></div>
                        <button type="submit" class="btn btn-primary" <?= !$hostingReady ? 'disabled' : '' ?>>
                            Weiter zu System-Anforderungen →
                        </button>
                    </div>
                </form>
                
            <?php elseif ($currentStep == 2): ?>
                <!-- Step 2: Requirements -->
                <h2 class="section-title">Systemanforderungen</h2>
                <p class="section-desc">Überprüfung der erforderlichen PHP-Erweiterungen und Berechtigungen.</p>
                
                <table class="requirements-table">
                    <thead>
                        <tr>
                            <th>Anforderung</th>
                            <th>Benötigt</th>
                            <th>Aktuell</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requirements as $req): ?>
                        <tr>
                            <td><?= htmlspecialchars($req['name']) ?></td>
                            <td><?= htmlspecialchars($req['required']) ?></td>
                            <td><?= htmlspecialchars($req['current']) ?></td>
                            <td class="<?= $req['met'] ? 'status-ok' : 'status-error' ?>">
                                <?= $req['met'] ? '✓ OK' : '✗ Fehlt' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <form method="POST">
                    <div class="actions">
                        <a href="?step=1" class="btn btn-secondary">← Zurück</a>
                        <button type="submit" class="btn btn-primary" <?= !$allRequirementsMet ? 'disabled' : '' ?>>
                            Weiter →
                        </button>
                    </div>
                </form>
                
            <?php elseif ($currentStep == 3): ?>
                <!-- Step 2: Database -->
                <h2 class="section-title">Datenbank-Konfiguration</h2>
                <p class="section-desc">Geben Sie Ihre MySQL-Datenbankdaten ein.</p>
                
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Datenbank-Host</label>
                            <input type="text" name="db_host" value="localhost" required>
                        </div>
                        <div class="form-group">
                            <label>Datenbank-Name</label>
                            <input type="text" name="db_name" value="ci_inbox" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Benutzername</label>
                            <input type="text" name="db_user" value="root" required>
                        </div>
                        <div class="form-group">
                            <label>Passwort</label>
                            <input type="password" name="db_pass">
                        </div>
                    </div>
                    
                    <div class="actions">
                        <a href="?step=1" class="btn btn-secondary">← Zurück</a>
                        <button type="submit" class="btn btn-primary">Weiter →</button>
                    </div>
                </form>
                
            <?php elseif ($currentStep == 4): ?>
                <!-- Step 4: Admin Account -->
                <h2 class="section-title">Administrator-Account</h2>
                <p class="section-desc">Erstellen Sie den ersten Administrator-Account.</p>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="admin_name" placeholder="Max Mustermann" required>
                    </div>
                    <div class="form-group">
                        <label>E-Mail-Adresse</label>
                        <input type="email" name="admin_email" placeholder="admin@example.com" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Passwort</label>
                            <input type="password" name="admin_password" minlength="8" required>
                        </div>
                        <div class="form-group">
                            <label>Passwort bestätigen</label>
                            <input type="password" name="admin_password_confirm" minlength="8" required>
                        </div>
                    </div>
                    
                    <div class="actions">
                        <a href="?step=3" class="btn btn-secondary">← Zurück</a>
                        <button type="submit" class="btn btn-primary">Weiter →</button>
                    </div>
                </form>
                
            <?php elseif ($currentStep == 5): ?>
                <!-- Step 5: IMAP/SMTP -->
                <h2 class="section-title">E-Mail-Konfiguration</h2>
                <p class="section-desc">Konfigurieren Sie IMAP für den E-Mail-Empfang und SMTP für den Versand. Sie können diesen Schritt überspringen und später konfigurieren.</p>
                
                <form method="POST">
                    <h3 style="font-size: 16px; margin-bottom: 15px; color: #374151;">IMAP (Empfang)</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>IMAP-Server</label>
                            <input type="text" name="imap_host" placeholder="imap.example.com">
                        </div>
                        <div class="form-group">
                            <label>Port</label>
                            <input type="number" name="imap_port" value="993">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Benutzername/E-Mail</label>
                            <input type="text" name="imap_user" placeholder="user@example.com">
                        </div>
                        <div class="form-group">
                            <label>Passwort</label>
                            <input type="password" name="imap_pass">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-group">
                            <input type="checkbox" name="imap_ssl" checked> SSL/TLS verwenden
                        </label>
                    </div>
                    
                    <h3 style="font-size: 16px; margin: 25px 0 15px; color: #374151;">SMTP (Versand)</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>SMTP-Server</label>
                            <input type="text" name="smtp_host" placeholder="smtp.example.com">
                        </div>
                        <div class="form-group">
                            <label>Port</label>
                            <input type="number" name="smtp_port" value="587">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Benutzername</label>
                            <input type="text" name="smtp_user" placeholder="user@example.com">
                        </div>
                        <div class="form-group">
                            <label>Passwort</label>
                            <input type="password" name="smtp_pass">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Absender-E-Mail</label>
                            <input type="email" name="smtp_from_email" placeholder="noreply@example.com">
                        </div>
                        <div class="form-group">
                            <label>Absender-Name</label>
                            <input type="text" name="smtp_from_name" value="CI-Inbox">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-group">
                            <input type="checkbox" name="smtp_ssl" checked> SSL/TLS verwenden
                        </label>
                    </div>
                    
                    <div class="actions">
                        <a href="?step=4" class="btn btn-secondary">← Zurück</a>
                        <div>
                            <button type="submit" class="btn btn-primary">Weiter →</button>
                        </div>
                    </div>
                </form>
                
            <?php elseif ($currentStep == 6): ?>
                <!-- Step 6: Confirm -->
                <h2 class="section-title">Installation abschließen</h2>
                <p class="section-desc">Überprüfen Sie Ihre Einstellungen und klicken Sie auf "Installation starten".</p>
                
                <div style="background: #f9fafb; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                    <h4 style="margin-bottom: 10px;">Zusammenfassung:</h4>
                    <p><strong>Datenbank:</strong> <?= htmlspecialchars($_SESSION['setup']['data']['db']['name'] ?? '-') ?> @ <?= htmlspecialchars($_SESSION['setup']['data']['db']['host'] ?? '-') ?></p>
                    <p><strong>Admin:</strong> <?= htmlspecialchars($_SESSION['setup']['data']['admin']['email'] ?? '-') ?></p>
                    <p><strong>IMAP:</strong> <?= !empty($_SESSION['setup']['data']['imap']['host']) ? htmlspecialchars($_SESSION['setup']['data']['imap']['host']) : 'Nicht konfiguriert' ?></p>
                    <p><strong>SMTP:</strong> <?= !empty($_SESSION['setup']['data']['smtp']['host']) ? htmlspecialchars($_SESSION['setup']['data']['smtp']['host']) : 'Nicht konfiguriert' ?></p>
                </div>
                
                <form method="POST">
                    <div class="actions">
                        <a href="?step=5" class="btn btn-secondary">← Zurück</a>
                        <button type="submit" class="btn btn-success">🚀 Installation starten</button>
                    </div>
                </form>
                
            <?php elseif ($currentStep == 7): ?>
                <!-- Step 7: Complete -->
                <div style="text-align: center; padding: 40px 0;">
                    <div class="success-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h2 class="section-title" style="text-align: center;">Installation abgeschlossen!</h2>
                    <p class="section-desc" style="text-align: center;">
                        CI-Inbox wurde erfolgreich installiert. Sie können sich jetzt mit Ihrem Administrator-Account anmelden.
                    </p>
                    <a href="/login.php" class="btn btn-success" style="margin-top: 20px;">
                        Zur Anmeldung →
                    </a>
                </div>
                <?php 
                // Clear setup session
                unset($_SESSION['setup']);
                ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

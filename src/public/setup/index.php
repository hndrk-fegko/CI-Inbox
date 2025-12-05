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

require_once __DIR__ . '/../../../vendor/autoload.php';

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
                header('Location: /login.php');
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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch ($currentStep) {
            case 1:
                // Requirements check - just proceed
                $_SESSION['setup']['step'] = 2;
                header('Location: /setup/?step=2');
                exit;
                
            case 2:
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
                
                $_SESSION['setup']['step'] = 3;
                header('Location: /setup/?step=3');
                exit;
                
            case 3:
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
                
                $_SESSION['setup']['step'] = 4;
                header('Location: /setup/?step=4');
                exit;
                
            case 4:
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
                    'from_name' => $_POST['smtp_from_name'] ?? 'C-IMAP',
                ];
                
                $_SESSION['setup']['step'] = 5;
                header('Location: /setup/?step=5');
                exit;
                
            case 5:
                // Complete setup
                completeSetup($_SESSION['setup']['data']);
                $_SESSION['setup']['step'] = 6;
                header('Location: /setup/?step=6');
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
    $migrations = glob($migrationsPath . '/*.php');
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
        'is_active' => true,
    ]);
    
    // 5. Store IMAP/SMTP config in system_settings
    if (!empty($data['imap']['host'])) {
        // Create IMAP account
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
}

/**
 * Generate .env file content
 */
function generateEnvFile(array $data): string
{
    return <<<ENV
# C-IMAP Environment Configuration
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
SMTP_ENCRYPTION={$data['smtp']['ssl'] ? 'tls' : 'none'}
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

$requirements = checkRequirements();
$allRequirementsMet = !in_array(false, array_column($requirements, 'met'));

// Setup steps
$steps = [
    1 => 'Anforderungen',
    2 => 'Datenbank',
    3 => 'Admin-Account',
    4 => 'E-Mail (IMAP/SMTP)',
    5 => 'Abschluss',
    6 => 'Fertig'
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>C-IMAP Setup</title>
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
            display: inline-flex;
            align-items: center;
            justify-content: center;
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
        .status-ok { color: #10b981; }
        .status-error { color: #ef4444; }
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
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .success-icon svg { width: 40px; height: 40px; color: white; }
        .skip-link {
            color: #6b7280;
            text-decoration: none;
            font-size: 14px;
        }
        .skip-link:hover { color: #374151; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 C-IMAP Setup</h1>
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
            
            <?php if ($currentStep == 1): ?>
                <!-- Step 1: Requirements -->
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
                        <div></div>
                        <button type="submit" class="btn btn-primary" <?= !$allRequirementsMet ? 'disabled' : '' ?>>
                            Weiter →
                        </button>
                    </div>
                </form>
                
            <?php elseif ($currentStep == 2): ?>
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
                
            <?php elseif ($currentStep == 3): ?>
                <!-- Step 3: Admin Account -->
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
                        <a href="?step=2" class="btn btn-secondary">← Zurück</a>
                        <button type="submit" class="btn btn-primary">Weiter →</button>
                    </div>
                </form>
                
            <?php elseif ($currentStep == 4): ?>
                <!-- Step 4: IMAP/SMTP -->
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
                            <input type="text" name="smtp_from_name" value="C-IMAP">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-group">
                            <input type="checkbox" name="smtp_ssl" checked> SSL/TLS verwenden
                        </label>
                    </div>
                    
                    <div class="actions">
                        <a href="?step=3" class="btn btn-secondary">← Zurück</a>
                        <div>
                            <button type="submit" class="btn btn-primary">Weiter →</button>
                        </div>
                    </div>
                </form>
                
            <?php elseif ($currentStep == 5): ?>
                <!-- Step 5: Confirm -->
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
                        <a href="?step=4" class="btn btn-secondary">← Zurück</a>
                        <button type="submit" class="btn btn-success">🚀 Installation starten</button>
                    </div>
                </form>
                
            <?php elseif ($currentStep == 6): ?>
                <!-- Step 6: Complete -->
                <div style="text-align: center; padding: 40px 0;">
                    <div class="success-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h2 class="section-title" style="text-align: center;">Installation abgeschlossen!</h2>
                    <p class="section-desc" style="text-align: center;">
                        C-IMAP wurde erfolgreich installiert. Sie können sich jetzt mit Ihrem Administrator-Account anmelden.
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

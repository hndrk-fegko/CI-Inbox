<?php
/**
 * Setup Wizard - Step 6: Review & Install
 * 
 * Final review before installation
 */

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Handle Step 6 form submission (starts installation)
 * 
 * @param array $sessionData Normalized session data from index.php
 */
function handleStep6Submit(array $sessionData): void
{
    $projectRoot = getProjectRoot();
    
    try {
        // STEP 1: Generate encryption key FIRST (before any DB operations)
        $encryptionKey = bin2hex(random_bytes(32)); // 64 hex chars = 32 bytes
        $sessionData['encryption_key'] = $encryptionKey;
        
        // STEP 2: Database connection
        $dsn = "mysql:host={$sessionData['db_host']};port={$sessionData['db_port']};charset=utf8mb4";
        $pdo = new PDO($dsn, $sessionData['db_user'], $sessionData['db_password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // K2: Skip CREATE DATABASE if checkbox checked
        if (empty($sessionData['db_exists'])) {
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$sessionData['db_name']}`");
        }
        
        // Use database
        $pdo->exec("USE `{$sessionData['db_name']}`");

        // Initialize Capsule
        $capsule = new Capsule;
        $capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => $sessionData['db_host'],
            'database'  => $sessionData['db_name'],
            'username'  => $sessionData['db_user'],
            'password'  => $sessionData['db_password'],
            'port'      => $sessionData['db_port'],
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
        
        // Run migrations
        $migrationsPath = $projectRoot . '/database/migrations';
        $migrations = glob($migrationsPath . '/*.php');
        sort($migrations);

        // Disable foreign key checks for the duration of migrations
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0;");
        $capsule->getConnection()->getPdo()->exec("SET FOREIGN_KEY_CHECKS=0;");
        
        try {
            foreach ($migrations as $migration) {
                $migrationInstance = require $migration;
                if (is_object($migrationInstance) && method_exists($migrationInstance, 'up')) {
                    $migrationInstance->up();
                } elseif (is_callable($migrationInstance)) {
                    // For migrations that return a closure
                    $migrationInstance();
                }
            }
        } finally {
            // Re-enable foreign key checks after migrations
            $pdo->exec("SET FOREIGN_KEY_CHECKS=1;");
            $capsule->getConnection()->getPdo()->exec("SET FOREIGN_KEY_CHECKS=1;");
        }
        
        // Create admin user
        $passwordHash = password_hash($sessionData['admin_password'], PASSWORD_BCRYPT);
        $avatarColor = '#' . substr(md5($sessionData['admin_email']), 0, 6);
        
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, avatar_color, created_at, updated_at) 
                              VALUES (?, ?, ?, 'admin', ?, NOW(), NOW())");
        $stmt->execute([
            $sessionData['admin_name'],
            $sessionData['admin_email'],
            $passwordHash,
            $avatarColor
        ]);
        $userId = (int)$pdo->lastInsertId();
        
        // K3: Store admin IMAP if enabled
        if (!empty($sessionData['enable_admin_imap'])) {
            $adminImapPassword = $sessionData['admin_imap_password_encrypted'] ?? null;
            
            // Validate password is not null/empty before encryption
            if (empty($adminImapPassword)) {
                throw new Exception('Admin IMAP password is required but was not provided.');
            }
            
            $encryptedAdminImapPassword = $encryptionService->encrypt($adminImapPassword);
            
            $stmt = $pdo->prepare("
                INSERT INTO imap_accounts (
                    user_id, email, imap_host, imap_port, imap_username,
                    imap_password_encrypted, imap_encryption, is_default, is_active, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1, NOW(), NOW())
            ");
            $stmt->execute([
                $userId,
                $sessionData['admin_email'],
                $sessionData['admin_imap_host'],
                $sessionData['admin_imap_port'],
                $sessionData['admin_imap_username'] ?? $sessionData['admin_email'],
                $encryptedAdminImapPassword,
                $sessionData['admin_imap_encryption'] ?? 'ssl',
            ]);
        }
        
        // Create default IMAP account (system-wide)
        $imapPassword = $sessionData['imap_password_encrypted'] ?? null;

        // Validate password is not null/empty before encryption
        if (empty($imapPassword)) {
            throw new Exception('IMAP password is required but was not provided.');
        }

        $encryptedImapPassword = $encryptionService->encrypt($imapPassword);

        $stmt = $pdo->prepare("
            INSERT INTO imap_accounts (
                user_id, email, imap_host, imap_port, imap_username,
                imap_password_encrypted, imap_encryption, is_default, is_active, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1, NOW(), NOW())
        ");
        $stmt->execute([
            $userId,
            $sessionData['imap_email'],
            $sessionData['imap_host'],
            $sessionData['imap_port'],
            $sessionData['imap_username'] ?? $sessionData['imap_email'],
            $encryptedImapPassword,
            $sessionData['imap_encryption'] ?? 'ssl',
        ]);
        
        // Create system labels
        $labels = [
            ['name' => 'Important', 'color' => '#ef4444', 'is_system' => 1],
            ['name' => 'Follow-up', 'color' => '#f59e0b', 'is_system' => 1],
            ['name' => 'In Progress', 'color' => '#3b82f6', 'is_system' => 1],
            ['name' => 'Done', 'color' => '#10b981', 'is_system' => 1]
        ];
        
        foreach ($labels as $label) {
            $stmt = $pdo->prepare("INSERT INTO labels (name, color, is_system, created_at, updated_at) 
                                  VALUES (?, ?, ?, NOW(), NOW())");
            $stmt->execute([$label['name'], $label['color'], $label['is_system']]);
        }
        
        // Seed system settings
        $stmt = $pdo->prepare("INSERT INTO system_settings (`key`, value, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
        $stmt->execute(['app_name', 'CI-Inbox']);
        $stmt->execute(['threads_per_page', '25']);
        $stmt->execute(['email_retention_days', '0']);
        
        // Write production .htaccess
        if (!writeProductionHtaccess($projectRoot)) {
            throw new Exception('Fehler beim Erstellen der .htaccess-Datei');
        }
        
        // STEP 5: Generate and write .env file as LAST step (atomic installation marker)
        // This prevents race conditions where .env exists but DB is incomplete
        $envContent = generateEnvFile($sessionData);
        
        // Replace empty ENCRYPTION_KEY with generated key
        $envContent = str_replace('ENCRYPTION_KEY=', "ENCRYPTION_KEY={$encryptionKey}", $envContent);
        
        // Write .env atomically
        $envPath = $projectRoot . '/.env';
        $tempFile = $envPath . '.tmp';
        
        $written = file_put_contents($tempFile, $envContent, LOCK_EX);
        if ($written === false) {
            throw new Exception('Fehler beim Schreiben der .env-Datei (Schreibrechte prüfen)');
        }
        
        // Atomic rename
        if (!rename($tempFile, $envPath)) {
            @unlink($tempFile);
            throw new Exception('Fehler beim Finalisieren der .env-Datei');
        }
        
        // Set secure permissions
        @chmod($envPath, 0600);
        
        updateSessionStep(7);
        redirectToStep(7);
        
    } catch (Exception $e) {
        // Rollback: Delete .env if it was written (allows setup restart)
        $envPath = $projectRoot . '/.env';
        if (file_exists($envPath)) {
            @unlink($envPath);
        }
        
        throw new Exception('Installation fehlgeschlagen: ' . $e->getMessage());
    }
}

/**
 * Render Step 6 form (review configuration)
 * 
 * @param array $sessionData All session data
 */
function renderStep6Form(array $sessionData): void
{
    ?>
    <h2 class="section-title">🔍 Zusammenfassung & Installation</h2>
    <p class="section-desc">Bitte prüfen Sie Ihre Eingaben. Klicken Sie auf "Installieren", um CI-Inbox einzurichten.</p>
    
    <div class="review-section">
        <h3>🗄️ Datenbank</h3>
        <table class="review-table">
            <tr><th>Host:</th><td><?= htmlspecialchars($sessionData['db_host'] ?? '') ?></td></tr>
            <tr><th>Port:</th><td><?= htmlspecialchars((string)($sessionData['db_port'] ?? '')) ?></td></tr>
            <tr><th>Datenbankname:</th><td><?= htmlspecialchars($sessionData['db_name'] ?? '') ?></td></tr>
            <tr><th>Benutzer:</th><td><?= htmlspecialchars($sessionData['db_user'] ?? '') ?></td></tr>
        </table>
    </div>
    
    <div class="review-section">
        <h3>👤 Administrator</h3>
        <table class="review-table">
            <tr><th>Name:</th><td><?= htmlspecialchars($sessionData['admin_name'] ?? '') ?></td></tr>
            <tr><th>E-Mail:</th><td><?= htmlspecialchars($sessionData['admin_email'] ?? '') ?></td></tr>
            <?php if (!empty($sessionData['enable_admin_imap'])): ?>
            <tr><th>Persönlicher IMAP:</th><td><?= htmlspecialchars($sessionData['admin_imap_host'] ?? '') ?>:<?= htmlspecialchars((string)($sessionData['admin_imap_port'] ?? '')) ?></td></tr>
            <?php endif; ?>
        </table>
    </div>
    
    <div class="review-section">
        <h3>📧 Gemeinsame Inbox</h3>
        <table class="review-table">
            <tr><th>IMAP Host:</th><td><?= htmlspecialchars($sessionData['imap_host'] ?? '') ?></td></tr>
            <tr><th>IMAP Port:</th><td><?= htmlspecialchars((string)($sessionData['imap_port'] ?? '')) ?></td></tr>
            <tr><th>IMAP Verschlüsselung:</th><td><?= strtoupper(htmlspecialchars($sessionData['imap_encryption'] ?? '')) ?></td></tr>
            <tr><th>SMTP Host:</th><td><?= htmlspecialchars($sessionData['smtp_host'] ?? '') ?></td></tr>
            <tr><th>SMTP Port:</th><td><?= htmlspecialchars((string)($sessionData['smtp_port'] ?? '')) ?></td></tr>
            <tr><th>SMTP Verschlüsselung:</th><td><?= strtoupper(htmlspecialchars($sessionData['smtp_encryption'] ?? '')) ?></td></tr>
        </table>
    </div>
    
    <div class="alert alert-warning" style="margin-top: 20px;">
        <strong>⚠️ Wichtig:</strong> Nach der Installation wird der Setup-Wizard automatisch deaktiviert. 
        Sie können sich dann mit Ihren Administrator-Zugangsdaten anmelden.
    </div>
    
    <form method="POST">
        <div class="actions">
            <a href="?step=5" class="btn btn-secondary">← Zurück</a>
            <button type="submit" class="btn btn-primary">🚀 Jetzt installieren</button>
        </div>
    </form>
    <?php
}

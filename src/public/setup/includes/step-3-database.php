<?php
/**
 * Setup Wizard - Step 3: Database Configuration
 * 
 * Handles database setup and validation
 */

declare(strict_types=1);

/**
 * Handle Step 3 form submission
 * 
 * @param array $post POST data
 * @return void Redirects on success, throws Exception on error
 * @throws Exception On validation or connection errors
 */
function handleStep3Submit(array $post): void
{
    $dbHost = $post['db_host'] ?? 'localhost';
    $dbName = $post['db_name'] ?? 'ci_inbox';
    $dbUser = $post['db_user'] ?? 'root';
    $dbPass = $post['db_pass'] ?? '';
    $dbExists = isset($post['db_exists']);  // K2: Checkbox for shared hosting
    
    // Validate database name (only allow safe characters)
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
        throw new Exception('Ungültiger Datenbankname. Nur Buchstaben, Zahlen und Unterstriche erlaubt.');
    }
    
    // Validate host (basic sanitization)
    $dbHost = preg_replace('/[^a-zA-Z0-9\.\-:]/', '', $dbHost);
    
    // K1: Try-Catch around database connection
    try {
        // Test connection
        $pdo = new PDO(
            "mysql:host={$dbHost};charset=utf8mb4",
            $dbUser,
            $dbPass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // K2: Create database only if checkbox NOT set (Shared Hosting Support)
        if (!$dbExists) {
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
        
        // Store configuration in session (nested structure)
        updateSessionData('db', [
            'host' => $dbHost,
            'name' => $dbName,
            'user' => $dbUser,
            'pass' => $dbPass,
            'port' => 3306,
            'exists' => $dbExists
        ]);
        
        updateSessionStep(4);
        redirectToStep(4);
        
    } catch (PDOException $e) {
        throw new Exception('Datenbankverbindung fehlgeschlagen: ' . $e->getMessage());
    }
}

/**
 * Render Step 3 form
 */
function renderStep3Form(): void
{
    ?>
    <h2 class="section-title">Datenbank-Konfiguration</h2>
    <p class="section-desc">Geben Sie Ihre MySQL-Datenbankdaten ein.</p>
    
    <!-- MySQL Port Info -->
    <div class="alert alert-info" style="margin-bottom: 20px; background: #eff6ff; border-color: #3b82f6; color: #1e40af;">
        <strong>💡 Port-Konfiguration:</strong> 
        Für nicht-standard MySQL-Ports verwenden Sie die Syntax <code>hostname:port</code> im Host-Feld 
        (z.B. <code>localhost:3307</code> oder <code>mysql.example.com:3308</code>). 
        Der Standard-Port 3306 wird automatisch verwendet, wenn kein Port angegeben wird.
    </div>
    
    <form method="POST">
        <div class="form-row">
            <div class="form-group">
                <label>Datenbank-Host</label>
                <input type="text" name="db_host" value="localhost" required>
                <small style="color: #6b7280;">Beispiele: <code>localhost</code>, <code>localhost:3307</code>, <code>mysql.example.com:3308</code></small>
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
        
        <!-- K2: Checkbox für Shared Hosting -->
        <div class="form-group" style="margin: 20px 0;">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="checkbox" name="db_exists" id="db_exists" style="width: auto; margin: 0;">
                <span>Datenbank existiert bereits (Skip CREATE DATABASE)</span>
            </label>
            <small style="color: #6b7280; margin-left: 28px; display: block; margin-top: 4px;">
                💡 Für Shared Hosting ohne CREATE DATABASE Rechte
            </small>
        </div>
        
        <div class="actions">
            <a href="?step=2" class="btn btn-secondary">← Zurück</a>
            <button type="submit" class="btn btn-primary">Weiter →</button>
        </div>
    </form>
    <?php
}

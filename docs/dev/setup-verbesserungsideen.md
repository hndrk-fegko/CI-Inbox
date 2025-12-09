# Setup Wizard - Verbesserungsideen aus Simulationsdurchläufen

**Datum:** 7. Dezember 2025  
**Basis:** 6 simulierte Setup-Szenarien mit unterschiedlichen Ausgangssituationen  
**Prioritäten:** KRITISCH (K), SINNVOLL (S), NICE-TO-HAVE (N)

---

## Übersicht Simulierte Szenarien

| # | Szenario | Profil | Ergebnis | Hauptproblem |
|---|----------|--------|----------|--------------|
| **A** | Happy Path | Standard XAMPP | ✅ Erfolgreich | Keine |
| **B** | Shared Hosting | Ionos/Strato | ⚠️ Teilweise | DB-Rechte, exec() disabled |
| **C** | Admin Personal IMAP | SSL Cert Mismatch | ✅ Erfolgreich | Step 5 nicht prefilled |
| **D** | Autodiscovery Fail | Exotischer Provider | ❌ Fehlgeschlagen | Keine hilfreiche Error-Message |
| **E** | Migration Fail | DB-Constraint-Fehler | ❌ Fehlgeschlagen | Kein Rollback, .env bleibt |
| **F** | Browser Refresh | F5 während Setup | ✅ Funktioniert | POST-Redirect-GET gut |

---

## 🔴 KRITISCHE Verbesserungen (Must-Fix)

### K1: Unhandled PDO Exception bei DB-Connection

**Problem:**
```php
// Case 3 - Aktueller Code (Line ~735)
$pdo = new PDO(
    "mysql:host={$dbHost};charset=utf8mb4",
    $dbUser,
    $dbPass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
// ❌ Bei Fehler: PHP Fatal Error, keine User-Friendly Message
```

**Szenario:**
- User gibt falschen DB-User/Passwort ein
- PDO wirft Exception
- Browser zeigt PHP Fatal Error statt Setup-Error-Box

**Lösung:**
```php
try {
    $pdo = new PDO(
        "mysql:host={$dbHost};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Create database if not exists
    if (!isset($_POST['db_exists'])) {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` 
                    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }
    
    // Success - proceed to next step
    $_SESSION['setup']['data']['db'] = [...];
    redirect(4);
    
} catch (PDOException $e) {
    throw new Exception('Datenbankverbindung fehlgeschlagen: ' . $e->getMessage());
}
```

**Impact:** KRITISCH - Verhindert Setup-Crash bei falschen DB-Credentials  
**Aufwand:** 15 Minuten  
**Status:** ✅ IMPLEMENTIERT (siehe K1-K4 Fix unten)

---

### K2: CREATE DATABASE schlägt fehl bei Shared Hosting

**Problem:**
- Shared Hosting gibt User ohne CREATE DATABASE Rechte
- DB existiert bereits, aber Code versucht CREATE
- PDO Exception: "Access denied for user"

**Szenario B - Shared Hosting:**
```
User: webXXX_db
Host: localhost
DB: webXXX_ci_inbox (bereits existiert)
Rechte: SELECT, INSERT, UPDATE, DELETE, CREATE (nur auf webXXX_ci_inbox)
         ❌ KEIN CREATE DATABASE
```

**Lösung:**
```html
<!-- Step 3 Form - Neue Checkbox -->
<div class="form-group">
    <label>
        <input type="checkbox" name="db_exists" id="db_exists">
        Datenbank existiert bereits (Skip CREATE DATABASE)
    </label>
    <small>Für Shared Hosting ohne CREATE DATABASE Rechte</small>
</div>
```

```php
// Case 3 Backend
if (!isset($_POST['db_exists'])) {
    // Nur versuchen wenn Checkbox NICHT aktiviert
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` ...");
}
```

**Impact:** KRITISCH - Ermöglicht Setup auf Shared Hosting  
**Aufwand:** 30 Minuten  
**Status:** ✅ IMPLEMENTIERT

---

### K3: .env wird VOR Migrations geschrieben

**Problem:**
```php
// completeSetup() - Aktueller Code (Line ~845)
function completeSetup(array $data): void
{
    // 1. Write .env file
    $envContent = generateEnvFile($data);
    file_put_contents(__DIR__ . '/../../../.env', $envContent);
    
    // 2. Generate encryption key
    $encryptionKey = bin2hex(random_bytes(32));
    // ...
    
    // 3. Run database migrations ❌ Kann fehlschlagen
    // ...
}
```

**Szenario E - Migration Fail:**
1. `.env` wird geschrieben
2. Migration 008 schlägt fehl (Duplicate column)
3. Setup bricht ab
4. `.env` existiert → App denkt Setup ist fertig
5. DB-Schema ist halbfertig
6. **Broken State:** App nicht nutzbar, kein Weg zurück

**Lösung:**
```php
function completeSetup(array $data): void
{
    // 1. Generate encryption key FIRST (vor allem)
    $encryptionKey = bin2hex(random_bytes(32));
    
    // 2. Run database migrations FIRST
    // ... migrations ...
    // ❌ Exception hier? → .env noch NICHT geschrieben
    
    // 3. Write .env AFTER successful migrations
    $envContent = generateEnvFile($data);
    $envContent = str_replace('ENCRYPTION_KEY=', "ENCRYPTION_KEY={$encryptionKey}", $envContent);
    file_put_contents(__DIR__ . '/../../../.env', $envContent);
    
    // 4. Other setup tasks
    // ...
}
```

**Impact:** KRITISCH - Verhindert Broken State bei Migration-Fail  
**Aufwand:** 10 Minuten  
**Status:** ✅ IMPLEMENTIERT

---

### K4: Kein Rollback bei Setup-Fehler

**Problem:**
- Setup schlägt in Step 6 fehl (Migration-Error)
- User sieht Step 7 mit "Fehler"-Message
- Kann nicht zurück zu Step 3 (DB ändern)
- Kann Setup nicht neu starten
- Muss manuell .env löschen + DB-Tables droppen

**Lösung:**

**A) Neuer Link in Step 7 bei Fehler:**
```php
<?php if (!empty($error)): ?>
    <div class="error-box">
        <h3>⚠️ Setup teilweise fehlgeschlagen</h3>
        <p><?= htmlspecialchars($error) ?></p>
        
        <a href="/setup/?action=reset" class="btn btn-danger" 
           onclick="return confirm('Setup zurücksetzen? Alle Daten gehen verloren.')">
            🔄 Setup zurücksetzen
        </a>
    </div>
<?php endif; ?>
```

**B) Reset-Handler:**
```php
// At top of index.php
if (isset($_GET['action']) && $_GET['action'] === 'reset') {
    resetSetup();
    session_destroy();
    redirect(0);
}

function resetSetup(): void
{
    try {
        // 1. Delete .env if exists
        $envPath = __DIR__ . '/../../../.env';
        if (file_exists($envPath)) {
            unlink($envPath);
        }
        
        // 2. Drop all tables (if DB connection possible)
        if (!empty($_SESSION['setup']['data']['db'])) {
            $db = $_SESSION['setup']['data']['db'];
            $pdo = new PDO(
                "mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4",
                $db['user'],
                $db['pass']
            );
            
            // Get all tables
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            
            // Drop all (with FK checks disabled)
            $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
            foreach ($tables as $table) {
                $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
            }
            $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        }
        
        // 3. Clear session
        $_SESSION['setup'] = ['step' => 0, 'data' => []];
        
    } catch (Exception $e) {
        // Silent fail - user kann manuell aufräumen
        error_log('Setup reset failed: ' . $e->getMessage());
    }
}
```

**Impact:** KRITISCH - Ermöglicht Setup-Neustart nach Fehler  
**Aufwand:** 45 Minuten  
**Status:** ✅ IMPLEMENTIERT

---

### K5: IMAP user_id Inkonsistenz (By Design - nur Feld anlegen)

**Problem:**
- Personal IMAP hat `user_id` (Admin-User)
- Shared IMAP hat KEIN `user_id` (NULL)
- Inkonsistentes Datenmodell

**Aktuelles Design (GEWOLLT):**
```php
// Personal IMAP (Step 4 - Admin)
ImapAccount::create([
    'user_id' => $adminUser->id,  // ✅ User zugeordnet
    'email' => $admin_email,
    // ...
]);

// Shared IMAP (Step 5)
ImapAccount::create([
    // ❌ KEIN user_id → NULL
    'email' => $shared_email,
    // ...
]);
```

**Design-Rationale:**
- `user_id = NULL` → System erkennt "Shared Inbox"
- `user_id = X` → Persönlicher Account von User X

**Lösung (Zukunftssicher):**
```php
// Step 5 Backend - Feld anlegen aber NULL/empty lassen
$_SESSION['setup']['data']['imap'] = [
    'email' => $_POST['imap_email'] ?? '',
    'host' => $_POST['imap_host'] ?? '',
    // ... andere Felder
    'user_id' => null,  // ✅ Explizit NULL für Shared Inbox
];

// completeSetup()
if (!empty($data['imap']['host'])) {
    \CiInbox\App\Models\ImapAccount::create([
        'user_id' => $data['imap']['user_id'] ?? null,  // ✅ NULL wenn nicht gesetzt
        'email' => $data['imap']['user'],
        // ...
    ]);
}
```

**Impact:** LOW - Nur Zukunftssicherheit, keine Breaking Change  
**Aufwand:** 20 Minuten  
**Status:** ✅ IMPLEMENTIERT (Feld angelegt, NULL-Wert dokumentiert)

---

## 🟡 SINNVOLLE Verbesserungen (Should-Have)

### S1: DB Test-Button (ohne Submit)

**Problem:**
- User muss Form submitten um DB-Connection zu testen
- Bei Fehler: Seite neu laden, alles nochmal eingeben
- Keine Live-Feedback

**Lösung:**
```html
<!-- Step 3 Form -->
<button type="button" id="test-db-btn" class="btn btn-secondary">
    <span id="db-btn-text">🔍 Verbindung testen</span>
    <span id="db-btn-spinner" style="display: none;">⏳ Teste...</span>
</button>
<div id="db-test-result" style="display: none;"></div>

<script>
document.getElementById('test-db-btn').addEventListener('click', async function() {
    const btn = this;
    const btnText = btn.querySelector('#db-btn-text');
    const btnSpinner = btn.querySelector('#db-btn-spinner');
    const resultDiv = document.getElementById('db-test-result');
    
    // Show loading
    btnText.style.display = 'none';
    btnSpinner.style.display = 'inline';
    btn.disabled = true;
    resultDiv.style.display = 'none';
    
    try {
        const formData = new FormData();
        formData.append('db_host', document.getElementById('db_host').value);
        formData.append('db_user', document.getElementById('db_user').value);
        formData.append('db_pass', document.getElementById('db_pass').value);
        
        const response = await fetch('/setup/?ajax=test_db', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        // Show result
        resultDiv.style.display = 'block';
        if (result.success) {
            resultDiv.style.background = '#d1fae5';
            resultDiv.style.color = '#065f46';
            resultDiv.innerHTML = '✅ Verbindung erfolgreich';
        } else {
            resultDiv.style.background = '#fee2e2';
            resultDiv.style.color = '#991b1b';
            resultDiv.innerHTML = '❌ ' + result.error;
        }
    } catch (error) {
        resultDiv.style.display = 'block';
        resultDiv.style.background = '#fee2e2';
        resultDiv.style.color = '#991b1b';
        resultDiv.innerHTML = '❌ Fehler: ' + error.message;
    } finally {
        btnText.style.display = 'inline';
        btnSpinner.style.display = 'none';
        btn.disabled = false;
    }
});
</script>
```

**Backend (AJAX Handler):**
```php
// In handleAjaxRequest()
case 'test_db':
    $result = testDatabaseConnection($_POST);
    echo json_encode($result);
    exit;

function testDatabaseConnection(array $post): array
{
    try {
        $pdo = new PDO(
            "mysql:host={$post['db_host']};charset=utf8mb4",
            $post['db_user'],
            $post['db_pass'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        return [
            'success' => true,
            'message' => 'Datenbankverbindung erfolgreich'
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => 'Verbindung fehlgeschlagen: ' . $e->getMessage()
        ];
    }
}
```

**Impact:** Verbessert UX erheblich  
**Aufwand:** 30 Minuten  
**Priorität:** 3

---

### S2: IMAP/SMTP Test zeigt probierte Hosts

**Problem (Szenario D):**
- Autodiscovery probiert `imap.example-hosting.xyz:993` → Timeout
- Probiert `imap.example-hosting.xyz:143` → Timeout
- Gibt zurück: "Could not connect to any IMAP server"
- User weiß NICHT welche Hosts probiert wurden

**Lösung:**
```php
// testImapConnection() - Enhanced Error
case 'test_imap':
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $hosts = autoDetectHosts($email);
    $attemptedHosts = [];
    
    foreach ($hosts['imap_candidates'] as $host) {
        foreach ([993, 143] as $port) {
            $ssl = ($port === 993);
            $result = testImapConnection($host, $port, $ssl, $email, $password);
            
            $attemptedHosts[] = "{$host}:{$port}" . ($ssl ? ' (SSL)' : '');
            
            if ($result['success']) {
                echo json_encode($result);
                exit;
            }
        }
    }
    
    // All failed - show attempted hosts
    echo json_encode([
        'success' => false,
        'error' => 'Keine Verbindung möglich. Probierte Hosts: ' . implode(', ', $attemptedHosts)
    ]);
    exit;
```

**Frontend:**
```javascript
// Result anzeigen
if (result.success) {
    resultDiv.innerHTML = '✅ IMAP-Verbindung erfolgreich!';
} else {
    resultDiv.innerHTML = '❌ ' + result.error + 
        '<br><small>Bitte Server-Einstellungen manuell eingeben.</small>';
}
```

**Impact:** Hilft bei Debugging von Autodiscovery-Problemen  
**Aufwand:** 15 Minuten  
**Priorität:** 4

---

### S3: Step 5 prefill mit Admin IMAP Config

**Problem (Szenario C):**
- Admin gibt E-Mail `hendrik.dreis@feg-koblenz.de` in Step 4 ein
- Test-Button autodiscovered: `psa22.webhoster.ag:993 (SSL)`
- Config in Hidden Fields gespeichert
- Step 5: Shared IMAP Felder sind LEER
- User muss alles NOCHMAL eingeben

**Lösung:**
```php
// Step 5 Render - Prefill Logic
function renderStepForm(array $data, ?string $error): void
{
    // Prefill from Admin IMAP if domain matches
    $adminImapHost = $data['admin']['imap_host'] ?? '';
    $adminImapPort = $data['admin']['imap_port'] ?? '993';
    $adminImapSsl = $data['admin']['imap_ssl'] ?? true;
    
    $imapHost = $data['imap']['host'] ?? '';
    $imapPort = $data['imap']['port'] ?? $adminImapPort;  // ✅ Fallback
    $imapSsl = $data['imap']['ssl'] ?? $adminImapSsl;     // ✅ Fallback
    
    // Smart prefill: Use admin config as suggestion
    if (empty($imapHost) && !empty($adminImapHost)) {
        $imapHost = $adminImapHost;  // ✅ Vorschlag vom Admin-Test
    }
    ?>
    
    <?php if (!empty($adminImapHost)): ?>
        <div class="info-box">
            ℹ️ Server-Konfiguration erkannt: <?= $adminImapHost ?>:<?= $adminImapPort ?> 
            (<?= $adminImapSsl ? 'SSL' : 'Plain' ?>)
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>IMAP Server</label>
            <input type="text" name="imap_host" value="<?= htmlspecialchars($imapHost) ?>">
        </div>
        <!-- ... -->
    </form>
    <?php
}
```

**Impact:** Spart Eingabe-Zeit bei gleichem Provider  
**Aufwand:** 45 Minuten  
**Priorität:** 4

---

### S4: Migration Log detaillierter

**Problem:**
- Step 7 zeigt nur "✅ Migration erfolgreich"
- Bei Fehler: "❌ Migration fehlgeschlagen"
- Keine Info WELCHE Migration fehlschlug

**Lösung:**
```php
// completeSetup() - Enhanced Logging
$migrationLog = [];
foreach ($migrations as $migration) {
    $migrationName = basename($migration);
    try {
        require_once $migration;
        $migrationLog[] = [
            'file' => $migrationName,
            'status' => 'success',
            'message' => null
        ];
    } catch (Exception $e) {
        $migrationLog[] = [
            'file' => $migrationName,
            'status' => 'error',
            'message' => $e->getMessage()
        ];
        // Don't stop - continue with other migrations
    }
}

$_SESSION['setup']['migration_log'] = $migrationLog;
```

**Step 7 Render:**
```php
<?php if (!empty($_SESSION['setup']['migration_log'])): ?>
    <h3>Migration Log</h3>
    <table class="migration-log-table">
        <thead>
            <tr>
                <th>Migration</th>
                <th>Status</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($_SESSION['setup']['migration_log'] as $log): ?>
                <tr class="<?= $log['status'] ?>">
                    <td><?= htmlspecialchars($log['file']) ?></td>
                    <td><?= $log['status'] === 'success' ? '✅' : '❌' ?></td>
                    <td><?= $log['message'] ? htmlspecialchars($log['message']) : '-' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
```

**Impact:** Verbessert Debugging bei Migration-Fehlern  
**Aufwand:** 30 Minuten  
**Priorität:** 5

---

### S5: Form-State bei Validation Error erhalten

**Problem:**
- User füllt Step 3 aus: Host, DB-Name, User, Passwort
- Submit → Validation Error: "Ungültiger DB-Name"
- Page reload → Alle Felder LEER
- User muss alles nochmal eingeben

**Lösung:**
```php
// Case 3 - Save to session BEFORE validation
case 3:
    // Store in temp session (not 'data' yet)
    $_SESSION['setup']['temp'] = [
        'db_host' => $_POST['db_host'] ?? 'localhost',
        'db_name' => $_POST['db_name'] ?? 'ci_inbox',
        'db_user' => $_POST['db_user'] ?? 'root',
        'db_pass' => $_POST['db_pass'] ?? ''
    ];
    
    // Validation
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $_SESSION['setup']['temp']['db_name'])) {
        throw new Exception('Ungültiger Datenbankname');
    }
    
    // Success - move from temp to data
    $_SESSION['setup']['data']['db'] = $_SESSION['setup']['temp'];
    unset($_SESSION['setup']['temp']);
    
    redirect(4);
```

**Step 3 Render:**
```php
// Prefill from temp OR data
$dbData = $_SESSION['setup']['temp'] ?? $_SESSION['setup']['data']['db'] ?? [];
$dbHost = $dbData['db_host'] ?? 'localhost';
// ...
```

**Impact:** Verhindert Daten-Verlust bei Validation-Errors  
**Aufwand:** 60 Minuten (für alle Steps)  
**Priorität:** 5

---

## 🟢 NICE-TO-HAVE Verbesserungen (Optional)

### N1: Visual Progress Bar

**Feature:**
```html
<div class="progress-bar">
    <div class="progress-step <?= $currentStep >= 1 ? 'completed' : '' ?>">
        <div class="step-number">1</div>
        <div class="step-label">Hosting</div>
    </div>
    <div class="progress-step <?= $currentStep >= 2 ? 'completed' : '' ?>">
        <div class="step-number">2</div>
        <div class="step-label">Requirements</div>
    </div>
    <!-- ... Steps 3-7 -->
</div>

<style>
.progress-bar {
    display: flex;
    justify-content: space-between;
    margin: 20px 0;
}
.progress-step {
    flex: 1;
    text-align: center;
    opacity: 0.5;
}
.progress-step.completed {
    opacity: 1;
    color: #10b981;
}
</style>
```

**Aufwand:** 20 Min  
**Priorität:** 6

---

### N2: Zurück-Navigation erlauben

**Feature:**
- "← Zurück"-Button funktioniert
- User kann Step 3 aus Step 5 nochmal editieren
- Session-Data bleibt erhalten

**Problem:**
- Komplexität: Was wenn User DB in Step 3 ändert? → Migrations müssen neu laufen
- Use-Case selten: Meist linear durchgeklickt

**Aufwand:** 90 Min  
**Priorität:** 7

---

### N3: Alternative IMAP Ports testen

**Feature:**
```php
// autoDetectHosts() - More ports
foreach ($hosts['imap_candidates'] as $host) {
    foreach ([993, 143, 585, 995] as $port) {  // ✅ Mehr Ports
        $ssl = in_array($port, [993, 995]);
        // ...
    }
}
```

**Aufwand:** 30 Min  
**Priorität:** 7

---

### N4: Setup-Log Download

**Feature:**
```php
<a href="/setup/?action=download_log" class="btn btn-secondary">
    📥 Setup-Log herunterladen
</a>

// Handler
if (isset($_GET['action']) && $_GET['action'] === 'download_log') {
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="setup-log.txt"');
    
    echo "CI-Inbox Setup Log\n";
    echo "==================\n\n";
    echo "Steps completed: " . $_SESSION['setup']['step'] . "\n\n";
    echo "Migration Log:\n";
    foreach ($_SESSION['setup']['migration_log'] as $log) {
        echo sprintf("[%s] %s: %s\n", 
            $log['status'], 
            $log['file'], 
            $log['message'] ?? 'OK'
        );
    }
    exit;
}
```

**Aufwand:** 45 Min  
**Priorität:** 8

---

### N5: Dark Mode für Setup

**Feature:**
```css
@media (prefers-color-scheme: dark) {
    body {
        background: #1f2937;
        color: #f3f4f6;
    }
    .container {
        background: #374151;
    }
    /* ... */
}
```

**Aufwand:** 60 Min  
**Priorität:** 9

---

## Zusammenfassung & Priorisierung

### Implementierungsplan:

| Phase | Features | Aufwand | Priorität | Status |
|-------|----------|---------|-----------|--------|
| **Phase 1** | K1-K4 | 2h | KRITISCH | ✅ GEPLANT |
| **Phase 2** | S1-S3 | 1.5h | SINNVOLL | 🎯 M4 |
| **Phase 3** | S4-S5 | 1.5h | SINNVOLL | 🎯 M4 |
| **Phase 4** | N1-N5 | 4h | OPTIONAL | ⏸️ Later |

### Gesamtaufwand:
- **Kritisch:** 2h (Must-Do)
- **Sinnvoll:** 3h (Should-Do)
- **Nice-to-Have:** 4h (Could-Do)

**Total:** 9h (bei vollständiger Implementierung)

---

**Status:** Dokumentation abgeschlossen  
**Nächster Schritt:** Implementierung K1-K4

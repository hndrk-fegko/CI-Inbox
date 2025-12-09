# CI-Inbox Setup Wizard - Bug Tracking

**Datum:** 2025-12-09  
**Status:** Statische Code-Analyse durchgeführt  
**Analysierte Szenarien:** Fresh Installation, Unterbrochene Installation, Vendor Missing Edge Cases

---

## 🔴 KRITISCH - Sofort beheben

### [KRITISCH] - Bug #1: generateEnvFile() Parameter-Mismatch
**Status:** 🔍 Neu gefunden  
**Datum:** 2025-12-09  
**Kategorie:** Installer / Fatal Error

**Problem:**
- `handleStep6Submit()` ruft `generateEnvFile($sessionData, $basePath)` mit 2 Parametern auf
- Die Funktion `generateEnvFile(array $data): string` akzeptiert aber nur 1 Parameter
- .env-Datei wird NIE geschrieben, da Funktion nur String zurückgibt, aber kein `file_put_contents()` aufruft
- Setup schlägt IMMER in Step 6 fehl

**Reproduktion:**
1. Setup-Wizard bis Step 6 durchlaufen
2. "Installation starten" klicken
3. **Erwartetes Verhalten:** .env wird erstellt, Installation läuft durch
4. **Tatsächliches Verhalten:** PHP Fatal Error oder silent fail, .env existiert nicht

**Betroffene Dateien:**
- `src/public/setup/includes/step-6-review.php` (Zeile 20)
- `src/public/setup/includes/functions.php` (Zeile 214)

**Lösung:**
Die `generateEnvFile()` Funktion generiert nur den String-Content, schreibt aber keine Datei. Es fehlt:

```php
// In handleStep6Submit() - NACH generateEnvFile():
$envContent = generateEnvFile($sessionData);
$envPath = $basePath . '/.env';

// Atomically write .env file
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

// Set proper permissions
@chmod($envPath, 0600);
```

**Alternative Lösung:**
generateEnvFile() erweitern, um direkt zu schreiben:

```php
function generateEnvFile(array $data, string $basePath): bool
{
    // Generate content (existing logic)
    $smtpEncryption = !empty($data['smtp']['ssl']) ? 'tls' : 'none';
    $smtpFromEmail = $data['smtp']['from_email'] ?? $data['imap']['user'] ?? '';
    $smtpFromName = $data['smtp']['from_name'] ?? 'CI-Inbox';
    
    $envContent = <<<ENV
# CI-Inbox Environment Configuration...
ENV;

    // Write to file atomically
    $envPath = rtrim($basePath, '/') . '/../../../.env';
    $tempFile = $envPath . '.tmp';
    
    $written = file_put_contents($tempFile, $envContent, LOCK_EX);
    if ($written === false) {
        return false;
    }
    
    if (!rename($tempFile, $envPath)) {
        @unlink($tempFile);
        return false;
    }
    
    @chmod($envPath, 0600);
    return true;
}
```

**Risiko-Bewertung:**
- **Wahrscheinlichkeit:** Hoch (100% - passiert bei jedem Setup)
- **Impact:** Kritisch (Setup kann nicht abgeschlossen werden, .env fehlt)

---

### [KRITISCH] - Bug #2: Session-Datenstruktur inkonsistent
**Status:** 🔍 Neu gefunden  
**Datum:** 2025-12-09  
**Kategorie:** Installer / Logic

**Problem:**
- Step 3 speichert Daten unter `$_SESSION['setup']['data']['db']` mit Feldern: `host`, `name`, `user`, `pass`, `port`
- Step 6 erwartet aber: `$sessionData['db_host']`, `$sessionData['db_name']`, `$sessionData['db_user']`, `$sessionData['db_password']`, `$sessionData['db_port']`
- Flache vs. verschachtelte Struktur führt zu `undefined array key` Errors

**Reproduktion:**
1. Step 3: Datenbankdaten eingeben und speichern
2. Prüfung: `var_dump($_SESSION['setup']['data'])` zeigt `['db' => ['host' => '...', ...]`
3. Step 6: Versucht auf `$sessionData['db_host']` zuzugreifen
4. **Tatsächliches Verhalten:** PHP Warning "Undefined array key 'db_host'"

**Betroffene Dateien:**
- `src/public/setup/includes/step-3-database.php` (Zeile 49-55)
- `src/public/setup/includes/step-6-review.php` (Zeilen 25-35)
- `src/public/setup/includes/functions.php` (Zeile 231-235 in generateEnvFile)

**Lösung:**
Konsistente Datenstruktur verwenden. **Option A** (flache Struktur überall):

```php
// In step-3-database.php handleStep3Submit():
updateSessionData('db_host', $dbHost);
updateSessionData('db_name', $dbName);
updateSessionData('db_user', $dbUser);
updateSessionData('db_pass', $dbPass);
updateSessionData('db_port', 3306);
updateSessionData('db_exists', $dbExists);
```

**Option B** (verschachtelte Struktur überall):
```php
// In step-6-review.php und generateEnvFile():
$dbHost = $sessionData['db']['host'] ?? '';
$dbName = $sessionData['db']['name'] ?? '';
$dbUser = $sessionData['db']['user'] ?? '';
$dbPass = $sessionData['db']['pass'] ?? '';
$dbPort = $sessionData['db']['port'] ?? 3306;
```

**Empfehlung:** Option A (flache Struktur) für bessere Kompatibilität mit generateEnvFile().

**Risiko-Bewertung:**
- **Wahrscheinlichkeit:** Hoch (100% bei jedem Setup)
- **Impact:** Kritisch (Setup schlägt in Step 6 fehl)

---

### [KRITISCH] - Bug #3: writeProductionHtaccess() Fehlerbehandlung fehlt
**Status:** 🔍 Neu gefunden  
**Datum:** 2025-12-09  
**Kategorie:** Installer / Error Handling

**Problem:**
- `writeProductionHtaccess()` hat keine Fehlerbehandlung
- `file_put_contents()` Rückgabewert wird nicht geprüft (Zeile 317 in functions.php)
- Wenn Schreibrechte fehlen, schlägt Setup silent fehl
- Step 6 ruft `writeProductionHtaccess($basePath)` mit 1 Parameter auf, aber Funktion nimmt 0 Parameter (Zeile 149 in step-6-review.php)

**Reproduktion:**
1. Root-Verzeichnis auf read-only setzen: `chmod 555 /path/to/ci-inbox`
2. Setup bis Step 6 durchlaufen
3. **Erwartetes Verhalten:** Error-Meldung "Keine Schreibrechte für .htaccess"
4. **Tatsächliches Verhalten:** Setup "erfolgreich", aber .htaccess fehlt → App nicht erreichbar

**Betroffene Dateien:**
- `src/public/setup/includes/functions.php` (Zeile 274-318)
- `src/public/setup/includes/step-6-review.php` (Zeile 149)

**Lösung:**
```php
function writeProductionHtaccess(string $basePath = ''): bool
{
    $htaccessContent = <<<'HTACCESS'
# CI-Inbox Production Configuration...
HTACCESS;

    $htaccessPath = __DIR__ . '/../../../../.htaccess';
    
    // Check write permissions first
    $dir = dirname($htaccessPath);
    if (!is_writable($dir)) {
        error_log("Setup Error: Directory {$dir} is not writable");
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
```

**Risiko-Bewertung:**
- **Wahrscheinlichkeit:** Mittel (shared hosting mit restriktiven Rechten)
- **Impact:** Kritisch (App nicht erreichbar nach "erfolgreichem" Setup)

---

### [KRITISCH] - Bug #4: Encryption Key nicht in .env geschrieben
**Status:** 🔍 Neu gefunden  
**Datum:** 2025-12-09  
**Kategorie:** Installer / Security

**Problem:**
- `generateEnvFile()` schreibt `ENCRYPTION_KEY=` (leer) in .env (Zeile 238)
- Encryption Key wird nie generiert oder in Session gespeichert
- Step 6 versucht IMAP/SMTP Passwörter zu verschlüsseln, aber Key fehlt
- `openssl_encrypt()` schlägt fehl oder nutzt leeren Key → Passwörter unverschlüsselt

**Reproduktion:**
1. Setup durchlaufen bis Step 6
2. .env-Datei prüfen: `ENCRYPTION_KEY=` (leer)
3. Datenbank prüfen: `imap_accounts.imap_password` sollte verschlüsselt sein
4. **Tatsächliches Verhalten:** Encryption schlägt fehl oder Passwörter sind unverschlüsselt gespeichert

**Betroffene Dateien:**
- `src/public/setup/includes/step-6-review.php` (Zeilen 62-86)
- `src/public/setup/includes/functions.php` (Zeile 238)

**Lösung:**
```php
// In handleStep6Submit() VOR Datenbank-Operationen:
$encryptionKey = bin2hex(random_bytes(32)); // 64 hex chars = 32 bytes
updateSessionData('encryption_key', $encryptionKey);

// Dann bei IMAP-Verschlüsselung:
$encKey = getSessionData('encryption_key');
$encIv = openssl_random_pseudo_bytes(16);
$encPassword = openssl_encrypt(
    $sessionData['admin_imap_password'],
    'AES-256-CBC',
    hex2bin($encKey),
    0,
    $encIv
);

// In generateEnvFile() - ersetze leeren Key:
ENCRYPTION_KEY={$data['encryption_key']}
```

**Alternative:** Encryption Key NACH Migrations generieren (wie in setup-fixes-implemented.md beschrieben):
```php
// 1. Generate key first
$encryptionKey = bin2hex(random_bytes(32));

// 2. Run migrations with encrypted passwords
// ... encryption logic ...

// 3. Write .env with key AFTER migrations
$envContent = generateEnvFile($sessionData);
$envContent = str_replace('ENCRYPTION_KEY=', "ENCRYPTION_KEY={$encryptionKey}", $envContent);
file_put_contents('.env', $envContent);
```

**Risiko-Bewertung:**
- **Wahrscheinlichkeit:** Hoch (100% bei jedem Setup)
- **Impact:** Kritisch (IMAP/SMTP Passwörter nicht verschlüsselt → Security Risk)

---

## 🟠 HOCH - Bald beheben

### [HOCH] - Bug #5: Migration-Fehler führen zu Broken State
**Status:** 🔍 Neu gefunden  
**Datum:** 2025-12-09  
**Kategorie:** Installer / Robustheit

**Problem:**
- Step 6 schreibt .env VOR Migrationen (Bug #1 betrifft dies auch)
- Wenn Migrations fehlschlagen, existiert .env bereits
- Setup kann nicht neu gestartet werden (.env exists → App versucht zu laden)
- Datenbank ist halbfertig (einige Tabellen erstellt, andere nicht)

**Reproduktion:**
1. Migration absichtlich fehlschlagen lassen (z.B. SQL-Syntax-Fehler in migration einfügen)
2. Setup in Step 6 ausführen
3. **Erwartetes Verhalten:** Rollback, .env nicht geschrieben
4. **Tatsächliches Verhalten:** .env existiert, DB halbfertig, Setup blockiert

**Betroffene Dateien:**
- `src/public/setup/includes/step-6-review.php` (Zeilen 19-44)

**Lösung:**
Reihenfolge ändern:

```php
function handleStep6Submit(): void
{
    $basePath = getBasePath();
    $sessionData = getSessionData();
    
    try {
        // 1. Generate encryption key FIRST
        $encryptionKey = bin2hex(random_bytes(32));
        updateSessionData('encryption_key', $encryptionKey);
        
        // 2. Database connection
        $dsn = "mysql:host={$sessionData['db_host']}...";
        $pdo = new PDO($dsn, $sessionData['db_user'], $sessionData['db_password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 3. Create database if needed
        if (empty($sessionData['db_exists'])) {
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$sessionData['db_name']}`");
        }
        
        $pdo->exec("USE `{$sessionData['db_name']}`");
        
        // 4. Run migrations (can fail without side effects)
        $migrationsPath = $basePath . '/database/migrations';
        $migrations = glob($migrationsPath . '/*.php');
        sort($migrations);
        
        foreach ($migrations as $migration) {
            require_once $migration;
        }
        
        // 5. Create admin user and IMAP accounts
        // ... (encryption with $encryptionKey)
        
        // 6. Write .env ONLY after all DB operations succeeded
        $envContent = generateEnvFile($sessionData);
        $envContent = str_replace('ENCRYPTION_KEY=', "ENCRYPTION_KEY={$encryptionKey}", $envContent);
        
        $envPath = $basePath . '/../../../.env';
        $tempFile = $envPath . '.tmp';
        
        if (file_put_contents($tempFile, $envContent, LOCK_EX) === false) {
            throw new Exception('Fehler beim Schreiben der .env-Datei');
        }
        
        if (!rename($tempFile, $envPath)) {
            @unlink($tempFile);
            throw new Exception('Fehler beim Finalisieren der .env-Datei');
        }
        
        // 7. Write .htaccess
        if (!writeProductionHtaccess($basePath)) {
            throw new Exception('Fehler beim Erstellen der .htaccess-Datei');
        }
        
        updateSessionStep(7);
        redirectToStep(7);
        
    } catch (Exception $e) {
        // Rollback: Delete .env if it was written
        $envPath = $basePath . '/../../../.env';
        if (file_exists($envPath)) {
            @unlink($envPath);
        }
        
        throw new Exception('Installation fehlgeschlagen: ' . $e->getMessage());
    }
}
```

**Risiko-Bewertung:**
- **Wahrscheinlichkeit:** Mittel (Migrations können fehlschlagen bei DB-Problemen)
- **Impact:** Hoch (Broken State, manueller Cleanup nötig)

---

### [HOCH] - Bug #6: Concurrent Setup Execution möglich
**Status:** 🔍 Neu gefunden  
**Datum:** 2025-12-09  
**Kategorie:** Installer / Race Condition

**Problem:**
- Kein Setup-Lock Mechanismus
- Zwei Benutzer können gleichzeitig Setup starten
- Sessions sind getrennt, aber schreiben in dieselbe .env und Datenbank
- Race Condition bei .env-Schreiben und Migrations
- Inkonsistente Daten möglich (zwei Admin-Accounts, doppelte Labels, etc.)

**Reproduktion:**
1. Tab 1: Setup starten, bis Step 5
2. Tab 2: Setup starten (neue Session), bis Step 5
3. Tab 1: Step 6 Submit
4. Tab 2: Step 6 Submit (gleichzeitig)
5. **Erwartetes Verhalten:** Tab 2 blockiert mit "Setup läuft bereits"
6. **Tatsächliches Verhalten:** Beide schreiben in DB, .env wird überschrieben, Chaos

**Betroffene Dateien:**
- `src/public/setup/index.php` (Session Init, Zeile 315)
- `src/public/setup/includes/step-6-review.php` (Installation)

**Lösung:**
Lock-File Mechanismus implementieren:

```php
// In handleStep6Submit() ganz am Anfang:
$lockFile = __DIR__ . '/../../../../data/setup.lock';

// Check if setup is already running
if (file_exists($lockFile)) {
    $lockAge = time() - filemtime($lockFile);
    
    // Stale lock (older than 5 minutes) → remove
    if ($lockAge > 300) {
        @unlink($lockFile);
    } else {
        throw new Exception('Setup läuft bereits. Bitte warten Sie, bis die andere Installation abgeschlossen ist.');
    }
}

// Create lock file
if (!is_dir(dirname($lockFile))) {
    @mkdir(dirname($lockFile), 0755, true);
}

$lockCreated = file_put_contents($lockFile, getmypid() . "\n" . date('Y-m-d H:i:s'), LOCK_EX);
if ($lockCreated === false) {
    throw new Exception('Setup-Lock konnte nicht erstellt werden');
}

try {
    // ... normal setup logic ...
    
} finally {
    // Always remove lock, even on exception
    @unlink($lockFile);
}
```

**Alternative:** .env als Lock verwenden (einfacher):
```php
// At start of handleStep6Submit():
$envPath = $basePath . '/../../../.env';
if (file_exists($envPath)) {
    throw new Exception('Setup wurde bereits durchgeführt. .env-Datei existiert bereits.');
}
```

**Risiko-Bewertung:**
- **Wahrscheinlichkeit:** Niedrig (unwahrscheinlich, dass zwei Admins gleichzeitig Setup starten)
- **Impact:** Hoch (Datenbank-Chaos, doppelte Accounts)

---

### [HOCH] - Bug #7: Port-Parsing in Database Host fehlt
**Status:** 🔍 Neu gefunden  
**Datum:** 2025-12-09  
**Kategorie:** Installer / Logic

**Problem:**
- Step 3 UI sagt: "Für nicht-standard Ports verwenden Sie `hostname:port` Syntax"
- Aber Code parst niemals den Port aus `db_host` Feld
- `db_port` wird hart auf 3306 gesetzt (Zeile 54 in step-3-database.php)
- PDO DSN verwendet dann falschen Port

**Reproduktion:**
1. Step 3: Host eingeben als `localhost:3307`
2. Submit
3. **Erwartetes Verhalten:** Port 3307 wird erkannt und verwendet
4. **Tatsächliches Verhalten:** Port 3306 verwendet, Connection schlägt fehl

**Betroffene Dateien:**
- `src/public/setup/includes/step-3-database.php` (Zeilen 19-54)

**Lösung:**
```php
function handleStep3Submit(array $post): void
{
    $dbHostInput = $post['db_host'] ?? 'localhost';
    $dbName = $post['db_name'] ?? 'ci_inbox';
    $dbUser = $post['db_user'] ?? 'root';
    $dbPass = $post['db_pass'] ?? '';
    $dbExists = isset($post['db_exists']);
    
    // Parse host:port syntax
    $dbHost = $dbHostInput;
    $dbPort = 3306; // Default
    
    if (strpos($dbHostInput, ':') !== false) {
        list($dbHost, $portStr) = explode(':', $dbHostInput, 2);
        $dbPort = (int)$portStr;
        
        // Validate port range
        if ($dbPort < 1 || $dbPort > 65535) {
            throw new Exception('Ungültiger Port. Muss zwischen 1 und 65535 liegen.');
        }
    }
    
    // Validate database name
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
        throw new Exception('Ungültiger Datenbankname. Nur Buchstaben, Zahlen und Unterstriche erlaubt.');
    }
    
    // Validate host (basic sanitization)
    $dbHost = preg_replace('/[^a-zA-Z0-9\.\-]/', '', $dbHost);
    
    try {
        // Test connection with parsed port
        $pdo = new PDO(
            "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4",
            $dbUser,
            $dbPass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // ... rest of logic ...
        
        updateSessionData('db', [
            'host' => $dbHost,
            'name' => $dbName,
            'user' => $dbUser,
            'pass' => $dbPass,
            'port' => $dbPort  // ✅ Use parsed port
        ]);
        
        updateSessionStep(4);
        redirectToStep(4);
        
    } catch (PDOException $e) {
        throw new Exception('Datenbankverbindung fehlgeschlagen: ' . $e->getMessage());
    }
}
```

**Risiko-Bewertung:**
- **Wahrscheinlichkeit:** Mittel (nicht-standard Ports sind selten, aber kommen vor)
- **Impact:** Hoch (Setup schlägt fehl, User ist verwirrt)

---

## 🟡 MITTEL - Nächste Iteration

### [MITTEL] - Bug #8: Session Fixation möglich
**Status:** 🔍 Neu gefunden  
**Datum:** 2025-12-09  
**Kategorie:** Security

**Problem:**
- `initSession()` regeneriert Session-ID nicht (Zeile 392 in functions.php)
- Angreifer kann Session-ID vor Setup setzen und nach Setup nutzen
- Session-ID bleibt gleich über alle Setup-Steps hinweg

**Reproduktion:**
1. Angreifer setzt Cookie: `PHPSESSID=malicious_id`
2. Admin durchläuft Setup mit dieser Session
3. Angreifer nutzt `malicious_id` nach Setup → hat Admin-Session
4. **Tatsächliches Verhalten:** Möglich (wenn Setup ohne Auth läuft)

**Betroffene Dateien:**
- `src/public/setup/includes/functions.php` (Zeile 390-404)

**Lösung:**
```php
function initSession(): array
{
    if (session_status() === PHP_SESSION_NONE) {
        // Secure session config
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', '0'); // Set to '1' in production with HTTPS
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        
        session_start();
        
        // Regenerate session ID on first access (prevents fixation)
        if (!isset($_SESSION['setup_initialized'])) {
            session_regenerate_id(true);
            $_SESSION['setup_initialized'] = true;
        }
    }
    
    if (!isset($_SESSION['setup'])) {
        $_SESSION['setup'] = [
            'step' => 1,
            'data' => []
        ];
    }
    
    return $_SESSION['setup'];
}
```

**Risiko-Bewertung:**
- **Wahrscheinlichkeit:** Niedrig (erfordert aktiven Angriff während Setup)
- **Impact:** Mittel (Session-Hijacking nach Setup möglich)

---

### [MITTEL] - Bug #9: Kein CSRF-Schutz in Setup-Forms
**Status:** 🔍 Neu gefunden  
**Datum:** 2025-12-09  
**Kategorie:** Security

**Problem:**
- Alle Setup-Forms haben kein CSRF-Token
- Angreifer kann POST-Requests faken
- Besonders kritisch in Step 6 (Installation)

**Reproduktion:**
1. Angreifer erstellt bösartige Seite mit Form:
```html
<form action="http://victim.com/src/public/setup/?step=6" method="POST">
    <input name="..." value="malicious">
</form>
<script>document.forms[0].submit();</script>
```
2. Admin besucht bösartige Seite während Setup-Session aktiv
3. **Tatsächliches Verhalten:** POST wird ausgeführt, Settings werden überschrieben

**Betroffene Dateien:**
- Alle `src/public/setup/includes/step-*.php` Files (Form-Rendering)

**Lösung:**
```php
// In initSession() - generate CSRF token:
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// In renderFooter() oder renderHeader() - add to forms:
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

// In each handleStepXSubmit() - verify:
function handleStep3Submit(array $post): void
{
    // CSRF Check
    if (!isset($post['csrf_token']) || $post['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        throw new Exception('Ungültige Anfrage. Bitte versuchen Sie es erneut.');
    }
    
    // ... rest of logic ...
}
```

**Risiko-Bewertung:**
- **Wahrscheinlichkeit:** Niedrig (erfordert aktiven Angriff während Setup)
- **Impact:** Mittel (Settings können manipuliert werden)

---

### [MITTEL] - Bug #10: Sensitive Data in Session nicht verschlüsselt
**Status:** 🔍 Neu gefunden  
**Datum:** 2025-12-09  
**Kategorie:** Security

**Problem:**
- IMAP/SMTP Passwörter in Session als Plaintext gespeichert
- Session-Datei auf Server lesbar (meist `/tmp/sess_*`)
- Bei shared hosting mit Sicherheitslücken können andere Benutzer Session lesen

**Reproduktion:**
1. Setup bis Step 5 durchlaufen
2. Session-Datei prüfen: `/tmp/sess_XXXXX`
3. **Tatsächliches Verhalten:** Passwörter sind im Klartext sichtbar

**Betroffene Dateien:**
- Alle Step-Handler die Passwörter speichern

**Lösung:**
Session-Passwörter verschlüsseln (mit temporärem Key):

```php
// In step-4 und step-5 handler:
function storePasswordSecurely(string $password): string
{
    // Use PHP's built-in encryption for session
    $key = $_SESSION['temp_encrypt_key'] ?? null;
    if (!$key) {
        $key = random_bytes(32);
        $_SESSION['temp_encrypt_key'] = $key;
    }
    
    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($password, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

function retrievePasswordSecurely(string $encryptedData): string
{
    $key = $_SESSION['temp_encrypt_key'] ?? null;
    if (!$key) {
        throw new Exception('Encryption key missing');
    }
    
    $data = base64_decode($encryptedData);
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    
    return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
}
```

**Alternative:** Passwörter gar nicht in Session speichern, sondern nur für Step 6 im POST weitergeben (weniger praktisch).

**Risiko-Bewertung:**
- **Wahrscheinlichkeit:** Niedrig (erfordert Server-Sicherheitslücke)
- **Impact:** Mittel (Passwort-Leak während Setup)

---

## 🟢 NIEDRIG - Optional

### [NIEDRIG] - Bug #11: Vendor Auto-Install Timeout nicht konfigurierbar
**Status:** 🔍 Neu gefunden  
**Datum:** 2025-12-09  
**Kategorie:** Installer / UX

**Problem:**
- `installComposerDependenciesVendorMissing()` hat keinen Timeout für `exec()` Befehl (Zeile 109 in index.php)
- Composer Install kann auf langsamen Servern >5 Minuten dauern
- PHP `max_execution_time` könnte Script abbrechen
- User wartet ewig ohne Feedback

**Reproduktion:**
1. Server mit langsamem Internet
2. Auto-Install Button klicken
3. **Erwartetes Verhalten:** Progress-Feedback oder konfigurierbarer Timeout
4. **Tatsächliches Verhalten:** Browser wartet, evtl. Timeout nach 60s

**Betroffene Dateien:**
- `src/public/setup/index.php` (Zeilen 60-123)

**Lösung:**
```php
// Set higher execution time limit for composer install
set_time_limit(300); // 5 minutes

// Add timestamp to response for timeout detection
$startTime = time();
$command .= " install --no-dev --optimize-autoloader --no-interaction 2>&1";

// Execute with timeout awareness
$output = [];
$returnVar = 0;
@exec($command, $output, $returnVar);
$duration = time() - $startTime;

$logContent = "=== Composer Install Log ===\n";
$logContent .= "Date: " . date('Y-m-d H:i:s') . "\n";
$logContent .= "Duration: {$duration} seconds\n";
$logContent .= "Command: {$command}\n";
$logContent .= "Return Code: {$returnVar}\n";
$logContent .= "Output:\n" . implode("\n", $output);
file_put_contents($logFile, $logContent);
```

**Frontend:** Loading Overlay erweitern mit:
```javascript
// Warn user if taking too long
setTimeout(() => {
    document.querySelector('.loading-warning').innerHTML = 
        '⚠️ Installation dauert länger als erwartet (>2 Min). Bitte haben Sie Geduld...';
}, 120000); // After 2 minutes
```

**Risiko-Bewertung:**
- **Wahrscheinlichkeit:** Mittel (langsame Server/Verbindungen)
- **Impact:** Niedrig (UX-Problem, aber Installation läuft)

---

### [NIEDRIG] - Bug #12: Partial Vendor Directory nicht erkannt
**Status:** 🔍 Neu gefunden  
**Datum:** 2025-12-09  
**Kategorie:** Installer / Edge Case

**Problem:**
- Vendor-Check prüft nur ob `vendor/autoload.php` existiert (Zeile 24 in index.php)
- Partial/corrupt vendor Verzeichnis wird nicht erkannt
- Wenn `composer install` abbricht, kann vendor/ halbfertig sein
- Setup lädt, schlägt aber später mit "Class not found" fehl

**Reproduktion:**
1. Composer Install manuell abbrechen: Ctrl+C während `composer install`
2. Prüfung: `vendor/autoload.php` existiert, aber `vendor/slim/` fehlt
3. Setup aufrufen
4. **Erwartetes Verhalten:** "Vendor incomplete, please reinstall"
5. **Tatsächliches Verhalten:** Setup lädt, schlägt später mit Class-Not-Found fehl

**Betroffene Dateien:**
- `src/public/setup/index.php` (Zeilen 24-26)

**Lösung:**
Vendor-Integrität prüfen:

```php
// Enhanced vendor check
$vendorAutoload = __DIR__ . '/../../../vendor/autoload.php';
$vendorExists = file_exists($vendorAutoload);

// Additional integrity check: Ensure critical packages exist
if ($vendorExists) {
    $criticalPackages = [
        __DIR__ . '/../../../vendor/slim/slim',
        __DIR__ . '/../../../vendor/illuminate/database',
        __DIR__ . '/../../../vendor/monolog/monolog',
    ];
    
    foreach ($criticalPackages as $package) {
        if (!is_dir($package)) {
            $vendorExists = false;
            error_log("Setup Warning: Vendor incomplete - missing {$package}");
            break;
        }
    }
}

if (!$vendorExists) {
    // Show vendor missing page...
}
```

**Alternative:** Prüfe composer.lock Hash:
```php
$composerLock = __DIR__ . '/../../../composer.lock';
$vendorInstalled = __DIR__ . '/../../../vendor/composer/installed.json';

if (file_exists($composerLock) && file_exists($vendorInstalled)) {
    $lockData = json_decode(file_get_contents($composerLock), true);
    $installedData = json_decode(file_get_contents($vendorInstalled), true);
    
    // Compare package counts
    if (count($lockData['packages']) !== count($installedData['packages'])) {
        $vendorExists = false;
    }
}
```

**Risiko-Bewertung:**
- **Wahrscheinlichkeit:** Niedrig (erfordert abgebrochene Installation)
- **Impact:** Niedrig (Setup schlägt fehl, aber User kann neu installieren)

---

### [NIEDRIG] - Bug #13: Windows Path Backslashes in Migrations
**Status:** 🔍 Neu gefunden  
**Datum:** 2025-12-09  
**Kategorie:** Installer / Cross-Platform

**Problem:**
- Migration-Path verwendet `glob()` mit relativen Pfaden (step-6-review.php Zeile 38)
- Auf Windows: Backslashes `\` können Probleme machen
- `$basePath . '/database/migrations'` kann zu `C:\xampp\htdocs/database/migrations` führen

**Reproduktion:**
1. Windows Server mit XAMPP
2. Setup bis Step 6
3. **Erwartetes Verhalten:** Migrations werden gefunden
4. **Tatsächliches Verhalten:** Möglicherweise keine Migrations gefunden (abhängig von PHP-Version)

**Betroffene Dateien:**
- `src/public/setup/includes/step-6-review.php` (Zeile 38)

**Lösung:**
```php
// Normalize path separators
$migrationsPath = str_replace('\\', '/', $basePath . '/database/migrations');
$migrations = glob($migrationsPath . '/*.php');

// Or use DIRECTORY_SEPARATOR constant
$migrationsPath = $basePath . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
$migrations = glob($migrationsPath . DIRECTORY_SEPARATOR . '*.php');
```

**Risiko-Bewertung:**
- **Wahrscheinlichkeit:** Niedrig (moderne PHP-Versionen handhaben Mixed-Slashes gut)
- **Impact:** Niedrig (Setup schlägt fehl, aber Fehler ist offensichtlich)

---

## ✅ GEFIXT - Bereits behobene Bugs

### [GEFIXT] ✅ - Race Condition: .env-Timing
**Status:** ✅ Gefixt  
**Datum:** 2025-12-06 (Commit c692d73)  
**Kategorie:** Installer / Race Condition

**Problem:**
- .env wurde VOR Migrations geschrieben
- Bei Migration-Fehler blieb .env zurück → Broken State
- Setup konnte nicht neu gestartet werden

**Lösung implementiert:**
- Reihenfolge geändert: Encryption Key generieren → Migrations → .env schreiben
- .env wird nur noch bei erfolgreichen Migrations geschrieben
- Siehe `docs/dev/setup-fixes-implemented.md` K3

**Quelle:** Problem statement, `setup-fixes-implemented.md`

---

### [GEFIXT] ✅ - Path Resolution: getProjectRoot vs getBasePath
**Status:** ✅ Gefixt  
**Datum:** 2025-12-06 (Commit c692d73)  
**Kategorie:** Installer / Logic

**Problem:**
- Inkonsistente Pfad-Berechnungen zwischen IONOS (subdirectory) und Plesk (root)
- `getProjectRoot()` und `getBasePath()` führten zu falschen Redirects

**Lösung implementiert:**
- `getBasePath()` Funktion vereinheitlicht (functions.php Zeile 58-67)
- Regex-basierte Erkennung: `/^(.*?)/setup/`
- Funktioniert sowohl für `/src/public/setup` als auch `/setup`

**Quelle:** Problem statement, `setup-fixes-implemented.md`

---

### [GEFIXT] ✅ - XAMPP PHP_BINARY httpd.exe Problem
**Status:** ✅ Gefixt  
**Datum:** 2025-12-06 (Commit c692d73)  
**Kategorie:** Installer / XAMPP

**Problem:**
- Auf XAMPP zeigte `PHP_BINARY` auf `httpd.exe` statt `php.exe`
- Composer Auto-Install schlug fehl: "httpd.exe: command not found"
- Betraf Zeile 96 in setup/index.php

**Lösung implementiert:**
- `getPhpExecutable()` Funktion mit XAMPP-Fallback-Pfaden (functions.php Zeile 20-46)
- Prüft bekannte XAMPP-Pfade: `C:\xampp\php\php.exe`, `C:\XAMPP\php\php.exe`, etc.
- Duplicate in setup/index.php als `getPhpExecutableEarly()` (Zeile 35-58)

**Quelle:** Problem statement, `docs/dev/issue-autosetup-php-binary.txt`

---

### [GEFIXT] ✅ - Root .htaccess Routing-Chaos
**Status:** ✅ Gefixt (früher)  
**Datum:** Vor 2025-12-06  
**Kategorie:** Installer / Routing

**Problem:**
- Root `index.php` routete zu `/src/public/setup/`
- Nach Setup sollte Root-index.php gelöscht werden
- .htaccess-Regeln konnten kollidieren

**Lösung implementiert:**
- Root `index.php` existiert nicht mehr (wurde bereits entfernt)
- .htaccess redirected direkt zu `src/public/`
- Step 7 cleanup ist nicht mehr nötig

**Quelle:** Problem statement (erwähnt als "früher gefixt")

---

### [GEFIXT] ✅ - DB Connection Error Handling (K1)
**Status:** ✅ Gefixt  
**Datum:** 2025-12-07  
**Kategorie:** Installer / Error Handling

**Problem:**
- PDO-Verbindung ohne Try-Catch → Fatal Error bei falschen Credentials

**Lösung implementiert:**
- Try-Catch um PDO-Connection in step-3-database.php (Zeilen 34-62)
- User-freundliche Error-Message: "Datenbankverbindung fehlgeschlagen: ..."

**Quelle:** `docs/dev/setup-fixes-implemented.md`

---

### [GEFIXT] ✅ - DB Exists Checkbox (K2 - Shared Hosting)
**Status:** ✅ Gefixt  
**Datum:** 2025-12-07  
**Kategorie:** Installer / Shared Hosting

**Problem:**
- Shared Hosting hat oft keine CREATE DATABASE Rechte
- Setup schlug fehl

**Lösung implementiert:**
- Checkbox "Datenbank existiert bereits" in Step 3 Form
- Skip CREATE DATABASE wenn Checkbox aktiviert (step-3-database.php Zeile 44-46)

**Quelle:** `docs/dev/setup-fixes-implemented.md`

---

### [GEFIXT] ✅ - user_id Field für Shared IMAP (K5)
**Status:** ✅ Gefixt  
**Datum:** 2025-12-07  
**Kategorie:** Installer / Data Model

**Problem:**
- Shared IMAP Accounts brauchen `user_id = NULL`
- Personal IMAP brauchen `user_id = <user_id>`

**Lösung implementiert:**
- Step 6 setzt `user_id = NULL` für Shared IMAP (step-6-review.php Zeile 112)
- Step 4 setzt `user_id = $userId` für Admin Personal IMAP

**Quelle:** `docs/dev/setup-fixes-implemented.md`

---

## Zusammenfassung

**Neu gefundene Bugs:** 13  
**Kritische Bugs:** 4 (müssen vor nächstem Release gefixt werden)  
**Hohe Bugs:** 3 (sollten in nächster Iteration gefixt werden)  
**Mittlere Bugs:** 3 (Sicherheit - können später gefixt werden)  
**Niedrige Bugs:** 3 (UX/Edge Cases - optional)  
**Gefixte Bugs:** 6 (dokumentiert als ✅)

**Dringendste Fixes:**
1. Bug #1 - generateEnvFile() Parameter-Mismatch (KRITISCH - Setup schlägt IMMER fehl)
2. Bug #2 - Session-Datenstruktur inkonsistent (KRITISCH - undefined array keys)
3. Bug #4 - Encryption Key fehlt (KRITISCH - Security Risk)
4. Bug #3 - writeProductionHtaccess() Error Handling (KRITISCH - Silent Fail)

---

**Erstellt:** 2025-12-09  
**Analysiert von:** GitHub Copilot Coding Agent  
**Methode:** Statische Code-Analyse der Setup-Wizard Dateien  
**Dateien analysiert:** 12 PHP-Dateien im Setup-Bereich  
**Code-Zeilen analysiert:** ~3000+ Zeilen

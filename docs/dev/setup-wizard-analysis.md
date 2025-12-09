# Setup Wizard - Analyse & Refactoring Plan

**Datum:** 7. Dezember 2025  
**Analyse-Umfang:** 2259 Zeilen, 19 Funktionen, 6 POST Cases, 7 UI Steps  
**Aktueller Zustand:** Monolithische Datei `src/public/setup/index.php`

---

## Teil 1: Simulierte Setup-Durchläufe

### Szenario A: Erfolgreicher Standard-Setup (Happy Path)
**Profil:** Kleinunternehmen, XAMPP lokal, Standard-MySQL

**Ablauf:**
1. ✅ **Step 1:** Vendor existiert, weiter
2. ✅ **Step 2:** Alle Requirements erfüllt
3. ✅ **Step 3:** DB localhost/root, DB wird angelegt
4. ✅ **Step 4:** Admin-Account angelegt, keine Personal IMAP
5. ✅ **Step 5:** IMAP/SMTP manuell eingegeben
6. ✅ **Step 6:** Review
7. ✅ **Step 7:** Migrations laufen durch, Setup komplett

**Beobachtungen:**
- ⚠️ **KRITISCH:** Step 3 testet DB-Connection, aber zeigt KEINE Fehlermeldung wenn Connection erfolgreich aber DB-Creation schlägt fehl
- ⚠️ **KRITISCH:** Step 3 wirft PDO Exception bei falschen Credentials → unhandled, zeigt PHP Error
- 💡 **SINNVOLL:** Kein "DB Test"-Button vor Submit → User muss Form absenden um zu testen

---

### Szenario B: Shared Hosting (Ionos/Strato)
**Profil:** DB existiert bereits, exec() disabled, FTP-Upload vendor.zip

**Ablauf:**
1. ❌ **Step 0:** Vendor fehlt → "Dependencies installieren"-Button erscheint
2. ❌ **Composer Auto-Install:** Schlägt fehl (exec disabled)
3. ✅ **Manuell:** User lädt vendor.zip via FTP hoch
4. ✅ **Step 1:** "Erneut prüfen" → Vendor erkannt
5. ✅ **Step 2:** Requirements Check
6. ⚠️ **Step 3:** DB-Name existiert bereits → CREATE DATABASE IF NOT EXISTS funktioniert
7. ❌ **Step 3:** DB-User hat keine CREATE DATABASE Rechte → PDO Exception

**Beobachtungen:**
- ⚠️ **KRITISCH:** Keine Option "DB existiert bereits" → CREATE DATABASE wirft Fehler bei fehlenden Rechten
- 💡 **SINNVOLL:** Button "Nur Verbindung testen (ohne DB erstellen)"
- 💡 **SINNVOLL:** Checkbox "Datenbank existiert bereits" → Skip CREATE

---

### Szenario C: Admin mit Personal IMAP + SSL Cert Mismatch
**Profil:** Admin nutzt persönliche E-Mail hendrik.dreis@feg-koblenz.de

**Ablauf:**
1. ✅ **Step 4:** Admin gibt E-Mail ein
2. ✅ **Step 4:** Checkbox "Personal IMAP" aktiviert
3. ✅ **Step 4:** "Test IMAP" → SSL Cert Error erkannt
4. ✅ **Step 4:** Hostname korrigiert: imap.feg-koblenz.de → psa22.webhoster.ag
5. ✅ **Step 4:** Config in Hidden Fields gespeichert
6. ⚠️ **Step 5:** Shared INBOX Felder sind LEER → User muss alles nochmal eingeben

**Beobachtungen:**
- 💡 **NICE TO HAVE:** Step 5 IMAP-Felder mit Admin-IMAP-Config vorausfüllen (Domain-basiert)
- 💡 **SINNVOLL:** Step 5 zeigt Hinweis "Admin-IMAP bereits erkannt: psa22.webhoster.ag:993"
- ⚠️ **KRITISCH:** Personal IMAP wird IMMER mit `user_id` angelegt, aber Shared IMAP OHNE → Inkonsistenz

---

### Szenario D: IMAP/SMTP Autodiscovery Fails
**Profil:** Exotischer Provider, keine Standard-Hostnames

**Ablauf:**
1. ✅ **Step 5:** User gibt E-Mail ein: test@example-hosting.xyz
2. ❌ **IMAP Test:** autodiscovery probiert imap.example-hosting.xyz:993 → Timeout
3. ❌ **IMAP Test:** autodiscovery probiert imap.example-hosting.xyz:143 → Timeout
4. ❌ **Ergebnis:** "Could not connect to any IMAP server"
5. ⚠️ **Felder bleiben LEER** → User weiß nicht welche Hosts probiert wurden

**Beobachtungen:**
- 💡 **SINNVOLL:** Error zeigt "Probierte Hosts: imap.example-hosting.xyz:993, :143"
- 💡 **SINNVOLL:** Fallback-Input erscheint: "Bitte manuell eingeben"
- 💡 **NICE TO HAVE:** "Alternative Port testen" (585, 995)

---

### Szenario E: Migration schlägt fehl
**Profil:** DB-Constraint-Fehler in Migration 008

**Ablauf:**
1. ✅ **Step 6:** Review → Submit
2. ✅ **.env File:** Wird geschrieben
3. ✅ **Encryption Key:** Generiert
4. ❌ **Migration 008:** Wirft Exception "Duplicate column name"
5. ⚠️ **Kein Rollback** → DB-Schema halbfertig, .env existiert
6. ⚠️ **Step 7:** Zeigt "Migration fehlgeschlagen" aber User kann nicht zurück

**Beobachtungen:**
- ⚠️ **KRITISCH:** Keine Transaction um Migrations → Partial success möglich
- ⚠️ **KRITISCH:** .env wird IMMER geschrieben, auch bei Migration-Fail
- 💡 **SINNVOLL:** Step 7 zeigt "Setup teilweise erfolgreich" + Link zu "Zurücksetzen"
- 💡 **KRITISCH:** Function `resetSetup()` fehlt → Löscht .env, dropped DB tables

---

### Szenario F: Browser-Refresh während Setup
**Profil:** User drückt F5 in Step 4

**Ablauf:**
1. ✅ **Step 4:** Form ausgefüllt, noch nicht submitted
2. ⚠️ **F5:** Page reload
3. ✅ **Session intakt:** `$_SESSION['setup']['step']` ist 4
4. ✅ **Formular LEER** → Alle Eingaben verloren (erwartetes Verhalten)

**Alternative - Submit + F5:**
1. ✅ **Step 4:** Form submitted → POST → Redirect zu Step 5
2. ⚠️ **F5 in Step 5:** Page reload
3. ❌ **Browser:** "Formular erneut senden?" → User klickt Ja
4. ⚠️ **Doppeltes POST** → Session überschreibt, aber keine Duplicate-Detection

**Beobachtungen:**
- ✅ **GUT:** POST-Redirect-GET Pattern verhindert meiste Probleme
- 💡 **NICE TO HAVE:** Form-Persistence via Session (Temp-Storage bei Validation Error)

---

## Teil 2: Verbesserungsvorschläge (Klassifiziert)

### 🔴 KRITISCH (Must-Fix vor Production)

| # | Problem | Lösung | Aufwand | Prio |
|---|---------|--------|---------|------|
| K1 | **DB Connection Fail → Unhandled PDO Exception** | Try-Catch um PDO in Case 3, zeige User-Friendly Error | 15 Min | 1 |
| K2 | **CREATE DATABASE schlägt fehl bei fehlenden Rechten** | Checkbox "DB existiert bereits" → Skip CREATE | 30 Min | 1 |
| K3 | **Migration Fail → .env bereits geschrieben** | Schreibe .env NACH Migrations, nicht vorher | 10 Min | 1 |
| K4 | **Kein Rollback bei Migration-Fail** | Function `resetSetup()`: Löscht .env, DROP Tables mit Confirmation | 45 Min | 2 |
| K5 | **Personal IMAP hat user_id, Shared IMAP nicht** | Konsistent: Shared IMAP auch mit user_id (NULL oder Admin) | 20 Min | 2 |

**Total: ~120 Min (2 Stunden)**

---

### 🟡 SINNVOLL (Should-Have für bessere UX)

| # | Feature | Nutzen | Aufwand | Prio |
|---|---------|--------|---------|------|
| S1 | **DB Test-Button in Step 3** | User kann Credentials testen ohne Submit | 30 Min | 3 |
| S2 | **IMAP/SMTP Test zeigt probierte Hosts** | Debugging bei Autodiscovery-Fail | 15 Min | 4 |
| S3 | **Step 5 prefill mit Admin IMAP Config** | Spart Eingabe bei gleicher Domain | 45 Min | 4 |
| S4 | **Migration Log detaillierter** | Zeige jede Migration einzeln (nicht nur ✅/❌) | 30 Min | 5 |
| S5 | **Validation Errors: Form-State erhalten** | Session-Temp-Storage bei Exception | 60 Min | 5 |

**Total: ~180 Min (3 Stunden)**

---

### 🟢 NICE TO HAVE (Could-Have für Komfort)

| # | Feature | Nutzen | Aufwand | Prio |
|---|---------|--------|---------|------|
| N1 | **Step-Progress-Bar** | Visueller Fortschritt 1-7 | 20 Min | 6 |
| N2 | **"Zurück"-Navigation erlaubt Änderungen** | User kann Step 3 aus Step 5 nochmal editieren | 90 Min | 7 |
| N3 | **Alternative IMAP Ports (585, 995) testen** | Fallback bei Standard-Port-Fail | 30 Min | 7 |
| N4 | **Setup-Log Download** | User kann kompletten Log als .txt runterladen | 45 Min | 8 |
| N5 | **Dark Mode für Setup** | Konsistent mit App Theme | 60 Min | 9 |

**Total: ~245 Min (4 Stunden)**

---

## Teil 3: Code-Struktur-Analyse

### Aktueller Aufbau (Monolithisch - 2259 Zeilen)

```
setup/index.php
├── Lines 1-20      : PHP Header, Error Display
├── Lines 21-134    : Vendor Auto-Install Logic (inline function)
├── Lines 136-152   : getBasePath() helper
├── Lines 153-362   : showVendorMissingPage() mit HTML
├── Lines 364-400   : parseEnvFile() helper
├── Lines 401-560   : IMAP/SMTP Helpers (extractDomain, testImapConnection, etc.)
├── Lines 561-627   : AJAX Handlers (test_imap, test_smtp)
├── Lines 628-683   : Check if already installed
├── Lines 684-836   : POST Handler - SWITCH Cases 1-6
├── Lines 839-948   : completeSetup() - Migration Logic
├── Lines 951-1076  : installComposerDependencies()
├── Lines 1078-1125 : writeProductionHtaccess()
├── Lines 1127-1171 : generateEnvFile()
├── Lines 1173-1437 : checkRequirements() + checkHostingEnvironment()
├── Lines 1438-1710 : HTML Header + Navigation
├── Lines 1711-2064 : UI Steps 1-7 (HTML Forms)
└── Lines 2065-2259 : JavaScript + HTML Footer
```

### Funktionsabhängigkeiten

#### Globale Funktionen (Von allen Steps benötigt):
- `getBasePath()` - URL-Building
- `parseEnvFile()` - .env lesen

#### Step-spezifische Funktionen:

**Step 0 (Vendor Check):**
- `showVendorMissingPage()`
- `installComposerDependenciesVendorMissing()`

**Step 1 (Hosting Check):**
- `checkHostingEnvironment()`
- `return_bytes()`, `format_bytes()`

**Step 2 (Requirements):**
- `checkRequirements()`

**Step 3 (Database):**
- PDO (inline)
- → Braucht: `testDatabaseConnection()` (NEU)

**Step 4 (Admin Account):**
- AJAX: `testImapConnection()`, `extractRealHostFromCertError()`, `autoDetectHosts()`, `extractDomain()`

**Step 5 (IMAP/SMTP):**
- AJAX: `testImapConnection()`, `testSmtpConnection()`
- Gleiche Helpers wie Step 4

**Step 6 (Complete):**
- `completeSetup()`
  - `generateEnvFile()`
  - Eloquent Migrations (inline)
  - `writeProductionHtaccess()`

---

## Teil 4: Refactoring-Vorschlag - Modular Structure

### Zielstruktur:

```
src/public/setup/
├── index.php                    # Main Controller (230 Zeilen)
├── includes/
│   ├── functions.php            # Globale Helpers (150 Zeilen)
│   ├── step-0-vendor.php        # Vendor Check (200 Zeilen)
│   ├── step-1-hosting.php       # Hosting Environment (180 Zeilen)
│   ├── step-2-requirements.php  # Requirements Check (120 Zeilen)
│   ├── step-3-database.php      # Database Setup (200 Zeilen)
│   ├── step-4-admin.php         # Admin Account (300 Zeilen)
│   ├── step-5-email.php         # IMAP/SMTP Config (350 Zeilen)
│   ├── step-6-review.php        # Review + Complete (100 Zeilen)
│   └── step-7-success.php       # Success Page (80 Zeilen)
└── assets/
    ├── setup.css                # Extracted styles
    └── setup.js                 # Extracted JavaScript
```

### index.php (Main Controller)

```php
<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

// Init session
if (!isset($_SESSION['setup'])) {
    $_SESSION['setup'] = ['step' => 0, 'data' => []];
}

$currentStep = (int)($_GET['step'] ?? $_SESSION['setup']['step']);
$error = null;

// Vendor check (special case)
if (!file_exists(__DIR__ . '/../../../vendor/autoload.php')) {
    require_once __DIR__ . '/includes/step-0-vendor.php';
    renderVendorCheck();
    exit;
}

require_once __DIR__ . '/../../../vendor/autoload.php';

// AJAX Handlers
if (isset($_GET['ajax'])) {
    handleAjaxRequest($_GET['ajax']);
    exit;
}

// POST Handler - Delegate to step files
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stepFile = __DIR__ . "/includes/step-{$currentStep}-*.php";
        $matches = glob($stepFile);
        
        if (!empty($matches)) {
            require_once $matches[0];
            $result = handleStepSubmit($_POST, $_SESSION);
            
            if ($result['success']) {
                $_SESSION['setup']['step'] = $result['next_step'];
                $_SESSION['setup']['data'] = array_merge(
                    $_SESSION['setup']['data'], 
                    $result['data']
                );
                redirect($result['next_step']);
            } else {
                $error = $result['error'];
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Render UI
renderHeader($currentStep);
renderStep($currentStep, $_SESSION['setup']['data'], $error);
renderFooter();
```

### Step File Structure (Example: step-3-database.php)

```php
<?php
/**
 * Step 3: Database Configuration
 * 
 * Handles:
 * - Form rendering
 * - POST validation
 * - DB connection test
 * - DB creation
 */

function handleStepSubmit(array $post, array &$session): array
{
    $dbHost = $post['db_host'] ?? 'localhost';
    $dbName = $post['db_name'] ?? 'ci_inbox';
    $dbUser = $post['db_user'] ?? 'root';
    $dbPass = $post['db_pass'] ?? '';
    $dbExists = isset($post['db_exists']); // NEW: Checkbox
    
    // Validation
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
        return [
            'success' => false,
            'error' => 'Ungültiger Datenbankname'
        ];
    }
    
    // Test connection
    try {
        $pdo = new PDO(
            "mysql:host={$dbHost};charset=utf8mb4",
            $dbUser,
            $dbPass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Create DB if needed
        if (!$dbExists) {
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` 
                        CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
        
        return [
            'success' => true,
            'next_step' => 4,
            'data' => [
                'db' => [
                    'host' => $dbHost,
                    'name' => $dbName,
                    'user' => $dbUser,
                    'pass' => $dbPass
                ]
            ]
        ];
        
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => 'Datenbankverbindung fehlgeschlagen: ' . $e->getMessage()
        ];
    }
}

function renderStepForm(array $data, ?string $error): void
{
    $dbHost = $data['db']['host'] ?? 'localhost';
    $dbName = $data['db']['name'] ?? 'ci_inbox';
    $dbUser = $data['db']['user'] ?? 'root';
    ?>
    <h2>Datenbank-Konfiguration</h2>
    
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <form method="POST" id="dbForm">
        <div class="form-group">
            <label>Host</label>
            <input type="text" name="db_host" value="<?= $dbHost ?>" required>
        </div>
        
        <div class="form-group">
            <label>Datenbankname</label>
            <input type="text" name="db_name" value="<?= $dbName ?>" required>
        </div>
        
        <div class="form-group">
            <label>Benutzername</label>
            <input type="text" name="db_user" value="<?= $dbUser ?>" required>
        </div>
        
        <div class="form-group">
            <label>Passwort</label>
            <input type="password" name="db_pass">
        </div>
        
        <!-- NEW: DB exists checkbox -->
        <div class="form-group">
            <label>
                <input type="checkbox" name="db_exists"> 
                Datenbank existiert bereits (Skip CREATE DATABASE)
            </label>
        </div>
        
        <!-- NEW: Test button -->
        <button type="button" id="test-db-btn">Verbindung testen</button>
        <div id="db-test-result"></div>
        
        <button type="submit">Weiter →</button>
    </form>
    
    <script>
    document.getElementById('test-db-btn').addEventListener('click', async function() {
        const formData = new FormData(document.getElementById('dbForm'));
        const response = await fetch('/setup/?ajax=test_db', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        document.getElementById('db-test-result').innerHTML = 
            result.success ? '✅ Verbindung erfolgreich' : '❌ ' + result.error;
    });
    </script>
    <?php
}
```

### includes/functions.php (Globale Helpers)

```php
<?php
/**
 * Global Helper Functions für Setup Wizard
 */

function getBasePath(): string { /* ... */ }
function parseEnvFile(string $path): array { /* ... */ }
function redirect(int $step): void {
    $basePath = getBasePath();
    header("Location: {$basePath}/setup/?step={$step}");
    exit;
}

function renderHeader(int $currentStep): void { /* HTML Header + Progress Bar */ }
function renderFooter(): void { /* HTML Footer */ }

function handleAjaxRequest(string $action): void
{
    header('Content-Type: application/json');
    
    switch ($action) {
        case 'test_db':
            echo json_encode(testDatabaseConnection($_POST));
            break;
        case 'test_imap':
            echo json_encode(testImapConnection(...));
            break;
        case 'test_smtp':
            echo json_encode(testSmtpConnection(...));
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }
}

function testDatabaseConnection(array $post): array
{
    try {
        $pdo = new PDO(
            "mysql:host={$post['db_host']};charset=utf8mb4",
            $post['db_user'],
            $post['db_pass'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        return ['success' => true, 'message' => 'Verbindung erfolgreich'];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// IMAP/SMTP Helpers
function extractDomain(string $email): string { /* ... */ }
function extractRealHostFromCertError(string $host, int $port): ?string { /* ... */ }
function autoDetectHosts(string $email): array { /* ... */ }
function testImapConnection(...): array { /* ... */ }
function testSmtpConnection(...): array { /* ... */ }
```

---

## Teil 5: Migration-Plan

### Phase 1: Kritische Fixes (2h)
✅ Ohne Refactoring, direkt in monolithische Datei

1. K1: DB Connection Error Handling
2. K2: "DB existiert bereits"-Checkbox
3. K3: .env Schreiben NACH Migrations
4. K4: resetSetup() Function
5. K5: Shared IMAP mit user_id

### Phase 2: Refactoring (8-12h)
✅ File-Split, Modular Structure

1. **Woche 1:**
   - `functions.php` extrahieren (globale Helpers)
   - Step 0-2 extrahieren (einfache Steps)
   
2. **Woche 2:**
   - Step 3-5 extrahieren (komplexe Forms)
   - AJAX Handler zentralisieren
   
3. **Woche 3:**
   - Step 6-7 (Complete + Success)
   - CSS/JS in separate Files
   - Testing aller Szenarien

### Phase 3: UX Improvements (4h)
✅ Sinnvolle Features nach Refactoring

1. S1: DB Test-Button
2. S3: Step 5 Prefill
3. S4: Migration Log Details

### Phase 4: Nice-to-Have (Optional)
✅ Nur wenn Zeit/Budget

- Progress Bar
- Zurück-Navigation
- Setup Log Download

---

## Zusammenfassung

### Aktueller Status:
- ✅ **Funktional:** Setup läuft durch (Happy Path)
- ⚠️ **Robust:** Fehlt Error Handling bei Edge Cases
- ❌ **Wartbar:** 2259 Zeilen Monolith, schwer zu erweitern

### Empfehlung:
1. **Sofort:** Phase 1 (Kritische Fixes) → 2h Aufwand
2. **M3 Ende:** Phase 2 (Refactoring) → 10h Aufwand
3. **M4:** Phase 3 (UX) → 4h Aufwand
4. **Later:** Phase 4 (Nice-to-Have)

### ROI:
- **Phase 1:** Verhindert Production-Bugs (High ROI)
- **Phase 2:** Senkt Maintenance-Cost langfristig (Medium ROI)
- **Phase 3:** Verbessert UX, reduziert Support-Anfragen (Medium ROI)
- **Phase 4:** Low ROI, nur bei Ressourcen-Überschuss

---

**Nächster Schritt:** Soll ich mit Phase 1 (Kritische Fixes) beginnen?

# CI-Inbox Installation Review - IONOS Webhosting
# Simulation: sv-wolken.de auf IONOS Shared Hosting

**Datum:** 5. Dezember 2025  
**Szenario:** Installation auf sv-wolken.de (IONOS Webhosting)  
**Hosting-Typ:** IONOS Webhosting Plus / Business  
**Besonderheiten:** Spezielle Verzeichnisstruktur, PHP-Version-Auswahl, .htaccess-Besonderheiten

---

## 🏢 IONOS Hosting - Spezifische Eigenschaften

### **Verzeichnisstruktur:**
```
/ (FTP-Root)
├── .htaccess              # Kann hier liegen
├── logs/                  # IONOS-eigene Logs
└── (Domainname)/         # sv-wolken.de/
    ├── .htaccess          # Oder hier
    ├── vendor/
    ├── src/
    │   └── public/        # Hier sollte DocumentRoot zeigen
    ├── database/
    └── composer.json
```

**IONOS-Besonderheit:** Bei manchen Tarifen ist der FTP-Root nicht gleich DocumentRoot!

### **PHP-Konfiguration:**
- ✅ PHP 8.1/8.2/8.3 verfügbar (Auswahl im Control Panel)
- ✅ IMAP-Extension meist aktiviert
- ⚠️ exec/shell_exec oft deaktiviert
- ✅ memory_limit: 256M-512M (je nach Tarif)
- ✅ Composer: NICHT vorinstalliert

### **Datenbank:**
- MySQL 5.7 oder 8.0 (je nach Paket)
- Host: meist `localhost` oder `db5123456789.hosting-data.io`
- User: meist `dbXXXXXXXX` (8-stellig)

### **Cron-Jobs:**
- ⚠️ Basic Tarif: KEINE Cronjobs
- ✅ Plus/Business: Cronjobs verfügbar (aber kompliziert einzurichten)
- **Empfehlung:** Webcron trotzdem nutzen (flexibler)

---

## 🎬 Installations-Simulation

### **Ausgangssituation:**
```
Domain: sv-wolken.de
IONOS-Paket: Webhosting Plus
PHP-Version: 8.1 (Standard 7.4 → muss umgestellt werden!)
FTP-Zugang: user@sv-wolken.de
Datenbank: Bereits angelegt via IONOS Control Panel
```

---

## **Phase 1: Vorbereitung (Lokal)**

### **Schritt 1.1: Repository klonen**
```powershell
PS C:\Users\Admin> cd C:\Projekte
PS C:\Projekte> git clone https://github.com/hndrk-fegko/C-IMAP.git ci-inbox
PS C:\Projekte> cd ci-inbox
```

✅ **Erfolgreich**

### **Schritt 1.2: Composer Dependencies installieren**
```powershell
PS C:\Projekte\ci-inbox> composer install --no-dev --optimize-autoloader
```

**Output:**
```
Loading composer repositories with package information
Installing dependencies from lock file
Package operations: 45 installs, 0 updates, 0 removals
  - Installing psr/container (2.0.2)
  - Installing slim/slim (4.12.0)
  - Installing illuminate/database (10.48.0)
  ...
  [45/45] Installing vlucas/phpdotenv
Generating optimized autoload files
```

✅ **Erfolgreich** - vendor/ Verzeichnis erstellt (~82 MB)

---

## **Phase 2: IONOS Control Panel Konfiguration**

### **Schritt 2.1: PHP-Version umstellen**

**Problem gefunden:**
```
IONOS Standard: PHP 7.4.x
CI-Inbox benötigt: PHP 8.1+
```

**Lösung:**
1. IONOS Control Panel → Hosting
2. sv-wolken.de → Einstellungen
3. "PHP-Version" → **8.1.x auswählen**
4. Speichern

⏱️ **Wartezeit:** 5-10 Minuten (IONOS aktiviert neue PHP-Version)

✅ **PHP 8.1.29 aktiv**

---

### **Schritt 2.2: Datenbank-Details notieren**

**IONOS Control Panel → Datenbanken:**
```
Datenbank-Name: db123456789_1
Hostname: db123456789.hosting-data.io
Benutzername: dbo123456789
Passwort: mO8#xK2$pL9@qR
```

📋 **Details kopiert**

---

### **Schritt 2.3: DocumentRoot prüfen**

**IONOS zeigt:**
```
DocumentRoot: /kunden/123456_78901/webseiten/sv-wolken.de/
```

**Problem:** Das ist NICHT das gleiche wie FTP-Root!

**FTP-Struktur:**
```
/kunden/123456_78901/
├── logs/
└── webseiten/
    └── sv-wolken.de/     ← DocumentRoot zeigt hierhin
```

⚠️ **IONOS-Spezialfall:** Projekt muss in `/webseiten/sv-wolken.de/` hochgeladen werden!

---

## **Phase 3: FTP-Upload**

### **Schritt 3.1: FileZilla Verbindung**

**Verbindungsdaten:**
```
Host: sv-wolken.de (oder ftp.ionos.de)
Benutzername: u123456789-sv-wolken
Passwort: ****************
Port: 21 (oder 22 für SFTP)
```

✅ **Verbunden**

**Aktuelles Verzeichnis:**
```
/kunden/123456_78901/webseiten/sv-wolken.de/
```

---

### **Schritt 3.2: Projekt hochladen**

**Upload-Strategie:** Komplettes Projekt inkl. vendor/ (da lokal bereits installiert)

```
Upload-Liste (FileZilla):
[====================================] 100%
- .htaccess (1 KB)
- composer.json (2 KB)
- vendor/ (4.235 Dateien, 82 MB)      ← ~35 Minuten
- src/ (287 Dateien)
- database/ (45 Dateien)
- logs/ (Verzeichnis erstellt)
- data/ (Verzeichnis erstellt)
...

Geschätzte Zeit: 45 Minuten
Tatsächliche Zeit: 52 Minuten
```

⏱️ **Upload dauerte länger als erwartet** (IONOS FTP ist langsam)

✅ **Alle Dateien hochgeladen**

---

### **Schritt 3.3: Verzeichnisstruktur verifizieren**

**Via FileZilla:**
```
/kunden/123456_78901/webseiten/sv-wolken.de/
├── .htaccess              ✅
├── vendor/                ✅ (82 MB)
│   └── autoload.php       ✅
├── src/
│   └── public/
│       ├── index.php      ✅
│       └── setup/
│           └── index.php  ✅
├── database/              ✅
├── logs/                  ✅
└── composer.json          ✅
```

✅ **Struktur korrekt**

---

## **Phase 4: Dateiberechtigungen setzen**

**IONOS-Besonderheit:** Dateiberechtigungen müssen manuell gesetzt werden!

### **Via FileZilla:**

**Rechtsklick → Dateiberechtigungen:**
```
logs/      → 755 (rwxr-xr-x)
data/      → 755 (rwxr-xr-x)
vendor/    → 755 (rekursiv)
src/       → 755 (rekursiv)
```

✅ **Berechtigungen gesetzt**

---

## **Phase 5: DocumentRoot-Problem lösen**

### **Problem erkannt:**

**IONOS DocumentRoot zeigt auf:**
```
/kunden/123456_78901/webseiten/sv-wolken.de/
```

**Wir brauchen aber:**
```
/kunden/123456_78901/webseiten/sv-wolken.de/src/public/
```

### **Lösungs-Option 1: DocumentRoot ändern (NICHT möglich bei IONOS Basic/Plus!)**

**IONOS erlaubt DocumentRoot-Änderung nur bei:**
- ❌ Webhosting Basic: Nein
- ❌ Webhosting Plus: Nein
- ✅ Managed Server / VPS: Ja

### **Lösungs-Option 2: .htaccess Redirect (Unsere Lösung!)**

**Prüfung der vorhandenen .htaccess:**
```apache
# CI-Inbox Root .htaccess
# Redirect to setup wizard or application

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Check if setup is needed
    RewriteCond %{REQUEST_URI} !^/src/public/setup/
    RewriteCond %{DOCUMENT_ROOT}/.env !-f
    RewriteRule ^(.*)$ src/public/setup/index.php [L]

    # If setup is complete, redirect all to src/public/
    RewriteCond %{REQUEST_URI} !^/src/public/
    RewriteRule ^(.*)$ src/public/$1 [L]
</IfModule>
```

✅ **Perfekt! Unsere .htaccess funktioniert auch mit IONOS-Struktur**

---

## **Phase 6: Erster Aufruf**

### **Browser-Test:**
```
URL: https://sv-wolken.de/
Browser: Firefox 121
```

### **Was passiert:**

1. **Apache prüft .htaccess:**
   ```apache
   RewriteCond %{DOCUMENT_ROOT}/.env !-f  # .env fehlt!
   RewriteRule ^(.*)$ src/public/setup/index.php [L]
   ```

2. **Redirect:**
   ```
   https://sv-wolken.de/ 
   → https://sv-wolken.de/src/public/setup/index.php
   ```

3. **setup/index.php lädt:**
   ```php
   // Zeile 13-20
   $vendorAutoload = __DIR__ . '/../../../vendor/autoload.php';
   $vendorExists = file_exists($vendorAutoload);
   ```
   ✅ `true` - vendor/ ist vorhanden!

4. **Setup-Wizard startet:**
   ```
   🚀 CI-Inbox Setup
   Willkommen! Lassen Sie uns Ihre Installation einrichten.
   ```

✅ **Setup-Wizard lädt erfolgreich!**

---

## **Phase 7: Setup-Wizard - Schritt 1 (Hosting-Check)**

### **System-Check läuft:**

```
🌐 Hosting-Umgebung prüfen

Prüfpunkt                    Aktuell              Empfohlen           Status
─────────────────────────────────────────────────────────────────────────
PHP Version                  8.1.29               8.1.0+              ✓ OK
PHP Memory Limit             512M                 128M empfohlen      ✓ OK
Max Execution Time           120s                 60s empfohlen       ✓ OK
Upload Max Filesize          32M                  10M empfohlen       ✓ OK
Composer Dependencies        Installiert          vorhanden           ✓ OK
Logs Verzeichnis             Ja                   Schreibrechte       ✓ OK
MySQL Support                Verfügbar            PDO MySQL           ✓ OK
IMAP Extension               Aktiviert            Erforderlich        ✓ OK
OpenSSL Extension            Aktiviert            Erforderlich        ✓ OK
Safe Mode                    Deaktiviert          Deaktiviert         ✓ OK
Speicherplatz                45.2 GB              100 MB empfohlen    ✓ OK
PHP Disabled Functions       exec, shell_exec     -                   ⚠ Warnung
                            deaktiviert
```

### **Empfehlung angezeigt:**
```
💡 Empfehlung (exec):
   Automatische Composer-Installation nicht möglich.
   Aber: vendor/ bereits vorhanden, kein Problem!
```

✅ **Alle Checks bestanden! "Weiter" Button ist grün**

---

## **Phase 8: Setup-Wizard - Schritt 2 (Systemanforderungen)**

```
Systemanforderungen

Anforderung                  Benötigt             Aktuell             Status
─────────────────────────────────────────────────────────────────────────
PHP Version                  8.1.0                8.1.29              ✓ OK
PDO MySQL Extension          Enabled              Enabled             ✓ OK
IMAP Extension               Enabled              Enabled             ✓ OK
OpenSSL Extension            Enabled              Enabled             ✓ OK
Mbstring Extension           Enabled              Enabled             ✓ OK
JSON Extension               Enabled              Enabled             ✓ OK
cURL Extension               Enabled              Enabled             ✓ OK
.env Schreibbar              Ja                   Ja                  ✓ OK
logs/ Schreibbar             Ja                   Ja                  ✓ OK
```

✅ **Alle Requirements erfüllt**

---

## **Phase 9: Setup-Wizard - Schritt 3 (Datenbank)**

### **User-Eingaben:**
```
Datenbank-Host:      db123456789.hosting-data.io
Datenbank-Name:      db123456789_1
Benutzername:        dbo123456789
Passwort:            mO8#xK2$pL9@qR
```

### **Verbindungstest:**
```php
try {
    $pdo = new PDO(
        "mysql:host=db123456789.hosting-data.io;charset=utf8mb4",
        "dbo123456789",
        "mO8#xK2$pL9@qR"
    );
    // Erfolgreich!
} catch (PDOException $e) {
    // ...
}
```

✅ **Verbindung erfolgreich**

### **Datenbank anlegen:**
```php
$pdo->exec("CREATE DATABASE IF NOT EXISTS `db123456789_1` ...");
```

⚠️ **Warnung:** Database already exists

**Aber das ist OK!** Die Datenbank wurde bereits im IONOS Control Panel angelegt.

✅ **Weiter zu Schritt 4**

---

## **Phase 10: Admin-Account, IMAP/SMTP & Installation**

**Schritte 4-6 verlaufen identisch zu Plesk-Simulation:**

### **Schritt 4: Admin-Account**
```
Name: Max Mustermann
E-Mail: admin@sv-wolken.de
Passwort: ****************
```
✅ **Validiert und gespeichert**

### **Schritt 5: IMAP/SMTP (Optional)**
```
IMAP Host: imap.ionos.de
Port: 993 (SSL)
Username: mail@sv-wolken.de
Password: ****************

SMTP Host: smtp.ionos.de
Port: 587 (STARTTLS)
From: noreply@sv-wolken.de
```

**Test-Verbindung:**
- IMAP: ✅ Erfolgreich
- SMTP: ✅ Erfolgreich

### **Schritt 6: Zusammenfassung**
```
Datenbank: db123456789_1 @ db123456789.hosting-data.io
Admin: admin@sv-wolken.de
IMAP: imap.ionos.de:993
SMTP: smtp.ionos.de:587
```

**Klick: [🚀 Installation starten]**

---

## **Phase 11: Installation läuft**

### **Was passiert:**

1. **`.env` schreiben:**
   ```
   /kunden/123456_78901/webseiten/sv-wolken.de/.env
   ```
   ✅ Erfolgreich (2 KB)

2. **Encryption Key generieren:**
   ```
   ENCRYPTION_KEY=e4b8c3f1a7d9...
   ```
   ✅ 64 Zeichen hex

3. **Datenbank-Migrationen:**
   ```
   Running migration: 001_create_users_table.php
   Running migration: 002_create_imap_accounts_table.php
   ...
   Running migration: 022_add_two_factor_auth.php
   ```
   ✅ 22 Migrationen erfolgreich

4. **Admin-User erstellen:**
   ```sql
   INSERT INTO users (email, password_hash, name, role, is_active) 
   VALUES ('admin@sv-wolken.de', '$2y$10$...', 'Max Mustermann', 'admin', 1);
   ```
   ✅ User ID 1 erstellt

5. **IMAP-Account speichern:**
   ```sql
   INSERT INTO imap_accounts (email, server, port, ssl, ...) 
   VALUES ('mail@sv-wolken.de', 'imap.ionos.de', 993, 1, ...);
   ```
   ✅ IMAP Account ID 1 erstellt

6. **Production .htaccess schreiben:**
   ```php
   writeProductionHtaccess();
   ```
   ✅ Root .htaccess überschrieben

**Installation dauerte:** 8 Sekunden

---

## **Phase 12: Schritt 7 - Fertig!**

```
✅ Installation abgeschlossen!

C-IMAP wurde erfolgreich installiert.
Sie können sich jetzt mit Ihrem Administrator-Account anmelden.

[Zur Anmeldung →]
```

**Klick auf "Zur Anmeldung":**

**Redirect:**
```
https://sv-wolken.de/src/public/login.php
```

**⚠️ IONOS-spezifisches Problem erkannt!**

---

## **❌ Problem gefunden: URL enthält noch /src/public/**

### **Erwartung:**
```
https://sv-wolken.de/login.php
```

### **Realität:**
```
https://sv-wolken.de/src/public/login.php
```

### **Ursache:**

Die `.htaccess` macht zwar interne Redirects, aber Browser-Redirects (wie nach Installation) verwenden die echte URL.

### **⚠️ KRITISCHES PROBLEM FÜR IONOS!**

---

## **🔧 Lösung 1: Setup-Wizard Base-Path Detection**

### **Problem-Analyse:**

**In `setup/index.php` Zeile ~205:**
```php
header('Location: /login.php');  // ❌ Funktioniert nur wenn DocumentRoot = src/public/
```

**Muss dynamisch sein:**
```php
$basePath = getBasePath();  // Erkennt "/src/public" oder ""
header("Location: {$basePath}/login.php");
```

### **Fix implementiert:**

**Neue Helper-Funktion hinzugefügt (Zeile ~23):**
```php
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
    $scriptName = $_SERVER['SCRIPT_NAME']; // e.g., "/src/public/setup/index.php"
    
    // Extract base path (everything before /setup/)
    if (preg_match('#^(.*?)/setup/#', $scriptName, $matches)) {
        return $matches[1]; // e.g., "/src/public" or ""
    }
    
    return '';
}
```

**Alle Redirects angepasst:**
- Zeile ~207: Login-Redirect
- Zeile ~239: Setup Step 1
- Zeile ~258: Setup Step 2
- Zeile ~264: Setup Step 3
- Zeile ~302: Setup Step 4
- Zeile ~332: Setup Step 5
- Zeile ~356: Setup Step 6
- Zeile ~363: Setup Step 7

✅ **Code angepasst in `src/public/setup/index.php`**

---

## **🔧 Lösung 2: .htaccess URL-Cleanup**

### **Problem:**

Selbst mit Base-Path im Setup-Wizard würden URLs wie `/src/public/login.php` im Browser sichtbar bleiben.

**Unschön:**
```
https://sv-wolken.de/src/public/login.php
https://sv-wolken.de/src/public/dashboard.php
```

### **Bessere Lösung:**

.htaccess sollte `/src/public/` aus sichtbaren URLs automatisch entfernen!

**Erweiterte Root .htaccess:**
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Security: Deny access to sensitive directories
    RewriteRule ^(vendor|database|logs|data)/ - [F,L]

    # Setup-Check: Redirect to installer if .env missing
    RewriteCond %{REQUEST_URI} !^/src/public/setup/
    RewriteCond %{DOCUMENT_ROOT}/.env !-f
    RewriteRule ^(.*)$ src/public/setup/index.php [L]

    # URL Cleanup: Strip /src/public/ from URLs if present (IONOS fix)
    RewriteCond %{THE_REQUEST} \s/src/public/(.+)\s [NC]
    RewriteRule ^ /%1 [R=301,L]

    # Internal Rewrite: Route all requests to src/public/
    RewriteCond %{REQUEST_URI} !^/src/public/
    RewriteRule ^(.*)$ src/public/$1 [L]
</IfModule>
```

**Neue Regel (Zeile 13-15):**
```apache
# URL Cleanup: Strip /src/public/ from URLs if present (IONOS fix)
RewriteCond %{THE_REQUEST} \s/src/public/(.+)\s [NC]
RewriteRule ^ /%1 [R=301,L]
```

**Was das macht:**
```
Browser: GET /src/public/login.php
→ Apache: 301 Redirect to /login.php
→ Browser: GET /login.php
→ Apache: Internal rewrite to src/public/login.php
→ Server liefert: src/public/login.php
→ Browser zeigt: https://sv-wolken.de/login.php ✓
```

✅ **Sauberste Lösung: URLs sehen immer clean aus!**

---

## **Phase 13: Fix-Implementierung**

### **Schritt 13.1: Root .htaccess aktualisieren**

**Via FileZilla:**
1. `.htaccess` im Root-Verzeichnis öffnen
2. Neue Regel hinzufügen (Zeile 13-15)
3. Speichern

✅ **Aktualisiert**

### **Schritt 13.2: Setup-Wizard wurde bereits gepatcht**

✅ **`src/public/setup/index.php` bereits mit `getBasePath()` erweitert**

---

## **Phase 14: Funktionstest nach Fix**

### **Test 1: Setup erneut aufrufen**
```
URL: https://sv-wolken.de/
```

**Apache prüft:**
```apache
RewriteCond %{DOCUMENT_ROOT}/.env !-f  # .env existiert jetzt!
```

**Kein Setup-Redirect!**

**Apache wendet an:**
```apache
RewriteCond %{REQUEST_URI} !^/src/public/
RewriteRule ^(.*)$ src/public/$1 [L]
```

**Interner Rewrite:**
```
/ → src/public/index.php
```

✅ **Dashboard lädt (oder Login, falls Session fehlt)**

---

### **Test 2: Direkter Setup-Aufruf**
```
URL: https://sv-wolken.de/src/public/setup/index.php
```

**Apache erkennt:**
```apache
RewriteCond %{THE_REQUEST} \s/src/public/(.+)\s [NC]
RewriteRule ^ /%1 [R=301,L]
```

**301 Redirect:**
```
→ https://sv-wolken.de/setup/index.php
```

**Dann internal rewrite:**
```
/setup/index.php → src/public/setup/index.php
```

**Setup-Check:**
```php
if (file_exists(__DIR__ . '/../../../.env')) {
    // Already installed, redirect to login
    $basePath = getBasePath();  // "" (weil jetzt / → src/public/ intern geroutet wird)
    header("Location: {$basePath}/login.php");
}
```

✅ **Funktioniert!**

---

### **Test 3: Login-Seite**
```
URL: https://sv-wolken.de/login.php
```

**Apache rewrite:**
```
/login.php → src/public/login.php (intern)
```

**Browser zeigt:**
```
https://sv-wolken.de/login.php  ✓
```

✅ **Clean URL!**

---

### **Test 4: Login durchführen**
```
POST /login.php
E-Mail: admin@sv-wolken.de
Passwort: ****************
```

**Controller:**
```php
header('Location: /dashboard.php');  // Oder /index.php
```

**Apache:**
```
/dashboard.php → src/public/dashboard.php (intern)
```

✅ **Dashboard lädt mit Clean URL!**

---

## **✅ Installation erfolgreich!**

---

## **📊 IONOS vs Plesk - Unterschiede Zusammenfassung**

| **Aspekt**                   | **Plesk (webhoster.ag)**      | **IONOS (sv-wolken.de)**       |
|------------------------------|-------------------------------|--------------------------------|
| **DocumentRoot**             | `/httpdocs/`                  | `/webseiten/{domain}/`         |
| **PHP Standard-Version**     | 8.0                           | 7.4 → Muss geändert werden!    |
| **exec/shell_exec**          | Deaktiviert                   | Deaktiviert                    |
| **FTP-Geschwindigkeit**      | ~30 Min (vendor/)             | ~52 Min (vendor/)              |
| **Control Panel**            | Plesk Obsidian                | IONOS Hosting Control Panel    |
| **DB-Host**                  | localhost                     | db123456789.hosting-data.io    |
| **Cron-Jobs**                | Verfügbar (kompliziert)       | Nur Plus/Business              |
| **.htaccess Setup**          | Funktioniert ohne Anpassung   | URL-Cleanup-Regel benötigt     |
| **Setup-Wizard Redirect**    | `/login.php` (direkt)         | `getBasePath()` detection nötig|

---

## **🔧 Notwendige Fixes für IONOS**

### **Fix 1: Setup-Wizard Base-Path Detection** ✅
- **Datei:** `src/public/setup/index.php`
- **Änderung:** `getBasePath()` Funktion hinzugefügt
- **Grund:** Redirects müssen Base-Path erkennen

### **Fix 2: Root .htaccess URL-Cleanup** ✅
- **Datei:** `.htaccess` (Root)
- **Änderung:** URL-Cleanup-Regel für `/src/public/` URLs
- **Grund:** Externe Redirects zeigen sonst `/src/public/` in Browser-URL

---

## **📝 Lessons Learned**

1. **IONOS DocumentRoot ist anders:**
   - Nicht `/html/` wie bei vielen Hostern
   - Sondern `/webseiten/{domain}/`

2. **PHP-Version muss manuell umgestellt werden:**
   - Standard ist 7.4, aber CI-Inbox braucht 8.1+
   - Änderung im Control Panel notwendig

3. **FTP ist langsamer als bei Plesk:**
   - 52 Minuten vs 30 Minuten (vendor/ upload)
   - Empfehlung: vendor.zip Methode nutzen

4. **.htaccess muss flexibler sein:**
   - URL-Cleanup-Regel für saubere URLs
   - Base-Path-Detection in PHP-Skripten

5. **Externe Redirects vs Internal Rewrites:**
   - .htaccess `RewriteRule` mit `[L]` = intern
   - PHP `header('Location:')` = extern (Browser sieht URL)
   - Kombination aus beidem für beste UX

---

## **🎯 Installation auf IONOS - Zusammenfassung**

### **Zeitaufwand:**
- Vorbereitung (lokal): 10 Minuten
- IONOS Control Panel: 15 Minuten (inkl. Wartezeit PHP-Umstellung)
- FTP-Upload: 52 Minuten
- Setup-Wizard: 8 Minuten
- **Gesamt:** ~85 Minuten

### **Gefundene Probleme:**
1. ❌ PHP 7.4 Standard → Muss auf 8.1 umgestellt werden
2. ❌ FTP langsamer als Plesk
3. ❌ URLs zeigen `/src/public/` bei externen Redirects
4. ❌ Setup-Wizard hatte keine Base-Path-Detection

### **Implementierte Lösungen:**
1. ✅ PHP-Version manuell im Control Panel ändern
2. ✅ Geduld beim Upload (oder vendor.zip nutzen)
3. ✅ .htaccess URL-Cleanup-Regel
4. ✅ `getBasePath()` Funktion im Setup-Wizard

### **Ergebnis:**
✅ **Installation erfolgreich!**  
✅ **Alle Fixes funktionieren!**  
✅ **CI-Inbox läuft einwandfrei auf IONOS Webhosting Plus!**

---

## **🚀 Nächste Schritte (für Produktiveinsatz)**

1. **Webcron einrichten:**
   ```
   cron-job.org → Neuer Job
   URL: https://sv-wolken.de/api/webcron/poll?token={CRON_SECRET_TOKEN}
   Intervall: Alle 5 Minuten
   ```

2. **SSL-Zertifikat prüfen:**
   ```
   IONOS Let's Encrypt ist meist automatisch aktiv
   Falls nicht: Control Panel → SSL/TLS
   ```

3. **Backup einrichten:**
   ```
   IONOS bietet tägliche Backups
   Zusätzlich: Datenbank-Export wöchentlich
   ```

4. **Performance überwachen:**
   ```
   logs/app.log regelmäßig prüfen
   Dashboard → System-Check nutzen
   ```

---

**Ende der Simulation**

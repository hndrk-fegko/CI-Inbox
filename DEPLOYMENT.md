# CI-Inbox Deployment Guide

**Anleitung für die Installation auf Standard-Webhosting (Shared Hosting)**

---

## 📦 Schritt 1: Projekt vorbereiten

### Option A: Lokal vorbereiten (empfohlen)

```bash
# 1. Repository klonen
git clone <repository-url> ci-inbox
cd ci-inbox

# 2. Composer Dependencies installieren
composer install --no-dev --optimize-autoloader

# 3. Projekt ist jetzt bereit zum Upload
```

**Wichtig:** `composer install` muss **vor** dem Upload ausgeführt werden, da die meisten Shared-Hosting-Anbieter keinen Composer haben!

### Option B: Automatische Installation durch Setup-Wizard ⭐ NEU

Der Setup-Wizard kann automatisch versuchen, die Dependencies zu installieren:
1. Laden Sie das Projekt **ohne** `vendor/` hoch
2. Rufen Sie den Setup-Wizard auf
3. Im Hosting-Check wird ein Button **"🚀 Automatisch beheben"** angezeigt
4. Der Wizard versucht `composer install` auszuführen
5. Falls erfolgreich → Weiter zur Installation
6. Falls fehlgeschlagen → Option C verwenden

**Funktioniert wenn:**
- PHP `exec()` Funktion nicht deaktiviert ist
- Composer global installiert ist ODER
- Der Wizard kann composer.phar herunterladen

### Option C: vendor.zip manuell herunterladen

Falls weder Option A noch B funktionieren:
1. Laden Sie `vendor.zip` herunter:
   - **GitHub Release:** https://github.com/hndrk-fegko/CI-Inbox/releases/latest
   - **Direktlink:** Im Setup-Wizard unter "📦 Manuelle Installation"
2. Entpacken Sie `vendor.zip` im Projekt-Root
3. Das Verzeichnis `vendor/` sollte nun existieren
4. Laden Sie diese per FTP hoch (falls noch nicht geschehen)

**vendor.zip erstellen (für Entwickler):**
```bash
# PHP-Skript (Linux/Mac/Windows)
php scripts/create-vendor-zip.php

# PowerShell (Windows)
.\scripts\create-vendor-zip.ps1
```

### Was sind Dependencies?
- PHP-Bibliotheken (wie Slim Framework, PHPMailer, Monolog)
- Werden im `vendor/` Verzeichnis gespeichert (~80 MB, ~50 MB gepackt)
- Notwendig für den Betrieb der Anwendung
- 11 Hauptpakete + deren Abhängigkeiten (ca. 4.000 Dateien)

---

## 📤 Schritt 2: Dateien hochladen

### Via FTP/SFTP

```
1. Verbinden Sie sich mit Ihrem Webhosting (FileZilla, WinSCP, etc.)
2. Laden Sie ALLE Dateien hoch nach: /public_html/ oder /htdocs/
3. Stellen Sie sicher, dass hochgeladen wurde:
   ✓ vendor/ (Composer Dependencies)
   ✓ src/
   ✓ database/
   ✓ .htaccess (Root-Verzeichnis)
   ✓ composer.json
```

**Struktur nach Upload:**
```
/public_html/
├── .htaccess          # Redirect-Logik (siehe unten)
├── vendor/            # PHP Dependencies (~80 MB)
├── src/
│   └── public/        # Web-Root (wird via .htaccess angesteuert)
├── database/
├── composer.json
└── ... weitere Dateien
```

---

## 🔧 Schritt 3: DocumentRoot konfigurieren

### Variante A: DocumentRoot ändern (ideal, aber nicht überall möglich)

Falls Ihr Hosting-Panel (cPanel, Plesk, **NICHT** IONOS Basic/Plus) es erlaubt:
1. Öffnen Sie Domain-Einstellungen
2. Ändern Sie DocumentRoot von `/public_html/` auf `/public_html/src/public/`
3. **Fertig!** Keine .htaccess im Root notwendig

**Verfügbar bei:**
- ✅ cPanel (Advanced)
- ✅ Plesk
- ✅ DirectAdmin
- ❌ IONOS Webhosting Basic/Plus (nur bei Managed Server/VPS)

### Variante B: .htaccess Redirect (Standard, funktioniert überall)

Die `.htaccess` im Root-Verzeichnis leitet automatisch um:

**Beim ersten Aufruf:** `domain.com` → `src/public/setup/` (Setup-Wizard)  
**Nach Installation:** `domain.com` → `src/public/` (Anwendung)

Die `.htaccess` wird automatisch vom Setup-Wizard erstellt.

**Funktioniert auf:**
- ✅ Alle Apache-basierten Shared Hosting-Umgebungen
- ✅ Plesk, cPanel, IONOS, ALL-INKL, HostEurope, etc.
- ✅ Automatische URL-Bereinigung (kein `/src/public/` in Browser-URLs)

---

## 🌐 Hosting-spezifische Hinweise

### **IONOS Webhosting**

**Besonderheiten:**
1. **PHP-Version MUSS manuell umgestellt werden!**
   - Standard: PHP 7.4 → CI-Inbox benötigt 8.1+
   - IONOS Control Panel → Hosting → Domain → Einstellungen → "PHP-Version" → 8.1.x
   - Wartezeit: 5-10 Minuten nach Änderung

2. **Datenbank-Host:**
   - Nicht `localhost`, sondern: `db123456789.hosting-data.io`
   - Details im IONOS Control Panel → Datenbanken

3. **FTP ist langsamer als andere Anbieter:**
   - vendor/ Upload: ~50 Minuten (vs. ~30 Minuten auf Plesk)
   - **Empfehlung:** vendor.zip nutzen (25 MB statt 4000 Dateien)

4. **DocumentRoot kann nicht geändert werden:**
   - IONOS Basic/Plus: Nur via .htaccess möglich
   - .htaccess von CI-Inbox funktioniert automatisch!

**Getestet auf:** IONOS Webhosting Plus (siehe `INSTALLATION-REVIEW-IONOS.md`)

---

### **Plesk (webhoster.ag, Strato, etc.)**

**Besonderheiten:**
1. **PHP-Version meist schon 8.0+** (selten Anpassung nötig)
2. **Datenbank-Host:** Meist `localhost`
3. **FTP-Geschwindigkeit:** Gut (~30 Min vendor/)
4. **DocumentRoot ändern:** Möglich (empfohlen)

**Getestet auf:** psa22.webhoster.ag (siehe `INSTALLATION-REVIEW.md`)

---

## 🚀 Schritt 4: Setup-Wizard ausführen

### Aufrufen

Öffnen Sie in Ihrem Browser: `https://ihre-domain.de/`

Sie werden automatisch zum Setup-Wizard weitergeleitet.

### 7 Schritte des Wizards

#### **Schritt 1: Hosting-Umgebung prüfen** 🌐

Der Wizard analysiert automatisch:
- ✅ PHP-Version (8.1+ erforderlich)
- ✅ Benötigte Extensions (IMAP, PDO MySQL, OpenSSL, etc.)
- ✅ Memory Limit (128 MB empfohlen)
- ✅ Verfügbarer Speicherplatz (100 MB+)
- ✅ Composer Dependencies (vendor/ vorhanden?)
- ✅ Schreibrechte (logs/, .env)

**Status-Anzeigen:**
- 🟢 **OK** - Alles perfekt
- 🟡 **Warnung** - Funktioniert, aber eingeschränkt
- 🔴 **Fehler** - Installation blockiert

**Automatische Fehlerbehebung:**

Wenn `vendor/` fehlt, bietet der Wizard drei Lösungen an:

**Option 1: Automatische Installation** (empfohlen)
```
🔧 Automatische Fehlerbehebung verfügbar
Composer Dependencies: Fehlend
[🚀 Automatisch beheben]
```
Klick auf den Button → Wizard führt `composer install` aus

**Option 2: Manuelle vendor.zip Installation**
```
📦 Manuelle Installation: vendor.zip herunterladen
[📥 vendor.zip herunterladen (GitHub Release)]
[📥 Alternativer Download (Dropbox)]
```
Zip herunterladen, entpacken, per FTP hochladen

**Option 3: Lokale Vorbereitung**
```
💡 Tipp: Führen Sie auf Ihrem lokalen PC composer install aus
und laden Sie dann das komplette Projekt inkl. vendor/ per FTP hoch.
```

**Empfehlungen bei anderen Problemen:**

| Problem | Lösung |
|---------|--------|
| PHP < 8.1 | In cPanel: "PHP-Version auswählen" → 8.1+ |
| IMAP Extension fehlt | In cPanel: "PHP Extensions" → IMAP aktivieren |
| Memory Limit < 128M | `.htaccess` ergänzen: `php_value memory_limit 128M` |

#### **Schritt 2: System-Anforderungen**

Prüfung aller PHP-Extensions und Berechtigungen.

#### **Schritt 3: Datenbank konfigurieren** 🗄️

```
Host: localhost (meist)
Datenbank: ci_inbox
Benutzername: [Ihr DB-User]
Passwort: [Ihr DB-Passwort]
```

**Hinweis:** Die Datenbank wird automatisch angelegt, falls nicht vorhanden.

#### **Schritt 4: Admin-Account erstellen** 👤

Erster Administrator-Zugang:
- Name
- E-Mail
- Passwort (min. 8 Zeichen)

#### **Schritt 5: IMAP/SMTP konfigurieren** 📧

Optional - kann auch später konfiguriert werden:
- **IMAP:** E-Mail-Empfang (z.B. `imap.example.com:993`)
- **SMTP:** E-Mail-Versand (z.B. `smtp.example.com:587`)

**Tipp:** Nutzen Sie den CLI Auto-Discovery Wizard für automatische Erkennung:
```bash
php src/modules/imap/tests/setup-autodiscover.php
```

#### **Schritt 6: Zusammenfassung**

Überprüfung aller Eingaben.

#### **Schritt 7: Installation abschließen** ✅

Der Wizard:
1. Erstellt `.env` Datei
2. Generiert Encryption Key (64 Zeichen)
3. Führt Datenbank-Migrationen aus
4. Erstellt Admin-User
5. **Schreibt finale .htaccess** (Redirect zu `src/public/`)

---

## 🔒 Schritt 5: Sicherheit prüfen

Nach erfolgreicher Installation:

### 1. .env schützen

Die `.env` sollte **nicht** öffentlich erreichbar sein. Die `.htaccess` blockiert den Zugriff automatisch.

**Test:** `https://ihre-domain.de/.env` sollte **403 Forbidden** zeigen.

### 2. Sensitive Verzeichnisse schützen

Automatisch geschützt durch `.htaccess`:
- `/vendor/`
- `/database/`
- `/logs/`
- `/tests/`
- `/src/` (außer `/src/public/`)

### 3. Setup-Wizard deaktivieren

Nach Installation ist `/setup/` automatisch deaktiviert (prüft auf vorhandenen Admin-User).

---

## ⏰ Schritt 6: Cron-Job einrichten (E-Mail-Polling)

CI-Inbox nutzt **Webcron** (externe Cron-Jobs), da viele Shared-Hosting-Anbieter keine Cronjobs anbieten.

### Bei cron-job.org registrieren

1. Gehen Sie zu: https://cron-job.org (kostenlos)
2. Erstellen Sie einen neuen Cronjob:
   ```
   Titel: CI-Inbox E-Mail Polling
   URL: https://ihre-domain.de/api/webcron/poll?token=<SECRET_TOKEN>
   Intervall: Alle 5 Minuten
   ```
3. `<SECRET_TOKEN>` finden Sie in Ihrer `.env` Datei unter `CRON_SECRET_TOKEN`

### Alternative: Hosting-Cron (falls verfügbar)

Falls Ihr Hoster Cronjobs anbietet:
```bash
*/5 * * * * curl -s "https://ihre-domain.de/api/webcron/poll?token=<SECRET_TOKEN>"
```

---

## 🐛 Troubleshooting

### Problem: "500 Internal Server Error"

**Ursache:** Meist `.htaccess` oder PHP-Version

**Lösung:**
1. Prüfen Sie PHP-Version (muss 8.1+ sein)
2. Schauen Sie in `logs/app.log`
3. Aktivieren Sie Error-Reporting: In `.env` → `APP_DEBUG=true`

### Problem: "Class not found"

**Ursache:** `vendor/` fehlt oder unvollständig

**Lösung:**
1. Lokal: `composer install --no-dev`
2. Komplettes `vendor/` Verzeichnis hochladen

### Problem: "Connection refused" bei Datenbank

**Ursache:** Falsche DB-Credentials

**Lösung:**
1. Prüfen Sie Zugangsdaten im Hosting-Panel
2. Host ist meist `localhost`, manchmal `127.0.0.1` oder spezifischer Hostname
3. `.env` korrigieren

### Problem: IMAP Extension fehlt

**Ursache:** PHP-Extension nicht aktiviert

**Lösung:**
1. cPanel → "Select PHP Version" → Extensions → IMAP aktivieren
2. Oder kontaktieren Sie Support

### Problem: Keine Schreibrechte für logs/

**Ursache:** Dateirechte falsch gesetzt

**Lösung:**
```bash
# Via FTP oder SSH:
chmod 755 logs/
chmod 755 data/
```

---

## 📊 Nach der Installation

### Erste Schritte

1. **Login:** `https://ihre-domain.de/login`
2. **IMAP-Konto hinzufügen:** Settings → IMAP Accounts
3. **Benutzer einladen:** Settings → Users
4. **Labels konfigurieren:** Settings → Labels

### Weitere Konfiguration

Siehe Dokumentation:
- [GETTING-STARTED.md](docs/GETTING-STARTED.md) - Erste Schritte
- [Setup-Autodiscover.md](docs/dev/Setup-Autodiscover.md) - IMAP/SMTP Auto-Config
- [architecture.md](docs/dev/architecture.md) - System-Architektur

---

## 🔄 Updates

### Update-Prozess

```bash
# 1. Backup erstellen
mysqldump -u [user] -p [database] > backup.sql
cp .env .env.backup

# 2. Neue Version hochladen
# Alle Dateien AUSSER .env überschreiben

# 3. Migrations ausführen (falls neue vorhanden)
php database/migrate.php
```

---

## 💬 Support

- **Dokumentation:** `docs/` Verzeichnis
- **Issues:** GitHub Issues
- **Logs:** `logs/app.log` für Fehleranalyse

---

**Viel Erfolg mit CI-Inbox! 🚀**

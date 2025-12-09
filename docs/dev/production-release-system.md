# CI-Inbox Production Release System

## Übersicht

Automatisierte Erstellung von sauberen Production-Releases ohne Development-Dateien.

## Komponenten

### 1. `.deployignore`
Definiert, welche Dateien/Ordner NICHT ins Production-Release kommen:
- Development-Dokumentation (`docs/dev/`, `basics.txt`)
- Test-Dateien (`tests/`)
- CI/CD-Konfiguration (`.github/`)
- IDE-Dateien (`.vscode/`, `.idea/`)
- Interne Dev-Tools
- **vendor/** (platform-spezifisch, auf Ziel-Server installiert)

### 2. `scripts/create-production-release.php`
PHP-Script zur lokalen Erstellung von Production-Releases:
```bash
php scripts/create-production-release.php
```
**Output:** `ci-inbox-production.zip` (~1.5 MB, ohne vendor/)

### 3. `scripts/create-vendor-zip-windows.php` 
PHP-Script für Windows-optimierte Dependencies:
```bash
php scripts/create-vendor-zip-windows.php
```
**Output:** `vendor-windows.zip` (~5-7 MB, Windows DLLs)
**Use Case:** XAMPP/WAMP/IIS-Deployments

### 4. `scripts/create-vendor-zip.php`
PHP-Script für platform-native vendor.zip:
```bash
php scripts/create-vendor-zip.php
```
**Output:** `vendor.zip` (aktuelles System)
**Note:** GitHub Actions erstellt Linux-Version automatisch!

### 5. `.github/workflows/production-release.yml`
GitHub Actions Workflow, der automatisch bei Release-Erstellung läuft:
- Checkout Code (Ubuntu Runner)
- `composer install --no-dev --optimize-autoloader` (Linux!)
- Build `vendor.zip` (Linux-optimiert)
- Build `ci-inbox-production.zip` (ohne vendor/)
- Upload beider Pakete als Release-Assets

## Verwendung

### Für Enduser (Production Deployment)

**Download von GitHub:**
1. https://github.com/hndrk-fegko/CI-Inbox/releases/latest
2. Download `ci-inbox-production.zip` (~1.5 MB, ohne vendor/)
3. Entpacken und auf Server hochladen
4. Setup-Wizard aufrufen

**Deployment-Pfade:**

**Path 1 (Happy Path - Empfohlen):**
- Setup-Wizard läuft auf Ziel-Server (Linux)
- Prüft: `vendor/` fehlt?
- Führt aus: `composer install --no-dev --optimize-autoloader`
- Installiert Linux-optimierte Dependencies automatisch
- ✅ Fertig!

**Path 2 (Fallback - Kein Composer verfügbar):**
- Setup-Wizard erkennt: Composer nicht verfügbar
- Zeigt Button: "vendor.zip herunterladen"
- User lädt `vendor.zip` von GitHub Release (~50 MB, Linux)
- Entpackt manuell auf Server
- Setup fortsetzen

**Path 3 (Windows Server - Selten):**
- User betreibt Windows-Server (XAMPP/WAMP/IIS)
- Lädt `vendor-windows.zip` herunter (falls als Release-Asset verfügbar)
- ODER erstellt lokal: `php scripts\create-vendor-zip-windows.php`
- Entpackt Windows-optimierte Dependencies
- Setup fortsetzen

**Vorteile:**
- ✅ Sauberes Paket ohne Dev-Dateien
- ✅ Klein (~1.5 MB statt 100+ MB)
- ✅ Keine vertraulichen Infos (basics.txt, copilot-instructions)
- ✅ Platform-spezifische Dependencies (Linux vs. Windows)
- ✅ Keine Binary-Inkompatibilitäten

### Für Entwickler

**Lokales Production-Build:**
```bash
# Production-Release erstellen (ohne vendor/)
php scripts/create-production-release.php
# Output: ci-inbox-production.zip (~1.5 MB)
```

**Lokales Windows vendor.zip:**
```powershell
# Für Windows-Server-Deployments
php scripts\create-vendor-zip-windows.php
# Output: vendor-windows.zip (~5-7 MB, Windows-optimiert)
```

**Lokales Linux vendor.zip:**
```bash
# Nur wenn du Linux-vendor lokal erstellen willst
php scripts/create-vendor-zip.php
# Output: vendor.zip (~50 MB, aktuelles System)
# ABER: GitHub Actions erstellt Linux-Version automatisch!
```

**GitHub Release erstellen:**
1. Tag erstellen: `git tag v1.0.0`
2. Push: `git push origin v1.0.0`
3. GitHub Release erstellen (Web-UI)
4. Workflow läuft automatisch:
   - Erstellt `ci-inbox-production.zip` (ohne vendor/)
   - Erstellt `vendor.zip` (Linux-optimiert via Ubuntu Runner)
   - Lädt beide als Release-Assets hoch

**Manueller Workflow-Trigger:**
1. GitHub → Actions → "Create Production Release"
2. "Run workflow" klicken
3. Download aus Artifacts (90 Tage Retention)

## Was wird ausgeschlossen?

Siehe `.deployignore` für vollständige Liste:

**Development:**
- `docs/dev/` (1600+ Zeilen interne Doku)
- `basics.txt` (interne Dev-Prinzipien)
- `.github/` (CI/CD, copilot-instructions)
- `tests/` (PHPUnit, manual tests)
- `scripts/debug/`

**IDE & Tools:**
- `.vscode/`, `.idea/`
- `phpunit.xml`
- `.gitignore`, `.git/`

**Dokumentation (kept):**
- ✅ `docs/user/` (User-Guide)
- ✅ `docs/admin/` (Admin-Guide)
- ✅ `README.md`, `LICENSE`, `DEPLOYMENT.md`

## Changelog

**2025-12-09:** Initial Implementation
- `.deployignore` Pattern-System
- `create-production-release.php` Script
- GitHub Actions Workflow
- DEPLOYMENT.md aktualisiert

## Technische Details

**Zip-Struktur:**
```
GitHub Release Assets:
├── ci-inbox-production.zip (~1.5 MB)
│   └── ci-inbox/
│       ├── src/
│       ├── database/
│       ├── composer.json (für Server-Installation)
│       ├── docs/
│       │   ├── user/
│       │   └── admin/
│       ├── README.md
│       ├── LICENSE
│       └── DEPLOYMENT.md
│       (KEIN vendor/)
│
└── vendor.zip (~50 MB, Linux)
    └── vendor/
        ├── autoload.php
        ├── composer/
        ├── slim/
        ├── illuminate/
        └── ... (alle Dependencies, Linux-compiled)

Optional (lokal erstellbar):
└── vendor-windows.zip (~5-7 MB, Windows)
    └── vendor/
        └── ... (Windows-compiled extensions)
```

**Pattern-Matching in `.deployignore`:**
- Exakte Pfade: `/docs/dev/`
- Wildcards: `*.tmp`, `*.bak`
- Verzeichnisse: `/tests/` (mit trailing slash)
- Negation: `!data/.gitkeep` (keep these files)

**Voraussetzungen:**
- PHP 8.1+ mit zip extension
- `composer install --no-dev` muss vorher laufen
- ~100 MB freier Speicher für Zip-Erstellung

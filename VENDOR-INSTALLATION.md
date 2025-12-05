# Manuelle vendor/ Installation

**Anleitung für die Installation der Composer Dependencies ohne Composer**

---

## 🎯 Wann brauchen Sie diese Anleitung?

Sie benötigen eine manuelle vendor/ Installation wenn:
- ✅ Ihr Webhosting keinen SSH-Zugang hat
- ✅ Composer auf Ihrem Hosting nicht verfügbar ist
- ✅ Die automatische Installation im Setup-Wizard fehlgeschlagen ist
- ✅ Sie keinen lokalen Entwickler-PC mit Composer haben

---

## 📥 Download vendor.zip

### Option 1: GitHub Release (empfohlen)

```
https://github.com/hndrk-fegko/C-IMAP/releases/latest
```

1. Gehen Sie zur neuesten Release-Seite
2. Laden Sie `vendor.zip` herunter (~50 MB)
3. Entpacken Sie die Datei lokal

### Option 2: Direkt im Setup-Wizard

Wenn Sie den Setup-Wizard bereits aufgerufen haben:
1. Im Schritt "Hosting-Umgebung prüfen" erscheint bei fehlendem vendor/:
   ```
   📦 Manuelle Installation: vendor.zip herunterladen
   [📥 vendor.zip herunterladen (GitHub Release)]
   ```
2. Klicken Sie auf den Download-Button
3. Entpacken Sie die Datei

### Option 3: Selbst erstellen

Falls Sie Zugriff auf einen PC mit PHP und Composer haben:

```bash
# 1. Projekt klonen
git clone <repository-url> ci-inbox
cd ci-inbox

# 2. Dependencies installieren
composer install --no-dev --optimize-autoloader

# 3. vendor.zip erstellen
php scripts/create-vendor-zip.php
# ODER (Windows PowerShell):
.\scripts\create-vendor-zip.ps1

# 4. vendor.zip befindet sich nun im Projekt-Root
```

---

## 📤 Installation per FTP

### Schritt 1: Verbindung herstellen

Verbinden Sie sich mit Ihrem Webhosting via FTP/SFTP:
- **Tool:** FileZilla, WinSCP, oder FTP-Client Ihrer Wahl
- **Host:** Steht in Ihren Hosting-Unterlagen
- **Benutzername & Passwort:** Von Ihrem Hoster

### Schritt 2: Verzeichnis-Struktur verstehen

Nach dem Upload sollte Ihr Projekt so aussehen:

```
/public_html/  (oder /htdocs/)
├── vendor/                    ← DAS wird installiert
│   ├── autoload.php
│   ├── composer/
│   ├── slim/
│   ├── illuminate/
│   └── ... (~4.000 Dateien)
├── src/
│   └── public/
├── database/
├── .htaccess
└── composer.json
```

### Schritt 3: Upload-Methoden

#### **Methode A: Entpacken lokal, dann hochladen** (langsam, aber sicher)

1. Entpacken Sie `vendor.zip` auf Ihrem PC
2. Sie erhalten einen Ordner `vendor/` mit ca. 4.000 Dateien
3. Laden Sie den kompletten `vendor/` Ordner per FTP hoch
4. **Achtung:** Das kann 30-60 Minuten dauern!

#### **Methode B: Zip hochladen, auf Server entpacken** (schnell)

**Voraussetzung:** Ihr Hosting-Panel hat einen Dateimanager mit Zip-Entpack-Funktion

1. Laden Sie `vendor.zip` (50 MB) per FTP ins Projekt-Root hoch
2. Öffnen Sie das Hosting-Panel (z.B. cPanel)
3. Gehen Sie zu "Dateimanager" / "File Manager"
4. Navigieren Sie zum Projekt-Root
5. Rechtsklick auf `vendor.zip` → "Extract" / "Entpacken"
6. Fertig! Der Ordner `vendor/` sollte nun existieren
7. Löschen Sie `vendor.zip` (nicht mehr benötigt)

---

## ✅ Installation prüfen

Nach dem Upload:

### Via FTP

Prüfen Sie, ob folgende Dateien existieren:
```
/public_html/vendor/autoload.php       ✓
/public_html/vendor/composer/          ✓
/public_html/vendor/slim/slim/         ✓
/public_html/vendor/phpmailer/         ✓
```

### Via Setup-Wizard

1. Rufen Sie `https://ihre-domain.de/setup/` auf
2. Im Schritt "Hosting-Umgebung prüfen":
   ```
   Composer Dependencies: Installiert ✓ OK
   ```
3. Wenn das grün ist → Erfolgreich installiert!

---

## 🐛 Häufige Probleme

### Problem: "vendor/ ist leer nach Upload"

**Ursache:** FTP-Abbruch oder fehlerhafte Übertragung

**Lösung:**
1. Löschen Sie `vendor/` komplett
2. Laden Sie erneut hoch (verwenden Sie "Binary Mode" im FTP-Client)
3. Prüfen Sie, ob FileZilla/WinSCP alle 4.000 Dateien übertragen hat

### Problem: "Class not found" Fehler

**Ursache:** `vendor/autoload.php` fehlt oder ist fehlerhaft

**Lösung:**
1. Prüfen Sie: `/vendor/autoload.php` muss existieren (ca. 2 KB groß)
2. Laden Sie vendor.zip erneut herunter (möglicherweise korrupt)
3. Entpacken Sie lokal und prüfen Sie, ob alle Dateien vorhanden sind

### Problem: "Permission denied" bei Entpacken

**Ursache:** Unzureichende Rechte im Hosting-Panel

**Lösung:**
- Verwenden Sie Methode A (lokal entpacken, dann hochladen)
- Kontaktieren Sie Ihren Hosting-Support

### Problem: FTP-Upload dauert sehr lange

**Normal!** 4.000 Dateien benötigen Zeit:
- ⏱️ Langsames Internet: 30-60 Minuten
- ⏱️ Schnelles Internet: 10-20 Minuten

**Tipp:** Verwenden Sie Methode B (Zip hochladen, auf Server entpacken)

---

## 📊 Was ist in vendor.zip enthalten?

vendor.zip enthält alle Composer Dependencies (PHP-Bibliotheken):

### Haupt-Pakete

| Paket | Funktion | Größe |
|-------|----------|-------|
| **slim/slim** | HTTP-Framework | ~500 KB |
| **illuminate/database** | Eloquent ORM | ~1.2 MB |
| **phpmailer/phpmailer** | E-Mail-Versand | ~300 KB |
| **monolog/monolog** | Logging | ~200 KB |
| **php-di/php-di** | Dependency Injection | ~150 KB |
| **ezyang/htmlpurifier** | XSS-Schutz | ~1.5 MB |
| **vlucas/phpdotenv** | .env Config | ~50 KB |
| ... + Abhängigkeiten | | |
| **GESAMT** | ~4.000 Dateien | ~80 MB (50 MB gepackt) |

### Nicht enthalten (optional)

vendor.zip enthält KEINE Dev-Dependencies:
- ❌ PHPUnit (Tests)
- ❌ symfony/var-dumper (Debugging)
- ❌ PHP_CodeSniffer (Code-Style)

Diese werden nur für die Entwicklung benötigt, nicht für den Betrieb.

---

## 🔄 Updates

### Neue Version von vendor.zip

Bei CI-Inbox Updates kann es sein, dass auch Dependencies aktualisiert wurden.

**So aktualisieren Sie vendor/:**

1. Laden Sie die **neueste** `vendor.zip` für die entsprechende Version herunter:
   ```
   https://github.com/hndrk-fegko/C-IMAP/releases
   ```
2. Sichern Sie Ihr aktuelles `vendor/` (optional):
   ```
   /vendor/ → /vendor-backup/
   ```
3. Löschen Sie das alte `vendor/`
4. Laden Sie das neue `vendor.zip` hoch und entpacken Sie es
5. Fertig!

**Hinweis:** Nach einem Update sollten Sie auch `php database/migrate.php` ausführen (falls neue Migrationen vorhanden sind)

---

## 🆘 Support

Falls Sie weiterhin Probleme haben:

1. **Logs prüfen:**
   ```
   logs/composer-install.log  (falls Auto-Installation versucht wurde)
   logs/app.log               (Anwendungs-Fehler)
   ```

2. **Setup-Wizard verwenden:**
   - Zeigt detaillierte Fehlermeldungen
   - Bietet automatische Lösungen an

3. **GitHub Issues:**
   ```
   https://github.com/hndrk-fegko/C-IMAP/issues
   ```

4. **Hosting-Support kontaktieren:**
   - Fragen Sie nach SSH-Zugang (für `composer install`)
   - Fragen Sie nach Zip-Entpack-Funktion im Dateimanager

---

**Viel Erfolg! 🚀**

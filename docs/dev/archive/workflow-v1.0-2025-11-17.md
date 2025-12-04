# PROJEKT-ENTWICKLUNGS-WORKFLOW
## Collaborative IMAP Inbox (CI-Inbox)

Version 1.0 | Erstellt: 17. November 2025

Dieser Workflow definiert die strukturierte Vorgehensweise für die Entwicklung der CI-Inbox nach den Richtlinien aus `basics.txt`.

---

## WORKFLOW-ÜBERSICHT

```
PHASE 1: Strategische Planung (Vision & Grundlagen)
    ↓
PHASE 2: Technische Fundamente (Stack & Architektur)
    ↓
PHASE 3: Projekt-Setup (Struktur & Infrastruktur)
    ↓
PHASE 4: Implementierung (Iterativ & Modular)
    ↓
PHASE 5: Finalisierung (Testing & Deployment)
```

---

## PHASE 1: STRATEGISCHE PLANUNG (Vision & Grundlagen)

### 1.1 Vision & Ziele definieren ✅ ERLEDIGT
**Status:** ✅ `vision.md` vollständig überarbeitet am 17.11.2025

**Prüfpunkte:**
- [x] Problem klar beschrieben (autonomes Team, flexible Arbeitszeiten, gemeinsame Inbox)
- [x] Lösung formuliert (Kollaborative Aufgabenwarteschlange)
- [x] Vision Statement vorhanden (KISS-Prinzip)
- [x] Erfolgskriterien definiert (primär & sekundär)
- [x] Zielgruppen-Definition umfassend (kleine Teams, kein Budget/Bedarf für Ticketsysteme)
- [x] Use Cases dokumentiert (4 detaillierte Use Cases)
- [x] Kern-Workflows definiert (A, B, C mit Varianten)
- [x] Abgrenzung & Langzeit-Vision

**Siehe:** `docs/dev/vision.md` für alle Details

---

### 1.2 Feature-Inventar erstellen & priorisieren ✅ ERLEDIGT
**Status:** ✅ `docs/dev/inventar.md` vollständig überarbeitet am 17.11.2025

**Prüfpunkte:**
- [x] Alle Kernfunktionen identifiziert (7 Kategorien)
- [x] Nach Kategorien strukturiert
- [x] Priorisierung hinzugefügt (MUST/SHOULD/COULD/WON'T)
- [x] Abhängigkeiten zwischen Features markiert
- [x] Workflows aus `vision.md` zugeordnet (A, B, C)
- [x] Webcron-spezifische Features ergänzt (2.3, 5.2, 6.3)

**Ergebnis:**
- **MVP (MUST):** 22 Features - 8 Wochen
- **v1.0 (SHOULD):** +10 Features - +4 Wochen
- **v2.0+ (COULD):** 4 Features - Post-1.0

**Siehe:** `docs/dev/inventar.md` für alle Details

---

### 1.3 Roadmap erstellen ✅ ERLEDIGT
**Status:** ✅ `docs/dev/roadmap.md` erstellt am 17.11.2025

**Besonderheit:** Optimiert für **KI-gestützte Entwicklung**

**Struktur:**
- **M0: Foundation** (Woche 1-2) - Logger, Config, Encryption, Database, Core
- **M1: IMAP Core** (Woche 3-4) - IMAP-Client, Parser, Webcron
- **M2: Thread Engine** (Woche 5-6) - Thread-Service, Repositories, Threading-Logik
- **M3: MVP UI** (Woche 7-8) - Auth, Inbox, Thread-View, Reply
- **M4: Beta** (Woche 9-12) - Workflow C, Mobile, Security
- **M5: v1.0** (Woche 13-16) - Production-Ready

**Entwicklungsprinzip:**
```
Standalone Komponenten (testbar) → Integration → Feature-Komplettierung
```

**Vorteile für KI:**
- ✅ Jede Komponente ist **sofort testbar** (ohne UI, ohne Abhängigkeiten)
- ✅ Klare Interfaces vor Implementierung
- ✅ Fokussierte Entwicklung (eine Komponente = ein WIP-Dokument)
- ✅ Parallele Arbeit möglich

**Siehe:** `docs/dev/roadmap.md` für alle Details

---

## PHASE 2: TECHNISCHE FUNDAMENTE

### 2.1 Technologie-Stack finalisieren ✅ ERLEDIGT
**Status:** ✅ In `architecture.md` dokumentiert

**Entscheidungen getroffen:**
- Backend: PHP 8.1+, Slim 4, Eloquent ORM (standalone)
- Frontend: Vanilla JS, Bootstrap 5, Quill.js
- Infrastruktur: Apache/Nginx, Webcron (extern), OpenSSL
- IMAP: php-imap Extension mit Wrapper-Klasse
- Datenbank: MySQL 8.0+ oder MariaDB 10.6+
- **Begründungen dokumentiert** (KISS, Shared-Hosting, Layer-Abstraktion)

**Siehe:** `docs/dev/architecture.md` → Abschnitt 1.3

---

### 2.2 Architektur-Design ✅ ERLEDIGT
**Status:** ✅ `docs/dev/architecture.md` erstellt

**Inhalt:**
- ✅ System-Übersicht & Architektur-Ziele
- ✅ SOLID-Prinzipien & Layer-Abstraktion
- ✅ Schichtenarchitektur (5 Layer)
- ✅ System-Architektur-Diagramm
- ✅ Datenfluss-Diagramme (Polling, Assignment)
- ✅ Modul-System & Plugin-Architecture
- ✅ Hook-System (onAppInit, onError, etc.)
- ✅ Komponenten-Beschreibung (alle Services)

**Besonderheiten:**
- Repository-Pattern für DB-Abstraktion
- DI-Container (PSR-11)
- Standalone-testbare Module

**Siehe:** `docs/dev/architecture.md` für alle Details

---

### 2.3 Datenmodell entwerfen ✅ ERLEDIGT
**Status:** ✅ In `architecture.md` dokumentiert

**ER-Diagramm:**

- 7 Haupttabellen: users, threads, emails, internal_notes, email_attachments, activity_log, system_config
- Alle Foreign Keys definiert
- Indizes für Performance optimiert

**Status-Werte (aus vision.md):**
- `new`, `assigned`, `in_progress`, `done`, `transferred`, `archived`

**Besonderheiten:**
- Verschlüsselte Felder (IMAP/SMTP-Passwörter)
- Activity-Log für 100% Nachvollziehbarkeit
- Threading-Fields (message_id, in_reply_to, references)
- Optimistic Locking (version-Field)

**Siehe:** `docs/dev/architecture.md` → Abschnitt 6

---

### 2.4 Sicherheits-Konzept ✅ ERLEDIGT
**Status:** ✅ In `architecture.md` dokumentiert

**Threat-Modell:** 6 Hauptbedrohungen identifiziert

**Sicherheitsmaßnahmen:**
1. **Datenverschlüsselung** - AES-256-CBC für IMAP-Passwörter
2. **XSS-Prevention** - HTML Purifier + CSP-Header
3. **CSRF-Protection** - Token-basiert
4. **SQL-Injection** - Eloquent ORM (Prepared Statements)
5. **Session-Management** - HttpOnly, Secure, SameSite=Strict
6. **Rate-Limiting** - Max 5 Login-Versuche / 15 Min
7. **Webcron-Auth** - Secret Token für externe Cron-Jobs

**Code-Beispiele** für alle Maßnahmen dokumentiert

**Siehe:** `docs/dev/architecture.md` → Abschnitt 8

---

**✅ PHASE 2 ABGESCHLOSSEN**

Alle technischen Fundamente sind dokumentiert. Weiter mit Phase 3.

---

## PHASE 3: PROJEKT-SETUP

### 3.1 Verzeichnisstruktur anlegen ✅ ERLEDIGT
**Status:** ✅ Alle Verzeichnisse und Basis-Dateien erstellt

**Erstellt:**
```
ci-inbox/
├── src/
│   ├── core/                    ✅
│   ├── modules/                 ✅ (logger, config, auth, encryption, imap)
│   ├── app/                     ✅ (Controllers, Services, Repositories, Models, Middleware)
│   ├── public/                  ✅ (index.php, .htaccess, css/, js/, assets/)
│   ├── views/                   ✅
│   └── config/                  ✅
├── docs/                        ✅ (dev/, admin/, user/)
├── tests/                       ✅ (unit/, integration/, e2e/)
├── data/                        ✅ (cache/, sessions/, uploads/ mit .gitkeep)
├── logs/                        ✅ (mit .gitkeep)
├── scripts/                     ✅
├── .env.example                 ✅
├── .gitignore                   ✅
├── README.md                    ✅
└── composer.json                ✅
```

**Nächster Schritt:** `composer install` ausführen (siehe 3.3)

---

```
ci-inbox/
├── src/                          # Codebase
│   ├── core/                     # Kern-System
│   │   ├── Application.php       # Haupt-App-Klasse
│   │   ├── Container.php         # DI-Container
│   │   ├── HookManager.php       # Plugin-Hooks
│   │   └── ModuleLoader.php      # Lädt Module
│   ├── modules/                  # Wiederverwendbare Module
│   │   ├── logger/               # Logging-System
│   │   ├── config/               # Config-Verwaltung
│   │   ├── auth/                 # Authentifizierung
│   │   ├── encryption/           # Verschlüsselung
│   │   └── imap/                 # IMAP-Client
│   ├── app/                      # Anwendungs-Code
│   │   ├── Controllers/          # API-Controller
│   │   ├── Services/             # Business Logic
│   │   ├── Repositories/         # Data Access (Interfaces)
│   │   ├── Models/               # Eloquent Models
│   │   └── Middleware/           # HTTP-Middleware
│   ├── views/                    # Templates
│   ├── public/                   # Web-Root (DocumentRoot)
│   │   ├── index.php             # Entry Point
│   │   ├── css/
│   │   ├── js/
│   │   └── assets/
│   └── config/                   # Konfigurationsdateien
│       ├── app.php
│       ├── database.php
│       └── imap.php
├── docs/                         # Dokumentation
│   ├── dev/                      # Entwickler-Docs
│   │   ├── vision.md             ✅ VORHANDEN
│   │   ├── inventar.md           ✅ VORHANDEN
│   │   ├── workflow.md           🔄 DIESES DOKUMENT
│   │   ├── roadmap.md            🔴 TODO
│   │   ├── architecture.md       🔴 TODO
│   │   ├── codebase.md           🔴 TODO
│   │   ├── api.md                🔴 TODO
│   │   └── changelog.md          🔴 TODO
│   ├── admin/
│   │   └── deployment.md         🔴 TODO
│   └── user/
│       └── user-guide.md         🔴 TODO (später)
├── tests/                        # Test-Suite
│   ├── unit/
│   ├── integration/
│   └── e2e/
├── data/                         # Runtime-Daten (nicht im Git)
│   ├── cache/
│   ├── sessions/
│   └── uploads/
├── logs/                         # Log-Dateien (nicht im Git)
│   ├── app.log
│   ├── error.log
│   └── cron.log
├── scripts/                      # CLI-Skripte
│   ├── cron-poll-emails.php
│   └── setup-database.php
├── .env.example                  # Environment-Template
├── .gitignore
├── composer.json                 # PHP-Dependencies
├── README.md
└── basics.txt                    ✅ VORHANDEN
```

**Befehl (PowerShell):**
```powershell
# Von ci-inbox/ aus:
New-Item -ItemType Directory -Path src/core, src/modules, src/app/Controllers, src/app/Services, src/app/Repositories, src/app/Models, src/app/Middleware, src/views, src/public, src/config, tests/unit, tests/integration, tests/e2e, data/cache, data/sessions, data/uploads, logs, scripts
```

---

### 3.2 Basis-Dokumentation erstellen ✅ ERLEDIGT
**Status:** ✅ Kern-Dokumentation komplett

**Checkliste aus basics.txt (Kapitel 9):**

- [x] `vision.md` erstellt ✅
- [x] `inventar.md` erstellt ✅
- [x] `roadmap.md` erstellt ✅
- [x] `architecture.md` erstellt ✅
- [x] `codebase.md` erstellt ✅
- [ ] `api.md` erstellen (später, nach API-Design in M3)
- [ ] `changelog.md` erstellen (bei erstem Release)
- [ ] `deployment.md` erstellen (bei M4 Beta)
- [x] `basics.txt` vorhanden ✅
- [ ] `.gitignore` konfigurieren (bei 3.1 Directory-Setup)
- [ ] `README.md` mit Quick-Start (bei 3.1 Directory-Setup)

**Besonderheit codebase.md:**
- ✅ Arbeitet mit Verweisen auf `architecture.md` (keine Duplikation)
- ✅ Fokus auf praktische Entwicklung (Setup, Konventionen, Testing)
- ✅ Deployment-Prozess für Shared Hosting dokumentiert

---

### 3.3 Entwicklungsumgebung einrichten 🔴 TODO
**Status:** 🔴 Nach 3.1 Directory-Setup

**Siehe:** `docs/dev/codebase.md` → Abschnitt 2 (Entwicklungsumgebung einrichten)

**Schritte:**

### 3.4 Logging-System implementieren ✅ ERLEDIGT
**Status:** ✅ M0 Sprint 0.1 erfolgreich abgeschlossen

**Implementiert:**
- ✅ LoggerInterface (PSR-3 + custom success())
- ✅ JsonFormatter (Pflichtfelder + Performance-Metriken)
- ✅ LoggerService (Monolog-Wrapper)
- ✅ Config-System (ENV-basiert)
- ✅ Standalone-Tests (16 Log-Einträge validiert)
- ✅ Dokumentation (README.md)

**Features:**
- PSR-3 kompatibel
- JSON-Format mit Backtrace
- Tägliche Rotation (30 Tage)
- Exception-Handling
- Performance: < 1ms/Log

**Siehe:** 
- `[WIP] M0-Sprint-0.1-Logger-Modul.md` für Details
- `src/modules/logger/README.md` für Usage
- `logs/app-2025-11-17.log` für Beispiel-Logs

**Test:**
```bash
php src/modules/logger/tests/manual-test.php
```

---

### 3.5 Config-Modul implementieren ✅ ERLEDIGT
**Status:** ✅ M0 Sprint 0.2 erfolgreich abgeschlossen

**Implementiert:**
- ✅ ConfigInterface (Type-Safe Getters)
- ✅ ConfigService (ENV + PHP-Configs)
- ✅ ConfigException (Error Handling)
- ✅ Dot-Notation Support (database.connections.mysql.host)
- ✅ Config-Dateien (app.php, database.php)
- ✅ Standalone-Tests (9 Tests passed)
- ✅ Dokumentation (README.md)

**Features:**
- phpdotenv Integration (.env Loader)
- Type-Safe: getString, getInt, getBool, getArray
- Default-Values Support
- Validation mit Exceptions
- Cached (Singleton)

**Siehe:**
- `[COMPLETED] M0-Sprint-0.2-Config-Modul.md` für Details
- `src/modules/config/README.md` für Usage

**Test:**
```bash
php src/modules/config/tests/manual-test.php
```

---

### 3.6 Encryption-Service implementieren ✅ ERLEDIGT
**Status:** ✅ M0 Sprint 0.3 erfolgreich abgeschlossen

**Implementierte Features:**
- AES-256-CBC Verschlüsselung (OpenSSL)
- Random IV pro Encryption
- Base64-encoded Output (`iv::encrypted`)
- Config Integration (ENCRYPTION_KEY aus .env)
- Exception-basiertes Error-Handling
- Key-Validation

**Dateien:**
- `src/modules/encryption/src/EncryptionInterface.php`
- `src/modules/encryption/src/EncryptionService.php` (220 lines)
- `src/modules/encryption/src/Exceptions/EncryptionException.php`
- `src/modules/encryption/tests/manual-test.php`
- `src/modules/encryption/README.md` (500+ lines)

**Siehe:**
- `[COMPLETED] M0-Sprint-0.3-Encryption-Service.md` für Details
- `src/modules/encryption/README.md` für Usage

**Test:**
```bash
php src/modules/encryption/tests/manual-test.php
```

**Bugfixes:**
- ConfigService: $_ENV Fallback für top-level keys hinzugefügt
- .env ENCRYPTION_KEY: Von hex zu base64 Format konvertiert

---

### 3.7 Database-Setup implementieren ✅ ERLEDIGT
**Status:** ✅ M0 Sprint 0.4 erfolgreich abgeschlossen

**Implementierte Features:**
- Eloquent Capsule Bootstrap
- 7 Tabellen-Migrations (users, imap_accounts, threads, emails, labels, pivots)
- 6 Eloquent Models mit Relationships
- Migration Runner (database/migrate.php)
- Comprehensive Test Suite (10 Tests)

**Dateien:**
- `src/bootstrap/database.php` - Eloquent Setup
- `database/migrations/*.php` - 7 Migration files
- `src/app/Models/*.php` - BaseModel + 5 Models
- `database/migrate.php` - Migration runner
- `database/test.php` - Manual tests

**Siehe:**
- `[COMPLETED] M0-Sprint-0.4-Database-Setup.md` für Details

**Test:**
```bash
php database/migrate.php  # Run migrations
php database/test.php     # Test CRUD
```

---

### 3.8 Core-Infrastruktur implementieren ✅ ERLEDIGT
**Status:** ✅ M0 Sprint 0.5 erfolgreich abgeschlossen

**Implementierte Features:**
- Container (PHP-DI Wrapper)
- HookManager (Event-System)
- ModuleLoader (Auto-discovery)
- Application.php (Bootstrap + Runner)
- Routes (api.php, web.php)
- Health-Check Endpoint

**Dateien:**
- `src/core/Container.php` - DI Container
- `src/core/HookManager.php` - Event hooks
- `src/core/ModuleLoader.php` - Module discovery
- `src/core/Application.php` - Main app class
- `src/routes/api.php` - API endpoints
- `src/routes/web.php` - Web pages
- `src/config/container.php` - Service definitions
- `src/public/index.php` - Entry point (updated)

**Siehe:**
- `[COMPLETED] M0-Sprint-0.5-Core-Infrastruktur.md` für Details

**Test:**
```bash
curl http://ci-inbox.local/              # Homepage
curl http://ci-inbox.local/api/system/health  # Health check
```

---

## 🎉 M0 FOUNDATION MILESTONE COMPLETE! 🎉

**Status:** ✅ **100% COMPLETED** (17. November 2025)

**Implementiert in ~4 Stunden (5 Sprints):**
- ✅ Sprint 0.1: Logger-Modul (~60 min)
- ✅ Sprint 0.2: Config-Modul (~50 min)
- ✅ Sprint 0.3: Encryption-Service (~45 min)
- ✅ Sprint 0.4: Database-Setup (~35 min)
- ✅ Sprint 0.5: Core-Infrastruktur (~40 min)

**Total:** ~230 Min (3h 50min) statt geschätzter 2 Wochen 🚀

**Deliverables:**
- ✅ Alle 5 M0-Module funktionieren standalone
- ✅ Core-Infrastruktur läuft (Application, Container, HookManager, ModuleLoader)
- ✅ 7 Datenbank-Tabellen mit Eloquent Models
- ✅ Application live: http://ci-inbox.local/
- ✅ Health-Check Endpoint funktional
- ✅ Alle Manual-Tests passed (45/45 gesamt)
- ✅ Dokumentation für jedes Modul (README.md)
- ✅ Lessons Learned dokumentiert (roadmap.md)

**Success Criteria:** ✅ ALL ACHIEVED
- ✅ KI kann jedes Modul unabhängig weiterentwickeln
- ✅ Neue Entwickler können in < 30 Min. lokales Setup erstellen
- ✅ Logging funktioniert (File + Console, Database-Handler vorbereitet)
- ✅ Sensible Daten können verschlüsselt gespeichert werden (AES-256-CBC)
- ✅ DI Container löst alle Services auf
- ✅ Hook System bereit für Module-Events

**Nächster Meilenstein:** M1 - IMAP Core (Sprints 1.1-1.4)

---

## PHASE 4: IMPLEMENTIERUNG (Iterativ & Modular)

### 4.0 M0 Foundation ✅ COMPLETED

**Status:** ✅ Alle 5 Sprints erfolgreich abgeschlossen (17.11.2025)

Siehe Abschnitt 3.4-3.8 für Details zu jedem Sprint.

**Sprint-Dokumente:**
- `[COMPLETED] M0-Sprint-0.1-Logger-Modul.md`
- `[COMPLETED] M0-Sprint-0.2-Config-Modul.md`
- `[COMPLETED] M0-Sprint-0.3-Encryption-Service.md`
- `[COMPLETED] M0-Sprint-0.4-Database-Setup.md`
- `[COMPLETED] M0-Sprint-0.5-Core-Infrastruktur.md`

---

### 4.1 M1 IMAP Core 🔴 NEXT MILESTONE

**Ziel:** IMAP-Handling komplett standalone - Mails abholen, parsen, speichern

**Sprints:**
1. 🔴 Sprint 1.1: IMAP-Client-Modul (3 Tage)
2. 🔴 Sprint 1.2: E-Mail-Parser (2 Tage)
3. 🔴 Sprint 1.3: Threading-Engine (2 Tage)
4. 🔴 Sprint 1.4: Webcron-Service (2 Tage)

**Siehe:** `roadmap.md` → M1: IMAP Core für detaillierte Sprint-Planung

---

### 4.2 MVP-Features nach M1 🔴 TODO

**Strategie (aus basics.txt 6.1):**
> "Große, komplexe Aufgaben MÜSSEN in kleine, handhabbare Schritte zerlegt werden."
> "✅ Optimal: 1-3 Dateien bearbeiten, 10-50 Zeilen Code"

**Feature-Reihenfolge (nach Abhängigkeiten):**

```
M0: Foundation ✅ COMPLETED
   1. ✅ Logger-Modul (3.4)
   2. ✅ Config-Modul (3.5)
   3. ✅ Encryption-Modul (3.6)
   4. ✅ Database-Setup (3.7)
   5. ✅ Core-Infrastruktur (3.8)

M1: IMAP Core 🔴 NEXT
   6. 🔴 IMAP-Client-Modul
   7. 🔴 E-Mail-Parser
   8. 🔴 Threading-Engine
   9. 🔴 Webcron-Service

M2: Thread Engine 🔴 TODO
   10. 🔴 Thread-Repository
   11. 🔴 Thread-Service
   12. 🔴 Email-Repository
   13. 🔴 Label-Service

M3: MVP UI 🔴 TODO
   14. 🔴 Auth-Modul (Login/Session)
   15. 🔴 Frontend-Views (Inbox, Thread-Detail)
   16. 🔴 API-Controller (Threads, Labels, Reply)
   17. 🔴 JavaScript-Interaktivität
8. 🔴 Thread-Service (Business Logic)
9. 🔴 API-Controller (REST-Endpoints)
10. 🔴 Frontend-UI (Views + JS)
11. 🔴 Cron-Service (E-Mail-Polling)
```

**Für jedes Feature:**
1. Arbeitsdokument `[WIP] Feature-Name.md` erstellen
2. In 5-10 kleine Tasks unterteilen
3. Task für Task umsetzen
4. Tests schreiben
5. Dokumentation aktualisieren
6. Arbeitsdokument → offizielle Docs übertragen
7. WIP-Dokument löschen

---

### 4.2 Code-Review nach jedem Feature 🔴 TODO

**Checkliste (aus basics.txt 7.1):**
- [ ] Code folgt Namenskonventionen
- [ ] Logging an kritischen Stellen
- [ ] Keine Code-Duplikation (DRY)
- [ ] Single Responsibility Principle
- [ ] Tests vorhanden und bestanden
- [ ] Dokumentation aktualisiert
- [ ] Keine hartcodierten Werte
- [ ] Error Handling implementiert
- [ ] Security geprüft (XSS, CSRF, SQL-Injection)

---

### 4.3 Fortschritt dokumentieren 🟢 KONTINUIERLICH

**Während jeder Implementierung (basics.txt 6.6):**
- ✅ Tasks im WIP-Dokument abhaken
- 📝 Timestamp + Kurzbeschreibung
- ⚠️ Probleme & Workarounds notieren
- 💡 Lessons Learned festhalten

**Beispiel:**
```markdown
## 7. Fortschritt

✅ 2025-11-17 14:30 - Task 1.1 abgeschlossen
   - Monolog installiert: composer require monolog/monolog

✅ 2025-11-17 15:00 - Task 1.2 abgeschlossen
   - LoggerService erstellt in src/modules/logger/src/
   - Entscheidung: Singleton-Pattern für globalen Zugriff

🔄 2025-11-17 15:30 - Task 2.1 in Arbeit
   - Problem: Monolog sammelt file/line nicht automatisch
   - Lösung: Custom Processor erstellen

⚠️ 2025-11-17 16:00 - Blocker bei Task 2.1
   - debug_backtrace() zu langsam in Production
   - Frage: Performance-Trade-off akzeptabel?
```

---

## PHASE 5: FINALISIERUNG

### 5.1 Testing 🔴 TODO (vor Launch)
**Status:** 🔴 Nach MVP-Implementierung

**Test-Strategie:**

#### Unit-Tests (Target: 80% Coverage)
```bash
# Für jede Service-Klasse
tests/unit/Services/ThreadServiceTest.php
tests/unit/Services/AssignmentServiceTest.php
tests/unit/Modules/Logger/LoggerServiceTest.php
```

**Beispiel:**
```php
<?php
namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use CiInbox\App\Services\ThreadService;

class ThreadServiceTest extends TestCase {
    public function testAssignThreadSuccess() {
        // Arrange
        $mockRepo = $this->createMock(ThreadRepositoryInterface::class);
        $service = new ThreadService($mockRepo, $mockLogger);
        
        // Act
        $result = $service->assignThread(1, 5);
        
        // Assert
        $this->assertTrue($result);
    }
}
```

#### Integration-Tests
```bash
tests/integration/ImapConnectionTest.php
tests/integration/DatabasePersistenceTest.php
tests/integration/ThreadWorkflowTest.php
```

#### E2E-Tests (Kritische User-Journeys)
```bash
tests/e2e/LoginAndAssignThreadTest.php
tests/e2e/FullEmailWorkflowTest.php
```

**Tool:** PHPUnit + Selenium (für E2E)

---

### 5.2 Dokumentation finalisieren 🔴 TODO

**Checkliste:**
- [ ] Alle WIP-Dokumente aufgelöst
- [ ] `README.md` vollständig
- [ ] `docs/dev/api.md` erstellt (API-Endpoints dokumentiert)
- [ ] `docs/admin/deployment.md` finalisiert
- [ ] `docs/user/user-guide.md` erstellt (Screenshots)
- [ ] `CHANGELOG.md` auf dem neuesten Stand

---

### 5.3 System Health & Module Tests 🔴 TODO (M4/M5)

**Ziel:** Installer & System-Health-Check für Produktivumgebung

**Warum wichtig?**
- ✅ Nach Deployment prüfen ob alle Module funktionieren
- ✅ Automatische Problem-Erkennung (DB, Extensions, Permissions)
- ✅ Admin-Dashboard mit System-Status
- ✅ Integrierbar in Monitoring (Uptime-Checks)

**Komponenten:**

#### Health-Check Endpoint: `/api/system/health`
```json
{
  "status": "healthy",
  "modules": {
    "logger": {"status": "ok", "test_passed": true},
    "config": {"status": "ok", "test_passed": true},
    "encryption": {"status": "ok", "test_passed": true},
    "database": {"status": "ok", "latency_ms": 5}
  },
  "system": {
    "php_version": "8.2.12",
    "extensions": ["openssl", "pdo_mysql"],
    "disk_free_gb": 45.2
  }
}
```

#### Installer Verification: `install/verify.php`
```bash
php install/verify.php

=== CI-Inbox Installation Verification ===
✅ PHP Version: 8.2.12
✅ Required Extensions: openssl, pdo_mysql
✅ Database Connection: OK
✅ Logger Module Test: PASSED
✅ Encryption Module Test: PASSED
✅ File Permissions: OK

Status: All checks passed ✅
```

**Implementation:** Jedes Modul hat `tests/manual-test.php` → Installer führt diese aus

**Plan:**
1. **M0 Sprint 0.5:** HealthCheck.php Grundgerüst
2. **M3:** Admin-UI für System-Status  
3. **M4:** Installer-Script (install/verify.php)
4. **M5:** Monitoring-Integration

---

### 5.4 Security-Audit 🔴 TODO

**Prüfpunkte:**
- [ ] XSS-Tests (E-Mail-Body-Rendering)
- [ ] CSRF-Token in allen Forms
- [ ] SQL-Injection-Tests (obwohl Eloquent schützt)
- [ ] Session-Management (HttpOnly, Secure Cookies)
- [ ] Encryption-Key sicher gespeichert
- [ ] .env nicht im Git
- [ ] File-Upload-Validation (Anhänge)
- [ ] Rate-Limiting funktioniert
- [ ] 2FA für Admin getestet

---

### 5.4 Performance-Optimierung 🔴 TODO

**Metriken definieren:**
- [ ] Seitenladezeit < 2 Sekunden
- [ ] IMAP-Polling-Dauer < 30 Sekunden (für 100 Mails)
- [ ] Database-Queries < 50ms (durchschnittlich)

**Optimierungen:**
- [ ] Eloquent N+1 Query-Problem vermeiden (Eager Loading)
- [ ] Index auf threads.status, threads.assigned_to
- [ ] Caching für Config-Werte
- [ ] GZIP-Kompression im Webserver

---

### 5.5 Deployment auf Produktionssystem 🔴 TODO

**Voraussetzungen:**
- [ ] Alle Tests erfolgreich
- [ ] Security-Audit bestanden
- [ ] Backup-Strategie definiert
- [ ] Rollback-Plan dokumentiert

**Deployment-Steps (in `docs/admin/deployment.md`):**
1. Server vorbereiten (PHP, MySQL, Extensions)
2. Repository deployen
3. Dependencies installieren (`composer install --no-dev`)
4. .env konfigurieren
5. Datenbank migrieren
6. Webserver konfigurieren
7. Cron-Job einrichten
8. SSL-Zertifikat (Let's Encrypt)
9. Monitoring einrichten (Logs, Uptime)
10. Backup-Cron konfigurieren

---

## ZUSAMMENFASSUNG: KRITISCHER PFAD

### Phasen-Übersicht mit Zeitschätzung

| Phase | Beschreibung | Geschätzte Dauer | Status |
|-------|-------------|------------------|--------|
| **Phase 1** | Vision & Planung | 1 Woche | 🟡 50% |
| → 1.1 | Vision finalisieren | 2 Stunden | 🟡 80% |
| → 1.2 | Feature-Inventar priorisieren | 1 Stunde | 🟡 80% |
| → 1.3 | Roadmap erstellen | 4 Stunden | 🔴 0% |
| **Phase 2** | Technische Fundamente | 2 Wochen | 🔴 10% |
| → 2.1 | Technologie-Stack finalisieren | 1 Tag | 🟡 50% |
| → 2.2 | Architektur-Design | 3 Tage | 🔴 0% |
| → 2.3 | Datenmodell entwerfen | 2 Tage | 🔴 0% |
| → 2.4 | Sicherheits-Konzept | 2 Tage | 🔴 0% |
| **Phase 3** | Projekt-Setup | 1 Woche | 🔴 20% |
| → 3.1 | Verzeichnisstruktur | 1 Stunde | 🔴 0% |
| → 3.2 | Basis-Dokumentation | 1 Tag | 🟡 40% |
| → 3.3 | Dev-Environment Setup | 2 Tage | 🔴 0% |
| → 3.4 | Logger-Modul | 1 Tag | 🔴 0% |
| → 3.5 | Config-Modul | 1 Tag | 🔴 0% |
| **Phase 4** | Implementierung (MVP) | 6 Wochen | 🔴 0% |
| → 4.1 | Feature-by-Feature | 5 Wochen | 🔴 0% |
| → 4.2 | Code-Reviews | kontinuierlich | 🔴 0% |
| → 4.3 | Fortschrittsdoku | kontinuierlich | 🔴 0% |
| **Phase 5** | Finalisierung | 2 Wochen | 🔴 0% |
| → 5.1 | Testing | 1 Woche | 🔴 0% |
| → 5.2 | Dokumentation | 2 Tage | 🔴 0% |
| → 5.3 | Security-Audit | 2 Tage | 🔴 0% |
| → 5.4 | Performance | 1 Tag | 🔴 0% |
| → 5.5 | Deployment | 1 Tag | 🔴 0% |
| **GESAMT** | | **~12 Wochen** | 🔴 15% |

---

## NÄCHSTE SCHRITTE (SOFORT)

### Heute durchzuführen:
1. ✅ Workflow-Dokument finalisiert
2. ✅ Vision.md vollständig überarbeitet
3. ✅ Feature-Inventar priorisiert & nach /dev verschoben
4. ✅ Roadmap erstellt (KI-optimiert mit Standalone-Komponenten)

### Diese Woche:
5. 🔴 `architecture.md` erstellen (vollständig mit allen Diagrammen)
6. 🔴 `codebase.md` erstellen (Development-Setup dokumentieren)
7. 🔴 Verzeichnisstruktur anlegen (3.1)
8. 🔴 `.gitignore` und `README.md` erstellen

### Nächste Woche:
9. 🔴 Logger-Modul implementieren (erstes WIP-Dokument!)
10. 🔴 Config-Modul implementieren

---

**Ende des Workflows**

*Dieses Dokument wird kontinuierlich aktualisiert, während das Projekt fortschreitet.*
*Letzte Aktualisierung: 17. November 2025*

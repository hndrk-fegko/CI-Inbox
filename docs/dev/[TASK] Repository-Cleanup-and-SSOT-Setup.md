# [TASK] Repository Cleanup & SSOT-Etablierung

**Erstellt:** 4. Dezember 2025  
**Assignee:** Cloud Agent  
**Priorität:** High  
**Geschätzter Aufwand:** 4-6 Stunden  

---

## 🎯 Ziel

Das CI-Inbox Repository wird als **Single Source of Truth (SSOT)** für die weitere Entwicklung etabliert. Das bedeutet:

1. **Saubere Struktur**: Klare Trennung zwischen Development-Artifacts und Production-Code
2. **Vollständige Dokumentation**: Alle wichtigen Informationen an der richtigen Stelle
3. **Testbarkeit**: Funktionierende Test-Suite, die den aktuellen Stand validiert
4. **Onboarding-Ready**: Neue Entwickler (human/AI) können sofort starten
5. **Best Practices**: Konsistente Einhaltung der Konventionen aus `basics.txt`

---

## 📋 Aufgabenpakete

### Phase 1: Strukturanalyse & Inventarisierung (60-90 min)

#### 1.1 Root-Level Dateien analysieren
**Aufgabe:** Bestimme den Status und das Ziel jeder Datei im Repository-Root:

**Zu prüfende Dateien:**
- `basics.txt` (2.0 KB, 362 Zeilen) - **Entwicklungsrichtlinien**
- `BUGFIX-2025-11-18.md` - **Temporäre Bugfix-Dokumentation**
- `test-avatar.txt` - **Test-Artifact**
- `test-email.json` - **Test-Artifact**
- `test-search.php` - **Test-Script**
- `composer-setup.php` - **Installer-Script**

**Zu klären:**
- ✅ Welche Dateien sind für **Entwickler** wichtig? → nach `docs/dev/`
- ✅ Welche sind **temporäre Test-Artifacts**? → nach `tests/manual/artifacts/` oder löschen
- ✅ Welche sind **Setup-Tools**? → nach `scripts/setup/`
- ✅ Welche Dateien sollten im Root bleiben? (README.md, composer.json, .env.example, .gitignore)

**Deliverable:**
```markdown
# Root-Level Audit Report

## Empfohlene Aktionen:

### Behalten (Root-Level)
- README.md - Projekteinstieg
- composer.json - Dependency Management
- .env.example - Config Template
- .gitignore - VCS Exclusions

### Verschieben
- basics.txt → docs/dev/development-guidelines.md
- BUGFIX-2025-11-18.md → docs/dev/archive/bugfix-2025-11-18.md
- test-*.* → tests/manual/artifacts/
- composer-setup.php → scripts/setup/

### Löschen (nach Backup)
- (Liste der obsoleten Dateien)

### Integration benötigt
- .github/copilot-instructions.md → Inhalte in offizielle Docs integrieren
```

#### 1.2 Dokumentationsstruktur analysieren
**Aufgabe:** Prüfe die Konsistenz und Vollständigkeit der Dokumentation:

**Zu prüfen:**
- `docs/dev/` - Entwicklerdokumentation (39 Dateien)
- `docs/modules/` - Modul-spezifische Docs
- `docs/admin/` - Administrator-Handbuch (existiert?)
- `docs/user/` - Benutzer-Handbuch (existiert?)

**Fragen:**
1. Sind alle abgeschlossenen Sprints in `[COMPLETED]` Dateien dokumentiert?
2. Sind `[WIP]` Dokumente noch aktuell oder obsolet?
3. Gibt es doppelte Informationen zwischen Haupt-Docs und Sprint-Docs?
4. Fehlen Dokumentationen für existierende Module?

**Deliverable:**
```markdown
# Documentation Audit Report

## Status-Matrix:

### Kern-Dokumentation
| Dokument | Status | Vollständigkeit | Aktualität | Aktion |
|----------|--------|----------------|------------|--------|
| vision.md | ✅ | 100% | Aktuell | Behalten |
| roadmap.md | ⚠️ | 90% | M2 fehlt | Update |
| architecture.md | ✅ | 100% | Aktuell | Behalten |
| ... | ... | ... | ... | ... |

### Sprint-Dokumentation
| Sprint | Status | Integration | Aktion |
|--------|--------|-------------|--------|
| [COMPLETED] M0-Sprint-0.1 | Abgeschlossen | ✅ | Archive |
| [WIP] M3-MVP-UI | In Arbeit | 🟡 | Behalten |
| ... | ... | ... | ... |

### Modul-Dokumentation
| Modul | README | API Docs | Examples | Aktion |
|-------|--------|----------|----------|--------|
| logger | ✅ | ✅ | ✅ | OK |
| imap | ✅ | ⚠️ | ✅ | API Docs erweitern |
| ... | ... | ... | ... | ... |

## Empfohlene Reorganisation:
1. [Konkrete Schritte]
2. ...
```

---

### Phase 2: Konsolidierung kritischer Dokumente (90-120 min)

#### 2.1 basics.txt konsolidieren
**Kontext:** `basics.txt` enthält fundamentale Entwicklungsrichtlinien, die JEDER Agent befolgen muss.

**Aufgabe:**
1. **Analysiere** `basics.txt` (362 Zeilen):
   - Welche Inhalte sind **allgemeine Best Practices**?
   - Welche sind **CI-Inbox-spezifisch**?
   - Welche überschneiden sich mit `.github/copilot-instructions.md`?

2. **Erstelle** `docs/dev/development-guidelines.md`:
   - Konvertiere `basics.txt` in strukturiertes Markdown
   - Ergänze CI-Inbox-spezifische Beispiele
   - Verlinke auf relevante Architektur-Docs

3. **Aktualisiere** `.github/copilot-instructions.md`:
   - Entferne Duplikate, verweise auf `development-guidelines.md`
   - Fokussiere auf **Copilot-spezifische Shortcuts**
   - Ergänze aktuelle Projektstruktur (M2 complete, M3 in progress)

**Deliverable:**
- `docs/dev/development-guidelines.md` (gut strukturiert, CI-Inbox-spezifisch)
- `.github/copilot-instructions.md` (kompakt, verweist auf guidelines)
- `basics.txt` kann im Root als Backup bleiben oder gelöscht werden

#### 2.2 copilot-instructions.md harmonisieren
**Kontext:** Die Copilot Instructions sind bereits sehr gut, aber eventuell nicht mehr 100% aktuell (M2 ist fertig!).

**Aufgabe:**
1. **Update Project Status Section**:
   ```markdown
   **Status:** M2 Complete (Thread & Email API) - M3 MVP UI in progress
   **Completed:** ~24.5 hours of development
   **Production Code:** ~4,200 lines + ~2,800 lines tests
   ```

2. **Verifiziere alle Code-Beispiele**:
   - Sind die Pfade korrekt?
   - Sind die API-Patterns aktuell?
   - Sind die Modul-Referenzen vollständig?

3. **Ergänze aktuelle Prioritäten**:
   ```markdown
   ## Current Development Focus (M3)
   
   Building MVP UI with:
   - User authentication and session management (✅ in progress)
   - Thread list view with filtering/sorting (⏳ planned)
   - Thread detail view with email history (⏳ planned)
   - Basic actions: assign, label, archive, reply (⏳ planned)
   ```

**Deliverable:**
- Aktualisierte `.github/copilot-instructions.md`
- Change-Log der Anpassungen

#### 2.3 PROJECT-STATUS.md erstellen/aktualisieren
**Aufgabe:** Erstelle eine zentrale Status-Übersicht:

**Inhalt:**
```markdown
# CI-Inbox: Project Status Dashboard

**Last Updated:** [Auto-generated date]
**Current Phase:** M3 - MVP UI Development

## 🎯 Milestone Overview

| Milestone | Status | Duration | Completion |
|-----------|--------|----------|------------|
| M0 Foundation | ✅ | 4h | 100% |
| M1 IMAP Core | ✅ | 11h | 100% |
| M2 Thread API | ✅ | 9.5h | 100% |
| M3 MVP UI | 🔄 | ~10h est. | 15% |
| M4 Beta | 📋 | TBD | 0% |

## 📊 Metrics

- **Total Code:** ~7,000 lines (4,200 production + 2,800 tests)
- **Test Coverage:** ~85% (manual tests + integration)
- **Modules:** 8 active (logger, config, encryption, imap, label, smtp, webcron, auth)
- **API Endpoints:** 20 (10 Thread, 3 Email, 7 Webhook)
- **Database Tables:** 9 (with migrations)

## 🚀 Next Steps

### Immediate (This Week)
- [ ] Complete user settings implementation
- [ ] Build thread list view
- [ ] Implement basic authentication

### Short-term (Next 2 Weeks)
- [ ] Thread detail view
- [ ] Email reply functionality
- [ ] Mobile-responsive UI

### Long-term (M4+)
- [ ] Personal IMAP accounts
- [ ] Advanced search
- [ ] Activity log

## 📚 Documentation Status

- ✅ Architecture fully documented
- ✅ All modules have README
- ⚠️ User documentation pending (M4)
- ⚠️ Admin guide pending (M4)

## 🐛 Known Issues

(Link to GitHub Issues or internal tracker)

## 🔗 Quick Links

- [Development Guidelines](development-guidelines.md)
- [Roadmap](roadmap.md)
- [API Documentation](api.md)
- [Architecture](architecture.md)
```

**Deliverable:**
- `docs/dev/PROJECT-STATUS.md` (aktuell und wartbar)

---

### Phase 3: Codebase-Validierung & Testing (120-180 min)

#### 3.1 Umfassender Funktionstest durchführen
**Ziel:** Validiere, dass alle implementierten Features funktionieren.

**Test-Checkliste:**

##### A) Foundation (M0)
```bash
# Logger-Modul
php tests/manual/logger-test.php
# Erwartung: Log-Dateien werden erstellt, strukturiertes JSON-Format

# Config-Modul
php tests/manual/config-test.php
# Erwartung: .env wird geladen, Defaults funktionieren

# Encryption
php tests/manual/encryption-test.php
# Erwartung: Ver-/Entschlüsselung funktioniert

# Database
php database/migrate.php
php database/test.php
# Erwartung: Alle Migrations laufen, CRUD funktioniert
```

##### B) IMAP Core (M1)
```bash
# IMAP Client
php tests/manual/imap-connection-test.php
# Erwartung: Verbindung zu Test-Mailbox erfolgreich

# Email Parser
php tests/manual/email-parser-test.php
# Erwartung: HTML/Plain/Attachments werden korrekt geparst

# Thread Manager
php tests/manual/threading-test.php
# Erwartung: E-Mails werden korrekt zu Threads gruppiert

# Label Manager
php tests/manual/label-test.php
# Erwartung: System- und Custom-Labels funktionieren

# Webcron Polling
php tests/manual/webcron-test.php
# Erwartung: Polling läuft, neue Mails werden verarbeitet
```

##### C) Thread API (M2)
```bash
# API Health Check
curl http://localhost/api/health
# Erwartung: {"status": "ok", "timestamp": "..."}

# Thread List
curl http://localhost/api/threads
# Erwartung: JSON-Liste aller Threads

# Thread Detail
curl http://localhost/api/threads/1
# Erwartung: Thread mit E-Mails und Labels

# Email Send
php tests/manual/email-send-test.php
# Erwartung: E-Mail wird verschickt, Thread wird aktualisiert

# Webhook
php tests/manual/webhook-test.php
# Erwartung: Webhook wird getriggert, HMAC-Validierung funktioniert
```

**Deliverable:**
```markdown
# Functional Test Report - [Date]

## Test Environment
- PHP Version: 8.1.x
- MySQL Version: 8.0.x
- OS: Windows 11 / XAMPP

## Test Results

### M0 Foundation
| Test | Status | Notes |
|------|--------|-------|
| Logger Module | ✅ PASS | Logs created in /logs/ |
| Config Module | ✅ PASS | .env loaded correctly |
| Encryption | ✅ PASS | AES-256 working |
| Database | ✅ PASS | All migrations OK |

### M1 IMAP Core
| Test | Status | Notes |
|------|--------|-------|
| IMAP Connection | ✅ PASS | Connected to test account |
| Email Parser | ⚠️ WARN | Attachment encoding issue (non-critical) |
| Thread Manager | ✅ PASS | Threads grouped correctly |
| Label Manager | ✅ PASS | Labels applied |
| Webcron Polling | ✅ PASS | Polls every 5min |

### M2 Thread API
| Test | Status | Notes |
|------|--------|-------|
| Health Endpoint | ✅ PASS | Returns status |
| Thread List | ✅ PASS | 47 threads found |
| Thread Detail | ✅ PASS | Emails + Labels loaded |
| Email Send | ❌ FAIL | SMTP credentials missing |
| Webhook | ✅ PASS | HMAC validated |

## Critical Issues
1. **SMTP not configured** - Email sending fails → Fix: Add SMTP credentials to .env
2. **Attachment encoding** - Umlauts in filenames broken → Fix: Use RFC 2047 encoding

## Recommendations
1. Add automated PHPUnit tests for M3
2. Create Docker setup for consistent test environment
3. Add CI/CD pipeline (GitHub Actions)
```

#### 3.2 Test-Suite organisieren
**Aufgabe:** Strukturiere die bestehenden Tests:

**Ziel-Struktur:**
```
tests/
├── unit/                       # PHPUnit Unit-Tests (zukünftig)
│   ├── Logger/
│   ├── Config/
│   └── Encryption/
├── integration/                # Integration-Tests (zukünftig)
│   ├── ImapTest.php
│   └── ThreadingTest.php
├── manual/                     # Manuelle Test-Scripts (aktuell)
│   ├── artifacts/             # Test-Dateien & Outputs
│   │   ├── test-email.json
│   │   └── test-attachments/
│   ├── m0-foundation/
│   │   ├── logger-test.php
│   │   ├── config-test.php
│   │   └── encryption-test.php
│   ├── m1-imap/
│   │   ├── imap-connection-test.php
│   │   ├── email-parser-test.php
│   │   ├── threading-test.php
│   │   └── label-test.php
│   ├── m2-api/
│   │   ├── thread-api-test.php
│   │   ├── email-send-test.php
│   │   └── webhook-test.php
│   └── README.md              # Test-Anleitung
└── e2e/                        # End-to-End Tests (zukünftig)
    └── README.md
```

**Aufgaben:**
1. Alle bestehenden Test-Scripts nach Meilenstein gruppieren
2. Test-Artifacts in separates Verzeichnis verschieben
3. `tests/manual/README.md` erstellen mit Anleitungen
4. Obsolete/doppelte Tests identifizieren und entfernen

**Deliverable:**
- Organisierte Test-Struktur
- `tests/manual/README.md` mit Anleitung zum Ausführen aller Tests

#### 3.3 Code-Qualität prüfen
**Aufgabe:** Statische Code-Analyse und Konsistenz-Check:

**Tools & Checks:**
```bash
# PSR-12 Compliance (mit PHP_CodeSniffer, falls vorhanden)
vendor/bin/phpcs --standard=PSR12 src/

# Strict Types Declaration Check
grep -r "declare(strict_types=1)" src/ | wc -l
# Erwartung: Alle PHP-Dateien haben strict types

# Logging Usage Check
grep -r "private LoggerInterface \$logger" src/ | wc -l
# Erwartung: Alle Services injizieren Logger

# TODO/FIXME Count
grep -rn "TODO\|FIXME" src/
# Dokumentiere alle offenen TODOs

# Deprecation Check
grep -rn "@deprecated" src/
# Dokumentiere veraltete APIs
```

**Deliverable:**
```markdown
# Code Quality Report

## PSR-12 Compliance
- Files checked: 87
- Violations: 12 (mostly indentation)
- Action: Run `phpcbf` to auto-fix

## Architecture Compliance
- ✅ All Services inject LoggerInterface
- ✅ All files use strict_types=1
- ⚠️ 3 Controllers have business logic → Refactor to Service layer
- ❌ ThreadService.php: Direct DB query (line 234) → Use Repository

## Technical Debt
- TODO count: 17 (documented below)
- FIXME count: 3 (documented below)
- Deprecated APIs: 0

## Open TODOs
| File | Line | TODO | Priority |
|------|------|------|----------|
| src/app/Services/ThreadService.php | 45 | Add caching | Low |
| src/modules/imap/src/ImapClient.php | 123 | Handle OAuth2 | Medium |
| ... | ... | ... | ... |

## Recommendations
1. Refactor Controllers (move logic to Services)
2. Add Repository for ThreadService line 234
3. Run phpcbf for PSR-12 auto-fixes
4. Create issues for all Medium/High TODOs
```

---

### Phase 4: Dokumentations-Konsolidierung (90-120 min)

#### 4.1 Sprint-Dokumentation archivieren
**Aufgabe:** Alle `[COMPLETED]` Sprint-Docs in Archive verschieben:

**Struktur:**
```
docs/dev/
├── archive/
│   ├── milestones/
│   │   ├── m0-foundation/
│   │   │   ├── Sprint-0.1-Logger-Modul.md
│   │   │   ├── Sprint-0.2-Config-Modul.md
│   │   │   └── ...
│   │   ├── m1-imap-core/
│   │   │   ├── Sprint-1.1-IMAP-Client-Modul.md
│   │   │   └── ...
│   │   └── m2-thread-api/
│   │       ├── Sprint-2.1-Thread-Management-API.md
│   │       └── ...
│   ├── bugs/
│   │   └── BUGFIX-2025-11-18.md
│   └── README.md               # Archive Index
├── [WIP] M3-MVP-UI.md          # Aktive WIP-Docs bleiben
├── development-guidelines.md    # Neu (aus basics.txt)
├── PROJECT-STATUS.md            # Zentrale Status-Übersicht
└── ... (Kern-Docs bleiben)
```

**Deliverable:**
- Archivierte Sprint-Dokumentation
- `docs/dev/archive/README.md` mit Index aller archivierten Docs

#### 4.2 Modul-Dokumentation vervollständigen
**Aufgabe:** Prüfe jedes Modul auf vollständige Dokumentation:

**Checkliste pro Modul:**
```markdown
## Module: [Name]

### Documentation Checklist
- [ ] README.md exists in `src/modules/{module}/`
- [ ] README.md contains:
  - [ ] Purpose & Features
  - [ ] Installation & Setup
  - [ ] Configuration Options
  - [ ] Public API Reference
  - [ ] Usage Examples
  - [ ] Dependencies
  - [ ] Testing Instructions
- [ ] API documentation in `docs/modules/{module}/` (if applicable)
- [ ] Examples in `src/modules/{module}/examples/` (optional)
```

**Module zu prüfen:**
1. `logger` - ✅ (bereits vollständig dokumentiert)
2. `config` - ✅
3. `encryption` - ✅
4. `imap` - ⚠️ (API docs erweitern)
5. `label` - ⚠️ (README erstellen)
6. `smtp` - ⚠️ (README erstellen)
7. `webcron` - ✅
8. `auth` - ⚠️ (README erstellen)
9. `theme` - ⚠️ (README erstellen)
10. `health` - ⚠️ (README erstellen)

**Deliverable:**
- Vollständige README für alle Module
- Aktualisierte/erweiterte API-Dokumentation wo nötig

#### 4.3 Onboarding-Dokumentation erstellen
**Aufgabe:** Erstelle einen klaren Einstiegspunkt für neue Entwickler:

**Dokument:** `docs/GETTING-STARTED.md`

**Inhalt:**
```markdown
# Getting Started with CI-Inbox Development

**Welcome!** This guide gets you up and running in 30 minutes.

## Prerequisites
- PHP 8.1+
- MySQL 8.0+ / MariaDB 10.6+
- Composer
- XAMPP (Windows) or equivalent

## Quick Setup (5 minutes)

### 1. Clone & Install
```bash
git clone [repo-url]
cd CI-Inbox
composer install
```

### 2. Configure Environment
```bash
cp .env.example .env
# Edit .env: Add database credentials
```

### 3. Initialize Database
```bash
php database/migrate.php
php database/seed-test-data.php
```

### 4. Verify Installation
```bash
php scripts/verify-installation.php
# Expected: All checks pass ✅
```

## Project Structure (5 minutes)

[Explain folder structure with ASCII tree]

## Your First Task (20 minutes)

### Task: Add a new label to the system

1. Read: `docs/dev/development-guidelines.md` (Section 6.3)
2. Create: `database/migrations/XXX_add_custom_label.php`
3. Test: `php database/migrate.php`
4. Verify: Check database table `labels`

## Next Steps

- 📖 Read [Development Guidelines](dev/development-guidelines.md)
- 🏗️ Study [Architecture Overview](dev/architecture.md)
- 🗺️ Check [Roadmap](dev/roadmap.md) for current priorities
- 💬 Join discussions in [GitHub Issues]

## Common Commands

```bash
# Run migrations
php database/migrate.php

# Run manual tests
php tests/manual/m1-imap/imap-connection-test.php

# Check logs
tail -f logs/app.log

# Start dev server
php -S localhost:8080 -t src/public
```

## Need Help?

- 📚 Check [PROJECT-STATUS.md](dev/PROJECT-STATUS.md) for current work
- 🐛 Search [GitHub Issues]
- 💡 Read [FAQ](FAQ.md)
```

**Deliverable:**
- `docs/GETTING-STARTED.md` (klar, praktisch, getestet)

---

### Phase 5: Repository-Hygiene & Finalisierung (60-90 min)

#### 5.1 .gitignore aktualisieren
**Aufgabe:** Stelle sicher, dass keine unnötigen Dateien committed werden:

**Zu prüfen:**
```gitignore
# Environment
.env

# Dependencies
/vendor/

# Logs
/logs/*.log
!/logs/.gitkeep

# Data
/data/*
!/data/.gitkeep
/data/cache/*
!/data/cache/.gitkeep

# IDE
.vscode/
.idea/
*.sublime-*

# OS
.DS_Store
Thumbs.db

# Tests
/tests/manual/artifacts/*
!/tests/manual/artifacts/.gitkeep

# Temp
*.tmp
*.bak
*.swp
~*
```

**Aufgaben:**
1. Prüfe, ob alle Patterns korrekt sind
2. Suche nach versehentlich committeten Dateien:
   ```bash
   git ls-files | grep -E "\.env$|\.log$|\.tmp$"
   ```
3. Entferne sie aus History (falls vorhanden)

**Deliverable:**
- Aktualisierte `.gitignore`
- Saubere Git-History

#### 5.2 README.md optimieren
**Aufgabe:** Haupteintrittspunkt des Repos perfektionieren:

**Struktur:**
```markdown
# CI-Inbox: Collaborative IMAP Inbox Management

[Badges: Status, PHP Version, License]

## 🎯 What is CI-Inbox?

[Kurze, prägnante Beschreibung - 2-3 Sätze]

## ✨ Key Features

[Liste der Hauptfeatures mit Icons]

## 🚀 Quick Start

[5-Minuten-Setup]

## 📚 Documentation

- 🆕 [Getting Started](docs/GETTING-STARTED.md) - **Start here!**
- 🏗️ [Architecture](docs/dev/architecture.md)
- 🗺️ [Roadmap](docs/dev/roadmap.md)
- 📊 [Project Status](docs/dev/PROJECT-STATUS.md)
- 🔧 [Development Guidelines](docs/dev/development-guidelines.md)
- 📖 [API Reference](docs/dev/api.md)

## 🤝 Contributing

[Link to CONTRIBUTING.md]

## 📝 License

MIT License - see [LICENSE](LICENSE)

## 🔗 Links

- [GitHub Repository]
- [Issue Tracker]
- [Documentation]
```

**Deliverable:**
- Aktualisiertes README.md (professionell, informativ, einladend)

#### 5.3 CONTRIBUTING.md erstellen
**Aufgabe:** Definiere klare Contribution-Guidelines:

**Inhalt:**
```markdown
# Contributing to CI-Inbox

**Thank you for contributing!** 🎉

## Development Workflow

1. **Read the guidelines**: [Development Guidelines](docs/dev/development-guidelines.md)
2. **Check the roadmap**: [Current priorities](docs/dev/roadmap.md)
3. **Find an issue**: [GitHub Issues] - look for `good first issue` tags
4. **Create a branch**: `git checkout -b feature/your-feature-name`
5. **Implement**: Follow coding standards (PSR-12, strict types)
6. **Test**: Write/update tests for your changes
7. **Document**: Update relevant documentation
8. **Commit**: Write clear commit messages
9. **Pull Request**: Reference the issue number

## Coding Standards

- **PSR-12** for code style
- **Strict types** in all PHP files
- **Logging** for all important operations
- **Layer abstraction** (Service → Repository pattern)
- **Tests** for all new features

## Running Tests

```bash
# Manual tests
php tests/manual/[category]/[test-name].php

# (Future: PHPUnit)
./vendor/bin/phpunit
```

## Pull Request Checklist

- [ ] Code follows PSR-12
- [ ] All files have `declare(strict_types=1)`
- [ ] Tests pass
- [ ] Documentation updated
- [ ] Changelog entry added
- [ ] No merge conflicts

## Questions?

Ask in the [GitHub Discussions] or open an issue!
```

**Deliverable:**
- `CONTRIBUTING.md`

#### 5.4 Finaler Cleanup
**Aufgabe:** Letzte Aufräumarbeiten:

**Checkliste:**
- [ ] Alle Root-Level Dateien sind kategorisiert
- [ ] Obsolete Dateien sind entfernt
- [ ] Test-Artifacts sind in `tests/manual/artifacts/`
- [ ] Alle Docs haben konsistente Formatierung
- [ ] Alle internen Links funktionieren
- [ ] `.github/copilot-instructions.md` ist aktuell
- [ ] `PROJECT-STATUS.md` ist aktuell
- [ ] README.md spiegelt aktuellen Stand wider

**Finale Validierung:**
```bash
# Link-Check (mit tool oder manuell)
grep -r "](docs/" . | grep ".md:" | # Prüfe alle internen Links

# Broken symlinks
find . -type l ! -exec test -e {} \; -print

# Empty directories (außer mit .gitkeep)
find . -type d -empty

# Large files (>1MB, sollten nicht committed sein)
find . -type f -size +1M

# Files without extension (sollte nur README sein)
find . -type f ! -name "*.*" ! -name "README"
```

**Deliverable:**
- Bereinigte Repository-Struktur
- Checkliste aller durchgeführten Aktionen

---

## 📋 Finale Deliverables

### 1. Dokumentation
- ✅ `docs/dev/development-guidelines.md` (aus basics.txt)
- ✅ `docs/dev/PROJECT-STATUS.md` (aktuell)
- ✅ `docs/GETTING-STARTED.md` (Onboarding)
- ✅ `docs/dev/archive/` (Sprint-Docs archiviert)
- ✅ Alle Module haben vollständige README
- ✅ `.github/copilot-instructions.md` (aktualisiert)

### 2. Tests
- ✅ `tests/manual/` (organisiert nach Milestones)
- ✅ `tests/manual/artifacts/` (Test-Dateien separiert)
- ✅ `tests/manual/README.md` (Test-Anleitung)
- ✅ Functional Test Report

### 3. Code Quality
- ✅ Code Quality Report
- ✅ Alle Violations dokumentiert
- ✅ Technical Debt transparent gemacht

### 4. Repository
- ✅ README.md (optimiert)
- ✅ CONTRIBUTING.md (neu)
- ✅ .gitignore (aktualisiert)
- ✅ Root-Level aufgeräumt
- ✅ Konsistente Struktur

### 5. Reports
- ✅ Root-Level Audit Report
- ✅ Documentation Audit Report
- ✅ Functional Test Report
- ✅ Code Quality Report
- ✅ Final Cleanup Checklist

---

## 🎯 Erfolgskriterien

### Technisch
- ✅ Alle Tests laufen durch (oder Fehler sind dokumentiert)
- ✅ Keine Compiler-Fehler/Warnings
- ✅ PSR-12 Compliance (oder Violations dokumentiert)
- ✅ Alle Module haben vollständige Dokumentation
- ✅ Git-History ist sauber

### Dokumentation
- ✅ Neuer Entwickler kann in 30min loslegen (mit GETTING-STARTED.md)
- ✅ Jedes Feature ist dokumentiert
- ✅ Architektur-Entscheidungen sind nachvollziehbar
- ✅ Aktuelle Prioritäten sind klar (PROJECT-STATUS.md)

### SSOT-Kriterien
- ✅ **Single Source**: Keine widersprüchlichen Informationen
- ✅ **Truth**: Dokumentation spiegelt aktuellen Code-Stand wider
- ✅ **Discoverable**: Wichtige Infos sind leicht auffindbar
- ✅ **Maintainable**: Struktur ist klar und wartbar
- ✅ **Onboarding-Ready**: Neue Agents können sofort produktiv arbeiten

---

## 📝 Arbeitsweise

### Empfohlene Vorgehensweise

1. **Sequentiell arbeiten**: Eine Phase nach der anderen
2. **Dokumentieren**: Alle Reports in `docs/dev/cleanup-reports/` ablegen
3. **Rückfragen**: Bei Unklarheiten nachfragen (nicht raten!)
4. **Kleine Commits**: Nach jeder abgeschlossenen Sub-Task committen
5. **Validieren**: Jede Änderung testen/verifizieren

### Kommunikation

Nach jeder Phase:
```markdown
## Phase X: [Name] - COMPLETED

### Durchgeführte Aktionen:
1. [Aktion 1]
2. [Aktion 2]

### Findings:
- [Finding 1]
- [Finding 2]

### Deliverables:
- ✅ [Deliverable 1]
- ✅ [Deliverable 2]

### Offene Fragen:
- ❓ [Frage 1]
- ❓ [Frage 2]

### Nächster Schritt:
Phase [X+1]: [Name]
```

### Bei Problemen
- **Dokumentiere** das Problem ausführlich
- **Analysiere** mögliche Ursachen
- **Schlage** Lösungsoptionen vor
- **Frage** nach Entscheidung (nicht selbst entscheiden bei Architektur-Fragen!)

---

## 🚀 Start Command

```bash
# 1. Status sichern
git status
git stash # Falls uncommitted changes

# 2. Branch erstellen
git checkout -b cleanup/repository-ssot-setup

# 3. Los geht's mit Phase 1!
echo "Starting Phase 1: Strukturanalyse & Inventarisierung..."
```

---

## 📞 Kontakt

Bei Fragen oder Unklarheiten:
- **Erstelle** ein Issue mit dem Tag `[CLEANUP-TASK]`
- **Dokumentiere** den aktuellen Stand
- **Warte** auf Klärung (nicht selbst entscheiden!)

---

**Viel Erfolg! 🚀**

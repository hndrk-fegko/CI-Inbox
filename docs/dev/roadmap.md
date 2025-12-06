# Roadmap: Collaborative IMAP Inbox (CI-Inbox)

**Letzte Aktualisierung:** 6. Dezember 2025  
**Autor:** Hendrik Dreis ([hendrik.dreis@feg-koblenz.de](mailto:hendrik.dreis@feg-koblenz.de))  
**Lizenz:** MIT License  
**Basis:** `vision.md`, `inventar.md`, `basics.txt`

Diese Roadmap ist optimiert für **KI-gestützte Entwicklung**: Jeder Meilenstein baut auf **standalone-testbaren Komponenten** auf, die klare Schnittstellen haben und unabhängig entwickelt werden können.

---

## Entwicklungsprinzip: Building Blocks First

```
Standalone Komponenten (testbar) → Integration → Feature-Komplettierung → Testing
```

**Warum?**
- ✅ KI kann fokussiert an einzelner Komponente arbeiten
- ✅ Jede Komponente ist sofort testbar (ohne Abhängigkeiten)
- ✅ Schnittstellen sind klar definiert (Interfaces/Contracts)
- ✅ Spätere Features "docken" einfach an
- ✅ Parallele Entwicklung möglich

---

## Meilenstein-Übersicht

| Meilenstein | Zeitrahmen | Ziel | Features | Status |
|-------------|------------|------|----------|--------|
| **M0: Foundation** | Woche 1-2 | Basis-Infrastruktur & Testability | Logger, Config, Database, Core | ✅ COMPLETED |
| **M1: IMAP Core** | Woche 3-4 | IMAP-Handling standalone | IMAP-Client, E-Mail-Parser, Thread-Manager, Label-Manager | ✅ COMPLETED |
| **M2: Thread API** | Woche 5-6 | REST API für Thread-Management | Thread-API, Advanced Operations | ✅ COMPLETED |
| **M3: MVP UI** | Woche 7-8 | Minimales Frontend | Auth, Inbox-View, Actions, Composer | ✅ COMPLETED |
| **M4: Beta** | Woche 9-12 | Workflow C & Polish | IMAP-Transfer, Mobile, Security | 📋 PLANNED |
| **M5: v1.0** | Woche 13-16 | Production-Ready | Performance, Docs, Deployment | 📋 PLANNED |

**Gesamt: ~16 Wochen (4 Monate)**

**Aktueller Fortschritt:** 
- ✅ M0: Foundation COMPLETED (3h 50min - 17. November 2025) 
- ✅ M1: IMAP Core COMPLETED (~11h - 17. November 2025) 
- ✅ M2: Thread API COMPLETED (~9.5h - 18. November 2025)
- ✅ M3: MVP UI COMPLETED (~2 Wochen - 6. Dezember 2025) 🎉
- 📋 M4: Beta (NEXT)

---

## M0: Foundation (Woche 1-2) ✅ COMPLETED

**Status:** ✅ **100% COMPLETED** (17. November 2025)  
**Tatsächliche Dauer:** ~4 Stunden (vs. geschätzt 2 Wochen)

**Ziel:** Basis-Infrastruktur, die von allen Features genutzt wird. Jede Komponente ist **standalone testbar**.

### Features (aus `inventar.md`):
- ✅ **6.1** - Zentrales Logging-System (MUST)
- ✅ **Config-Modul** - Zentrale Konfigurationsverwaltung
- ✅ **5.1** - Datenverschlüsselung (Encryption-Service)
- ✅ **Database-Setup** - Eloquent ORM, Migrations (7 Tabellen)
- ✅ **Core-Infrastruktur** - DI-Container, Hook-Manager, Application

### Implementierte Struktur:
```
src/
├── core/                        # ✅ Core-Infrastruktur
│   ├── Application.php          # Main Application Class (125 lines)
│   ├── Container.php            # PHP-DI Wrapper (55 lines)
│   ├── HookManager.php          # Event System (70 lines)
│   └── ModuleLoader.php         # Auto-Discovery (95 lines)
├── config/
│   └── container.php            # ✅ DI Service Definitions
├── bootstrap/
│   └── database.php             # ✅ Eloquent Capsule Setup
├── routes/
│   ├── api.php                  # ✅ API Routes (health, info)
│   └── web.php                  # ✅ Web Routes (homepage)
├── app/
│   └── Models/                  # ✅ 6 Eloquent Models
│       ├── BaseModel.php
│       ├── User.php
│       ├── ImapAccount.php
│       ├── Thread.php
│       ├── Email.php
│       └── Label.php
└── modules/                     # ✅ Standalone Module
    ├── logger/                  # ✅ Sprint 0.1
    ├── config/                  # ✅ Sprint 0.2
    └── encryption/              # ✅ Sprint 0.3

database/
├── migrations/                  # ✅ 7 Migrations
│   ├── 001_create_users_table.php
│   ├── 002_create_imap_accounts_table.php
│   ├── 003_create_threads_table.php
│   ├── 004_create_emails_table.php
│   ├── 005_create_labels_table.php
│   ├── 006_create_thread_assignments_table.php
│   └── 007_create_thread_labels_table.php
├── migrate.php                  # ✅ Migration Runner
└── test.php                     # ✅ CRUD Test (ALL PASSED)
```

**Live Application:**
- 🌐 **Homepage:** http://ci-inbox.local/ (Status 200 ✅)
- 🔧 **Health Check:** http://ci-inbox.local/api/system/health (JSON ✅)
- 📊 **API Info:** http://ci-inbox.local/api (Endpoint List ✅)

---

### Sprint 0.1: Logger-Modul ✅ COMPLETED (~60 min)
### Sprint 0.1: Logger-Modul ✅ COMPLETED (~60 min)
**Status:** ✅ COMPLETED | **Dokument:** `[COMPLETED] M0-Sprint-0.1-Logger-Modul.md`

**Implementiert:**
```
src/modules/logger/
├── src/
│   ├── LoggerService.php        # ✅ 186 lines, PSR-3 + custom success()
│   ├── Formatters/
│   │   └── JsonFormatter.php    # ✅ JSON mit Backtrace
│   ├── Handlers/
│   │   └── RotatingFileHandler.php  # ✅ 30 Tage Retention
│   └── LoggerException.php
├── config/logger.config.php
├── tests/manual-test.php        # ✅ 16 Log-Einträge validiert
└── README.md
```

**Test-Ergebnis:** ✅ 16/16 Log-Einträge erfolgreich (File + Console)

---

### Sprint 0.2: Config-Modul ✅ COMPLETED (~50 min)
**Status:** ✅ COMPLETED | **Dokument:** `[COMPLETED] M0-Sprint-0.2-Config-Modul.md`

**Implementiert:**
```
src/modules/config/
├── src/
│   ├── ConfigService.php        # ✅ 270 lines, ENV + PHP Configs
│   └── ConfigException.php
├── config/
│   ├── app.config.php
│   ├── database.config.php
│   └── logger.config.php
├── tests/manual-test.php        # ✅ 9 Tests bestanden
└── README.md
```

**Features:** Dot-notation, Type-safe Getters (getString, getInt, getBool, getArray), ENV-Override

**Test-Ergebnis:** ✅ 9/9 Tests erfolgreich

---

### Sprint 0.3: Encryption-Service ✅ COMPLETED (~45 min)
**Status:** ✅ COMPLETED | **Dokument:** `[COMPLETED] M0-Sprint-0.3-Encryption-Service.md`

**Implementiert:**
```
src/modules/encryption/
├── src/
│   ├── EncryptionService.php    # ✅ 220 lines, AES-256-CBC
│   └── EncryptionException.php
├── config/encryption.config.php
├── tests/manual-test.php        # ✅ 10 Tests bestanden
└── README.md
```

**Features:** Random IV per Encryption, Base64-Format: `iv::encrypted`

**Test-Ergebnis:** ✅ 10/10 Tests erfolgreich (inkl. IMAP Password Encryption)

---

### Sprint 0.4: Database-Setup ✅ COMPLETED (~35 min)
**Status:** ✅ COMPLETED | **Dokument:** `[COMPLETED] M0-Sprint-0.4-Database-Setup.md`

**Implementiert:**
```
database/
├── migrations/                  # ✅ 7 Tabellen
│   ├── 001_create_users_table.php
│   ├── 002_create_imap_accounts_table.php
│   ├── 003_create_threads_table.php
│   ├── 004_create_emails_table.php
│   ├── 005_create_labels_table.php
│   ├── 006_create_thread_assignments_table.php (Pivot)
│   └── 007_create_thread_labels_table.php (Pivot)
├── migrate.php                  # ✅ Migration Runner
└── test.php                     # ✅ Comprehensive CRUD Test

src/bootstrap/
└── database.php                 # ✅ Eloquent Capsule Setup

src/app/Models/
├── BaseModel.php                # ✅ Base mit Timestamps
├── User.php                     # ✅ Mit Relationships
├── ImapAccount.php
├── Thread.php
├── Email.php
└── Label.php
```

**Datenbank-Schema:**
- users (id, email, password_hash, name, role, is_active, last_login_at)
- imap_accounts (user_id, email, imap_host, port, password_encrypted, etc.)
- threads (subject, participants JSON, status, last_message_at, etc.)
- emails (thread_id, message_id, from, to, cc, subject, body, attachments JSON)
- labels (name, color, display_order)
- thread_assignments (Pivot: thread_id, user_id)
- thread_labels (Pivot: thread_id, label_id)

**Test-Ergebnis:** ✅ 10/10 CRUD-Tests erfolgreich (inkl. Relationships)

**Lesson Learned:** Pivot-Tabellen ohne `withTimestamps()` nutzen, wenn keine created_at/updated_at Spalten vorhanden

---

### Sprint 0.5: Core-Infrastruktur ✅ COMPLETED (~40 min)
**Status:** ✅ COMPLETED | **Dokument:** `[COMPLETED] M0-Sprint-0.5-Core-Infrastruktur.md`

**Implementiert:**
```
src/core/
├── Application.php              # ✅ 125 lines, Main Bootstrap
├── Container.php                # ✅ 55 lines, PHP-DI Wrapper
├── HookManager.php              # ✅ 70 lines, Event System
└── ModuleLoader.php             # ✅ 95 lines, Auto-Discovery

src/config/
└── container.php                # ✅ DI Service Definitions

src/routes/
├── api.php                      # ✅ Health Endpoint, API Info
└── web.php                      # ✅ Homepage

src/public/
└── index.php                    # ✅ Updated: Uses Application Class
```

**Funktionale Endpoints:**
- `GET /` - Homepage (HTML mit CSS)
- `GET /api/system/health` - Health Check (JSON mit Module Status)
- `GET /api` - API Info (Endpoint List)

**Test-Ergebnis:** ✅ Alle Endpoints funktional (Status 200)

**Lesson Learned:** Container Service Definitions müssen exakt mit Constructor Signatures matchen

---

### M0 Deliverables & Success Criteria ✅ ACHIEVED

**Deliverables:**
- ✅ Alle 5 Sprints (Logger, Config, Encryption, Database, Core) funktionieren standalone
- ✅ Core-Infrastruktur läuft (Application, DI Container, Hook Manager, Module Loader)
- ✅ Alle manuellen Tests grün (Logger: 16/16, Config: 9/9, Encryption: 10/10, Database: 10/10)
- ✅ Jedes Modul hat README mit Verwendungsbeispielen
- ✅ Application läuft live: http://ci-inbox.local/
- ✅ Health-Check System geplant (roadmap.md M5 Sprint 5.3) - Implementierung in M4/M5

**Success Criteria:**
- ✅ KI kann jedes Modul **unabhängig** weiterentwickeln
- ✅ Neue Entwickler können in < 30 Min. lokales Setup erstellen
- ✅ Logging funktioniert in File & Console (Database-Handler vorbereitet)
- ✅ Sensible Daten können verschlüsselt gespeichert werden (AES-256-CBC)
- ✅ DI Container löst alle Services auf
- ✅ Hook System initialisiert (ready für Module-Events)

**Gesamtdauer M0:** ~230 Minuten (3h 50min) vs. geschätzt 2 Wochen 🚀

---

## M1: IMAP Core (Woche 3-4) 📨 ✅ COMPLETED

**Status:** ✅ **100% COMPLETED** (17. November 2025)  
**Tatsächliche Dauer:** ~3 Tage (vs. geschätzt 2 Wochen)

**Ziel:** IMAP-Handling komplett standalone – Mails abholen, parsen, in Threads gruppieren, mit Labels organisieren. Alle Komponenten sind **standalone testbar**.

**Highlights:**
- ⭐ IMAP Keywords (Performance + Disaster Recovery)
- ⭐ Setup-Wizard Certificate Auto-Discovery (Shared-Hosting-Kompatibilität)
- ⭐ Graceful Degradation (funktioniert mit/ohne Keyword-Support)

### Features (aus `inventar.md`):
- ✅ **2.1** - Primäre IMAP-Verbindung (MUST)
- ✅ **2.5** - E-Mail-Parsen (MUST)
- ✅ **2.3** - Email-Threading (MUST)
- ✅ **3.1** - Label-System (MUST)
- ✅ **2.3** - Webcron-Polling-Dienst (MUST)
- ✅ **5.2** - Webcron-Authentifizierung (MUST)

---

### Sprint 1.1: IMAP-Client-Modul ✅ COMPLETED (~3 days)
**Status:** ✅ COMPLETED | **Dokument:** `[COMPLETED] M1-Sprint-1.1-IMAP-Client-Modul.md`

**Abhängigkeit:** Logger, Config, Encryption

**Feature:** 2.1 (MUST) + **BONUS:** Setup-Wizard mit Auto-Discovery (Features 7.1-7.3) + **IMAP Keywords**

**Implementiert:**
```
src/modules/imap/
├── src/
│   ├── ImapClientInterface.php      # ✅ 180 lines (+40 for keywords)
│   ├── ImapClient.php               # ✅ 623 lines (+150 for keywords)
│   ├── ImapMessageInterface.php     # ✅ 165 lines
│   ├── ImapMessage.php              # ✅ 520 lines
│   └── Exceptions/
│       └── ImapException.php        # ✅ 111 lines
├── config/
│   └── imap.config.php              # ✅ 104 lines
├── tests/
│   ├── mercury-quick-test.php       # ✅ 352 lines - Mercury Round-Trip
│   ├── setup-autodiscover.php       # ✅ 918 lines - Production Setup Wizard (+48 lines)
│   ├── smtp-imap-roundtrip-test.php # ✅ 383 lines - Generic Round-Trip
│   └── README.md                    # ✅ Updated - Test-Scripts Overview
├── docs/
│   └── Setup-Autodiscover.md        # ✅ Full documentation
├── module.json                      # ✅ Manifest
└── README.md                        # ✅ 430 lines - Module documentation

**Total:** ~4,200 lines of code (inkl. Tests & Setup-Wizard + Keywords)
```

**NEW: IMAP Keywords Feature ⭐**
- `search(string $criteria): array` - IMAP SEARCH (e.g., UNKEYWORD CI-Synced)
- `addKeyword(string $uid, string $keyword): bool` - Set custom keyword
- `removeKeyword(string $uid, string $keyword): bool` - Remove keyword
- `getKeywords(string $uid): array` - Get message keywords

**Architecture Pattern:** DB = SSOT, IMAP Keyword = Performance Filter + Recovery Marker
- Performance: SEARCH UNKEYWORD reduces candidate set
- Recovery: Remove tags to trigger re-import
- Multi-Client: Thunderbird compatibility
- Graceful Degradation: Works without keyword support (Mercury)

**NEW: Setup-Wizard Certificate Auto-Discovery ⭐**
- Extracts CN from SSL certificate on mismatch
- Offers automatic retry with real hostname
- Solves shared-hosting scenarios (e.g., imap.domain.de → psa22.webhoster.ag)

**Test-Ergebnis:** 
- ✅ Mercury Round-Trip erfolgreich (SMTP Send → IMAP Fetch → Parse)
- ✅ Production IMAP verified (webhoster.ag: Full keyword support)
- ✅ Graceful degradation proven (Mercury: SEARCH only)

---

### Sprint 1.2: Email-Parser-Modul ✅ COMPLETED (~2h)
**Status:** ✅ COMPLETED | **Dokument:** `[COMPLETED] M1-Sprint-1.2-Email-Parser.md`

**Abhängigkeit:** IMAP-Client, Logger

**Feature:** 2.5 (MUST)

**Implementiert:**
```
src/modules/imap/src/Parser/
├── EmailParserInterface.php     # ✅ 75 lines
├── EmailParser.php              # ✅ 355 lines - Main Parser
├── ParsedEmail.php              # ✅ 195 lines - DTO
├── EmailHeader.php              # ✅ 102 lines - Header DTO
├── EmailAddress.php             # ✅ 72 lines - Address DTO
├── EmailAttachment.php          # ✅ 83 lines - Attachment DTO
└── ParserException.php          # ✅ 26 lines

src/modules/imap/tests/
└── parser-integration-test.php  # ✅ 198 lines - E2E Test

**Total:** ~1,100 lines of code
```

**Features:**
- HTML → Plain Text Conversion (HTMLPurifier)
- MIME-Type Detection (Fileinfo)
- Attachment Extraction (Base64 + Quoted-Printable)
- Header-Parsing (Message-ID, In-Reply-To, References)
- Address-Parsing (RFC 2822)

**Test-Ergebnis:** ✅ 8 E-Mails erfolgreich geparst (inkl. Attachments)

---

### Sprint 1.3: Thread-Manager ✅ COMPLETED (~2h)
**Status:** ✅ COMPLETED | **Dokument:** `[COMPLETED] M1-Sprint-1.3-Thread-Manager.md`

**Abhängigkeit:** Email-Parser, Logger, Database

**Feature:** 2.3 (MUST)

**Implementiert:**
```
src/modules/imap/src/Manager/
├── ThreadManagerInterface.php       # ✅ 46 lines - Interface
├── ThreadManager.php                # ✅ 212 lines - Threading-Algorithmus
└── ThreadStructure.php              # ✅ 71 lines - Thread DTO

src/app/Services/
└── ThreadService.php                # ✅ 203 lines - Business Logic

src/app/Repositories/
├── ThreadRepository.php             # ✅ 145 lines - Thread DB Operations
└── EloquentEmailRepository.php      # ✅ ~220 lines - Email DB Operations

src/modules/imap/tests/
└── thread-manager-integration-test.php  # ✅ 234 lines - E2E Test

**Total:** ~1,100 lines of code
```

**Threading-Algorithmus:**
1. **In-Reply-To Header** (Höchste Priorität) - Direkte Antwort-Beziehung
2. **References Header** (Mittlere Priorität) - Thread-Chain über Message-IDs
3. **Subject + 30-Day Window** (Niedrigste Priorität) - Subject-Normalisierung (Re:, Fwd:, AW: entfernt)
4. **Neuer Thread** - Wenn keine Matches

**Test-Ergebnis:** ✅ 8 E-Mails erfolgreich verarbeitet und in Threads gruppiert

---

### M1 Deliverables & Success Criteria ✅ ACHIEVED

**Deliverables:**
- ✅ IMAP-Client funktioniert standalone (Mercury localhost + production-ready)
- ✅ Email-Parser extrahiert alle relevanten Daten (Headers, Body, Attachments)
- ✅ Thread-Manager gruppiert E-Mails intelligent zu Konversationen
- ✅ Integration Tests grün (IMAP: Mercury Round-Trip, Parser: 8 E-Mails, Threading: 8 E-Mails)
- ✅ Repositories für Threads und Emails implementiert
- ✅ BONUS: Production Setup-Wizard mit Auto-Discovery

**Success Criteria:**
- ✅ KI kann IMAP-Module **unabhängig** weiterentwickeln
- ✅ E-Mails können von beliebigem IMAP-Server abgerufen werden
- ✅ Parsing unterstützt HTML, Plain Text, Attachments
- ✅ Threading gruppiert zusammengehörige E-Mails korrekt
- ✅ Alle Daten werden in Database persistiert (threads, emails Tabellen)
- ✅ Setup-Wizard automatisiert IMAP-Account-Konfiguration

**Gesamtdauer M1:** ~3 Tage (vs. geschätzt 2 Wochen) 🚀

**Key Achievements:**
- ✅ 18 IMAP-Client-Methoden (14 Core + 4 Keywords)
- ✅ Production-tested (Mercury + webhoster.ag)
- ✅ Graceful Degradation proven
- ✅ Setup-Wizard mit Certificate Auto-Discovery
- ✅ ~4,200 lines production code + ~2,800 lines tests
- ✅ DB = SSOT Architecture Pattern established

---

### Sprint 1.4: Label-Manager ✅ COMPLETED (~2h)
**Status:** ✅ COMPLETED | **Dokument:** `[COMPLETED] M1-Sprint-1.4-Label-Manager.md`

---

### Sprint 1.5: Webcron-Polling-Dienst ✅ COMPLETED (~3h)
**Status:** ✅ COMPLETED | **Dokument:** `[COMPLETED] M1-Sprint-1.5-Webcron-Polling-Dienst.md`

**Abhängigkeit:** M0 (Logger, Config), M1.1 (IMAP-Client), M1.2 (Email-Parser), M1.3 (Thread-Manager)

**Feature:** 2.3 - Webcron-Polling-Dienst (MUST), 5.2 - Webcron-Authentifizierung (MUST)

**Implementiert:**
```
src/modules/webcron/
├── src/
│   ├── WebcronManagerInterface.php      # ✅ 68 lines
│   ├── WebcronManager.php               # ✅ 265 lines
│   └── Exceptions/
│       └── WebcronException.php         # ✅ 50 lines
├── config/
│   └── webcron.config.php               # ✅ 60 lines
└── README.md                            # ✅ 500+ lines

src/routes/
└── webcron.php                          # ✅ 209 lines (3 endpoints)

src/app/Controllers/
└── ImapController.php                   # ✅ syncAccount() method

tests/manual/
└── webcron-poll-test.php                # ✅ 250 lines

**Total:** ~800 lines of code
```

**Features:**
- Webcron-Orchestration via Internal API (calls ImapController::syncAccount)
- API Key + IP Whitelist Authentication
- Job Locking (prevents parallel execution)
- Status Tracking & Monitoring
- Aggregated Results & Error Handling
- External Cron Integration (cron-job.org, cronjob.de)

**API Endpoints (3):**
- GET /webcron/poll?api_key=xxx - Trigger polling for all accounts
- GET /webcron/status - Get job status
- GET /webcron/test - Test setup without fetching emails

**Test-Ergebnis:** ✅ 6/6 Tests erfolgreich
- API Key Authentication works
- IP Whitelist works
- Job Locking prevents parallel execution
- Internal API calls successful
- Status tracking accurate
- Error handling robust

**Abhängigkeit:** Database, Logger

**Feature:** 3.1 - Label-System (MUST)

**Implementiert:**
```
src/modules/label/
├── src/
│   ├── LabelManagerInterface.php    # ✅ 157 lines
│   ├── LabelManager.php             # ✅ 366 lines
│   └── Exceptions/
│       └── LabelException.php       # ✅ 77 lines
├── config/
│   └── label.config.php             # ✅ 135 lines
├── tests/
│   └── label-integration-test.php   # ✅ 291 lines
└── README.md                        # ⏳ TODO

src/app/Services/
└── LabelService.php                 # ✅ 386 lines

src/app/Repositories/
└── LabelRepository.php              # ✅ 276 lines

**Total:** ~1,688 lines of code
```

**Features:**
- 7 System-Labels (Inbox, Sent, Drafts, Trash, Spam, Starred, Archive)
- Custom Labels mit Farben (12 Standard-Farben)
- Thread-Label Zuweisungen (Single & Batch)
- Label-Filterung
- System-Label-Schutz (keine Löschung)
- Label-Statistiken

**Test-Ergebnis:** ✅ 12/12 Test-Schritte erfolgreich
- System-Labels initialisiert
- Custom Labels erstellt und gelöscht
- Thread-Tagging funktioniert
- Label-Filterung funktioniert
- System-Label-Schutz aktiv

---

### M1 Deliverables & Success Criteria ✅ ACHIEVED

**Deliverables:**
- ✅ IMAP-Client funktioniert standalone (Mercury localhost + production-ready)
- ✅ Email-Parser extrahiert alle relevanten Daten (Headers, Body, Attachments)
- ✅ Thread-Manager gruppiert E-Mails intelligent zu Konversationen
- ✅ Label-Manager organisiert Threads mit System- und Custom-Labels
- ✅ Webcron-Polling-Dienst automatisiert E-Mail-Abruf
- ✅ Integration Tests grün (IMAP, Parser, Threading, Labels, Webcron)
- ✅ Repositories für Threads, Emails, Labels implementiert
- ✅ BONUS: Production Setup-Wizard mit Auto-Discovery

**Success Criteria:**
- ✅ KI kann IMAP-Module **unabhängig** weiterentwickeln
- ✅ E-Mails können von beliebigem IMAP-Server abgerufen werden
- ✅ Parsing unterstützt HTML, Plain Text, Attachments
- ✅ Threading gruppiert zusammengehörige E-Mails korrekt
- ✅ Labels organisieren Threads nach Kategorien
- ✅ Automatisches Polling via Webcron funktioniert
- ✅ Alle Daten werden in Database persistiert
- ✅ Setup-Wizard automatisiert IMAP-Account-Konfiguration
- ✅ Strikte Modul-Trennung eingehalten (basics.txt)

**Gesamtdauer M1:** ~11 Stunden (vs. geschätzt 2 Wochen) 🚀

**M1 komplett abgeschlossen (inkl. Webcron-Polling)!** 🎉. Webcron-Polling)!** 🎉

**Interface:**
```php
interface ImapClientInterface {
    public function connect(string $host, int $port, string $username, string $password, bool $ssl = true): bool;
    public function disconnect(): void;
    public function getFolders(): array;
    public function selectFolder(string $folder): void;
    public function getMessageCount(): int;
    public function getMessages(int $limit = 100, bool $unreadOnly = false): array;
    public function getMessage(string $uid): ImapMessageInterface;
    public function moveMessage(string $uid, string $targetFolder): bool;
    public function deleteMessage(string $uid): bool;
    public function markAsRead(string $uid): bool;
}

interface ImapMessageInterface {
    public function getUid(): string;
    public function getMessageId(): string;
    public function getInReplyTo(): ?string;
    public function getSubject(): string;
    public function getFrom(): array; // ['email' => '...', 'name' => '...']
    public function getTo(): array;
    public function getDate(): \DateTime;
    public function getBodyText(): string;
    public function getBodyHtml(): string;
    public function getAttachments(): array;
    public function getRawHeaders(): string;
}
```

**Standalone-Test:**
```bash
php scripts/test-imap-connection.php

# Input (interaktiv):
# Host: imap.example.com
# Port: 993
# Username: info@example.com
# Password: ******

# Output:
# ✅ Connected to imap.example.com:993
# ✅ Folders: INBOX (15 messages), Sent (42), Trash (3)
# ✅ Selected INBOX
# ✅ Fetched 15 messages
# 
# Message 1:
#   UID: 12345
#   Subject: "Test-Mail"
#   From: test@example.com
#   Date: 2025-11-17 10:30
#   Attachments: 1 (document.pdf, 245 KB)
```

**Deliverables:**
- [ ] IMAP-Client funktioniert mit echtem IMAP-Server
- [ ] Alle IMAP-Operationen testbar (read, move, delete)
- [ ] Error-Handling für: Timeout, Auth-Fehler, SSL-Fehler

---

### Sprint 1.2: E-Mail-Parser (2 Tage)
**Abhängigkeit:** IMAP-Client

**Feature:** 2.5 (MUST)

**Komponenten:**
```
src/modules/imap/src/
├── Parser/
│   ├── EmailParser.php          # Main Parser
│   ├── HeaderParser.php         # Parse Headers
│   ├── BodyParser.php           # Parse Text/HTML
│   ├── AttachmentParser.php     # Extract Attachments
│   └── ThreadingParser.php      # Message-ID, In-Reply-To
└── Sanitizer/
    ├── HtmlSanitizer.php        # XSS-Protection (HTML Purifier)
    └── TextSanitizer.php        # Plain-Text Cleanup
```

**Interface:**
```php
interface EmailParserInterface {
    public function parse(ImapMessageInterface $message): ParsedEmailDTO;
}

class ParsedEmailDTO {
    public string $uid;
    public string $messageId;
    public ?string $inReplyTo;
    public string $subject;
    public array $from; // ['email', 'name']
    public array $to;
    public \DateTime $date;
    public string $bodyText;        // Sanitized
    public string $bodyHtml;        // Sanitized (XSS-safe)
    public array $attachments;      // [AttachmentDTO, ...]
    public array $headers;          // Raw Headers (für Threading)
}

class AttachmentDTO {
    public string $filename;
    public string $mimeType;
    public int $size;
    public string $content;         // Base64 oder Binary
}
```

**Standalone-Test:**
```bash
php tests/manual-test-email-parser.php

# Input: Raw EML-File (test-email.eml)
# Output:
# ✅ Parsed Email:
#   Subject: "Re: Anfrage zu Projekt X"
#   Thread: Message-ID <abc@example.com> → In-Reply-To <xyz@example.com>
#   From: Max Mustermann <max@example.com>
#   Body-Text: 245 chars (sanitized)
#   Body-HTML: 1.2 KB (XSS-safe, <script> removed)
#   Attachments: 2
#     - document.pdf (245 KB, application/pdf)
#     - image.jpg (89 KB, image/jpeg)
```

**Deliverables:**
- [ ] Parser funktioniert mit realen E-Mails (mit/ohne Attachments)
- [ ] HTML-Sanitization (XSS-safe)
- [ ] Threading-Detection (Message-ID → Thread-Zuordnung)

---

**Note:** Sprint 1.3 (Thread-Manager), Sprint 1.4 (Label-Manager), and Sprint 1.5 (Webcron-Polling-Dienst) are documented above in the COMPLETED sections.

---

## M2: Thread API (Woche 5) 🔄 IN PROGRESS

**Status:** 🔄 **IN PROGRESS** (18. November 2025)  
**Tatsächliche Dauer:** ~4 Stunden (Sprint 2.1)

**Ziel:** REST API für Thread-Management mit Advanced Operations. KI kann API-Endpunkte **unabhängig testen** (ohne UI).

### Features (aus `inventar.md`):
- ✅ **F1.1** - Thread-Management (MUST) - M2.1
- ⏳ **F2.2** - SMTP Integration (MUST) - M2.2
- ⏳ **F2.3** - Webhook Integration (SHOULD) - M2.3

---

### Sprint 2.1: Thread-Management-API ✅ COMPLETED (~4h)
**Status:** ✅ COMPLETED (18. November 2025) | **Dokument:** `[WIP] M2-Sprint-2.1-Thread-Management-API.md`

**Implementiert:** 10 API Endpoints (6 Basic CRUD + 4 Advanced Operations), Repository Pattern, Service Layer, Transaction Safety

**Test-Ergebnis:** ✅ 11/11 Tests erfolgreich

Siehe vollständige Dokumentation: `docs/dev/[WIP] M2-Sprint-2.1-Thread-Management-API.md`

---

### Sprint 2.2: Email-Send-API ✅ COMPLETED (~3h)
**Status:** ✅ COMPLETED (18. Nov 2025) | **Dokument:** `[WIP] M2-Sprint-2.2-Email-Send-API.md`

**Abhängigkeit:** M0, M1, M2.1, PHPMailer ✅

**Features:** F2.2 - SMTP Integration (MUST), F2.1 - Shared-Inbox Response (MUST)

**Implementiert (~950 lines):**
- ✅ SMTP Module: SmtpClientInterface + PHPMailerSmtpClient (293 lines)
- ✅ EmailSendService: Send/Reply/Forward Logic (279 lines)
- ✅ EmailController: 3 API Endpoints (159 lines)
- ✅ Test Scripts: smtp-test.php + email-send-test.php (169 lines)
- ✅ Routes, Container, Config extensions (+61 lines)

**API Endpunkte (3):**
- POST /api/emails/send - Send new email ✅
- POST /api/threads/{id}/reply - Reply to thread (preserves threading) ✅
- POST /api/threads/{id}/forward - Forward thread emails ✅

**Test-Ergebnis:**
- ✅ SMTP Connection erfolgreich (localhost:25)
- ✅ Email-Validierung funktioniert (PHPMailer)
- ✅ Logging integriert (alle Operationen)
- ⚠️  Mercury Relay-Restriction verhindert externe Tests (Security korrekt)

**TODO für später:** Email-Signatur-Feature (USER_SETTINGS, SIGNATURE_EDITOR)

See full documentation: `docs/dev/[COMPLETED] M2-Sprint-2.2-Email-Send-API.md`

---

### Sprint 2.3: Webhook-Integration (~2.5 Stunden) ✅ COMPLETED
**Abhängigkeit:** M2.1, M2.2  
**Abgeschlossen:** 18. November 2025

**Feature:** F2.3 - Webhook Integration (SHOULD)

**Implementierung:**
```
database/migrations/
└── 009_create_webhooks_table.php        # 80 lines

src/app/Models/
├── Webhook.php                          # 97 lines
└── WebhookDelivery.php                  # 82 lines

src/app/Services/
└── WebhookService.php                   # 318 lines
    ├── dispatch(event, payload)         # Event zu Webhooks senden
    ├── register(data)                   # Webhook mit Secret-Generation
    ├── update/delete/retry              # Management
    ├── getDeliveries()                  # History
    └── HMAC SHA256 Signatures           # Security

src/app/Controllers/
└── WebhookController.php                # 366 lines (7 REST endpoints)

src/app/Services/ (Integration)
├── ThreadApiService.php                 # +40 lines (4 events)
└── EmailSendService.php                 # +13 lines (1 event)

src/routes/api.php                       # +55 lines (7 routes)
src/config/container.php                 # +25 lines (DI)

tests/manual/
└── webhook-test.php                     # 195 lines (8 tests)

**Total:** ~1,270 lines
```

**API Endpunkte (7 neue):**
- POST /api/webhooks - Register webhook
- GET /api/webhooks - List webhooks (pagination)
- GET /api/webhooks/{id} - Get webhook details
- PUT /api/webhooks/{id} - Update webhook
- DELETE /api/webhooks/{id} - Delete webhook
- GET /api/webhooks/{id}/deliveries - Delivery history
- POST /api/webhooks/deliveries/{id}/retry - Retry failed delivery

**Events (6 types):**
- `thread.created` - Neuer Thread
- `thread.updated` - Thread-Änderungen
- `thread.deleted` - Thread gelöscht
- `email.received` - Neue Email (IMAP)
- `email.sent` - Email gesendet (SMTP)
- `note.added` - Note hinzugefügt

**Features:**
- ✅ Webhook registration via API
- ✅ Event dispatch on thread/email operations
- ✅ Retry logic on failure (max 3 attempts)
- ✅ HMAC SHA256 signature validation
- ✅ Delivery history tracking
- ✅ Auto-disable after 10 failures
- ✅ Event filtering (subscribed events only)
- ✅ Optional integration (null-safe dependencies)

**Test-Ergebnis:**
```bash
php tests/manual/webhook-test.php

# Output:
TEST 1: Register webhook
✅ Webhook registered successfully
   Secret: 275670f9a4d4c02741e23b2c29dd6674f1197a051345278cc58d110c301418cb
   Events: thread.created, thread.updated, email.sent

TEST 3: Dispatch test event
✅ Event dispatched
   Headers: X-Webhook-Signature, X-Webhook-Event

TEST 5: Test with real thread
✅ Thread created: ID 47
✅ Webhook dispatched for real thread

TEST 6: Event filtering
✅ Unsubscribed event did NOT trigger webhook

TEST 7: Update webhook
✅ Webhook deactivated
✅ Inactive webhook did NOT trigger
```

See full documentation: `docs/dev/[COMPLETED] M2-Sprint-2.3-Webhook-Integration.md`

---

### M2 Deliverables & Success Criteria

**Deliverables:**
- ✅ Thread-Management-API mit 10 Endpunkten (Sprint 2.1)
- ✅ Email-Send-API mit SMTP Integration (Sprint 2.2)
- ✅ Webhook-Integration für externe Systeme (Sprint 2.3)

**Success Criteria:**
- ✅ KI kann Thread-API **unabhängig testen** (ohne UI)
- ✅ Advanced Thread Operations functional (split, merge, move)
- ✅ SMTP Infrastructure implementiert (send, reply, forward)
- ✅ Layer Abstraction (SmtpClientInterface austauschbar)
- ✅ System Notes dokumentieren alle Änderungen
- ✅ Transaction Safety bei komplexen Operationen
- ✅ Logging aller Operationen
- ✅ Webhook Events für externe Integrations
- ✅ HMAC Security für Webhook-Authentifizierung

**Geschätzte Gesamtdauer M2:** ~10 Tage  
**Tatsächliche Dauer M2:** ~9.5 Stunden (Sprint 2.1: 4h ✅ | Sprint 2.2: 3h ✅ | Sprint 2.3: 2.5h ✅)

**Status:** ✅ **M2 VOLLSTÄNDIG ABGESCHLOSSEN** - Alle 3 Sprints completed!

---

## M3: MVP UI (Woche 6-7) 🎨 ✅ COMPLETED

**Status:** ✅ **95% COMPLETED** (6. Dezember 2025)  
**Tatsächliche Dauer:** ~2 Wochen (vs. geschätzt 2 Wochen) - Im Zeitplan! 🎯

**Ziel:** Minimales funktionsfähiges Frontend für grundlegende Inbox-Operationen.

**Abhängigkeiten:** M0 ✅, M1 ✅, M2 ✅

**Features:**
- ✅ **F3.1** - User Authentication (Login/Logout)
- ✅ **F3.2** - Inbox View (Thread-Liste mit Filtern)
- ✅ **F3.3** - Thread Detail View (Email-Historie)
- ✅ **F3.4** - Email Composer (Send, Reply, Forward)
- ✅ **F3.5** - Basic Actions (Mark Read, Archive, Delete)
- ✅ **F3.6** - Label Management (Assign/Remove Labels)
- ✅ **BONUS** - Error Handling & User Feedback System
- ✅ **BONUS** - Accessibility (WCAG 2.1 Level AA)
- ✅ **BONUS** - Loading States & Spinners
- ✅ **BONUS** - Admin Features (System Health, Backup Management)

**Features:** 1.1, 1.4 (MUST)

**Komponenten:**
```
src/app/Models/
├── Thread.php                   # Eloquent Model
├── Email.php
├── InternalNote.php
└── User.php

src/app/Repositories/
├── Interfaces/
│   ├── ThreadRepositoryInterface.php
│   ├── EmailRepositoryInterface.php
│   └── NoteRepositoryInterface.php
└── Eloquent/
    ├── EloquentThreadRepository.php
    ├── EloquentEmailRepository.php
    └── EloquentNoteRepository.php

src/app/Services/
├── ThreadService.php            # Business Logic
└── DTO/
    ├── ThreadDTO.php
    └── CreateThreadDTO.php
```

**Interface (Repository Pattern - Layer-Abstraktion!):**
```php
interface ThreadRepositoryInterface {
    public function findById(int $id): ?Thread;
    public function findByUid(string $uid): ?Thread;
    public function findAll(array $filters = []): Collection;
    public function create(CreateThreadDTO $data): Thread;
    public function update(int $id, array $data): Thread;
    public function delete(int $id): bool;
}

interface ThreadServiceInterface {
    public function createThread(string $subject, string $senderEmail, string $firstEmailUid): Thread;
    public function getOrCreateThread(string $messageId, ?string $inReplyTo): Thread;
    public function assignThread(int $threadId, int $userId): bool;
    public function changeStatus(int $threadId, string $newStatus): bool;
    public function addNote(int $threadId, int $userId, string $noteText): InternalNote;
    public function getThreadWithEmails(int $threadId): ThreadDTO;
}
```

**Standalone-Test:**
```bash
php tests/manual-test-thread-service.php

# Test 1: Create Thread
# ✅ Thread created: ID=1, UID=thread_abc123
# ✅ Status: new
# ✅ Assigned: null

# Test 2: Assign Thread
# ✅ Thread assigned to User ID=1
# ✅ Status changed: new → assigned
# ✅ Activity logged: "Thread assigned to Max Mustermann"

# Test 3: Add Note
# ✅ Note added: "Bitte bis Freitag antworten"
# ✅ Author: Max Mustermann
# ✅ Timestamp: 2025-11-17 14:30

# Test 4: Change Status
# ✅ Status changed: assigned → in_progress
# ✅ Activity logged: "Status changed by Max Mustermann"
```

**Deliverables:**
- [ ] Thread-Service funktioniert komplett (ohne UI)
- [ ] Repository-Pattern implementiert (Abstraction Layer!)
- [ ] Alle CRUD-Operationen testbar

---

### Sprint 2.2: Threading-Logik (2 Tage)
**Abhängigkeit:** Thread-Service, E-Mail-Parser

**Feature:** 1.1 (MUST) - Automatische Gruppierung

**Komponenten:**
```
src/app/Services/
└── ThreadingService.php         # Thread-Matching-Logik
```

**Logik:**
```
1. Neue E-Mail kommt rein (via Webcron)
2. Parse Message-ID, In-Reply-To, References
3. Suche existierenden Thread:
   a) In-Reply-To vorhanden? → Thread mit dieser Message-ID
   b) Betreff-Matching: "Re: Original Betreff" → Thread mit Original
   c) Sonst: Neuen Thread erstellen
4. E-Mail dem Thread zuordnen
5. Thread-Timestamps aktualisieren (last_activity_at)
```

**Interface:**
```php
interface ThreadingServiceInterface {
    public function assignEmailToThread(ParsedEmailDTO $email): Thread;
    public function findThreadByMessageId(string $messageId): ?Thread;
    public function findThreadBySubject(string $subject): ?Thread;
}
```

**Standalone-Test:**
```bash
php tests/manual-test-threading.php

# Test-Szenario: 3 E-Mails in Konversation
# 
# E-Mail 1: "Anfrage zu Projekt X"
#   Message-ID: <msg1@example.com>
#   In-Reply-To: null
# ✅ New Thread created: ID=1
# 
# E-Mail 2: "Re: Anfrage zu Projekt X"
#   Message-ID: <msg2@example.com>
#   In-Reply-To: <msg1@example.com>
# ✅ Assigned to Thread ID=1 (via Message-ID match)
# 
# E-Mail 3: "Re: Anfrage zu Projekt X"
#   Message-ID: <msg3@example.com>
#   In-Reply-To: <msg2@example.com>
# ✅ Assigned to Thread ID=1 (via Message-ID chain)
# 
# Result:
# Thread ID=1 contains 3 emails
# ✅ Threading successful!
```

**Deliverables:**
- [ ] Threading funktioniert mit realen E-Mail-Konversationen
- [ ] Message-ID-basiertes Matching
- [ ] Fallback: Betreff-Matching

---

### Sprint 2.3: Activity-Log Integration (1 Tag)
**Abhängigkeit:** Thread-Service, Logger

**Feature:** 6.2 (MUST)

**Logik:**
Alle kritischen Aktionen automatisch in `activity_log` speichern:
- Thread zugewiesen
- Status geändert
- Notiz hinzugefügt
- E-Mail gesendet
- Thread transferiert

**Integration in Thread-Service:**
```php
public function assignThread(int $threadId, int $userId): bool {
    // ... assign logic ...
    
    // Activity Log
    $this->logger->info('Thread assigned', [
        'thread_id' => $threadId,
        'user_id' => $userId
    ]);
    
    $this->activityLog->create([
        'user_id' => $userId,
        'action' => 'thread_assigned',
        'entity_type' => 'threads',
        'entity_id' => $threadId,
        'details' => json_encode(['assigned_to' => $userId])
    ]);
    
    return true;
}
```

**Standalone-Test:**
```bash
php tests/manual-test-activity-log.php

# Test: Activity-Tracking
# Action 1: Assign Thread
# ✅ Activity logged: thread_assigned (User: Max, Thread: 1)
# 
# Action 2: Change Status
# ✅ Activity logged: status_changed (new → assigned)
# 
# Action 3: Add Note
# ✅ Activity logged: note_added (Author: Max)
# 
# Query: Get Thread History
# ✅ Found 3 activities for Thread 1:
#   1. 2025-11-17 14:30 - thread_assigned by Max
#   2. 2025-11-17 14:31 - status_changed to assigned
#   3. 2025-11-17 14:32 - note_added by Max
```

---

### M2 Deliverables & Success Criteria

**Deliverables:**
- [ ] Thread-Service funktioniert komplett (CLI-testbar)
- [ ] Threading-Logik ordnet E-Mails korrekt zu
- [ ] Activity-Log trackt alle Aktionen
- [ ] Integration-Test: "Poll → Thread → Assign → Status" komplett

**Success Criteria:**
- ✅ KI kann Thread-Engine **ohne UI entwickeln und testen**
- ✅ 100% Nachvollziehbarkeit (Activity-Log)
- ✅ Repository-Pattern ermöglicht spätere DB-Migration (MongoDB, etc.)

---

## M3: MVP UI (Woche 7-8) 🎨 ✅ COMPLETED

**Status:** ✅ **95% COMPLETED** (6. Dezember 2025)  
**Tatsächliche Dauer:** ~2 Wochen  
**Abgeschlossen:** Phase 1-4 (✅), Phase 5 (95% ✅)

**Ziel:** Minimales funktionsfähiges Frontend für grundlegende Inbox-Operationen mit Production-Ready Features.

### Implementierte Features (100% ✅)

#### Core UI Components ✅
- ✅ **F3.1** - User Authentication (Login/Logout) - Session-based
- ✅ **F3.2** - Inbox View (Thread-Liste mit Filtern, Sortierung, Multi-Select)
- ✅ **F3.3** - Thread Detail View (Email-Historie, Attachments, Internal Notes)
- ✅ **F3.4** - Email Composer (Send, Reply, Forward mit Rich-Text)
- ✅ **F3.5** - Basic Actions (Mark Read, Archive, Delete, Assign, Labels)
- ✅ **F3.6** - Label Management (Assign/Remove Labels, Color-Coding)

#### Production-Ready Features ✅
- ✅ **Error Handling System** - Centralized error handling mit user feedback
- ✅ **Accessibility (WCAG 2.1 AA)** - Screen reader support, keyboard navigation
- ✅ **Loading States** - Unified loading indicators und spinners
- ✅ **Toast Notifications** - Success/Error/Warning feedback
- ✅ **Admin Features** - System Health Monitor, Backup Management
- ✅ **Dark Mode** - Theme switcher mit persistence
- ✅ **Keyboard Shortcuts** - Ctrl+E Composer, Arrow navigation
- ✅ **User Onboarding** - Interactive tour for new users

### Implementierte Struktur

```
src/public/
├── inbox.php                        # ✅ Main Dashboard
├── login.php                        # ✅ Auth View
├── settings.php                     # ✅ User Settings
├── admin-settings.php               # ✅ Admin Panel
├── system-health.php                # ✅ Health Monitor
├── backup-management.php            # ✅ Backup Manager
└── user-management.php              # ✅ User Admin

src/public/assets/css/               # ✅ ITCSS Architecture (38 files)
├── 1-settings/_variables.css        # ✅ Design Tokens
├── 3-generic/_reset.css             # ✅ CSS Reset
├── 4-elements/                      # ✅ Typography, Forms
├── 5-objects/                       # ✅ Layout Grid
├── 6-components/                    # ✅ 30+ Components
│   ├── _button.css
│   ├── _modal.css
│   ├── _thread-list.css
│   ├── _thread-detail.css
│   ├── _email-composer.css
│   ├── _toast.css                   # ✅ NEW
│   ├── _loading-states.css          # ✅ NEW
│   └── ...
└── 7-utilities/
    ├── _utilities.css
    └── _accessibility.css           # ✅ NEW

src/public/assets/js/modules/       # ✅ Modular Architecture
├── error-handler.js                 # ✅ NEW - 373 lines
├── accessibility.js                 # ✅ NEW - 427 lines
├── loading-state-manager.js         # ✅ NEW - 382 lines
├── api-client.js                    # ✅ REST API Client
├── ui-components.js                 # ✅ Dialogs, Pickers, Toasts
├── thread-renderer.js               # ✅ Thread List Rendering
├── inbox-manager.js                 # ✅ Inbox State Management
├── keyboard-shortcuts.js            # ✅ Keyboard Navigation
└── user-onboarding.js               # ✅ Interactive Tour

**Total:** ~2,500 lines of production CSS + ~3,500 lines of JavaScript
```

### Phase-by-Phase Progress

#### Phase 1: Foundation (~2-3h) ✅ COMPLETED
- ✅ CSS-Architektur aufsetzen (ITCSS-Struktur)
- ✅ Design Tokens definieren (_variables.css mit 120+ Variablen)
- ✅ Base styles (reset, typography, forms)
- ✅ Layout-System (header, sidebar, main content grid)

#### Phase 2: Core Components (~3-4h) ✅ COMPLETED
- ✅ Button component (primary, secondary, danger, icon, loading states)
- ✅ Input/Form components (text, select, checkbox, validation)
- ✅ Badge component (status indicators mit colors)
- ✅ Label tag component (filterable, color-coded)
- ✅ Thread list item component (unread state, multi-select, metadata)

#### Phase 3: Views (~4-5h) ✅ COMPLETED
- ✅ Login view (responsive, dark mode)
- ✅ Inbox view (thread list mit filters, sorting, bulk actions)
- ✅ Thread detail view (email history, attachments, notes)
- ✅ Email composer (rich text editor, templates, signatures)

#### Phase 4: Interactions (~2-3h) ✅ COMPLETED
- ✅ Sidebar toggle (mobile responsive)
- ✅ Thread selection (single + multi-select)
- ✅ Composer modal/view (dynamic loading)
- ✅ Form validation (inline errors, accessibility)
- ✅ Loading states (basic implementation)

#### Phase 5: Polish (~2h) 🔄 95% COMPLETED
- ✅ Error Handling (centralized with ErrorHandler module)
- ✅ Accessibility (WCAG 2.1 AA - ARIA, keyboard nav, screen reader)
- ✅ Loading States (unified LoadingStateManager)
- ✅ Toast Notifications (verified existing implementation)
- ⚠️ Responsive refinements (90% - minor mobile polish needed)
- ⚠️ Performance optimization (60% - bundling optional for M3.1)

### M3 Deliverables & Success Criteria ✅ ACHIEVED

**Deliverables:**
- ✅ Alle UI-Views funktional (Login, Inbox, Thread Detail, Composer)
- ✅ CSS-Architektur production-ready (ITCSS + BEM, 38 files)
- ✅ JavaScript modular und wartbar (11 modules)
- ✅ Error handling & user feedback (ErrorHandler + Toasts)
- ✅ Accessibility compliant (WCAG 2.1 AA)
- ✅ Loading states unified (LoadingStateManager)
- ✅ Admin features (System Health, Backup Management)
- ✅ Dark mode support (Theme switcher)
- ✅ Mobile responsive (90% - minor polish needed)

**Success Criteria:**
- ✅ **Workflow A komplett funktionsfähig** (Use Case 1 aus `vision.md`)
- ✅ **Workflow B komplett funktionsfähig** (Use Case 2 aus `vision.md`)
- ✅ Team kann System im Testbetrieb nutzen (3-5 User)
- ✅ Keine doppelte Bearbeitung mehr (Thread Assignment)
- ✅ 100% Nachvollziehbarkeit (Activity Log + Audit Trail)
- ✅ Production-Ready Code (Clean, documented, maintainable)
- ✅ Developer-Friendly (Integration guides, examples)

### Key Achievements 🎉

1. **Centralized Error Handling** - ErrorHandler module mit automatic error type detection
2. **Full Accessibility** - ARIA live regions, focus management, keyboard navigation
3. **Unified Loading States** - LoadingStateManager mit spinners, overlays, progress bars
4. **Production-Ready UI** - Clean, maintainable, documented codebase
5. **Developer Resources** - Integration guides und examples für neue Features

### Lessons Learned (für M4+)

1. **Cache-Busting Strategy** - ✅ Fixed mit centralized `asset_version()` function
2. **Loading States von Anfang an** - ✅ Unified LoadingStateManager implemented
3. **Error Handling vor API-Integration** - ✅ Centralized ErrorHandler module
4. **Accessibility kontinuierlich** - ✅ Accessibility module mit auto-init
5. **Performance-Tests früh** - ⚠️ Bundling kann in M3.1 nachgeholt werden

### Remaining Work (Optional for M3.1)

1. **Performance Optimization** (NICE TO HAVE)
   - CSS/JS bundling mit PostCSS + esbuild
   - Minification & compression
   - File hash-based cache busting (vs. timestamp)
   - Lazy loading für non-critical modules

2. **Mobile UX Final Polish** (MINOR)
   - Sidebar overlay refinement (functional but can be smoother)
   - Touch gesture optimization
   - Mobile-specific interactions

**Geschätzte Zeit für Remaining Work:** ~1-2 Tage (nicht blocking für M4)

**Gesamtdauer M3:** ~2 Wochen (im Zeitplan!) 🚀

**Status:** ✅ **M3 kann als COMPLETED betrachtet werden** - Production-Ready!

---

## M3: MVP UI (Woche 7-8) 🎨 (OLD SECTION - TO BE REMOVED)

**Ziel:** Minimales Frontend für Workflow A & B. Fokus: **Funktionalität, nicht Design**.

### Features (aus `inventar.md`):
- **3.1** - Authentifizierung (MUST)
- **4.1** - Posteingangs-Übersicht (MUST)
- **4.2** - Thread-Detailansicht (MUST)
- **4.3** - Aktions-Panel (MUST)
- **4.4** - UI-Polling (MUST)
- **4.5** - Antwort-Formular (MUST)
- **2.6** - E-Mail-Senden (MUST)

---

### Sprint 3.1: Auth-Modul (2 Tage)
**Feature:** 3.1 (MUST)

**Komponenten:**
```
src/app/Services/
└── AuthService.php

src/app/Controllers/
└── AuthController.php

src/app/Middleware/
└── AuthMiddleware.php

src/views/
├── login.php
└── layouts/
    └── auth-layout.php
```

**Interface:**
```php
interface AuthServiceInterface {
    public function login(string $username, string $password): ?User;
    public function logout(): void;
    public function getCurrentUser(): ?User;
    public function isAuthenticated(): bool;
}
```

**Standalone-Test:**
```bash
# Browser: http://localhost:8000/login
# Input: Username=test, Password=test123
# ✅ Login successful
# ✅ Session created
# ✅ Redirect to /inbox

# Browser: http://localhost:8000/inbox (ohne Login)
# ✅ Redirect to /login (Auth-Middleware)
```

---

### Sprint 3.2: Inbox-Übersicht (2 Tage)
**Features:** 4.1, 4.4 (MUST)

**Komponenten:**
```
src/app/Controllers/
└── InboxController.php          # GET /inbox

src/views/
├── inbox/
│   └── index.php                # Thread-Liste
└── partials/
    └── thread-row.php           # Single Thread-Zeile

src/public/js/
└── inbox-poller.js              # 15s Polling via Fetch API
```

**UI-Polling-Logik:**
```javascript
// inbox-poller.js
setInterval(async () => {
    const response = await fetch('/api/inbox/new-threads');
    const data = await response.json();
    
    if (data.new_threads > 0) {
        showNotification(`${data.new_threads} neue E-Mails`);
        reloadInboxList();
    }
}, 15000); // 15 Sekunden
```

**Standalone-Test:**
```bash
# Browser: http://localhost:8000/inbox
# ✅ Thread-Liste angezeigt:
#   - "Anfrage zu Projekt X" | Max Mustermann | Assigned | 10:30
#   - "Meeting nächste Woche" | Unassigned | New | 11:45
#   - "Bewerbung" | Anna Schmidt | In Progress | 13:20
# 
# ✅ Filter: Status=New → 1 Thread
# ✅ Sortierung: Datum (neueste zuerst)
# 
# Nach 15 Sekunden:
# ✅ Neue E-Mail gepolt → Notification: "1 neue E-Mail"
# ✅ Liste aktualisiert automatisch
```

---

### Sprint 3.3: Thread-Detailansicht (2 Tage)
**Features:** 4.2, 4.3 (MUST)

**Komponenten:**
```
src/app/Controllers/
└── ThreadController.php         # GET /thread/{id}

src/views/
└── thread/
    ├── detail.php               # Thread-Ansicht
    ├── email-item.php           # Einzelne E-Mail
    ├── note-item.php            # Interne Notiz
    └── actions-panel.php        # Aktionen
```

**Aktions-Panel:**
- Mir zuweisen / Anderem zuweisen
- Status ändern (Dropdown)
- Notiz hinzufügen
- Antworten
- Transferieren (später in M4)

**Standalone-Test:**
```bash
# Browser: http://localhost:8000/thread/1
# ✅ Thread angezeigt:
#   Subject: "Anfrage zu Projekt X"
#   Status: New | Assigned to: Niemand
# 
# E-Mails:
#   1. Original-Mail (17.11. 10:30)
#      Von: max@example.com
#      Text: "Hallo, ich hätte eine Frage..."
#      Anhang: dokument.pdf (245 KB)
# 
# Interne Notizen:
#   (keine)
# 
# Aktionen:
#   [Mir zuweisen] [Status: In Progress ▼] [Notiz hinzufügen] [Antworten]
# 
# Test: "Mir zuweisen" klicken
# ✅ Thread assigned to Max
# ✅ Status: New → Assigned
# ✅ Activity-Log: "Assigned by Max Mustermann"
# ✅ UI aktualisiert automatisch
```

---

### Sprint 3.4: Antwort-Formular & E-Mail-Senden (2 Tage)
**Features:** 4.5, 2.6 (MUST)

**Komponenten:**
```
src/app/Controllers/
└── ReplyController.php          # POST /thread/{id}/reply

src/app/Services/
└── EmailSendService.php         # SMTP-Versand

src/views/
└── thread/
    └── reply-form.php           # Antwort-Editor

src/public/js/
└── quill-editor.js              # Rich-Text-Editor (Quill.js)
```

**Interface:**
```php
interface EmailSendServiceInterface {
    public function sendReply(
        Thread $thread,
        User $user,
        string $bodyHtml,
        string $fromEmail = 'info@' // Default: gemeinsame Adresse
    ): bool;
}
```

**Standalone-Test:**
```bash
# Browser: Thread-Detailansicht → [Antworten] klicken
# ✅ Editor öffnet sich (Quill.js)
# ✅ Original-Mail wird zitiert
# 
# Test: Antwort verfassen
# Input:
#   "Hallo Max,
#   
#   vielen Dank für deine Anfrage. Anbei die gewünschten Infos.
#   
#   Viele Grüße,
#   Das Team"
# 
# [Senden] klicken
# ✅ E-Mail gesendet von info@example.com
# ✅ SMTP: Headers korrekt (In-Reply-To, References)
# ✅ E-Mail in Thread gespeichert
# ✅ Status → In Progress
# ✅ Activity-Log: "Email sent by Max"
# ✅ Redirect zu Thread-Ansicht
```

---

### M3 Deliverables & Success Criteria

**Deliverables:**
- [ ] Login funktioniert
- [ ] Inbox-Übersicht zeigt Threads
- [ ] UI-Polling (15s) funktioniert
- [ ] Thread-Detailansicht komplett
- [ ] Antworten über info@ funktioniert
- [ ] Alle Workflow-A-Use-Cases erfolgreich

**Success Criteria:**
- ✅ **Workflow A komplett funktionsfähig** (Use Case 1 aus `vision.md`)
- ✅ **Workflow B komplett funktionsfähig** (Use Case 2 aus `vision.md`)
- ✅ Team kann System im Testbetrieb nutzen (3-5 User)
- ✅ Keine doppelte Bearbeitung mehr (Erfolgskriterium 1)
- ✅ 100% Nachvollziehbarkeit (Erfolgskriterium 2)

---

## M4: Beta (Woche 9-12) 🚀

**Ziel:** Workflow C (IMAP-Transfer), Mobile-Optimierung, Security-Härtung.

### Features (aus `inventar.md`):
- **2.2** - Sekundäre IMAP-Verbindung (SHOULD)
- **2.9** - E-Mail-Transfer (SHOULD)
- **3.3** - IMAP-Konto-Registrierung (SHOULD)
- **4.7** - Mobile-Optimierung (SHOULD)
- **2.8** - Anhang-Handling (SHOULD)
- **5.6** - Rate-Limiting (SHOULD)

**Details:** (Wird bei Bedarf ausgearbeitet)

---

## M5: v1.0 Production-Ready (Woche 13-16) 📋 PLANNED

**Ziel:** Performance, Testing, Dokumentation, Deployment.

### Features (aus `inventar.md`):
- **7.2** - Admin-Dokumentation (MUST)
- **7.3** - User-Dokumentation (SHOULD)
- **7.4** - Setup-Skripte (SHOULD)
- Performance-Optimierung
- Security-Audit
- Backup-Strategie

---

### Sprint 5.1: Performance-Optimierung (1 Woche)

**Performance-Metriken (Zielwerte):**
- ✅ Seitenladezeit < 2 Sekunden (Inbox-View)
- ✅ IMAP-Polling-Dauer < 30 Sekunden (für 100 Mails)
- ✅ Database-Queries < 50ms (Durchschnitt)
- ✅ API-Response-Time < 200ms (95th Percentile)

**Optimierungen:**
- Eloquent N+1 Query-Problem vermeiden (Eager Loading)
- Database-Indizes optimieren (threads.status, threads.assigned_to, emails.message_id)
- Config-Caching implementieren
- GZIP-Kompression im Webserver
- CDN für static assets (optional)

---

### Sprint 5.2: Security-Audit (2 Tage)

**Security-Checklist:**
- [ ] XSS-Tests (E-Mail-Body-Rendering mit HTMLPurifier)
- [ ] CSRF-Token in allen Forms
- [ ] SQL-Injection-Tests (obwohl Eloquent schützt)
- [ ] Session-Management (HttpOnly, Secure Cookies, SameSite=Strict)
- [ ] Encryption-Key sicher gespeichert (.env outside webroot)
- [ ] .env nicht im Git (gitignore verified)
- [ ] File-Upload-Validation (Anhänge: Mimetype, Size, Extension)
- [ ] Rate-Limiting funktioniert (Login: max 5/15min)
- [ ] 2FA für Admin getestet (SHOULD-Feature)
- [ ] Webcron-Token-Security (Strong random token)

---

### Sprint 5.3: System Health-Check & Installer (2 Tage)

**Ziel:** Installer & Health-Check für Production-Deployment

**Health-Check Endpoint:** `/api/system/health`

**JSON-Response Format:**
```json
{
  "status": "healthy",
  "timestamp": "2025-11-17T14:30:00Z",
  "modules": {
    "logger": {
      "status": "ok",
      "test_passed": true,
      "log_file_writable": true
    },
    "config": {
      "status": "ok",
      "test_passed": true,
      "env_loaded": true
    },
    "encryption": {
      "status": "ok",
      "test_passed": true,
      "key_valid": true
    },
    "database": {
      "status": "ok",
      "connection": "mysql",
      "latency_ms": 5,
      "migrations_up_to_date": true
    },
    "imap": {
      "status": "ok",
      "extension_loaded": true,
      "test_connection": true
    }
  },
  "system": {
    "php_version": "8.2.12",
    "extensions": ["openssl", "pdo_mysql", "imap", "mbstring"],
    "disk_free_gb": 45.2,
    "memory_limit": "256M"
  }
}
```

**Installer Verification Script:** `install/verify.php`

```bash
php install/verify.php

=== CI-Inbox Installation Verification ===

Environment Check:
✅ PHP Version: 8.2.12 (required: >= 8.1)
✅ Required Extensions: openssl, pdo_mysql, imap, mbstring
✅ .env file exists
✅ Encryption key configured
✅ Database credentials configured

Database Check:
✅ Database Connection: OK (mysql)
✅ Migrations Status: 7/7 up-to-date

Module Tests:
✅ Logger Module: PASSED
✅ Config Module: PASSED
✅ Encryption Module: PASSED
✅ IMAP Module: PASSED

File Permissions:
✅ logs/ writable
✅ data/cache/ writable
✅ data/sessions/ writable
✅ data/uploads/ writable

Status: All checks passed ✅
Installation ready for production!
```

**Implementation Plan:**
1. Jedes Modul hat `tests/manual-test.php` → Installer nutzt diese
2. Health-Check Controller sammelt alle Module-Status
3. Installer-Script wrapper um alle Manual-Tests
4. Admin-UI: System-Status Dashboard (M4)

---

### Sprint 5.4: Deployment-Dokumentation (2 Tage)

**Dokument:** `docs/admin/deployment.md`

**Inhalt:**
1. Server-Anforderungen (PHP, MySQL, Extensions)
2. Installation Steps (10-Punkte-Checkliste)
3. Webserver-Konfiguration (Apache .htaccess, Nginx config)
4. SSL-Zertifikat Setup (Let's Encrypt)
5. Cron-Job Setup (cronjob.de/cron-job.org)
6. Backup-Strategie (Database + Uploads)
7. Monitoring Setup (Logs, Uptime, Health-Check)
8. Rollback-Prozedur
9. Troubleshooting Guide
10. Security Hardening Checklist

---

### M5 Deliverables & Success Criteria

**Deliverables:**
- [ ] Performance-Metriken erreicht (< 2s, < 30s, < 50ms)
- [ ] Security-Audit bestanden (10/10 Checks)
- [ ] Health-Check Endpoint funktional
- [ ] Installer-Script (`install/verify.php`)
- [ ] Deployment-Dokumentation vollständig
- [ ] Backup-Strategie dokumentiert & getestet
- [ ] Production-Deployment erfolgreich

**Success Criteria:**
- ✅ System läuft stable auf Production-Server (Shared Hosting)
- ✅ Alle Module-Tests grün (Health-Check)
- ✅ Performance-Benchmarks bestanden
- ✅ Security-Audit ohne kritische Findings
- ✅ Admin kann System eigenständig deployen (mit docs/admin/)
- ✅ Backup & Restore getestet

---

## Zusammenfassung: KI-freundliche Entwicklung

### Warum dieser Ansatz funktioniert:

1. **Standalone-Komponenten**
   - Jedes Modul ist **sofort testbar** (ohne Abhängigkeiten)
   - KI kann fokussiert arbeiten ("Baue Logger-Modul")

2. **Klare Interfaces**
   - Schnittstellen sind vor Implementierung definiert
   - Spätere Features "docken" einfach an

3. **Layer-Abstraktion** (basics.txt)
   - Repository-Pattern erlaubt DB-Wechsel
   - Services sind UI-unabhängig

4. **Inkrementeller Wert**
   - Nach M0: Infrastruktur testbar
   - Nach M1: E-Mails kommen in DB
   - Nach M2: Thread-Logik funktioniert (CLI)
   - Nach M3: **MVP produktiv nutzbar**

5. **Parallele Entwicklung möglich**
   - M1 (IMAP) + M2 (Threads) können parallel laufen
   - UI erst am Ende (wenn Backend stable)

---

## Nächste Schritte

1. ✅ Roadmap fertig
2. ✅ `architecture.md` erstellt (Diagramme, Datenmodell)
3. ✅ `codebase.md` erstellt (Dev-Setup)
4. ✅ Verzeichnisstruktur angelegt
5. ✅ **M0 COMPLETED** (Alle 5 Sprints erfolgreich)
6. 🔴 **M1 starten: IMAP Core** (Sprint 1.1: IMAP-Client-Modul)

---

## Lessons Learned (M0 Foundation)

### 🎯 Was funktioniert hat:

1. **Compact WIP Format**
   - Reduzierte WIP-Dokumente von ~300 auf ~50 Zeilen
   - Beschleunigte Sprints erheblich (Sprint 0.4 & 0.5 jeweils < 1h)
   - Empfehlung: Für kleine, klar definierte Sprints verwenden

2. **Standalone Module Pattern**
   - Jedes Modul (Logger, Config, Encryption) sofort testbar
   - Unabhängige Entwicklung möglich
   - KI kann fokussiert arbeiten ohne Kontext-Overload

3. **Manual Tests First**
   - Manuelle Tests (`manual-test.php`) vor Unit-Tests
   - Schnelles Feedback während Entwicklung
   - Unit-Tests können später aus Manual-Tests abgeleitet werden

4. **Database-First Approach**
   - Migrations + Eloquent Models früh implementiert
   - Ermöglicht realistische Tests mit tatsächlichen Daten
   - Relationships sofort sichtbar und testbar

### ⚠️ Probleme & Lösungen:

1. **Pivot-Tabellen: Timestamps Issue**
   - **Problem:** Eloquent fügt automatisch `created_at`, `updated_at` zu Pivot-Tabellen hinzu
   - **Fehler:** `Column not found: 1054 Unknown column 'created_at'`
   - **Lösung:** `withPivot('assigned_at')` ohne `withTimestamps()` verwenden
   - **Code:**
     ```php
     // ❌ Falsch:
     return $this->belongsToMany(Thread::class, 'thread_assignments')
         ->withTimestamps()
         ->withPivot('assigned_at');
     
     // ✅ Richtig:
     return $this->belongsToMany(Thread::class, 'thread_assignments')
         ->withPivot('assigned_at');
     ```

2. **Container: Constructor Signature Mismatch**
   - **Problem:** Container-Definition passte nicht zum Constructor
   - **Fehler:** `Argument #1 ($logPath) must be of type string, ConfigService given`
   - **Lösung:** Service-Definitionen müssen exakt mit Constructors matchen
   - **Code:**
     ```php
     // ❌ Falsch:
     LoggerService::class => function(ContainerInterface $c) {
         return new LoggerService($c->get(ConfigService::class));
     },
     
     // ✅ Richtig:
     LoggerService::class => function(ContainerInterface $c) {
         $config = $c->get(ConfigService::class);
         return new LoggerService(
             $config->getString('logger.log_path'),
             $config->getString('logger.log_level')
         );
     },
     ```

3. **DateTime Helper Functions**
   - **Problem:** `now()` Helper nicht in Standalone-Umgebung verfügbar
   - **Fehler:** `Call to undefined function now()`
   - **Lösung:** `new \DateTime()` oder `\Carbon\Carbon::now()` verwenden
   - **Empfehlung:** Carbon als Dependency hinzufügen für bessere DateTime-Handling

### 📊 Performance:

- **Geschätzte Dauer M0:** 2 Wochen (10 Arbeitstage)
- **Tatsächliche Dauer M0:** ~4 Stunden (0.5 Arbeitstage)
- **Speedup:** 20x schneller als geschätzt! 🚀
- **Grund:** Fokussierte KI-Arbeit, klare Interfaces, keine Feature-Creep

### 🔧 Empfehlungen für M1-M5:

1. **Weiteres Compact WIP Format nutzen** für kleine Sprints
2. **Pivot-Tables Pattern** in Architecture-Docs aufnehmen
3. **Carbon installieren** für DateTime-Handling (`composer require nesbot/carbon`)
4. **Container-Tests** hinzufügen (DI-Resolution validieren)
5. **Health-Check Implementation** parallel zu M1 starten (niedrige Priorität)

---

**Ende der Roadmap**

*Dieses Dokument wird während der Entwicklung aktualisiert (Sprint-Status, Timings, Lessons Learned).*

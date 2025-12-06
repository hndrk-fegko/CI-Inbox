# CI-Inbox: Codebase-Dokumentation

**Version:** 0.3.0 (M2/M3 - Thread & Email API)  
**Datum:** 6. Dezember 2025  
**Autor:** Hendrik Dreis ([hendrik.dreis@feg-koblenz.de](mailto:hendrik.dreis@feg-koblenz.de))  
**Lizenz:** MIT License  
**Status:** ✅ M0-M2 Complete | 🔄 M3 In Progress

> **Hinweis:** Diese Dokumentation ist die **maßgebliche Quelle** für die aktuelle Code-Struktur. 
> Sprint-Dokumente (`[COMPLETED]`, `[WIP]`) dokumentieren den historischen Planungsstand.

---

## 1. Übersicht

Dieses Dokument beschreibt die **Entwicklungsumgebung**, **Code-Konventionen** und **Arbeitsweise** für das CI-Inbox-Projekt.

**Verwandte Dokumente:**
- **Technische Architektur:** Siehe `architecture.md` (Schichten, Module, Datenmodell)
- **Feature-Liste:** Siehe `inventar.md` (Alle Features mit Prioritäten)
- **Entwicklungs-Roadmap:** Siehe `roadmap.md` (Milestones M0-M5)
- **Sprint-Plan & Roadmap:** Siehe `roadmap.md` (Meilensteine M0-M5)

---

## 2. Entwicklungsumgebung einrichten

### 2.1 Systemvoraussetzungen

**Aktuell verwendet (Produktiv):**
- **PHP:** 8.2.12 (XAMPP)
- **Composer:** 2.9.1 (lokal installiert)
- **Webserver:** Apache 2.4.58 (XAMPP)
- **Datenbank:** MySQL 8.0.36 (MariaDB kompatibel)
- **vHost:** ci-inbox.local → `c:/xampp/htdocs/ci-inbox/src/public`

**Minimum-Anforderungen:**
- **PHP:** 8.1 oder höher
- **Composer:** 2.5+
- **Webserver:** Apache 2.4+ oder Nginx 1.18+
- **Datenbank:** MySQL 8.0+ oder MariaDB 10.6+
- **PHP-Extensions:**
  - `pdo_mysql` (Datenbankzugriff) ✅
  - `imap` (IMAP-Funktionen) ✅
  - `openssl` (Verschlüsselung) ✅
  - `mbstring` (String-Verarbeitung) ✅
  - `json` (JSON-Parsing) ✅
  - `curl` (HTTP-Requests) ✅

**Empfohlen für Entwicklung:**
- **IDE:** VS Code mit PHP Intelephense Extension
- **Debugging:** Xdebug 3.x
- **Git:** 2.40+
- **Node.js:** 18+ (optional, nur für Frontend-Tooling)

---

### 2.2 Installation (Schritt-für-Schritt)

#### 1. Repository klonen
```bash
git clone <repository-url> ci-inbox
cd ci-inbox
```

#### 2. PHP-Dependencies installieren
```bash
composer install
```

**Installierte Packages (siehe `composer.json`):**

**Production:**
- `slim/slim`: ^4.12 - HTTP-Framework ✅
- `slim/psr7`: ^1.6 - PSR-7 HTTP Messages ✅
- `php-di/php-di`: ^7.0 - Dependency Injection Container ✅
- `illuminate/database`: ^10.0 - Eloquent ORM (standalone) ✅
- `monolog/monolog`: ^3.5 - Logging Library ✅
- `vlucas/phpdotenv`: ^5.5 - Environment-Variablen Loader ✅
- `ezyang/htmlpurifier`: ^4.16 - XSS-Prevention ✅

**Development:**
- `phpunit/phpunit`: ^10.0 - Testing Framework 🔴

#### 3. Environment konfigurieren
```bash
# Kopiere Template
cp .env.example .env

# Bearbeite .env und setze:
# - DB_HOST=localhost
# - DB_DATABASE=ci_inbox
# - DB_USERNAME=root
# - DB_PASSWORD=<dein_passwort>
# - ENCRYPTION_KEY (generiere mit: php -r "echo base64_encode(random_bytes(32));")
# - APP_ENV=development
# - APP_URL=http://ci-inbox.local
```

**Aktuell verwendete .env (Entwicklung):**
```env
APP_NAME="CI-Inbox"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://ci-inbox.local

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=ci_inbox
DB_USERNAME=root
DB_PASSWORD=

ENCRYPTION_KEY=<base64_encoded_32_bytes>
ENCRYPTION_CIPHER=AES-256-CBC

LOG_PATH=logs/
LOG_LEVEL=debug
LOG_RETENTION_DAYS=30
```

#### 4. Datenbank einrichten
```bash
# Erstelle Datenbank
mysql -u root -p -e "CREATE DATABASE ci_inbox CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Führe Migrations aus
php database/migrate.php
```

**Ergebnis (7 Tabellen):**
- users
- imap_accounts
- threads
- emails
- labels
- thread_assignments (Pivot)
- thread_labels (Pivot)

#### 5. Webserver konfigurieren

**Apache vHost (ci-inbox.local):**
```apache
<VirtualHost *:80>
    ServerName ci-inbox.local
    DocumentRoot "c:/xampp/htdocs/ci-inbox/src/public"
    
    <Directory "c:/xampp/htdocs/ci-inbox/src/public">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog "logs/ci-inbox-error.log"
    CustomLog "logs/ci-inbox-access.log" common
</VirtualHost>
```

**hosts-Datei (Windows: `C:\Windows\System32\drivers\etc\hosts`):**
```
127.0.0.1 ci-inbox.local
```
```nginx
server {
    listen 80;
    server_name ci-inbox.local;
    root /path/to/ci-inbox/src/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

#### 6. Berechtigungen setzen
```bash
# Linux/macOS
chmod -R 775 data/ logs/
chown -R www-data:www-data data/ logs/

# Windows: Keine Aktion nötig (XAMPP läuft als aktueller User)
```

#### 7. Anwendung testen
```bash
# Browser öffnen
http://ci-inbox.local/

# Oder: Health-Check testen
curl http://ci-inbox.local/api/system/health
```

**Erwartete Ausgabe (Health-Check):**
```json
{
  "status": "healthy",
  "timestamp": "2025-11-17T12:31:39+01:00",
  "modules": {
    "logger": {"status": "ok"},
    "config": {"status": "ok"},
    "encryption": {"status": "ok"},
    "database": {"status": "ok"}
  },
  "system": {
    "php_version": "8.2.12",
    "extensions": ["openssl", "pdo_mysql", "imap", ...]
  }
}
```

**Alternative: PHP Built-in Server (Entwicklung):**
```bash
php -S localhost:8080 -t src/public
```

Dann öffne: `http://localhost:8080`

---

### 2.3 Entwicklungs-Tools einrichten

#### VS Code Extensions (empfohlen)
```json
{
  "recommendations": [
    "bmewburn.vscode-intelephense-client",  // PHP IntelliSense
    "xdebug.php-debug",                      // Debugging
    "EditorConfig.EditorConfig",             // Code-Formatierung
    "DEVSENSE.composer-php-vscode",          // Composer Support
    "mikestead.dotenv"                       // .env Syntax
  ]
}
```

#### Xdebug konfigurieren (php.ini)
```ini
[xdebug]
zend_extension=xdebug.so
xdebug.mode=develop,debug
xdebug.start_with_request=yes
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
```

---

## 3. Verzeichnisstruktur

**Vollständige Struktur:** Siehe `architecture.md` → Abschnitt 9.1

**Aktuell implementierte Struktur (M0 Complete):**

```
ci-inbox/
├── src/                          # Codebase
│   ├── core/                     # ✅ Kern-System
│   │   ├── Application.php       # Main App Class (125 lines)
│   │   ├── Container.php         # DI Container Wrapper (55 lines)
│   │   ├── HookManager.php       # Event System (70 lines)
│   │   └── ModuleLoader.php      # Auto-Discovery (95 lines)
│   ├── modules/                  # ✅ Standalone Module
│   │   ├── logger/               # Logger-Modul (M0 Sprint 0.1)
│   │   │   ├── src/
│   │   │   │   ├── LoggerService.php (186 lines)
│   │   │   │   ├── Formatters/JsonFormatter.php
│   │   │   │   └── Handlers/RotatingFileHandler.php
│   │   │   ├── config/logger.config.php
│   │   │   ├── tests/manual-test.php
│   │   │   └── README.md
│   │   ├── config/               # Config-Modul (M0 Sprint 0.2)
│   │   │   ├── src/
│   │   │   │   ├── ConfigService.php (270 lines)
│   │   │   │   └── ConfigException.php
│   │   │   ├── config/app.config.php, database.config.php
│   │   │   ├── tests/manual-test.php
│   │   │   └── README.md
│   │   └── encryption/           # Encryption-Modul (M0 Sprint 0.3)
│   │       ├── src/
│   │       │   ├── EncryptionService.php (220 lines)
│   │       │   └── EncryptionException.php
│   │       ├── config/encryption.config.php
│   │       ├── tests/manual-test.php
│   │       └── README.md
│   ├── app/                      # ✅ Anwendungs-Code
│   │   └── Models/               # Eloquent Models (M0 Sprint 0.4)
│   │       ├── BaseModel.php
│   │       ├── User.php
│   │       ├── ImapAccount.php
│   │       ├── Thread.php
│   │       ├── Email.php
│   │       └── Label.php
│   ├── bootstrap/                # ✅ Bootstrap Scripts
│   │   └── database.php          # Eloquent Capsule Setup
│   ├── routes/                   # ✅ Route Definitions (M0 Sprint 0.5)
│   │   ├── api.php               # API Routes (Health, Info)
│   │   └── web.php               # Web Routes (Homepage)
│   ├── config/                   # ✅ DI Container Config
│   │   └── container.php         # Service Definitions
│   ├── public/                   # ✅ Web-Root (DocumentRoot)
│   │   ├── index.php             # Entry Point (uses Application class)
│   │   ├── login.php             # Login-Seite (M3)
│   │   ├── inbox.php             # Inbox-Hauptansicht (M3)
│   │   └── assets/               # Static Assets
│   │       ├── css/              # Core CSS (ITCSS)
│   │       │   ├── main.css      # Compiled CSS (für Login)
│   │       │   ├── 1-settings/   # Variables, Config
│   │       │   ├── 3-generic/    # Reset, Normalize
│   │       │   ├── 4-elements/   # Base HTML Elements
│   │       │   ├── 5-objects/    # Layout Patterns
│   │       │   ├── 6-components/ # UI Components
│   │       │   └── 7-utilities/  # Helper Classes
│   │       └── modules/          # Modul-spezifische Assets
│   │           └── (z.B. darkmode/, webhooks/)
│   └── views/                    # 🔴 Templates (TODO in M3)
├── database/                     # ✅ Database Layer (M0 Sprint 0.4)
│   ├── migrations/
│   │   ├── 001_create_users_table.php
│   │   ├── 002_create_imap_accounts_table.php
│   │   ├── 003_create_threads_table.php
│   │   ├── 004_create_emails_table.php
│   │   ├── 005_create_labels_table.php
│   │   ├── 006_create_thread_assignments_table.php
│   │   └── 007_create_thread_labels_table.php
│   ├── migrate.php               # Migration Runner
│   └── test.php                  # CRUD Test Script
├── docs/                         # ✅ Dokumentation
│   └── dev/                      # Entwickler-Docs
│       ├── vision.md             ✅
│       ├── inventar.md           ✅
│       ├── workflow.md           ✅
│       ├── roadmap.md            ✅
│       ├── architecture.md       ✅
│       ├── codebase.md           ✅ (dieses Dokument)
│       ├── [COMPLETED] M0-Sprint-0.1-Logger-Modul.md
│       ├── [COMPLETED] M0-Sprint-0.2-Config-Modul.md
│       ├── [COMPLETED] M0-Sprint-0.3-Encryption-Service.md
│       ├── [COMPLETED] M0-Sprint-0.4-Database-Setup.md
│       └── [COMPLETED] M0-Sprint-0.5-Core-Infrastruktur.md
├── tests/                        # 🔴 Test-Suite (TODO Phase 5)
│   ├── unit/
│   ├── integration/
│   └── e2e/
├── data/                         # ✅ Runtime-Daten (gitignored)
│   ├── cache/
│   ├── sessions/
│   └── uploads/
├── logs/                         # ✅ Log-Dateien (gitignored)
│   └── app-2025-11-17.log
├── scripts/                      # 🔴 CLI-Skripte (TODO M1)
│   └── cron-poll-emails.php      # Webcron (M1 Sprint 1.4)
├── .env.example                  ✅
├── .env                          ✅ (gitignored)
├── .gitignore                    ✅
├── composer.json                 ✅
├── composer.lock                 ✅
├── README.md                     ✅
├── basics.txt                    ✅
├── inventar.md                   ✅ (Legacy, moved to docs/dev)
└── vision.md                     ✅ (Legacy, moved to docs/dev)
```

**Wichtigste Verzeichnisse:**

| Pfad | Beschreibung | Status | Im Git? |
|------|--------------|--------|---------|
| `src/core/` | Kern-System (Application, Container, HookManager, ModuleLoader) | ✅ M0 | ✅ Ja |
| `src/modules/` | Wiederverwendbare Module (logger, config, encryption) | ✅ M0 | ✅ Ja |
| `src/app/Models/` | Eloquent Models (User, Thread, Email, etc.) | ✅ M0 | ✅ Ja |
| `src/bootstrap/` | Bootstrap Scripts (database.php) | ✅ M0 | ✅ Ja |
| `src/routes/` | Route Definitions (api.php, web.php) | ✅ M0 | ✅ Ja |
| `src/public/` | Web-Root (index.php, CSS, JS) | ✅ M0 | ✅ Ja |
| `src/config/` | DI Container Configuration | ✅ M0 | ✅ Ja |
| `database/migrations/` | Database Migrations (7 tables) | ✅ M0 | ✅ Ja |
| `data/` | Runtime-Daten (Cache, Sessions, Uploads) | ✅ | ❌ Nein (.gitignore) |
| `logs/` | Log-Dateien | ✅ | ❌ Nein (.gitignore) |
| `tests/` | Test-Suite (Unit, Integration, E2E) | 🔴 TODO | ✅ Ja |
| `docs/dev/` | Entwickler-Dokumentation | ✅ | ✅ Ja |
| `scripts/` | CLI-Skripte (Cron, Setup) | 🔴 TODO M1 | ✅ Ja |

---

## 4. Code-Konventionen

### 4.1 PHP-Standards

**Wir folgen PSR-12 (Extended Coding Style):**
- **Namespaces:** `CiInbox\` (Root), `CiInbox\Modules\Logger\` (Module)
- **Klassen:** `PascalCase` (z.B. `ThreadService`, `ImapClient`)
- **Methoden:** `camelCase` (z.B. `assignThread()`, `fetchEmails()`)
- **Konstanten:** `UPPER_SNAKE_CASE` (z.B. `MAX_LOGIN_ATTEMPTS`)
- **Properties:** `camelCase` (z.B. `$userId`, `$threadStatus`)
- **Einrückung:** 4 Spaces (keine Tabs)
- **Zeilenlänge:** Max 120 Zeichen (Soft Limit)

**Beispiel:**
```php
<?php

declare(strict_types=1);

namespace CiInbox\App\Services;

use CiInbox\App\Repositories\ThreadRepositoryInterface;
use CiInbox\Modules\Logger\LoggerService;

class ThreadService
{
    private ThreadRepositoryInterface $threadRepository;
    private LoggerService $logger;

    public function __construct(
        ThreadRepositoryInterface $threadRepository,
        LoggerService $logger
    ) {
        $this->threadRepository = $threadRepository;
        $this->logger = $logger;
    }

    public function assignThread(int $threadId, int $userId): bool
    {
        $this->logger->info('Assigning thread', [
            'thread_id' => $threadId,
            'user_id' => $userId,
        ]);

        $thread = $this->threadRepository->findById($threadId);
        
        if (!$thread) {
            $this->logger->warning('Thread not found', ['thread_id' => $threadId]);
            return false;
        }

        $thread->assigned_to = $userId;
        $thread->status = 'assigned';
        $thread->save();

        return true;
    }
}
```

---

### 4.2 Datei-Struktur

**PHP-Dateien:**
```php
<?php
// 1. Declare strict types (immer erste Zeile nach <?php)
declare(strict_types=1);

// 2. Namespace
namespace CiInbox\App\Controllers;

// 3. Use-Statements (alphabetisch sortiert)
use CiInbox\App\Services\ThreadService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// 4. Klassen-Docblock
/**
 * Controller für Thread-Management
 * 
 * Verwaltet HTTP-Requests für Thread-Operationen.
 */
class ThreadController
{
    // 5. Properties
    private ThreadService $threadService;

    // 6. Constructor
    public function __construct(ThreadService $threadService)
    {
        $this->threadService = $threadService;
    }

    // 7. Public Methods
    public function list(Request $request, Response $response): Response
    {
        // Implementation
    }

    // 8. Protected/Private Methods
    private function validateThreadId(int $id): bool
    {
        // Implementation
    }
}
```

---

### 4.3 Naming Conventions

#### Klassen-Namen
- **Controller:** `*Controller` (z.B. `ThreadController`, `AuthController`)
- **Service:** `*Service` (z.B. `ThreadService`, `ImapService`)
- **Repository:** `*Repository` oder `*RepositoryInterface` (z.B. `ThreadRepository`, `ThreadRepositoryInterface`)
- **Model:** Entity-Name (z.B. `Thread`, `User`, `Email`)
- **Middleware:** `*Middleware` (z.B. `AuthMiddleware`, `CorsMiddleware`)

#### Methoden-Namen
- **CRUD-Operationen:** `create()`, `update()`, `delete()`, `find()`, `findById()`, `findAll()`
- **Boolean-Checks:** `is*()`, `has*()`, `can*()` (z.B. `isAssigned()`, `hasAttachments()`, `canDelete()`)
- **Actions:** Verb + Noun (z.B. `assignThread()`, `fetchEmails()`, `sendReply()`)

#### Datenbank-Felder (siehe `architecture.md` Abschnitt 6)
- **Timestamps:** `created_at`, `updated_at` (automatisch von Eloquent)
- **Foreign Keys:** `*_id` (z.B. `user_id`, `thread_id`)
- **Boolean:** `is_*` (z.B. `is_active`, `is_read`)
- **Status:** `status` (ENUM oder VARCHAR)

---

### 4.4 Kommentare & Dokumentation

**Docblocks verwenden (PSR-5 Draft):**
```php
/**
 * Weist einen Thread einem User zu
 * 
 * @param int $threadId Die ID des Threads
 * @param int $userId Die ID des Users
 * @return bool True bei Erfolg, false bei Fehler
 * @throws ThreadNotFoundException Wenn Thread nicht existiert
 */
public function assignThread(int $threadId, int $userId): bool
{
    // Implementation
}
```

**Inline-Kommentare:**
- **WARUM, nicht WAS:** Code sollte selbsterklärend sein
- **Nur bei komplexer Logik:** Business-Rules, Workarounds, TODOs
```php
// Berechne Thread-Priority basierend auf SLA (max 24h Response)
$priority = ($hoursOld > 20) ? 'high' : 'normal';

// TODO: Implementiere Escalation-Logic (siehe Ticket #42)
```

**TODOs markieren:**
```php
// TODO(marius): Implement caching for thread list
// FIXME: Race condition wenn 2 User gleichzeitig assignen
// HACK: Workaround for php-imap bug (see issue #123)
```

---

## 5. Architektur-Patterns

**Details:** Siehe `architecture.md` → Abschnitt 3 & 4

### 5.1 Layer-Abstraktion (PFLICHT!)

**Regel aus `basics.txt` Kap. 4:**
> "Geschäftslogik NIEMALS direkt an Implementierungsdetails koppeln"

**Schichten (von oben nach unten):**
1. **Presentation Layer** → Controller empfangen HTTP-Requests
2. **Controller Layer** → Validierung, Delegierung an Services
3. **Service Layer** → Business Logic (NIEMALS direkt Eloquent!)
4. **Repository Layer** → Abstraktion (Interface)
5. **Implementation Layer** → Eloquent, IMAP, etc. (austauschbar)

**Beispiel (RICHTIG):**
```php
// Service Layer (Business Logic)
class ThreadService
{
    // Dependency auf INTERFACE, nicht auf Implementierung!
    public function __construct(
        private ThreadRepositoryInterface $repo  // ✅ Interface
    ) {}
}

// Repository Interface (Data Access Layer)
interface ThreadRepositoryInterface
{
    public function findById(int $id): ?Thread;
    public function save(Thread $thread): bool;
}

// Repository Implementation (Implementation Layer)
class ThreadRepository implements ThreadRepositoryInterface
{
    public function __construct(
        private LoggerInterface $logger  // ✅ Alle Repos haben Logging
    ) {}
    
    public function findById(int $id): ?Thread
    {
        return Thread::find($id);  // Eloquent-spezifisch
    }
}
```

**Beispiel (FALSCH - Nicht so machen!):**
```php
// ❌ Service nutzt direkt Eloquent Model
class ThreadService
{
    public function getThread(int $id): ?Thread
    {
        return Thread::find($id);  // ❌ Direkte Kopplung an Eloquent!
    }
}
```

---

### 5.2 Dependency Injection (DI)

**Wir verwenden PSR-11 Container (siehe `architecture.md` Abschnitt 5.3):**

```php
// Container konfigurieren (src/core/Container.php)
$container->set(LoggerService::class, function() {
    return new LoggerService(new FileHandler('/logs/app.log'));
});

$container->set(ThreadRepositoryInterface::class, function($c) {
    return new ThreadRepository($c->get(LoggerService::class));
});

$container->set(ThreadService::class, function($c) {
    return new ThreadService(
        $c->get(ThreadRepositoryInterface::class),
        $c->get(LoggerService::class)
    );
});
```

**In Controllern nutzen:**
```php
// Slim Routes (src/public/index.php)
$app->get('/api/threads/{id}', function (Request $request, Response $response, $args) {
    $threadService = $this->get(ThreadService::class);  // DI Container
    $thread = $threadService->getThread((int) $args['id']);
    // ...
});
```

---

### 5.3 Repository Pattern

**Siehe `architecture.md` Abschnitt 3.3 für vollständiges Beispiel**

**Vorteile:**
- ✅ Datenbank austauschbar (MySQL → MongoDB ohne Business Logic zu ändern)
- ✅ Testbarkeit (Mock Repository für Unit Tests)
- ✅ Wiederverwendbarkeit (Repository in mehreren Services)

**Aktuelle Struktur (Stand 2025-11-28):**
```
src/app/Repositories/
├── ThreadRepositoryInterface.php       # Interface
├── ThreadRepository.php                # Implementierung (mit Logging)
├── EmailRepositoryInterface.php        # Interface
├── EloquentEmailRepository.php         # Implementierung (mit Logging)
├── NoteRepositoryInterface.php         # Interface
├── EloquentNoteRepository.php          # Implementierung (mit Logging)
├── LabelRepository.php                 # Direkt genutzt (mit Logging)
├── ImapAccountRepository.php           # Direkt genutzt (mit Logging)
├── SystemSettingRepository.php         # Direkt genutzt (mit Logging)
└── SignatureRepository.php             # Direkt genutzt (mit Logging)
```

**Hinweis:** Alle Repositories haben LoggerInterface injiziert und loggen CRUD-Operationen.

---

### 5.4 Module/Plugin System

**Siehe `architecture.md` Abschnitt 5 für vollständige Dokumentation**

**Jedes Modul in `src/modules/` ist:**
- ✅ **Standalone:** Kann isoliert getestet werden
- ✅ **Wiederverwendbar:** In anderen Projekten nutzbar
- ✅ **Hook-basiert:** Registriert sich über Hooks im Core

**Module-Struktur:**
```
src/modules/logger/
├── module.json           # Manifest (Name, Version, Hooks)
├── src/
│   ├── LoggerService.php
│   └── Handlers/
├── config/
│   └── logger.config.php
├── tests/
│   └── LoggerServiceTest.php
└── README.md             # Standalone-Dokumentation
```

**Hooks (siehe `architecture.md` Abschnitt 5.4):**
- `onAppInit`, `onConfigLoad`, `onBeforeRequest`, `onAfterResponse`, `onError`, `onShutdown`

---

## 6. Testing-Strategie

### 6.1 Test-Pyramide

```
     ┌─────────────┐
     │  E2E Tests  │  ← Wenige (5-10) - Volle User-Flows
     └─────────────┘
    ┌───────────────┐
    │Integration    │  ← Mittel (20-30) - API, DB, IMAP
    │    Tests      │
    └───────────────┘
  ┌───────────────────┐
  │   Unit Tests      │  ← Viele (100+) - Jede Methode isoliert
  └───────────────────┘
```

---

### 6.2 Unit Tests (PHPUnit)

**Ziel:** Jede Service-Methode isoliert testen

**Beispiel:**
```php
// tests/unit/Services/ThreadServiceTest.php
use PHPUnit\Framework\TestCase;
use CiInbox\App\Services\ThreadService;
use CiInbox\App\Repositories\Mock\MockThreadRepository;

class ThreadServiceTest extends TestCase
{
    private ThreadService $service;
    private MockThreadRepository $mockRepo;

    protected function setUp(): void
    {
        $this->mockRepo = new MockThreadRepository();
        $this->service = new ThreadService($this->mockRepo);
    }

    public function testAssignThreadSuccess(): void
    {
        $threadId = 1;
        $userId = 42;

        $result = $this->service->assignThread($threadId, $userId);

        $this->assertTrue($result);
        $this->assertEquals('assigned', $this->mockRepo->getThreadStatus($threadId));
    }

    public function testAssignThreadNotFound(): void
    {
        $result = $this->service->assignThread(999, 42);
        $this->assertFalse($result);
    }
}
```

**Ausführen:**
```bash
./vendor/bin/phpunit tests/unit/
```

---

### 6.3 Integration Tests

**Ziel:** API-Endpoints mit echter Datenbank testen

**Beispiel:**
```php
// tests/integration/Api/ThreadApiTest.php
class ThreadApiTest extends TestCase
{
    public function testGetThreadsReturnsJson(): void
    {
        $client = new TestClient();
        $response = $client->get('/api/threads');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJson($response->getBody());
    }
}
```

---

### 6.4 Standalone Tests für Module (PFLICHT)

**Aus `basics.txt` Kap. 6.5 + `roadmap.md`:**

Jedes Modul in M0-M1 bekommt ein **manuelles Test-Skript** für schnelles Debugging:

```php
// scripts/manual-test-logger.php
require_once __DIR__ . '/../vendor/autoload.php';

use CiInbox\Modules\Logger\LoggerService;

echo "=== Logger-Modul Test ===\n";

$logger = new LoggerService('/logs/test.log');
$logger->info('Test message', ['foo' => 'bar']);

echo "✅ Log geschrieben. Prüfe logs/test.log\n";
```

**Ausführen:**
```bash
php scripts/manual-test-logger.php
```

---

## 7. Logging & Debugging

**Vollständige Dokumentation:** Siehe `architecture.md` Abschnitt 7.1

### 7.1 Logger verwenden (Monolog / PSR-3)

**In allen Services/Controllern:**
```php
use CiInbox\Modules\Logger\LoggerService;

class ThreadService
{
    public function __construct(
        private LoggerService $logger
    ) {}

    public function assignThread(int $threadId, int $userId): bool
    {
        // 1. Info-Level für normale Operationen
        $this->logger->info('Assigning thread', [
            'thread_id' => $threadId,
            'user_id' => $userId,
        ]);

        try {
            // Logic...
            return true;
        } catch (\Exception $e) {
            // 2. Error-Level für Exceptions
            $this->logger->error('Failed to assign thread', [
                'thread_id' => $threadId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }
}
```

**Log-Levels (PSR-3):**
- `debug()` - Entwicklungs-Infos (nur in DEV)
- `info()` - Normale Operationen
- `warning()` - Unerwartete Situationen (nicht kritisch)
- `error()` - Fehler (Exception, aber App läuft weiter)
- `critical()` - Kritischer Fehler (System nicht nutzbar)

---

### 7.2 Log-Dateien

**Struktur (siehe `architecture.md` Abschnitt 9.1):**
```
logs/
├── app.log           # Haupt-Log (alle Requests)
├── error.log         # Nur Errors/Exceptions
├── cron.log          # IMAP-Polling-Cron
└── security.log      # Login-Versuche, Rate-Limiting
```

**Log-Format (JSON für einfaches Parsing):**
```json
{
  "timestamp": "2025-11-17T14:30:45.123Z",
  "level": "INFO",
  "message": "Thread assigned successfully",
  "context": {
    "thread_id": 42,
    "user_id": 7,
    "ip": "192.168.1.100",
    "session_id": "abc123"
  }
}
```

---

### 7.3 Error Handling

**Globaler Error-Handler in `src/public/index.php`:**
```php
use CiInbox\Modules\Logger\LoggerService;
use Slim\Factory\AppFactory;

$app = AppFactory::create();

// Error Middleware
$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorHandler = $errorMiddleware->getDefaultErrorHandler();

$errorHandler->registerErrorRenderer('application/json', function (\Throwable $exception, bool $displayErrorDetails) use ($logger) {
    $logger->error('Unhandled exception', [
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString(),
    ]);

    return [
        'error' => true,
        'message' => $displayErrorDetails ? $exception->getMessage() : 'Internal Server Error',
    ];
});
```

---

## 8. Build & Deployment

**Deployment-Architektur:** Siehe `architecture.md` → Abschnitt 9

### 8.1 Deployment-Prozess (Shared Hosting)

**1. Code auf Server kopieren (FTP/SFTP oder Git):**
```bash
# Via Git (empfohlen)
ssh user@server.example.com
cd /var/www/ci-inbox-releases
git clone <repo-url> release-2025-11-17
cd release-2025-11-17
```

**2. Dependencies installieren:**
```bash
composer install --no-dev --optimize-autoloader
```

**3. Environment konfigurieren:**
```bash
cp .env.example .env
nano .env  # Setze PROD-Werte (DB, Keys, etc.)
```

**4. Datenbank migrieren (falls nötig):**
```bash
php scripts/setup-database.php
```

**5. Symlink umbiegen (Zero-Downtime):**
```bash
ln -sfn /var/www/ci-inbox-releases/release-2025-11-17 /var/www/ci-inbox-current
```

**6. Cron-Job einrichten (siehe Abschnitt 8.3):**
Registriere Webhook bei cronjob.de/cron-job.org

---

### 8.2 Apache .htaccess (Shared Hosting)

**In `src/public/.htaccess`:**
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Route alle Requests zu index.php
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [QSA,L]
</IfModule>

# Security Headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Disable directory listing
Options -Indexes

# Protect sensitive files
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>
```

---

### 8.3 Cron-Job Setup (Webcron)

**Siehe `architecture.md` Abschnitt 9.2 für Details**

**Bei cronjob.de/cron-job.org registrieren:**
1. URL: `https://ci-inbox.example.com/api/cron/poll-emails`
2. Intervall: **Alle 5 Minuten**
3. Auth: HTTP Header `X-Cron-Token: <SECRET_FROM_ENV>`

**Cron-Skript (`scripts/cron-poll-emails.php`):**
```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use CiInbox\Modules\Imap\ImapService;
use CiInbox\Modules\Logger\LoggerService;

$logger = new LoggerService('/logs/cron.log');
$logger->info('Cron job started');

// Authentifizierung prüfen
$token = $_SERVER['HTTP_X_CRON_TOKEN'] ?? '';
if ($token !== getenv('CRON_SECRET_TOKEN')) {
    $logger->error('Invalid cron token', ['ip' => $_SERVER['REMOTE_ADDR']]);
    http_response_code(403);
    exit('Forbidden');
}

// IMAP pollen
$imapService = new ImapService();
$newEmails = $imapService->fetchNewEmails();

$logger->info('Cron job completed', ['emails_fetched' => count($newEmails)]);
http_response_code(200);
echo json_encode(['success' => true, 'emails' => count($newEmails)]);
```

---

## 9. Git-Workflow

### 9.1 Branching-Strategie

**Wir verwenden GitHub Flow (einfach für kleine Teams):**

- **`main`** - Production-Ready Code (immer deploybar)
- **`feature/*`** - Feature-Branches (z.B. `feature/m0-logger`, `feature/imap-client`)
- **`bugfix/*`** - Bugfix-Branches (z.B. `bugfix/thread-assignment`)

**Workflow:**
```bash
# 1. Feature-Branch erstellen
git checkout -b feature/m0-logger

# 2. Committen (häufig, kleine Commits)
git add src/modules/logger/
git commit -m "feat(logger): Add LoggerService with PSR-3 interface"

# 3. Push & Pull Request
git push origin feature/m0-logger
# Auf GitHub: Pull Request erstellen nach main

# 4. Review & Merge
# Nach Approval: Merge in main (mit Squash)

# 5. Branch löschen
git branch -d feature/m0-logger
```

---

### 9.2 Commit-Messages (Conventional Commits)

**Format:**
```
<type>(<scope>): <subject>

<body (optional)>

<footer (optional)>
```

**Types:**
- `feat`: Neue Funktion (z.B. `feat(imap): Add email fetching`)
- `fix`: Bugfix (z.B. `fix(auth): Prevent login bypass`)
- `refactor`: Code-Refactoring ohne Feature-Änderung
- `docs`: Dokumentation (z.B. `docs(readme): Update installation steps`)
- `test`: Tests hinzufügen/ändern
- `chore`: Build/Config-Änderungen (z.B. `chore(composer): Update dependencies`)

**Beispiele:**
```bash
git commit -m "feat(logger): Implement Monolog integration with JSON formatter"
git commit -m "fix(threads): Prevent race condition on assignment"
git commit -m "docs(architecture): Add ER diagram for database schema"
```

---

### 9.3 .gitignore

**Wichtig - folgende Dateien/Verzeichnisse NICHT committen:**
```gitignore
# Environment
.env
.env.local

# Dependencies
/vendor/
node_modules/

# Runtime Data
/data/cache/
/data/sessions/
/data/uploads/

# Logs
/logs/*.log

# IDE
.vscode/
.idea/
*.sublime-*

# OS
.DS_Store
Thumbs.db

# Build
/build/
/dist/
```

---

## 10. Qualitätssicherung

### 10.1 Code-Review Checklist

**Vor jedem Merge prüfen:**
- [ ] PSR-12 Standard eingehalten? (Formatierung, Naming)
- [ ] Layer-Abstraktion korrekt? (Kein direkter DB-Zugriff in Services)
- [ ] Alle neuen Methoden dokumentiert? (Docblocks)
- [ ] Unit Tests vorhanden? (Min. 80% Coverage für neue Klassen)
- [ ] Error-Handling implementiert? (Try-Catch, Logging)
- [ ] Security-Checks? (XSS, SQL-Injection, CSRF)
- [ ] Performance-Impact? (N+1 Queries vermieden?)

---

### 10.2 Testing-Strategie

**Ziel:** 80% Code Coverage vor Production-Release

#### Unit-Tests (PHPUnit)

**Für jede Service-Klasse:**
```php
<?php
namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use CiInbox\App\Services\ThreadService;

class ThreadServiceTest extends TestCase {
    public function testAssignThreadSuccess() {
        // Arrange
        $mockRepo = $this->createMock(ThreadRepositoryInterface::class);
        $mockLogger = $this->createMock(LoggerService::class);
        $service = new ThreadService($mockRepo, $mockLogger);
        
        // Act
        $result = $service->assignThread(1, 5);
        
        // Assert
        $this->assertTrue($result);
    }
    
    public function testAssignThreadWithInvalidId() {
        $service = new ThreadService($mockRepo, $mockLogger);
        
        $this->expectException(\InvalidArgumentException::class);
        $service->assignThread(-1, 5);
    }
}
```

**Test-Struktur:**
```
tests/
├── unit/
│   ├── Services/
│   │   ├── ThreadServiceTest.php
│   │   ├── AssignmentServiceTest.php
│   │   └── LabelServiceTest.php
│   └── Modules/
│       ├── Logger/LoggerServiceTest.php
│       └── Imap/ImapClientTest.php
├── integration/
│   ├── ImapConnectionTest.php
│   ├── DatabasePersistenceTest.php
│   └── ThreadWorkflowTest.php
└── e2e/
    ├── LoginAndAssignThreadTest.php
    └── FullEmailWorkflowTest.php
```

**Ausführen:**
```bash
# Alle Tests
vendor/bin/phpunit

# Nur Unit-Tests
vendor/bin/phpunit tests/unit/

# Mit Coverage-Report
vendor/bin/phpunit --coverage-html coverage/
```

#### Integration-Tests

**Testen Module-Zusammenspiel:**
```php
class ImapConnectionTest extends TestCase {
    public function testFetchEmailsAndPersist() {
        // Real IMAP connection (Test-Account)
        $imap = new ImapClient($logger, $config);
        $imap->connect('imap.example.com', 993, 'test@example.com', 'password');
        
        // Fetch emails
        $messages = $imap->getMessages(10);
        
        // Parse & persist
        foreach ($messages as $msg) {
            $parsed = $parser->parse($msg);
            $email = $emailRepo->create($parsed);
            
            $this->assertNotNull($email->id);
        }
    }
}
```

#### E2E-Tests (Selenium)

**Kritische User-Journeys:**
```php
class LoginAndAssignThreadTest extends WebTestCase {
    public function testUserCanLoginAndAssignThread() {
        $this->visit('/login')
             ->fillField('username', 'test@example.com')
             ->fillField('password', 'password')
             ->pressButton('Login')
             ->assertPageContains('Inbox');
        
        $this->click('Thread #1')
             ->click('Assign to me')
             ->assertPageContains('Assigned to you');
    }
}
```

**Performance-Benchmarks:**
- Seitenladezeit < 2 Sekunden
- IMAP-Polling < 30 Sekunden (für 100 Mails)
- Database-Queries < 50ms (Durchschnitt)

---

### 10.3 Coding-Guidelines Checkliste

**Aus `basics.txt` befolgen:**
- [ ] **Kap. 2 (Logging):** Jede wichtige Operation geloggt?
- [ ] **Kap. 3 (Modularität):** Code wiederverwendbar?
- [ ] **Kap. 4 (Layer-Abstraktion):** Keine direkten Implementierungen in Business Logic?
- [ ] **Kap. 5 (Error-Handling):** Saubere Exception-Hierarchie?
- [ ] **Kap. 6 (Task-Management):** Task < 50 Zeilen? Sonst: Subtasks anlegen

---

## 11. Nächste Schritte

**Nach Setup der Entwicklungsumgebung:**
1. **M0 Sprint 0.1:** Logger-Modul implementieren (siehe `roadmap.md`)
2. **M0 Sprint 0.2:** Config-Modul implementieren
3. **M0 Sprint 0.3:** Encryption-Service implementieren
4. ... (siehe `roadmap.md` für vollständigen Plan)

**Weitere Dokumentation lesen:**
- `roadmap.md` - Sprint-Plan & Meilensteine (M0-M5)
- `architecture.md` - Technische Architektur & Patterns
- `roadmap.md` - Milestone-Plan (M0-M5)
- `inventar.md` - Feature-Liste mit Prioritäten

---

**Fragen?** Siehe `roadmap.md` für Sprint-Details oder `codebase.md` § 7 (Logging & Debugging)

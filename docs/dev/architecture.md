# Architektur-Dokumentation: CI-Inbox

**Letzte Aktualisierung:** 6. Dezember 2025  
**Autor:** Hendrik Dreis ([hendrik.dreis@feg-koblenz.de](mailto:hendrik.dreis@feg-koblenz.de))  
**Lizenz:** MIT License  
**Basis:** `vision.md`, `inventar.md`, `roadmap.md`, `basics.txt`

Diese Dokumentation beschreibt die technische Architektur der CI-Inbox mit Fokus auf **Modularität**, **Testbarkeit** und **Layer-Abstraktion**.

---

## Inhaltsverzeichnis

1. [System-Übersicht](#1-system-übersicht)
2. [Architektur-Prinzipien](#2-architektur-prinzipien)
3. [Schichtenarchitektur (Layered Architecture)](#3-schichtenarchitektur-layered-architecture)
4. [System-Architektur-Diagramm](#4-system-architektur-diagramm)
5. [Modul-System & Plugin-Architecture](#5-modul-system--plugin-architecture)
6. [Datenmodell (Database Schema)](#6-datenmodell-database-schema)
7. [Komponenten-Beschreibung](#7-komponenten-beschreibung)
8. [Sicherheits-Architektur](#8-sicherheits-architektur)
9. [Deployment-Architektur](#9-deployment-architektur)
10. [Skalierungsplanung](#10-skalierungsplanung)

---

## 1. System-Übersicht

### 1.1 Was ist die CI-Inbox?

Die CI-Inbox ist eine **schlanke Kollaborations-Ebene über bestehenden IMAP-Postfächern**, die kleinen Teams (3-7 Personen) ermöglicht, gemeinsame E-Mail-Posteingänge transparent zu verwalten.

### 1.2 Architektur-Ziele

1. **Modularität** - Jede Komponente ist austauschbar
2. **Testbarkeit** - Standalone-Tests ohne Abhängigkeiten
3. **Layer-Abstraktion** - Business Logic unabhängig von Implementation
4. **KISS-Prinzip** - So einfach wie möglich, so komplex wie nötig
5. **KI-freundlich** - Klare Schnittstellen für schrittweise Entwicklung

### 1.3 Technologie-Stack

#### Backend
- **PHP:** 8.1+ (moderne Features, Property Promotion, Enums)
- **Framework:** Slim 4 (leichtgewichtig, PSR-7 HTTP Messages)
- **ORM:** Eloquent Standalone (Layer-Abstraktion, Active Record)
- **IMAP:** php-imap Extension + Custom Wrapper
- **Datenbank:** MySQL 8.0 / MariaDB 10.6+
- **Logging:** Monolog (PSR-3 kompatibel)

#### Frontend
- **JavaScript:** Vanilla ES6+ (kein Framework, KISS)
- **CSS:** Bootstrap 5 (Responsive, Mobile-First)
- **Rich-Text:** Quill.js (E-Mail-Editor)
- **AJAX:** Fetch API (natives Browser-API)

#### Infrastruktur
- **Webserver:** Apache/Nginx (Shared Hosting kompatibel)
- **Cron:** Externer Webcron (cronjob.de, cron-job.org)
- **Verschlüsselung:** OpenSSL (AES-256-CBC)
- **Session:** PHP-Sessions (FileHandler oder Redis)

---

## 2. Architektur-Prinzipien

### 2.1 SOLID-Prinzipien

- **S**ingle Responsibility - Jede Klasse hat eine klare Aufgabe
- **O**pen/Closed - Erweiterbar ohne Änderung (Plugin-System)
- **L**iskov Substitution - Interfaces sind austauschbar
- **I**nterface Segregation - Kleine, fokussierte Interfaces
- **D**ependency Inversion - Abhängig von Abstraktion, nicht Implementation

### 2.2 Layer-Abstraktion (aus basics.txt Kap. 4)

**Prinzip:** Business Logic NIEMALS direkt an Implementation koppeln.

**Beispiel:**
```php
// ❌ FALSCH: Business Logic direkt an MySQL gekoppelt
class ThreadService {
    public function assignThread($id, $userId) {
        $pdo->exec("UPDATE threads SET assigned_to = $userId WHERE id = $id");
    }
}

// ✅ RICHTIG: Business Logic nutzt Abstraction
class ThreadService {
    public function __construct(
        private ThreadRepositoryInterface $threadRepo // Interface!
    ) {}
    
    public function assignThread($id, $userId) {
        $thread = $this->threadRepo->findById($id);
        $thread->assigned_to = $userId;
        $this->threadRepo->save($thread);
    }
}
```

**Vorteil:** Migration MySQL → MongoDB erfordert nur neue Repository-Implementierung!

### 2.3 Dependency Injection

**Prinzip:** Abhängigkeiten werden "injiziert", nicht intern erstellt.

```php
// ❌ FALSCH: Abhängigkeit wird intern erstellt
class ThreadService {
    private $logger;
    
    public function __construct() {
        $this->logger = new FileLogger(); // Hard-coded!
    }
}

// ✅ RICHTIG: Abhängigkeit wird injiziert
class ThreadService {
    public function __construct(
        private LoggerInterface $logger // Interface wird injiziert
    ) {}
}

// Container bindet Implementation:
$container->singleton(LoggerInterface::class, function() {
    return new MonologLogger();
});
```

### 2.4 Repository Pattern

**Prinzip:** Data Access Layer abstrahiert Datenbankzugriff.

```php
// Interface (Data Access Layer)
interface ThreadRepositoryInterface {
    public function findById(int $id): ?Thread;
    public function save(Thread $thread): bool;
    public function delete(int $id): bool;
}

// Implementation: MySQL via Eloquent
class ThreadRepository implements ThreadRepositoryInterface {
    public function findById(int $id): ?Thread {
        return Thread::find($id); // Eloquent
    }
}

// Implementation 2: MongoDB (später)
class MongoThreadRepository implements ThreadRepositoryInterface {
    public function findById(int $id): ?Thread {
        return $this->collection->findOne(['_id' => $id]); // MongoDB
    }
}
```

---

## 3. Schichtenarchitektur (Layered Architecture)

Gemäß `basics.txt` Kapitel 4: Geschäftslogik NIEMALS direkt an Implementation koppeln.

```
┌─────────────────────────────────────────────────────────┐
│           PRESENTATION LAYER (UI)                       │
│   - Views (Twig/PHP Templates)                          │
│   - JavaScript (UI-Interaktionen, Polling)              │
│   - CSS (Bootstrap 5)                                   │
├─────────────────────────────────────────────────────────┤
│           API/CONTROLLER LAYER                          │
│   - Slim Routes (HTTP Handling)                         │
│   - Request Validation & Transformation                 │
│   - Response Formatting (JSON/HTML)                     │
│   - Middleware (Auth, CSRF, Rate-Limiting)              │
├─────────────────────────────────────────────────────────┤
│           SERVICE LAYER (Business Logic)                │
│   - ThreadService, AssignmentService                    │
│   - EmailSendService, AuthService                       │
│   - ThreadingService (E-Mail → Thread-Matching)         │
│   ← HIER ist die Geschäftslogik!                        │
├─────────────────────────────────────────────────────────┤
│           DATA ACCESS LAYER (Abstraktion!)              │
│   - Interfaces: ThreadRepositoryInterface               │
│   - Interfaces: UserRepositoryInterface                 │
│   - Interfaces: ImapClientInterface                     │
│   ← Abstrakte Schnittstellen, KEINE Implementation!     │
├─────────────────────────────────────────────────────────┤
│           IMPLEMENTATION LAYER (austauschbar!)          │
│   - ThreadRepository, EloquentEmailRepository (MySQL)   │
│   - PhpImapClient (IMAP)                                │
│   - RedisSessionHandler (Sessions)                      │
│   ← Konkrete Implementierungen, AUSTAUSCHBAR!           │
└─────────────────────────────────────────────────────────┘

         ┌─────────────────────────────────┐
         │   INFRASTRUCTURE LAYER          │
         │   - Logger (Monolog)            │
         │   - Config (DotEnv)             │
         │   - Encryption (OpenSSL)        │
         │   - Database (Eloquent)         │
         └─────────────────────────────────┘
```

### Layer-Regeln:

1. ✅ **Presentation Layer** darf nur **Controller Layer** nutzen
2. ✅ **Controller Layer** darf nur **Service Layer** nutzen
3. ✅ **Service Layer** darf nur **Data Access Layer (Interfaces!)** nutzen
4. ✅ **Data Access Layer** definiert nur **Interfaces** (keine Implementation!)
5. ✅ **Implementation Layer** implementiert **Data Access Interfaces**

❌ **VERBOTEN:** Presentation Layer → direkt zur Datenbank!

---

## 4. System-Architektur-Diagramm

### 4.1 Komponenten-Übersicht

```
┌──────────────────────────────────────────────────────────────────┐
│                        BROWSER (Client)                          │
│   - HTML/CSS/JavaScript                                          │
│   - Polling alle 15 Sekunden (Fetch API)                         │
└───────────────────────────┬──────────────────────────────────────┘
                            │ HTTP/HTTPS
┌───────────────────────────▼──────────────────────────────────────┐
│                    WEBSERVER (Apache/Nginx)                      │
│   - DocumentRoot: src/public/                                    │
│   - mod_rewrite (Clean URLs)                                     │
│   - SSL/TLS (Let's Encrypt)                                      │
└───────────────────────────┬──────────────────────────────────────┘
                            │ PHP-FPM
┌───────────────────────────▼──────────────────────────────────────┐
│                     CI-INBOX APPLICATION                         │
│                                                                  │
│  ┌────────────────────────────────────────────────────────┐    │
│  │              CORE (src/core/)                          │    │
│  │  - Application.php (Main App)                          │    │
│  │  - Container.php (DI-Container, PSR-11)                │    │
│  │  - HookManager.php (Plugin-Hooks)                      │    │
│  │  - ModuleLoader.php (Auto-load Modules)                │    │
│  └────────────────────────────────────────────────────────┘    │
│                                                                  │
│  ┌────────────────────────────────────────────────────────┐    │
│  │           MODULES (src/modules/)                       │    │
│  │  - logger/       → Zentrales Logging (Monolog)         │    │
│  │  - config/       → .env Loading, Caching               │    │
│  │  - encryption/   → AES-256-CBC                         │    │
│  │  - imap/         → IMAP-Client & Parser                │    │
│  │  - auth/         → Authentifizierung                   │    │
│  └────────────────────────────────────────────────────────┘    │
│                                                                  │
│  ┌────────────────────────────────────────────────────────┐    │
│  │         APPLICATION (src/app/)                         │    │
│  │                                                          │    │
│  │  Controllers/    → HTTP-Request-Handling               │    │
│  │  Services/       → Business Logic                      │    │
│  │  Repositories/   → Data Access (Interfaces!)           │    │
│  │  Models/         → Eloquent Models                     │    │
│  │  Middleware/     → Auth, CSRF, Rate-Limiting           │    │
│  └────────────────────────────────────────────────────────┘    │
│                                                                  │
└──────────────────┬───────────────────────┬──────────────────────┘
                   │                       │
       ┌───────────▼──────────┐  ┌────────▼──────────────┐
       │   DATABASE (MySQL)   │  │  IMAP-SERVER (extern) │
       │   - threads          │  │  - info@example.com   │
       │   - emails           │  │  - User-Postfächer    │
       │   - users            │  │                       │
       │   - internal_notes   │  │                       │
       │   - activity_log     │  │                       │
       └──────────────────────┘  └───────────────────────┘

┌──────────────────────────────────────────────────────────────────┐
│              WEBCRON-SERVICE (extern)                            │
│   - cronjob.de / cron-job.org                                    │
│   - Ruft alle 5-15 Min auf: /webhooks/poll-emails.php?token=X   │
└───────────────────────────┬──────────────────────────────────────┘
                            │ HTTPS
                            └──────────► CI-Inbox (Webhook)
```

### 4.2 Datenfluss: E-Mail-Polling

```
1. Webcron-Service (extern)
   │
   ├─► GET /webhooks/poll-emails.php?token=SECRET_TOKEN
   │
2. CI-Inbox: Token-Validierung (5.2)
   │
   ├─► CronService->pollEmails()
   │
3. ImapClient->connect(info@example.com)
   │
   ├─► ImapClient->getMessages(unreadOnly=true)
   │
4. Für jede neue Mail:
   │
   ├─► EmailParser->parse($message)
   │   └─► ParsedEmailDTO
   │
   ├─► ThreadingService->assignEmailToThread($parsedEmail)
   │   ├─► Suche Thread via Message-ID / In-Reply-To
   │   └─► Erstelle neuen Thread oder füge zu existierendem hinzu
   │
   ├─► EmailRepository->save($email)
   │   └─► DB: INSERT INTO emails (...)
   │
   └─► Logger->info("Email processed", ['uid' => ...])

5. Response: JSON {"success": true, "new_emails": 3}
```

### 4.3 Datenfluss: User weist Thread zu

```
1. User klickt in UI: "Mir zuweisen"
   │
   ├─► POST /api/thread/123/assign
   │   Body: {"user_id": 5}
   │
2. AuthMiddleware->validate()
   │
3. ThreadController->assign()
   │
   ├─► ThreadService->assignThread($threadId, $userId)
   │   │
   │   ├─► ThreadRepository->findById($threadId)
   │   │   └─► DB: SELECT * FROM threads WHERE id = 123
   │   │
   │   ├─► $thread->assigned_to = $userId
   │   ├─► $thread->status = 'assigned'
   │   ├─► $thread->assigned_at = now()
   │   │
   │   ├─► ThreadRepository->save($thread)
   │   │   └─► DB: UPDATE threads SET assigned_to=5, status='assigned' ...
   │   │
   │   ├─► ActivityLog->create([
   │   │       'action' => 'thread_assigned',
   │   │       'user_id' => 5,
   │   │       'entity_id' => 123
   │   │   ])
   │   │   └─► DB: INSERT INTO activity_log (...)
   │   │
   │   └─► Logger->info("Thread assigned", ['thread' => 123, 'user' => 5])
   │
4. Response: JSON {"success": true, "thread": {...}}
   │
5. UI: Aktualisierung (Thread-Zeile wird updated)
```

---

## 5. Modul-System & Plugin-Architecture

### 5.1 Modul-Struktur

Jedes Modul in `src/modules/` folgt dieser Struktur:

```
src/modules/<module-name>/
├── module.json              # Manifest (Metadaten)
├── src/                     # PHP-Code
│   ├── <ModuleName>Service.php
│   └── ...
├── config/                  # Konfiguration
│   └── <module>.config.php
├── tests/                   # Tests
│   └── <ModuleName>Test.php
└── README.md                # Dokumentation
```

### 5.2 Modul-Manifest (module.json)

```json
{
  "name": "logger",
  "version": "1.0.0",
  "description": "Central logging system",
  "author": "CI-Inbox Team",
  "hooks": ["onAppInit", "onError", "onShutdown"],
  "dependencies": ["config"],
  "autoload": {
    "psr-4": {
      "CiInbox\\Modules\\Logger\\": "src/"
    }
  },
  "provides": {
    "LoggerInterface": "CiInbox\\Modules\\Logger\\LoggerService"
  }
}
```

### 5.3 Hook-System

**Core Hooks (Phase 1):**

| Hook | Zeitpunkt | Use Case |
|------|-----------|----------|
| `onAppInit` | App-Initialisierung | Module registrieren |
| `onConfigLoad` | Config geladen | Config-Validierung |
| `onBeforeRequest` | Vor HTTP-Request | Request-Logging |
| `onAfterResponse` | Nach HTTP-Response | Performance-Tracking |
| `onError` | Bei Fehler | Error-Logging |
| `onShutdown` | Vor Beenden | Cleanup |

**Verwendung:**

```php
// In LoggerModule.php
class LoggerModule implements ModuleInterface {
    public function registerHooks(HookManager $hooks): void {
        $hooks->on('onError', [$this, 'logError']);
        $hooks->on('onAppInit', [$this, 'initLogger']);
    }
    
    public function logError(\Throwable $e): void {
        $this->logger->exception($e);
    }
}

// In Application.php
$hooks->trigger('onError', $exception);
```

### 5.4 Modul-Lifecycle

```
1. Application->boot()
2. ModuleLoader->loadModules()
   ├─► Liest alle module.json aus src/modules/
   ├─► Prüft Dependencies (Reihenfolge!)
   └─► Registriert Autoloading (Composer PSR-4)
3. Für jedes Modul:
   ├─► HookManager->register($module)
   ├─► Container->bind(Interfaces, Implementations)
   └─► Trigger 'onAppInit' Hook
4. Application->run()
```

---

## 6. Datenmodell (Database Schema)

### 6.1 Entity-Relationship-Diagramm

```
┌─────────────────┐
│     USERS       │
├─────────────────┤
│ id (PK)         │
│ username        │◄──────────┐
│ email           │           │
│ password_hash   │           │
│ role            │           │ 1:N
│ imap_host (enc) │           │
│ imap_user (enc) │           │
│ smtp_host (enc) │           │
│ created_at      │           │
└─────────────────┘           │
                              │
┌─────────────────┐           │
│    THREADS      │           │
├─────────────────┤           │
│ id (PK)         │           │
│ thread_uid      │           │
│ subject         │           │
│ first_sender    │           │
│ status          │           │
│ assigned_to (FK)├───────────┘
│ assigned_at     │
│ created_at      │
│ last_activity   │◄──────────┐
└─────────────────┘           │
        │                     │ 1:N
        │ 1:N                 │
        │                     │
┌───────▼─────────┐   ┌───────┴─────────┐
│     EMAILS      │   │ INTERNAL_NOTES  │
├─────────────────┤   ├─────────────────┤
│ id (PK)         │   │ id (PK)         │
│ thread_id (FK)  │   │ thread_id (FK)  │
│ imap_uid        │   │ user_id (FK)    │
│ message_id      │   │ note_text       │
│ in_reply_to     │   │ created_at      │
│ sender_email    │   └─────────────────┘
│ subject         │
│ body_text       │
│ body_html       │
│ received_at     │
│ created_at      │◄──────────┐
└─────────────────┘           │
        │                     │ 1:N
        │ 1:N                 │
        │                     │
┌───────▼─────────┐   ┌───────┴─────────┐
│  ATTACHMENTS    │   │  ACTIVITY_LOG   │
├─────────────────┤   ├─────────────────┤
│ id (PK)         │   │ id (PK)         │
│ email_id (FK)   │   │ user_id (FK)    │
│ filename        │   │ action          │
│ mime_type       │   │ entity_type     │
│ size            │   │ entity_id       │
│ storage_path    │   │ details (JSON)  │
│ created_at      │   │ created_at      │
└─────────────────┘   └─────────────────┘

┌─────────────────┐
│ SYSTEM_CONFIG   │
├─────────────────┤
│ id (PK)         │
│ key (unique)    │
│ value (enc?)    │
│ is_encrypted    │
│ created_at      │
└─────────────────┘
```

### 6.2 Tabellen-Details

#### **users**
```sql
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    
    -- Persönliche IMAP/SMTP-Zugangsdaten (für Workflow C)
    imap_host VARCHAR(255) NULL,
    imap_port INT NULL,
    imap_username TEXT NULL,      -- Verschlüsselt!
    imap_password TEXT NULL,      -- Verschlüsselt!
    imap_ssl BOOLEAN DEFAULT TRUE,
    
    smtp_host VARCHAR(255) NULL,
    smtp_port INT NULL,
    smtp_username TEXT NULL,      -- Verschlüsselt!
    smtp_password TEXT NULL,      -- Verschlüsselt!
    smtp_ssl BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### **threads**
```sql
CREATE TABLE threads (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    thread_uid VARCHAR(255) UNIQUE NOT NULL,  -- Interner Thread-Identifier
    
    subject VARCHAR(500) NOT NULL,
    first_sender_email VARCHAR(255) NOT NULL,
    first_sender_name VARCHAR(255) NULL,
    
    -- Status basierend auf Workflows aus vision.md
    status ENUM(
        'new',          -- Neu/Unzugewiesen
        'assigned',     -- Zugewiesen (Workflow B)
        'in_progress',  -- In Bearbeitung (Workflow A)
        'done',         -- Erledigt
        'transferred',  -- Persönlich übernommen (Workflow C)
        'archived'      -- Archiviert
    ) DEFAULT 'new',
    
    assigned_to INT UNSIGNED NULL,           -- FK → users.id
    assigned_at TIMESTAMP NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_activity_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_status (status),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_last_activity (last_activity_at),
    INDEX idx_thread_uid (thread_uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### **emails**
```sql
CREATE TABLE emails (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    thread_id INT UNSIGNED NOT NULL,         -- FK → threads.id
    
    imap_uid VARCHAR(255) NOT NULL,          -- IMAP UID (verhindert Duplikate)
    message_id VARCHAR(500) NOT NULL,        -- RFC Message-ID (für Threading)
    in_reply_to VARCHAR(500) NULL,           -- RFC In-Reply-To (für Threading)
    references TEXT NULL,                    -- RFC References (für Threading)
    
    sender_email VARCHAR(255) NOT NULL,
    sender_name VARCHAR(255) NULL,
    recipient_email VARCHAR(255) NOT NULL,
    recipient_name VARCHAR(255) NULL,
    
    subject VARCHAR(500) NULL,
    body_text TEXT NULL,                     -- Plain-Text (sanitized)
    body_html MEDIUMTEXT NULL,               -- HTML (XSS-safe via HTML Purifier)
    
    received_at TIMESTAMP NOT NULL,          -- Original E-Mail-Datum
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_imap_uid (imap_uid),
    INDEX idx_thread_id (thread_id),
    INDEX idx_message_id (message_id),
    INDEX idx_received_at (received_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### **internal_notes**
```sql
CREATE TABLE internal_notes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    thread_id INT UNSIGNED NOT NULL,         -- FK → threads.id
    user_id INT UNSIGNED NOT NULL,           -- FK → users.id (Verfasser)
    
    note_text TEXT NOT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    
    INDEX idx_thread_id (thread_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### **email_attachments**
```sql
CREATE TABLE email_attachments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email_id INT UNSIGNED NOT NULL,          -- FK → emails.id
    
    filename VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    size INT UNSIGNED NOT NULL,              -- Bytes
    storage_path VARCHAR(500) NOT NULL,      -- Pfad in data/uploads/
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (email_id) REFERENCES emails(id) ON DELETE CASCADE,
    
    INDEX idx_email_id (email_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### **activity_log** (Audit-Trail für Nachvollziehbarkeit)
```sql
CREATE TABLE activity_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,               -- FK → users.id (NULL = System)
    
    action ENUM(
        'thread_assigned',
        'thread_unassigned',
        'status_changed',
        'note_added',
        'note_updated',
        'note_deleted',
        'email_sent',
        'email_received',
        'thread_transferred',
        'thread_archived',
        'user_login',
        'user_logout'
    ) NOT NULL,
    
    entity_type VARCHAR(50) NOT NULL,        -- 'threads', 'emails', 'users'
    entity_id INT UNSIGNED NOT NULL,
    
    details JSON NULL,                       -- Zusätzliche Informationen
    ip_address VARCHAR(45) NULL,             -- IPv4/IPv6
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### **system_config** (Admin-Settings)
```sql
CREATE TABLE system_config (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) UNIQUE NOT NULL,
    `value` TEXT NULL,
    is_encrypted BOOLEAN DEFAULT FALSE,      -- TRUE = Wert ist verschlüsselt
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_key (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Beispiel-Einträge:
-- ('main_imap_host', 'imap.example.com', FALSE)
-- ('main_imap_password', '<encrypted>', TRUE)
-- ('webcron_secret_token', '<encrypted>', TRUE)
-- ('polling_interval', '300', FALSE) -- Sekunden
```

### 6.3 Indizes & Performance

**Wichtige Indizes für Performance:**

1. `threads.status` + `assigned_to` - Für Inbox-Filter
2. `threads.last_activity_at` - Für Sortierung
3. `emails.thread_id` + `received_at` - Für Thread-Ansicht
4. `emails.message_id` - Für Threading-Logik
5. `activity_log.entity_type` + `entity_id` - Für Audit-Trail

**Abfrage-Optimierung:**

```sql
-- Typische Abfrage: Inbox-Übersicht
SELECT t.*, u.username as assigned_user, 
       (SELECT COUNT(*) FROM emails WHERE thread_id = t.id) as email_count
FROM threads t
LEFT JOIN users u ON t.assigned_to = u.id
WHERE t.status IN ('new', 'assigned', 'in_progress')
ORDER BY t.last_activity_at DESC
LIMIT 50;

-- Index-Hint:
-- INDEX idx_status_activity (status, last_activity_at)
```

---

## 7. Komponenten-Beschreibung

### 7.1 Core-Module

#### **Logger-Modul** (`src/modules/logger/`)

**Zweck:** Zentrales Logging für gesamte Anwendung

**Features:**
- PSR-3 kompatibel (Monolog-basiert)
- Log-Level: DEBUG, INFO, WARNING, ERROR, EXCEPTION, SUCCESS, FAILURE, ANNOUNCEMENT
- Handler: File, Database, später: Email, Slack
- Formatter: JSON mit Pflichtfeldern (timestamp, level, module, file, line, function, message, context)

**Interface:**
```php
interface LoggerInterface {
    public function debug(string $message, array $context = []): void;
    public function info(string $message, array $context = []): void;
    public function error(string $message, array $context = []): void;
    // ...
}
```

---

#### **Config-Modul** (`src/modules/config/`)

**Zweck:** Zentrale Konfigurationsverwaltung

**Features:**
- .env-File laden (DotEnv)
- Config-Caching (für Performance)
- Type-safe Access
- Validation (JSON Schema)

**Interface:**
```php
interface ConfigInterface {
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value): void;
    public function reload(): void;
}
```

---

#### **Encryption-Modul** (`src/modules/encryption/`)

**Zweck:** Verschlüsselung sensibler Daten (IMAP-Passwörter)

**Features:**
- AES-256-CBC
- Key aus .env (außerhalb Git!)
- IV (Initialization Vector) pro Verschlüsselung

**Interface:**
```php
interface EncryptionInterface {
    public function encrypt(string $data): string;
    public function decrypt(string $encrypted): string;
}
```

---

#### **IMAP-Modul** (`src/modules/imap/`)

**Zweck:** IMAP-Client & E-Mail-Parser

**Komponenten:**
- `ImapClient.php` - Connection-Handling
- `ImapMailbox.php` - Folder-Operations
- `ImapMessage.php` - Message-Objekt
- `Parser/EmailParser.php` - E-Mail-Parsing
- `Sanitizer/HtmlSanitizer.php` - XSS-Protection

**Interface:**
```php
interface ImapClientInterface {
    public function connect(string $host, int $port, string $username, string $password, bool $ssl = true): bool;
    public function getMessages(int $limit = 100, bool $unreadOnly = false): array;
    public function getMessage(string $uid): ImapMessageInterface;
}

interface EmailParserInterface {
    public function parse(ImapMessageInterface $message): ParsedEmailDTO;
}
```

---

### 7.2 Application-Services

#### **ThreadService** (`src/app/Services/ThreadService.php`)

**Zweck:** Business Logic für Threads

**Methoden:**
```php
class ThreadService {
    public function createThread(CreateThreadDTO $data): Thread;
    public function assignThread(int $threadId, int $userId): bool;
    public function changeStatus(int $threadId, string $newStatus): bool;
    public function addNote(int $threadId, int $userId, string $noteText): InternalNote;
    public function getThreadWithEmails(int $threadId): ThreadDTO;
}
```

---

#### **ThreadingService** (`src/app/Services/ThreadingService.php`)

**Zweck:** E-Mail → Thread-Matching

**Logik:**
1. Prüfe `In-Reply-To` Header → Finde Thread via `message_id`
2. Prüfe `References` Header → Finde Thread-Chain
3. Fallback: Betreff-Matching (`Re: Original Betreff`)
4. Sonst: Neuen Thread erstellen

**Methoden:**
```php
class ThreadingService {
    public function assignEmailToThread(ParsedEmailDTO $email): Thread;
    public function findThreadByMessageId(string $messageId): ?Thread;
    public function findThreadBySubject(string $subject): ?Thread;
}
```

---

#### **EmailSendService** (`src/app/Services/EmailSendService.php`)

**Zweck:** E-Mail-Versand via SMTP

**Features:**
- Senden über gemeinsame Adresse (info@) - Workflow A
- Senden über persönliche Adresse - Workflow C
- Korrekte Threading-Header (In-Reply-To, References)
- HTML-E-Mails mit Plain-Text-Fallback

**Methoden:**
```php
class EmailSendService {
    public function sendReply(
        Thread $thread,
        User $user,
        string $bodyHtml,
        string $fromEmail = 'info@'
    ): bool;
}
```

---

#### **CronService** (`src/app/Services/CronService.php`)

**Zweck:** Webcron-Polling-Logik

**Ablauf:**
1. IMAP-Verbindung öffnen
2. Neue Mails abrufen (unread, nicht in DB)
3. Jede Mail parsen
4. Threading-Logik anwenden
5. In DB speichern
6. Logger & Activity-Log

**Methoden:**
```php
class CronService {
    public function pollEmails(): PollResultDTO;
}
```

---

## 8. Sicherheits-Architektur

### 8.1 Threat-Modell

**Potenzielle Bedrohungen:**

1. **XSS (Cross-Site Scripting)** - E-Mail-Bodies können bösartiges HTML enthalten
2. **CSRF (Cross-Site Request Forgery)** - Unberechtigte Aktionen
3. **SQL-Injection** - Direkte DB-Zugriffe
4. **Session-Hijacking** - Session-Cookies abfangen
5. **Brute-Force-Attacken** - Login-Versuche
6. **IMAP-Password-Exposure** - Sensible Daten in DB

### 8.2 Sicherheitsmaßnahmen

#### **1. Datenverschlüsselung (Priority #1)**

**Problem:** IMAP/SMTP-Passwörter in Datenbank

**Lösung:**
```php
class EncryptionService {
    private string $key; // Aus .env, 32 Bytes (AES-256)
    
    public function encrypt(string $data): string {
        $iv = openssl_random_pseudo_bytes(16); // Zufälliger IV
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $this->key, 0, $iv);
        return base64_encode($iv . $encrypted); // IV + Encrypted Data
    }
    
    public function decrypt(string $encrypted): string {
        $data = base64_decode($encrypted);
        $iv = substr($data, 0, 16);
        $ciphertext = substr($data, 16);
        return openssl_decrypt($ciphertext, 'AES-256-CBC', $this->key, 0, $iv);
    }
}
```

**Key-Management:**
- Key in `.env`: `ENCRYPTION_KEY=base64:...` (32 Bytes)
- Key NIEMALS in Git committen
- Key-Rotation alle 6 Monate (Dokumentiert in `docs/admin/deployment.md`)
- Backup des Keys sicher speichern (außerhalb Webserver)

---

#### **2. XSS-Prevention**

**Problem:** E-Mail-Bodies können `<script>` Tags enthalten

**Lösung:**
```php
use HTMLPurifier;

class HtmlSanitizer {
    private HTMLPurifier $purifier;
    
    public function sanitize(string $html): string {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,br,strong,em,ul,ol,li,a[href],img[src|alt]');
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        
        $this->purifier = new HTMLPurifier($config);
        return $this->purifier->purify($html);
    }
}
```

**CSP-Header:**
```php
// In Middleware
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'");
```

---

#### **3. CSRF-Protection**

**Lösung:** Token-basiert in allen Forms

```php
// Session: Token generieren
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Form:
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

// Middleware: Validierung
if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    throw new SecurityException('CSRF token mismatch');
}
```

---

#### **4. SQL-Injection Prevention**

**Lösung:** Eloquent ORM (verwendet Prepared Statements)

```php
// ✅ SICHER: Eloquent
Thread::where('id', $_GET['id'])->first();

// ❌ UNSICHER: Raw Query
DB::raw("SELECT * FROM threads WHERE id = " . $_GET['id']);
```

---

#### **5. Session-Management**

```php
// Session-Config
ini_set('session.cookie_httponly', '1');    // Kein JavaScript-Zugriff
ini_set('session.cookie_secure', '1');      // Nur über HTTPS
ini_set('session.cookie_samesite', 'Strict'); // CSRF-Protection
ini_set('session.gc_maxlifetime', '1800');  // 30 Min Timeout

// Session-Regeneration nach Login
session_regenerate_id(true);
```

---

#### **6. Rate-Limiting**

```php
class RateLimiter {
    public function checkLoginAttempts(string $username): bool {
        $key = "login_attempts:$username";
        $attempts = $this->cache->get($key, 0);
        
        if ($attempts >= 5) {
            return false; // Blocked
        }
        
        $this->cache->set($key, $attempts + 1, 900); // 15 Min
        return true;
    }
}
```

---

#### **7. Webcron-Authentifizierung**

```php
// .env
WEBCRON_SECRET_TOKEN=random_64_char_token_here

// webhooks/poll-emails.php
$token = $_GET['token'] ?? '';
if (!hash_equals($_ENV['WEBCRON_SECRET_TOKEN'], $token)) {
    http_response_code(403);
    die('Unauthorized');
}
```

---

## 9. Deployment-Architektur

### 9.1 Shared-Hosting-Setup

```
Shared Hosting (Webspace)
├── public_html/               ← DocumentRoot
│   └── ci-inbox/
│       ├── index.php          ← Symlink zu ../private/src/public/index.php
│       ├── .htaccess          ← Rewrite-Rules
│       └── assets/            ← Symlink zu ../private/src/public/assets/
│
└── private/                   ← Außerhalb DocumentRoot!
    ├── ci-inbox/
    │   ├── src/               ← Codebase
    │   ├── data/              ← Runtime-Daten (nicht im Web!)
    │   ├── logs/              ← Log-Dateien
    │   ├── .env               ← Konfiguration (NICHT im Git!)
    │   ├── composer.json
    │   └── vendor/
    └── backups/               ← DB-Backups
```

### 9.2 Webserver-Konfiguration

#### Apache (.htaccess)
```apache
# public_html/ci-inbox/.htaccess

RewriteEngine On

# Redirect HTTP → HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Redirect alles zu index.php (außer existierende Dateien)
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]

# Security Headers
<IfModule mod_headers.c>
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-Content-Type-Options "nosniff"
    Header set X-XSS-Protection "1; mode=block"
    Header set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'"
</IfModule>
```

### 9.3 Webcron-Setup (cronjob.de)

**Schritt 1:** Account bei cronjob.de / cron-job.org erstellen

**Schritt 2:** Cronjob anlegen:
- URL: `https://ci-inbox.example.com/webhooks/poll-emails.php?token=SECRET_TOKEN_HERE`
- Interval: Alle 5-15 Minuten
- Methode: GET oder POST
- Timeout: 30 Sekunden

**Schritt 3:** Secret Token in `.env`:
```
WEBCRON_SECRET_TOKEN=random_64_char_token_generate_via_openssl
```

**Token generieren:**
```bash
openssl rand -hex 32
```

---

## 10. Skalierungsplanung

### 10.1 Aktuelle Skalierung (MVP - v1.0)

**Zielgruppe:** 3-7 User

**Hardware:**
- Shared Hosting (1-2 CPU-Cores)
- 512 MB - 1 GB RAM
- 5-10 GB Speicher
- MySQL Shared-Instanz

**Traffic:**
- ~50-100 Requests/Stunde
- Webcron: 12 Polls/Stunde (alle 5 Min)
- UI-Polling: 7 User × 4 Polls/Min = 28 Requests/Min

**Bottlenecks:**
- IMAP-Polling (kann bis 30s dauern)
- HTML-Sanitization (CPU-intensiv)

---

### 10.2 Mittlere Skalierung (v2.0 - 20-50 User)

**Änderungen:**
1. **VPS statt Shared Hosting**
   - 2-4 CPU-Cores
   - 2-4 GB RAM
   - Eigener MySQL-Server

2. **Caching-Layer einführen:**
   - Redis für Sessions
   - Redis für Config-Cache
   - APCu für Opcode-Cache

3. **Async-Polling:**
   - Queue-System (Redis Queue)
   - Background-Worker für E-Mail-Processing

4. **CDN für Assets:**
   - CloudFlare/Bunny CDN für CSS/JS/Images

**Migration:**
- Dank Repository-Pattern: Einfach!
- `ThreadRepository`, `EloquentEmailRepository` bleiben gleich
- Nur Infrastructure-Layer ändert sich

---

### 10.3 Große Skalierung (v3.0+ - 100+ User)

**Änderungen:**
1. **Multi-Server-Setup:**
   - Load-Balancer (Nginx)
   - 2-4 App-Server (PHP-FPM)
   - Dedizierter MySQL-Server (Master-Slave-Replication)
   - Redis-Cluster (Sessions, Cache, Queues)

2. **Microservices:**
   - IMAP-Polling-Service (separater Worker)
   - E-Mail-Send-Service (separater Worker)
   - WebSocket-Server (für Echtzeit-Updates)

3. **Datenbank-Optimierung:**
   - Sharding nach Team/Mandant
   - Read-Replicas für Reports
   - Archive-Tabellen für alte Threads

**Migration:**
- Repository-Pattern erlaubt schrittweise Migration
- Services bleiben unverändert (Layer-Abstraktion!)

---

## Zusammenfassung

### Architektur-Highlights:

1. ✅ **Layer-Abstraktion** - Business Logic unabhängig von Implementation
2. ✅ **Repository-Pattern** - Einfache DB-Migration (MySQL → MongoDB)
3. ✅ **Modul-System** - Plugin-Architektur für Erweiterbarkeit
4. ✅ **SOLID-Prinzipien** - Saubere, wartbare Codebase
5. ✅ **Security-First** - Verschlüsselung, XSS-Protection, CSRF-Tokens
6. ✅ **KI-freundlich** - Klare Interfaces, Standalone-Komponenten
7. ✅ **Skalierbar** - Von 7 Usern bis 100+ User ohne Rewrite

---

## 11. Implementierte Patterns (M0 Foundation) ✅

**Stand:** 17. November 2025 - M0 Complete

### 11.1 Dependency Injection Container (PHP-DI)

**Pattern:** Service Container mit Auto-Wiring

**Implementierung:**
```
src/core/Container.php          # Wrapper für PHP-DI
src/config/container.php        # Service Definitions
```

**Verwendung:**
```php
use CiInbox\Core\Container;

$container = Container::getInstance();
$logger = $container->get(LoggerService::class);
$config = $container->get(ConfigService::class);
```

**Registrierte Services (M0):**
- LoggerService (Monolog-Wrapper)
- ConfigService (ENV + PHP Configs)
- EncryptionService (AES-256-CBC)
- HookManager (Event System)
- ModuleLoader (Auto-Discovery)

**Siehe:** `src/config/container.php` für alle Definitions

---

### 11.2 Hook System (Event-Driven Architecture)

**Pattern:** Observer Pattern für Module-Kommunikation

**Implementierung:**
```
src/core/HookManager.php        # 70 lines
```

**Verwendung:**
```php
// Register Hook
$hookManager->register('module.loaded', function($data) {
    $logger->info('Module loaded', $data);
});

// Trigger Hook
$hookManager->trigger('module.loaded', [
    'module' => 'logger',
    'version' => '1.0.0'
]);
```

**Vorteile:**
- Lose Kopplung zwischen Modulen
- Erweiterbar ohne Core-Änderungen
- KI kann neue Hooks hinzufügen

**Geplante Hooks (M1+):**
- `imap.email_fetched` - Wenn neue E-Mail abgeholt wurde
- `thread.created` - Wenn neuer Thread erstellt wurde
- `thread.assigned` - Wenn Thread zugewiesen wurde
- `label.applied` - Wenn Label angewendet wurde

---

### 11.3 Module Auto-Discovery

**Pattern:** Convention over Configuration

**Implementierung:**
```
src/core/ModuleLoader.php       # 95 lines
```

**Modul-Struktur:**
```
src/modules/{module_name}/
├── module.json                 # Manifest (Name, Version, Dependencies)
├── src/                        # PHP Code
├── config/                     # Config Files
├── tests/                      # Tests
└── README.md                   # Documentation
```

**module.json Beispiel:**
```json
{
  "name": "logger",
  "version": "1.0.0",
  "description": "Logging System (PSR-3)",
  "dependencies": [],
  "hooks": ["app.boot"],
  "services": ["LoggerService"]
}
```

**Vorteile:**
- Neue Module ohne Core-Änderungen
- Dependency-Management auf Modul-Ebene
- KI kann Module unabhängig entwickeln

**Implementierte Module (M0):**
- `logger` (PSR-3 Logging)
- `config` (ENV + PHP Configs)
- `encryption` (AES-256-CBC)

---

### 11.4 Application Bootstrap Pattern

**Pattern:** Front Controller + Application Class

**Implementierung:**
```
src/core/Application.php        # 125 lines - Main Bootstrap
src/public/index.php            # 20 lines - Entry Point
```

**Bootstrap-Ablauf:**
```
1. Load .env (Dotenv)
2. Initialize Container (PHP-DI)
3. Register Services (container.php)
4. Setup Database (Eloquent Capsule)
5. Load Modules (ModuleLoader)
6. Initialize Hooks (HookManager)
7. Load Routes (api.php, web.php)
8. Run Slim Application
```

**Vorteile:**
- Einheitlicher Entry-Point
- Testbar (Application-Instanz mockbar)
- KI kann Bootstrap erweitern ohne index.php zu ändern

**Siehe:** `src/core/Application.php` für Details

---

### 11.5 Database Schema (Eloquent ORM)

**Pattern:** Active Record + Relationships

**Implementierte Tabellen (7):**

#### 1. users
```php
id, email, password_hash, name, role, is_active, last_login_at
```

#### 2. imap_accounts
```php
id, user_id, email, imap_host, imap_port, imap_username,
password_encrypted, encryption, is_default
```

#### 3. threads
```php
id, subject, participants (JSON), preview, status,
last_message_at, message_count, has_attachments
```

#### 4. emails
```php
id, thread_id, message_id, in_reply_to, from, to, cc,
subject, body_plain, body_html, attachments (JSON), sent_at
```

#### 5. labels
```php
id, name, color, display_order
```

#### 6. thread_assignments (Pivot)
```php
id, thread_id, user_id, assigned_at
```

#### 7. thread_labels (Pivot)
```php
id, thread_id, label_id, applied_at
```

**Eloquent Models:**
```
src/app/Models/
├── BaseModel.php               # Base mit Timestamps
├── User.php
├── ImapAccount.php
├── Thread.php
├── Email.php
└── Label.php
```

**Relationships:**
```php
// User Model
public function imapAccounts(): HasMany
public function assignedThreads(): BelongsToMany

// Thread Model
public function emails(): HasMany
public function labels(): BelongsToMany
public function assignedUsers(): BelongsToMany

// Email Model
public function thread(): BelongsTo
```

**Lesson Learned:** Pivot-Tables ohne `withTimestamps()` wenn keine created_at/updated_at Spalten

**Siehe:** `database/migrations/*.php` für Migrationen

---

### 11.6 Configuration Management

**Pattern:** Environment-based + Type-Safe Getters

**Implementierung:**
```
src/modules/config/src/ConfigService.php    # 270 lines
```

**Features:**
- ✅ phpdotenv Integration (.env Loader)
- ✅ Dot-notation (database.connections.mysql.host)
- ✅ Type-Safe Getters (getString, getInt, getBool, getArray)
- ✅ Default-Values Support
- ✅ Validation mit Exceptions
- ✅ Cached (Singleton)

**Config-Dateien:**
```
src/modules/config/config/
├── app.config.php              # Application Settings
├── database.config.php         # DB Connections
└── logger.config.php           # Logging Settings
```

**Verwendung:**
```php
$config = $container->get(ConfigService::class);

$dbHost = $config->getString('database.connections.mysql.host');
$dbPort = $config->getInt('database.connections.mysql.port', 3306);
$debug = $config->getBool('app.debug', false);
```

**Siehe:** `src/modules/config/README.md` für Details

---

### 11.7 Logging System (PSR-3)

**Pattern:** Monolog + Custom Formatters

**Implementierung:**
```
src/modules/logger/src/LoggerService.php            # 186 lines
src/modules/logger/src/Formatters/JsonFormatter.php # JSON Format
src/modules/logger/src/Handlers/RotatingFileHandler.php # 30 Tage
```

**Features:**
- ✅ PSR-3 kompatibel (debug, info, warning, error)
- ✅ Custom Levels (success, failure, announcement)
- ✅ JSON-Format mit Backtrace
- ✅ Tägliche Rotation (30 Tage Retention)
- ✅ Exception-Handling
- ✅ Performance: < 1ms/Log

**Verwendung:**
```php
$logger = $container->get(LoggerService::class);

$logger->info('User logged in', ['user_id' => 123]);
$logger->success('Thread assigned', ['thread_id' => 456]);
$logger->error('IMAP connection failed', ['host' => 'imap.example.com']);
$logger->exception($exception, ['context' => 'data']);
```

**Log-Format (JSON):**
```json
{
  "timestamp": "2025-11-17T12:30:45+01:00",
  "level": "INFO",
  "message": "User logged in",
  "context": {"user_id": 123},
  "backtrace": ["file": "...", "line": 42],
  "memory_usage": "2.5 MB",
  "execution_time": "0.15 ms"
}
```

**Siehe:** `src/modules/logger/README.md` für Details

---

### 11.8 Encryption Service

**Pattern:** OpenSSL Wrapper (AES-256-CBC)

**Implementierung:**
```
src/modules/encryption/src/EncryptionService.php    # 220 lines
```

**Features:**
- ✅ AES-256-CBC Encryption
- ✅ Random IV per Encryption
- ✅ Base64-encoded Output (`iv::encrypted`)
- ✅ Config Integration (ENCRYPTION_KEY aus .env)
- ✅ Exception-based Error-Handling
- ✅ Key-Validation

**Verwendung:**
```php
$encryption = $container->get(EncryptionService::class);

$encrypted = $encryption->encrypt('sensitive_password');
// Output: "base64_iv::base64_encrypted_data"

$decrypted = $encryption->decrypt($encrypted);
// Output: "sensitive_password"
```

**Anwendungsfälle:**
- IMAP-Passwörter (imap_accounts.password_encrypted)
- API-Tokens
- Sensible User-Daten

**Siehe:** `src/modules/encryption/README.md` für Details

---

### 11.9 Frontend JavaScript Architektur (M3)

**Pattern:** Modulares IIFE-Pattern mit globalen Namespaces

**Aktualisiert:** 28. November 2025

**Implementierung:**
```
src/public/assets/js/
├── modules/                     # Modulare JS-Komponenten
│   ├── api-client.js           # Zentralisierte API-Aufrufe (Fetch)
│   ├── ui-components.js        # Wiederverwendbare UI (Dialoge, Toasts, Picker)
│   ├── thread-renderer.js      # HTML-Generierung für Thread-Details
│   └── inbox-manager.js        # Event-Handler & Inbox-Steuerung
├── email-composer.js           # E-Mail-Editor (Quill.js Integration)
├── theme-switcher.js           # Dark/Light Mode Toggle
├── user-settings.js            # Benutzereinstellungen
├── admin-settings.js           # Admin-Dashboard
└── user-management.js          # Benutzerverwaltung (Admin)
```

**Modul-Struktur (IIFE-Pattern):**
```javascript
const ModuleName = (function() {
    'use strict';
    
    // Private state
    let privateVar = null;
    
    // Private functions
    function privateHelper() { }
    
    // Public API
    return {
        publicMethod,
        anotherMethod
    };
})();

// Global verfügbar machen
window.ModuleName = ModuleName;
```

**Abhängigkeiten:**
```
inbox-manager.js
    ├── ApiClient (api-client.js)
    ├── UiComponents (ui-components.js)
    └── ThreadRenderer (thread-renderer.js)

ui-components.js
    └── ApiClient (api-client.js)

thread-renderer.js
    └── (standalone, keine Abhängigkeiten)

api-client.js
    └── (standalone, Fetch API Wrapper)
```

**Globale Verfügbarkeit:**
```javascript
// Alle Module sind global zugänglich
window.ApiClient          // API-Aufrufe
window.UiComponents       // UI-Dialoge, Toasts
window.ThreadRenderer     // Thread-Detail Rendering
window.InboxManager       // Inbox-Steuerung

// Backwards Compatibility Aliases
window.showConfirmDialog   = UiComponents.showConfirmDialog
window.showSuccessMessage  = UiComponents.showSuccessMessage
window.showAssignmentPicker = UiComponents.showAssignmentPicker
// etc.
```

**Error Handling:**
- Netzwerkfehler werden in `ApiClient.request()` abgefangen
- Alle async Funktionen haben try/catch
- Fehler werden via `UiComponents.showErrorMessage()` angezeigt
- Console-Logging für Debugging

**Browser-Kompatibilität:**
- ✅ Firefox 55+ (IntersectionObserver, ES6+)
- ✅ Chrome 51+ (IntersectionObserver, ES6+)
- ❌ Internet Explorer (nicht unterstützt)
- ❌ Safari (nicht getestet/unterstützt)

**Feature Detection:**
```javascript
// IntersectionObserver für Auto-Read
if (!('IntersectionObserver' in window)) {
    console.warn('IntersectionObserver not supported');
    return;
}
```

---

### Nächste Schritte:

1. ✅ M0 Foundation Complete
2. ✅ Architecture.md aktualisiert (Implementierte Patterns)
3. ✅ `codebase.md` aktualisiert (Dev-Setup, Code-Konventionen)
4. ✅ `M1-Preparation.md` erstellt (Quick-Start für M1)
5. 🔴 **M1 Sprint 1.1 starten: IMAP-Client-Modul!**

---

**Ende der Architektur-Dokumentation**

*Dieses Dokument wird bei Architektur-Änderungen aktualisiert.*  
*Bei Fragen: Siehe `docs/dev/roadmap.md` für Sprint-Plan und `docs/dev/codebase.md` für Entwicklungs-Setup.*  
*Für M1 Start: Siehe `docs/dev/M1-Preparation.md` für Quick-Context.*

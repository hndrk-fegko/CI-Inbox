# M1 Preparation Guide: IMAP Core

**Für:** KI-gestützte Entwicklung (neue Session)  
**Status:** M0 Foundation ✅ COMPLETED → M1 IMAP Core 🔴 READY TO START  
**Datum:** 17. November 2025

---

## 🎯 Quick Context: Was ist bereits fertig?

### M0 Foundation - COMPLETED ✅

**Alle 5 Sprints erfolgreich abgeschlossen in ~4 Stunden:**

1. **Logger-Modul** (~60 min) - PSR-3 + custom success(), JSON-Format, Rotation
2. **Config-Modul** (~50 min) - ENV + PHP Configs, Dot-notation, Type-safe Getters
3. **Encryption-Service** (~45 min) - AES-256-CBC, Random IV, Base64-Format
4. **Database-Setup** (~35 min) - 7 Tabellen, Eloquent Models, Migrations
5. **Core-Infrastruktur** (~40 min) - DI Container, Hook Manager, Application Class

**Ergebnis:**
- ✅ Application läuft live: http://ci-inbox.local/
- ✅ Health-Check Endpoint: http://ci-inbox.local/api/system/health
- ✅ Alle Module standalone testbar
- ✅ Dokumentation komplett (README für jedes Modul)

---

## 🚀 Quick Start: Projekt aufsetzen

### 1. Environment prüfen

**Systemvoraussetzungen:**
```bash
php --version     # Benötigt: 8.1+ (Aktuell: 8.2.12)
composer --version # Benötigt: 2.5+ (Aktuell: 2.9.1)
mysql --version   # Benötigt: 8.0+
```

**PHP Extensions prüfen:**
```bash
php -m | grep -E "(pdo_mysql|imap|openssl|mbstring|json|curl)"
```

Alle 6 Extensions müssen vorhanden sein.

---

### 2. Repository klonen & Dependencies installieren

```bash
cd /path/to/ci-inbox
composer install
```

**Installierte Dependencies (Production):**
- slim/slim ^4.12 - HTTP Framework
- slim/psr7 ^1.6 - PSR-7 HTTP Messages
- php-di/php-di ^7.0 - Dependency Injection
- illuminate/database ^10.0 - Eloquent ORM
- monolog/monolog ^3.5 - Logging
- vlucas/phpdotenv ^5.5 - ENV Loader
- ezyang/htmlpurifier ^4.16 - XSS Prevention

---

### 3. Environment konfigurieren

```bash
cp .env.example .env
nano .env  # Oder Editor deiner Wahl
```

**Wichtige Settings (.env):**
```env
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
```

**ENCRYPTION_KEY generieren:**
```bash
php -r "echo base64_encode(random_bytes(32)) . PHP_EOL;"
```

---

### 4. Datenbank einrichten

```bash
# Datenbank erstellen
mysql -u root -p -e "CREATE DATABASE ci_inbox CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Migrations ausführen
php database/migrate.php
```

**Erwartete Ausgabe:**
```
=== CI-Inbox Database Migration Runner ===
Found 7 migration(s)

Running: 001_create_users_table.php... ✅ Done
Running: 002_create_imap_accounts_table.php... ✅ Done
Running: 003_create_threads_table.php... ✅ Done
Running: 004_create_emails_table.php... ✅ Done
Running: 005_create_labels_table.php... ✅ Done
Running: 006_create_thread_assignments_table.php... ✅ Done
Running: 007_create_thread_labels_table.php... ✅ Done

=== All migrations completed ===
```

---

### 5. Application testen

```bash
# Health-Check
curl http://ci-inbox.local/api/system/health

# Homepage
curl http://ci-inbox.local/
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

**Status 200** = Alles bereit für M1! 🚀

---

## 📦 Verfügbare Services (DI Container)

**Container-Auflösung (siehe `src/config/container.php`):**

```php
use CiInbox\Core\Container;

$container = Container::getInstance();

// Logger Service (PSR-3 kompatibel)
$logger = $container->get(LoggerService::class);
$logger->info('Test message');
$logger->success('Operation completed');
$logger->error('Something failed', ['context' => 'data']);

// Config Service (Type-Safe Getters)
$config = $container->get(ConfigService::class);
$dbHost = $config->getString('database.connections.mysql.host');
$dbPort = $config->getInt('database.connections.mysql.port');

// Encryption Service (AES-256-CBC)
$encryption = $container->get(EncryptionService::class);
$encrypted = $encryption->encrypt('sensitive_data');
$decrypted = $encryption->decrypt($encrypted);

// IMAP Client (NEW - M1 Sprint 1.1 ✅)
$imap = $container->get(ImapClient::class);
$imap->connect('imap.example.com', 993, 'user', 'pass', true);
$messages = $imap->getMessages(10);

// Hook Manager (Event System)
$hookManager = $container->get(HookManager::class);
$hookManager->register('imap.email_fetched', function($data) {
    // Handle event
});
$hookManager->trigger('imap.email_fetched', ['email' => $emailData]);

// Database (Eloquent Models)
use CiInbox\App\Models\User;
use CiInbox\App\Models\Thread;
use CiInbox\App\Models\Email;

$user = User::find(1);
$threads = Thread::where('status', 'open')->get();
```

---

## 🧪 Setup & Testing Tools (NEW)

### Setup Auto-Discovery Wizard ⭐

**File:** `src/modules/imap/tests/setup-autodiscover.php`

Intelligenter Setup-Assistent mit automatischer SMTP/IMAP-Erkennung.

**Usage:**
```bash
C:\xampp\php\php.exe src/modules/imap/tests/setup-autodiscover.php
```

**Features:**
- ✅ Auto-detect IMAP/SMTP servers from email domain
- ✅ Test 8 SMTP configurations automatically
- ✅ Scan all IMAP folders (filter-compatible!)
- ✅ Save to .env + setup-config.json

**See:** `docs/dev/Setup-Autodiscover.md` for full documentation

### Test Scripts

**Mercury Quick Test** (Development):
```bash
C:\xampp\php\php.exe src/modules/imap/tests/mercury-quick-test.php
```

**All test scripts:** See `src/modules/imap/tests/README.md`

---

## 📦 Verfügbare Services (DI Container)

**Container-Auflösung (siehe `src/config/container.php`):**

```php
use CiInbox\Core\Container;

$container = Container::getInstance();

// Logger Service (PSR-3 kompatibel)
$logger = $container->get(LoggerService::class);
$logger->info('Test message');
$logger->success('Operation completed');
$logger->error('Something failed', ['context' => 'data']);

// Config Service (Type-Safe Getters)
$config = $container->get(ConfigService::class);
$dbHost = $config->getString('database.connections.mysql.host');
$dbPort = $config->getInt('database.connections.mysql.port');

// Encryption Service (AES-256-CBC)
$encryption = $container->get(EncryptionService::class);
$encrypted = $encryption->encrypt('sensitive_data');
$decrypted = $encryption->decrypt($encrypted);

// Hook Manager (Event System)
$hookManager = $container->get(HookManager::class);
$hookManager->register('imap.email_fetched', function($data) {
    // Handle event
});
$hookManager->trigger('imap.email_fetched', ['email' => $emailData]);

// Database (Eloquent Models)
use CiInbox\App\Models\User;
use CiInbox\App\Models\Thread;
use CiInbox\App\Models\Email;

$user = User::find(1);
$threads = Thread::where('status', 'open')->get();
```

---

## 🗄️ Datenbank-Schema (7 Tabellen)

### 1. users
```sql
id, email, password_hash, name, role, is_active, last_login_at, created_at, updated_at
```

### 2. imap_accounts
```sql
id, user_id, email, imap_host, imap_port, imap_username, 
password_encrypted, encryption, is_default, created_at, updated_at
```

### 3. threads
```sql
id, subject, participants (JSON), preview, status, 
last_message_at, message_count, has_attachments, created_at, updated_at
```

### 4. emails
```sql
id, thread_id, message_id, in_reply_to, from, to, cc, subject, 
body_plain, body_html, attachments (JSON), sent_at, created_at, updated_at
```

### 5. labels
```sql
id, name, color, display_order, created_at, updated_at
```

### 6. thread_assignments (Pivot)
```sql
id, thread_id, user_id, assigned_at
```

### 7. thread_labels (Pivot)
```sql
id, thread_id, label_id, applied_at
```

**Eloquent Relationships:**
- User → hasMany ImapAccounts
- User → belongsToMany Threads (via thread_assignments)
- Thread → belongsToMany Labels (via thread_labels)
- Thread → hasMany Emails
- Email → belongsTo Thread

---

## 📋 M1: IMAP Core - Was kommt als nächstes?

### Milestone Overview

**Ziel:** IMAP-Handling komplett standalone - Mails abholen, parsen, speichern

**4 Sprints (Geschätzt: 9 Tage):**

### ✅ Sprint 1.1: IMAP-Client-Modul (COMPLETED)
**Feature:** Primäre IMAP-Verbindung (inventar.md 2.1 - MUST)

**Status:** ✅ **COMPLETED** (17. November 2025, ~1 Stunde)

**Implementiert:**
- ✅ ImapClient mit 14 Operationen
- ✅ ImapMessage mit Lazy Loading
- ✅ Exception Handling (8 Typen)
- ✅ Mercury-kompatibel getestet
- ✅ Setup-Wizard mit Auto-Discovery
- ✅ Vollständige Dokumentation

**Deliverables:**
- ✅ `src/modules/imap/` - Main module (1,100+ lines)
- ✅ `src/modules/imap/tests/` - 3 test scripts
- ✅ `src/modules/imap/README.md` - Full API docs
- ✅ `src/modules/imap/QUICKSTART.md` - New developer guide
- ✅ `docs/dev/Setup-Autodiscover.md` - Setup wizard docs

**See:** `docs/dev/[COMPLETED] M1-Sprint-1.1-IMAP-Client-Modul.md`

---

### 🔴 Sprint 1.2: E-Mail-Parser (NEXT - 2 Tage)
**Feature:** E-Mail-Parsen (inventar.md 2.5 - MUST)

**Zu implementieren:**
```
src/modules/imap/
├── src/
│   ├── ImapClient.php           # Haupt-Client (php-imap wrapper)
│   ├── ImapConnection.php       # Connection Handler
│   ├── ImapMailbox.php          # Mailbox Operations
│   ├── ImapMessage.php          # Message Object
│   └── ImapException.php
├── config/imap.config.php
├── tests/manual-test.php
└── README.md
```

**Interface (Schnittstelle):**
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
```

**Standalone-Test:**
```bash
php src/modules/imap/tests/manual-test.php

# Input (interaktiv):
# Host: imap.example.com
# Port: 993
# Username: info@example.com
# Password: ******

# Erwartete Ausgabe:
# ✅ Connected to imap.example.com:993
# ✅ Folders: INBOX (15 messages), Sent (42), Trash (3)
# ✅ Selected INBOX
# ✅ Fetched 15 messages
```

**Dependencies:**
- Logger-Service (für Logging)
- Config-Service (für IMAP-Einstellungen)
- Encryption-Service (für Passwort-Entschlüsselung)

**Deliverables:**
- [ ] ImapClient funktioniert mit echtem IMAP-Server
- [ ] Alle IMAP-Operationen testbar (read, move, delete)
- [ ] Error-Handling für: Timeout, Auth-Fehler, SSL-Fehler
- [ ] README mit Usage-Beispielen
- [ ] Manual-Test erfolgreich (min. 10 Tests)

---

### Sprint 1.2: E-Mail-Parser (2 Tage)
**Feature:** E-Mail-Parsen (inventar.md 2.5 - MUST)

**Zu implementieren:**
```
src/modules/imap/src/Parser/
├── EmailParser.php              # Main Parser
├── HeaderParser.php             # Parse Headers
├── BodyParser.php               # Parse Text/HTML
├── AttachmentParser.php         # Extract Attachments
└── ThreadingParser.php          # Message-ID, In-Reply-To
```

**Siehe:** `roadmap.md` → M1 Sprint 1.2 für Details

---

### Sprint 1.3: Threading-Engine (2 Tage)
**Feature:** Thread-Gruppierung (inventar.md 1.1 - MUST)

**Zu implementieren:**
```
src/app/Services/
├── ThreadingService.php         # Haupt-Logik
└── ThreadMerger.php             # Merge-Strategie
```

**Siehe:** `roadmap.md` → M1 Sprint 1.3 für Details

---

### Sprint 1.4: Webcron-Service (2 Tage)
**Feature:** Webcron-Polling-Dienst (inventar.md 2.3 - MUST)

**Zu implementieren:**
```
scripts/
└── cron-poll-emails.php         # CLI-Script für Cron

src/app/Services/
└── WebcronService.php           # Orchestriert Fetch + Parse + Save
```

**Siehe:** `roadmap.md` → M1 Sprint 1.4 für Details

---

## 📚 Wichtige Dokumente

### Pflichtlektüre vor M1-Start:

1. **roadmap.md** → M1: IMAP Core Abschnitt
   - Alle 4 Sprints detailliert beschrieben
   - Interfaces vorab definiert
   - Success Criteria

2. **architecture.md** → Abschnitt 4 (Module-Schicht)
   - Wie Module strukturiert sind
   - Interface-First Approach

3. **basics.txt** → Kapitel 6 (Aufgabenkomplexität)
   - Wie große Aufgaben zerlegt werden
   - Optimal: 1-3 Dateien, 10-50 Zeilen Code

4. **inventar.md** → Kategorie 2 (IMAP & E-Mail-Verwaltung)
   - Alle IMAP-Features mit Prioritäten
   - Abhängigkeiten zwischen Features

### Nice-to-Have:

5. **[COMPLETED] M0-Sprint-0.X** Dokumente
   - Sehen, wie M0 Sprints strukturiert waren
   - Pattern für WIP-Dokumente

6. **workflow.md** → Abschnitt 4.X (Sprint-Workflow)
   - Wie Sprints ablaufen
   - Testing-Strategie

---

## ⚠️ Lessons Learned aus M0

### Was funktioniert hat:

1. **Compact WIP Format** - Reduzierte WIP-Docs (~50 Zeilen statt 300)
2. **Standalone Module Pattern** - Jedes Modul sofort testbar
3. **Manual Tests First** - Schnelles Feedback während Entwicklung
4. **Database-First Approach** - Realistische Tests mit echten Daten

### Häufige Probleme & Lösungen:

#### 1. Pivot-Tabellen: Timestamps Issue
```php
// ❌ Falsch:
return $this->belongsToMany(Thread::class, 'thread_assignments')
    ->withTimestamps()
    ->withPivot('assigned_at');

// ✅ Richtig:
return $this->belongsToMany(Thread::class, 'thread_assignments')
    ->withPivot('assigned_at');
```

#### 2. Container: Constructor Signature Mismatch
```php
// ❌ Falsch:
LoggerService::class => function($c) {
    return new LoggerService($c->get(ConfigService::class));
},

// ✅ Richtig:
LoggerService::class => function($c) {
    $config = $c->get(ConfigService::class);
    return new LoggerService(
        $config->getString('logger.log_path'),
        $config->getString('logger.log_level')
    );
},
```

#### 3. DateTime Helper Functions
```php
// ❌ Falsch (Laravel Helper nicht verfügbar):
$timestamp = now();

// ✅ Richtig:
$timestamp = new \DateTime();

// ✅ Besser: Carbon installieren
composer require nesbot/carbon
$timestamp = \Carbon\Carbon::now();
```

---

## 🎯 Nächster Schritt: M1 Sprint 1.1 starten

**Kommando für neue Session:**

> "Starte M1 Sprint 1.1: IMAP-Client-Modul. Siehe roadmap.md → M1 Sprint 1.1 für Details. Erstelle WIP-Dokument im Compact-Format (~50 Zeilen) und beginne mit der Implementierung."

**Oder alternativ:**

> "Lies roadmap.md M1 Sprint 1.1 und erstelle das WIP-Dokument. Dann starte die Implementierung."

---

**Viel Erfolg mit M1! 🚀**

*Stand: 17. November 2025 | M0 Foundation Complete*

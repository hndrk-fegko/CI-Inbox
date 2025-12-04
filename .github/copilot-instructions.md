# CI-Inbox: AI Coding Agent Instructions

## Project Overview
CI-Inbox is a lightweight collaborative IMAP inbox management system for small teams (3-7 people), built as a layer over existing IMAP mailboxes. The system focuses on thread-based email organization without replacing the underlying email infrastructure.

**Tech Stack:** PHP 8.1+, Slim 4 Framework, Eloquent ORM (standalone), MySQL 8.0+, Vanilla JS frontend  
**Status:** M2 Complete (Thread & Email API) - M3 MVP UI in progress  
**Architecture:** Modular plugin-based system with layer abstraction and dependency injection

## Architecture Principles

### Layer Abstraction (from `basics.txt`)
**Critical:** Business logic NEVER directly couples to implementation. Always use interfaces and dependency injection.

```php
// ✅ CORRECT: Service depends on interface
class ThreadService {
    public function __construct(
        private ThreadRepositoryInterface $threadRepo,  // Interface!
        private LoggerInterface $logger
    ) {}
}

// ❌ WRONG: Direct coupling to implementation
class ThreadService {
    public function assignThread($id, $userId) {
        $pdo->exec("UPDATE threads SET assigned_to = $userId WHERE id = $id");
    }
}
```

### Module System
- All core functionality lives in reusable modules under `src/modules/`
- Modules are standalone and can be tested independently
- Active modules: `logger`, `config`, `encryption`, `imap`, `label`, `smtp`, `webcron`, `auth`
- Each module has its own namespace: `CiInbox\Modules\{ModuleName}\`

### Hook/Plugin Architecture
- System uses `HookManager` for event-driven extensions
- Key hooks: `onAppInit`, `onConfigLoad`, `registerRoute`
- Modules can register at hook points to extend functionality
- See `src/core/HookManager.php` for available hooks

## Code Organization

### Directory Structure
```
src/
├── core/              # Application, Container, HookManager, ModuleLoader
├── modules/           # Standalone reusable modules (logger, imap, etc.)
├── app/
│   ├── Controllers/   # Slim route handlers
│   ├── Services/      # Business logic (uses interfaces)
│   ├── Repositories/  # Data access (implements interfaces)
│   └── Models/        # Eloquent models
├── routes/            # api.php (REST), web.php (views)
├── config/            # container.php (DI definitions)
└── public/            # index.php (entry point)
```

### Naming Patterns
- **Controllers:** `{Entity}Controller` (e.g., `ThreadController`)
- **Services:** `{Entity}Service` (e.g., `ThreadService`)
- **Repositories:** `{Entity}Repository` implements `{Entity}RepositoryInterface`
- **Module prefix:** Module-scoped variables include module name (e.g., `$loggerConfig`, `$imapClient`)

## Logging Requirements

**CRITICAL:** Every component must use the central `LoggerService` from `src/modules/logger/`

```php
// Always inject logger via DI
public function __construct(
    private LoggerInterface $logger
) {}

// Log with context
$this->logger->info('Processing email for threading', [
    'message_id' => $parsedEmail->messageId,
    'subject' => $parsedEmail->subject
]);

// Available levels (PSR-3 compliant): debug, info, warning, error
// For success messages, use info() with [SUCCESS] prefix:
$this->logger->info('[SUCCESS] Operation completed', ['id' => $id]);
```

**PSR-3 Compliance:** Do NOT use `->success()` method - it's not PSR-3 standard. Instead use `->info('[SUCCESS] ...')`.

**Log Format:** Structured JSON with timestamp, level, message, context, file, line  
**Location:** `logs/` directory (excluded from git)

## Cache-Busting Pattern

**All CSS/JS includes MUST use the centralized cache-busting mechanism:**

### Configuration File
Located at `src/config/version.php`:
```php
<?php
// Development: Use timestamp for cache-busting
const ASSET_VERSION = '1.0.0';  // Update for production releases

function asset_version(): string {
    if (getenv('APP_ENV') === 'production') {
        return '?v=' . ASSET_VERSION;
    }
    return '?v=' . time();  // Dynamic in development
}
```

### Usage in PHP Views
```php
<?php require_once __DIR__ . '/../config/version.php'; ?>

<!-- CSS includes -->
<link rel="stylesheet" href="/assets/css/app.css<?= asset_version() ?>">

<!-- JS includes -->
<script src="/assets/js/inbox.js<?= asset_version() ?>"></script>
```

### Standardized Syntax (Regex-Searchable)
All asset includes follow this exact pattern:
```
<?= asset_version() ?>
```

To update all cache versions in production, search-and-replace `ASSET_VERSION` in `version.php`.

### Version Update Command
```bash
# Find all cache-busted assets
grep -r "asset_version()" src/public/
```

## Database & Migrations

### Running Migrations
```bash
php database/migrate.php
```
- Migration files in `database/migrations/` numbered sequentially (001_, 002_, etc.)
- Uses Eloquent standalone (no Laravel framework)
- Database initialized via `src/bootstrap/database.php`

### Data Model
- **users**: User accounts with settings
- **imap_accounts**: Shared IMAP credentials (encrypted)
- **threads**: Email conversation threads
- **emails**: Individual messages
- **labels**: System + custom labels
- **thread_labels**: Many-to-many thread-label relations
- **internal_notes**: Team collaboration notes on threads
- **webhooks**: External system integrations

### Working with Models
```php
// Models use Eloquent Active Record
$thread = Thread::find($id);
$thread->status = 'closed';
$thread->save();

// Repositories abstract data access
$thread = $this->threadRepository->find($id);  // Preferred in Services
```

## Development Workflow

### Local Setup
- **PHP:** 8.1+ (XAMPP, php.ini extensions: pdo_mysql, imap, openssl, mbstring)
- **Database:** MySQL 8.0+ / MariaDB 10.6+
- **vHost:** Point to `src/public/` directory
- **Environment:** Copy `.env.example` to `.env`, configure DB and encryption key

### Running Tests
```bash
# Manual module tests (preferred for current phase)
php tests/manual/{test-name}.php

# Future PHPUnit tests (M5 planned)
./vendor/bin/phpunit tests/unit/
./vendor/bin/phpunit tests/integration/
```

### Starting Development Server
```bash
# PHP built-in server
php -S localhost:8080 -t src/public

# Or use XAMPP vHost: http://ci-inbox.local
```

## API Patterns

### Route Structure (Slim 4)
Routes defined in `src/routes/api.php` and `web.php`:
```php
$app->group('/api', function (RouteCollectorProxy $group) use ($container) {
    $group->group('/threads', function (RouteCollectorProxy $group) {
        $group->get('', function ($request, $response) { /* ... */ });
    });
});
```

### Response Format
```php
// Standard JSON response
$response->getBody()->write(json_encode([
    'success' => true,
    'data' => $data
]));
return $response->withHeader('Content-Type', 'application/json');
```

## Security Patterns

### Encryption
- Use `EncryptionService` from `src/modules/encryption/` for sensitive data
- IMAP passwords encrypted in database (AES-256-CBC)
- Encryption key in `.env` file (generate with: `php -r "echo bin2hex(random_bytes(32));"`)

### Authentication
- Session-based auth (M3+ feature)
- API auth via CRON_SECRET_TOKEN for webcron endpoints
- Webhook HMAC signatures for external integrations

## Key Services to Know

### ThreadService (`src/app/Services/ThreadService.php`)
- Processes emails and assigns to threads
- Uses ThreadManager (IMAP module) for threading logic
- Methods: `processEmail()`, `getThread()`, `assignThread()`

### ImapClient (`src/modules/imap/src/ImapClient.php`)
- 18 methods for IMAP operations
- Graceful degradation for servers without IMAP keyword support
- Methods: `connect()`, `fetchEmails()`, `searchByKeyword()`, `setFlag()`

### WebcronPollingService (`src/modules/webcron/`)
- Polls IMAP accounts on schedule (external cron trigger)
- Auth: API key + IP whitelist
- Endpoint: `POST /api/webcron/poll?token={CRON_SECRET_TOKEN}`

## Common Tasks

### Adding a New API Endpoint
1. Add route in `src/routes/api.php`
2. Create/extend Controller in `src/app/Controllers/`
3. Controller calls Service (business logic)
4. Service uses Repository (data access via interface)
5. Log all operations with context

### Creating a New Service
1. Define interface: `{Name}ServiceInterface` with method contracts
2. Implement service class with injected dependencies
3. Register in `src/config/container.php` DI definitions
4. Inject LoggerInterface for all operations

### Adding a Database Table
1. Create migration: `database/migrations/00X_create_{table}_table.php`
2. Define schema using Eloquent Schema Builder
3. Create Eloquent model: `src/app/Models/{Model}.php` extending `BaseModel`
4. Run: `php database/migrate.php`

## Testing Philosophy

- **M0-M2:** Standalone module tests (manual PHP scripts in `tests/manual/`)
- **M5:** Full PHPUnit suite planned
- Each module should be testable without full application bootstrap
- Use dependency injection to mock components in tests

## Important Files

- `basics.txt`: Core development principles (MUST READ for architecture decisions)
- `docs/dev/architecture.md`: Technical architecture deep dive (1600 lines)
- `docs/dev/codebase.md`: Development environment setup (1292 lines)
- `docs/dev/roadmap.md`: Feature timeline and milestones (1683 lines)
- `src/core/Application.php`: Main bootstrap logic (195 lines)
- `composer.json`: Dependencies and PSR-4 autoloading

## Conventions

- **Code Style:** PSR-12
- **Doc Blocks:** Required for all classes and public methods
- **Type Hints:** Strict types (`declare(strict_types=1)`) in all files
- **Property Promotion:** Use PHP 8.1 constructor property promotion
- **Dependency Injection:** Constructor injection preferred, container autowiring enabled

## What NOT to Do

- ❌ Don't bypass Repository interfaces in Services
- ❌ Don't create database queries directly in Controllers
- ❌ Don't skip logging for operations that modify data
- ❌ Don't add features without checking `docs/dev/inventar.md` priorities (MUST/SHOULD/COULD)
- ❌ Don't commit `.env`, `logs/`, or `data/` directories

## Module Registration & Hooks

### How Modules Register
Modules use `module.json` manifest files for auto-discovery:
```json
{
  "name": "webcron",
  "hooks": {
    "onAppInit": { "priority": 10 }
  }
}
```

### Available Hooks
- `onAppInit`: Application initialized (register routes, services)
- `onConfigLoad`: Configuration loaded
- `onBeforeShutdown`: Before application terminates

### Hook Registration Pattern
```php
// In module code
$hookManager->register('onAppInit', function() use ($container) {
    // Register routes, initialize services
}, $priority = 10);  // Lower priority = earlier execution
```

## Frontend Integration (M3+)

### JavaScript Patterns
- **No framework**: Vanilla ES6+ JavaScript only
- **Fetch API**: Standard for all API calls
- **Async/Await**: Preferred over promises
- **Console logging**: Use `console.log('[Module] Action:', data)` format

### API Call Pattern
```javascript
// Standard API call structure
const API_BASE = '/api/user';  // or '/api/threads', etc.

async function fetchData() {
    try {
        const response = await fetch(`${API_BASE}/endpoint`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('[Feature] Success:', result.data);
            // Handle success
        } else {
            console.error('[Feature] Error:', result.error);
            // Show error to user
        }
    } catch (error) {
        console.error('[Feature] Failed:', error);
        // Handle network error
    }
}
```

### Frontend File Organization
- `src/public/assets/js/{feature}.js` - Feature-specific JavaScript
- `src/public/assets/css/{feature}.css` - Feature-specific styles
- `src/views/{feature}.php` - Server-rendered views (PHP templates)

## Testing Patterns

### Manual Test Structure
All manual tests in `tests/manual/` follow this pattern:

```php
<?php
/**
 * {Feature} Manual Test
 * 
 * Tests {specific functionality}
 * Usage: php tests/manual/{test-name}.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use CiInbox\Modules\Config\ConfigService;
use CiInbox\Modules\Logger\LoggerService;

// Initialize system
$config = new ConfigService(__DIR__ . '/../../');
require_once __DIR__ . '/../../src/bootstrap/database.php';
initDatabase($config);

$logger = new LoggerService(__DIR__ . '/../../logs/');

echo "=== {Test Name} ===" . PHP_EOL . PHP_EOL;

// TEST 1: {Description}
echo "TEST 1: {Description}" . PHP_EOL;
try {
    // Test implementation
    echo "✅ Success" . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Failed: {$e->getMessage()}" . PHP_EOL;
    exit(1);
}
```

### Test Organization
- Each test is standalone and can run independently
- Tests use real database (ensure dev environment)
- Output uses ✅/❌ emojis for clarity
- Exit code 1 on failure for CI integration readiness

## Authentication & Security (M3+)

### Current Auth Status
- **M2 (Current)**: No authentication - API endpoints are open
- **M3 (In Progress)**: Session-based authentication being implemented
- **Production**: Token-based auth for webcron endpoints via `CRON_SECRET_TOKEN`

### Auth Pattern (When Implementing)
```php
// Middleware approach (to be used in M3+)
$app->add(function ($request, $handler) use ($container) {
    // Check session/auth
    if (!isAuthenticated($request)) {
        return jsonResponse(['error' => 'Unauthorized'], 401);
    }
    return $handler->handle($request);
});
```

### Webhook Security
- HMAC-SHA256 signatures for webhook payloads
- Signature verification: `hash_hmac('sha256', $payload, $secret)`
- Secret stored encrypted in database

## Project Vision & Goals

### Core Principle (from `vision.md`)
**Transform shared IMAP mailboxes into collaborative task queues** for small autonomous teams (3-7 people) without ticketing system complexity.

### Key Differentiators
- **IMAP-Independent**: Users can take personal ownership of sensitive threads
- **Thread-Based**: Conversations grouped by Message-ID/References/Subject
- **Transparency First**: 100% audit trail of who did what
- **Keep It Simple**: Only features small teams actually need

### Three Workflows (A/B/C)
- **Workflow A**: Shared inbox → Assign → Handle in CI-Inbox → Mark done
- **Workflow B**: Shared inbox → Internal notes → Handoff context
- **Workflow C**: Personal IMAP takeover for sensitive topics (M4 feature)

## Common Patterns from Existing Code

### Service Layer Pattern
Services orchestrate business logic and coordinate between repositories:
```php
class ThreadService {
    public function processEmail(ParsedEmail $email): Thread {
        // 1. Check if exists
        if ($this->emailRepository->existsByMessageId($email->messageId)) {
            return $this->getExistingThread($email);
        }
        
        // 2. Find or create thread
        $threadId = $this->findExistingThread($email);
        $thread = $threadId ? $this->find($threadId) : $this->create($email);
        
        // 3. Save and update
        $this->saveEmail($email, $thread->id);
        $this->updateMetadata($thread->id);
        
        // 4. Log everything
        $this->logger->info('Email processed', [
            'thread_id' => $thread->id,
            'message_id' => $email->messageId
        ]);
        
        return $thread;
    }
}
```

### Repository Pattern
Repositories handle data persistence only:
```php
class ThreadRepository {
    public function find(int $id): ?Thread {
        return Thread::with(['emails', 'labels'])->find($id);
    }
    
    public function save(Thread $thread): void {
        $thread->save();
        $this->logger->debug('Thread saved', ['id' => $thread->id]);
    }
}
```

### Controller Pattern (Slim 4)
Controllers are thin - delegate to services immediately:
```php
$app->get('/api/threads/{id}', function ($request, $response, $args) use ($container) {
    $service = $container->get(ThreadService::class);
    
    try {
        $thread = $service->getThread((int)$args['id']);
        return jsonResponse($response, ['success' => true, 'data' => $thread]);
    } catch (Exception $e) {
        return jsonResponse($response, ['error' => $e->getMessage()], 500);
    }
});
```

## Windows Development Commands

Project runs on Windows with XAMPP:

```powershell
# Run migrations
C:\xampp\php\php.exe database\migrate.php

# Run manual tests
C:\xampp\php\php.exe tests\manual\webhook-test.php

# Database operations
C:\xampp\mysql\bin\mysql.exe -u root -e "USE ci_inbox; SELECT * FROM threads LIMIT 5;"

# Start dev server (if not using XAMPP vHost)
C:\xampp\php\php.exe -S localhost:8080 -t src\public
```

## Performance & Optimization

### IMAP Keyword Strategy
- **Preferred**: Use IMAP keywords for threading metadata (`$Thread123`, `$CiInboxProcessed`)
- **Fallback**: Graceful degradation to database-only if server lacks keyword support
- **Detection**: `ImapClient` auto-detects capability in setup

### Database Indexes
Key indexes for performance:
- `threads.status` - Filter by status (open/in_progress/closed/archived)
- `emails.message_id` - Duplicate detection
- `emails.thread_id` - Thread email lookups
- `thread_labels.thread_id, thread_labels.label_id` - Label filtering

## Current Development Focus (M3)

Building MVP UI with:
- User authentication and session management
- Thread list view with filtering/sorting
- Thread detail view with email history
- Basic actions: assign, label, archive, reply
- See `docs/dev/[WIP] M2-Sprint-2.2-Email-Send-API.md` for current sprint details

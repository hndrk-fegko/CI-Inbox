# [TASK] Admin Settings: Migration zum modularen System

**Erstellt:** 5. Dezember 2025  
**Assignee:** Cloud Agent  
**Priorität:** High  
**Geschätzter Aufwand:** 6-8 Stunden  
**Abhängigkeiten:** Keine (standalone task)

---

## 🎯 Ziel

Migriere das bestehende Admin-Interface (`admin-settings.php`) zum neuen **modularen Plugin-System** (`admin-settings-new.php`) und mache es **production-ready**.

### Erfolgs-Kriterien

1. ✅ **Feature-Parity:** Alle Funktionen aus `-old` sind in `-new` verfügbar
2. ✅ **Module Complete:** Alle 8 Module vollständig implementiert (kein "Coming Soon")
3. ✅ **Backend-APIs:** Alle API-Endpoints funktionieren
4. ✅ **UI/UX Consistency:** Einheitliches Design über alle Module
5. ✅ **Testing:** Alle Features manuell getestet und dokumentiert
6. ✅ **Documentation:** Jedes Modul dokumentiert (Vision + Implementation)
7. ✅ **Production-Ready:** Deployment-fähig, keine TODOs

---

## 📋 Phase 1: Analyse & Vergleich (90-120 min)

### 1.1 Admin-Settings Vergleich

**Aufgabe:** Erstelle eine detaillierte Feature-Matrix zwischen beiden Versionen.

#### Schritt 1: Analysiere `admin-settings.php` (alte Version)

**Zu dokumentieren:**
```markdown
# Feature Matrix: admin-settings.php (OLD)

## Tab-Struktur
1. Dashboard
   - Welche Karten?
   - Welche Metriken?
   - Welche Actions?

2. System Health
   - Module Health Checks
   - Database Status
   - IMAP Connection Status
   - Log File Status

3. User Management
   - User List
   - Create/Edit/Delete
   - Role Management
   - Avatar Management

4. Email Signatures
   - Global Signatures
   - Personal Signatures
   - CRUD Operations

5. IMAP Accounts
   - Account List
   - Configuration
   - Connection Testing

6. Backup Management
   - Create Backup
   - Restore Backup
   - Backup History

7. System Settings
   - General Settings
   - Email Settings
   - Security Settings

## JavaScript Funktionalität
- Event Listeners
- API Calls
- Form Validation
- Dynamic UI Updates

## CSS Styling
- Custom Styles
- Component Classes
- Responsive Breakpoints
```

#### Schritt 2: Analysiere `admin-settings-new.php` (modulares System)

**Zu dokumentieren:**
```markdown
# Architecture Analysis: admin-settings-new.php

## Auto-Discovery Mechanism
- Glob Pattern: `src/views/admin/modules/*.php`
- Module Loading Logic
- Priority Sorting
- Error Handling

## Module Contract
- Required Keys: id, title, priority
- Optional Keys: icon, card, content, script
- Callable Functions vs. Static Content

## Current Module Status

### 010-imap.php
- ✅ Card: Implementiert
- ⚠️ Content: "Coming Soon" Placeholder
- ❌ Script: Nur Skeleton
- 🔗 APIs: Fehlen

### 020-smtp.php
- Status...

### 030-cron.php
- Status...

(etc. für alle Module)
```

#### Schritt 3: Feature-Gap-Matrix erstellen

**Deliverable:** `docs/dev/migration-reports/feature-gap-matrix.md`

```markdown
# Feature Gap Matrix

| Feature | Old (admin-settings.php) | New (admin-settings-new.php) | Status | Migration Effort |
|---------|-------------------------|------------------------------|--------|------------------|
| Dashboard Overview | ✅ Full | ⚠️ Partial | GAP | 2h |
| System Health Checks | ✅ Full | ❌ Missing | GAP | 3h |
| User Management UI | ✅ Full | ✅ Full | OK | - |
| User Avatar Upload | ✅ Full | ⚠️ Broken? | CHECK | 1h |
| Global IMAP Config | ✅ Full | ❌ Placeholder | GAP | 4h |
| SMTP Configuration | ✅ Full | ❌ Placeholder | GAP | 3h |
| Webcron Status | ✅ Full | ✅ Full | OK | - |
| Backup Management | ✅ Full | ✅ Full | OK | - |
| Database Tools | ⚠️ Partial | ❌ Placeholder | GAP | 2h |
| Email Signatures | ✅ Full | ✅ Full | OK | - |
| Theme Switcher | ✅ Full | ✅ Full | OK | - |
| **Logger Viewer** | ❌ Missing | 🆕 **Template** | NEW | 4h |

## Summary
- ✅ Feature Parity: 4/11 (36%)
- ⚠️ Partial Implementation: 2/11 (18%)
- ❌ Missing/Placeholder: 5/11 (45%)
- 🆕 New Features: 1 (Logger)

**Total Migration Effort:** ~19 hours
**Critical Path:** IMAP Config (4h) → SMTP Config (3h) → System Health (3h)
```

---

## 📋 Phase 2: Module-Vision & Roadmap (60-90 min)

### 2.1 Für jedes Modul: Vision definieren

**Aufgabe:** Erstelle für JEDES der 8 Module eine klare Vision.

**Template:** `docs/dev/module-visions/[MODULE-NAME]-vision.md`

#### Beispiel: IMAP Module Vision

```markdown
# IMAP Module Vision

## Zweck
Zentrale Verwaltung aller IMAP-Account-Konfigurationen für das System.

## Zielgruppe
- **Admins:** Konfigurieren globale IMAP-Defaults
- **Users:** Sehen ihre persönlichen IMAP-Accounts (read-only in Admin-View)

## Dashboard Card (Overview)
- **Status Badge:** 
  - 🟢 "Configured" (wenn mind. 1 Account aktiv)
  - 🔴 "Not Configured" (wenn keine Accounts)
- **Metrics:**
  - Anzahl aktiver Accounts
  - Letzte erfolgreiche Verbindung (Timestamp)
  - Fehlerhafte Accounts (Count)
- **Quick Actions:**
  - "Test Connection" Button
  - "View Logs" Link

## Full Tab (Detailed Config)

### Section 1: Global IMAP Defaults
- Host (Input)
- Port (Input, default: 993)
- Encryption (Dropdown: SSL, TLS, None)
- Username Pattern (Input, z.B. "{email}")
- Connection Timeout (Input, default: 30s)
- **Action:** Save Defaults

### Section 2: Account List
- **Table Columns:**
  - ID
  - Email
  - Host:Port
  - Status (Badge: Connected/Error/Disabled)
  - Last Sync (Timestamp)
  - Actions (Test, Edit, Delete)
- **Pagination:** 10 per page
- **Search:** By email/host
- **Filter:** By status

### Section 3: Add New Account
- Form (collapsed by default)
- Fields: Email, Host, Port, Encryption, Username, Password
- **Validation:**
  - Email format
  - Host reachable
  - Port valid (1-65535)
  - Test connection before save
- **Action:** Add Account

### Section 4: Autodiscover Service
- **Info Box:** Explain autodiscover feature
- **Configuration:**
  - Enable/Disable Toggle
  - Autodiscover URL (Input)
  - Fallback Hosts (List)
- **Action:** Save Autodiscover Config

## API Endpoints (Required)

### GET /api/admin/imap/accounts
Response:
```json
{
  "success": true,
  "data": {
    "accounts": [
      {
        "id": 1,
        "email": "info@example.com",
        "host": "imap.example.com",
        "port": 993,
        "encryption": "ssl",
        "status": "connected",
        "last_sync": "2025-12-05T16:30:00Z",
        "error": null
      }
    ],
    "total": 1,
    "configured": true
  }
}
```

### POST /api/admin/imap/test
Request:
```json
{
  "host": "imap.example.com",
  "port": 993,
  "encryption": "ssl",
  "username": "user@example.com",
  "password": "secret"
}
```

Response:
```json
{
  "success": true,
  "data": {
    "connected": true,
    "latency_ms": 234,
    "server_info": "Microsoft Exchange Server",
    "capabilities": ["IMAP4rev1", "IDLE", "NAMESPACE"]
  }
}
```

### POST /api/admin/imap/accounts
(Create new account)

### PUT /api/admin/imap/accounts/{id}
(Update account)

### DELETE /api/admin/imap/accounts/{id}
(Delete account)

### GET /api/admin/imap/defaults
(Get global defaults)

### PUT /api/admin/imap/defaults
(Update global defaults)

## JavaScript Behavior

### Auto-Refresh Status
- Poll `/api/admin/imap/accounts` every 30s
- Update status badges dynamically
- Show toast notification on status change

### Connection Test
- Click "Test Connection" → Show loading spinner
- API call → Display result (success/error)
- Show detailed error message if failed

### Form Validation
- Email: RFC 5322 validation
- Host: DNS lookup (client-side check if possible)
- Port: 1-65535 range
- Password: Min 8 chars (warning, not enforced)

## Error Handling

### Connection Errors
- **Timeout:** "Connection timed out. Check host and firewall."
- **Auth Failed:** "Authentication failed. Check username/password."
- **SSL Error:** "SSL certificate validation failed. Use TLS or disable SSL."
- **Host Not Found:** "Host not reachable. Check DNS and network."

### User Feedback
- ✅ Success: Green toast, 3s auto-dismiss
- ⚠️ Warning: Yellow toast, 5s auto-dismiss
- ❌ Error: Red toast, stays until dismissed

## Migration from Old System

### Data to Migrate
1. **IMAP Account Records:**
   - Query: `SELECT * FROM imap_accounts`
   - No schema changes needed
   - Preserve encryption

2. **UI Components:**
   - Old: Tabs-based layout
   - New: Card + Sidebar
   - **Action:** Refactor HTML structure

3. **JavaScript:**
   - Old: Inline scripts in PHP
   - New: Module-scoped functions
   - **Action:** Extract and modularize

### Testing Checklist
- [ ] Load existing accounts from DB
- [ ] Create new account (success)
- [ ] Create new account (validation errors)
- [ ] Test connection (success)
- [ ] Test connection (all error types)
- [ ] Edit existing account
- [ ] Delete account (with confirmation)
- [ ] Auto-refresh status updates
- [ ] Pagination works
- [ ] Search filters correctly
- [ ] Mobile responsive

## Dependencies
- **Backend:** ImapClient (existing)
- **Frontend:** None (vanilla JS)
- **Database:** imap_accounts table (existing)
- **APIs:** 7 endpoints (to be created)

## Estimated Effort
- Vision & Planning: 0.5h (this document)
- Backend APIs: 2h
- Frontend Implementation: 1.5h
- Testing & Debugging: 0.5h
- Documentation: 0.5h
**Total:** ~5 hours

## Success Metrics
- ✅ All API endpoints return correct data
- ✅ UI matches Figma mockups (if available)
- ✅ No console errors
- ✅ All tests pass
- ✅ Mobile responsive (tested on 3 devices)
- ✅ Admin can manage accounts without docs
```

**Aufgabe:** Erstelle solche Visions für ALLE Module:
1. ✅ 010-imap.php (Beispiel oben)
2. 020-smtp.php
3. 030-cron.php (bereits implementiert, Vision = Status Quo dokumentieren)
4. 040-backup.php (bereits implementiert)
5. 050-database.php
6. 060-users.php (bereits implementiert)
7. 070-signatures.php (bereits implementiert)
8. 080-logger.php (Template vorhanden, Vision erweitern)

**Deliverable:** 8 Vision-Dokumente in `docs/dev/module-visions/`

---

## 📋 Phase 3: Backend-API-Implementierung (3-4 Stunden)

### 3.1 API-Endpoint-Übersicht erstellen

**Aufgabe:** Liste ALLE benötigten API-Endpoints auf.

**Deliverable:** `docs/dev/migration-reports/api-endpoints-required.md`

```markdown
# Required API Endpoints for Modular Admin Settings

## IMAP Module (010)
- [ ] GET    /api/admin/imap/accounts
- [ ] POST   /api/admin/imap/accounts
- [ ] PUT    /api/admin/imap/accounts/{id}
- [ ] DELETE /api/admin/imap/accounts/{id}
- [ ] POST   /api/admin/imap/test
- [ ] GET    /api/admin/imap/defaults
- [ ] PUT    /api/admin/imap/defaults

## SMTP Module (020)
- [ ] GET    /api/admin/smtp/config
- [ ] PUT    /api/admin/smtp/config
- [ ] POST   /api/admin/smtp/test

## Cron Module (030)
- [x] GET    /api/admin/cron/status (exists)
- [x] POST   /api/admin/cron/trigger (exists)
- [ ] GET    /api/admin/cron/history
- [ ] PUT    /api/admin/cron/schedule

## Backup Module (040)
- [x] GET    /api/admin/backup/list (exists)
- [x] POST   /api/admin/backup/create (exists)
- [x] POST   /api/admin/backup/restore (exists)
- [ ] DELETE /api/admin/backup/{id}

## Database Module (050)
- [ ] GET    /api/admin/database/status
- [ ] POST   /api/admin/database/optimize
- [ ] POST   /api/admin/database/vacuum
- [ ] GET    /api/admin/database/tables

## Users Module (060)
- [x] GET    /api/users (exists)
- [x] POST   /api/users (exists)
- [x] PUT    /api/users/{id} (exists)
- [x] DELETE /api/users/{id} (exists)
- [ ] GET    /api/admin/users/stats

## Signatures Module (070)
- [x] GET    /api/admin/signatures (exists)
- [x] POST   /api/admin/signatures (exists)
- [x] PUT    /api/admin/signatures/{id} (exists)
- [x] DELETE /api/admin/signatures/{id} (exists)

## Logger Module (080)
- [ ] GET    /api/admin/logger/level
- [ ] PUT    /api/admin/logger/level
- [ ] GET    /api/admin/logger/stream
- [ ] GET    /api/admin/logger/stats
- [ ] POST   /api/admin/logger/download
- [ ] POST   /api/admin/logger/clear

## Summary
- **Total Endpoints:** 35
- **Existing:** 11 (31%)
- **To Create:** 24 (69%)
```

### 3.2 API-Implementierung priorisieren

**Aufgabe:** Sortiere nach Priorität (Critical Path zuerst).

**Priorität 1 (Blocker):**
1. IMAP APIs (7 endpoints) - Ohne diese kein IMAP-Modul
2. Logger APIs (6 endpoints) - Neues Feature, vollständig benötigt
3. Database APIs (4 endpoints) - Placeholder-Modul finalisieren

**Priorität 2 (Nice-to-Have):**
4. SMTP APIs (3 endpoints)
5. Cron History API (2 endpoints)
6. User Stats API (1 endpoint)

### 3.3 Controller & Service erstellen

**Für jedes fehlende API-Endpoint:**

**Schritt 1: Controller erstellen**
```php
// src/app/Controllers/Admin/ImapAdminController.php
<?php
declare(strict_types=1);

namespace CiInbox\App\Controllers\Admin;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use CiInbox\App\Services\ImapAdminService;
use CiInbox\Modules\Logger\LoggerInterface;

class ImapAdminController
{
    public function __construct(
        private ImapAdminService $imapService,
        private LoggerInterface $logger
    ) {}
    
    /**
     * GET /api/admin/imap/accounts
     */
    public function getAccounts(Request $request, Response $response): Response
    {
        try {
            $accounts = $this->imapService->getAllAccounts();
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => [
                    'accounts' => $accounts,
                    'total' => count($accounts),
                    'configured' => count($accounts) > 0
                ]
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
                
        } catch (\Exception $e) {
            $this->logger->error('Failed to get IMAP accounts', [
                'error' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
    
    // ... weitere Methoden
}
```

**Schritt 2: Service erstellen**
```php
// src/app/Services/ImapAdminService.php
<?php
declare(strict_types=1);

namespace CiInbox\App\Services;

use CiInbox\App\Repositories\ImapAccountRepositoryInterface;
use CiInbox\Modules\Imap\ImapClient;
use CiInbox\Modules\Logger\LoggerInterface;

class ImapAdminService
{
    public function __construct(
        private ImapAccountRepositoryInterface $imapRepo,
        private ImapClient $imapClient,
        private LoggerInterface $logger
    ) {}
    
    public function getAllAccounts(): array
    {
        $accounts = $this->imapRepo->findAll();
        
        return array_map(function($account) {
            return [
                'id' => $account->id,
                'email' => $account->email,
                'host' => $account->imap_host,
                'port' => $account->imap_port,
                'encryption' => $account->imap_encryption,
                'status' => $account->is_active ? 'active' : 'inactive',
                'last_sync' => $account->last_sync_at?->toIso8601String(),
                'error' => $account->last_error
            ];
        }, $accounts);
    }
    
    public function testConnection(array $config): array
    {
        try {
            $startTime = microtime(true);
            
            $this->imapClient->connect(
                $config['host'],
                $config['port'],
                $config['username'],
                $config['password'],
                $config['encryption']
            );
            
            $latency = round((microtime(true) - $startTime) * 1000);
            $serverInfo = $this->imapClient->getServerInfo();
            
            $this->imapClient->disconnect();
            
            return [
                'connected' => true,
                'latency_ms' => $latency,
                'server_info' => $serverInfo,
                'capabilities' => $this->imapClient->getCapabilities()
            ];
            
        } catch (\Exception $e) {
            $this->logger->warning('IMAP connection test failed', [
                'host' => $config['host'],
                'error' => $e->getMessage()
            ]);
            
            return [
                'connected' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // ... weitere Methoden
}
```

**Schritt 3: Route registrieren**
```php
// src/routes/api.php
$app->group('/api/admin/imap', function (RouteCollectorProxy $group) use ($container) {
    $controller = $container->get(\CiInbox\App\Controllers\Admin\ImapAdminController::class);
    
    $group->get('/accounts', [$controller, 'getAccounts']);
    $group->post('/accounts', [$controller, 'createAccount']);
    $group->put('/accounts/{id}', [$controller, 'updateAccount']);
    $group->delete('/accounts/{id}', [$controller, 'deleteAccount']);
    $group->post('/test', [$controller, 'testConnection']);
    $group->get('/defaults', [$controller, 'getDefaults']);
    $group->put('/defaults', [$controller, 'updateDefaults']);
});
```

**Schritt 4: DI Container konfigurieren**
```php
// src/config/container.php
use CiInbox\App\Controllers\Admin\ImapAdminController;
use CiInbox\App\Services\ImapAdminService;

// ImapAdminService
ImapAdminService::class => function (ContainerInterface $c) {
    return new ImapAdminService(
        $c->get(\CiInbox\App\Repositories\ImapAccountRepositoryInterface::class),
        $c->get(\CiInbox\Modules\Imap\ImapClient::class),
        $c->get(\CiInbox\Modules\Logger\LoggerInterface::class)
    );
},

// ImapAdminController
ImapAdminController::class => function (ContainerInterface $c) {
    return new ImapAdminController(
        $c->get(ImapAdminService::class),
        $c->get(\CiInbox\Modules\Logger\LoggerInterface::class)
    );
},
```

**Deliverable:** Alle 24 fehlenden API-Endpoints implementiert

---

## 📋 Phase 4: Frontend-Migration (2-3 Stunden)

### 4.1 Module Content vervollständigen

**Für jedes Modul mit "Coming Soon" Placeholder:**

**Schritt 1: Alte Implementierung extrahieren**
```php
// Finde in admin-settings.php:
// <div id="imap-tab" class="tab-content">...</div>

// Extrahiere HTML-Struktur
// Extrahiere JavaScript
// Extrahiere CSS (wenn inline)
```

**Schritt 2: In modulares Format konvertieren**
```php
// src/views/admin/modules/010-imap.php

'content' => function() {
    ?>
    <div class="c-tabs__content" id="imap-tab">
        <!-- Migrierter Content von admin-settings.php -->
        <!-- Angepasst an neue Design-Klassen (.c-*) -->
    </div>
    <?php
},

'script' => function() {
    ?>
    // Migriertes JavaScript
    // Angepasst an neue API-Endpoints
    // Modularisiert (keine globalen Variablen)
    <?php
}
```

**Schritt 3: Design harmonisieren**
- Verwende `.c-*` Klassen (konsistent über alle Module)
- Entferne alte CSS-Klassen
- Responsive Breakpoints testen

### 4.2 JavaScript modernisieren

**Refactoring-Patterns:**

**ALT (admin-settings.php):**
```javascript
// Inline script, globale Variablen
var imapAccounts = [];

function loadImapAccounts() {
    $.ajax({ // jQuery dependency
        url: '/api/imap/accounts',
        success: function(data) {
            imapAccounts = data;
            renderTable();
        }
    });
}
```

**NEU (modulares System):**
```javascript
// Module-scoped, moderne async/await
async function loadImapAccounts() {
    try {
        const response = await fetch('/api/admin/imap/accounts');
        const result = await response.json();
        
        if (result.success) {
            renderAccountsTable(result.data.accounts);
            updateStatusBadge(result.data.configured);
        } else {
            showError(result.error);
        }
    } catch (error) {
        console.error('[IMAP] Failed to load accounts:', error);
        showError('Failed to load IMAP accounts');
    }
}

// Auto-init when module tab is active
if (document.getElementById('imap-tab')) {
    loadImapAccounts();
}
```

**Checkliste:**
- [ ] Ersetze jQuery durch Vanilla JS
- [ ] Verwende `async/await` statt Callbacks
- [ ] Module-scoped Funktionen (keine Globals)
- [ ] Konsistentes Error Handling
- [ ] Console-Logging mit Prefix `[MODULE]`

### 4.3 UI-Komponenten vervollständigen

**Für jedes Modul:**

**Dashboard Card:**
```php
'card' => function() {
    ?>
    <div class="c-admin-card" data-module="imap" onclick="window.switchToTab('imap')">
        <div class="c-admin-card__header">
            <div class="c-admin-card__icon">
                <!-- SVG Icon -->
            </div>
            <h3 class="c-admin-card__title">Module Title</h3>
        </div>
        <p class="c-admin-card__description">Short description</p>
        <div class="c-admin-card__content">
            <!-- Dynamic metrics (loaded via JS) -->
            <div class="c-info-row">
                <span class="c-info-row__label">Status</span>
                <span id="imap-status-badge" class="c-status-badge">
                    <span class="status-dot"></span>
                    Loading...
                </span>
            </div>
        </div>
    </div>
    <?php
}
```

**Tab Content:**
```php
'content' => function() {
    ?>
    <div class="c-tabs__content" id="module-tab">
        <!-- Header -->
        <div style="margin-bottom: 2rem;">
            <h3 style="margin: 0 0 0.5rem 0; font-size: 1.25rem; font-weight: 600;">
                Module Title
            </h3>
            <p style="margin: 0; color: #666; font-size: 0.875rem;">
                Description
            </p>
        </div>
        
        <!-- Content Sections -->
        <div class="c-card">
            <!-- Section 1 -->
        </div>
        
        <div class="c-card">
            <!-- Section 2 -->
        </div>
    </div>
    <?php
}
```

---

## 📋 Phase 5: Testing & Validierung (2-3 Stunden)

### 5.1 Funktionale Tests durchführen

**Für JEDES Modul:**

**Test-Checkliste:**
```markdown
## Module: IMAP Configuration

### Dashboard Card Tests
- [ ] Card wird geladen (keine JS-Fehler)
- [ ] Status-Badge zeigt korrekten Status
- [ ] Metriken werden korrekt angezeigt
- [ ] Click auf Card wechselt zu Tab

### Tab Content Tests
- [ ] Tab-Content wird geladen
- [ ] Alle Sections sind sichtbar
- [ ] Forms sind funktional
- [ ] Validation funktioniert

### API Tests
- [ ] GET /api/admin/imap/accounts → 200
- [ ] POST /api/admin/imap/accounts → 201 (valid data)
- [ ] POST /api/admin/imap/accounts → 400 (invalid data)
- [ ] POST /api/admin/imap/test → 200 (success)
- [ ] POST /api/admin/imap/test → 500 (connection failed)
- [ ] PUT /api/admin/imap/accounts/{id} → 200
- [ ] DELETE /api/admin/imap/accounts/{id} → 200

### JavaScript Tests
- [ ] Auto-refresh funktioniert (30s interval)
- [ ] Event Listeners registriert
- [ ] Error Handling funktioniert
- [ ] Toast Notifications erscheinen
- [ ] Keine console.error (nur .log/.warn)

### UI/UX Tests
- [ ] Mobile Responsive (320px width)
- [ ] Tablet Responsive (768px width)
- [ ] Desktop (1920px width)
- [ ] Dark Mode funktioniert
- [ ] Hover-States sichtbar
- [ ] Focus-States sichtbar (Accessibility)

### Cross-Browser Tests
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Edge (latest)
- [ ] Safari (latest) - wenn verfügbar

### Performance Tests
- [ ] Page Load < 500ms
- [ ] API Calls < 200ms
- [ ] No memory leaks (Tab-Wechsel 10x)
- [ ] Smooth animations (60fps)
```

**Deliverable:** Test-Report für jedes Modul

### 5.2 Integrationstests

**Scenario-based Testing:**

**Scenario 1: Admin konfiguriert neuen IMAP-Account**
1. Login als Admin
2. Navigiere zu Admin Settings
3. Click auf IMAP Card
4. Fill out "Add New Account" Form
5. Click "Test Connection"
   - **Expected:** Success message, green toast
6. Click "Save"
   - **Expected:** Account erscheint in Liste
7. Verify in Dashboard Card
   - **Expected:** Status Badge = "Configured"

**Scenario 2: Admin ändert Log Level**
1. Navigiere zu Logger Tab
2. Ändere Log Level von INFO zu DEBUG
3. Click "Save Configuration"
   - **Expected:** Success toast
4. Reload Page
   - **Expected:** Log Level = DEBUG (persistiert)
5. View Live Logs
   - **Expected:** DEBUG-Messages erscheinen

**Scenario 3: Admin erstellt Backup**
1. Navigiere zu Backup Tab
2. Click "Create Backup"
   - **Expected:** Progress indicator
3. Wait for completion
   - **Expected:** Success toast, Backup in Liste
4. Click "Download"
   - **Expected:** ZIP-File download startet

**Deliverable:** `docs/dev/migration-reports/integration-test-results.md`

### 5.3 Accessibility Check

**WCAG 2.1 Level AA:**
- [ ] Alle Formular-Felder haben `<label>`
- [ ] Buttons haben aussagekräftigen Text (kein "Click here")
- [ ] Farbkontrast ≥ 4.5:1 (Text zu Background)
- [ ] Keyboard-Navigation funktioniert (Tab-Order)
- [ ] Focus-Indicator sichtbar
- [ ] Screen-Reader-freundlich (aria-labels wo nötig)
- [ ] Fehler-Messages sind klar und beschreibend

**Tool:** Lighthouse Audit (Chrome DevTools)
- **Target:** Score ≥ 90

---

## 📋 Phase 6: Dokumentation & Finalisierung (1-2 Stunden)

### 6.1 Code-Dokumentation vervollständigen

**Für jedes neue File:**

**PHP-Dateien:**
```php
<?php
declare(strict_types=1);

namespace CiInbox\App\Controllers\Admin;

/**
 * IMAP Admin Controller
 * 
 * Handles IMAP account configuration for admin interface.
 * Provides CRUD operations and connection testing.
 * 
 * @package CiInbox\App\Controllers\Admin
 * @author GitHub Copilot Cloud Agent
 * @since M4
 */
class ImapAdminController
{
    /**
     * Get all IMAP accounts
     * 
     * Returns list of all configured IMAP accounts with status.
     * 
     * @param Request $request HTTP request
     * @param Response $response HTTP response
     * @return Response JSON response with accounts array
     * 
     * @api GET /api/admin/imap/accounts
     * @authenticated Admin only
     */
    public function getAccounts(Request $request, Response $response): Response
    {
        // ...
    }
}
```

**JavaScript:**
```javascript
/**
 * IMAP Module - Admin Settings
 * 
 * Manages IMAP account configuration in admin interface.
 * Features: Auto-refresh status, connection testing, CRUD operations.
 * 
 * @module AdminSettings/IMAP
 * @author GitHub Copilot Cloud Agent
 * @since M4
 */

/**
 * Load all IMAP accounts from API
 * 
 * Fetches accounts and updates UI. Auto-called on module load.
 * Handles errors gracefully with toast notifications.
 * 
 * @async
 * @returns {Promise<void>}
 * @throws {Error} If API call fails
 */
async function loadImapAccounts() {
    // ...
}
```

### 6.2 User-Dokumentation erstellen

**Deliverable:** `docs/admin/admin-settings-guide.md`

```markdown
# Admin Settings - Benutzerhandbuch

## Zugriff
1. Login mit Admin-Account
2. Click auf User-Avatar (oben rechts)
3. Wähle "System Settings"

## Module-Übersicht

### Dashboard
Zeigt Überblick über alle System-Komponenten:
- IMAP Status
- SMTP Status
- Cron Jobs
- Backup Status
- Database Health
- User Count
- Signatures
- Logger Status

**Quick Actions:**
- Click auf Card → Jump to Detail-Tab

### IMAP Configuration
Verwalte globale IMAP-Einstellungen.

**Neue IMAP-Account hinzufügen:**
1. Navigiere zu "IMAP" Tab
2. Scrolle zu "Add New Account"
3. Fülle Formular aus:
   - Email: info@example.com
   - Host: imap.example.com
   - Port: 993 (SSL) oder 143 (TLS)
   - Encryption: SSL/TLS/None
   - Username: (meist = Email)
   - Password: ***
4. Click "Test Connection"
   - ✅ Erfolg: Grünes Feedback
   - ❌ Fehler: Rote Fehlermeldung mit Details
5. Click "Save" wenn Test erfolgreich

**Account bearbeiten:**
1. Finde Account in Liste
2. Click "Edit" Button
3. Ändere Felder
4. "Test Connection" empfohlen
5. Click "Save"

**Account löschen:**
1. Click "Delete" Button
2. Bestätige in Dialog
3. Account wird entfernt

(... für alle Module)
```

### 6.3 Migration-Anleitung erstellen

**Deliverable:** `docs/dev/MIGRATION-GUIDE.md`

```markdown
# Migration Guide: admin-settings.php → admin-settings-new.php

## Pre-Migration Checklist
- [ ] Backup erstellen
- [ ] Tests auf Staging-System durchführen
- [ ] Alle User informieren (Downtime 2-5min)

## Migration Steps

### 1. Database Migrations (if needed)
```bash
php database/migrate.php
```

### 2. File Rename
```bash
# Backup old version
mv src/public/admin-settings.php src/public/admin-settings-legacy.php.bak

# Activate new version
mv src/public/admin-settings-new.php src/public/admin-settings.php

# Update symlinks/references
grep -r "admin-settings.php" src/views/
# Manually update if found
```

### 3. Clear Caches
```bash
# PHP OPcache
opcache_reset();

# Browser Cache
# Inform users to hard-refresh (Ctrl+F5)
```

### 4. Verify Functionality
```bash
# Run test suite
php tests/manual/admin-settings-test.php

# Manual checks:
# 1. Login as admin
# 2. Navigate to /admin-settings.php
# 3. Test each module (click through all tabs)
# 4. Verify all actions work
```

### 5. Rollback Plan (if issues)
```bash
# Restore old version
mv src/public/admin-settings.php src/public/admin-settings-new-FAILED.php
mv src/public/admin-settings-legacy.php.bak src/public/admin-settings.php

# Clear cache again
opcache_reset();
```

## Post-Migration

### Monitoring (First 24h)
- [ ] Check error logs every 2h
- [ ] Monitor user feedback
- [ ] Check API response times
- [ ] Verify no JS console errors

### Cleanup (After 1 week)
```bash
# If all good, remove backups
rm src/public/admin-settings-legacy.php.bak
rm src/public/admin-settings-old-tabs.php.bak
```

## Known Issues & Workarounds
(None expected, but document if discovered)
```

---

## 📋 Phase 7: Production-Ready Finalisierung (1 Stunde)

### 7.1 Code-Review Checklist

```markdown
# Code Review Checklist

## Architecture
- [ ] Alle Services injizieren Logger
- [ ] Keine direkten DB-Queries in Controllern
- [ ] Repository-Pattern konsistent verwendet
- [ ] DI Container korrekt konfiguriert

## Code Quality
- [ ] PSR-12 Compliance (run phpcs)
- [ ] Alle Files haben `declare(strict_types=1)`
- [ ] Keine TODOs im Production-Code
- [ ] Keine FIXMEs im Production-Code
- [ ] Keine `var_dump()` / `print_r()`
- [ ] Keine `die()` / `exit()` (außer nach Redirects)

## Security
- [ ] Alle Admin-Endpoints haben Auth-Check
- [ ] Input-Validierung für alle Formulare
- [ ] Output-Encoding (XSS-Prevention)
- [ ] CSRF-Protection (wenn Slim Middleware aktiv)
- [ ] SQL-Injection-sicher (Eloquent ORM)

## Performance
- [ ] Keine N+1 Queries (eager loading wo nötig)
- [ ] API-Responses < 200ms
- [ ] Page Load < 500ms
- [ ] JavaScript optimiert (keine Blocking-Scripts)

## Frontend
- [ ] Keine console.log() in Production
- [ ] Error Handling für alle API-Calls
- [ ] Loading-States für Async-Operations
- [ ] Mobile Responsive (getestet)
- [ ] Cross-Browser kompatibel

## Testing
- [ ] Alle API-Endpoints getestet
- [ ] Alle UI-Interaktionen getestet
- [ ] Edge-Cases berücksichtigt
- [ ] Error-Scenarios getestet
```

### 7.2 Performance-Optimierung

**Checks:**
- [ ] Lazy-Loading für Tabs (nur aktiver Tab lädt Content)
- [ ] API-Calls gecached (z.B. 30s für Status)
- [ ] Debouncing für Search-Inputs
- [ ] Pagination für große Listen (>50 Einträge)

### 7.3 Final Smoke Test

**10-Minuten-Test (alle Module durchklicken):**
```
1. Login als Admin
2. Navigate to Admin Settings
3. Dashboard:
   - Alle Cards laden?
   - Metriken korrekt?
4. IMAP Tab:
   - Liste lädt?
   - Test Connection funktioniert?
   - Add/Edit/Delete funktioniert?
5. SMTP Tab:
   - Config speichern funktioniert?
   - Test Email versenden funktioniert?
6. Cron Tab:
   - Status korrekt?
   - Manual Trigger funktioniert?
7. Backup Tab:
   - Create Backup funktioniert?
   - Download funktioniert?
8. Database Tab:
   - Tables anzeigen funktioniert?
   - Optimize funktioniert?
9. Users Tab:
   - List korrekt?
   - CRUD funktioniert?
10. Signatures Tab:
    - List korrekt?
    - CRUD funktioniert?
11. Logger Tab:
    - Live Stream funktioniert?
    - Filter funktionieren?
    - Level-Change speichert?

✅ All Green? → PRODUCTION READY!
```

---

## 📦 Finale Deliverables

### Reports (in `docs/dev/migration-reports/`)
1. ✅ `feature-gap-matrix.md` - Feature-Vergleich alt/neu
2. ✅ `api-endpoints-required.md` - API-Übersicht
3. ✅ `integration-test-results.md` - Test-Ergebnisse
4. ✅ `performance-benchmarks.md` - Performance-Metriken

### Visions (in `docs/dev/module-visions/`)
1. ✅ `010-imap-vision.md`
2. ✅ `020-smtp-vision.md`
3. ✅ `030-cron-vision.md`
4. ✅ `040-backup-vision.md`
5. ✅ `050-database-vision.md`
6. ✅ `060-users-vision.md`
7. ✅ `070-signatures-vision.md`
8. ✅ `080-logger-vision.md`

### Code
1. ✅ 24 neue API-Endpoints (Controller + Service + Routes)
2. ✅ 8 Module vollständig implementiert (kein "Coming Soon")
3. ✅ Alle JavaScript-Funktionen modernisiert
4. ✅ UI konsistent über alle Module
5. ✅ Tests geschrieben und bestanden

### Documentation
1. ✅ `docs/admin/admin-settings-guide.md` - User-Guide
2. ✅ `docs/dev/MIGRATION-GUIDE.md` - Deployment-Guide
3. ✅ Code-Comments in allen neuen Files
4. ✅ API-Dokumentation aktualisiert

---

## 🚨 Wichtige Hinweise

### Do's
✅ **Systematisch vorgehen:** Phase für Phase, Modul für Modul
✅ **Logging nutzen:** Alle Operationen loggen (PSR-3)
✅ **Tests schreiben:** Vor Implementierung überlegen, wie testen
✅ **Dokumentieren:** Code-Comments, User-Docs, API-Docs
✅ **Nachfragen:** Bei Unklarheiten stoppen und fragen

### Don'ts
❌ **Nicht raten:** Lieber nachfragen als falsches implementieren
❌ **Keine Shortcuts:** PSR-12, strict_types, Layer abstraction befolgen
❌ **Nicht hetzen:** Qualität > Geschwindigkeit
❌ **Kein "Quick & Dirty":** Production-Ready Code von Anfang an
❌ **Keine Breaking Changes:** Bestehende APIs nicht ändern

### Bei Problemen
1. **Dokumentiere:** Problem genau beschreiben
2. **Analysiere:** Mögliche Ursachen sammeln
3. **Lösungsoptionen:** 2-3 Vorschläge machen
4. **Frage:** Welchen Weg gehen? (nicht selbst entscheiden bei Architektur)

---

## 📊 Zeitplan & Milestones

### Tag 1 (4h)
- ✅ Phase 1: Analyse & Vergleich (2h)
- ✅ Phase 2: Module-Visions (2h)

### Tag 2 (4h)
- ✅ Phase 3: Backend-APIs (4h)
  - Priorität 1: IMAP, Logger, Database

### Tag 3 (3h)
- ✅ Phase 3: Backend-APIs (1h)
  - Priorität 2: SMTP, Cron, User Stats
- ✅ Phase 4: Frontend-Migration (2h)

### Tag 4 (3h)
- ✅ Phase 4: Frontend-Migration (1h)
- ✅ Phase 5: Testing (2h)

### Tag 5 (2h)
- ✅ Phase 5: Testing (1h)
- ✅ Phase 6: Dokumentation (1h)

### Tag 6 (1h)
- ✅ Phase 7: Finalisierung (1h)

**Total:** ~17 Stunden (2-3 Arbeitstage)

---

## 🎯 Success Metrics

### Quantitative
- ✅ 0 "Coming Soon" Placeholders
- ✅ 24+ API-Endpoints implementiert
- ✅ 8 Module vollständig funktional
- ✅ 100% Test-Coverage (manuelle Tests)
- ✅ 0 console.error() in Production
- ✅ Page Load < 500ms
- ✅ API Response < 200ms
- ✅ Lighthouse Score ≥ 90

### Qualitative
- ✅ Admin kann alle Funktionen ohne Dokumentation nutzen
- ✅ UI ist konsistent und intuitiv
- ✅ Code ist wartbar und erweiterbar
- ✅ Dokumentation ist vollständig und klar
- ✅ System ist stabil (keine Crashes bei normalem Gebrauch)

---

## 🚀 Los geht's!

```bash
# 1. Branch erstellen
git checkout -b feature/admin-settings-modular

# 2. Phase 1 starten
echo "Starting Phase 1: Analysis & Comparison..."
mkdir -p docs/dev/migration-reports
mkdir -p docs/dev/module-visions

# 3. Erste Analyse durchführen
# → Öffne admin-settings.php und admin-settings-new.php
# → Erstelle feature-gap-matrix.md
```

**Viel Erfolg! Bei Fragen einfach melden.** 🎉

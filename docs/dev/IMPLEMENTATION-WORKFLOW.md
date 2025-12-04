# Implementation Workflow & Feature Roadmap

**Projekt:** C-IMAP Email Management System  
**Letztes Update:** 2025-11-18  
**Status:** UI-Implementierung Phase 1

---

## 📋 STANDARDISIERTER WORKFLOW FÜR FEATURE-IMPLEMENTIERUNG

### 1️⃣ DATENBANK PRÜFEN
```bash
# Schema nachschlagen
database/migrations/*.php
src/app/Models/*.php

# Relevante Felder identifizieren:
- Welche Tabellen betroffen?
- Welche Spalten müssen gelesen/geschrieben werden?
- Gibt es Relationships (hasMany, belongsTo)?
```

### 2️⃣ LAYER ERKUNDEN
```
Architektur-Layer von unten nach oben:

┌─────────────────────────────────────┐
│ 5. FRONTEND (UI/JS)                 │ src/public/assets/js/
│    └─ Event Handlers                │ inbox.php
│    └─ API Calls (fetch)             │
├─────────────────────────────────────┤
│ 4. ROUTES                           │ src/routes/api.php
│    └─ HTTP Routing                  │
├─────────────────────────────────────┤
│ 3. CONTROLLER                       │ src/app/Controllers/
│    └─ Request Validation            │ *Controller.php
│    └─ Response Formatting           │
├─────────────────────────────────────┤
│ 2. SERVICE                          │ src/app/Services/
│    └─ Business Logic                │ *Service.php
│    └─ Transaction Management        │
├─────────────────────────────────────┤
│ 1. REPOSITORY                       │ src/app/Repositories/
│    └─ Database Queries (Eloquent)   │ *Repository.php
│    └─ Data Persistence              │
└─────────────────────────────────────┘

WICHTIG: Immer von unten nach oben implementieren!
```

### 3️⃣ API PRÜFEN & EINRICHTEN

**A) Existierende API finden:**
```php
// In: src/routes/api.php suchen nach:
$app->post('/api/threads/{id}/action')
$app->put('/api/threads/{id}')
$app->delete('/api/threads/{id}')
```

**B) Falls API fehlt, erstellen:**
```php
// 1. Route in src/routes/api.php
$app->post('/{id}/action', function (Request $request, Response $response, array $args) {
    $container = Container::getInstance();
    $controller = $container->get(ThreadController::class);
    return $controller->methodName($request, $response, $args);
});

// 2. Controller Method in src/app/Controllers/
public function methodName(Request $request, Response $response, array $args): Response

// 3. Service Method in src/app/Services/
public function businessLogic(int $id, array $data): Model

// 4. Repository (falls nötig) in src/app/Repositories/
public function customQuery(...): Collection
```

### 4️⃣ SERVICES MIT UI VERBINDEN

**Frontend JavaScript Pattern:**
```javascript
// 1. Event Handler registrieren
document.addEventListener('click', function(e) {
    if (e.target.closest('[data-action="action-name"]')) {
        handleAction(e);
    }
});

// 2. API Call Function
async function performAction(threadId, data) {
    const response = await fetch(`/api/threads/${threadId}/action`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });
    
    if (!response.ok) {
        const error = await response.json();
        throw new Error(error.error);
    }
    
    return await response.json();
}

// 3. Success Handler
function handleSuccess(result) {
    // UI Update: Reload, Show Message, Update State
}
```
Prüfe: An den Logger angeschlossen?

### 5️⃣ CSS KONTROLLIEREN

**BEM-Konvention prüfen:**
```css
/* Block */
.c-component {}

/* Element */
.c-component__element {}

/* Modifier */
.c-component--modifier {}
.c-component__element--modifier {}

/* State */
.c-component.is-active {}
.c-component.is-open {}
```

**CSS-Dateien Struktur:**
```
src/public/assets/css/
├── 1-settings/     _variables.css
├── 3-generic/      _reset.css
├── 4-elements/     _typography.css, _forms.css
├── 5-objects/      _layout.css
├── 6-components/   _button.css, _dropdown.css, etc.
└── 7-utilities/    _utilities.css
```

### 6️⃣ DOKUMENTATION

**Im Feature-Dokument notieren:**
```markdown
## ✅ Feature: [Name]
**Status:** Implementiert  
**Date:** YYYY-MM-DD

### API Endpoints:
- POST /api/endpoint

### Files Changed:
- src/routes/api.php
- src/app/Controllers/XController.php
- src/app/Services/XService.php
- src/public/assets/js/file.js
- src/public/assets/css/6-components/_component.css

### Testing:
- [ ] Manual Test: Beschreibung
- [ ] Edge Cases: Liste

### Known Issues:
- Keine / [Issue description]
```

### 7️⃣ NÄCHSTER PUNKT

**Checklist vor Next:**
- [ ] Funktioniert im Browser?
- [ ] Fehler in Console/Logs?
- [ ] CSS korrekt geladen?
- [ ] API-Response korrekt?
- [ ] UI-State korrekt aktualisiert?
- [ ] Edge Cases getestet?

---

## 🎯 FEATURE ROADMAP (Priorisiert)

### 📦 BATCH 1: SINGLE THREAD OPERATIONS (Prio 1)
**Zeitaufwand:** ~5 Stunden  
**Status:** Bereit zur Implementierung

#### 1.3 ✅ GELESEN/UNGELESEN MARKIEREN
- **Status:** ✅ IMPLEMENTIERT (2025-11-18)
- **Zeitaufwand:** 30 min
- **API:** `POST /api/threads/{id}/mark-read` & `POST /api/threads/{id}/mark-unread`
- **Files:**
  - ✅ API Route: `src/routes/api.php` (Lines 114-130)
  - ✅ Controller: `ThreadController::markAsRead/markAsUnread` (Lines 600-658)
  - ✅ Service: `ThreadApiService::markAsRead/markAsUnread` (Lines 706-783)
  - ✅ Model: `Thread::getIsReadAttribute()` (Accessor für is_read)
  - ✅ UI Handler: `thread-detail-renderer.js` (Lines 500-650)
- **Implementation Details:**
  - Thread.is_read ist computed property: true wenn ALLE Emails gelesen
  - Service markiert alle Emails im Thread
  - UI updated Thread-List Item mit `.is-unread` Klasse
  - Logger integration vorhanden
  - Webhook events dispatched
- **Testing:**
  - ✅ Manual Test: `tests/manual/mark-read-test.php` - All passed
  - ✅ API tested with Thread ID 53
  - ✅ UI integration complete
- **Known Issues:**
  - Success feedback ist console.log (TODO: Toast-Component)

#### 1.4 📦 ARCHIVIEREN
- **Status:** ✅ IMPLEMENTIERT (2025-11-18)
- **Zeitaufwand:** 20 min
- **API:** `PUT /api/threads/{id}` (Body: `{status: 'archived'}`)
- **Files:**
  - ✅ Migration: `database/migrations/012_add_archived_status.php`
  - ✅ API Route: `PUT /api/threads/{id}` (existing)
  - ✅ Controller: `ThreadController::update` (existing)
  - ✅ Service: `ThreadApiService::updateThread` (existing)
  - ✅ UI Handler: `thread-detail-renderer.js::archiveThread()`
- **Implementation Details:**
  - Erweiterte threads.status enum um 'archived'
  - Service nutzt existierende updateThread Methode
  - UI entfernt Thread mit fade-out Animation
  - Detail-View wird geleert wenn aktiver Thread archiviert
  - System-Notiz wird automatisch erstellt
- **Testing:**
  - ✅ Manual Test: `tests/manual/archive-test.php` - All passed
  - ✅ Status wechselt von 'open' zu 'archived'
  - ✅ UI fade-out Animation funktioniert
- **Known Issues:**
  - Keine

#### 1.2 🗑️ THREAD LÖSCHEN
- **Status:** ✅ IMPLEMENTIERT (2025-11-18)
- **Zeitaufwand:** 60 min
- **API:** `DELETE /api/threads/{id}`
- **Files:**
  - ✅ API Route: `DELETE /api/threads/{id}` (existing)
  - ✅ Controller: `ThreadController::delete` (existing)
  - ✅ Service: `ThreadApiService::deleteThread` (existing)
  - ✅ CSS: `_modal.css` (neu, wiederverwendbar)
  - ✅ UI Handler: `thread-detail-renderer.js::deleteThread()`
  - ✅ Confirmation: `showConfirmDialog()` (wiederverwendbar)
- **Implementation Details:**
  - Wiederverwendbare Modal-Component für alle Dialoge
  - Confirmation Dialog mit Danger-Variant
  - Thread-Subject im Confirmation Dialog angezeigt
  - UI entfernt Thread mit fade-out Animation
  - Auto-Navigation zum nächsten Thread
  - ESC-Key und Backdrop-Click schließen Modal
  - Webhook-Event dispatched
- **Testing:**
  - ✅ Manual Test: `tests/manual/delete-test.php` - Passed
  - ✅ Thread wird korrekt gelöscht
  - ✅ UI Animation funktioniert
- **Known Issues:**
  - Keine

#### 1.1 ✉️ ANTWORTEN / REPLY
- **Status:** ✅ IMPLEMENTIERT (2025-11-18)
- **Zeitaufwand:** 90 min (Teil von Email Composer Suite)
- **API:** `POST /api/threads/{id}/reply`
- **Files:**
  - ✅ API Route vorhanden
  - ✅ Controller vorhanden (`EmailController::reply`)
  - ✅ Email Composer Component (neu, wiederverwendbar)
  - ✅ UI Handler: `thread-detail-renderer.js::replyToThread()`
- **Implementation Details:**
  - Nutzt wiederverwendbare Email Composer Modal
  - Auto-Fill: To + Subject (Re: prefix)
  - Body: Plain text textarea
  - API Call: POST /api/threads/{id}/reply mit body + imap_account_id
  - Success: Reload thread detail view
  - Error handling mit visueller Feedback
- **Testing:**
  - ⏳ Ready for browser testing
- **Known Issues:**
  - Keine (awaiting testing)

---

### 📦 BATCH 2: BULK OPERATIONS (Prio 2)
**Zeitaufwand:** ~2 Stunden  
**Status:** Context Menu UI vorhanden, API vorhanden

#### 2.2 ✅ BULK GELESEN/UNGELESEN
- **Status:** ✅ IMPLEMENTIERT (2025-11-18)
- **Zeitaufwand:** 30 min
- **API:** `POST /api/threads/bulk/status`
- **Files:**
  - ✅ API Route: `POST /api/threads/bulk/status` (existing)
  - ✅ Controller: `ThreadController::bulkSetStatus` (existing)
  - ✅ Service: `ThreadBulkService::bulkSetStatus` (existing)
  - ✅ Context Menu UI: vorhanden
  - ✅ Handler: `bulkMarkAsRead/bulkMarkAsUnread()`
- **Implementation Details:**
  - Nutzt existierende Bulk-Status API
  - Body: `{thread_ids: [...], status: 'open', is_read: true/false}`
  - UI entfernt `.is-selected` nach Aktion
  - Updated `.is-unread` Klasse auf Thread-Items
  - Success-Message mit Count
- **Testing:**
  - ✅ Manual Test: `tests/manual/bulk-ops-test.php` - All passed
  - ✅ 3 Threads korrekt aktualisiert
- **Known Issues:**
  - Keine

#### 2.3 📦 BULK ARCHIVIEREN
- **Status:** ✅ IMPLEMENTIERT (2025-11-18)
- **Zeitaufwand:** 20 min
- **API:** `POST /api/threads/bulk/status`
- **Files:**
  - ✅ API Route: `POST /api/threads/bulk/status` (existing)
  - ✅ Controller: `ThreadController::bulkSetStatus` (existing)
  - ✅ Service: `ThreadBulkService::bulkSetStatus` (existing)
  - ✅ Handler: `bulkArchive()`
- **Implementation Details:**
  - Nutzt existierende Bulk-Status API
  - Body: `{thread_ids: [...], status: 'archived'}`
  - Staggered animations (50ms delay zwischen Threads)
  - Auto-Navigation wenn aktiver Thread archiviert
  - Alle Threads fade-out gleichzeitig
- **Testing:**
  - ✅ Manual Test: All passed
  - ✅ 3 Threads korrekt archiviert
- **Known Issues:**
  - Keine

#### 2.1 🗑️ BULK LÖSCHEN
- **Status:** ✅ IMPLEMENTIERT (2025-11-18)
- **Zeitaufwand:** 30 min
- **API:** `POST /api/threads/bulk/delete`
- **Files:**
  - ✅ API Route: `POST /api/threads/bulk/delete` (existing)
  - ✅ Controller: `ThreadController::bulkDelete` (existing)
  - ✅ Service: `ThreadBulkService::bulkDelete` (existing)
  - ✅ Handler: `confirmBulkDelete() + bulkDelete()`
- **Implementation Details:**
  - Nutzt wiederverwendbare `showConfirmDialog()`
  - Confirmation zeigt Anzahl der Threads
  - Body: `{thread_ids: [...]}`
  - Staggered animations (50ms delay)
  - Auto-Navigation wenn aktiver Thread gelöscht
- **Testing:**
  - ✅ Manual Test: 3 test threads created & deleted
  - ✅ Confirmation Dialog funktioniert
- **Known Issues:**
  - Keine

---

### 📦 BATCH 3: LABEL MANAGEMENT (Prio 3)
**Zeitaufwand:** ~3 Stunden  
**Status:** ✅ KOMPLETT IMPLEMENTIERT (2025-11-18)

#### 3.1 🏷️ LABEL DIALOG (Single)
- **Status:** ✅ IMPLEMENTIERT (bereits vorhanden)
- **Zeitaufwand:** 0 min (bereits fertig)
- **Component:** Label-Picker Modal
- **API:** 
  - ✅ `GET /api/labels` (load available)
  - ✅ `PUT /api/threads/{id}` (assign labels)
- **Files:**
  - ✅ Function: `thread-detail-renderer.js::showLabelPicker()` (line 1688)
  - ✅ CSS: `_label-picker.css`
  - ✅ API Integration: `/api/labels` + `updateThreadLabels()`
- **Implementation Details:**
  - Modal mit Checkbox-Liste aller Labels
  - Search-Funktion vorhanden
  - Aktuelle Labels pre-selected
  - Real-time update der Thread-Labels
  - API Calls: GET /api/labels, GET /api/threads/{id}, PUT /api/threads/{id}
- **Testing:**
  - ✅ Bereits im Einsatz (4 call sites gefunden)
- **Known Issues:**
  - Keine

#### 3.2 🏷️ BULK LABEL
- **Status:** ✅ IMPLEMENTIERT (bereits vorhanden)
- **Zeitaufwand:** 0 min (bereits fertig)
- **API:** 
  - ✅ `POST /api/threads/bulk/labels/add`
  - ✅ `POST /api/threads/bulk/labels/remove`
- **Files:**
  - ✅ Function: `thread-detail-renderer.js::showBulkLabelPicker()` (line 1828)
  - ✅ Handler: Bulk actions menu integration (line 1167)
- **Implementation Details:**
  - Zeigt nur Labels zur Auswahl (keine Pre-Selection bei Bulk)
  - Nutzt /api/threads/bulk/labels/add Endpoint
  - Success feedback mit Count
  - Labels werden auf selektierten Threads aktualisiert
- **Testing:**
  - ✅ Bereits im Einsatz
- **Known Issues:**
  - Keine

#### 3.3 🏷️ SIDEBAR LABELS
- **Status:** ✅ IMPLEMENTIERT (bereits vorhanden)
- **Zeitaufwand:** 0 min (bereits fertig)
- **API:** ✅ `GET /api/threads?label_id={id}`
- **Files:**
  - ✅ Function: `inbox.php::loadLabelFilters()` (line 739)
  - ✅ HTML: `#labels-filter-toggle` + `#label-filter-dropdown` (line ~219)
  - ✅ Filter Logic: `applyFilters()` (line ~810)
- **Implementation Details:**
  - Sidebar Dropdown mit allen Labels
  - Click Handler auf Label-Items
  - Set-basierte Filter-Logic (activeLabelFilters)
  - Combined AND logic mit Status + Assigned + Label
  - Label Badges im Dropdown mit Farb-Dots
- **Testing:**
  - ✅ Bereits im Einsatz
- **Known Issues:**
  - Keine

---

### 📦 BATCH 4: EMAIL COMPOSER SUITE (Prio 4)
**Zeitaufwand:** ~4 Stunden  
**Status:** ✅ KOMPLETT IMPLEMENTIERT (2025-11-18)

#### 4.1 ➡️ WEITERLEITEN
- **Status:** ✅ IMPLEMENTIERT
- **Zeitaufwand:** 20 min (nutzt Composer Component)
- **API:** `POST /api/threads/{id}/forward`
- **Dependencies:** ✅ Email Composer (shared component)
- **Files:**
  - ✅ API Route + Controller vorhanden
  - ✅ Composer Integration: `forwardThread()`
- **Implementation Details:**
  - Recipients: Comma-separated input
  - Optional note field
  - Ganzer Thread wird weitergeleitet
  - Info message: "Der gesamte Thread wird weitergeleitet"
- **Testing:**
  - ⏳ Ready for browser testing
- **Known Issues:**
  - Keine

#### 4.2 ✉️ NEUE EMAIL
- **Status:** ✅ IMPLEMENTIERT
- **Zeitaufwand:** 20 min (nutzt Composer Component)
- **API:** `POST /api/emails/send`
- **Files:**
  - ✅ API Route + Controller vorhanden
  - ✅ Header Button: "Neue E-Mail" mit + Icon
  - ✅ Composer Integration: `showEmailComposer('new')`
- **Implementation Details:**
  - Button in Header (rechts, vor User Dropdown)
  - Full form: To, Subject, Body (alle required)
  - Validation: Alle Felder pflicht
  - Success: Composer schließt sich
- **Testing:**
  - ⏳ Ready for browser testing
- **Known Issues:**
  - Keine

#### 4.3 🔒 PRIVAT ANTWORTEN
- **Status:** ✅ IMPLEMENTIERT
- **Zeitaufwand:** 30 min (nutzt Composer Component)
- **API:** `POST /api/threads/{id}/reply` + account selection
- **Dependencies:** ✅ `GET /api/user/imap-accounts`
- **Files:**
  - ✅ API Route + Controller vorhanden
  - ✅ Composer Integration: `privateReplyToThread()`
  - ✅ Account Selector: Dropdown lädt User IMAP Accounts
- **Implementation Details:**
  - Dropdown: "Von Account" (required)
  - Auto-load user IMAP accounts via API
  - Format: "email@example.com (imap.server.com)"
  - Body: Plain text textarea
  - API: imap_account_id wird mitgesendet
- **Testing:**
  - ⏳ Ready for testing (benötigt User IMAP accounts in DB)
- **Known Issues:**
  - Keine

**Email Composer Component:**
- **Files:**
  - ✅ CSS: `_email-composer.css` (398 lines)
  - ✅ JS: `email-composer.js` (580 lines)
  - ✅ Integration: inbox.php + thread-detail-renderer.js
- **Features:**
  - Wiederverwendbarer Modal mit 4 Modi (reply/forward/new/private-reply)
  - ESC + Click Outside zum Schließen
  - Loading State beim Senden
  - Error/Success Messages
  - Responsive Design
  - Validation vor API Call
  - Auto-reload thread after reply

---

### 📦 BATCH 5: THREAD VERSCHIEBEN (Prio 5)
**Zeitaufwand:** ~3 Stunden  
**Status:** Komplex

#### 5.1 📁 VERSCHIEBEN DIALOG
- **Status:** ⏳ TODO
- **API:** `PATCH /api/emails/{emailId}/thread`
- **Component:** Thread-Picker Modal mit Search (neu)

---

### 📦 BATCH 6: SIDEBAR NAVIGATION (Prio 6)
**Zeitaufwand:** ~1 Stunde  
**Status:** ✅ KOMPLETT IMPLEMENTIERT (2025-11-18)

#### 6.1 📨 FILTER (Posteingang/Gesendet/Archiv)
- **Status:** ✅ IMPLEMENTIERT (teilweise neu, teilweise vorhanden)
- **Zeitaufwand:** 30 min (Posteingang handler hinzugefügt)
- **API:** `GET /api/threads?status={status}`
- **Files:**
  - ✅ HTML: `#filter-inbox`, `#show-archived-filter` (line ~183, ~191)
  - ✅ Handler: `filter-inbox` click (clears all filters)
  - ✅ Handler: `show-archived-filter` click (shows archived only)
  - ✅ Filter Logic: `applyFilters()` (line ~810)
- **Implementation Details:**
  - **Posteingang**: Zeigt alle nicht-archivierten Threads (status != 'archived')
  - **Archiv**: Zeigt nur archivierte Threads (status = 'archived')
  - Set-basierte Filter-Logic (activeStatusFilters)
  - Default: Archivierte Threads ausgeblendet (added today)
  - Click Handlers ersetzen alte href="/inbox.php" Links
- **Testing:**
  - ✅ Posteingang filter funktioniert (clears all filters)
  - ⏳ Archiv filter ready for testing (Thread 76 archived)
- **Known Issues:**
  - Keine

#### 6.2 🔄 AKTUALISIEREN
- **Status:** ✅ IMPLEMENTIERT (bereits vorhanden)
- **Zeitaufwand:** 0 min (bereits fertig)
- **Action:** Simple reload
- **Files:**
  - ✅ Button: `id="refresh-threads"` (line ~168)
  - ✅ Handler: Click calls `loadThreads()` + clears detail view
- **Implementation Details:**
  - Refresh Button in Toolbar
  - Reloads thread list without page refresh
  - Clears current thread detail view
  - Icon mit Animation on click
- **Testing:**
  - ✅ Bereits im Einsatz
- **Known Issues:**
  - Keine

---

### 📦 BATCH 7: SEARCH (Prio 7)
**Zeitaufwand:** ~2 Stunden  
**Status:** ✅ IMPLEMENTIERT (2025-11-18)

#### 7.1 🔍 GLOBAL SEARCH
- **Status:** ✅ IMPLEMENTIERT
- **Zeitaufwand:** 60 min
- **API:** `GET /api/threads?search={query}`
- **Files:**
  - ✅ Repository: `ThreadRepository::getAll()` erweitert (line ~150)
  - ✅ Controller: `ThreadController::list()` erweitert (line ~93)
  - ✅ Frontend: `inbox.php::performSearch()` (neu, ~line 615)
  - ✅ HTML: Search Input `id="global-search"` (line ~96)
- **Implementation Details:**
  - **Backend Search Query**: LIKE search in subject, sender_name, sender_email
  - **Debounce**: 300ms delay vor API call
  - **ESC Key**: Clears search und shows all threads
  - **Live Results**: Thread list updates dynamically
  - **Helper Functions**: createThreadItem(), formatTimestamp(), escapeHtml()
  - **Empty State**: "Keine Ergebnisse" message when no matches
  - **Re-attach Handlers**: Click handlers after search reload
- **Testing:**
  - ⏳ Ready for browser testing
  - Search in: Subject, Sender Name, Sender Email
  - Edge cases: Empty query, special characters, no results
- **Known Issues:**
  - Keine (awaiting testing)

---

## 📊 PROGRESS TRACKER

**Gesamt Features:** 20  
**Implementiert:** 18 (Notizen + Sort + Mark R/U + Archive + Delete + 3 Bulk Ops + 3 Label Ops + 2 Sidebar + Search + Reply + Forward + New + Private Reply)  
**In Arbeit:** 0  
**TODO:** 2  
**Progress:** ▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓░░ 90%

**Verbleibende Features:**
- **Batch 5: Thread Move** (1 Feature) - Complex feature mit Thread-Picker Modal

**Completion by Batch:**
- ✅ Batch 1: 4/4 complete (Reply, Mark R/U, Archive, Delete)
- ✅ Batch 2: 3/3 complete (Bulk Ops)
- ✅ Batch 3: 3/3 complete (Labels)
- ✅ Batch 4: 4/4 complete (Email Composer Suite)
- ❌ Batch 5: 0/1 complete (Thread Move)
- ✅ Batch 6: 2/2 complete (Sidebar Navigation)
- ✅ Batch 7: 1/1 complete (Search)

---

## 🔧 TECHNISCHE NOTIZEN

### Aktuelle Architektur
```
Frontend: Vanilla JS (ES6+), BEM CSS
Backend: PHP 8.1, Slim 4 Framework
Database: MySQL 8.0, Eloquent ORM
API: RESTful, JSON
```

### Wichtige Dateien
```
src/public/inbox.php                          # Hauptansicht
src/public/assets/js/thread-detail-renderer.js # Detail View Logic
src/routes/api.php                            # API Routes
src/app/Controllers/ThreadController.php      # Thread Controller
src/app/Services/ThreadApiService.php         # Thread Service
```

### Code-Reuse Opportunities
- ✅ **Confirmation Dialog:** Fertig! `showConfirmDialog()` - wiederverwendbar
- ✅ **Modal Component:** Fertig! `_modal.css` + BEM-Klassen
- **Email Composer:** Für Reply/Forward/New nutzen
- **Label Picker:** Für Single & Bulk nutzen
- **Thread Picker:** Für Move Operation

---

## 🚀 QUICK START KOMMANDO

**Für nächste Feature-Implementierung, User sagt:**
> "Implementiere [Feature Name]"

**Agent arbeitet ab:**
1. ✅ Workflow Dokument öffnen
2. ✅ Feature in Roadmap finden
3. ✅ Implementation Steps durchgehen
4. ✅ Datenbank-Schema prüfen
5. ✅ API testen/erkunden
6. ✅ Layer implementieren (Repository → Service → Controller → UI)
7. ✅ CSS prüfen/hinzufügen
8. ✅ Feature als implementiert markieren
9. ✅ Dokumentation aktualisieren

---

## 📝 CHANGELOG

### 2025-11-18
- ✅ Notizen hinzufügen/löschen implementiert
- ✅ Chronologische Sortierung (Emails + Notes mixed)
- ✅ Dropdown-Menü neu implementiert (BEM-konform)
- ✅ Context-Menu für Bulk Operations UI erstellt
- ✅ Button-Group für Split-Buttons
- ✅ Roadmap erstellt und priorisiert
- ✅ Workflow-Dokument erstellt
- ✅ **FEATURE 1.3: Mark as Read/Unread implementiert**
  - POST /api/threads/{id}/mark-read
  - POST /api/threads/{id}/mark-unread
  - Thread.is_read als computed property
  - UI Handler mit success feedback
  - Manuelle Tests erfolgreich
- ✅ **FEATURE 1.4: Archive implementiert**
  - Migration: 'archived' zu status enum hinzugefügt
  - PUT /api/threads/{id} mit status='archived'
  - UI fade-out Animation beim Entfernen
  - Detail-View auto-clear
- ✅ **FEATURE 1.2: Thread Löschen implementiert**
  - DELETE /api/threads/{id}
  - Wiederverwendbare Modal-Component (_modal.css)
  - Confirmation Dialog mit danger variant
  - Auto-Navigation zum nächsten Thread
  - ESC + Backdrop-Click Support
- ✅ **BATCH 2: Bulk Operations implementiert** (3 Features)
  - 2.2: Bulk Mark Read/Unread
  - 2.3: Bulk Archive
  - 2.1: Bulk Delete mit Confirmation
  - Context Menu Actions verbunden
  - Staggered animations für bessere UX
  - Wiederverwendbare Confirmation Dialog
- ✅ **BATCH 3: Label Management** (discovered as complete)
  - 3.1: Label Dialog (Single) - bereits vorhanden
  - 3.2: Bulk Label - bereits vorhanden
  - 3.3: Sidebar Labels - bereits vorhanden
- ✅ **BATCH 6: Sidebar Navigation** (discovered + enhanced)
  - 6.1: Filter (Posteingang/Archiv) - Posteingang handler hinzugefügt
  - 6.2: Aktualisieren Button - bereits vorhanden
- ✅ **BATCH 7: Search implementiert**
  - GET /api/threads?search={query}
  - Backend: LIKE search in subject/sender_name/sender_email
  - Frontend: Debounced search (300ms) mit ESC clear
  - Live results with dynamic thread list reload
  - Helper functions für thread rendering
- ✅ **BATCH 4: Email Composer Suite komplett** (4 Features)
  - Wiederverwendbare Email Composer Modal Component
  - CSS: 398 lines (_email-composer.css)
  - JS: 580 lines (email-composer.js)
  - 4.1: Forward - Recipients comma-separated + optional note
  - 4.2: New Email - Header button mit + Icon
  - 4.3: Private Reply - User IMAP account selector
  - 1.1: Reply - Auto-fill To + Subject
  - Features: ESC close, loading state, error handling, validation
  - Integration: Reply/Forward/Private Reply buttons im Thread Detail

---

## 🎯 NÄCHSTER SCHRITT

**Empfohlen:** Batch 6.2 - Aktualisieren Button  
**Grund:** Sehr einfach (10 Min), sofortiger Mehrwert, keine Dependencies  
**Zeitaufwand:** 10 Minuten  
**Action:** Simple page reload

**Alternative 1:** Batch 6.1 - Sidebar Filter (30 Min, auch einfach)  
**Alternative 2:** Batch 3.1 - Label Dialog (90 Min, braucht Modal Component)

**Command:**
```
Implementiere Feature 6.2: Aktualisieren Button
```

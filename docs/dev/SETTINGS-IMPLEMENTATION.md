# Settings & System Configuration Implementation

**Projekt:** CI-Inbox Email Management System  
**Letztes Update:** 2025-11-18  
**Status:** Planung & Implementierung

---

## 📋 ARCHITECTURE OVERVIEW

### Settings-System Struktur

```
┌─────────────────────────────────────────────────────┐
│                  SETTINGS SYSTEM                     │
├─────────────────────────────────────────────────────┤
│                                                      │
│  USER SETTINGS              SYSTEM SETTINGS          │
│  (Per User)                 (Admin Only)             │
│                                                      │
│  • Profile & Avatar         • Global IMAP Config    │
│  • Password Change          • SMTP Config            │
│  • Email Signature          • Cron Monitor           │
│  • Personal IMAP Accounts   • Log Monitor            │
│  • Notifications            • Backup & Restore       │
│  • Display Preferences      • Webhook Management     │
│                             • User Management (CRUD) │
│                             • 🔴 Danger Zone         │
│                                                      │
└─────────────────────────────────────────────────────┘
```

---

## 🎯 PHASE 1: MVP IMPLEMENTATION (Jetzt)

### 📦 BATCH 1: USER SETTINGS - PROFILE & SECURITY
**Zeitaufwand:** ~3 Stunden  
**Prio:** 1

#### 1.1 👤 PROFILE SETTINGS
**Status:** ✅ COMPLETED (Backend 100%, Frontend 100%)  
**Zeitaufwand:** 90 min ✅

**Features:**
- ✅ Name ändern
- ✅ Email (Display) ändern
- ✅ Avatar/Bild hochladen
- ✅ Timezone auswählen
- ✅ Language (für später)

**Database Schema:**
```sql
users table (already exists):
- id
- name
- email
- avatar_path (NEW - nullable) ✅ ADDED
- timezone (NEW - default: 'UTC') ✅ ADDED
- language (NEW - default: 'de') ✅ ADDED
- created_at
- updated_at
```

**Migration:** `013_add_user_settings_fields.php` ✅ CREATED & EXECUTED

**API Endpoints:**
- ✅ `GET /api/user/profile` (current user) ✅ IMPLEMENTED
- ✅ `PUT /api/user/profile` (update name, email, timezone) ✅ IMPLEMENTED
- ✅ `POST /api/user/profile/avatar` (upload avatar) ✅ IMPLEMENTED
- ✅ `DELETE /api/user/profile/avatar` (remove avatar) ✅ IMPLEMENTED

**Files:**
```
database/migrations/013_add_user_settings_fields.php      ✅ CREATED
src/app/Controllers/UserProfileController.php             ✅ CREATED (280 lines)
src/app/Services/UserProfileService.php                   ✅ CREATED (255 lines)
src/public/settings.php                                   ✅ CREATED (600+ lines)
src/public/assets/js/user-settings.js                     ✅ CREATED (390 lines)
src/config/container.php                                  ✅ UPDATED (registered services)
src/routes/api.php                                        ✅ UPDATED (5 routes added)
```

**Implementation Steps:**
1. ✅ Prüfe User Model/API (bereits vorhanden)
2. ✅ Migration erstellen für neue Felder
3. ✅ UserProfileController erstellen (5 endpoints)
4. ✅ UserProfileService mit Avatar-Upload (2MB limit, validation)
5. ✅ Frontend: Settings-Panel UI (3 tabs: Profile, Personal IMAP, Security)
6. ✅ Avatar Upload mit File Input (stored in data/uploads/avatars/)
7. ✅ Image Preview (2-letter initials as fallback)
8. ✅ API Integration (all endpoints connected)
9. ✅ Success Feedback (alert system with auto-hide)

---

#### 1.2 🔒 PASSWORD CHANGE
**Status:** ✅ COMPLETED  
**Zeitaufwand:** 30 min ✅

**Features:**
- ✅ Current password verification
- ✅ New password (2x confirm)
- ✅ Password strength indicator (min 8 chars, match validation)
- ✅ Success feedback

**API Endpoints:**
- ✅ `POST /api/user/profile/change-password` ✅ IMPLEMENTED (via UserProfileController)

**Files:**
```
src/public/assets/js/user-settings.js                     [UPDATE]
src/public/assets/css/6-components/_password-form.css    [NEW]
```

**Implementation Steps:**
1. Password Change Form UI
2. Client-side validation
3. Password strength indicator (JS)
4. API Integration (bereits vorhanden!)
5. Success/Error handling

---

#### 1.3 ✍️ EMAIL SIGNATURE
**Status:** ✅ COMPLETED (Backend 100%, Frontend 100%)  
**Zeitaufwand:** 90 min ✅  
**Completed:** 2025-11-18

**Features:**
- ✅ Textarea Editor für Signature Content (HTML support)
- ✅ Multiple Signatures (Personal + Global)
- ✅ Set Default Signature
- ✅ SMTP Status Check (hide personal if not configured)
- ✅ Signature List UI with badges (Default, Global)

**Database Schema:**
```sql
CREATE TABLE signatures (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,                    -- NULL für global signatures
    type ENUM('personal', 'global') NOT NULL DEFAULT 'personal',
    name VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_type (type)
);
```

**Migration:** `014_create_signatures_table.php` ✅ CREATED & EXECUTED

**API Endpoints:**
- ✅ `GET /api/user/signatures` (list all: personal + global) ✅ IMPLEMENTED
- ✅ `GET /api/user/signatures/smtp-status` (check SMTP config) ✅ IMPLEMENTED
- ✅ `GET /api/user/signatures/{id}` (get single) ✅ IMPLEMENTED
- ✅ `POST /api/user/signatures` (create) ✅ IMPLEMENTED
- ✅ `PUT /api/user/signatures/{id}` (update) ✅ IMPLEMENTED
- ✅ `DELETE /api/user/signatures/{id}` (delete) ✅ IMPLEMENTED
- ✅ `POST /api/user/signatures/{id}/set-default` (set default) ✅ IMPLEMENTED
- ✅ `GET /api/admin/signatures` (admin: list global) ✅ IMPLEMENTED
- ✅ `POST /api/admin/signatures` (admin: create global) ✅ IMPLEMENTED
- ✅ `PUT /api/admin/signatures/{id}` (admin: update global) ✅ IMPLEMENTED
- ✅ `DELETE /api/admin/signatures/{id}` (admin: delete global) ✅ IMPLEMENTED
- ✅ `POST /api/admin/signatures/{id}/set-default` (admin: set default) ✅ IMPLEMENTED

**Files:**
```
database/migrations/014_create_signatures_table.php       ✅ CREATED
src/app/Models/Signature.php                              ✅ CREATED (100 lines)
src/app/Repositories/SignatureRepository.php              ✅ CREATED (178 lines, full logging)
src/app/Services/SignatureService.php                     ✅ CREATED (428 lines)
src/app/Controllers/SignatureController.php               ✅ CREATED (217 lines)
src/public/settings.php                                   ✅ UPDATED (added signatures tab & modal)
src/public/assets/js/user-settings.js                     ✅ UPDATED (added 250+ lines signature code)
src/routes/api.php                                        ✅ UPDATED (12 signature routes)
src/config/container.php                                  ✅ UPDATED (registered SignatureService)
```

**Implementation Details:**
1. ✅ Migration & Model mit Encryption Support
2. ✅ Repository mit LoggerService Integration (9 methods, full try-catch)
3. ✅ Service Layer mit Validation & Business Logic
4. ✅ Controller mit 12 REST Endpoints (6 user + 6 admin)
5. ✅ Frontend: Signature List UI (reused IMAP account list pattern)
6. ✅ Signature Editor Modal (name, content textarea, default checkbox)
7. ✅ SMTP Status Check (show warning if SMTP not configured)
8. ✅ Set Default Action with badge display
9. ✅ Complete CRUD Operations (Create, Read, Update, Delete)
10. ✅ Console Logging (23+ console statements with [UserSettings] prefix)

**Testing:**
- ✅ Backend tested with tests/manual/signature-test.php (all 12 endpoints working)
- ✅ Frontend integration tested in browser
- ✅ Logs verified in logs/app-2025-11-18.log (JSON formatted with context)
- ✅ All API responses use consistent format: `{success: true, data: [...]}`
9. API Integration
10. Use in Email Composer (Future: Link to Reply)

---

### 📦 BATCH 2: USER SETTINGS - PERSONAL IMAP
**Zeitaufwand:** 30 min  
**Prio:** 2

#### 2.1 📧 PERSONAL IMAP ACCOUNTS UI
**Status:** ✅ COMPLETED  
**Zeitaufwand:** 30 min ✅

**Features:**
- ✅ List Personal IMAP Accounts (with Test/Edit/Delete buttons)
- ✅ Add New Account (modal form with 7 fields)
- ✅ Edit Credentials (load account data into modal)
- ✅ Test Connection (individual + modal test button)
- ✅ Delete Account (confirmation dialog)

**API Endpoints:**
- ✅ `GET /api/user/imap-accounts` (bereits implementiert!)
- ✅ `POST /api/user/imap-accounts` (bereits implementiert!)
- ✅ `PUT /api/user/imap-accounts/{id}` (bereits implementiert!)
- ✅ `DELETE /api/user/imap-accounts/{id}` (bereits implementiert!)
- ✅ `POST /api/user/imap-accounts/{id}/test-connection` (bereits implementiert!)

**Files:**
```
src/public/settings.php                                   ✅ INCLUDED (Personal IMAP tab)
src/public/assets/js/user-settings.js                     ✅ UPDATED (CRUD functions added)
```

**Implementation Steps:**
1. ✅ UI: IMAP Account List (empty state + populated list)
2. ✅ Add Account Form Modal (7 fields: label, email, host, port, username, password, SSL)
3. ✅ Edit Account Form (load data via GET /api/user/imap-accounts/{id})
4. ✅ Test Connection Button (individual account + modal test)
5. ✅ Delete Confirmation (window.confirm dialog)
6. ✅ API Integration (alle Endpoints vorhanden!)
7. ✅ Success Feedback (alert system with 5s auto-hide)

**Note:** Backend + Frontend 100% fertig!

---

### 🎨 HEADER NAVIGATION
**Status:** ✅ COMPLETED
**Zeitaufwand:** 30 min ✅

**Features:**
- ✅ User Dropdown in Header (all pages: inbox.php, settings.php, admin-settings.php)
- ✅ Avatar Button (2-letter initials, no email text - compact design)
- ✅ Dropdown Menu with User Info (avatar, name, email)
- ✅ Navigation Items:
  - Inbox (active on inbox.php)
  - Profile (active on settings.php)
  - Settings (active on admin-settings.php, admin-only)
  - Logout (POST form)
- ✅ Active Page Highlighting (blue background for current page)
- ✅ JavaScript Toggle (click, click-outside, ESC key)
- ✅ Identical styles across all pages
- ✅ Responsive design

**Files:**
```
src/public/inbox.php                                      ✅ UPDATED (header with dropdown)
src/public/settings.php                                   ✅ UPDATED (header with dropdown)
src/public/admin-settings.php                             ✅ CREATED (complete admin page)
src/public/assets/css/6-components/_header.css            ✅ UPDATED (dropdown styles)
```

---

### 📦 BATCH 3: SYSTEM SETTINGS - GLOBAL CONFIG
**Zeitaufwand:** ~4 Stunden  
**Prio:** 1

#### 3.1 📨 GLOBAL IMAP CONFIGURATION
**Status:** ✅ COMPLETED (Backend 100%, Frontend 100%, Autodiscover 100%)  
**Zeitaufwand:** 120 min ✅

**Features:**
- ✅ Setup via Autodiscover-Flow (Integration des existierenden Scripts)
- ✅ Manual Configuration
- ✅ Host, Port, SSL, Username, Password (encrypted)
- ✅ Inbox Folder Selection
- ✅ Test Connection Button
- ✅ Save & Apply

**Database Schema:**
```sql
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(255) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    is_encrypted BOOLEAN DEFAULT FALSE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_setting_key (setting_key)
);

-- Initial Settings:
-- 'imap.host' (string)
-- 'imap.port' (integer)
-- 'imap.ssl' (boolean)
-- 'imap.username' (string)
-- 'imap.password' (string, encrypted)
-- 'imap.inbox_folder' (string, default: 'INBOX')
-- 'smtp.host' (string)
-- 'smtp.port' (integer)
-- 'smtp.ssl' (boolean)
-- 'smtp.auth' (boolean)
-- 'smtp.username' (string)
-- 'smtp.password' (string, encrypted)
-- 'smtp.from_name' (string)
-- 'smtp.from_email' (string)
```

**Migration:** `016_create_system_settings_table.php` ✅ CREATED & EXECUTED

**API Endpoints:**
- ✅ `GET /api/admin/settings/imap` (get IMAP config)
- ✅ `PUT /api/admin/settings/imap` (update IMAP config)
- ✅ `POST /api/admin/settings/imap/test` (test connection)
- ✅ `POST /api/admin/settings/imap/autodiscover` (run autodiscover from email)

**Files:**
```
database/migrations/016_create_system_settings_table.php  ✅ CREATED
database/seed-system-settings.php                         ✅ CREATED (14 settings)
src/app/Models/SystemSetting.php                          ✅ CREATED (58 lines)
src/app/Repositories/SystemSettingRepository.php          ✅ CREATED (168 lines)
src/app/Services/SystemSettingsService.php                ✅ CREATED (287 lines)
src/app/Services/AutoDiscoverService.php                  ✅ CREATED (280 lines)
src/app/Controllers/SystemSettingsController.php          ✅ CREATED (320 lines, 9 endpoints)
src/public/admin-settings.php                             ✅ UPDATED (modal + autodiscover button)
src/public/assets/js/admin-settings.js                    ✅ CREATED (360 lines)
src/config/container.php                                  ✅ UPDATED (services registered)
src/routes/api.php                                        ✅ UPDATED (9 routes added)
tests/manual/test-autodiscover.php                        ✅ CREATED
tests/manual/test-imap-config-flow.php                    ✅ CREATED (5 test scenarios)
```

**UI Status:**
- ✅ Admin page created with header navigation
- ✅ Global IMAP card with dynamic status badge
- ✅ "Configure" button opens modal
- ✅ IMAP configuration modal with all fields
- ✅ "Auto-discover" button with email detection
- ✅ "Test Connection" validates IMAP credentials
- ✅ "Save Configuration" persists encrypted settings
- ✅ Status changes from "Not Configured" to "Configured"
- ✅ Password masking in API responses (********)
- ✅ Success/error alerts with auto-hide

**Implementation Steps:**
1. Migration & Model erstellen
2. Repository mit Encryption Support
3. SystemSettingsService
4. Controller & Routes
5. Frontend: Admin Settings Panel
6. IMAP Configuration Form
7. Autodiscover Button (reuse existing PHP script)
8. Test Connection Logic
9. Save & Encrypt Passwords
10. Success Feedback

---

#### 3.2 📤 SMTP CONFIGURATION
**Status:** ✅ COMPLETED (Backend 100%, Frontend 100%, Autodiscover 100%)  
**Zeitaufwand:** 60 min ✅

**Features:**
- ✅ Host, Port, SSL, Auth
- ✅ Username, Password (encrypted)
- ✅ From Name/Email (global default)
- ✅ Test Connection (send test email)
- ✅ Auto-discover from email address
- ✅ Toggle authentication fields visibility

**API Endpoints:**
- ✅ `GET /api/admin/settings/smtp` (get SMTP config)
- ✅ `PUT /api/admin/settings/smtp` (update SMTP config)
- ✅ `POST /api/admin/settings/smtp/test` (test connection)
- ✅ `POST /api/admin/settings/smtp/autodiscover` (auto-detect from email)

**Files:**
```
src/public/admin-settings.php                             ✅ UPDATED (modal + card)
src/public/assets/js/admin-settings.js                    ✅ UPDATED (260+ lines SMTP code)
tests/manual/test-smtp-autodiscover.php                   ✅ CREATED
```

**Implementation Steps:**
1. ✅ SMTP Configuration Modal (9 fields: host, port, SSL, auth toggle, username, password, from_name, from_email)
2. ✅ SMTP Card with dynamic status badge
3. ✅ Test Connection Button (validates credentials)
4. ✅ Auto-discover functionality (detects smtp.gmail.com, etc.)
5. ✅ API Integration (all 4 endpoints)
6. ✅ Password encryption (via SystemSettingRepository)
7. ✅ Auth fields toggle (show/hide based on auth checkbox)
8. ✅ Success Feedback (alerts with auto-hide)

---

#### 3.3 ⏰ CRON MONITOR
**Status:** ✅ COMPLETED  
**Zeitaufwand:** 90 min ✅  
**Completed:** 2025-11-18

**Features:**
- ✅ Real-time Status Badge (Green 🟢 / Yellow 🟡 / Red 🔴)
  - 🟢 Green: ≥10 triggers in last hour
  - 🟡 Yellow: 1-9 triggers in last hour
  - 🔴 Red: 0 triggers in last hour
- ✅ Last Execution Display (relative time + duration)
- ✅ Execution Count (last hour + total)
- ✅ Execution History Modal (last 20 runs with table)
- ✅ Auto-refresh (every 60 seconds)
- ✅ Integration with Webhook Poll Endpoint

**Database Schema:**
```sql
CREATE TABLE cron_executions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    execution_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    accounts_polled INT DEFAULT 0,
    new_emails_found INT DEFAULT 0,
    duration_ms INT DEFAULT 0,
    status ENUM('success', 'error') DEFAULT 'success',
    error_message TEXT,
    
    INDEX idx_execution_timestamp (execution_timestamp),
    INDEX idx_status (status)
);
```

**Migration:** `017_create_cron_executions_table.php` ✅

**API Endpoints:**
- ✅ `GET /api/admin/cron/status` (current status + last execution + totals)
- ✅ `GET /api/admin/cron/history?limit=N` (recent executions, default 20)
- ✅ `GET /api/admin/cron/statistics` (averages + success rate + total emails)

**Files:**
```
database/migrations/017_create_cron_executions_table.php  ✅ CREATED
database/run_017.php                                       ✅ CREATED (bootstrap wrapper)
src/app/Models/CronExecution.php                          ✅ CREATED (66 lines, 3 helper methods)
src/app/Services/CronMonitorService.php                   ✅ CREATED (213 lines, 4 methods)
src/app/Controllers/CronMonitorController.php             ✅ CREATED (115 lines, 3 REST endpoints)
src/app/Controllers/WebhookController.php                 ✅ UPDATED (execution logging)
src/modules/webcron/src/WebcronManager.php                ✅ UPDATED (simplified auto-archiving)
src/config/container.php                                  ✅ UPDATED (services + WebcronManager concrete class)
src/routes/api.php                                        ✅ UPDATED (3 routes added)
src/public/admin-settings.php                             ✅ UPDATED (dynamic card + history modal)
src/public/assets/js/admin-settings.js                    ✅ UPDATED (+140 lines: loadCronStatus, viewHistory, auto-refresh)
tests/manual/test-cron-monitor.php                        ✅ CREATED (basic API test)
tests/manual/test-cron-monitor-e2e.php                    ✅ CREATED (E2E workflow test)
```

**Implementation Steps:**
1. ✅ Migration erstellen & ausführen (cron_executions table)
2. ✅ CronExecution Model mit Helper Methods (isSuccessful, getFormattedDuration, getRelativeTime)
3. ✅ CronMonitorService (getStatus, getHistory, logExecution, getStatistics)
4. ✅ CronMonitorController (3 REST endpoints with error handling)
5. ✅ Routes & DI Container Registration
6. ✅ Update WebhookController to log executions (success + error cases)
7. ✅ Fix WebcronManager auto-archiving bug (simplified to skip)
8. ✅ Frontend: Dynamic Cron Status Card (badge colors, last execution, execution count)
9. ✅ Frontend: Cron History Modal (Bootstrap modal with table)
10. ✅ JavaScript Functions (loadCronStatus, viewCronHistory, startCronAutoRefresh)
11. ✅ E2E Testing (webhook → execution logged → status updated → history visible)

**Test Results:**
- ✅ API Status: HTTP 200, status calculation correct (red/yellow/green based on frequency)
- ✅ API History: HTTP 200, returns recent executions with relative timestamps
- ✅ API Statistics: HTTP 200, returns aggregated stats (avg duration, success rate, total emails)
- ✅ Webhook Integration: POST /webhooks/poll-emails logs execution automatically (1 account, 0 emails, 4.36s, success)
- ✅ Status Transition: Execution count increased from 1 to 2, status remained yellow (expected behavior)

---

### 📦 BATCH 4: SYSTEM SETTINGS - MONITORING
**Zeitaufwand:** ~2 Stunden  
**Prio:** 2

#### 4.1 📊 LOG MONITOR
**Status:** ⏳ TODO  
**Zeitaufwand:** 120 min

**Features:**
- Live Log Viewer (tail -f style)
- Filter by Level (DEBUG, INFO, ERROR, etc.)
- Filter by Channel
- Search in Logs
- Download Logs
- Auto-scroll
- Pause/Resume

**API Endpoints:**
- ⏳ `GET /api/admin/logs` (paginated, filterable)
- ⏳ `GET /api/admin/logs/tail` (SSE - Server-Sent Events)
- ⏳ `GET /api/admin/logs/download` (download as file)

**Files:**
```
src/app/Controllers/LogMonitorController.php              [NEW]
src/app/Services/LogReaderService.php                     [NEW]
src/public/assets/js/log-monitor.js                       [NEW]
src/public/assets/css/6-components/_log-viewer.css       [NEW]
```

**Implementation Steps:**
1. LogReaderService (read JSON logs)
2. Controller with SSE support
3. Frontend: Log Viewer Component
4. Filter UI (Level, Channel, Search)
5. Auto-scroll Logic
6. Pause/Resume Button
7. Download Button
8. SSE Integration (live updates)

---

#### 4.2 📦 BACKUP & RESTORE (Basic)
**Status:** ⏳ TODO  
**Zeitaufwand:** 90 min

**Features:**
- **Manual Backup:**
  - Create Backup Button
  - Download ZIP (DB + Uploads + Config)
- **Backup Info:**
  - Last Backup Timestamp
  - Backup Size
  - Backup Contents List

**API Endpoints:**
- ⏳ `POST /api/admin/backup/create` (create backup, return download URL)
- ⏳ `GET /api/admin/backup/download/{filename}` (download backup file)
- ⏳ `GET /api/admin/backup/list` (list available backups)
- ⏳ `DELETE /api/admin/backup/{filename}` (delete old backup)

**Files:**
```
src/app/Services/BackupService.php                        [NEW]
src/app/Controllers/BackupController.php                  [NEW]
src/public/assets/js/backup-manager.js                    [NEW]
src/public/assets/css/6-components/_backup-panel.css     [NEW]
data/backups/                                             [NEW DIR]
```

**Implementation Steps:**
1. BackupService (create ZIP)
2. Controller & Routes
3. Frontend: Backup Panel
4. Create Backup Button
5. Progress Indicator
6. Download Link
7. Backup List Table
8. Delete Old Backups

**Backup Contents:**
```
backup-YYYY-MM-DD-HHmmss.zip
├── database.sql           (mysqldump or sqlite copy)
├── uploads/               (all uploaded files)
├── .env.backup            (config backup)
└── manifest.json          (backup metadata)
```

---

### 📦 BATCH 5: SYSTEM SETTINGS - DANGER ZONE
**Zeitaufwand:** ~2 Stunden  
**Prio:** 3

#### 5.1 🔴 DANGER ZONE
**Status:** ⏳ TODO  
**Zeitaufwand:** 120 min

**Features:**
- **Confirmation Required:** User muss `I KNOW THIS IS DANGEROUS` eintippen
- **Actions:**
  1. ⚙️ Re-run Setup Wizard (relaunch autodiscover)
  2. 🗑️ Wipe Database (delete all threads/emails, keep users)
  3. 📧 Wipe IMAP (delete all messages from server)
  4. 🔧 Change Database Connection (update credentials)
  5. 🔄 Factory Reset (complete reinstallation, keeps .env)

**API Endpoints:**
- ⏳ `POST /api/admin/danger/rerun-setup` (start setup wizard)
- ⏳ `POST /api/admin/danger/wipe-database` (confirm: phrase)
- ⏳ `POST /api/admin/danger/wipe-imap` (confirm: phrase)
- ⏳ `POST /api/admin/danger/change-db-connection` (new credentials)
- ⏳ `POST /api/admin/danger/factory-reset` (confirm: phrase)

**Files:**
```
src/app/Services/DangerZoneService.php                    [NEW]
src/app/Controllers/DangerZoneController.php              [NEW]
src/public/assets/js/danger-zone.js                       [NEW]
src/public/assets/css/6-components/_danger-zone.css      [NEW]
```

**Implementation Steps:**
1. DangerZoneService (implement dangerous operations)
2. Controller with phrase validation
3. Frontend: Danger Zone Panel (Red Theme)
4. Confirmation Modal with Input
5. Phrase Validation
6. Progress Indicators
7. Success/Error Feedback
8. Auto-logout after Factory Reset

**Security:**
- All actions require admin role
- All actions require confirmation phrase
- All actions are logged
- Email notification to admin after execution

---

## 🎯 PHASE 2: ADVANCED FEATURES

### 📦 BATCH 6: USER MANAGEMENT (CRUD)
**Zeitaufwand:** ~2 Stunden  
**Prio:** 1

#### 6.1 👥 USER MANAGEMENT UI
**Status:** ⏳ TODO  
**Zeitaufwand:** 120 min

**Features:**
- List All Users
- Create New User
- Edit User (Name, Email, Role)
- Delete User (with confirmation)
- Assign Roles (Admin, User)
- Reset User Password (Admin)

**API Endpoints:**
- ✅ `GET /api/users` (bereits vorhanden!)
- ✅ `GET /api/users/{id}` (bereits vorhanden!)
- ✅ `POST /api/users` (bereits vorhanden!)
- ✅ `PUT /api/users/{id}` (bereits vorhanden!)
- ✅ `DELETE /api/users/{id}` (bereits vorhanden!)
- ✅ `POST /api/users/{id}/password` (bereits vorhanden!)

**Files:**
```
src/public/admin-users.php                                [NEW]
src/public/assets/js/user-management.js                   [NEW]
src/public/assets/css/6-components/_user-table.css       [NEW]
```

**Implementation Steps:**
1. User List Table UI
2. Create User Modal
3. Edit User Modal
4. Delete Confirmation
5. Password Reset Form (Admin)
6. Role Assignment Dropdown
7. API Integration (alle Endpoints vorhanden!)
8. Success Feedback

**Note:** Backend bereits 100% fertig! Nur Frontend fehlt.

---

### 📦 BATCH 7: ADVANCED BACKUP
**Zeitaufwand:** ~3 Stunden  
**Prio:** 2

#### 7.1 📦 BACKUP RESTORE
**Status:** ⏳ TODO  
**Zeitaufwand:** 120 min

**Features:**
- Upload Backup ZIP
- Validate Backup Structure
- Preview Restore (what will be restored)
- Confirm Restore (phrase: `I UNDERSTAND THE RISKS`)
- Restore Process:
  1. Stop Services
  2. Backup Current State (safety)
  3. Restore Database
  4. Restore Uploads
  5. Restore Config
  6. Restart Services
- Progress Indicator
- Success/Error Feedback

**API Endpoints:**
- ⏳ `POST /api/admin/backup/upload` (upload backup file)
- ⏳ `POST /api/admin/backup/validate` (validate structure)
- ⏳ `POST /api/admin/backup/restore` (confirm: phrase)

**Files:**
```
src/app/Services/BackupService.php                        [UPDATE]
src/public/assets/js/backup-manager.js                    [UPDATE]
```

**Implementation Steps:**
1. Upload Handler (large files)
2. Validation Logic
3. Preview Component
4. Restore Service
5. Frontend: Upload Form
6. Confirmation Modal
7. Progress Bar
8. Error Handling

---

#### 7.2 🕐 SCHEDULED BACKUPS
**Status:** ⏳ TODO  
**Zeitaufwand:** 60 min

**Features:**
- Enable/Disable Auto-Backup
- Schedule Selection (Daily, Weekly)
- Retention Policy (keep last X backups)
- Email Notification on Completion

**Database Schema:**
```sql
ALTER TABLE system_settings ADD:
- 'backup.enabled' (boolean)
- 'backup.schedule' (string: 'daily', 'weekly')
- 'backup.retention_days' (integer, default: 30)
- 'backup.notify_email' (string)
```

**API Endpoints:**
- ⏳ `GET /api/admin/backup/schedule`
- ⏳ `PUT /api/admin/backup/schedule`

**Files:**
```
src/app/Services/ScheduledBackupService.php               [NEW]
scripts/backup-cron.php                                   [NEW]
```

**Implementation Steps:**
1. Scheduled Backup Service
2. Cron Script Integration
3. Retention Policy Logic
4. Email Notification
5. Frontend: Schedule Config UI
6. API Integration

---

### 📦 BATCH 8: ADVANCED SETTINGS
**Zeitaufwand:** ~2 Stunden  
**Prio:** 3

#### 8.1 ⚙️ SYSTEM DEFAULTS
**Status:** ⏳ TODO  
**Zeitaufwand:** 60 min

**Features:**
- Default Labels (for new threads)
- Thread Assignment Rules
- Email Retention Policy (days)
- Max Attachment Size (MB)
- Session Timeout (minutes)

**Database Settings:**
```
'defaults.thread_labels' (json array of label IDs)
'defaults.retention_days' (integer, default: 365)
'defaults.max_attachment_mb' (integer, default: 25)
'defaults.session_timeout' (integer, default: 60)
```

**API Endpoints:**
- ⏳ `GET /api/admin/settings/defaults`
- ⏳ `PUT /api/admin/settings/defaults`

**Files:**
```
src/public/assets/js/admin-settings.js                    [UPDATE]
```

**Implementation Steps:**
1. Settings Form UI
2. Validation Logic
3. API Integration
4. Apply Defaults in Thread Creation

---

#### 8.2 🔗 WEBHOOK MANAGEMENT
**Status:** ⏳ TODO  
**Zeitaufwand:** 60 min

**Features:**
- Webhook Token Display
- Regenerate Token Button
- Webhook URL Display (Copy Button)
- Test Webhook Trigger
- Webhook Logs (last 20 calls)

**API Endpoints:**
- ✅ `POST /webhooks/poll-emails` (bereits vorhanden!)
- ⏳ `POST /api/admin/webhook/regenerate-token`
- ⏳ `GET /api/admin/webhook/logs`

**Files:**
```
src/app/Controllers/WebhookController.php                 [UPDATE]
src/public/assets/js/webhook-management.js                [NEW]
```

**Implementation Steps:**
1. Token Regeneration Logic
2. Webhook Logs Storage
3. Frontend: Webhook Panel
4. Display URL + Copy Button
5. Test Trigger Button
6. Logs Table

---

## 📊 PROGRESS TRACKER

### Phase 1 (MVP)
**Gesamt Features:** 10  
**Implementiert:** 6 (1.1 Profile, 1.2 Password, 1.3 Signatures, 2.1 Personal IMAP, 2.3 Header, 3.1 IMAP, 3.2 SMTP)  
**In Arbeit:** 0  
**TODO:** 4  
**Progress:** ██████░░░░ 60%

**Completed:**
- ✅ Batch 1.1: User Profile Settings (Backend + Frontend)
- ✅ Batch 1.2: Password Change (Frontend)
- ✅ Batch 1.3: Email Signature Editor (Backend + Frontend, 12 API endpoints)
- ✅ Batch 2.1: Personal IMAP Accounts UI (Frontend)
- ✅ Batch 2.3: Header Navigation (all 3 pages: inbox, settings, admin-settings)
- ✅ Batch 3.1: Global IMAP Configuration (Backend + Frontend + Autodiscover)
- ✅ Batch 3.2: Global SMTP Configuration (Backend + Frontend + Autodiscover)

**Next Recommended:**
- 🎯 Batch 3.3: Cron Monitor (monitoring feature)
- 🎯 Batch 4.1: Log Monitor (system monitoring)
- 🎯 Batch 4.2: Backup System (manual backup)

### Phase 2 (Advanced)
**Gesamt Features:** 5  
**Implementiert:** 0  
**In Arbeit:** 0  
**TODO:** 5  
**Progress:** ░░░░░░░░░░ 0%

---

## 🗂️ DATABASE MIGRATIONS OVERVIEW

```
012_add_archived_status.php                [UI Agent]
013_add_user_settings_fields.php           [Phase 1.1]
014_create_signatures_table.php            [Phase 1.3]
015_create_system_settings_table.php       [Phase 3.1]
016_create_cron_executions_table.php       [Phase 3.3]
```

---

## 🔧 TECHNICAL NOTES

### Authentication & Authorization

**Current State:**
- ❌ No authentication middleware yet
- ⚠️ User ID hardcoded (user_id = 1)

**Required for Settings:**
- ✅ JWT Middleware (identify current user)
- ✅ Admin Role Check (for system settings)
- ✅ User-specific data filtering

**Implementation Priority:**
- Before Phase 1.1 (User Settings)
- Required for all `/api/user/*` endpoints
- Required for all `/api/admin/*` endpoints

---

### File Upload Handling

**Avatar Upload:**
- Max size: 2MB
- Allowed formats: JPG, PNG, GIF
- Storage: `data/uploads/avatars/{user_id}/{filename}`
- Resize: 200x200px thumbnail

**Signature Images:**
- Max size: 1MB
- Allowed formats: JPG, PNG, GIF
- Storage: `data/uploads/signatures/{user_id}/{filename}`
- Inline embedding in signature HTML

**Backup Files:**
- Storage: `data/backups/backup-{timestamp}.zip`
- Retention: Keep last 10 backups (configurable)
- Cleanup: Delete older backups automatically

---

### Security Considerations

**Password Encryption:**
- All IMAP/SMTP passwords stored encrypted (AES-256-CBC)
- Encryption key in `.env` (ENCRYPTION_KEY)
- Never log decrypted passwords

**Admin Actions:**
- All danger zone actions require `I KNOW THIS IS DANGEROUS` phrase
- All actions logged to audit log
- Email notification to admin
- Rate limiting (max 3 attempts per hour)

**File Uploads:**
- Validate file types (MIME check)
- Scan for malicious content (optional: ClamAV)
- Generate random filenames (prevent overwrite)
- Store outside webroot (security)

---

### Code Reuse Opportunities

**Settings Components:**
- ✅ Generic Settings Form Component (reusable)
- ✅ Confirmation Modal (delete, danger actions)
- ✅ File Upload Component (avatar, signature images, backups)
- ✅ Status Indicator (cron monitor, test connection)
- ✅ Copy-to-Clipboard Button (webhook URL, tokens)

**Services:**
- ✅ EncryptionService (already exists - passwords)
- ✅ LoggerService (already exists - audit logs)
- ✅ BackupService (new - for backups)
- ✅ SystemSettingsService (new - key-value store)

---

## 🚀 QUICK START

**Für nächste Feature-Implementierung:**

### User sagt:
> "Implementiere Email Signature Editor" (Batch 1.3)

### Agent arbeitet ab:
1. ✅ Settings-Dokument öffnen
2. ✅ Batch 1.3 finden
3. ✅ Database Schema prüfen (signatures table)
4. ✅ Migration 014 erstellen
5. ✅ Model/Repository/Service implementieren
6. ✅ Controller & Routes registrieren (6 endpoints)
7. ✅ Frontend UI erstellen (signature list + editor)
8. ✅ API Integration (CRUD operations)
9. ✅ Testing
10. ✅ Feature als implementiert markieren

---

## 🎯 RECOMMENDED NEXT STEPS

**Option 1: Batch 1.3 - Email Signature Editor**  
**Grund:** User-facing feature, keine Backend-Dependencies  
**Zeitaufwand:** 90 Minuten  
**Command:**
```
Implementiere Batch 1.3: Email Signature Editor
```

**Option 2: Batch 3.1 - Global IMAP Configuration**  
**Grund:** Admin feature, UI bereits vorhanden (admin-settings.php)  
**Zeitaufwand:** 120 Minuten  
**Command:**
```
Implementiere Batch 3.1: Global IMAP Configuration
```

**Option 3: Batch 3.3 - Cron Monitor**  
**Grund:** Monitoring feature, wichtig für Systemgesundheit  
**Zeitaufwand:** 90 Minuten  
**Command:**
```
Implementiere Batch 3.3: Cron Monitor
```

---

## 📊 CURRENT STATUS SUMMARY

**Phase 1 Progress:** 40% (4 von 10 Features)

**Completed Today (2025-11-18):**
- ✅ User Profile Settings (Avatar Upload, Timezone, Language)
- ✅ Password Change (Client-side validation, API integration)
- ✅ Personal IMAP UI (Full CRUD, Test Connection)
- ✅ Email Signature Editor (Personal + Global, SMTP Status Check, Full CRUD)
- ✅ Header Navigation (3 pages, dropdown, active highlighting)
- ✅ Admin Settings Page (UI shell mit 4 cards)
- ✅ Comprehensive Logging (Console + Server logs, all layers)

**Ready to Test:**
- 🧪 settings.php → http://ci-inbox.local/settings.php (4 tabs: Profile, IMAP, Signatures, Security)
- 🧪 admin-settings.php → http://ci-inbox.local/admin-settings.php
- 🧪 User Profile API → GET /api/user/profile
- 🧪 Personal IMAP API → All 6 endpoints functional
- 🧪 Signatures API → All 12 endpoints functional (6 user + 6 admin)

**Next Priority:** Global IMAP Config oder Notifications Settings

### 2025-11-18 (Implementation Day)
- ✅ Settings Architecture entworfen
- ✅ Phase 1 (MVP) definiert
- ✅ Phase 2 (Advanced) definiert
- ✅ Database Schema geplant
- ✅ API Endpoints definiert
- ✅ Bestehende APIs identifiziert (User CRUD, Personal IMAP)
- ✅ Backup Strategy entworfen
- ✅ Security Considerations dokumentiert
- ✅ **sabre/dav 4.7.0 installiert** (WebDAV/Nextcloud backup support)
- ✅ **Migration 013 erstellt und ausgeführt** (user settings fields)
- ✅ **UserProfileService implementiert** (255 lines, avatar upload, password change)
- ✅ **UserProfileController implementiert** (280 lines, 5 REST endpoints)
- ✅ **ConfigService .env Bug gefixt** (custom parser for variables_order='GPCS')
- ✅ **settings.php erstellt** (600+ lines, 3 tabs: Profile, Personal IMAP, Security)
- ✅ **user-settings.js erstellt** (390 lines, full CRUD for Personal IMAP)
- ✅ **Header Dropdown implementiert** (all 3 pages: inbox, settings, admin-settings)
- ✅ **admin-settings.php erstellt** (UI shell with 4 cards, admin-only access)
- ✅ **Batch 1.1 COMPLETED** (User Profile Settings - Backend + Frontend 100%)
- ✅ **Batch 1.2 COMPLETED** (Password Change - Frontend 100%)
- ✅ **Batch 2.1 COMPLETED** (Personal IMAP UI - Frontend 100%)
- ✅ **Header Navigation COMPLETED** (compact avatar button, active page highlighting)

---

## 🎯 RECOMMENDED START

**Empfohlen:** Batch 1.1 - User Profile Settings  
**Grund:** Basis für alle weiteren User-Settings  
**Dependencies:** JWT Middleware (Authentication)  
**Zeitaufwand:** 90 Minuten

**Command:**
```
Implementiere Batch 1.1: User Profile Settings (mit Avatar Upload)
```

---

## 💡 ADDITIONAL IDEAS (Future Consideration)

### User Settings:
- 🌓 Dark Mode Toggle
- 📧 Email Notification Preferences (per label, per thread)
- 🔔 Browser Notification Settings
- ⌨️ Keyboard Shortcuts Customization
- 📊 Dashboard Widgets Configuration

### System Settings:
- 📈 Analytics Dashboard (email volume, response times)
- 🔍 Search Index Configuration (rebuild, optimize)
- 🔒 Two-Factor Authentication (2FA) Setup
- 📧 Email Templates Management (for auto-replies)
- 🌐 Multi-Language Support Configuration
- 🎨 Theming/Branding (Logo, Colors, Custom CSS)

### Backup & Maintenance:
- ☁️ External Backup Storage (WebDAV/Nextcloud, FTP)
- 🔄 Auto-Migration Tool (for DB schema updates)
- 🧹 Cleanup Jobs (old logs, orphaned files, soft-deleted items)
- 📊 System Health Dashboard (disk space, DB size, memory usage)

---

## 🔗 EXTERNAL BACKUP INTEGRATION

### WebDAV/Nextcloud Support

**Package:** `sabre/dav` (^4.6)

**Features:**
- Upload backups to Nextcloud/ownCloud
- Compatible with WebDAV protocol
- Auth: Basic, Digest, Bearer Token
- Automatic folder creation
- Resume uploads (chunked transfer)

**Configuration (system_settings):**
```
'backup.webdav.enabled' (boolean)
'backup.webdav.url' (string, e.g., https://cloud.example.com/remote.php/dav/files/username/CI-IMAP-Backups/)
'backup.webdav.username' (string)
'backup.webdav.password' (string, encrypted)
'backup.webdav.auth_type' (string: 'basic', 'digest', 'bearer')
```

**Implementation:**
```php
// src/app/Services/BackupService.php
public function uploadToWebDAV(string $backupFile): bool
{
    $settings = [
        'baseUri' => $this->systemSettings->get('backup.webdav.url'),
        'userName' => $this->systemSettings->get('backup.webdav.username'),
        'password' => $this->encryption->decrypt(
            $this->systemSettings->get('backup.webdav.password')
        ),
    ];
    
    $client = new \Sabre\DAV\Client($settings);
    
    $remoteFile = basename($backupFile);
    $client->request('PUT', $remoteFile, fopen($backupFile, 'r'));
    
    return true;
}
```

**UI:**
- Enable/Disable WebDAV Backup
- WebDAV URL Input
- Test Connection Button
- Auth Type Selection
- Credentials (encrypted storage)
- Upload on Backup Creation (checkbox)

---

## 🛠️ DATABASE MAINTENANCE & INTEGRITY

### Automated Integrity Checks

**Status:** ✅ IMPLEMENTED (2025-11-19)  
**Integration:** Webcron (every 10th execution)

**Features Implemented:**
- ✅ Orphaned Threads Detection (message_count > 0 but no emails)
- ✅ Incorrect message_count Detection
- ✅ Emails with Invalid thread_id Detection
- ✅ Duplicate message_ids Detection
- ✅ Automatic Logging on Issues Found

**Files Created:**
```
database/check-integrity.php          (Manual CLI check - 5 tests)
database/cleanup-orphaned-threads.php (Archive/Delete/Fix modes)
database/fix-message-counts.php       (Auto-correct message_counts)
database/restore-test-emails.php      (Development helper)
```

**Webcron Integration:**
```php
// src/modules/webcron/src/WebcronManager.php
private function runIntegrityCheck(): array
{
    // Runs every 10th polling execution
    // Checks: orphaned threads, wrong counts, orphaned emails, duplicates
    // Logs warnings if issues found
}
```

**Manual Commands:**
```bash
# Check database integrity
php database/check-integrity.php

# Cleanup orphaned threads (archive mode, safe)
php database/cleanup-orphaned-threads.php --archive --dry-run
php database/cleanup-orphaned-threads.php --archive

# Fix message_counts
php database/fix-message-counts.php --dry-run
php database/fix-message-counts.php
```

---

### 🎯 FUTURE ENHANCEMENT: Integrity Settings UI

**Planned for Phase 2 (Advanced Settings):**

#### Admin Settings → Database Maintenance

**Configuration Options:**
```
'integrity.check_enabled' (boolean, default: true)
'integrity.check_interval' (integer, default: 10)  // Every N webcron executions
'integrity.auto_fix' (boolean, default: false)     // Auto-run cleanup
'integrity.fix_mode' (enum: 'archive', 'fix-count', 'delete', default: 'archive')
'integrity.notify_on_issues' (boolean, default: true)
'integrity.notify_email' (string, admin email)
```

**UI Features:**
- Enable/Disable Automatic Checks
- Check Interval Slider (1-50 executions)
- Auto-Fix Mode Selection:
  - ⚠️ Archive orphaned threads (safe, non-destructive)
  - 🔧 Fix message_count only (safe)
  - 🗑️ Delete orphaned threads (destructive, requires confirmation)
- Email Notification Toggle
- Manual Trigger Button ("Run Integrity Check Now")
- Last Check Display (timestamp + status badge)
- Issues Found Display (list with details)
- Quick Actions:
  - "View Full Report" (opens modal with detailed results)
  - "Run Cleanup" (opens cleanup modal with mode selection)
  - "Download Report" (export as JSON/CSV)

**Database Settings (system_settings table):**
```sql
INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('integrity.check_enabled', 'true', 'boolean', 'Enable automatic integrity checks'),
('integrity.check_interval', '10', 'integer', 'Check every N webcron executions'),
('integrity.auto_fix', 'false', 'boolean', 'Automatically fix issues'),
('integrity.fix_mode', 'archive', 'string', 'archive|fix-count|delete'),
('integrity.notify_on_issues', 'true', 'boolean', 'Send email when issues found'),
('integrity.notify_email', '', 'string', 'Admin email for notifications');
```

**API Endpoints (Future):**
```
GET  /api/admin/integrity/settings       (get current config)
PUT  /api/admin/integrity/settings       (update config)
POST /api/admin/integrity/run            (manual trigger)
GET  /api/admin/integrity/status         (last check status)
GET  /api/admin/integrity/history        (recent checks)
POST /api/admin/integrity/cleanup        (run cleanup with mode)
```

**Implementation Priority:** Batch 4.3 (Phase 2)  
**Estimated Time:** 90 minutes

---

**Status:** 📋 Ready for Implementation  
**Next Action:** Install sabre/dav → Start with JWT Middleware → Batch 1.1

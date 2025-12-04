# Admin Settings - Tab Structure & Implementation Plan

**Status:** Planning Phase  
**Created:** 2025-11-20  
**Purpose:** Define structure for modular admin settings with tab-based navigation

---

## Architecture Overview

### File Structure
```
src/
├── public/
│   └── admin-settings.php          # Main container with header, tabs navigation
└── views/
    └── admin/
        └── tabs/
            ├── overview.php         # ✅ Dashboard with all cards
            ├── imap.php             # 🔄 IMAP configuration
            ├── smtp.php             # 🔄 SMTP configuration
            ├── cron.php             # 🔄 Cron monitoring & history
            ├── backup.php           # 🔄 Backup management
            ├── database.php         # 🔄 Database info & maintenance
            ├── users.php            # ✅ User management (already exists)
            └── signatures.php       # ✅ Email signatures (already exists)
```

### API Routes Structure
```
src/routes/
├── api.php                          # Main API routes file
└── admin/                           # Admin-specific API modules
    ├── imap-routes.php              # IMAP config API
    ├── smtp-routes.php              # SMTP config API
    ├── backup-routes.php            # ✅ Already exists
    ├── cron-routes.php              # ✅ Already exists
    ├── database-routes.php          # Database tools API
    ├── user-routes.php              # ✅ Already exists
    └── signature-routes.php         # ✅ Already exists
```

---

## Tab Specifications

### 1. Overview Tab ✅ COMPLETED
**File:** `src/views/admin/tabs/overview.php`  
**Purpose:** Dashboard showing status of all system components

**Features:**
- ✅ Clickable cards linking to detailed tabs
- ✅ Quick status indicators for each component
- ✅ Live metrics (backup count, cron status)
- ✅ Consistent card design with icons

**API Dependencies:**
- ✅ `/api/admin/backup/list` - Backup count
- ✅ `/api/system/cron-status` - Cron health

---

### 2. IMAP Configuration Tab
**File:** `src/views/admin/tabs/imap.php`  
**Status:** 🔄 To Implement  
**Priority:** HIGH

**Purpose:** Configure global IMAP settings and autodiscover

**UI Components:**
- Configuration form with fields:
  - Host (text input)
  - Port (number input, default: 993)
  - Encryption (dropdown: SSL/TLS, STARTTLS, None)
  - Username (text input)
  - Password (password input, encrypted storage)
  - Test connection button
- Autodiscover configuration:
  - Enable/disable toggle
  - Domain whitelist (comma-separated)
  - Autodiscover priority (global vs per-user)
- Connection test results display
- Save configuration button

**API Endpoints to Create:**
```
GET  /api/admin/settings/imap        # Get current IMAP config
POST /api/admin/settings/imap        # Save IMAP config
POST /api/admin/settings/imap/test   # Test IMAP connection
GET  /api/admin/settings/imap/accounts  # List configured accounts
```

**Service Layer:**
- `SystemSettingsService::getImapConfig()`
- `SystemSettingsService::saveImapConfig($data)`
- `SystemSettingsService::testImapConnection($config)`

**Database:**
- Use existing `system_settings` table
- Keys: `imap_host`, `imap_port`, `imap_encryption`, `imap_username`, `imap_password_encrypted`, `imap_autodiscover_enabled`

**Security:**
- Encrypt password with `EncryptionService`
- Validate host format (no URLs, only hostnames/IPs)
- Test connection before saving
- Log all configuration changes

---

### 3. SMTP Configuration Tab
**File:** `src/views/admin/tabs/smtp.php`  
**Status:** 🔄 To Implement  
**Priority:** HIGH

**Purpose:** Configure global SMTP settings for outgoing emails

**UI Components:**
- Configuration form with fields:
  - Host (text input)
  - Port (number input, default: 587)
  - Encryption (dropdown: STARTTLS, SSL/TLS, None)
  - Username (text input)
  - Password (password input, encrypted storage)
  - From email (email input)
  - From name (text input)
  - Test email button (sends test to admin)
- Autodiscover configuration:
  - Enable/disable toggle
  - Use same credentials as IMAP (checkbox)
- Connection test results display
- Save configuration button

**API Endpoints to Create:**
```
GET  /api/admin/settings/smtp        # Get current SMTP config
POST /api/admin/settings/smtp        # Save SMTP config
POST /api/admin/settings/smtp/test   # Send test email
```

**Service Layer:**
- `SystemSettingsService::getSmtpConfig()`
- `SystemSettingsService::saveSmtpConfig($data)`
- `SystemSettingsService::sendTestEmail($to)`

**Database:**
- Use existing `system_settings` table
- Keys: `smtp_host`, `smtp_port`, `smtp_encryption`, `smtp_username`, `smtp_password_encrypted`, `smtp_from_email`, `smtp_from_name`, `smtp_autodiscover_enabled`

**Security:**
- Encrypt password with `EncryptionService`
- Validate email format (from email)
- Test connection before saving
- Log all configuration changes

---

### 4. Cron Monitor Tab
**File:** `src/views/admin/tabs/cron.php`  
**Status:** 🔄 To Implement  
**Priority:** MEDIUM

**Purpose:** Monitor webhook polling service and execution history

**UI Components:**
- Status overview card:
  - Current status (healthy/warning/error)
  - Last execution timestamp
  - Next expected execution (if configured)
  - Success rate (last 50 runs)
  - Emails processed today/this week
- Execution history table:
  - Columns: Timestamp, Duration, Emails Processed, Status, Error Message
  - Pagination (20 per page)
  - Filter by status (success/error)
  - Search by date range
- Performance chart:
  - Line chart: Executions per hour (last 24h)
  - Bar chart: Emails processed per day (last 7 days)
- Manual trigger button (for testing)
- Clear old executions button (retention settings)

**API Endpoints:** ✅ Already exist
```
GET /api/admin/cron/status           # ✅ Current status
GET /api/admin/cron/history          # ✅ Execution history with pagination
GET /api/admin/cron/statistics       # ✅ Performance stats
```

**Additional Features:**
- Export execution log to CSV
- Email alerts configuration (on failure)
- Webhook URL display with token masked

---

### 5. Backup Management Tab
**File:** `src/views/admin/tabs/backup.php`  
**Status:** 🔄 To Implement  
**Priority:** HIGH

**Purpose:** Manage database backups and configure automated schedules

**UI Components:**
- Quick actions card:
  - Create backup now button (large, prominent)
  - Restore from backup button (with file upload)
  - Configure schedule button (opens modal)
- Backup list table:
  - Columns: Filename, Size (MB), Created, Compression Ratio, Actions
  - Actions: Download, Delete, Restore
  - Sortable columns
  - Search/filter by date
- Schedule configuration modal:
  - Frequency (daily/weekly/manual)
  - Time of day (time picker)
  - Retention days (number input, default 30)
  - Remote sync configuration:
    - Sync target (WebDAV, FTP, S3)
    - Connection settings
    - Test connection button
- Disk usage indicator:
  - Backup directory size
  - Available disk space warning

**API Endpoints:** ✅ Partially exist
```
POST   /api/admin/backup/create      # ✅ Create backup
GET    /api/admin/backup/list        # ✅ List backups
GET    /api/admin/backup/download/{filename}  # ✅ Download backup
DELETE /api/admin/backup/delete/{filename}    # ✅ Delete backup
POST   /api/admin/backup/cleanup     # ✅ Cleanup old backups

# To implement:
POST   /api/admin/backup/restore     # Restore from backup
GET    /api/admin/backup/schedule    # Get schedule config
POST   /api/admin/backup/schedule    # Save schedule config
POST   /api/admin/backup/remote/test # Test remote connection
```

**Service Layer Extensions:**
- `BackupService::restoreBackup($filename)` - NEW
- `BackupService::getScheduleConfig()` - NEW
- `BackupService::saveScheduleConfig($data)` - NEW
- `BackupService::testRemoteConnection($config)` - NEW

**Database:**
- Add to `system_settings` table:
  - `backup_schedule_enabled`
  - `backup_schedule_frequency`
  - `backup_schedule_time`
  - `backup_retention_days`
  - `backup_remote_enabled`
  - `backup_remote_type` (webdav/ftp/s3)
  - `backup_remote_config` (JSON)

---

### 6. Database Management Tab
**File:** `src/views/admin/tabs/database.php`  
**Status:** 🔄 To Implement  
**Priority:** MEDIUM

**Purpose:** Database information, migrations, and maintenance tools

**UI Components:**
- Connection info card:
  - Database name
  - Host
  - MySQL/MariaDB version
  - Connection status (live check)
  - Uptime
- Migration status card:
  - Current version
  - Available migrations (if any)
  - Run migrations button
  - Migration history table
- Database statistics:
  - Total tables
  - Total size (MB/GB)
  - Table breakdown (name, rows, size)
  - Index usage statistics
- Maintenance tools:
  - Optimize tables button
  - Repair tables button
  - Check integrity button
  - Clear orphaned data button

**API Endpoints to Create:**
```
GET  /api/admin/database/info        # Connection info & version
GET  /api/admin/database/migrations  # Migration status
POST /api/admin/database/migrate     # Run pending migrations
GET  /api/admin/database/statistics  # Table sizes & stats
POST /api/admin/database/optimize    # Optimize all tables
POST /api/admin/database/repair      # Repair tables
POST /api/admin/database/integrity   # Check data integrity
```

**Service Layer:**
- `DatabaseService::getConnectionInfo()`
- `DatabaseService::getMigrationStatus()`
- `DatabaseService::runMigrations()`
- `DatabaseService::getStatistics()`
- `DatabaseService::optimizeTables()`
- `DatabaseService::repairTables()`
- `DatabaseService::checkIntegrity()`

**Security:**
- Admin-only access (all endpoints)
- Log all maintenance operations
- Confirmation modal for destructive operations
- Lock migrations during execution

---

### 7. User Management Tab ✅ MOSTLY COMPLETE
**File:** `src/views/admin/tabs/users.php`  
**Status:** ✅ Extract from existing code  
**Priority:** LOW (cleanup only)

**Purpose:** Manage user accounts, roles, and permissions

**Current Features:**
- ✅ User list table with search/filter
- ✅ Add user modal
- ✅ Edit user modal
- ✅ Delete user confirmation
- ✅ Role management (user/admin)
- ✅ Active/inactive status toggle

**API Endpoints:** ✅ Already exist
```
GET    /api/users           # ✅ List users
POST   /api/users           # ✅ Create user
GET    /api/users/{id}      # ✅ Get user details
PUT    /api/users/{id}      # ✅ Update user
DELETE /api/users/{id}      # ✅ Delete user
```

**Tasks:**
- Extract existing users tab HTML from `admin-settings.php`
- Move JavaScript to separate file: `src/public/assets/js/admin/users-tab.js`
- Add user statistics to overview card

---

### 8. Email Signatures Tab ✅ MOSTLY COMPLETE
**File:** `src/views/admin/tabs/signatures.php`  
**Status:** ✅ Extract from existing code  
**Priority:** LOW (cleanup only)

**Purpose:** Manage global email signatures and monitor user signatures

**Current Features:**
- ✅ Global signatures list
- ✅ User signatures list
- ✅ Create/edit signature modal
- ✅ HTML editor for signature content
- ✅ Default signature toggle
- ✅ Preview signature

**API Endpoints:** ✅ Already exist
```
GET    /api/admin/signatures           # ✅ List all signatures
POST   /api/admin/signatures           # ✅ Create signature
GET    /api/admin/signatures/{id}      # ✅ Get signature
PUT    /api/admin/signatures/{id}      # ✅ Update signature
DELETE /api/admin/signatures/{id}      # ✅ Delete signature
```

**Tasks:**
- Extract existing signatures tab HTML from `admin-settings.php`
- Move JavaScript to separate file: `src/public/assets/js/admin/signatures-tab.js`
- Add signature statistics to overview card

---

## Implementation Order

### Phase 1: Extract Existing Tabs (1-2 hours)
1. ✅ Create `src/views/admin/tabs/overview.php` - DONE
2. Extract `users.php` from admin-settings.php
3. Extract `signatures.php` from admin-settings.php
4. Create stub files for remaining tabs
5. Update `admin-settings.php` to use includes

### Phase 2: High Priority Tabs (6-8 hours)
1. **IMAP Tab** (2-3 hours):
   - Create UI with form
   - Implement API endpoints
   - Add test connection feature
   - Integrate with SystemSettingsService

2. **SMTP Tab** (2-3 hours):
   - Create UI with form
   - Implement API endpoints
   - Add test email feature
   - Integrate with SystemSettingsService

3. **Backup Tab** (2-3 hours):
   - Move backup-management.php content
   - Add schedule configuration
   - Add restore functionality
   - Add remote sync configuration

### Phase 3: Medium Priority Tabs (4-6 hours)
1. **Cron Tab** (2-3 hours):
   - Create execution history view
   - Add performance charts (Chart.js)
   - Add manual trigger feature
   - Add email alerts configuration

2. **Database Tab** (2-3 hours):
   - Create info cards
   - Implement maintenance tools API
   - Add migration runner
   - Add table statistics view

### Phase 4: Polish & Testing (2-3 hours)
1. Consistent styling across all tabs
2. Mobile responsive adjustments
3. Error handling and loading states
4. Integration testing
5. Update documentation

---

## JavaScript Module Structure

```javascript
// src/public/assets/js/admin/admin-tabs.js
// Main tab switching logic

// src/public/assets/js/admin/
// ├── tabs/
// │   ├── overview.js       # Overview metrics loading
// │   ├── imap-tab.js       # IMAP config form handling
// │   ├── smtp-tab.js       # SMTP config form handling
// │   ├── cron-tab.js       # Cron history & charts
// │   ├── backup-tab.js     # Backup management
// │   ├── database-tab.js   # Database tools
// │   ├── users-tab.js      # User management (extract from admin-settings.js)
// │   └── signatures-tab.js # Signature management (extract from admin-settings.js)
```

---

## Tab Navigation Implementation

### Updated Tab Buttons (admin-settings.php)
```html
<div class="c-tabs">
    <button class="c-tabs__tab is-active" data-tab="overview">Overview</button>
    <button class="c-tabs__tab" data-tab="imap">IMAP</button>
    <button class="c-tabs__tab" data-tab="smtp">SMTP</button>
    <button class="c-tabs__tab" data-tab="cron">Cron Monitor</button>
    <button class="c-tabs__tab" data-tab="backup">Backups</button>
    <button class="c-tabs__tab" data-tab="database">Database</button>
    <button class="c-tabs__tab" data-tab="users">Users</button>
    <button class="c-tabs__tab" data-tab="signatures">Signatures</button>
</div>
```

### Tab Content Includes
```php
<!-- Overview Tab -->
<?php include __DIR__ . '/../views/admin/tabs/overview.php'; ?>

<!-- IMAP Tab -->
<?php include __DIR__ . '/../views/admin/tabs/imap.php'; ?>

<!-- SMTP Tab -->
<?php include __DIR__ . '/../views/admin/tabs/smtp.php'; ?>

<!-- Cron Tab -->
<?php include __DIR__ . '/../views/admin/tabs/cron.php'; ?>

<!-- Backup Tab -->
<?php include __DIR__ . '/../views/admin/tabs/backup.php'; ?>

<!-- Database Tab -->
<?php include __DIR__ . '/../views/admin/tabs/database.php'; ?>

<!-- Users Tab -->
<?php include __DIR__ . '/../views/admin/tabs/users.php'; ?>

<!-- Signatures Tab -->
<?php include __DIR__ . '/../views/admin/tabs/signatures.php'; ?>
```

### JavaScript Tab Switching
```javascript
// Global function for card-to-tab navigation
function switchToTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.c-tabs__content').forEach(tab => {
        tab.classList.remove('is-active');
    });
    
    // Remove active state from all buttons
    document.querySelectorAll('.c-tabs__tab').forEach(btn => {
        btn.classList.remove('is-active');
    });
    
    // Show target tab
    const targetTab = document.getElementById(`${tabName}-tab`);
    if (targetTab) {
        targetTab.classList.add('is-active');
    }
    
    // Activate target button
    const targetButton = document.querySelector(`[data-tab="${tabName}"]`);
    if (targetButton) {
        targetButton.classList.add('is-active');
    }
    
    // Trigger tab-specific load function if exists
    const loadFunction = window[`load${tabName.charAt(0).toUpperCase() + tabName.slice(1)}Tab`];
    if (typeof loadFunction === 'function') {
        loadFunction();
    }
}
```

---

## Security Considerations

### All Tabs:
- ✅ Session-based admin authentication
- ⏳ Add AuthMiddleware to API routes (Phase 1 Security)
- ⏳ Add CSRF tokens to forms (Phase 1 Security)
- ⏳ Add rate limiting (Phase 2 Security)
- ⏳ Add input validation service (Phase 2 Security)

### Sensitive Configuration (IMAP/SMTP):
- ✅ Encrypt passwords with `EncryptionService`
- ✅ Never display passwords in plain text (show masked or empty)
- ✅ Test connections before saving
- ✅ Log all configuration changes with user context

### Database Operations:
- ⏳ Confirmation modals for destructive operations
- ⏳ Lock migrations during execution
- ⏳ Backup before running migrations
- ⏳ Audit log for all maintenance operations

---

## Success Criteria

### Functionality:
- [ ] All 8 tabs fully implemented and working
- [ ] Card-to-tab navigation functional
- [ ] All API endpoints working and tested
- [ ] Forms validate and save correctly
- [ ] Test connections/emails working
- [ ] Backup/restore fully functional

### Code Quality:
- [ ] Modular file structure (separate tab files)
- [ ] DRY principle (no duplicate code)
- [ ] Consistent styling (BEM, ITCSS)
- [ ] Comprehensive error handling
- [ ] Logging for all operations

### User Experience:
- [ ] Intuitive navigation
- [ ] Clear status indicators
- [ ] Helpful error messages
- [ ] Loading states for async operations
- [ ] Mobile responsive design

### Security:
- [ ] Admin-only access enforced
- [ ] CSRF protection on forms
- [ ] Encrypted sensitive data
- [ ] Input validation on all inputs
- [ ] Audit log for admin actions

---

**Next Steps:**
1. ✅ Create `overview.php` - COMPLETED
2. Refactor `admin-settings.php` to use tab includes
3. Extract users and signatures tabs
4. Create stub files for remaining tabs
5. Begin Phase 2 implementation (IMAP, SMTP, Backup)

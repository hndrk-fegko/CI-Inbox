# Admin Features - System Health & Backup Management

**Status:** ✅ Completed (M3 Sprint 3.x)  
**Created:** 2025-01-XX  
**Components:** System Health Monitor, Backup Management

---

## Overview

This document describes the two new admin-only features added to CI-Inbox for production readiness:

1. **System Health Monitor** - Real-time system metrics and error monitoring
2. **Backup Management** - Database backup creation and restoration

---

## 1. System Health Monitor

### Purpose
Provides administrators with real-time visibility into system health, cron job status, and error logs.

### Location
- **UI:** `src/public/system-health.php`
- **API:** `src/routes/api.php` - `/api/system/*` group

### Features

#### Dashboard Metrics
- **Database Status:** Connection status and response time
- **Disk Space:** Available storage with warning thresholds
- **PHP Version:** Runtime version check
- **IMAP Accounts:** Count of configured accounts

#### Cron Job Monitoring
- **Last Execution:** Timestamp of most recent poll
- **Success Rate:** Percentage of successful executions (last 50 runs)
- **Emails Processed Today:** Count of emails handled today
- **Status Chart:** Visual representation of execution history

#### Error Log Viewer
- **Real-time Display:** Auto-refreshes every 30 seconds
- **Severity Filtering:** Shows errors and warnings
- **Detailed Context:** Displays error message, file, line number, context data
- **Timestamp:** Human-readable time format

### API Endpoints

#### `GET /api/system/health`
Returns basic system health metrics.

**Response:**
```json
{
  "success": true,
  "data": {
    "database": {
      "status": "connected",
      "response_time": "0.003s"
    },
    "disk_space": {
      "free_gb": 45.2,
      "total_gb": 100.0,
      "usage_percent": 54.8
    },
    "php_version": "8.1.25",
    "imap_accounts": 2
  }
}
```

#### `GET /api/system/cron-status`
Returns cron execution statistics.

**Response:**
```json
{
  "success": true,
  "data": {
    "last_poll": "2025-01-13 14:32:05",
    "status": "healthy",
    "success_rate": 98.5,
    "emails_today": 47,
    "last_error": null
  }
}
```

#### `GET /api/system/errors`
Returns recent error log entries (default: last 50 errors).

**Query Parameters:**
- `limit` (optional): Number of errors to return (default: 50)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "timestamp": "2025-01-13 14:30:12",
      "level": "error",
      "message": "IMAP connection timeout",
      "file": "src/modules/imap/ImapClient.php",
      "line": 145,
      "context": {
        "account_id": 1,
        "host": "mail.example.com"
      }
    }
  ]
}
```

### UI Components

#### Auto-Refresh
- **Metrics:** Refreshes every 30 seconds
- **Cron Status:** Refreshes every 30 seconds
- **Error Log:** Refreshes every 30 seconds
- **Manual Refresh:** Button available for immediate update

#### Navigation
- Accessible from main admin panel
- Link in user dropdown menu
- Breadcrumb navigation

### Security
- **Admin Only:** Requires `user_role === 'admin'` in session
- **Session Check:** Redirects to login if not authenticated

---

## 2. Backup Management

### Purpose
Enables administrators to create, download, and manage database backups for disaster recovery.

### Location
- **UI:** `src/public/backup-management.php`
- **Service:** `src/app/Services/BackupService.php`
- **API:** `src/routes/api.php` - `/api/admin/backup/*` group
- **Storage:** `data/backups/` directory

### Features

#### Backup Creation
- **One-Click Backup:** Creates compressed SQL dump
- **Automatic Compression:** Gzip compression (level 9, ~90% reduction)
- **Metadata Tracking:** Filename, size, compression ratio, creation date
- **Security:** Uses `escapeshellarg()` for mysqldump parameters

#### Backup Listing
- **Sorted Display:** Most recent backups first
- **Size Information:** Shows original and compressed sizes
- **Creation Date:** Human-readable timestamps
- **Quick Actions:** Download and delete buttons per backup

#### Backup Download
- **Secure Delivery:** Validates filename format before serving
- **Direct Stream:** Uses `readfile()` for efficient transfer
- **Proper Headers:** Sets Content-Type and Content-Disposition

#### Backup Deletion
- **Confirmation Dialog:** Requires user confirmation
- **Secure Deletion:** Validates filename before unlink
- **Logging:** All deletions logged for audit trail

#### Auto-Cleanup
- **Retention Policy:** Configurable retention period (default: 30 days)
- **Scheduled Cleanup:** Can be triggered manually or via cron
- **Safe Deletion:** Only removes files older than retention period

### API Endpoints

#### `POST /api/admin/backup/create`
Creates a new database backup.

**Response:**
```json
{
  "success": true,
  "data": {
    "filename": "backup-2025-01-13_14-35-22.sql.gz",
    "size": 1234567,
    "size_mb": 1.18,
    "compression_ratio": 89.5,
    "path": "/path/to/data/backups/backup-2025-01-13_14-35-22.sql.gz"
  }
}
```

#### `GET /api/admin/backup/list`
Lists all available backups.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "filename": "backup-2025-01-13_14-35-22.sql.gz",
      "size": 1234567,
      "size_mb": 1.18,
      "created_at": 1705156522,
      "created_at_human": "2025-01-13 14:35:22",
      "path": "/path/to/data/backups/backup-2025-01-13_14-35-22.sql.gz"
    }
  ]
}
```

#### `GET /api/admin/backup/download/{filename}`
Downloads a specific backup file.

**Response:** Binary file stream (application/gzip)

**Error Response (404):**
```json
{
  "success": false,
  "error": "Backup not found"
}
```

#### `DELETE /api/admin/backup/delete/{filename}`
Deletes a specific backup file.

**Response:**
```json
{
  "success": true
}
```

#### `POST /api/admin/backup/cleanup`
Deletes backups older than retention period.

**Request Body:**
```json
{
  "retention_days": 30
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "deleted_count": 3
  }
}
```

### Backup Strategy

#### Filename Format
```
backup-YYYY-MM-DD_HH-MM-SS.sql.gz
```

Example: `backup-2025-01-13_14-35-22.sql.gz`

#### Compression
- **Algorithm:** Gzip (level 9)
- **Typical Ratio:** 85-95% size reduction
- **Buffer Size:** 512KB for large databases

#### Security
- **Filename Validation:** Regex `/^backup-\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql\.gz$/`
- **Path Traversal Protection:** Rejects `../` or absolute paths
- **SQL Injection Protection:** Uses `escapeshellarg()` for mysqldump command
- **Admin-Only Access:** All endpoints require admin role

#### Exclusions
- **Uploaded Attachments:** Not included in backup (too large)
- **Session Files:** Temporary data excluded
- **Cache Files:** Regenerable data excluded
- **Log Files:** Stored separately

### BackupService Methods

#### `createBackup(): array`
Creates a new compressed SQL dump.

**Returns:**
```php
[
    'filename' => 'backup-2025-01-13_14-35-22.sql.gz',
    'size' => 1234567,
    'size_mb' => 1.18,
    'compression_ratio' => 89.5,
    'path' => '/full/path/to/backup.sql.gz'
]
```

#### `listBackups(): array`
Returns array of all backups sorted by date descending.

#### `getBackupPath(string $filename): ?string`
Validates filename and returns full path, or null if invalid.

#### `deleteBackup(string $filename): bool`
Deletes a backup file after validation.

#### `cleanupOldBackups(int $retentionDays = 30): int`
Deletes backups older than specified days. Returns count of deleted files.

#### `compressFile(string $source, string $destination): array`
Compresses a file using Gzip level 9.

**Returns:**
```php
[
    'original_size' => 12345678,
    'compressed_size' => 1234567,
    'compression_ratio' => 90.0
]
```

### Dependencies
- **LoggerService:** Logs all backup operations
- **ConfigService:** Reads database credentials from config
- **mysqldump:** System utility (must be in PATH or XAMPP bin)
- **PHP Gzip Extension:** For compression (standard in PHP 8.1+)

### Testing
Manual test script: `tests/manual/backup-service-test.php`

**Usage:**
```bash
php tests/manual/backup-service-test.php
```

**Tests:**
1. Initialize BackupService
2. List existing backups
3. Create new backup
4. Verify backup file exists
5. List backups again (verify new backup appears)
6. Test invalid filename rejection (security)
7. Delete test backup (with confirmation)
8. Test cleanup old backups logic

---

## Container Registration

Both services are registered in `src/config/container.php`:

```php
// Backup Service
\CiInbox\App\Services\BackupService::class => function($container) {
    return new \CiInbox\App\Services\BackupService(
        $container->get(LoggerService::class),
        $container->get(ConfigService::class)
    );
},

// Cron Monitor Service (already existed)
\CiInbox\App\Services\CronMonitorService::class => function($container) {
    return new \CiInbox\App\Services\CronMonitorService(
        $container->get(LoggerService::class)
    );
},
```

---

## Navigation Integration

### Admin Panel Links
Add to `admin-settings.php` sidebar:
```html
<a href="/system-health.php" class="nav-link">
    <svg><!-- icon --></svg>
    System Health
</a>

<a href="/backup-management.php" class="nav-link">
    <svg><!-- icon --></svg>
    Backup Management
</a>
```

### User Dropdown
Add to header user dropdown in all admin pages:
```html
<a href="/system-health.php">🔍 System Health</a>
<a href="/backup-management.php">💾 Backup Management</a>
```

---

## Production Deployment

### Pre-Deployment Checklist

#### System Health Monitor
- [ ] Verify cron job is running (webhook polling active)
- [ ] Check log rotation is configured (prevent huge log files)
- [ ] Test auto-refresh intervals in production environment
- [ ] Verify IMAP account connections work

#### Backup Management
- [ ] Ensure `data/backups/` directory exists and is writable
- [ ] Verify mysqldump is accessible in PATH
- [ ] Test backup creation with production database size
- [ ] Configure automated backup cron job (daily recommended)
- [ ] Set up off-site backup sync (optional but recommended)
- [ ] Test restoration process from backup

### Recommended Cron Jobs

#### Daily Backup (3 AM)
```bash
0 3 * * * curl -X POST http://ci-inbox.local/api/admin/backup/create
```

#### Weekly Cleanup (Sunday 4 AM)
```bash
0 4 * * 0 curl -X POST http://ci-inbox.local/api/admin/backup/cleanup -d '{"retention_days":30}'
```

### Monitoring Alerts
Consider adding alerts for:
- Backup creation failures
- Disk space below 10 GB
- Cron success rate below 90%
- Error log entries with level "critical"

---

## Known Limitations

### System Health Monitor
- **No historical graphs:** Current implementation shows real-time data only
- **Error log size:** May be slow with extremely large log files (10,000+ entries)
- **No email alerts:** System doesn't send notifications on critical errors

### Backup Management
- **No incremental backups:** Full database dump each time
- **No encryption:** Backups stored unencrypted (add GPG encryption if needed)
- **No remote backup:** Files stored locally only (add S3/FTP sync if needed)
- **No restore UI:** Restoration requires manual mysqldump import via CLI

---

## Future Enhancements (Post-M3)

### System Health Monitor
- [ ] Add historical performance graphs (using Chart.js)
- [ ] Implement email alerts for critical errors
- [ ] Add disk I/O and network monitoring
- [ ] Create webhook notifications for status changes

### Backup Management
- [ ] Add backup restoration UI (one-click restore)
- [ ] Implement incremental/differential backups
- [ ] Add backup encryption (GPG or OpenSSL)
- [ ] Add remote backup sync (S3, FTP, Dropbox)
- [ ] Add backup verification (checksum validation)
- [ ] Create backup scheduling UI (instead of manual cron)

---

## Security Considerations

### Access Control
- ✅ Admin-only access enforced in both UI and API
- ✅ Session validation before rendering pages
- ⚠️ **TODO:** Add AuthMiddleware to API routes (Phase 1 security)
- ⚠️ **TODO:** Add CSRF protection to forms (Phase 1 security)

### File Security
- ✅ Filename validation prevents path traversal
- ✅ SQL injection prevention with `escapeshellarg()`
- ✅ `.gitignore` prevents backup files in version control
- ⚠️ **Consider:** Add backup encryption for sensitive data
- ⚠️ **Consider:** Add file integrity checks (SHA256 checksums)

### API Security
- ⚠️ **TODO:** Add rate limiting to prevent abuse
- ⚠️ **TODO:** Add input validation for all parameters
- ⚠️ **TODO:** Add API authentication (currently session-only)

---

## Troubleshooting

### Backup Creation Fails

**Error:** "mysqldump: command not found"  
**Solution:** Add mysqldump to PATH or use full path in BackupService:
```php
// Windows XAMPP
$mysqldumpPath = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
```

**Error:** "Permission denied writing to data/backups/"  
**Solution:** Make directory writable:
```bash
chmod 755 data/backups/
```

**Error:** "Database connection failed"  
**Solution:** Check `.env` file has correct DB credentials

### System Health Shows "Disconnected"

**Error:** "Database status: disconnected"  
**Solution:** Verify Eloquent is initialized in api.php:
```php
require_once __DIR__ . '/../bootstrap/database.php';
initDatabase($config);
```

### Error Log Not Displaying

**Error:** "No errors found" but logs exist  
**Solution:** Check log format is JSON (not plain text):
```php
// Correct format in LoggerService
$this->logger->error('Message', ['context' => 'data']);
```

---

## References

- **Roadmap:** `docs/dev/PRODUCTION-READINESS.md`
- **Architecture:** `docs/dev/architecture.md`
- **Logger Module:** `src/modules/logger/README.md`
- **Config Module:** `src/modules/config/README.md`
- **Manual Tests:** `tests/manual/backup-service-test.php`

---

**Last Updated:** 2025-01-XX  
**Status:** ✅ Production Ready (pending Phase 1 security)

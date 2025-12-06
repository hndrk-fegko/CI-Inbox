# API Endpoints: Admin Settings Module

**Date:** December 2025  
**Status:** ✅ Complete - All 54 API endpoints implemented

---

## Overview

This document lists all API endpoints used by the admin settings modules.

---

## IMAP Module (010)

| Method | Endpoint | Purpose | Status |
|--------|----------|---------|--------|
| GET | `/api/admin/settings/imap` | Get IMAP configuration | ✅ |
| PUT | `/api/admin/settings/imap` | Update IMAP configuration | ✅ |
| POST | `/api/admin/settings/imap/test` | Test IMAP connection | ✅ |
| POST | `/api/admin/settings/imap/autodiscover` | Auto-discover from email | ✅ |

### Request/Response Examples

**GET /api/admin/settings/imap**
```json
{
  "success": true,
  "data": {
    "configured": true,
    "host": "imap.example.com",
    "port": 993,
    "encryption": "ssl",
    "username": "user@example.com",
    "password": "********",
    "inbox_folder": "INBOX",
    "validate_cert": true
  }
}
```

**POST /api/admin/settings/imap/test**
```json
// Request
{
  "host": "imap.example.com",
  "port": 993,
  "encryption": "ssl",
  "username": "user@example.com",
  "password": "secret"
}

// Response (success)
{
  "success": true,
  "message": "Connection successful",
  "data": ["INBOX", "Sent", "Drafts", "Trash"]
}
```

---

## SMTP Module (020)

| Method | Endpoint | Purpose | Status |
|--------|----------|---------|--------|
| GET | `/api/admin/settings/smtp` | Get SMTP configuration | ✅ |
| PUT | `/api/admin/settings/smtp` | Update SMTP configuration | ✅ |
| POST | `/api/admin/settings/smtp/test` | Send test email | ✅ |
| POST | `/api/admin/settings/smtp/autodiscover` | Auto-discover from email | ✅ |

### Request/Response Examples

**PUT /api/admin/settings/smtp**
```json
// Request
{
  "host": "smtp.example.com",
  "port": 465,
  "encryption": "ssl",
  "username": "user@example.com",
  "password": "secret",
  "from_name": "Support Team",
  "from_email": "support@example.com",
  "auth_required": true
}

// Response
{
  "success": true,
  "message": "SMTP configuration updated successfully",
  "data": { /* config object */ }
}
```

**POST /api/admin/settings/smtp/test**
```json
// Request
{
  "test_email": "admin@example.com"
}

// Response
{
  "success": true,
  "message": "Test email sent successfully"
}
```

---

## Cron Module (030)

| Method | Endpoint | Purpose | Status |
|--------|----------|---------|--------|
| GET | `/api/system/cron-status` | Get cron service status | ✅ |
| GET | `/api/admin/cron/history` | Get execution history | ✅ |
| GET | `/api/admin/cron/statistics` | Get performance stats | ✅ |
| GET | `/api/admin/cron/webhook` | Get webhook URL/token | ✅ |
| POST | `/api/admin/cron/webhook/regenerate` | Regenerate webhook token | ✅ |

### Health Thresholds (Minutely Cron)
- **Healthy:** >55 executions/hour
- **Degraded:** 30-55 executions/hour
- **Delayed:** <30 executions/hour
- **Stale:** <1 execution/hour

### Request/Response Examples

**GET /api/system/cron-status**
```json
{
  "success": true,
  "data": {
    "status": "healthy",
    "last_poll_at": "2025-12-05 14:30:00",
    "minutes_ago": 5,
    "executions_last_hour": 58,
    "emails_today": 42
  }
}
```

**GET /api/admin/cron/webhook**
```json
{
  "success": true,
  "data": {
    "token": "abc123...",
    "url": "https://example.com/api/webcron/poll?token=abc123..."
  }
}
```

---

## Backup Module (040)

| Method | Endpoint | Purpose | Status |
|--------|----------|---------|--------|
| GET | `/api/admin/backup/list` | List all backups | ✅ |
| POST | `/api/admin/backup/create` | Create new backup | ✅ |
| GET | `/api/admin/backup/download/{filename}` | Download backup | ✅ |
| DELETE | `/api/admin/backup/delete/{filename}` | Delete backup | ✅ |
| POST | `/api/admin/backup/cleanup` | Bulk delete old backups | ✅ |
| GET | `/api/admin/backup/schedule` | Get auto-backup schedule | ✅ |
| PUT | `/api/admin/backup/schedule` | Update schedule | ✅ |
| GET | `/api/admin/backup/storage` | Get external storage config | ✅ |
| PUT | `/api/admin/backup/storage` | Update storage config | ✅ |
| POST | `/api/admin/backup/storage/test` | Test storage connection | ✅ |
| DELETE | `/api/admin/backup/storage` | Remove storage config | ✅ |
| GET | `/api/admin/backup/usage` | Get storage usage stats | ✅ |

### Request/Response Examples

**GET /api/admin/backup/list**
```json
{
  "success": true,
  "data": [
    {
      "filename": "backup_2025-12-05_143000.sql",
      "size": 1048576,
      "size_human": "1.0 MB",
      "created_at": "2025-12-05 14:30:00",
      "location": "local",
      "is_monthly": false
    }
  ]
}
```

**PUT /api/admin/backup/schedule**
```json
// Request
{
  "enabled": true,
  "frequency": "daily",
  "time": "03:00",
  "retention_days": 30,
  "location": "both",
  "keep_monthly": true
}
```

---

## Database Module (050)

| Method | Endpoint | Purpose | Status |
|--------|----------|---------|--------|
| GET | `/api/system/health` | Get system health | ✅ |
| GET | `/api/admin/database/status` | Get database status | ✅ |
| GET | `/api/admin/database/tables` | List tables with sizes | ✅ |
| POST | `/api/admin/database/optimize` | Optimize all tables | ✅ |
| POST | `/api/admin/database/analyze` | Analyze all tables | ✅ |
| GET | `/api/admin/database/orphaned` | Check orphaned data | ✅ |
| GET | `/api/admin/database/migrations` | Get migration status | ✅ |

---

## Users Module (060)

| Method | Endpoint | Purpose | Status |
|--------|----------|---------|--------|
| GET | `/api/users` | List all users | ✅ |
| POST | `/api/users` | Create user | ✅ |
| PUT | `/api/users/{id}` | Update user | ✅ |
| DELETE | `/api/users/{id}` | Delete user | ✅ |
| GET | `/api/admin/users/stats` | Get user statistics | ✅ |

---

## OAuth2 Module (065) - NEW

| Method | Endpoint | Purpose | Status |
|--------|----------|---------|--------|
| GET | `/api/admin/oauth/config` | Get OAuth configuration | ✅ |
| PUT | `/api/admin/oauth/config` | Update global settings | ✅ |
| PUT | `/api/admin/oauth/providers/{provider}` | Update provider config | ✅ |
| GET | `/api/admin/oauth/stats` | Get OAuth statistics | ✅ |
| GET | `/api/admin/oauth/users` | List OAuth-linked users | ✅ |

### Supported Providers
- Google
- Microsoft / Azure AD
- GitHub
- Custom OIDC

---

## Signatures Module (070)

| Method | Endpoint | Purpose | Status |
|--------|----------|---------|--------|
| GET | `/api/admin/signatures` | List all signatures | ✅ |
| POST | `/api/admin/signatures` | Create signature | ✅ |
| PUT | `/api/admin/signatures/{id}` | Update signature | ✅ |
| DELETE | `/api/admin/signatures/{id}` | Delete signature | ✅ |
| POST | `/api/admin/signatures/{id}/set-default` | Set default signature | ✅ |

### Signature Types
- **Shared Inbox:** Global signatures for team inbox
- **Personal:** User-specific signatures (admin editable)

---

## Logger Module (080)

| Method | Endpoint | Purpose | Status |
|--------|----------|---------|--------|
| GET | `/api/system/errors` | Get recent errors | ✅ |
| GET | `/api/admin/logger/level` | Get current log level | ✅ |
| PUT | `/api/admin/logger/level` | Set log level | ✅ |
| GET | `/api/admin/logger/stream` | Get log stream | ✅ |
| GET | `/api/admin/logger/stats` | Get log statistics | ✅ |
| POST | `/api/admin/logger/clear` | Clear log files | ✅ |
| POST | `/api/admin/logger/download` | Download log archive | ✅ |

---

## Summary

| Module | Implemented | Pending | Total |
|--------|-------------|---------|-------|
| IMAP | 4 | 0 | 4 |
| SMTP | 4 | 0 | 4 |
| Cron | 5 | 0 | 5 |
| Backup | 12 | 0 | 12 |
| Database | 7 | 0 | 7 |
| Users | 5 | 0 | 5 |
| OAuth2 | 5 | 0 | 5 |
| Signatures | 5 | 0 | 5 |
| Logger | 7 | 0 | 7 |
| **Total** | **54** | **0** | **54** |

---

## Authentication

All admin endpoints require:
1. Active session (`$_SESSION['user_id']`)
2. Admin role (`$_SESSION['user_role'] === 'admin'`)

Unauthorized requests return:
```json
{
  "success": false,
  "error": "Unauthorized"
}
```

---

## Error Handling

All endpoints follow a consistent error format:
```json
{
  "success": false,
  "error": "Human-readable error message"
}
```

HTTP Status codes:
- `200` - Success
- `400` - Bad Request (validation error)
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `500` - Internal Server Error

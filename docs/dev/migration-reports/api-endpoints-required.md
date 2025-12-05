# API Endpoints: Admin Settings Module

**Date:** December 2025  
**Status:** ✅ All endpoints implemented and connected

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

### Request/Response Examples

**GET /api/system/cron-status**
```json
{
  "success": true,
  "data": {
    "status": "healthy",
    "last_poll_at": "2025-12-05 14:30:00",
    "minutes_ago": 5,
    "emails_today": 42,
    "success_rate": 98
  }
}
```

**GET /api/admin/cron/history?page=1&per_page=10**
```json
{
  "success": true,
  "data": [
    {
      "started_at": "2025-12-05 14:30:00",
      "accounts_polled": 3,
      "emails_fetched": 5,
      "duration_ms": 1234,
      "status": "success"
    }
  ],
  "meta": {
    "total": 150,
    "page": 1,
    "per_page": 10
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
      "created_at_human": "Today at 14:30"
    }
  ]
}
```

**POST /api/admin/backup/cleanup**
```json
// Request
{
  "retention_days": 30
}

// Response
{
  "success": true,
  "data": {
    "deleted_count": 5
  }
}
```

---

## Database Module (050)

| Method | Endpoint | Purpose | Status |
|--------|----------|---------|--------|
| GET | `/api/system/health` | Get system health | ✅ |

### Notes

The database module uses the system health endpoint for connection status. 
Table information is currently displayed based on the known schema.
Future enhancements could add:
- `GET /api/admin/database/tables` - List tables with sizes
- `POST /api/admin/database/optimize` - Optimize all tables
- `POST /api/admin/database/analyze` - Analyze all tables

---

## Users Module (060)

| Method | Endpoint | Purpose | Status |
|--------|----------|---------|--------|
| GET | `/api/users` | List all users | ✅ |
| POST | `/api/users` | Create user | ✅ |
| PUT | `/api/users/{id}` | Update user | ✅ |
| DELETE | `/api/users/{id}` | Delete user | ✅ |

---

## Signatures Module (070)

| Method | Endpoint | Purpose | Status |
|--------|----------|---------|--------|
| GET | `/api/admin/signatures` | List all signatures | ✅ |
| POST | `/api/signatures/global` | Create global signature | ✅ |
| PUT | `/api/signatures/global/{id}` | Update signature | ✅ |
| DELETE | `/api/signatures/global/{id}` | Delete signature | ✅ |

---

## Logger Module (080)

| Method | Endpoint | Purpose | Status |
|--------|----------|---------|--------|
| GET | `/api/system/errors` | Get recent errors | ✅ |

### Notes

The logger module currently uses the system errors endpoint for log viewing.
Future enhancements could add:
- `GET /api/admin/logger/level` - Get current log level
- `PUT /api/admin/logger/level` - Set log level
- `POST /api/admin/logger/clear` - Clear log files
- `GET /api/admin/logger/download` - Download log archive

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

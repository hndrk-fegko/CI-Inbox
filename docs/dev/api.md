# CI-Inbox REST API Documentation

**Version:** 0.3.0  
**Base URL:** `http://ci-inbox.local/api`  
**Last Updated:** 6. Dezember 2025  
**Autor:** Hendrik Dreis ([hendrik.dreis@feg-koblenz.de](mailto:hendrik.dreis@feg-koblenz.de))  
**Lizenz:** MIT License

This document provides a complete reference for all REST API endpoints in CI-Inbox.

---

## Table of Contents

1. [Authentication](#authentication)
2. [Response Format](#response-format)
3. [Error Handling](#error-handling)
4. [System Endpoints](#system-endpoints)
5. [Thread Management API](#thread-management-api)
6. [Email API](#email-api)
7. [IMAP Sync API](#imap-sync-api)
8. [Webhook API](#webhook-api)
9. [Data Models](#data-models)
10. [Pagination & Filtering](#pagination--filtering)

---

## Authentication

### Current Status
**Implemented:** Web-based authentication (GET/POST /auth/login, POST /auth/logout)  
**Partial:** Session-based API authentication (session cookie works, but no dedicated API endpoints)  
**Planned:** Token-based authentication (JWT) for API consumers

### Authentication Flow (Web)
```
1. User visits GET /auth/login
2. User submits POST /auth/login with email/password
3. Session established (PHP Session)
4. User can access protected routes
5. User logs out via POST /auth/logout
```

### Authentication Flow (API - Planned)
```
1. POST /api/auth/login → Returns JWT token
2. Include token in Authorization header: Bearer <token>
3. API validates token on each request
4. POST /api/auth/logout → Invalidate token
```

**Current Implementation:** `AuthController` with session management (src/app/Controllers/AuthController.php)

**Demo Credentials:**
- Email: `demo@c-imap.local` / Password: `demo123`
- Email: `admin@c-imap.local` / Password: `admin123`

**TODO:**
- [ ] POST /api/auth/login - API login endpoint
- [ ] POST /api/auth/logout - API logout endpoint
- [ ] GET /api/auth/me - Get current user info
- [ ] Authentication middleware for API routes
- [ ] JWT token generation and validation

---

## Response Format

### Success Response
```json
{
  "success": true,
  "data": { /* response data */ },
  "meta": { /* pagination, timestamps, etc. */ }
}
```

### Error Response
```json
{
  "success": false,
  "error": "Error message",
  "code": "ERROR_CODE",
  "details": { /* optional error details */ }
}
```

### HTTP Status Codes
- `200 OK` - Success
- `201 Created` - Resource created
- `400 Bad Request` - Validation error
- `401 Unauthorized` - Authentication required
- `403 Forbidden` - Permission denied
- `404 Not Found` - Resource not found
- `500 Internal Server Error` - Server error

---

## Error Handling

### Common Error Codes
| Code | Description | HTTP Status |
|------|-------------|-------------|
| `VALIDATION_ERROR` | Invalid input data | 400 |
| `THREAD_NOT_FOUND` | Thread does not exist | 404 |
| `EMAIL_NOT_FOUND` | Email does not exist | 404 |
| `UNAUTHORIZED` | Authentication failed | 401 |
| `FORBIDDEN` | Insufficient permissions | 403 |
| `INTERNAL_ERROR` | Server error | 500 |

---

## System Endpoints

### Health Check

**Endpoint:** `GET /api/system/health`

**Description:** Check system health and availability.

**Response:**
```json
{
  "status": "healthy",
  "timestamp": "2025-11-18T14:30:00+00:00",
  "modules": {
    "logger": { "status": "ok" },
    "config": { "status": "ok" },
    "encryption": { "status": "ok" },
    "database": { "status": "ok" }
  },
  "system": {
    "php_version": "8.1.0",
    "extensions": ["imap", "openssl", "pdo_mysql"]
  }
}
```

---

### API Info

**Endpoint:** `GET /api`

**Description:** Get API version and available endpoints.

**Response:**
```json
{
  "name": "CI-Inbox API",
  "version": "0.1.0",
  "endpoints": {
    "system": ["GET /api/system/health"],
    "threads": ["POST /api/threads", "GET /api/threads", ...],
    "emails": ["POST /api/emails/send", ...],
    "webhooks": ["POST /api/webhooks", ...]
  }
}
```

---

## Thread Management API

### Create Thread

**Endpoint:** `POST /api/threads`

**Request Body:**
```json
{
  "subject": "Customer Inquiry",
  "status": "open",
  "assigned_to": 1,
  "labels": [1, 2]
}
```

**Response:** `201 Created`
```json
{
  "success": true,
  "thread": {
    "id": 123,
    "subject": "Customer Inquiry",
    "status": "open",
    "assigned_to": 1,
    "email_count": 0,
    "unread_count": 0,
    "labels": [],
    "created_at": "2025-11-18T14:30:00+00:00",
    "updated_at": "2025-11-18T14:30:00+00:00",
    "last_message_at": null
  }
}
```

---

### List Threads

**Endpoint:** `GET /api/threads`

**Query Parameters:**
| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| `status` | string | Filter by status: `open`, `closed`, `archived` | - |
| `assigned_to` | int | Filter by assigned user ID | - |
| `label` | int | Filter by label ID | - |
| `limit` | int | Results per page | 50 |
| `offset` | int | Pagination offset | 0 |

**Example:** `GET /api/threads?status=open&limit=10&offset=0`

**Response:**
```json
{
  "threads": [
    {
      "id": 123,
      "subject": "Customer Inquiry",
      "status": "open",
      "assigned_to": 1,
      "email_count": 3,
      "unread_count": 1,
      "labels": [
        { "id": 1, "name": "Support", "color": "blue" }
      ],
      "last_message_at": "2025-11-18T14:30:00+00:00",
      "created_at": "2025-11-18T10:00:00+00:00"
    }
  ],
  "meta": {
    "total": 45,
    "limit": 10,
    "offset": 0
  }
}
```

---

### Get Thread Details

**Endpoint:** `GET /api/threads/{id}`

**Query Parameters:**
| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| `with_emails` | bool | Include emails | true |
| `with_notes` | bool | Include internal notes | true |

**Response:**
```json
{
  "thread": {
    "id": 123,
    "subject": "Customer Inquiry",
    "status": "open",
    "assigned_to": 1,
    "emails": [
      {
        "id": 456,
        "subject": "Customer Inquiry",
        "from_email": "customer@example.com",
        "from_name": "John Doe",
        "to_addresses": ["support@company.com"],
        "body_html": "<p>Hello...</p>",
        "body_text": "Hello...",
        "is_read": false,
        "has_attachments": false,
        "sent_at": "2025-11-18T14:30:00+00:00"
      }
    ],
    "notes": [
      {
        "id": 789,
        "content": "Customer seems satisfied",
        "user_id": 1,
        "position": 1,
        "created_at": "2025-11-18T15:00:00+00:00"
      }
    ],
    "labels": [],
    "created_at": "2025-11-18T14:30:00+00:00"
  }
}
```

---

### Get Thread Details for UI

**Endpoint:** `GET /api/threads/{id}/details`

**Description:** Optimized endpoint for UI, includes all related data.

**Response:**
```json
{
  "thread": {
    "id": 123,
    "subject": "Customer Inquiry",
    "status": "open",
    "emails": [ /* full email objects */ ],
    "notes": [ /* full note objects */ ],
    "labels": [ /* full label objects */ ],
    "assigned_user": {
      "id": 1,
      "email": "agent@company.com",
      "name": "Agent Name"
    }
  }
}
```

---

### Update Thread

**Endpoint:** `PUT /api/threads/{id}`

**Request Body:**
```json
{
  "subject": "Updated Subject",
  "status": "closed",
  "assigned_to": 2
}
```

**Response:**
```json
{
  "success": true,
  "thread": { /* updated thread object */ }
}
```

---

### Delete Thread

**Endpoint:** `DELETE /api/threads/{id}`

**Response:**
```json
{
  "success": true
}
```

---

### Add Internal Note

**Endpoint:** `POST /api/threads/{id}/notes`

**Request Body:**
```json
{
  "content": "Customer called back, issue resolved",
  "user_id": 1,
  "position": 1
}
```

**Response:** `201 Created`
```json
{
  "success": true,
  "note": {
    "id": 789,
    "thread_id": 123,
    "content": "Customer called back, issue resolved",
    "user_id": 1,
    "position": 1,
    "created_at": "2025-11-18T15:00:00+00:00"
  }
}
```

---

### Split Thread

**Endpoint:** `POST /api/threads/{id}/split`

**Description:** Split thread by moving emails to new thread.

**Request Body:**
```json
{
  "email_ids": [456, 457],
  "new_subject": "New Thread Subject"
}
```

**Response:**
```json
{
  "success": true,
  "original_thread": { /* original thread */ },
  "new_thread": { /* newly created thread */ }
}
```

---

### Merge Threads

**Endpoint:** `POST /api/threads/{targetId}/merge`

**Description:** Merge source thread into target thread.

**Request Body:**
```json
{
  "source_thread_id": 124
}
```

**Response:**
```json
{
  "success": true,
  "merged_thread": { /* merged thread */ }
}
```

---

### Assign Email to Thread

**Endpoint:** `POST /api/threads/{id}/emails/{emailId}/assign`

**Description:** Assign an email to a specific thread.

**Response:**
```json
{
  "success": true,
  "thread": { /* updated thread */ }
}
```

---

### Move Email to Different Thread

**Endpoint:** `PATCH /api/emails/{emailId}/thread`

**Request Body:**
```json
{
  "thread_id": 125
}
```

**Response:**
```json
{
  "success": true,
  "email": { /* updated email */ }
}
```

---

## Email API

### Send New Email

**Endpoint:** `POST /api/emails/send`

**Request Body:**
```json
{
  "to": "customer@example.com",
  "cc": ["manager@company.com"],
  "bcc": [],
  "subject": "Your Inquiry",
  "body_html": "<p>Dear Customer...</p>",
  "body_text": "Dear Customer...",
  "attachments": [],
  "thread_id": 123
}
```

**Response:** `201 Created`
```json
{
  "success": true,
  "email": {
    "id": 456,
    "subject": "Your Inquiry",
    "to_addresses": ["customer@example.com"],
    "sent_at": "2025-11-18T15:30:00+00:00",
    "thread_id": 123
  }
}
```

---

### Reply to Thread

**Endpoint:** `POST /api/threads/{id}/reply`

**Description:** Reply to all emails in thread (preserves threading headers).

**Request Body:**
```json
{
  "body_html": "<p>Thank you for your inquiry...</p>",
  "body_text": "Thank you for your inquiry...",
  "attachments": []
}
```

**Response:**
```json
{
  "success": true,
  "email": { /* sent email object */ }
}
```

---

### Forward Thread

**Endpoint:** `POST /api/threads/{id}/forward`

**Description:** Forward thread emails to new recipient.

**Request Body:**
```json
{
  "to": "colleague@company.com",
  "body_html": "<p>FYI...</p>",
  "body_text": "FYI..."
}
```

**Response:**
```json
{
  "success": true,
  "email": { /* forwarded email object */ }
}
```

---

## IMAP Sync API

### Sync IMAP Account

**Endpoint:** `POST /api/imap/accounts/{id}/sync`

**Description:** Fetch new emails from IMAP server for specific account.

**Response:**
```json
{
  "success": true,
  "data": {
    "account_id": 1,
    "email": "support@company.com",
    "new_emails": 5,
    "processed": 5,
    "failed": 0,
    "errors": [],
    "duration": 3.45
  }
}
```

---

## Webhook API

### Register Webhook

**Endpoint:** `POST /api/webhooks`

**Request Body:**
```json
{
  "url": "https://your-app.com/webhook",
  "events": ["thread.created", "email.sent"],
  "secret": "your-secret-key",
  "is_active": true
}
```

**Response:** `201 Created`
```json
{
  "success": true,
  "webhook": {
    "id": 1,
    "url": "https://your-app.com/webhook",
    "events": ["thread.created", "email.sent"],
    "is_active": true,
    "created_at": "2025-11-18T16:00:00+00:00"
  }
}
```

---

### List Webhooks

**Endpoint:** `GET /api/webhooks`

**Response:**
```json
{
  "webhooks": [
    {
      "id": 1,
      "url": "https://your-app.com/webhook",
      "events": ["thread.created"],
      "is_active": true,
      "last_triggered_at": "2025-11-18T15:30:00+00:00"
    }
  ]
}
```

---

### Get Webhook Details

**Endpoint:** `GET /api/webhooks/{id}`

**Response:**
```json
{
  "webhook": {
    "id": 1,
    "url": "https://your-app.com/webhook",
    "events": ["thread.created", "email.sent"],
    "secret": "***hidden***",
    "is_active": true,
    "failed_attempts": 0,
    "created_at": "2025-11-18T16:00:00+00:00"
  }
}
```

---

### Update Webhook

**Endpoint:** `PUT /api/webhooks/{id}`

**Request Body:**
```json
{
  "url": "https://new-url.com/webhook",
  "events": ["thread.created"],
  "is_active": false
}
```

**Response:**
```json
{
  "success": true,
  "webhook": { /* updated webhook */ }
}
```

---

### Delete Webhook

**Endpoint:** `DELETE /api/webhooks/{id}`

**Response:**
```json
{
  "success": true
}
```

---

### Get Webhook Delivery History

**Endpoint:** `GET /api/webhooks/{id}/deliveries`

**Response:**
```json
{
  "deliveries": [
    {
      "id": 100,
      "webhook_id": 1,
      "event_type": "thread.created",
      "payload": { /* event payload */ },
      "response_status": 200,
      "response_body": "OK",
      "attempts": 1,
      "delivered_at": "2025-11-18T15:30:00+00:00"
    }
  ]
}
```

---

### Retry Failed Delivery

**Endpoint:** `POST /api/webhooks/deliveries/{id}/retry`

**Response:**
```json
{
  "success": true,
  "delivery": { /* updated delivery object */ }
}
```

---

## Label Management API

### Create Label

**Endpoint:** `POST /api/labels`

**Request Body:**
```json
{
  "name": "Important",
  "color": "#FF5733",
  "display_order": 10
}
```

**Response:** `201 Created`
```json
{
  "success": true,
  "label_id": 1,
  "message": "Label created successfully"
}
```

---

### List Labels

**Endpoint:** `GET /api/labels`

**Query Parameters:**
| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| `system_only` | bool | Only system labels | false |

**Response:**
```json
{
  "labels": [
    {
      "id": 1,
      "name": "Important",
      "color": "#FF5733",
      "display_order": 10,
      "is_system_label": false
    }
  ],
  "total": 15
}
```

---

### Get Label

**Endpoint:** `GET /api/labels/{id}`

**Response:**
```json
{
  "label": {
    "id": 1,
    "name": "Important",
    "color": "#FF5733",
    "display_order": 10
  }
}
```

---

### Update Label

**Endpoint:** `PUT /api/labels/{id}`

**Request Body:**
```json
{
  "name": "Very Important",
  "color": "#FF0000"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Label updated successfully"
}
```

---

### Delete Label

**Endpoint:** `DELETE /api/labels/{id}`

**Response:**
```json
{
  "success": true,
  "message": "Label deleted successfully"
}
```

**Note:** System labels cannot be deleted.

---

### Get Label Statistics

**Endpoint:** `GET /api/labels/stats`

**Description:** Get thread count per label.

**Response:**
```json
{
  "statistics": [
    {
      "label_id": 1,
      "label_name": "Important",
      "thread_count": 15,
      "color": "#FF5733",
      "is_system": false
    }
  ]
}
```

---

## Bulk Operations API

### Bulk Update Threads

**Endpoint:** `POST /api/threads/bulk/update`

**Request Body:**
```json
{
  "thread_ids": [1, 2, 3],
  "updates": {
    "status": "closed",
    "assigned_to": 5
  }
}
```

**Response:**
```json
{
  "success": true,
  "result": {
    "updated": 3,
    "failed": 0,
    "total": 3,
    "errors": []
  }
}
```

---

### Bulk Delete Threads

**Endpoint:** `POST /api/threads/bulk/delete`

**Request Body:**
```json
{
  "thread_ids": [1, 2, 3]
}
```

**Response:**
```json
{
  "success": true,
  "result": {
    "deleted": 3,
    "failed": 0,
    "total": 3
  }
}
```

---

### Bulk Assign Threads

**Endpoint:** `POST /api/threads/bulk/assign`

**Request Body:**
```json
{
  "thread_ids": [1, 2, 3],
  "user_id": 5
}
```

**Response:**
```json
{
  "success": true,
  "result": {
    "updated": 3,
    "failed": 0
  }
}
```

---

### Bulk Set Status

**Endpoint:** `POST /api/threads/bulk/status`

**Request Body:**
```json
{
  "thread_ids": [1, 2, 3],
  "status": "closed"
}
```

**Response:**
```json
{
  "success": true,
  "result": {
    "updated": 3,
    "failed": 0
  }
}
```

---

### Bulk Add Label

**Endpoint:** `POST /api/threads/bulk/labels/add`

**Request Body:**
```json
{
  "thread_ids": [1, 2, 3],
  "label_id": 5
}
```

**Response:**
```json
{
  "success": true,
  "result": {
    "added": 3,
    "failed": 0
  }
}
```

---

### Bulk Remove Label

**Endpoint:** `POST /api/threads/bulk/labels/remove`

**Request Body:**
```json
{
  "thread_ids": [1, 2, 3],
  "label_id": 5
}
```

**Response:**
```json
{
  "success": true,
  "result": {
    "removed": 3,
    "failed": 0
  }
}
```

---

## User Management API

**Status:** 🔴 NOT IMPLEMENTED (Planned)

### List Users

**Endpoint:** `GET /api/users`

**Authorization:** Admin only

**Query Parameters:**
| Parameter | Type | Description | Default |
|-----------|------|-------------|---------|
| `role` | string | Filter by role: `user`, `admin` | - |
| `is_active` | bool | Filter by active status | - |
| `limit` | int | Results per page | 50 |
| `offset` | int | Pagination offset | 0 |

**Response:**
```json
{
  "users": [
    {
      "id": 1,
      "email": "user@company.com",
      "name": "John Doe",
      "role": "user",
      "is_active": true,
      "last_login_at": "2025-11-18T14:30:00+00:00",
      "created_at": "2025-11-01T10:00:00+00:00"
    }
  ],
  "meta": {
    "total": 15,
    "limit": 50,
    "offset": 0
  }
}
```

---

### Get User

**Endpoint:** `GET /api/users/{id}`

**Response:**
```json
{
  "user": {
    "id": 1,
    "email": "user@company.com",
    "name": "John Doe",
    "role": "user",
    "is_active": true,
    "imap_accounts": [
      {
        "id": 1,
        "email": "personal@gmail.com",
        "imap_host": "imap.gmail.com"
      }
    ],
    "last_login_at": "2025-11-18T14:30:00+00:00",
    "created_at": "2025-11-01T10:00:00+00:00"
  }
}
```

---

### Create User

**Endpoint:** `POST /api/users`

**Authorization:** Admin only

**Request Body:**
```json
{
  "email": "newuser@company.com",
  "name": "Jane Smith",
  "password": "secure_password_123",
  "role": "user",
  "is_active": true
}
```

**Response:** `201 Created`
```json
{
  "success": true,
  "user": {
    "id": 16,
    "email": "newuser@company.com",
    "name": "Jane Smith",
    "role": "user",
    "is_active": true,
    "created_at": "2025-11-18T16:00:00+00:00"
  }
}
```

---

### Update User

**Endpoint:** `PUT /api/users/{id}`

**Authorization:** Admin or self

**Request Body:**
```json
{
  "name": "Jane Doe",
  "email": "jane.doe@company.com",
  "role": "admin",
  "is_active": false
}
```

**Response:**
```json
{
  "success": true,
  "user": { /* updated user */ }
}
```

---

### Delete User

**Endpoint:** `DELETE /api/users/{id}`

**Authorization:** Admin only

**Response:**
```json
{
  "success": true,
  "message": "User deleted successfully"
}
```

---

### Change Password

**Endpoint:** `POST /api/users/{id}/password`

**Authorization:** Admin or self

**Request Body:**
```json
{
  "current_password": "old_password",
  "new_password": "new_secure_password",
  "confirm_password": "new_secure_password"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Password changed successfully"
}
```

---

## Personal IMAP Account API

**Purpose:** Manage user's personal email accounts (Gmail, Outlook, etc.)  
**Use Case:** Workflow C - Transfer emails from personal accounts to shared inbox

**Naming Convention:**
- `/api/user/imap-accounts` - User's personal email accounts
- `/api/imap/accounts/{id}/sync` - Main shared inbox sync (separate!)

---

### List Personal Accounts

**Endpoint:** `GET /api/user/imap-accounts`

**Authorization:** User

**Query Parameters:**
- None

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "email": "user@gmail.com",
      "imap_host": "imap.gmail.com",
      "imap_port": 993,
      "imap_username": "user@gmail.com",
      "imap_encryption": "ssl",
      "is_default": false,
      "is_active": true,
      "last_sync_at": "2025-11-18T10:00:00Z",
      "created_at": "2025-11-01T08:00:00Z",
      "updated_at": "2025-11-18T10:00:00Z"
    }
  ],
  "count": 1
}
```

---

### Get Personal Account

**Endpoint:** `GET /api/user/imap-accounts/{id}`

**Authorization:** User (owner only)

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "email": "user@gmail.com",
    "imap_host": "imap.gmail.com",
    "imap_port": 993,
    "imap_username": "user@gmail.com",
    "imap_encryption": "ssl",
    "is_default": false,
    "is_active": true,
    "last_sync_at": "2025-11-18T10:00:00Z"
  }
}
```

---

### Create Personal Account

**Endpoint:** `POST /api/user/imap-accounts`

**Authorization:** User

**Request Body:**
```json
{
  "email": "user@gmail.com",
  "password": "app-specific-password",
  "imap_host": "imap.gmail.com",
  "imap_port": 993,
  "imap_username": "user@gmail.com",
  "imap_encryption": "ssl",
  "is_default": false,
  "is_active": true
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "email": "user@gmail.com",
    "imap_host": "imap.gmail.com",
    "imap_port": 993,
    "imap_username": "user@gmail.com",
    "imap_encryption": "ssl",
    "is_default": false,
    "is_active": true,
    "created_at": "2025-11-18T12:00:00Z"
  }
}
```

**Validation:**
- Email must be valid format
- Password required
- Duplicate email not allowed (per user)

---

### Update Personal Account

**Endpoint:** `PUT /api/user/imap-accounts/{id}`

**Authorization:** User (owner only)

**Request Body:**
```json
{
  "imap_host": "imap.gmail.com",
  "imap_port": 993,
  "password": "new-app-password",
  "is_active": true
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "email": "user@gmail.com",
    "imap_host": "imap.gmail.com",
    "imap_port": 993,
    "is_active": true,
    "updated_at": "2025-11-18T12:30:00Z"
  }
}
```

---

### Delete Personal Account

**Endpoint:** `DELETE /api/user/imap-accounts/{id}`

**Authorization:** User (owner only)

**Response:**
```json
{
  "success": true,
  "message": "Account deleted successfully"
}
```

---

### Test IMAP Connection

**Endpoint:** `POST /api/user/imap-accounts/{id}/test-connection`

**Authorization:** User (owner only)

**Response (Success):**
```json
{
  "success": true,
  "message": "Connection successful"
}
```

**Response (Failure):**
```json
{
  "success": false,
  "message": "Connection failed: Invalid credentials"
}
```

---

## Public Webhook Endpoint

**Purpose:** External cron services (cron-job.org, EasyCron) can trigger email polling

**Endpoint:** `POST /webhooks/poll-emails`

**Important:** This is NOT part of `/api/webhooks` (webhook management)!

**Authentication:**
- X-Webhook-Token header
- Authorization: Bearer token
- Request body: `{"token": "..."}`
- Query parameter: `?token=...`

**Secret Token:** Set via environment variable `WEBCRON_SECRET_TOKEN`

**Request Example:**
```bash
curl -X POST http://ci-inbox.local/webhooks/poll-emails \
  -H "X-Webhook-Token: your-secret-token-here"
```

**Response (Success):**
```json
{
  "success": true,
  "data": {
    "accounts_polled": 3,
    "total_new_emails": 12,
    "duration_seconds": 4.5,
    "errors": []
  }
}
```

**Response (Auth Failure):**
```json
{
  "success": false,
  "error": "Invalid authentication token"
}
```

**Setup with External Cron:**
1. Sign up at cron-job.org
2. Create new cron job
3. URL: `https://your-domain.com/webhooks/poll-emails`
4. Method: POST
5. Header: `X-Webhook-Token: your-secret-token`
6. Schedule: Every 5 minutes

---

## Data Models

### Thread Model

```typescript
interface Thread {
  id: number
  subject: string
  status: 'open' | 'closed' | 'archived'
  assigned_to: number | null
  email_count: number
  unread_count: number
  sender_email: string | null
  sender_name: string | null
  last_message_at: string | null  // ISO 8601
  created_at: string              // ISO 8601
  updated_at: string              // ISO 8601
  
  // Relations (when loaded)
  emails?: Email[]
  labels?: Label[]
  notes?: InternalNote[]
  assigned_user?: User
}
```

---

### Email Model

```typescript
interface Email {
  id: number
  thread_id: number
  message_id: string
  subject: string
  from_email: string
  from_name: string | null
  to_addresses: string[]
  cc_addresses: string[]
  bcc_addresses: string[]
  body_html: string | null
  body_text: string
  is_read: boolean
  is_outgoing: boolean
  has_attachments: boolean
  sent_at: string          // ISO 8601
  received_at: string      // ISO 8601
  created_at: string       // ISO 8601
}
```

---

### Label Model

```typescript
interface Label {
  id: number
  name: string
  color: string            // hex color or named color
  description: string | null
  created_at: string       // ISO 8601
}
```

---

### Internal Note Model

```typescript
interface InternalNote {
  id: number
  thread_id: number
  user_id: number | null
  content: string
  position: number
  created_at: string       // ISO 8601
}
```

---

### Webhook Model

```typescript
interface Webhook {
  id: number
  url: string
  events: string[]         // ['thread.created', 'email.sent', ...]
  secret: string
  is_active: boolean
  last_triggered_at: string | null  // ISO 8601
  failed_attempts: number
  created_at: string       // ISO 8601
  updated_at: string       // ISO 8601
}
```

---

## Pagination & Filtering

### Current Implementation

**Pagination:**
- Query Parameters: `limit`, `offset`
- Default: `limit=50`, `offset=0`
- Response includes `meta` object with totals

**Example:**
```
GET /api/threads?limit=20&offset=40
```

**Response:**
```json
{
  "threads": [ /* 20 threads */ ],
  "meta": {
    "total": 150,
    "limit": 20,
    "offset": 40
  }
}
```

---

### Filtering

**Thread Filters:**
- `status` - Filter by thread status
- `assigned_to` - Filter by assigned user ID
- `label` - Filter by label ID

**Example:**
```
GET /api/threads?status=open&assigned_to=1
```

---

### Planned Improvements

**TODO:**
1. **Cursor-based Pagination** - Better performance for large datasets
2. **Search Endpoint** - Full-text search across emails
3. **Advanced Filters:**
   - Date range filtering
   - Unread/read filtering
   - Has attachments filtering
4. **Bulk Operations:**
   - `POST /api/threads/bulk/update` - Update multiple threads
   - `POST /api/threads/bulk/delete` - Delete multiple threads
   - `POST /api/threads/bulk/assign` - Assign multiple threads
5. **Attachment Management:**
   - `GET /api/emails/{id}/attachments` - List attachments
   - `GET /api/emails/{id}/attachments/{attachmentId}` - Download attachment

---

## API Usage Examples

### Example 1: List Open Threads

```bash
curl -X GET "http://ci-inbox.local/api/threads?status=open&limit=10" \
  -H "Content-Type: application/json"
```

---

### Example 2: Create Thread and Add Note

```bash
# 1. Create thread
curl -X POST "http://ci-inbox.local/api/threads" \
  -H "Content-Type: application/json" \
  -d '{
    "subject": "New Customer Inquiry",
    "status": "open"
  }'

# Response: {"success": true, "thread": {"id": 123, ...}}

# 2. Add note
curl -X POST "http://ci-inbox.local/api/threads/123/notes" \
  -H "Content-Type: application/json" \
  -d '{
    "content": "Customer contacted via phone",
    "user_id": 1
  }'
```

---

### Example 3: Reply to Thread

```bash
curl -X POST "http://ci-inbox.local/api/threads/123/reply" \
  -H "Content-Type: application/json" \
  -d '{
    "body_html": "<p>Thank you for your inquiry...</p>",
    "body_text": "Thank you for your inquiry..."
  }'
```

---

## Architecture Notes

### Layer Structure

Following `basics.txt` principles:

```
┌─────────────────────────────────────┐
│   HTTP Layer (Routes)               │  ← api.php
├─────────────────────────────────────┤
│   Controller Layer                  │  ← ThreadController, EmailController
├─────────────────────────────────────┤
│   Service Layer (Business Logic)    │  ← ThreadApiService, EmailSendService
├─────────────────────────────────────┤
│   Repository Layer (Data Access)    │  ← ThreadRepository, EmailRepository
├─────────────────────────────────────┤
│   Model Layer (Eloquent ORM)        │  ← Thread, Email, Label models
└─────────────────────────────────────┘
```

### Responsibilities

- **Routes:** HTTP routing only
- **Controllers:** Request validation, response formatting
- **Services:** Business logic, orchestration
- **Repositories:** Database queries, data persistence
- **Models:** Data representation, relationships

---

## Changelog

### Version 0.1.0 (November 18, 2025)

**Implemented:**
- ✅ Thread Management API (CRUD, notes, split, merge)
- ✅ Email API (send, reply, forward)
- ✅ IMAP Sync API
- ✅ Webhook API (CRUD, deliveries, retry)
- ✅ User Management API (CRUD, password change)
- ✅ Personal IMAP Account API (user's personal email accounts)
- ✅ Label Management API (CRUD, statistics)
- ✅ Bulk Operations API (update, delete, assign, labels)
- ✅ Public Webhook Endpoint (POST /webhooks/poll-emails for external cron)
- ✅ Basic filtering (status, assigned_to, label)
- ✅ Offset-based pagination

**TODO:**
- [ ] API Authentication Middleware (JWT)
- [ ] Cursor-based pagination
- [ ] Search endpoints
- [ ] Attachment management API
- [ ] Advanced filtering (date range, unread, etc.)

---

## Support & Feedback

For questions or feedback:
- **Documentation:** `docs/dev/`
- **Architecture:** `docs/dev/architecture.md`
- **Codebase:** `docs/dev/codebase.md`

---

**End of API Documentation**

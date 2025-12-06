# Webcron Module

**Automatic Email Polling System**

**Version:** 1.0.0  
**Status:** ✅ Production Ready  
**Autor:** Hendrik Dreis  
**Lizenz:** MIT License

---

## Overview

The Webcron module provides automated email polling functionality for CI-Inbox. It orchestrates IMAP synchronization across all active accounts via internal API calls, enabling regular email fetching triggered by external cron services.

**Key Features:**
- 🔄 Automatic polling of all active IMAP accounts
- 🔒 API key + IP whitelist authentication
- 📊 Job status tracking and monitoring
- 🚫 Job locking to prevent parallel execution
- 🔌 Internal API orchestration (uses `/api/imap/accounts/{id}/sync`)

---

## Architecture

```
┌─────────────────────────────────────────────────┐
│  External Cron Service                          │
│  (e.g., cron-job.org)                           │
│  Calls: /webcron/poll?api_key=xxx               │
└───────────────┬─────────────────────────────────┘
                │
                ▼
┌─────────────────────────────────────────────────┐
│  WebcronManager (Orchestrator)                  │
│  - Validates API key & IP                       │
│  - Fetches active IMAP accounts                 │
│  - Makes internal HTTP calls to sync API        │
│  - Aggregates results                           │
└───────────────┬─────────────────────────────────┘
                │
                ▼ (Internal HTTP: POST /api/imap/accounts/{id}/sync)
┌─────────────────────────────────────────────────┐
│  ImapController::syncAccount()                  │
│  - Connects to IMAP server                      │
│  - Fetches new emails (using CI-Synced tag)     │
│  - Parses & processes emails                    │
│  - Creates threads                              │
│  - Updates sync timestamp                       │
└─────────────────────────────────────────────────┘
```

---

## Components

### 1. WebcronManager

**File:** `src/WebcronManager.php`

**Responsibilities:**
- Orchestrate polling jobs
- Manage job locking
- Make internal API calls to sync endpoints
- Aggregate results from multiple accounts

**Key Methods:**
```php
// Poll all active accounts
public function runPollingJob(): array

// Poll specific account
public function pollAccount(int $accountId): array

// Get current job status
public function getJobStatus(): array

// Test webcron setup
public function testSetup(): array
```

### 2. WebcronManagerInterface

**File:** `src/WebcronManagerInterface.php`

Defines the contract for webcron implementations. Allows for alternative implementations (e.g., queue-based polling).

### 3. WebcronException

**File:** `src/Exceptions/WebcronException.php`

Specialized exceptions for webcron operations:
- `jobAlreadyRunning()` - Job lock active
- `noActiveAccounts()` - No accounts to poll
- `accountNotFound()` - Invalid account ID
- `accountInactive()` - Account disabled

---

## Configuration

**File:** `config/webcron.config.php`

```php
return [
    // Internal API base URL for sync calls
    'api_base_url' => 'http://ci-inbox.local',
    
    // Polling interval (documentation only)
    'polling_interval' => 5,  // minutes
    
    // Limits
    'max_emails_per_run' => 50,
    'max_accounts_per_run' => 10,
    'account_timeout' => 30,  // seconds
    
    // Security
    'allowed_ips' => [
        '127.0.0.1',      // Localhost
        '::1',            // IPv6 localhost
        // '203.0.113.0',  // Production cron IP
    ],
    
    // Authentication
    'api_key' => getenv('WEBCRON_API_KEY') ?: 'dev-secret-key-12345',
    
    // Job locking
    'enable_job_lock' => true,
    'job_lock_timeout_seconds' => 300,
];
```

---

## API Endpoints

### 1. Poll Endpoint

```
GET /webcron/poll?api_key=YOUR_KEY
```

**Query Parameters:**
- `api_key` (required) - Authentication key
- `account_id` (optional) - Poll single account only

**Response (Success):**
```json
{
  "success": true,
  "result": {
    "accounts_processed": 2,
    "emails_fetched": 5,
    "errors": []
  }
}
```

**Response (Error):**
```json
{
  "success": false,
  "error": "Invalid API key"
}
```

**Status Codes:**
- `200` - Success
- `401` - Invalid API key
- `403` - IP not allowed
- `500` - Server error

---

### 2. Status Endpoint

```
GET /webcron/status
```

**No authentication required** (for monitoring).

**Response:**
```json
{
  "success": true,
  "status": {
    "is_running": false,
    "active_accounts": 2,
    "last_run": "2025-11-18 14:30:00",
    "last_run_result": {
      "accounts_processed": 2,
      "emails_fetched": 5,
      "duration_seconds": 3.45,
      "errors_count": 0
    }
  }
}
```

---

### 3. Test Endpoint

```
GET /webcron/test?api_key=YOUR_KEY
```

Tests webcron setup without fetching emails.

**Response:**
```json
{
  "success": true,
  "checks": {
    "config_loaded": true,
    "database_connected": true,
    "active_accounts": 2,
    "imap_module_available": true
  }
}
```

---

## Usage

### Development (Manual Trigger)

```bash
# Using curl
curl "http://ci-inbox.local/webcron/poll?api_key=dev-secret-key-12345"

# Using browser
http://ci-inbox.local/webcron/poll?api_key=dev-secret-key-12345
```

### Production (External Cron Service)

**Recommended Services:**
- [cron-job.org](https://cron-job.org) - Free, reliable
- [EasyCron](https://www.easycron.com) - Feature-rich
- Server-based cron (if available)

**Setup Example (cron-job.org):**

1. Create account at cron-job.org
2. Add new cron job:
   - **URL:** `https://your-domain.com/webcron/poll?api_key=YOUR_SECRET_KEY`
   - **Interval:** Every 5 minutes
   - **Method:** GET
3. Add your IP to `allowed_ips` in config
4. Test with manual trigger
5. Monitor via `/webcron/status`

---

## Testing

### Manual Test Script

**File:** `tests/manual/webcron-poll-test.php`

```bash
# Run test
php tests/manual/webcron-poll-test.php
```

**Tests:**
1. ✓ Get webcron status (no auth)
2. ✓ Poll without API key (should fail with 401)
3. ✓ Poll with valid API key (full sync)
4. ✓ Poll single account

---

## How It Works

### Polling Flow

```
1. External Cron → GET /webcron/poll?api_key=xxx
2. WebcronManager validates API key + IP
3. Acquire job lock (prevent parallel runs)
4. Fetch all active IMAP accounts from DB
5. For each account:
   a. Make internal HTTP call: POST /api/imap/accounts/{id}/sync
   b. ImapController connects to IMAP server
   c. Fetch new emails (using CI-Synced tag as marker)
   d. Parse emails (EmailParser)
   e. Assign to threads (ThreadManager)
   f. Store in database
   g. Mark emails with CI-Synced tag
6. Aggregate results
7. Release job lock
8. Return JSON response
```

### Deduplication Strategy

**Database = Source of Truth (SSOT)**
- Each email has unique `message_id` (from email headers)
- Before processing: Check if `message_id` exists in DB
- If exists: Skip (set CI-Synced tag if missing)
- If new: Process and store

**IMAP Tag = Performance Marker**
- `CI-Synced` custom keyword tag on IMAP server
- Used for fast filtering: `UNKEYWORD CI-Synced`
- Reduces emails to check (performance optimization)
- **Fallback:** If server doesn't support keywords, DB check handles deduplication

---

## Monitoring

### Check Status

```bash
# Via API
curl http://ci-inbox.local/webcron/status

# Via logs
tail -f logs/app.log | grep -i webcron
```

### Metrics to Monitor

- **Accounts processed:** Should match active account count
- **Emails fetched:** Varies (0 if no new emails)
- **Errors:** Should be 0 (investigate if > 0)
- **Duration:** Typical 2-10 seconds per account
- **Last run:** Should be recent (within polling interval)

---

## Troubleshooting

### Issue: "Invalid API key"

**Cause:** API key mismatch  
**Fix:** Check `webcron.config.php` → `api_key` value

```bash
# Verify current API key
grep -r "WEBCRON_API_KEY" .env
# or check config directly
```

---

### Issue: "IP not allowed"

**Cause:** Client IP not in whitelist  
**Fix:** Add IP to `allowed_ips` array in config

```php
'allowed_ips' => [
    '127.0.0.1',      // Localhost
    '203.0.113.5',    // Add your cron service IP
],
```

---

### Issue: "Job already running"

**Cause:** Previous job didn't finish (or crashed)  
**Fix:** Job lock times out after 5 minutes automatically

```bash
# Check logs for crash
tail -f logs/app.log | grep "Webcron polling job"
```

---

### Issue: No emails fetched

**Possible Causes:**
1. No new emails in mailbox
2. All emails already synced
3. IMAP connection failed

**Debug:**
```bash
# Check account status
curl http://ci-inbox.local/api/imap/accounts

# Manual sync single account
curl -X POST http://ci-inbox.local/api/imap/accounts/1/sync
```

---

## Security Considerations

### 1. API Key Protection

- Store in environment variable: `WEBCRON_API_KEY`
- Use strong random value in production
- Never commit to git

```bash
# Generate secure key
php -r "echo bin2hex(random_bytes(32));"
```

---

### 2. IP Whitelist

- Always use IP whitelist in production
- Add only trusted cron service IPs
- Monitor access attempts in logs

---

### 3. HTTPS Required

- Use HTTPS in production
- Prevents API key interception
- Protects email content during sync

---

## Performance

### Optimization Strategies

1. **IMAP Tag Filtering:** Uses `CI-Synced` keyword to reduce emails to check
2. **Job Locking:** Prevents parallel execution
3. **Timeout Management:** Account timeout prevents hanging
4. **Batch Processing:** Processes multiple accounts in single run

### Typical Performance

- **Empty run** (no new emails): 1-3 seconds
- **With new emails:** 2-10 seconds per account
- **Large mailbox:** First sync may take minutes (one-time)

---

## Dependencies

### Required Modules

- ✅ `logger` - Logging (PSR-3)
- ✅ `imap` - IMAP client
- ✅ `database` - Eloquent ORM
- ✅ `encryption` - Password decryption

### Required Database Tables

- `imap_accounts` - Active accounts
- `emails` - Email storage (deduplication)
- `threads` - Thread grouping
- `labels` - Label assignments

---

## Integration Points

### ImapController

**Endpoint:** `POST /api/imap/accounts/{id}/sync`

WebcronManager makes internal HTTP calls to this endpoint for actual email fetching.

### Container Registration

**File:** `src/config/container.php`

```php
WebcronManagerInterface::class => function($container) {
    return new WebcronManager(
        $container->get(ImapAccountRepository::class),
        $container->get(LoggerInterface::class),
        $container->get('webcron.config')
    );
}
```

---

## Future Enhancements

### Planned Features

- [ ] Queue-based polling (Laravel Queue)
- [ ] Webhook notifications on sync complete
- [ ] Per-account polling intervals
- [ ] Retry logic for failed accounts
- [ ] Sync statistics dashboard
- [ ] Email filtering rules during sync

---

## Support

**Documentation:**
- Architecture: `docs/dev/architecture.md`
- Sprint Docs: `docs/dev/[COMPLETED] M1-Sprint-1.5-Webcron-Polling-Dienst.md`

**Logs:**
- Application: `logs/app.log`
- Web Server: Check Apache/Nginx logs

**Debug Mode:**
- Enable in `.env`: `APP_DEBUG=true`
- Verbose logging: `LOG_LEVEL=debug`

---

## License

Part of CI-Inbox Project - Internal Documentation

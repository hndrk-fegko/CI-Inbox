# Manual Test Suite

This directory contains all manual test scripts for CI-Inbox. These tests are designed to be run individually from the command line to verify specific functionality.

## Test Categories

### Foundation Tests (M0)
| Test Script | Purpose | Status |
|-------------|---------|--------|
| `test-env-loading.php` | Verifies .env configuration loading | ✅ |

### IMAP & Email Tests (M1)
| Test Script | Purpose | Status |
|-------------|---------|--------|
| `test-autodiscover.php` | IMAP server autodiscovery | ✅ |
| `test-imap-config-flow.php` | Complete IMAP configuration flow | ✅ |
| `email-send-test.php` | Email sending via SMTP | ✅ |
| `smtp-test.php` | SMTP connection and basic send | ✅ |
| `test-smtp-autodiscover.php` | SMTP server autodiscovery | ✅ |

### Thread & API Tests (M2)
| Test Script | Purpose | Status |
|-------------|---------|--------|
| `thread-api-test.php` | Thread API endpoints | ✅ |
| `label-api-test.php` | Label API endpoints | ✅ |
| `webhook-test.php` | Webhook registration & dispatch | ✅ |
| `webhook-poll-test.php` | Webhook polling integration | ✅ |
| `archive-test.php` | Thread archiving functionality | ✅ |
| `bulk-operations-test.php` | Bulk thread operations | ✅ |
| `bulk-ops-test.php` | Bulk operations variant | ✅ |
| `bulk-delete-error-test.php` | Error handling in bulk delete | ✅ |
| `direct-bulk-delete-test.php` | Direct bulk delete test | ✅ |
| `delete-test.php` | Thread deletion | ✅ |
| `mark-read-test.php` | Mark threads as read | ✅ |
| `test-search.php` | Thread search functionality | ✅ |
| `list-threads.php` | List all threads | ✅ |

### User & Authentication Tests (M3)
| Test Script | Purpose | Status |
|-------------|---------|--------|
| `test-login.php` | User login flow | ✅ |
| `user-api-test.php` | User API endpoints | ✅ |
| `user-profile-test.php` | User profile management | ✅ |
| `create-test-user.php` | Create test user accounts | ✅ |
| `create-test-account.php` | Create test IMAP accounts | ✅ |
| `list-users.php` | List all users | ✅ |
| `debug-userprofile-controller.php` | Debug user profile controller | ✅ |
| `signature-test.php` | Email signature functionality | ✅ |
| `signature-frontend-test.php` | Frontend signature integration | ✅ |
| `test-signature-api.php` | Signature API endpoints | ✅ |
| `personal-imap-account-test.php` | Personal IMAP account setup | ✅ |

### System & Infrastructure Tests
| Test Script | Purpose | Status |
|-------------|---------|--------|
| `system-health-test.php` | System health check | ✅ |
| `webcron-poll-test.php` | Webcron polling service | ✅ |
| `test-cron-monitor.php` | Cron job monitoring | ✅ |
| `test-cron-monitor-e2e.php` | Cron monitor E2E test | ✅ |
| `test-integrity-webcron.php` | Webcron integrity check | ✅ |
| `backup-service-test.php` | Backup service functionality | ✅ |
| `theme-module-test.php` | Theme module functionality | ✅ |
| `test-php-detection.php` | PHP CLI path detection (CLI) | ✅ |
| `test-php-binary.php` | PHP binary detection (Web) | ✅ |

### Utility Scripts
| Script | Purpose |
|--------|---------|
| `get-thread-ids.php` | Retrieve thread IDs for testing |

## Running Tests

### Prerequisites
1. PHP 8.1+ installed
2. Database configured (MySQL 8.0+ / MariaDB 10.6+)
3. `.env` file properly configured
4. Dependencies installed via `composer install`

### Basic Usage

```bash
# Run from project root
cd /path/to/ci-inbox

# Run individual test
php tests/manual/webhook-test.php

# Run with verbose output
php tests/manual/smtp-test.php 2>&1

# Run multiple related tests
php tests/manual/thread-api-test.php && php tests/manual/label-api-test.php
```

### Test Environment Setup

```bash
# 1. Ensure database is migrated
php database/migrate.php

# 2. Create test user (if needed)
php tests/manual/create-test-user.php

# 3. Create test IMAP account (if needed)
php tests/manual/create-test-account.php
```

## Test Artifacts

Test data files are stored in `tests/manual/artifacts/`:

| File | Purpose |
|------|---------|
| `test-email.json` | Sample email data for testing |
| `test-avatar.txt` | Sample avatar image data |

## Test Output Format

All tests follow a consistent output pattern:

```
=== Test Name ===

TEST 1: Description
✅ Success message
   Additional details

TEST 2: Description
❌ Failure message
   Error details

=== All tests completed ===
```

## Writing New Tests

When creating new manual tests, follow this pattern:

```php
<?php
/**
 * Test Name Manual Test
 * 
 * Description of what this test verifies.
 * Usage: php tests/manual/your-test.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use CiInbox\Modules\Config\ConfigService;
use CiInbox\Modules\Logger\LoggerService;

// Initialize system
$config = new ConfigService(__DIR__ . '/../../');
require_once __DIR__ . '/../../src/bootstrap/database.php';
initDatabase($config);

$logger = new LoggerService(__DIR__ . '/../../logs/');

echo "=== Your Test Name ===" . PHP_EOL . PHP_EOL;

// TEST 1: Description
echo "TEST 1: What is being tested" . PHP_EOL;
try {
    // Test implementation
    echo "✅ Success" . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Failed: {$e->getMessage()}" . PHP_EOL;
    exit(1);
}

echo PHP_EOL . "=== All tests completed ===" . PHP_EOL;
```

## Troubleshooting

### Common Issues

1. **"Class not found" errors**
   - Run `composer install` to ensure dependencies are installed
   - Check autoload paths in your test script

2. **Database connection failures**
   - Verify `.env` file has correct database credentials
   - Ensure MySQL/MariaDB service is running

3. **SMTP/IMAP connection failures**
   - Check network connectivity
   - Verify credentials in `.env` or test script
   - Check firewall settings

4. **Permission errors**
   - Ensure `logs/` directory is writable
   - Check file permissions on test scripts

### Getting Help

- Check `logs/app.log` for detailed error messages
- Review `docs/dev/codebase.md` for development setup
- Open an issue on GitHub for persistent problems

## Test Coverage by Milestone

| Milestone | Tests | Status |
|-----------|-------|--------|
| M0 Foundation | 1 | ✅ Complete |
| M1 IMAP Core | 5 | ✅ Complete |
| M2 Thread API | 13 | ✅ Complete |
| M3 MVP UI | 12 | 🔄 In Progress |
| M4 Beta | 3 | 📋 Planned |

---

**Last Updated:** December 2025

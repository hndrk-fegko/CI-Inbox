# CI-Inbox: Production Readiness Roadmap

**Status:** M2 Complete → Production Preparation  
**Erstellt:** 20. November 2025  
**Ziel:** System produktionsreif machen innerhalb 2-3 Tage

---

## 📊 Current Status Assessment

### ✅ **Fertiggestellt & Stabil**

| Komponente | Status | Tests | Production-Ready? |
|-----------|--------|-------|------------------|
| Logger Module | ✅ Complete | ✅ Manual Tests | ✅ YES |
| Config Module | ✅ Complete | ✅ Manual Tests | ✅ YES |
| Encryption Service | ✅ Complete | ✅ Tested | ✅ YES |
| Database Schema | ✅ Complete | ✅ 17 Migrations | ✅ YES |
| IMAP Client | ✅ Complete | ✅ Production Tested | ✅ YES |
| Email Parser | ✅ Complete | ✅ Tested | ✅ YES |
| Thread Manager | ✅ Complete | ✅ Tested | ✅ YES |
| Webcron Polling | ✅ Complete | ✅ Token Auth | ✅ YES |
| Thread API (10) | ✅ Complete | ✅ Tested | ⚠️ NEEDS AUTH |
| Email Send API (3) | ✅ Complete | ✅ Tested | ⚠️ NEEDS AUTH |
| Webhook API (7) | ✅ Complete | ✅ Tested | ⚠️ NEEDS AUTH |

### ⚠️ **Security Gaps (KRITISCH)**

| Issue | Impact | Files Affected | Effort |
|-------|--------|---------------|--------|
| **No API Auth Middleware** | 🔴 CRITICAL | 27 API Endpoints offen | 4h |
| **23x TODO: Get user_id from JWT** | 🔴 HIGH | Controller Files | 2h |
| **No Rate Limiting** | 🟠 MEDIUM | Login, API | 2h |
| **No Input Validation** | 🟠 MEDIUM | All Controllers | 3h |
| **No CSRF Protection** | 🟠 MEDIUM | Web Forms | 1h |

### 📋 **Code Quality Issues**

- **23 TODOs** im Production-Code (grep search results)
- **Hardcoded user_id = 1** in SignatureController, UserProfileController
- **Missing error handling** in einigen Services

---

## 🎯 Production Readiness Plan

### **Phase 1: Authentication & Authorization** ⏱️ 4-6h | 🔴 KRITISCH

**Goal:** API-Endpoints absichern, Auth-Middleware implementieren

#### 1.1 API Authentication Middleware (2h)

**Create:** `src/app/Middleware/AuthMiddleware.php`

```php
<?php
namespace CiInbox\App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use CiInbox\Modules\Logger\LoggerInterface;

class AuthMiddleware
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if user is authenticated
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
            $this->logger->warning('Unauthorized API access attempt', [
                'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
                'path' => $request->getUri()->getPath()
            ]);

            $response = new Response();
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Authentication required'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }

        // Add user info to request attributes
        $request = $request
            ->withAttribute('user_id', $_SESSION['user_id'])
            ->withAttribute('user_email', $_SESSION['user_email'])
            ->withAttribute('user_role', $_SESSION['user_role'] ?? 'user');

        return $handler->handle($request);
    }
}
```

**Apply Middleware:** Update `src/routes/api.php`

```php
// Protect all API routes except system endpoints
$app->group('/api', function (RouteCollectorProxy $group) use ($container) {
    
    // Thread Management API (protected)
    $group->group('/threads', function (RouteCollectorProxy $group) {
        // ... existing routes
    })->add($container->get(AuthMiddleware::class));
    
    // Email API (protected)
    $group->group('/emails', function (RouteCollectorProxy $group) {
        // ... existing routes
    })->add($container->get(AuthMiddleware::class));
    
    // User API (protected)
    $group->group('/user', function (RouteCollectorProxy $group) {
        // ... existing routes
    })->add($container->get(AuthMiddleware::class));
    
    // Webhooks API (protected)
    $group->group('/webhooks', function (RouteCollectorProxy $group) {
        // ... existing routes
    })->add($container->get(AuthMiddleware::class));
    
    // System endpoints (public)
    $group->get('/system/health', ...);
    $group->get('/system/info', ...);
});
```

#### 1.2 Fix 23 TODO: Get user_id from JWT (2h)

**Pattern:** Replace all hardcoded `$userId = 1;` with request attribute

**Files to update:**
- `src/app/Controllers/SignatureController.php` (11 instances)
- `src/app/Controllers/UserProfileController.php` (5 instances)
- `src/routes/api.php` (2 instances)
- `src/app/Services/ThreadService.php` (1 instance)

**Example Fix:**

```php
// ❌ BEFORE
public function getProfile(Request $request, Response $response): Response {
    $userId = 1; // TODO: Get from JWT
    
// ✅ AFTER
public function getProfile(Request $request, Response $response): Response {
    $userId = $request->getAttribute('user_id');
```

#### 1.3 CSRF Protection for Web Forms (1h)

**Create:** `src/app/Middleware/CsrfMiddleware.php`

```php
<?php
namespace CiInbox\App\Middleware;

class CsrfMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Generate token if not exists
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Validate token for POST/PUT/DELETE
        if (in_array($request->getMethod(), ['POST', 'PUT', 'DELETE'])) {
            $token = $request->getParsedBody()['csrf_token'] ?? '';
            $sessionToken = $_SESSION['csrf_token'] ?? '';

            if (!hash_equals($sessionToken, $token)) {
                $response = new Response();
                $response->getBody()->write('CSRF token mismatch');
                return $response->withStatus(403);
            }
        }

        return $handler->handle($request);
    }
}
```

#### 1.4 Container Registration

**Update:** `src/config/container.php`

```php
// Middleware
AuthMiddleware::class => DI\autowire(),
CsrfMiddleware::class => DI\autowire(),
RateLimitMiddleware::class => DI\autowire(),
```

---

### **Phase 2: Security Hardening** ⏱️ 3-4h | 🟠 HIGH

#### 2.1 Rate Limiting (2h)

**Create:** `src/app/Middleware/RateLimitMiddleware.php`

```php
<?php
namespace CiInbox\App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class RateLimitMiddleware
{
    private array $limits = [
        'login' => ['max' => 5, 'window' => 900],  // 5 attempts per 15 min
        'api' => ['max' => 60, 'window' => 60],    // 60 requests per minute
    ];

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        $path = $request->getUri()->getPath();
        
        // Determine rate limit type
        $limitType = 'api';
        if (str_contains($path, '/auth/login')) {
            $limitType = 'login';
        }
        
        $key = "rate_limit:{$limitType}:{$ip}";
        $cacheFile = __DIR__ . "/../../data/cache/{$key}";
        
        // Check rate limit
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            $count = $data['count'] ?? 0;
            $resetTime = $data['reset'] ?? 0;
            
            if (time() < $resetTime) {
                if ($count >= $this->limits[$limitType]['max']) {
                    $response = new Response();
                    $response->getBody()->write(json_encode([
                        'success' => false,
                        'error' => 'Rate limit exceeded',
                        'retry_after' => $resetTime - time()
                    ]));
                    return $response
                        ->withHeader('Content-Type', 'application/json')
                        ->withStatus(429);
                }
                $data['count']++;
            } else {
                // Reset counter
                $data = [
                    'count' => 1,
                    'reset' => time() + $this->limits[$limitType]['window']
                ];
            }
        } else {
            $data = [
                'count' => 1,
                'reset' => time() + $this->limits[$limitType]['window']
            ];
        }
        
        // Save updated counter
        @mkdir(dirname($cacheFile), 0755, true);
        file_put_contents($cacheFile, json_encode($data));
        
        return $handler->handle($request);
    }
}
```

#### 2.2 Input Validation Service (2h)

**Create:** `src/app/Services/ValidationService.php`

```php
<?php
namespace CiInbox\App\Services;

class ValidationService
{
    public function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function validateThreadId(mixed $id): int
    {
        if (!is_numeric($id) || $id < 1) {
            throw new \InvalidArgumentException('Invalid thread ID');
        }
        return (int)$id;
    }

    public function validatePagination(array $params): array
    {
        $page = max(1, (int)($params['page'] ?? 1));
        $perPage = min(100, max(1, (int)($params['per_page'] ?? 20)));
        
        return ['page' => $page, 'per_page' => $perPage];
    }

    public function sanitizeHtml(string $html): string
    {
        // Use HTMLPurifier for production
        return strip_tags($html, '<p><br><strong><em><ul><ol><li><a>');
    }

    public function validatePassword(string $password): bool
    {
        // Min 8 chars, 1 uppercase, 1 lowercase, 1 number
        return strlen($password) >= 8 &&
               preg_match('/[A-Z]/', $password) &&
               preg_match('/[a-z]/', $password) &&
               preg_match('/[0-9]/', $password);
    }
}
```

---

### **Phase 3: Error Handling & Logging** ⏱️ 2-3h | 🟠 MEDIUM

#### 3.1 Global Error Handler

**Create:** `src/app/Middleware/ErrorHandlerMiddleware.php`

```php
<?php
namespace CiInbox\App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use CiInbox\Modules\Logger\LoggerInterface;

class ErrorHandlerMiddleware
{
    private LoggerInterface $logger;
    private bool $debug;

    public function __construct(LoggerInterface $logger, bool $debug = false)
    {
        $this->logger = $logger;
        $this->debug = $debug;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        try {
            return $handler->handle($request);
        } catch (\Exception $e) {
            $this->logger->error('Unhandled exception', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            $response = new Response();
            $errorData = [
                'success' => false,
                'error' => $this->debug ? $e->getMessage() : 'Internal server error'
            ];

            if ($this->debug) {
                $errorData['debug'] = [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ];
            }

            $response->getBody()->write(json_encode($errorData));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}
```

#### 3.2 Audit Log Enhancement

**Update:** `src/app/Services/AuditLogService.php` (create if not exists)

```php
<?php
namespace CiInbox\App\Services;

use CiInbox\Modules\Logger\LoggerInterface;

class AuditLogService
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function logAction(string $action, int $userId, array $context = []): void
    {
        $this->logger->info("AUDIT: {$action}", array_merge([
            'user_id' => $userId,
            'timestamp' => time(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ], $context));
    }

    // Specific audit methods
    public function logThreadAssignment(int $threadId, int $userId, int $assignedTo): void
    {
        $this->logAction('thread_assigned', $userId, [
            'thread_id' => $threadId,
            'assigned_to' => $assignedTo
        ]);
    }

    public function logEmailSent(int $threadId, int $userId, string $to): void
    {
        $this->logAction('email_sent', $userId, [
            'thread_id' => $threadId,
            'recipient' => $to
        ]);
    }

    public function logStatusChange(int $threadId, int $userId, string $from, string $to): void
    {
        $this->logAction('status_changed', $userId, [
            'thread_id' => $threadId,
            'from_status' => $from,
            'to_status' => $to
        ]);
    }
}
```

---

### **Phase 4: Production Configuration** ⏱️ 2h | 🟡 MEDIUM

#### 4.1 Environment Configuration

**Update:** `.env.example`

```dotenv
# Production-specific settings
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Security
SESSION_SECURE=true          # HTTPS only
SESSION_HTTPONLY=true
SESSION_SAMESITE=Strict

# Rate Limiting
RATE_LIMIT_LOGIN=5           # Max login attempts per 15 min
RATE_LIMIT_API=60            # Max API requests per minute

# Monitoring
ENABLE_AUDIT_LOG=true
LOG_LEVEL=info               # production: info, development: debug

# Backup
BACKUP_ENABLED=true
BACKUP_RETENTION_DAYS=30
```

#### 4.2 Production Health Checks

**Create:** `src/app/Controllers/SystemHealthController.php` (bereits vorhanden, erweitern)

**Add endpoints:**
- `GET /api/system/health` - Basic health (DB, Disk, IMAP)
- `GET /api/system/health/detailed` - Full diagnostic (Admin only)
- `GET /api/system/metrics` - Performance metrics

---

### **Phase 5: Deployment Preparation** ⏱️ 3-4h | 🟢 LOW

#### 5.1 Production Deployment Guide

**Create:** `docs/admin/DEPLOYMENT.md`

**Topics:**
- Server requirements (PHP 8.1+, MySQL 8.0+, SSL)
- Apache/Nginx configuration
- SSL certificate setup (Let's Encrypt)
- Database setup & migrations
- Environment configuration
- Webcron setup (cron-job.org)
- Backup strategy
- Monitoring setup

#### 5.2 Installation Script

**Create:** `scripts/production-setup.php`

```bash
php scripts/production-setup.php

=== CI-Inbox Production Setup ===

1. Checking PHP version... ✓ 8.1.12
2. Checking extensions... ✓ All required
3. Checking permissions... ✓ OK
4. Database connection... ✓ Connected
5. Running migrations... ✓ 17 migrations applied
6. Generating keys... ✓ Encryption & CRON tokens created
7. Creating admin user... ✓ admin@example.com
8. Testing IMAP... ✓ Connection successful

Setup complete! Next steps:
- Configure IMAP accounts in Admin Panel
- Set up external cron: https://your-domain.com/webhooks/poll-emails
- Enable SSL (Let's Encrypt recommended)
```

#### 5.3 Security Checklist

**Create:** `docs/admin/SECURITY-CHECKLIST.md`

```markdown
# Pre-Production Security Checklist

## Environment
- [ ] APP_ENV=production
- [ ] APP_DEBUG=false
- [ ] SSL certificate installed (HTTPS)
- [ ] SESSION_SECURE=true
- [ ] Firewall configured (only 80/443 open)

## Secrets
- [ ] Unique ENCRYPTION_KEY (32 bytes)
- [ ] Unique CRON_SECRET_TOKEN (32 bytes)
- [ ] Strong database password
- [ ] .env not accessible via web

## Permissions
- [ ] data/ directory writable (0755)
- [ ] logs/ directory writable (0755)
- [ ] vendor/ NOT web-accessible
- [ ] .env NOT web-accessible

## Database
- [ ] Migrations applied
- [ ] Admin user created
- [ ] Backup strategy configured

## Monitoring
- [ ] Log rotation enabled
- [ ] Health check endpoint tested
- [ ] Webcron monitoring active
```

---

### **Phase 6: Testing & Validation** ⏱️ 2-3h | 🟢 LOW

#### 6.1 Production Smoke Tests

**Create:** `tests/production/smoke-test.php`

```php
<?php
/**
 * Production Smoke Test
 * 
 * Validates critical functionality before deployment
 */

$tests = [
    'Database Connection',
    'Migrations Applied',
    'IMAP Connection',
    'SMTP Connection',
    'Auth System',
    'API Authentication',
    'Rate Limiting',
    'Webcron Token Auth',
    'Log System',
    'Encryption Service'
];

$passed = 0;
$failed = 0;

foreach ($tests as $test) {
    echo "Testing: {$test}... ";
    // Run test
    if (runTest($test)) {
        echo "✓\n";
        $passed++;
    } else {
        echo "✗\n";
        $failed++;
    }
}

echo "\nResults: {$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
```

---

## 📋 Implementation Priority

### 🔴 **CRITICAL (Do First - Day 1)**
1. Phase 1.1: API Auth Middleware (2h)
2. Phase 1.2: Fix 23 TODOs (2h)
3. Phase 2.1: Rate Limiting (2h)

### 🟠 **HIGH (Day 2)**
4. Phase 1.3: CSRF Protection (1h)
5. Phase 2.2: Input Validation (2h)
6. Phase 3.1: Error Handling (2h)

### 🟡 **MEDIUM (Day 2-3)**
7. Phase 3.2: Audit Log (1h)
8. Phase 4.1: Production Config (1h)
9. Phase 4.2: Health Checks (1h)

### 🟢 **LOW (Day 3)**
10. Phase 5: Deployment Docs (3h)
11. Phase 6: Testing (2h)

---

## 📊 Estimated Timeline

| Day | Hours | Tasks | Outcome |
|-----|-------|-------|---------|
| **Day 1** | 6h | Auth + Rate Limiting | API secured |
| **Day 2** | 6h | CSRF + Validation + Error Handling | Hardened |
| **Day 3** | 4h | Config + Docs + Testing | Production-ready |
| **Total** | **16h** | **11 Major Tasks** | **✅ READY** |

---

## 🎯 Success Criteria

### Before Production Deployment:

- [ ] ✅ Zero TODOs in production code
- [ ] ✅ All API endpoints require authentication
- [ ] ✅ Rate limiting active (login + API)
- [ ] ✅ CSRF protection on all forms
- [ ] ✅ Input validation on all endpoints
- [ ] ✅ Error handling catches all exceptions
- [ ] ✅ Audit log tracks critical actions
- [ ] ✅ Production .env configured
- [ ] ✅ Deployment guide complete
- [ ] ✅ Security checklist passed
- [ ] ✅ Smoke tests pass

---

## 🚀 Next Steps

**Start with:**
```bash
# Day 1 Morning
1. Create AuthMiddleware.php
2. Update api.php routes
3. Fix 23 TODOs (user_id)

# Day 1 Afternoon
4. Create RateLimitMiddleware.php
5. Test auth + rate limiting

# Day 2
6. CSRF + Validation + Error Handling

# Day 3
7. Production config + docs + final testing
```

**Testing Strategy:**
- Manual testing after each phase
- Smoke tests before deployment
- Production monitoring after go-live

---

**Ende des Roadmaps - Ready for Production!** 🎉

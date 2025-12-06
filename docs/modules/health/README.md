# System Health Check & Monitoring

**Autor:** Hendrik Dreis  
**Lizenz:** MIT License

## Übersicht

Das System Health Check Modul bietet umfassende Überwachung der CI-Inbox Installation mit Integration in den Keep-it-easy Update-Server.

**Features:**
- ✅ Echtzeit-Monitoring von System-Ressourcen
- ✅ Module Health Checks (Logger, Config, Encryption, etc.)
- ✅ Database Performance Tracking
- ✅ Error & Performance Metriken
- ✅ Keep-it-easy UpdateServer Integration
- ✅ CLI Installation Verification Tool
- ✅ RESTful API Endpoints

---

## API Endpoints

### 1. Basic Health Check (Public)

**Endpoint:** `GET /api/system/health`

**Use Case:** Load Balancer Health Checks, Uptime Monitoring

**Response:**
```json
{
  "status": "ok",
  "timestamp": 1700000000,
  "version": "0.1.0",
  "checks": {
    "system": true,
    "database": true
  }
}
```

**HTTP Status:**
- `200 OK` - System is healthy
- `503 Service Unavailable` - System has critical issues

---

### 2. Detailed Health Check (Authenticated)

**Endpoint:** `GET /api/system/health/detailed`

**Use Case:** Administrative Monitoring, Debugging

**Response:**
```json
{
  "timestamp": 1700000000,
  "version": "0.1.0",
  "installation_id": "ci-inbox-hostname",
  "system": {
    "php_version": "8.2.12",
    "memory_usage": 47185920,
    "memory_usage_percentage": 17.6,
    "disk_free_mb": 2048,
    "disk_usage_percentage": 80.0,
    "extensions": ["openssl", "pdo_mysql", "imap"]
  },
  "database": {
    "connection_status": "ok",
    "latency_ms": 5,
    "threads_total": 145,
    "emails_total": 342
  },
  "modules": {
    "logger": {
      "status": "ok",
      "test_passed": true,
      "metrics": { ... }
    },
    "config": { ... },
    "encryption": { ... }
  },
  "errors": {
    "php_errors_24h": 0,
    "php_warnings_24h": 2
  },
  "performance": {
    "avg_response_time_ms": 145,
    "uptime_percentage": 99.8
  },
  "analysis": {
    "overall_status": "healthy",
    "is_healthy": true,
    "issues": [],
    "warnings": [],
    "recommendations": []
  }
}
```

---

### 3. UpdateServer Report (Keep-it-easy)

**Endpoint:** `GET /api/system/health/report`

**Use Case:** Keep-it-easy UpdateServer Polling

**Response:**
```json
{
  "installation_id": "ci-inbox-abc123",
  "timestamp": 1700000000,
  "version": "0.1.0",
  "system": {
    "php_version": "8.2.12",
    "memory_usage": 47185920,
    "disk_free": 2147483648,
    "disk_total": 10737418240
  },
  "data": {
    "submissions_count": 145,
    "maintainers_count": 5,
    "buildings_count": 3
  },
  "mail": {
    "sent_24h": 8,
    "failed_24h": 0
  },
  "errors": {
    "php_errors_24h": 0,
    "http_errors_24h": 1
  }
}
```

---

### 4. Send Report to UpdateServer (Push)

**Endpoint:** `POST /api/system/health/send`

**Use Case:** Manual triggering of health report push

**Configuration Required (.env):**
```env
UPDATE_SERVER_ENABLED=true
UPDATE_SERVER_URL=https://updates.keep-it-easy.de
UPDATE_SERVER_TOKEN=your_installation_token
INSTALLATION_ID=ci-inbox-unique-id
```

**Response:**
```json
{
  "success": true,
  "message": "Report sent successfully",
  "installation_id": "ci-inbox-abc123",
  "server_response": {
    "success": true,
    "status": "Healthy"
  }
}
```

---

## CLI Installation Verification

**Script:** `scripts/verify-installation.php`

### Usage

```bash
# Basic check
php scripts/verify-installation.php

# Verbose output with detailed metrics
php scripts/verify-installation.php --verbose
```

### Checks Performed

1. **PHP Environment**
   - PHP Version >= 8.1
   - Required Extensions (openssl, pdo, imap, mbstring, json, curl, etc.)
   - Memory Limit >= 128M

2. **File System**
   - Required directories exist and writable
   - Vendor directory present

3. **Configuration**
   - .env file exists
   - Required ENV variables configured
   - Encryption key valid (32 bytes)

4. **Database**
   - Connection successful
   - All tables exist
   - Record counts (verbose mode)

5. **System Health (API)**
   - Overall health status
   - Module health checks
   - Issues and warnings

### Exit Codes

- `0` - All checks passed
- `1` - One or more critical checks failed
- `2` - Script execution error

### Example Output

```
╔════════════════════════════════════════════════╗
║  CI-Inbox Installation Verification           ║
╚════════════════════════════════════════════════╝

==================================================
PHP Environment
==================================================

✔ PHP Version >= 8.1 → Current: 8.2.12
✔ Extension: openssl → Encryption support
✔ Extension: pdo → Database connectivity
✔ Memory Limit >= 128M → Current: 256M

==================================================
Database
==================================================

✔ Database connection → ci_inbox@localhost
✔ Table: users
✔ Table: threads

==================================================
System Health (API)
==================================================

✔ Overall system status → healthy
✔ Module: logger → Status: ok
✔ Module: config → Status: ok
✔ Module: encryption → Status: ok

==================================================
Total Checks: 35
Passed: 35
Failed: 0
==================================================

✔ All critical checks passed!
Installation is ready for production!
```

---

## Module Health Interface

Jedes Modul kann Health-Checks implementieren durch das `ModuleHealthInterface`:

```php
interface ModuleHealthInterface {
    public function getHealthStatus(): ModuleHealthDTO;
    public function runHealthTest(): bool;
    public function getModuleName(): string;
}
```

### Implementierte Module

**1. Logger Module**
- ✅ Log file writable check
- ✅ Log size monitoring
- ✅ Write test

**2. Config Module**
- ✅ .env file exists
- ✅ Required keys present
- ✅ Configuration loading

**3. Encryption Module**
- ✅ OpenSSL extension loaded
- ✅ Key valid (32 bytes)
- ✅ Encrypt/Decrypt round-trip test

---

## Keep-it-easy UpdateServer Integration

### Setup

1. **Register Installation on UpdateServer**
   - Get `INSTALLATION_ID` and `UPDATE_SERVER_TOKEN`

2. **Configure .env**
```env
UPDATE_SERVER_ENABLED=true
UPDATE_SERVER_URL=https://updates.keep-it-easy.de
UPDATE_SERVER_TOKEN=your_server_token
INSTALLATION_ID=ci-inbox-your-unique-id
```

3. **Configure Cron (on UpdateServer)**
```bash
# UpdateServer cron pulls health from all installations
0 */6 * * * php /path/to/update_system/cron/health_check.php
```

### Data Flow

**Pull Model (Server → Client):**
```
UpdateServer (Cron) → GET /api/system/health/report → CI-Inbox
```

**Push Model (Client → Server):**
```
CI-Inbox → POST /api/v1/health_api.php → UpdateServer
```

### Authentication

**Request Headers:**
```
X-Server-Token: your_installation_token
Content-Type: application/json
```

---

## Testing

### Manual API Test

```bash
# 1. Basic Health
curl http://localhost:8000/api/system/health

# 2. Detailed Health
curl http://localhost:8000/api/system/health/detailed

# 3. UpdateServer Report
curl http://localhost:8000/api/system/health/report

# 4. Send to UpdateServer
curl -X POST http://localhost:8000/api/system/health/send
```

### PHP Test Script

```bash
php tests/manual/system-health-test.php
```

**Output:**
```
=== CI-Inbox System Health - Manual Test ===

✓ SystemHealthService loaded

--- Test 1: Basic Health ---
Status: ok
Version: 0.1.0

--- Test 2: System Metrics ---
PHP Version: 8.2.12
Memory Usage: 45.12 MB
...

=== All Tests Complete ===
✓ SystemHealthService is working correctly!
```

---

## Architecture

### Service Layer

**SystemHealthService** (`src/app/Services/SystemHealthService.php`)
- Central health check orchestration
- Module registration and aggregation
- Metrics collection
- Analysis and reporting

### DTOs

**ModuleHealthDTO** (`src/app/DTOs/ModuleHealthDTO.php`)
- Status: ok | warning | critical | error
- Metrics dictionary
- Test result

**HealthAnalysisDTO** (`src/app/DTOs/HealthAnalysisDTO.php`)
- Overall status
- Issues and warnings
- Recommendations

### Controller

**SystemHealthController** (`src/app/Controllers/SystemHealthController.php`)
- RESTful API endpoints
- UpdateServer integration
- Authentication handling

---

## Metrics Reference

### System Metrics

| Metric | Description | Threshold |
|--------|-------------|-----------|
| `memory_usage_percentage` | PHP memory usage | Warning: >80%, Critical: >90% |
| `disk_usage_percentage` | Disk space usage | Warning: >80%, Critical: >90% |
| `php_version` | PHP version | >= 8.1 required |
| `extensions` | Loaded PHP extensions | Required: openssl, pdo, imap |

### Database Metrics

| Metric | Description |
|--------|-------------|
| `connection_status` | Database connection ok/error |
| `latency_ms` | Query response time (ms) |
| `threads_total` | Total email threads |
| `emails_total` | Total emails stored |

### Error Metrics

| Metric | Description | Threshold |
|--------|-------------|-----------|
| `php_errors_24h` | PHP errors in last 24h | Warning: >10, Critical: >50 |
| `php_warnings_24h` | PHP warnings in last 24h | Info only |
| `http_errors_24h` | HTTP errors in last 24h | Warning: >10 |

---

## Troubleshooting

### Health Check Returns 503

**Possible Causes:**
- Database connection failed
- Disk usage > 95%
- Critical module failure

**Resolution:**
```bash
# Check detailed health
curl http://localhost:8000/api/system/health/detailed

# Run verification script
php scripts/verify-installation.php --verbose
```

### Module Test Failed

**Diagnosis:**
```bash
php tests/manual/system-health-test.php
```

Check module-specific metrics in output.

### UpdateServer Integration Not Working

**Check Configuration:**
```bash
# Verify ENV variables
php -r "require 'vendor/autoload.php'; \$dotenv = Dotenv\Dotenv::createImmutable(__DIR__); \$dotenv->load(); var_dump(\$_ENV['UPDATE_SERVER_ENABLED'], \$_ENV['UPDATE_SERVER_URL'], \$_ENV['UPDATE_SERVER_TOKEN']);"
```

**Test Manually:**
```bash
curl -X POST http://localhost:8000/api/system/health/send
```

---

## Future Enhancements

- [ ] Performance Metrics via APM Integration
- [ ] Mail Tracking (sent/failed counts)
- [ ] Backup System Integration
- [ ] IMAP Module Health Checks
- [ ] Webcron Module Health Checks
- [ ] Real-time WebSocket Health Updates
- [ ] Prometheus Metrics Export
- [ ] Grafana Dashboard Templates

---

## See Also

- [M5-Sprint-5.3-System-Health-Check.md](./[WIP] M5-Sprint-5.3-System-Health-Check.md) - Detailed implementation documentation
- [Keep-it-easy Health Monitoring System](../../../../../../Keep-it-easy/update_system/docs/concepts/04_health_monitoring_system.md)
- [architecture.md](./architecture.md) - Overall system architecture

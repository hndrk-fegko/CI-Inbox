# [WIP] M5 Sprint 5.3: System Health-Check & UpdateServer Integration

**Milestone:** M5 - Production Ready  
**Sprint:** 5.3 (geplant)  
**Geschätzte Dauer:** 2 Tage  
**Status:** 🔄 IN PROGRESS  
**Start:** 18. November 2025

---

## 🎯 Ziel

Implementierung eines vollständigen System Health-Check Systems mit Integration in den Keep-it-easy Update-Server für zentrale Überwachung und Management von CI-Inbox Installationen.

**Features:**
- **F6.3** - Webcron-Monitoring (SHOULD) - System Health Tracking
- **F7.1** - Installation Verification Script
- **UpdateServer Integration** - Reporting an Keep-it-easy Update-Server

---

## 📋 Architektur-Planung

### Integration mit Keep-it-easy Update-Server

Basierend auf: `Keep-it-easy\update_system\docs\concepts\04_health_monitoring_system.md`

**Update-Server Architektur:**
1. **Server-seitig**: Cron-Job ruft täglich alle registrierten Installationen auf
2. **Client-seitig**: CI-Inbox beantwortet Health-Check-Requests mit JSON-Report
3. **Bidirektional**: Installation kann auch proaktiv Reports senden

**API-Endpoints:**

```
Client (CI-Inbox):
- GET /api/system/health              → Öffentlicher Health-Check (basic)
- GET /api/system/health/detailed     → Detaillierter Health-Check (auth required)
- POST /api/system/health/report      → Sende Report an Update-Server

Server (Keep-it-easy):
- POST /api/v1/health_api.php         → Empfange Health-Report von Installation
```

---

## 📊 Health-Metriken (Keep-it-easy kompatibel)

### System-Metriken
```json
{
  "system": {
    "php_version": "8.2.12",
    "memory_usage": 47185920,           // bytes
    "memory_limit": 268435456,           // bytes
    "memory_usage_percentage": 17.6,
    "disk_free_mb": 2048,
    "disk_total_mb": 10240,
    "disk_usage_percentage": 80.0,
    "server_load": [0.5, 0.7, 0.6],
    "extensions": ["openssl", "pdo_mysql", "imap", "mbstring"]
  }
}
```

### Database-Metriken
```json
{
  "database": {
    "connection_status": "ok",
    "latency_ms": 5,
    "migrations_current": 12,
    "migrations_pending": 0,
    "threads_total": 145,
    "threads_open": 12,
    "emails_total": 342,
    "users_count": 5,
    "imap_accounts_count": 3
  }
}
```

### Module-Health
```json
{
  "modules": {
    "logger": {
      "status": "ok",
      "log_file_writable": true,
      "log_size_mb": 2.4,
      "last_log_entry": 1730937600
    },
    "config": {
      "status": "ok",
      "env_loaded": true,
      "required_keys_present": true
    },
    "encryption": {
      "status": "ok",
      "key_valid": true,
      "test_passed": true
    },
    "imap": {
      "status": "ok",
      "extension_loaded": true,
      "accounts_configured": 3,
      "last_sync": 1730937600
    },
    "smtp": {
      "status": "ok",
      "last_email_sent": 1730937600,
      "emails_sent_24h": 8,
      "emails_failed_24h": 0
    },
    "webcron": {
      "status": "ok",
      "last_poll": 1730937600,
      "avg_duration_ms": 2340,
      "emails_fetched_24h": 42
    }
  }
}
```

### Errors & Performance
```json
{
  "errors": {
    "php_errors_24h": 0,
    "php_warnings_24h": 2,
    "http_errors_24h": 1,
    "last_error_timestamp": 1730937600,
    "error_log_size_mb": 5.2
  },
  "performance": {
    "avg_response_time_ms": 145,
    "max_response_time_ms": 850,
    "uptime_percentage": 99.8,
    "uptime_last_check": 1730937600,
    "requests_24h": 342
  }
}
```

---

## 🏗️ Implementation Plan

### 1. SystemHealthService (src/app/Services/)

**Verantwortlichkeiten:**
- Aggregiert Health-Status von allen Modulen
- Berechnet System-Metriken (Memory, Disk, Load)
- Analysiert Database-Status
- Führt Module-Tests aus
- Generiert Health-Report im Keep-it-easy Format

```php
interface SystemHealthServiceInterface {
    public function getBasicHealth(): array;
    public function getDetailedHealth(): array;
    public function runModuleTests(): array;
    public function getSystemMetrics(): array;
    public function getDatabaseMetrics(): array;
    public function analyzeHealth(array $health): HealthAnalysisDTO;
}
```

### 2. Module Health Interfaces

Jedes Modul implementiert `ModuleHealthInterface`:

```php
interface ModuleHealthInterface {
    public function getHealthStatus(): ModuleHealthDTO;
    public function runHealthTest(): bool;
}
```

**Module:**
- LoggerModule: Log-File-Writeable Test
- ConfigModule: ENV-Keys Present Test
- EncryptionModule: Encrypt/Decrypt Round-Trip Test
- ImapModule: Connection Test (optional, expensive)
- SmtpModule: Configuration Valid Test
- WebcronModule: Last-Poll-Status

### 3. SystemHealthController (src/app/Controllers/)

```php
class SystemHealthController {
    public function basic(Request $request, Response $response): Response;
    public function detailed(Request $request, Response $response): Response;
    public function sendReport(Request $request, Response $response): Response;
}
```

### 4. UpdateServer Integration

**Client-Konfiguration (.env):**
```
# Keep-it-easy Update-Server Integration
UPDATE_SERVER_ENABLED=true
UPDATE_SERVER_URL=https://updates.keep-it-easy.de
UPDATE_SERVER_TOKEN=<installation_token>
INSTALLATION_ID=ci-inbox-<unique-id>
```

**Report-Service:**
```php
class UpdateServerReportService {
    public function sendHealthReport(): bool;
    public function registerInstallation(): bool;
    public function checkForUpdates(): UpdateCheckDTO;
}
```

### 5. Installation Verification Script

**scripts/verify-installation.php:**
- CLI-Tool für Production Readiness Check
- Prüft alle Anforderungen
- Generiert Report
- Exit-Codes für CI/CD

```bash
php scripts/verify-installation.php

=== CI-Inbox Installation Verification ===

Environment Check:
✅ PHP Version: 8.2.12 (required: >= 8.1)
✅ Required Extensions: openssl, pdo_mysql, imap, mbstring
✅ .env file exists
✅ Encryption key configured
✅ Database credentials configured

Database Check:
✅ Database Connection: OK (mysql)
✅ Migrations Status: 12/12 up-to-date

Module Tests:
✅ Logger Module: PASSED
✅ Config Module: PASSED
✅ Encryption Module: PASSED
✅ IMAP Module: PASSED (3 accounts configured)

File Permissions:
✅ logs/ writable
✅ data/cache/ writable
✅ data/sessions/ writable
✅ data/uploads/ writable

Status: All checks passed ✅
Installation ready for production!
```

---

## 🔧 Implementierungs-Reihenfolge

1. ✅ **Planning & Architecture** (dieses Dokument)
2. ✅ **ModuleHealthInterface** - Interface für Module
3. ✅ **SystemHealthService** - Core Business Logic
4. ✅ **Module Health Implementations** - Logger, Config, Encryption
5. ✅ **SystemHealthController** - API Endpoints
6. ⏳ **UpdateServerReportService** - Keep-it-easy Integration (included in Controller)
7. ✅ **Installation Verification Script** - CLI Tool
8. ⏳ **Testing** - Unit & Integration Tests
9. ⏳ **Documentation** - Complete this document

---

## 📝 Keep-it-easy Data Contract

### Health Report POST to Update-Server

**Endpoint:** `POST https://updates.keep-it-easy.de/api/v1/health_api.php`

**Headers:**
```
Content-Type: application/json
X-Server-Token: <installation_token>
```

**Body:**
```json
{
  "installation_id": "ci-inbox-abc123",
  "timestamp": 1730937600,
  "version": "0.1.0",
  "system": {
    "php_version": "8.2.12",
    "memory_usage": 47185920,
    "disk_free": 2147483648,
    "disk_total": 10737418240
  },
  "data": {
    "submissions_count": 145,      // threads_total
    "maintainers_count": 5,        // users_count
    "buildings_count": 3           // imap_accounts_count
  },
  "backups": {
    "last_backup": 0,              // TODO: Implement Backup-System
    "backup_count": 0,
    "total_backup_size": 0
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

**Response:**
```json
{
  "success": true,
  "status": "Healthy",           // Healthy | Warning | Critical
  "issues": []
}
```

---

## 🚀 Next Steps

1. Implementiere `ModuleHealthInterface` als Basis
2. Implementiere `SystemHealthService` mit Metrik-Collection
3. Erweitere Module um Health-Check Methods
4. Erstelle `SystemHealthController` und registriere Routes
5. Implementiere UpdateServer-Integration
6. Erstelle CLI Verification Script
7. Testing & Documentation

---

**Status:** Planning complete, ready for implementation

---

## 📦 Implementierte Komponenten

### 1. Core DTOs und Interfaces

**Dateien:**
- `src/app/DTOs/ModuleHealthDTO.php` - Health-Status eines Moduls
- `src/app/DTOs/HealthAnalysisDTO.php` - Gesamtanalyse des System-Status
- `src/app/Interfaces/ModuleHealthInterface.php` - Interface für Module

**Features:**
- Status-Typen: `ok`, `warning`, `critical`, `error`
- Metriken-Support für Module
- Automatische Analyse von Health-Daten

### 2. SystemHealthService

**Datei:** `src/app/Services/SystemHealthService.php`

**Implementierte Methoden:**
- `getBasicHealth()` - Einfacher öffentlicher Health-Check
- `getDetailedHealth()` - Detaillierter Health-Check (authentifiziert)
- `getSystemMetrics()` - PHP, Memory, Disk, Extensions
- `getDatabaseMetrics()` - Connection, Latency, Record Counts
- `getModulesHealth()` - Health-Status aller registrierten Module
- `getErrorMetrics()` - Log-Analyse (24h Fenster)
- `getPerformanceMetrics()` - Performance-Tracking (TODO: APM)
- `analyzeHealth()` - Gesamtauswertung mit Empfehlungen
- `generateUpdateServerReport()` - Keep-it-easy kompatibles Format

**Module-Registration:**
```php
$healthService = new SystemHealthService($logger);
$healthService->registerModule($loggerService);
$healthService->registerModule($configService);
$healthService->registerModule($encryptionService);
```

### 3. Module Health Implementations

**LoggerService** (`src/modules/logger/src/LoggerService.php`):
- Prüft: Log-File Writable, Log-Size, Last Modified
- Test: Testlog schreiben

**ConfigService** (`src/modules/config/src/ConfigService.php`):
- Prüft: .env exists, Required Keys Present
- Test: Kritische Keys lesbar

**EncryptionService** (`src/modules/encryption/src/EncryptionService.php`):
- Prüft: OpenSSL loaded, Key valid, Key length (32 bytes)
- Test: Encrypt/Decrypt Round-Trip

### 4. SystemHealthController

**Datei:** `src/app/Controllers/SystemHealthController.php`

**API Endpoints:**
```
GET  /api/system/health          - Basic health (public)
GET  /api/system/health/detailed - Detailed health (auth)
GET  /api/system/health/report   - UpdateServer report
POST /api/system/health/send     - Send to UpdateServer
```

**Features:**
- HTTP Status Codes: 200 (ok), 503 (critical)
- JSON responses mit Pretty Print
- UpdateServer Integration (curl-based)
- Authentication via X-Server-Token Header

### 5. Dependency Injection

**Datei:** `src/config/container.php`

**Registrierungen:**
```php
SystemHealthService::class => function($container) {
    $healthService = new SystemHealthService($container->get(LoggerService::class));
    $healthService->registerModule($container->get(LoggerService::class));
    $healthService->registerModule($container->get(ConfigService::class));
    $healthService->registerModule($container->get(EncryptionService::class));
    return $healthService;
},

SystemHealthController::class => function($container) {
    return new SystemHealthController(
        $container->get(SystemHealthService::class),
        $container->get(LoggerService::class)
    );
},
```

### 6. Installation Verification Script

**Datei:** `scripts/verify-installation.php`

**Features:**
- CLI-Interface mit Colors
- Checks:
  - PHP Version >= 8.1
  - Required Extensions (openssl, pdo, imap, etc.)
  - Memory Limit >= 128M
  - File System (directories writable)
  - .env Configuration
  - Database Connection & Tables
  - System Health API
- Exit Codes: 0 (success), 1 (failed), 2 (error)
- Verbose Mode: `php scripts/verify-installation.php --verbose`

**Example Output:**
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
...

==================================================
Database
==================================================

✔ Database connection → ci_inbox@localhost
✔ Table: users
✔ Table: threads
...

Total Checks: 42
Passed: 42
Failed: 0

✔ All critical checks passed!
Installation is ready for production!
```

---

## 🧪 Testing (TODO)

### Unit Tests

**Zu erstellen:**
- `tests/unit/Services/SystemHealthServiceTest.php`
- `tests/unit/DTOs/ModuleHealthDTOTest.php`
- `tests/unit/DTOs/HealthAnalysisDTOTest.php`

**Tests:**
- Module Registration
- Metrics Collection
- Health Analysis Logic
- UpdateServer Report Generation

### Integration Tests

**Zu erstellen:**
- `tests/integration/SystemHealthApiTest.php`

**Tests:**
- GET /api/system/health
- GET /api/system/health/detailed
- GET /api/system/health/report
- POST /api/system/health/send

### Manual Testing

```bash
# 1. Verification Script
php scripts/verify-installation.php --verbose

# 2. Basic Health Check
curl http://localhost:8000/api/system/health

# 3. Detailed Health Check
curl http://localhost:8000/api/system/health/detailed

# 4. UpdateServer Report
curl http://localhost:8000/api/system/health/report

# 5. Send to UpdateServer (with config)
curl -X POST http://localhost:8000/api/system/health/send
```

---

## 📚 Nächste Schritte

1. **Tests schreiben** - Unit & Integration Tests
2. **Documentation vervollständigen** - Dieses Dokument finalisieren
3. **IMAP Module Health** - Health-Check für ImapClient
4. **Webcron Module Health** - Health-Check für WebcronManager
5. **Performance Tracking** - APM Integration für getPerformanceMetrics()
6. **Backup System** - Implementierung für backup metrics
7. **Mail Tracking** - Implementierung für mail metrics (sent/failed)

---

**Status:** Core implementation complete, testing & docs pending

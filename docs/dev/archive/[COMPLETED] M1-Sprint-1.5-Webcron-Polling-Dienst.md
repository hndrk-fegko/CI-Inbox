# [COMPLETED] M1 Sprint 1.5: Webcron-Polling-Dienst

**Milestone:** M1 - IMAP Core  
**Sprint:** 1.5 (von 5)  
**Geschätzte Dauer:** 2-3 Tage  
**Tatsächliche Dauer:** ~3 Stunden  
**Status:** ✅ COMPLETED  
**Start:** 17. November 2025  
**Abgeschlossen:** 18. November 2025

---

## Ergebnis

✅ **Webcron-Polling-System vollständig implementiert:**

1. GET /webcron/poll - Trigger email polling for all accounts
2. GET /webcron/status - Monitor job status
3. GET /webcron/test - Test setup without fetching emails

**Features:**
- ✅ API Key + IP Whitelist Authentication
- ✅ Job Locking (prevents parallel execution)
- ✅ Internal API orchestration (calls ImapController::syncAccount)
- ✅ Aggregated results & error handling
- ✅ Status tracking & monitoring
- ✅ Manual test script included

---

## Ziel

Automatischer E-Mail-Polling-Dienst, der via Webcron-Endpoint alle IMAP-Accounts regelmäßig abruft und neue E-Mails verarbeitet. Der Service orchestriert IMAP-Client, Email-Parser und Thread-Manager zur vollständigen E-Mail-Verarbeitung.

**Feature:** F1.5 - IMAP Auto-Polling (inventar.md - MUST)

---

## Implementierung

### Code-Statistik

**Implementiert (~800 lines):**
- `src/modules/webcron/src/WebcronManagerInterface.php` (68 lines)
- `src/modules/webcron/src/WebcronManager.php` (265 lines)
- `src/modules/webcron/src/Exceptions/WebcronException.php` (50 lines)
- `src/modules/webcron/config/webcron.config.php` (60 lines)
- `src/modules/webcron/README.md` (500+ lines) - Comprehensive documentation
- `src/routes/webcron.php` (209 lines) - API endpoints
- `src/app/Controllers/ImapController.php` - syncAccount() method (478 lines total)
- `tests/manual/webcron-poll-test.php` (250 lines) - Test suite

**Integriert:**
- `src/config/container.php` (+15 lines) - Service registration
- `src/core/Application.php` (+6 lines) - Route loading

---

## Architektur

### Webcron → Internal API Pattern

```
┌───────────────────────────────────────────┐
│  External Cron Service (cron-job.org)     │
│  GET /webcron/poll?api_key=xxx            │
└─────────────────┬─────────────────────────┘
                  │
                  ▼
┌───────────────────────────────────────────┐
│  WebcronManager (Orchestrator)            │
│  - API Key + IP Validation                │
│  - Job Locking                            │
│  - Fetch Active Accounts                  │
│  - Internal HTTP Calls                    │
└─────────────────┬─────────────────────────┘
                  │
                  ▼ POST /api/imap/accounts/{id}/sync
┌───────────────────────────────────────────┐
│  ImapController::syncAccount()            │
│  - Connect IMAP                           │
│  - Fetch New Emails (CI-Synced filter)    │
│  - Parse → Thread → Store                 │
│  - Update Sync Timestamp                  │
└───────────────────────────────────────────┘
```

**Warum Internal API?**
- ✅ Trennung: Webcron-Logic ≠ IMAP-Logic
- ✅ Wiederverwendbar: Sync-API auch für UI/CLI nutzbar
- ✅ Testbar: Beide Komponenten isoliert testbar
- ✅ Skalierbar: Queue-basiertes Polling später leicht integrierbar

---

## Zu implementieren

```
src/modules/webcron/
├── src/
│   ├── WebcronManagerInterface.php  # Interface für Polling-Jobs
│   ├── WebcronManager.php           # Job-Orchestrator
│   └── Exceptions/
│       └── WebcronException.php     # Webcron-spezifische Exceptions
├── config/
│   └── webcron.config.php           # Polling-Intervalle, Limits
├── tests/
│   └── webcron-integration-test.php # E2E Test
└── README.md

src/app/Services/
├── PollingService.php               # E-Mail-Polling-Logik
└── EmailProcessingService.php       # E-Mail-Verarbeitung (Parser → Thread → DB)

src/app/Repositories/
└── ImapAccountRepository.php        # IMAP-Account CRUD

src/routes/
└── webcron.php                      # Route: GET /webcron/poll

**Geschätzt:** ~1,000 lines of code
```

---

## Komponenten-Übersicht

### 1. WebcronManager (Modul-Layer)
**Verantwortlich für:**
- Job-Definitionen verwalten
- Job-Scheduling koordinieren
- Job-Status tracking
- Keine Business-Logik (nur Orchestrierung)

### 2. PollingService (Business-Layer)
**Verantwortlich für:**
- IMAP-Accounts abrufen
- Pro Account: IMAP-Client triggern
- Neue UIDs identifizieren (Vergleich mit DB)
- E-Mails fetchen
- An EmailProcessingService übergeben

### 3. EmailProcessingService (Business-Layer)
**Verantwortlich für:**
- E-Mail parsen (EmailParser)
- Thread-Assignment (ThreadManager)
- Datenbank persistieren (Repositories)
- Labels auto-assignen (z.B. "Inbox")

### 4. ImapAccountRepository (Data-Layer)
**Verantwortlich für:**
- CRUD für IMAP-Accounts
- Query: Alle aktiven Accounts
- Last-Sync-Timestamp updaten

---

## WebcronManager Interface

```php
interface WebcronManagerInterface
{
    /**
     * Führt einen Polling-Job aus (alle IMAP-Accounts)
     * 
     * @return array Job-Ergebnis ['accounts_processed' => int, 'emails_fetched' => int, ...]
     */
    public function runPollingJob(): array;
    
    /**
     * Führt Polling für einen spezifischen Account aus
     * 
     * @param int $accountId IMAP-Account-ID
     * @return array Job-Ergebnis ['emails_fetched' => int, 'errors' => array]
     */
    public function pollAccount(int $accountId): array;
    
    /**
     * Holt Job-Status
     * 
     * @return array Status ['last_run' => timestamp, 'is_running' => bool, ...]
     */
    public function getJobStatus(): array;
    
    /**
     * Testet Webcron-Setup (Connectivity, Config)
     * 
     * @return array Test-Ergebnis ['success' => bool, 'checks' => array]
     */
    public function testSetup(): array;
}
```

---

## PollingService Interface

```php
class PollingService
{
    /**
     * Polling für alle aktiven IMAP-Accounts durchführen
     * 
     * @return array ['accounts_processed' => int, 'total_emails' => int, 'errors' => array]
     */
    public function pollAllAccounts(): array;
    
    /**
     * Polling für einen spezifischen Account
     * 
     * @param int $accountId IMAP-Account-ID
     * @return array ['emails_fetched' => int, 'new_threads' => int, 'errors' => array]
     */
    public function pollAccount(int $accountId): array;
    
    /**
     * Holt neue UIDs seit letztem Polling
     * 
     * @param int $accountId IMAP-Account-ID
     * @param string $folder IMAP-Folder (z.B. 'INBOX')
     * @return array UIDs
     */
    private function getNewUIDs(int $accountId, string $folder): array;
}
```

---

## EmailProcessingService Interface

```php
class EmailProcessingService
{
    /**
     * Verarbeitet eine E-Mail komplett (Parse → Thread → DB)
     * 
     * @param object $rawEmail Raw Email-Daten vom IMAP-Client
     * @param int $accountId IMAP-Account-ID
     * @return int Email-ID in DB
     */
    public function processEmail(object $rawEmail, int $accountId): int;
    
    /**
     * Batch-Verarbeitung mehrerer E-Mails
     * 
     * @param array $rawEmails Array von Raw Email-Daten
     * @param int $accountId IMAP-Account-ID
     * @return array ['processed' => int, 'failed' => int, 'email_ids' => array]
     */
    public function processEmailBatch(array $rawEmails, int $accountId): array;
    
    /**
     * Auto-Label-Assignment (z.B. "Inbox" für neue E-Mails)
     * 
     * @param int $threadId Thread-ID
     * @param string $folder IMAP-Folder
     * @return void
     */
    private function autoAssignLabels(int $threadId, string $folder): void;
}
```

---

## Webcron Config

**Datei:** `src/modules/webcron/config/webcron.config.php`

```php
return [
    // Polling-Intervall (Minuten)
    'polling_interval' => 5,
    
    // Max. E-Mails pro Polling-Durchlauf
    'max_emails_per_run' => 50,
    
    // Max. Accounts pro Durchlauf (für Rate-Limiting)
    'max_accounts_per_run' => 10,
    
    // Timeout pro Account (Sekunden)
    'account_timeout' => 30,
    
    // Ordner zum Abrufen
    'folders' => [
        'INBOX',
        'Sent', // Optional
    ],
    
    // Fehler-Behandlung
    'retry_failed_accounts' => true,
    'max_retries' => 3,
    
    // Job-Status Tracking
    'track_job_history' => true,
    'job_history_retention_days' => 30,
    
    // Security
    'allowed_ips' => [
        '127.0.0.1',      // Localhost (Dev)
        '::1',            // Localhost IPv6
        // '203.0.113.0',  // Production Webcron IP
    ],
    
    // API-Key für Webcron-Endpoint
    'api_key' => getenv('WEBCRON_API_KEY') ?: 'dev-secret-key-12345',
];
```

---

## Webcron Route

**Datei:** `src/routes/webcron.php`

```php
<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;

return function (RouteCollectorProxy $group) {
    
    /**
     * GET /webcron/poll
     * 
     * Triggert E-Mail-Polling für alle aktiven Accounts
     * 
     * Query-Parameter:
     * - api_key: Authentifizierung (required)
     * - account_id: Nur einen Account abrufen (optional)
     */
    $group->get('/poll', function (Request $request, Response $response) {
        $webcronManager = $this->get('WebcronManager');
        $config = $this->get('webcron.config');
        
        // 1. API-Key validieren
        $apiKey = $request->getQueryParams()['api_key'] ?? null;
        if ($apiKey !== $config['api_key']) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Invalid API key'
            ]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        
        // 2. IP-Whitelist prüfen
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!in_array($clientIp, $config['allowed_ips'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'IP not allowed'
            ]));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }
        
        // 3. Polling durchführen
        try {
            $accountId = $request->getQueryParams()['account_id'] ?? null;
            
            if ($accountId) {
                $result = $webcronManager->pollAccount((int)$accountId);
            } else {
                $result = $webcronManager->runPollingJob();
            }
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'result' => $result
            ]));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });
    
    /**
     * GET /webcron/status
     * 
     * Gibt Webcron-Status zurück
     */
    $group->get('/status', function (Request $request, Response $response) {
        $webcronManager = $this->get('WebcronManager');
        
        $status = $webcronManager->getJobStatus();
        
        $response->getBody()->write(json_encode([
            'success' => true,
            'status' => $status
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });
    
    /**
     * GET /webcron/test
     * 
     * Testet Webcron-Setup ohne E-Mails abzurufen
     */
    $group->get('/test', function (Request $request, Response $response) {
        $webcronManager = $this->get('WebcronManager');
        
        $testResult = $webcronManager->testSetup();
        
        $response->getBody()->write(json_encode([
            'success' => $testResult['success'],
            'checks' => $testResult['checks']
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });
};
```

---

## Implementierungs-Schritte

### Schritt 1: WebcronManager Interface & Implementation
- ✅ `WebcronManagerInterface.php` erstellt
- ✅ `WebcronManager.php` implementiert
- ✅ `WebcronException.php` erstellt
- ✅ Job-Orchestrierung implementiert
- ✅ Job-Locking implementiert

### Schritt 2: Internal API Integration
- ✅ Uses existing `ImapController::syncAccount()` endpoint
- ✅ Internal HTTP calls via cURL
- ✅ Result aggregation from multiple accounts

### Schritt 3: Webcron Config & Routes
- ✅ `webcron.config.php` erstellt
- ✅ `webcron.php` Routes erstellt
- ✅ API-Key & IP-Whitelist implementiert
- ✅ Routes in `Application.php` registriert

### Schritt 4: Documentation
- ✅ Comprehensive `README.md` erstellt
- ✅ API documentation mit Beispielen
- ✅ Troubleshooting guide
- ✅ Production setup instructions

### Schritt 5: Testing
- ✅ `webcron-poll-test.php` erstellt
- ✅ Manual testing erfolgreich
- ✅ All endpoints funktional

---

## Success Criteria

- ✅ Webcron-Endpoint erreichbar (GET /webcron/poll)
- ✅ API-Key-Authentifizierung funktioniert
- ✅ IP-Whitelist schützt Endpoint
- ✅ Polling ruft alle aktiven IMAP-Accounts ab
- ✅ Neue E-Mails werden erkannt (CI-Synced tag + DB-Check)
- ✅ E-Mails werden vollständig verarbeitet (Parse → Thread → DB)
- ✅ Job-Status wird geloggt und ist abrufbar
- ✅ Job-Locking verhindert parallele Ausführung
- ✅ Test-Script verfügbar (webcron-poll-test.php)
- ✅ Comprehensive README dokumentiert alle Features

---

## Test-Ergebnisse

**Test-Script:** `tests/manual/webcron-poll-test.php`

```bash
php tests/manual/webcron-poll-test.php
```

**Tests durchgeführt:**
1. ✅ GET /webcron/status - Status ohne Auth
2. ✅ GET /webcron/poll - Ohne API Key (401 Expected)
3. ✅ GET /webcron/poll?api_key=xxx - Full Sync
4. ✅ GET /webcron/poll?api_key=xxx&account_id=1 - Single Account

**Ergebnis:** Alle Tests bestanden ✅

---

## Verwendung

### Development (Manual Trigger)

```bash
# Via curl
curl "http://ci-inbox.local/webcron/poll?api_key=dev-secret-key-12345"

# Via Browser
http://ci-inbox.local/webcron/poll?api_key=dev-secret-key-12345
```

### Production (External Cron)

**Setup bei cron-job.org:**
1. Account erstellen
2. Neuer Cron Job:
   - URL: `https://your-domain.com/webcron/poll?api_key=YOUR_SECRET_KEY`
   - Intervall: Alle 5 Minuten
   - Methode: GET
3. IP in `allowed_ips` Config hinzufügen
4. Monitoring via `/webcron/status`

---

## Lessons Learned

### ✅ Was gut funktioniert hat

1. **Internal API Pattern:** Klare Trennung Webcron ↔ IMAP-Logic
2. **IMAP Tag + DB:** Hybrid-Deduplication (Performance + Reliability)
3. **Job Locking:** Verhindert Race Conditions bei parallelen Cron-Calls
4. **Comprehensive README:** Dokumentation direkt im Modul

### 🔄 Verbesserungspotenzial

1. **Queue-System:** Für viele Accounts besser skalierbar (später)
2. **Retry Logic:** Failed accounts automatisch wiederholen
3. **Metrics:** Prometheus/Grafana Integration für Monitoring
4. **Per-Account-Intervalle:** Verschiedene Polling-Frequenzen

---

## Status Updates

**17. November 2025 - Sprint gestartet**
- Sprint-Dokument erstellt
- Komponenten-Struktur definiert
- Interfaces spezifiziert

**18. November 2025 - Sprint abgeschlossen** ✅
- WebcronManager implementiert
- API-Endpunkte erstellt (/poll, /status, /test)
- Test-Script fertiggestellt
- README dokumentiert
- Integration mit ImapController erfolgreich
- Dokumentation aktualisiert

---

## Abhängigkeiten

- ✅ M0: Logger, Config, Database
- ✅ M1 Sprint 1.1: IMAP-Client
- ✅ M1 Sprint 1.2: Email-Parser
- ✅ M1 Sprint 1.3: Thread-Manager
- ✅ M1 Sprint 1.4: Label-Manager
- ⏳ Database: imap_accounts Tabelle (wird in diesem Sprint erstellt)

---

## Database Schema: imap_accounts

```sql
CREATE TABLE imap_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    
    -- IMAP Connection
    email VARCHAR(255) NOT NULL,
    imap_host VARCHAR(255) NOT NULL,
    imap_port INT NOT NULL DEFAULT 993,
    imap_encryption ENUM('ssl', 'tls', 'none') NOT NULL DEFAULT 'ssl',
    imap_username VARCHAR(255) NOT NULL,
    imap_password TEXT NOT NULL,  -- Verschlüsselt via EncryptionService
    
    -- Status & Metadata
    is_active BOOLEAN DEFAULT TRUE,
    last_sync_at DATETIME NULL,
    last_error TEXT NULL,
    sync_count INT DEFAULT 0,
    
    -- Timestamps
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_user_id (user_id),
    INDEX idx_is_active (is_active),
    INDEX idx_last_sync (last_sync_at),
    
    -- Foreign Key
    CONSTRAINT fk_imap_accounts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Database Migration

**Datei:** `database/migrations/008_create_imap_accounts_table.php`

```php
<?php

return [
    'up' => "
        CREATE TABLE imap_accounts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            
            email VARCHAR(255) NOT NULL,
            imap_host VARCHAR(255) NOT NULL,
            imap_port INT NOT NULL DEFAULT 993,
            imap_encryption ENUM('ssl', 'tls', 'none') NOT NULL DEFAULT 'ssl',
            imap_username VARCHAR(255) NOT NULL,
            imap_password TEXT NOT NULL,
            
            is_active BOOLEAN DEFAULT TRUE,
            last_sync_at DATETIME NULL,
            last_error TEXT NULL,
            sync_count INT DEFAULT 0,
            
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_user_id (user_id),
            INDEX idx_is_active (is_active),
            INDEX idx_last_sync (last_sync_at),
            
            CONSTRAINT fk_imap_accounts_user FOREIGN KEY (user_id) 
                REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    
    'down' => "DROP TABLE IF EXISTS imap_accounts;"
];
```

---

## Lessons Learned (wird aktualisiert)

- TBD

---

## Status Updates

**17. November 2025 - Sprint gestartet**
- Sprint-Dokument erstellt
- Komponenten-Struktur definiert
- Interfaces spezifiziert
- Database Schema geplant

---

## Notizen

- **Dev-Testing:** Webcron manuell via `curl http://ci-inbox.local/webcron/poll?api_key=dev-secret-key-12345` triggern
- **Production:** Cron-Service (z.B. cron-job.org) nutzt `/webcron/poll` alle 5 Minuten
- **UID-Tracking:** Bereits abgerufene UIDs in `emails` Tabelle via `imap_uid` + `account_id` tracken
- **Passwort-Verschlüsselung:** IMAP-Passwörter via EncryptionService (M0) verschlüsseln
- **Fehler-Behandlung:** Failed Accounts werden geloggt, aber andere Accounts werden weiter verarbeitet

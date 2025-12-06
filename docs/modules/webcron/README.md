# Webcron Module

**Version:** 0.1.0  
**Status:** ✅ Implementiert  
**Autor:** Hendrik Dreis  
**Lizenz:** MIT License  
**Modul-Pfad:** `src/modules/webcron/`

## Übersicht

Das Webcron-Modul ermöglicht **externes Triggern von E-Mail-Polling** via HTTP-Requests. Es bietet zwei Zugriffswege:

1. **Interner Webcron:** `/webcron/poll` (Mercury-basiert)
2. **Externer Webhook:** `/webhooks/poll-emails` (für cron-job.org, etc.)

---

## Architektur

```
┌──────────────────────────────────────────────────┐
│  Trigger-Optionen                                │
├──────────────────────────────────────────────────┤
│  1. Interner Webcron (Mercury)                   │
│     GET /webcron/poll?api_key=xxx                │
│                                                   │
│  2. Externer Webhook (cron-job.org)              │
│     POST /webhooks/poll-emails                   │
│     Header: X-Webhook-Token: xxx                 │
└──────────────────┬───────────────────────────────┘
                   │
                   ↓
┌──────────────────────────────────────────────────┐
│  WebcronManager::runPollingJob()                 │
│  ├─ Get active IMAP accounts                     │
│  ├─ For each account:                            │
│  │   └─ Call internal API: /api/imap/sync       │
│  └─ Return statistics                            │
└──────────────────────────────────────────────────┘
```

**Wichtig:** Beide Wege führen zur **gleichen Logik** (`WebcronManager`)!

---

## Dateien

| Datei | Beschreibung |
|-------|--------------|
| `src/modules/webcron/src/WebcronManager.php` | Orchestriert Polling-Jobs |
| `src/modules/webcron/src/WebcronManagerInterface.php` | Interface Definition |
| `src/modules/webcron/src/WebcronConfig.php` | Konfiguration |
| `src/modules/webcron/src/WebcronException.php` | Custom Exception |
| `src/routes/webcron.php` | Interne Webcron-Routes |
| `src/app/Controllers/WebhookController.php` | Webhook Endpoint |
| `tests/manual/webcron-poll-test.php` | Test-Skript (intern) |
| `tests/manual/webhook-poll-test.php` | Test-Skript (extern) |

---

## Option A: Interner Webcron

### Endpoint

**URL:** `GET /webcron/poll`  
**Auth:** Query Parameter `api_key`

### Verwendung

```bash
curl "http://localhost:8000/webcron/poll?api_key=your-api-key"
```

### Konfiguration

**Config File:** `src/config/webcron.php`

```php
return [
    'api_key' => getenv('WEBCRON_API_KEY') ?: 'default-api-key',
    'internal_api_base_url' => 'http://localhost:8000'
];
```

**Environment Variable:**
```bash
WEBCRON_API_KEY=your-secure-api-key
```

### Mercury Integration

**Mercury Config:** `mercury.json`

```json
{
  "schedules": [
    {
      "name": "email-polling",
      "cron": "*/5 * * * *",
      "command": "curl http://localhost:8000/webcron/poll?api_key=${WEBCRON_API_KEY}"
    }
  ]
}
```

**Vorteile:**
- ✅ Interne Kontrolle
- ✅ PHP-native Cron-Jobs
- ✅ Kein externer Dienst nötig

**Nachteile:**
- ❌ Hosting muss Cronjobs unterstützen
- ❌ Kein externes Monitoring

---

## Option B: Externer Webhook

### Endpoint

**URL:** `POST /webhooks/poll-emails`  
**Auth:** Token in Header, Body oder Query

### Authentication Methods

**1. Header: X-Webhook-Token**
```bash
curl -X POST http://localhost:8000/webhooks/poll-emails \
  -H "X-Webhook-Token: your-secret-token"
```

**2. Header: Authorization Bearer**
```bash
curl -X POST http://localhost:8000/webhooks/poll-emails \
  -H "Authorization: Bearer your-secret-token"
```

**3. Request Body**
```bash
curl -X POST http://localhost:8000/webhooks/poll-emails \
  -H "Content-Type: application/json" \
  -d '{"token": "your-secret-token"}'
```

**4. Query Parameter**
```bash
curl -X POST "http://localhost:8000/webhooks/poll-emails?token=your-secret-token"
```

### Konfiguration

**Environment Variable:**
```bash
WEBCRON_SECRET_TOKEN=your-secure-random-token
```

**Token generieren:**
```bash
# Linux/Mac
openssl rand -hex 32

# PowerShell
[System.Convert]::ToBase64String((1..32 | ForEach-Object { Get-Random -Maximum 256 }))
```

**Wichtig:** Mindestens 32 Zeichen, zufällig generiert!

### Response Format

**Success (200):**
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

**Auth Failure (401):**
```json
{
  "success": false,
  "error": "Invalid authentication token"
}
```

**Server Error (500):**
```json
{
  "success": false,
  "error": "Polling failed: Database connection error"
}
```

### Vorteile

- ✅ Funktioniert auf jedem Shared Hosting (kein Cron nötig!)
- ✅ Externes Monitoring (Execution History)
- ✅ Zuverlässiger (professioneller Cron-Dienst)
- ✅ Flexible Trigger-Events möglich

### Nachteile

- ❌ Abhängigkeit von externem Dienst
- ❌ Zusätzliche Kosten (bei Premium-Features)

---

## Setup mit cron-job.org

### Schritt 1: Account erstellen

1. Besuche https://cron-job.org
2. Registriere kostenlosen Account
3. Email bestätigen

### Schritt 2: Cron Job anlegen

**Einstellungen:**
```
Title:       CI-Inbox Email Polling
URL:         https://your-domain.com/webhooks/poll-emails
Method:      POST
Schedule:    */5 * * * * (alle 5 Minuten)
Headers:     X-Webhook-Token: your-secret-token
Timeout:     30 seconds
```

**Screenshot:**
```
┌─────────────────────────────────────┐
│  Create New Cron Job                │
├─────────────────────────────────────┤
│  Title: CI-Inbox Email Polling      │
│  URL:   https://...                 │
│  Method: [POST ▼]                   │
│  Schedule: [Every 5 minutes ▼]     │
│                                     │
│  ☑ Enable request headers          │
│  Header Name:  X-Webhook-Token      │
│  Header Value: ******************   │
│                                     │
│  [Test Job]  [Create Job]          │
└─────────────────────────────────────┘
```

### Schritt 3: Testen

1. Klicke "Run now"
2. Warte auf Response
3. Check Execution History

**Erwartung:** Status 200, Success Message

### Schritt 4: Monitoring

Cron-job.org zeigt:
- ✅ Execution History (letzte 100 Runs)
- ✅ Success/Failure Rate
- ✅ Response Times
- ✅ HTTP Status Codes
- ✅ Email-Alerts bei Failures

---

## Setup mit EasyCron

### Schritt 1: Account erstellen

1. Besuche https://www.easycron.com
2. Free Plan wählen (25 Jobs)
3. Registrieren

### Schritt 2: Cron Job anlegen

```
Cron Expression: */5 * * * *
URL:             https://your-domain.com/webhooks/poll-emails
HTTP Method:     POST
HTTP Headers:    X-Webhook-Token: your-secret-token
Timeout:         30 seconds
Email Alerts:    On failure
```

### Schritt 3: Enable & Test

1. Job aktivieren ("Enable")
2. "Run now" für Test
3. Check Logs

---

## Setup mit Linux Crontab

### Cronjob hinzufügen

```bash
# Crontab öffnen
crontab -e

# Job hinzufügen (alle 5 Minuten)
*/5 * * * * curl -X POST https://your-domain.com/webhooks/poll-emails \
  -H "X-Webhook-Token: your-secret-token" \
  >> /var/log/cimap-polling.log 2>&1
```

### Logs prüfen

```bash
# Letzte Ausführungen
tail -f /var/log/cimap-polling.log

# Fehler filtern
grep "success\":false" /var/log/cimap-polling.log
```

---

## WebcronManager Internals

### Class Structure

```php
class WebcronManager implements WebcronManagerInterface
{
    public function __construct(
        private ImapAccountRepository $accountRepository,
        private LoggerInterface $logger,
        private array $config
    ) {}
    
    public function runPollingJob(): array
    {
        // 1. Get active accounts
        // 2. Poll each account
        // 3. Collect statistics
        // 4. Return result
    }
    
    private function pollAccount(int $accountId): array
    {
        // Call internal API: /api/imap/accounts/{id}/sync
    }
}
```

### Polling Flow

```
1. runPollingJob() aufgerufen
   ↓
2. Get active accounts (ImapAccountRepository)
   ↓
3. For each account:
   ├─ Start timer
   ├─ Call pollAccount(accountId)
   │   └─ Internal API: POST /api/imap/accounts/{id}/sync
   ├─ Collect result (new emails, errors)
   └─ Log execution time
   ↓
4. Aggregate statistics
   ↓
5. Return result array
```

### Internal API Call

**Wichtig:** Verwendet **interne HTTP-Requests** statt direkter Service-Calls!

```php
private function pollAccount(int $accountId): array
{
    $url = $this->config['internal_api_base_url'] 
         . "/api/imap/accounts/{$accountId}/sync";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $result = json_decode($response, true);
    
    return $result;
}
```

**Vorteil:** Gleiche Logik wie manuelle Sync-Requests, kein Code-Duplikation.

---

## Job Locking

**Problem:** Mehrfache parallele Ausführungen vermeiden

**Lösung:** File-based Locking

```php
public function runPollingJob(): array
{
    $lockFile = sys_get_temp_dir() . '/cimap-polling.lock';
    
    // Check if already running
    if (file_exists($lockFile)) {
        $lockTime = filemtime($lockFile);
        $age = time() - $lockTime;
        
        // Stale lock? (älter als 5 Minuten)
        if ($age < 300) {
            throw new WebcronException('Polling job already running');
        }
        
        // Remove stale lock
        unlink($lockFile);
    }
    
    // Create lock
    touch($lockFile);
    
    try {
        // Run polling...
        $result = $this->doPoll();
    } finally {
        // Always remove lock
        unlink($lockFile);
    }
    
    return $result;
}
```

**Wichtig:** Lock wird auch bei Exceptions entfernt (finally-Block)!

---

## Error Handling

### Account-Level Errors

**Strategie:** Einzelne Account-Fehler stoppen nicht das gesamte Polling

```php
foreach ($accounts as $account) {
    try {
        $result = $this->pollAccount($account->id);
        $successCount++;
    } catch (\Exception $e) {
        $this->logger->error('Account polling failed', [
            'account_id' => $account->id,
            'error' => $e->getMessage()
        ]);
        
        $errors[] = [
            'account_id' => $account->id,
            'email' => $account->email,
            'error' => $e->getMessage()
        ];
    }
}

return [
    'accounts_polled' => $successCount,
    'errors' => $errors,
    'total_accounts' => count($accounts)
];
```

### System-Level Errors

**Strategie:** Kritische Fehler werden geloggt und als 500 zurückgegeben

```php
try {
    $result = $webcronManager->runPollingJob();
    return $this->jsonResponse($response, [
        'success' => true,
        'data' => $result
    ], 200);
    
} catch (\Exception $e) {
    $this->logger->critical('Webcron polling failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    return $this->jsonResponse($response, [
        'success' => false,
        'error' => $e->getMessage()
    ], 500);
}
```

---

## Monitoring & Logging

### Log Levels

| Level | Verwendung | Beispiel |
|-------|------------|----------|
| DEBUG | Request-Details | `Webhook poll triggered from IP: 1.2.3.4` |
| INFO | Normal operation | `Polling 3 accounts` |
| SUCCESS | Erfolgreiche Jobs | `Polling completed: 12 new emails` |
| WARNING | Auth-Failures | `Invalid token from IP: 1.2.3.4` |
| ERROR | Account-Fehler | `Account 5 sync failed: Connection timeout` |
| CRITICAL | System-Fehler | `Polling failed: Database unavailable` |

### Log Format

```
[2025-11-18 14:30:00] [INFO] Webhook: Poll emails triggered
[2025-11-18 14:30:00] [DEBUG] Polling 3 active accounts
[2025-11-18 14:30:02] [SUCCESS] Account 1 synced: 5 new emails
[2025-11-18 14:30:04] [SUCCESS] Account 2 synced: 7 new emails
[2025-11-18 14:30:06] [ERROR] Account 3 failed: Connection timeout
[2025-11-18 14:30:06] [SUCCESS] Polling completed: 12 new emails (1 error)
```

### Metrics

**WebcronManager speichert:**
- Last run timestamp
- Success/failure count
- Average duration
- Error messages

**Abrufbar via:** `GET /api/system/health`

```json
{
  "webcron": {
    "last_run": "2025-11-18T14:30:06Z",
    "last_duration_seconds": 6.2,
    "last_accounts_polled": 3,
    "last_new_emails": 12,
    "last_errors": 1
  }
}
```

---

## Testing

### Manual Tests

**Interner Webcron:**
```powershell
php tests/manual/webcron-poll-test.php
```

**Externer Webhook:**
```powershell
php tests/manual/webhook-poll-test.php
```

**Erwartung:** Alle Tests bestanden ✓

### Integration Tests

**Test-Szenarien:**
1. ✅ Erfolgreicher Poll (alle Accounts)
2. ✅ Partieller Fehler (1 Account fehlgeschlagen)
3. ✅ Auth-Failure (ungültiger Token)
4. ✅ Concurrent Requests (Locking)
5. ✅ Empty Account List (keine aktiven Accounts)

---

## Erweiterte Verwendung

### Weitere Webhook-Events

Der Webhook-Mechanismus ist generisch erweiterbar:

```php
// Weitere Webhooks hinzufügen:
POST /webhooks/poll-emails         ← Email polling
POST /webhooks/cleanup-old-data    ← Datenbereinigung
POST /webhooks/send-reports        ← Report-Versand
POST /webhooks/backup-database     ← Backup triggern
```

**Alle mit gleicher Token-Auth!**

### Trigger von externen Events

**Beispiel: GitHub Action**
```yaml
- name: Trigger Email Poll
  run: |
    curl -X POST ${{ secrets.WEBHOOK_URL }} \
      -H "X-Webhook-Token: ${{ secrets.WEBHOOK_TOKEN }}"
```

**Beispiel: Zapier Integration**
```
Trigger: "New email in Gmail"
   ↓
Action: HTTP POST to /webhooks/poll-emails
```

---

## Security Best Practices

### Token Security

✅ **DO:**
- Verwende lange, zufällige Tokens (min. 32 Zeichen)
- Speichere Token nur in Environment Variables
- Verwende HTTPS für Production
- Rotiere Token regelmäßig (alle 90 Tage)
- Rate-Limiting (max 10 Requests/Minute)

❌ **DON'T:**
- Niemals Token in Code committen
- Kein Token in URL-Query (wird geloggt!)
- Kein einfaches Passwort als Token
- Kein unverschlüsseltes HTTP

### IP Whitelisting (Optional)

```php
$allowedIPs = ['1.2.3.4', '5.6.7.8'];
$clientIP = $_SERVER['REMOTE_ADDR'];

if (!in_array($clientIP, $allowedIPs)) {
    return 403 Forbidden;
}
```

**Vorteil:** Zusätzliche Sicherheitsebene  
**Nachteil:** Wartungsaufwand bei IP-Änderungen

---

## Troubleshooting

### Problem: "Invalid authentication token"

**Ursache:** Token stimmt nicht überein

**Lösung:**
```powershell
# Check Environment Variable
echo $env:WEBCRON_SECRET_TOKEN

# Test mit curl
curl -X POST http://localhost:8000/webhooks/poll-emails \
  -H "X-Webhook-Token: your-secret-token-here" \
  -v  # Verbose output für Debugging
```

### Problem: "Polling job already running"

**Ursache:** Lock-File existiert noch

**Lösung:**
```bash
# Manuell Lock entfernen
rm /tmp/cimap-polling.lock

# Check ob Prozess wirklich läuft
ps aux | grep webcron
```

### Problem: Timeouts

**Ursache:** Zu viele Accounts, zu langsame IMAP-Server

**Lösung:**
```php
// Timeout erhöhen in WebcronManager
curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 60 Sekunden

// Oder: Batch-Processing
// Poll nur 5 Accounts pro Run
```

---

## Related Documentation

- **API Reference:** `docs/dev/api.md`
- **WebcronManager Source:** `src/modules/webcron/src/WebcronManager.php`
- **Webhook Controller:** `src/app/Controllers/WebhookController.php`
- **Configuration:** `docs/dev/CONFIGURATION.md`

---

**Status:** ✅ Vollständig implementiert  
**Production Ready:** ✅ Ja  
**Last Updated:** 18. November 2025

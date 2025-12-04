<?php
declare(strict_types=1);

/**
 * Webcron Configuration
 * 
 * Konfiguration für automatisches E-Mail-Polling
 */

return [
    // Internal API Base URL (for HTTP calls to sync endpoints)
    'api_base_url' => $_ENV['APP_URL'] ?? 'http://ci-inbox.local',
    
    // Polling-Intervall (Minuten) - für Dokumentation
    // (Actual interval wird vom externen Webcron-Service gesteuert)
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
        // 'Sent',  // Optional: Sent-Mails abrufen
    ],
    
    // Fehler-Behandlung
    'retry_failed_accounts' => true,
    'max_retries' => 3,
    'retry_delay_minutes' => 15,
    
    // Job-Status Tracking
    'track_job_history' => true,
    'job_history_retention_days' => 30,
    
    // Security: IP-Whitelist für Webcron-Endpoint
    'allowed_ips' => [
        '127.0.0.1',      // Localhost (Dev)
        '::1',            // Localhost IPv6
        // '203.0.113.0',  // Production Webcron IP (z.B. cron-job.org)
    ],
    
    // API-Key für Webcron-Endpoint Authentifizierung
    // Wird via Query-Parameter übergeben: /webcron/poll?api_key=...
    'api_key' => getenv('WEBCRON_API_KEY') ?: 'dev-secret-key-12345',
    
    // Job-Locking (verhindert parallele Ausführung)
    'enable_job_lock' => true,
    'job_lock_timeout_seconds' => 300,  // Max. 5 Minuten
];

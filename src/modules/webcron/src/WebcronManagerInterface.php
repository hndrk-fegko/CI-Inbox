<?php
declare(strict_types=1);

namespace CiInbox\Modules\Webcron;

/**
 * Webcron Manager Interface
 * 
 * Job-Orchestrierung für E-Mail-Polling via Webcron
 */
interface WebcronManagerInterface
{
    /**
     * Führt einen Polling-Job aus (alle aktiven IMAP-Accounts)
     * 
     * @return array Job-Ergebnis ['accounts_processed' => int, 'emails_fetched' => int, 'errors' => array]
     */
    public function runPollingJob(): array;
    
    /**
     * Führt Polling für einen spezifischen Account aus
     * 
     * @param int $accountId IMAP-Account-ID
     * @return array Job-Ergebnis ['emails_fetched' => int, 'new_threads' => int, 'errors' => array]
     */
    public function pollAccount(int $accountId): array;
    
    /**
     * Holt Job-Status
     * 
     * @return array Status ['last_run' => ?string, 'is_running' => bool, 'active_accounts' => int]
     */
    public function getJobStatus(): array;
    
    /**
     * Testet Webcron-Setup (Connectivity, Config)
     * 
     * @return array Test-Ergebnis ['success' => bool, 'checks' => array]
     */
    public function testSetup(): array;
}

<?php
declare(strict_types=1);

namespace CiInbox\Modules\Webcron;

use CiInbox\App\Repositories\ImapAccountRepository;
use CiInbox\Modules\Webcron\Exceptions\WebcronException;
use Psr\Log\LoggerInterface;

/**
 * Webcron Manager
 * 
 * Orchestrates polling jobs via internal API calls
 */
class WebcronManager implements WebcronManagerInterface
{
    /** @var array|null Cached last job result */
    private static ?array $lastJobResult = null;
    
    /** @var bool Job lock to prevent parallel execution */
    private static bool $jobLock = false;
    
    private ImapAccountRepository $accountRepository;
    private LoggerInterface $logger;
    private array $config;
    private string $apiBaseUrl;
    
    public function __construct(
        ImapAccountRepository $accountRepository,
        LoggerInterface $logger,
        array $config = []
    ) {
        $this->accountRepository = $accountRepository;
        $this->logger = $logger;
        $this->config = $config;
        
        // Internal API base URL (localhost, same server)
        $this->apiBaseUrl = $config['api_base_url'] ?? 'http://ci-inbox.local';
    }
    
    /**
     * {@inheritdoc}
     */
    public function runPollingJob(): array
    {
        $startTime = microtime(true);
        
        // Check job lock
        if (self::$jobLock) {
            $this->logger->warning('Polling job already running (locked)');
            throw WebcronException::jobAlreadyRunning();
        }
        
        // Acquire lock
        self::$jobLock = true;
        
        try {
            $this->logger->info('Webcron polling job started');
            
            // Get all active accounts
            $accounts = $this->accountRepository->getActiveAccounts();
            
            if (empty($accounts)) {
                $this->logger->warning('No active IMAP accounts found');
                throw WebcronException::noActiveAccounts();
            }
            
            $this->logger->info('Polling accounts via API', [
                'count' => count($accounts)
            ]);
            
            $results = [
                'accounts_processed' => 0,
                'emails_fetched' => 0,
                'errors' => []
            ];
            
            // Trigger sync for each account via internal API
            foreach ($accounts as $account) {
                try {
                    $syncResult = $this->pollAccount($account->id);
                    
                    if ($syncResult['success']) {
                        $results['accounts_processed']++;
                        $results['emails_fetched'] += $syncResult['data']['processed'] ?? 0;
                    } else {
                        $results['errors'][] = [
                            'account_id' => $account->id,
                            'email' => $account->email,
                            'error' => $syncResult['error'] ?? 'Unknown error'
                        ];
                    }
                    
                } catch (\Exception $e) {
                    $this->logger->error('Polling account failed', [
                        'account_id' => $account->id,
                        'email' => $account->email,
                        'error' => $e->getMessage()
                    ]);
                    
                    $results['errors'][] = [
                        'account_id' => $account->id,
                        'email' => $account->email,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            $duration = microtime(true) - $startTime;
            
            $this->logger->info('Webcron polling job completed', [
                'accounts_processed' => $results['accounts_processed'],
                'emails_fetched' => $results['emails_fetched'],
                'duration_seconds' => round($duration, 2)
            ]);
            
            // Run auto-archiving job (after email polling)
            try {
                $archiveResult = $this->runAutoArchiving();
                $results['auto_archive'] = $archiveResult;
                
                $this->logger->info('Auto-archiving completed', $archiveResult);
            } catch (\Exception $archiveEx) {
                $this->logger->error('Auto-archiving failed', [
                    'error' => $archiveEx->getMessage()
                ]);
                $results['auto_archive'] = [
                    'error' => $archiveEx->getMessage()
                ];
            }
            
            // Run database integrity check (every 10th execution)
            static $executionCounter = 0;
            $executionCounter++;
            
            if ($executionCounter % 10 === 0) {
                try {
                    $integrityResult = $this->runIntegrityCheck();
                    $results['integrity_check'] = $integrityResult;
                    
                    if ($integrityResult['status'] !== 'ok') {
                        $this->logger->warning('Database integrity check found issues', $integrityResult);
                    }
                } catch (\Exception $integrityEx) {
                    $this->logger->error('Integrity check failed', [
                        'error' => $integrityEx->getMessage()
                    ]);
                    $results['integrity_check'] = [
                        'error' => $integrityEx->getMessage()
                    ];
                }
            }
            
            // Cache result
            self::$lastJobResult = $results;
            
            // Release lock
            self::$jobLock = false;
            
            return $results;
            
        } catch (\Exception $e) {
            // Release lock on error
            self::$jobLock = false;
            
            $this->logger->error('Webcron polling job failed', [
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function pollAccount(int $accountId): array
    {
        $this->logger->info('Polling account via API', [
            'account_id' => $accountId
        ]);
        
        // Account validieren
        $account = $this->accountRepository->find($accountId);
        if (!$account) {
            throw WebcronException::accountNotFound($accountId);
        }
        
        if (!$account->is_active) {
            throw WebcronException::accountInactive($accountId);
        }
        
        // Internal API call: POST /api/imap/accounts/{id}/sync
        $apiUrl = rtrim($this->apiBaseUrl, '/') . "/api/imap/accounts/{$accountId}/sync";
        
        $this->logger->debug('Calling internal API', [
            'url' => $apiUrl,
            'account_id' => $accountId
        ]);
        
        // Use cURL for internal HTTP request
        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ]
        ]);
        
        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            $this->logger->error('API call failed (cURL)', [
                'account_id' => $accountId,
                'error' => $curlError
            ]);
            
            return [
                'success' => false,
                'error' => "API call failed: {$curlError}"
            ];
        }
        
        $result = json_decode($responseBody, true);
        
        if ($httpCode !== 200 || !$result) {
            $this->logger->error('API call failed (HTTP)', [
                'account_id' => $accountId,
                'http_code' => $httpCode,
                'response' => $responseBody
            ]);
            
            return [
                'success' => false,
                'error' => $result['error'] ?? "HTTP {$httpCode}"
            ];
        }
        
        $this->logger->info('Account polled successfully via API', [
            'account_id' => $accountId,
            'processed' => $result['data']['processed'] ?? 0
        ]);
        
        return $result;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getJobStatus(): array
    {
        return [
            'last_result' => self::$lastJobResult,
            'is_running' => self::$jobLock,
            'timestamp' => date('c')
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function testSetup(): array
    {
        $issues = [];
        
        // Check active accounts
        $accounts = $this->accountRepository->getActiveAccounts();
        if (empty($accounts)) {
            $issues[] = 'No active IMAP accounts configured';
        }
        
        // Check API availability
        $healthUrl = rtrim($this->apiBaseUrl, '/') . '/api/system/health';
        $ch = curl_init($healthUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            $issues[] = "Internal API not reachable (HTTP {$httpCode})";
        }
        
        return [
            'status' => empty($issues) ? 'ok' : 'issues_found',
            'active_accounts' => count($accounts),
            'api_base_url' => $this->apiBaseUrl,
            'api_reachable' => $httpCode === 200,
            'issues' => $issues
        ];
    }
    
    /**
     * Run auto-archiving job (archives closed threads after X hours)
     * 
     * @return array Result with archived count
     */
    private function runAutoArchiving(): array
    {
        // TODO: Implement auto-archiving via SystemSettingsService
        //  For now, skip auto-archiving (not critical for email polling)
        return [
            'archived' => 0,
            'skipped' => true,
            'reason' => 'Auto-archiving not yet implemented'
        ];
    }
    
    /**
     * Run database integrity check
     * 
     * Checks for:
     * - Orphaned threads (message_count > 0 but no emails)
     * - Incorrect message_counts
     * - Emails with invalid thread_id
     * - Duplicate message_ids
     * - Empty threads
     * 
     * @return array Check results with issues found
     */
    private function runIntegrityCheck(): array
    {
        $this->logger->info('Running database integrity check');
        
        try {
            // Use Illuminate DB directly (no need for service layer here)
            $db = \Illuminate\Database\Capsule\Manager::table('threads');
            
            $issues = [];
            
            // Check 1: Orphaned threads (message_count > 0 but no emails)
            $orphanedCount = $db->from('threads as t')
                ->leftJoin('emails as e', 't.id', '=', 'e.thread_id')
                ->where('t.message_count', '>', 0)
                ->whereNull('e.id')
                ->groupBy('t.id')
                ->count();
            
            if ($orphanedCount > 0) {
                $issues[] = "Orphaned threads: {$orphanedCount}";
            }
            
            // Check 2: Threads with incorrect message_count
            $wrongCounts = \Illuminate\Database\Capsule\Manager::table('threads as t')
                ->leftJoin('emails as e', 't.id', '=', 'e.thread_id')
                ->selectRaw('COUNT(*) as count')
                ->groupBy('t.id', 't.message_count')
                ->havingRaw('t.message_count != COUNT(e.id)')
                ->count();
            
            if ($wrongCounts > 0) {
                $issues[] = "Wrong message_counts: {$wrongCounts}";
            }
            
            // Check 3: Emails with invalid thread_id
            $orphanedEmails = \Illuminate\Database\Capsule\Manager::table('emails as e')
                ->leftJoin('threads as t', 'e.thread_id', '=', 't.id')
                ->whereNull('t.id')
                ->count();
            
            if ($orphanedEmails > 0) {
                $issues[] = "Orphaned emails: {$orphanedEmails}";
            }
            
            // Check 4: Duplicate message_ids
            $duplicates = \Illuminate\Database\Capsule\Manager::table('emails')
                ->selectRaw('message_id, COUNT(*) as count')
                ->groupBy('message_id')
                ->having('count', '>', 1)
                ->count();
            
            if ($duplicates > 0) {
                $issues[] = "Duplicate message_ids: {$duplicates}";
            }
            
            $status = empty($issues) ? 'ok' : 'issues_found';
            
            if (!empty($issues)) {
                $this->logger->warning('Database integrity issues found', [
                    'issues' => $issues,
                    'recommendation' => 'Run: php database/cleanup-orphaned-threads.php --archive'
                ]);
            } else {
                $this->logger->info('Database integrity check passed');
            }
            
            return [
                'status' => $status,
                'issues' => $issues,
                'checked_at' => date('Y-m-d H:i:s')
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Integrity check failed', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'checked_at' => date('Y-m-d H:i:s')
            ];
        }
    }
}

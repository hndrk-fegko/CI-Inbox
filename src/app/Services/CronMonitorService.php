<?php
/**
 * Cron Monitor Service
 * 
 * Monitors webcron polling service health and provides status information.
 * Also manages webhook configuration and token regeneration.
 */

declare(strict_types=1);

namespace CiInbox\App\Services;

use CiInbox\App\Models\CronExecution;
use CiInbox\App\Repositories\SystemSettingRepository;
use CiInbox\Modules\Logger\LoggerInterface;
use Carbon\Carbon;

class CronMonitorService
{
    // Health thresholds for minutely cron
    private const THRESHOLD_HEALTHY = 55;   // >55 executions/hour = healthy
    private const THRESHOLD_DELAYED = 30;   // <30 executions/hour = delayed
    private const THRESHOLD_STALE = 1;      // <1 execution/hour = stale
    
    private ?SystemSettingRepository $settingsRepository = null;
    
    public function __construct(
        private LoggerInterface $logger
    ) {
        // Try to get settings repository if available
        try {
            $container = \CiInbox\Core\Container::getInstance();
            if ($container->has(SystemSettingRepository::class)) {
                $this->settingsRepository = $container->get(SystemSettingRepository::class);
            }
        } catch (\Exception $e) {
            // Log that settings repository is unavailable
            $this->logger->debug('[CronMonitor] Settings repository unavailable, using defaults', [
                'reason' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get current cron service status
     * 
     * Status rules:
     * - GREEN: 10+ executions in last hour
     * - YELLOW: 1-9 executions in last hour
     * - RED: 0 executions in last hour
     * 
     * @return array{status: string, color: string, executions_last_hour: int, last_execution: array|null, total_executions: int}
     */
    public function getStatus(): array
    {
        $this->logger->debug('[CronMonitor] Getting cron status');
        
        try {
            // Get executions in last hour
            $lastHour = Carbon::now()->subHour();
            $executionsLastHour = CronExecution::where('execution_timestamp', '>=', $lastHour)->count();
            
            // Get last execution
            $lastExecution = CronExecution::orderBy('execution_timestamp', 'desc')->first();
            
            // Get total executions
            $totalExecutions = CronExecution::count();
            
            // Calculate status
            $status = 'red';
            $statusText = 'Not Running';
            
            if ($executionsLastHour >= 10) {
                $status = 'green';
                $statusText = 'Healthy';
            } elseif ($executionsLastHour >= 1) {
                $status = 'yellow';
                $statusText = 'Running (Low Frequency)';
            }
            
            $result = [
                'status' => $statusText,
                'color' => $status,
                'executions_last_hour' => $executionsLastHour,
                'last_execution' => $lastExecution ? [
                    'timestamp' => $lastExecution->execution_timestamp->toIso8601String(),
                    'relative_time' => $lastExecution->getRelativeTime(),
                    'accounts_polled' => $lastExecution->accounts_polled,
                    'new_emails_found' => $lastExecution->new_emails_found,
                    'duration' => $lastExecution->getFormattedDuration(),
                    'status' => $lastExecution->status,
                    'error_message' => $lastExecution->error_message
                ] : null,
                'total_executions' => $totalExecutions
            ];
            
            $this->logger->info('[CronMonitor] Status retrieved', [
                'status' => $status,
                'executions_last_hour' => $executionsLastHour
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            $this->logger->error('[CronMonitor] Failed to get status', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'status' => 'Unknown',
                'color' => 'gray',
                'executions_last_hour' => 0,
                'last_execution' => null,
                'total_executions' => 0
            ];
        }
    }
    
    /**
     * Get execution history
     * 
     * @param int $limit Number of recent executions to retrieve
     * @return array
     */
    public function getHistory(int $limit = 20): array
    {
        $this->logger->debug('[CronMonitor] Getting execution history', ['limit' => $limit]);
        
        try {
            $executions = CronExecution::orderBy('execution_timestamp', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($execution) {
                    return [
                        'id' => $execution->id,
                        'timestamp' => $execution->execution_timestamp->toIso8601String(),
                        'relative_time' => $execution->getRelativeTime(),
                        'accounts_polled' => $execution->accounts_polled,
                        'new_emails_found' => $execution->new_emails_found,
                        'duration' => $execution->getFormattedDuration(),
                        'status' => $execution->status,
                        'error_message' => $execution->error_message
                    ];
                })
                ->toArray();
            
            $this->logger->info('[CronMonitor] History retrieved', ['count' => count($executions)]);
            
            return $executions;
            
        } catch (\Exception $e) {
            $this->logger->error('[CronMonitor] Failed to get history', [
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }
    
    /**
     * Log a cron execution
     * 
     * @param int $accountsPolled Number of accounts polled
     * @param int $newEmailsFound Number of new emails found
     * @param int $durationMs Duration in milliseconds
     * @param string $status 'success' or 'error'
     * @param string|null $errorMessage Error message if status is 'error'
     * @return void
     */
    public function logExecution(
        int $accountsPolled,
        int $newEmailsFound,
        int $durationMs,
        string $status = 'success',
        ?string $errorMessage = null
    ): void {
        try {
            CronExecution::create([
                'accounts_polled' => $accountsPolled,
                'new_emails_found' => $newEmailsFound,
                'duration_ms' => $durationMs,
                'status' => $status,
                'error_message' => $errorMessage
            ]);
            
            $this->logger->info('[CronMonitor] Execution logged', [
                'accounts_polled' => $accountsPolled,
                'new_emails_found' => $newEmailsFound,
                'duration_ms' => $durationMs,
                'status' => $status
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('[CronMonitor] Failed to log execution', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get statistics
     * 
     * @return array{avg_duration_ms: float, avg_accounts_polled: float, avg_emails_found: float, success_rate: float, total_emails_found: int}
     */
    public function getStatistics(): array
    {
        $this->logger->debug('[CronMonitor] Getting statistics');
        
        try {
            $executions = CronExecution::all();
            
            if ($executions->isEmpty()) {
                return [
                    'avg_duration_ms' => 0,
                    'avg_accounts_polled' => 0,
                    'avg_emails_found' => 0,
                    'success_rate' => 0,
                    'total_emails_found' => 0
                ];
            }
            
            $successCount = $executions->where('status', 'success')->count();
            $totalCount = $executions->count();
            
            $stats = [
                'avg_duration_ms' => round($executions->avg('duration_ms'), 2),
                'avg_accounts_polled' => round($executions->avg('accounts_polled'), 2),
                'avg_emails_found' => round($executions->avg('new_emails_found'), 2),
                'success_rate' => $totalCount > 0 ? round(($successCount / $totalCount) * 100, 2) : 0,
                'total_emails_found' => $executions->sum('new_emails_found')
            ];
            
            $this->logger->info('[CronMonitor] Statistics retrieved', $stats);
            
            return $stats;
            
        } catch (\Exception $e) {
            $this->logger->error('[CronMonitor] Failed to get statistics', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'avg_duration_ms' => 0,
                'avg_accounts_polled' => 0,
                'avg_emails_found' => 0,
                'success_rate' => 0,
                'total_emails_found' => 0
            ];
        }
    }
    
    /**
     * Get webhook configuration
     * 
     * @return array{token: string, url: string}
     */
    public function getWebhookConfig(): array
    {
        $this->logger->debug('[CronMonitor] Getting webhook config');
        
        try {
            // Get token from settings or generate default
            $token = null;
            
            if ($this->settingsRepository) {
                $token = $this->settingsRepository->get('cron.webhook_token');
            }
            
            // Fallback to environment variable or generate
            if (empty($token)) {
                $token = getenv('WEBCRON_API_KEY') ?: 'dev-secret-key-12345';
            }
            
            // Build webhook URL
            $baseUrl = getenv('APP_URL') ?: 'http://localhost';
            $url = rtrim($baseUrl, '/') . '/api/webcron/poll?token=' . $token;
            
            return [
                'token' => $token,
                'url' => $url
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('[CronMonitor] Failed to get webhook config', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'token' => 'error',
                'url' => ''
            ];
        }
    }
    
    /**
     * Regenerate webhook token
     * 
     * @return array{success: bool, token: string, url: string, message: string}
     */
    public function regenerateWebhookToken(): array
    {
        $this->logger->info('[CronMonitor] Regenerating webhook token');
        
        try {
            // Generate new secure token
            $newToken = bin2hex(random_bytes(32));
            
            // Save to settings if repository available
            if ($this->settingsRepository) {
                $this->settingsRepository->set('cron.webhook_token', $newToken);
            }
            
            // Build new webhook URL
            $baseUrl = getenv('APP_URL') ?: 'http://localhost';
            $url = rtrim($baseUrl, '/') . '/api/webcron/poll?token=' . $newToken;
            
            $this->logger->info('[CronMonitor] Webhook token regenerated successfully');
            
            return [
                'success' => true,
                'token' => $newToken,
                'url' => $url,
                'message' => 'Webhook token regenerated successfully. Update your cron service with the new URL.'
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('[CronMonitor] Failed to regenerate webhook token', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'token' => '',
                'url' => '',
                'message' => 'Failed to regenerate token: ' . $e->getMessage()
            ];
        }
    }
}

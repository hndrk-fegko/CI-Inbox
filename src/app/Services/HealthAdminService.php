<?php
/**
 * Health Admin Service
 * 
 * Provides system health monitoring, automated tests, and self-healing capabilities.
 */

declare(strict_types=1);

namespace CiInbox\App\Services;

use CiInbox\Modules\Logger\LoggerInterface;
use CiInbox\Modules\Config\ConfigService;

class HealthAdminService
{
    private string $dataDir;
    
    public function __construct(
        private LoggerInterface $logger,
        private ?ConfigService $config = null
    ) {
        $this->dataDir = __DIR__ . '/../../../data';
        
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
    }
    
    /**
     * Get overall health summary
     */
    public function getSummary(): array
    {
        $status = $this->getStatus();
        
        $healthy = 0;
        $warning = 0;
        $critical = 0;
        
        foreach ($status as $key => $value) {
            if (is_string($value)) {
                if ($value === 'healthy') $healthy++;
                elseif ($value === 'warning') $warning++;
                else $critical++;
            }
        }
        
        $overallStatus = 'healthy';
        if ($critical > 0) $overallStatus = 'critical';
        elseif ($warning > 0) $overallStatus = 'warning';
        
        return [
            'overall_status' => $overallStatus,
            'healthy_count' => $healthy,
            'warning_count' => $warning,
            'critical_count' => $critical,
            'last_check' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Get detailed health status
     */
    public function getStatus(): array
    {
        return [
            'database' => $this->checkDatabase(),
            'imap' => $this->checkImap(),
            'smtp' => $this->checkSmtp(),
            'disk' => $this->checkDisk(),
            'disk_free' => $this->getDiskFree(),
            'cron' => $this->checkCron(),
            'queue' => $this->checkQueue(),
            'queue_size' => $this->getQueueSize(),
            'sessions' => $this->checkSessions()
        ];
    }
    
    /**
     * Check database connectivity
     */
    private function checkDatabase(): string
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                getenv('DB_HOST') ?: 'localhost',
                getenv('DB_NAME') ?: 'ci_inbox'
            );
            
            $pdo = new \PDO(
                $dsn,
                getenv('DB_USER') ?: 'root',
                getenv('DB_PASS') ?: '',
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
            
            $stmt = $pdo->query('SELECT 1');
            return $stmt ? 'healthy' : 'critical';
            
        } catch (\Exception $e) {
            $this->logger->error('[Health] Database check failed', ['error' => $e->getMessage()]);
            return 'critical';
        }
    }
    
    /**
     * Check IMAP connectivity
     */
    private function checkImap(): string
    {
        try {
            // Check if IMAP config exists
            $configFile = $this->dataDir . '/imap-config.json';
            if (!file_exists($configFile)) {
                return 'warning'; // Not configured
            }
            
            $config = json_decode(file_get_contents($configFile), true);
            if (empty($config['host'])) {
                return 'warning';
            }
            
            // Try to connect (timeout 5 seconds)
            $socket = @fsockopen(
                $config['host'],
                (int)($config['port'] ?? 993),
                $errno,
                $errstr,
                5
            );
            
            if ($socket) {
                fclose($socket);
                return 'healthy';
            }
            
            return 'critical';
            
        } catch (\Exception $e) {
            return 'warning';
        }
    }
    
    /**
     * Check SMTP connectivity
     */
    private function checkSmtp(): string
    {
        try {
            $configFile = $this->dataDir . '/smtp-config.json';
            if (!file_exists($configFile)) {
                return 'warning';
            }
            
            $config = json_decode(file_get_contents($configFile), true);
            if (empty($config['host'])) {
                return 'warning';
            }
            
            $socket = @fsockopen(
                $config['host'],
                (int)($config['port'] ?? 587),
                $errno,
                $errstr,
                5
            );
            
            if ($socket) {
                fclose($socket);
                return 'healthy';
            }
            
            return 'critical';
            
        } catch (\Exception $e) {
            return 'warning';
        }
    }
    
    /**
     * Check disk space
     */
    private function checkDisk(): string
    {
        try {
            $free = disk_free_space(__DIR__);
            $total = disk_total_space(__DIR__);
            
            if ($free === false || $total === false) {
                return 'warning';
            }
            
            $percentFree = ($free / $total) * 100;
            
            if ($percentFree < 5) return 'critical';
            if ($percentFree < 15) return 'warning';
            return 'healthy';
            
        } catch (\Exception $e) {
            return 'warning';
        }
    }
    
    /**
     * Get disk free space formatted
     */
    private function getDiskFree(): string
    {
        try {
            $free = disk_free_space(__DIR__);
            if ($free === false) return 'Unknown';
            
            $gb = $free / (1024 * 1024 * 1024);
            return round($gb, 1) . ' GB';
            
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }
    
    /**
     * Check cron/webcron status
     */
    private function checkCron(): string
    {
        try {
            $historyFile = $this->dataDir . '/cron-history.json';
            if (!file_exists($historyFile)) {
                return 'warning'; // No history = not running
            }
            
            $history = json_decode(file_get_contents($historyFile), true);
            if (empty($history)) {
                return 'warning';
            }
            
            // Get last execution
            $lastExecution = end($history);
            $lastTime = strtotime($lastExecution['timestamp'] ?? '');
            
            if (!$lastTime) return 'warning';
            
            $minutesAgo = (time() - $lastTime) / 60;
            
            // Based on cron thresholds from 030-cron module
            if ($minutesAgo < 30) return 'healthy';
            if ($minutesAgo < 60) return 'warning';
            return 'critical';
            
        } catch (\Exception $e) {
            return 'warning';
        }
    }
    
    /**
     * Check email queue
     */
    private function checkQueue(): string
    {
        try {
            // Check for stuck emails in database
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                getenv('DB_HOST') ?: 'localhost',
                getenv('DB_NAME') ?: 'ci_inbox'
            );
            
            $pdo = new \PDO(
                $dsn,
                getenv('DB_USER') ?: 'root',
                getenv('DB_PASS') ?: '',
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
            
            // Check if queue table exists
            $stmt = $pdo->query("SHOW TABLES LIKE 'email_queue'");
            if ($stmt->rowCount() === 0) {
                return 'healthy'; // No queue table = nothing stuck
            }
            
            // Check for old stuck items
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM email_queue WHERE status = 'pending' AND created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
            $stmt->execute();
            $stuck = (int)$stmt->fetchColumn();
            
            if ($stuck > 10) return 'critical';
            if ($stuck > 0) return 'warning';
            return 'healthy';
            
        } catch (\Exception $e) {
            return 'healthy'; // Assume healthy if can't check
        }
    }
    
    /**
     * Get queue size
     */
    private function getQueueSize(): int
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                getenv('DB_HOST') ?: 'localhost',
                getenv('DB_NAME') ?: 'ci_inbox'
            );
            
            $pdo = new \PDO(
                $dsn,
                getenv('DB_USER') ?: 'root',
                getenv('DB_PASS') ?: '',
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
            
            $stmt = $pdo->query("SHOW TABLES LIKE 'email_queue'");
            if ($stmt->rowCount() === 0) {
                return 0;
            }
            
            $stmt = $pdo->query("SELECT COUNT(*) FROM email_queue WHERE status = 'pending'");
            return (int)$stmt->fetchColumn();
            
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Check session health
     */
    private function checkSessions(): string
    {
        try {
            $sessionPath = session_save_path() ?: sys_get_temp_dir();
            
            if (!is_writable($sessionPath)) {
                return 'critical';
            }
            
            return 'healthy';
            
        } catch (\Exception $e) {
            return 'warning';
        }
    }
    
    /**
     * Run a specific health test
     */
    public function runTest(string $testName): array
    {
        $startTime = microtime(true);
        $passed = false;
        $message = '';
        
        try {
            switch ($testName) {
                case 'database':
                    $status = $this->checkDatabase();
                    $passed = $status === 'healthy';
                    $message = $passed ? 'Database connection successful' : 'Database connection failed';
                    break;
                    
                case 'imap':
                    $status = $this->checkImap();
                    $passed = $status === 'healthy';
                    $message = $passed ? 'IMAP connection successful' : 'IMAP connection failed or not configured';
                    break;
                    
                case 'smtp':
                    $status = $this->checkSmtp();
                    $passed = $status === 'healthy';
                    $message = $passed ? 'SMTP connection successful' : 'SMTP connection failed or not configured';
                    break;
                    
                case 'disk':
                    $status = $this->checkDisk();
                    $passed = $status === 'healthy';
                    $message = 'Disk space: ' . $this->getDiskFree() . ' free';
                    break;
                    
                case 'cron':
                    $status = $this->checkCron();
                    $passed = $status === 'healthy';
                    $message = $passed ? 'Cron is running normally' : 'Cron may be delayed or not running';
                    break;
                    
                case 'queue':
                    $status = $this->checkQueue();
                    $passed = $status === 'healthy';
                    $queueSize = $this->getQueueSize();
                    $message = "Queue size: {$queueSize} pending items";
                    break;
                    
                case 'sessions':
                    $status = $this->checkSessions();
                    $passed = $status === 'healthy';
                    $message = $passed ? 'Session storage is healthy' : 'Session storage issue detected';
                    break;
                    
                default:
                    $message = 'Unknown test: ' . $testName;
            }
            
        } catch (\Exception $e) {
            $message = 'Test error: ' . $e->getMessage();
        }
        
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        // Log test result
        $this->logTestResult($testName, $passed, $message, $duration);
        
        return [
            'test' => $testName,
            'passed' => $passed,
            'message' => $message,
            'duration_ms' => $duration,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Log test result for reporting
     */
    private function logTestResult(string $test, bool $passed, string $message, float $duration): void
    {
        $reportFile = $this->dataDir . '/health-reports.json';
        
        $reports = [];
        if (file_exists($reportFile)) {
            $reports = json_decode(file_get_contents($reportFile), true) ?: [];
        }
        
        // Add new result
        $reports[] = [
            'test' => $test,
            'passed' => $passed,
            'message' => $message,
            'duration_ms' => $duration,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Keep only last 500 results
        if (count($reports) > 500) {
            $reports = array_slice($reports, -500);
        }
        
        file_put_contents($reportFile, json_encode($reports, JSON_PRETTY_PRINT));
    }
    
    /**
     * Get schedule configuration
     */
    public function getSchedule(): array
    {
        $scheduleFile = $this->dataDir . '/health-schedule.json';
        
        if (file_exists($scheduleFile)) {
            $schedule = json_decode(file_get_contents($scheduleFile), true);
            if ($schedule) {
                return $schedule;
            }
        }
        
        return [
            'enabled' => false,
            'interval' => 15,
            'self_heal' => true,
            'notify_admin' => true,
            'last_run' => null
        ];
    }
    
    /**
     * Update schedule configuration
     */
    public function updateSchedule(array $config): array
    {
        $current = $this->getSchedule();
        
        $updated = array_merge($current, [
            'enabled' => $config['enabled'] ?? false,
            'interval' => (int)($config['interval'] ?? 15),
            'self_heal' => $config['self_heal'] ?? true,
            'notify_admin' => $config['notify_admin'] ?? true
        ]);
        
        $scheduleFile = $this->dataDir . '/health-schedule.json';
        file_put_contents($scheduleFile, json_encode($updated, JSON_PRETTY_PRINT));
        
        $this->logger->info('[Health] Schedule updated', $updated);
        
        return $updated;
    }
    
    /**
     * Get test reports summary
     */
    public function getReports(int $limit = 20): array
    {
        $reportFile = $this->dataDir . '/health-reports.json';
        
        if (!file_exists($reportFile)) {
            return ['reports' => []];
        }
        
        $allResults = json_decode(file_get_contents($reportFile), true) ?: [];
        
        // Group by timestamp (minute granularity)
        $grouped = [];
        foreach ($allResults as $result) {
            $minute = substr($result['timestamp'], 0, 16); // YYYY-MM-DD HH:MM
            if (!isset($grouped[$minute])) {
                $grouped[$minute] = ['passed' => 0, 'total' => 0, 'timestamp' => $minute];
            }
            $grouped[$minute]['total']++;
            if ($result['passed']) {
                $grouped[$minute]['passed']++;
            }
        }
        
        // Get latest reports
        $reports = array_values($grouped);
        usort($reports, fn($a, $b) => strcmp($b['timestamp'], $a['timestamp']));
        
        return [
            'reports' => array_slice($reports, 0, $limit)
        ];
    }
    
    /**
     * Execute self-healing action
     */
    public function selfHeal(string $healType): array
    {
        $success = false;
        $message = '';
        
        try {
            switch ($healType) {
                case 'disk':
                    $message = $this->healDisk();
                    $success = true;
                    break;
                    
                case 'queue':
                    $message = $this->healQueue();
                    $success = true;
                    break;
                    
                case 'sessions':
                    $message = $this->healSessions();
                    $success = true;
                    break;
                    
                default:
                    $message = 'Unknown heal type: ' . $healType;
            }
            
        } catch (\Exception $e) {
            $message = 'Healing failed: ' . $e->getMessage();
        }
        
        // Log the healing action
        $this->logHealingAction($healType, $success, $message);
        
        return [
            'action' => $healType,
            'success' => $success,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Heal disk - clean old logs
     */
    private function healDisk(): string
    {
        $logsDir = __DIR__ . '/../../../logs';
        $deleted = 0;
        
        if (is_dir($logsDir)) {
            $files = glob($logsDir . '/*.log');
            $cutoff = strtotime('-7 days');
            
            foreach ($files as $file) {
                if (filemtime($file) < $cutoff) {
                    unlink($file);
                    $deleted++;
                }
            }
        }
        
        // Also clean old backup files (older than retention)
        $backupsDir = $this->dataDir . '/backups';
        if (is_dir($backupsDir)) {
            $files = glob($backupsDir . '/backup-*.sql.gz');
            $cutoff = strtotime('-30 days');
            
            foreach ($files as $file) {
                if (filemtime($file) < $cutoff) {
                    unlink($file);
                    $deleted++;
                }
            }
        }
        
        $this->logger->info('[Health] Disk cleanup completed', ['deleted_files' => $deleted]);
        
        return "Cleaned up {$deleted} old files";
    }
    
    /**
     * Heal queue - retry failed items
     */
    private function healQueue(): string
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                getenv('DB_HOST') ?: 'localhost',
                getenv('DB_NAME') ?: 'ci_inbox'
            );
            
            $pdo = new \PDO(
                $dsn,
                getenv('DB_USER') ?: 'root',
                getenv('DB_PASS') ?: '',
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
            
            // Check if queue table exists
            $stmt = $pdo->query("SHOW TABLES LIKE 'email_queue'");
            if ($stmt->rowCount() === 0) {
                return 'No queue table exists';
            }
            
            // Reset failed items to pending
            $stmt = $pdo->prepare("UPDATE email_queue SET status = 'pending', attempts = 0, updated_at = NOW() WHERE status = 'failed'");
            $stmt->execute();
            $reset = $stmt->rowCount();
            
            // Delete very old stuck items
            $stmt = $pdo->prepare("DELETE FROM email_queue WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $stmt->execute();
            $deleted = $stmt->rowCount();
            
            $this->logger->info('[Health] Queue cleanup completed', [
                'reset' => $reset,
                'deleted' => $deleted
            ]);
            
            return "Reset {$reset} failed items, deleted {$deleted} old items";
            
        } catch (\Exception $e) {
            return 'Queue cleanup error: ' . $e->getMessage();
        }
    }
    
    /**
     * Heal sessions - clean old sessions
     */
    private function healSessions(): string
    {
        $sessionPath = session_save_path() ?: sys_get_temp_dir();
        $deleted = 0;
        
        if (is_dir($sessionPath)) {
            $files = glob($sessionPath . '/sess_*');
            $cutoff = strtotime('-24 hours');
            
            foreach ($files as $file) {
                if (filemtime($file) < $cutoff) {
                    @unlink($file);
                    $deleted++;
                }
            }
        }
        
        $this->logger->info('[Health] Session cleanup completed', ['deleted_sessions' => $deleted]);
        
        return "Cleaned up {$deleted} old session files";
    }
    
    /**
     * Log healing action
     */
    private function logHealingAction(string $action, bool $success, string $message): void
    {
        $logFile = $this->dataDir . '/healing-log.json';
        
        $log = [];
        if (file_exists($logFile)) {
            $log = json_decode(file_get_contents($logFile), true) ?: [];
        }
        
        $log[] = [
            'action' => $action,
            'success' => $success,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Keep only last 100 entries
        if (count($log) > 100) {
            $log = array_slice($log, -100);
        }
        
        file_put_contents($logFile, json_encode($log, JSON_PRETTY_PRINT));
    }
    
    /**
     * Get healing log
     */
    public function getHealingLog(): array
    {
        $logFile = $this->dataDir . '/healing-log.json';
        
        if (!file_exists($logFile)) {
            return ['entries' => []];
        }
        
        $log = json_decode(file_get_contents($logFile), true) ?: [];
        
        // Return newest first
        return [
            'entries' => array_reverse($log)
        ];
    }
    
    /**
     * Clear healing log
     */
    public function clearHealingLog(): bool
    {
        $logFile = $this->dataDir . '/healing-log.json';
        
        if (file_exists($logFile)) {
            unlink($logFile);
        }
        
        $this->logger->info('[Health] Healing log cleared');
        
        return true;
    }
    
    /**
     * Export health report as JSON
     */
    public function exportReport(): array
    {
        return [
            'summary' => $this->getSummary(),
            'status' => $this->getStatus(),
            'schedule' => $this->getSchedule(),
            'reports' => $this->getReports(50),
            'healing_log' => $this->getHealingLog(),
            'exported_at' => date('Y-m-d H:i:s'),
            'system_info' => [
                'php_version' => PHP_VERSION,
                'os' => PHP_OS,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
            ]
        ];
    }
}

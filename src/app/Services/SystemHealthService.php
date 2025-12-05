<?php

namespace CiInbox\App\Services;

use CiInbox\App\DTOs\ModuleHealthDTO;
use CiInbox\App\DTOs\HealthAnalysisDTO;
use CiInbox\App\Interfaces\ModuleHealthInterface;
use Psr\Log\LoggerInterface;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * System Health Check Service
 * 
 * Sammelt Health-Status von allen Modulen und System-Komponenten.
 * Kompatibel mit Keep-it-easy Update-Server Protokoll.
 */
class SystemHealthService
{
    /**
     * @var ModuleHealthInterface[] Registrierte Module für Health-Checks
     */
    private array $modules = [];

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Registriert ein Modul für Health-Checks
     */
    public function registerModule(ModuleHealthInterface $module): void
    {
        $this->modules[$module->getModuleName()] = $module;
    }

    /**
     * Gibt einen einfachen Health-Status zurück (öffentlich)
     * 
     * @return array Basic health status
     */
    public function getBasicHealth(): array
    {
        try {
            $systemMetrics = $this->getSystemMetrics();
            $dbMetrics = $this->getDatabaseMetrics();

            // Simple OK/ERROR Status basierend auf kritischen Checks
            $status = 'ok';
            $checks = [
                'system' => $systemMetrics['disk_usage_percentage'] < 95,
                'database' => $dbMetrics['connection_status'] === 'ok'
            ];

            foreach ($checks as $check => $passed) {
                if (!$passed) {
                    $status = 'error';
                    break;
                }
            }

            return [
                'status' => $status,
                'timestamp' => time(),
                'version' => $_ENV['APP_VERSION'] ?? '0.1.0',
                'checks' => $checks
            ];
        } catch (\Exception $e) {
            $this->logger->error('Health check failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'error',
                'timestamp' => time(),
                'error' => 'Health check failed'
            ];
        }
    }

    /**
     * Gibt detaillierten Health-Status zurück (authentifiziert)
     * Kompatibel mit Keep-it-easy Update-Server Format
     * 
     * @return array Detailed health data
     */
    public function getDetailedHealth(): array
    {
        try {
            return [
                'timestamp' => time(),
                'version' => $_ENV['APP_VERSION'] ?? '0.1.0',
                'installation_id' => $_ENV['INSTALLATION_ID'] ?? 'ci-inbox-' . gethostname(),
                'system' => $this->getSystemMetrics(),
                'database' => $this->getDatabaseMetrics(),
                'modules' => $this->getModulesHealth(),
                'errors' => $this->getErrorMetrics(),
                'performance' => $this->getPerformanceMetrics()
            ];
        } catch (\Exception $e) {
            $this->logger->error('Detailed health check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Sammelt System-Metriken (Memory, Disk, PHP)
     */
    public function getSystemMetrics(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        $memoryPercentage = $memoryLimit > 0 ? round(($memoryUsage / $memoryLimit) * 100, 2) : 0;

        // Disk Space
        $diskFree = @disk_free_space(__DIR__);
        $diskTotal = @disk_total_space(__DIR__);
        $diskUsagePercentage = $diskTotal > 0 ? round((($diskTotal - $diskFree) / $diskTotal) * 100, 2) : 0;

        // Server Load (Unix only)
        $serverLoad = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];

        // Required PHP Extensions
        $requiredExtensions = ['openssl', 'pdo_mysql', 'imap', 'mbstring', 'json', 'curl'];
        $extensions = array_filter($requiredExtensions, function($ext) {
            return extension_loaded($ext);
        });

        return [
            'php_version' => PHP_VERSION,
            'memory_usage' => $memoryUsage,
            'memory_limit' => $memoryLimit,
            'memory_usage_percentage' => $memoryPercentage,
            'disk_free' => $diskFree ?: 0,
            'disk_total' => $diskTotal ?: 0,
            'disk_free_mb' => $diskFree ? round($diskFree / 1024 / 1024, 2) : 0,
            'disk_total_mb' => $diskTotal ? round($diskTotal / 1024 / 1024, 2) : 0,
            'disk_usage_percentage' => $diskUsagePercentage,
            'server_load' => $serverLoad,
            'extensions' => array_values($extensions),
            'extensions_missing' => array_values(array_diff($requiredExtensions, $extensions)),
            'os' => PHP_OS_FAMILY,
            'sapi' => PHP_SAPI
        ];
    }

    /**
     * Sammelt Database-Metriken
     */
    public function getDatabaseMetrics(): array
    {
        try {
            $startTime = microtime(true);
            
            // Test Connection
            $connection = DB::connection();
            $connection->getPdo();
            
            $latency = round((microtime(true) - $startTime) * 1000, 2); // ms

            // Count records
            $threadsTotal = DB::table('threads')->count();
            $threadsOpen = DB::table('threads')->where('status', 'open')->count();
            $emailsTotal = DB::table('emails')->count();
            $usersCount = DB::table('users')->count();
            $imapAccountsCount = DB::table('imap_accounts')->count();

            // Check for pending migrations (simplified - could check migrations table)
            $migrationFiles = glob(__DIR__ . '/../../database/migrations/*.php');
            $migrationCount = count($migrationFiles);

            return [
                'connection_status' => 'ok',
                'latency_ms' => $latency,
                'driver' => $connection->getDriverName(),
                'database' => $connection->getDatabaseName(),
                'migrations_current' => $migrationCount,
                'migrations_pending' => 0, // TODO: Implement proper check
                'threads_total' => $threadsTotal,
                'threads_open' => $threadsOpen,
                'emails_total' => $emailsTotal,
                'users_count' => $usersCount,
                'imap_accounts_count' => $imapAccountsCount
            ];
        } catch (\Exception $e) {
            $this->logger->error('Database health check failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'connection_status' => 'error',
                'error' => $e->getMessage(),
                'latency_ms' => 0,
                'threads_total' => 0,
                'threads_open' => 0,
                'emails_total' => 0,
                'users_count' => 0,
                'imap_accounts_count' => 0
            ];
        }
    }

    /**
     * Sammelt Health-Status aller registrierten Module
     */
    public function getModulesHealth(): array
    {
        $modulesHealth = [];

        foreach ($this->modules as $moduleName => $module) {
            try {
                $health = $module->getHealthStatus();
                $modulesHealth[$moduleName] = $health->toArray();
            } catch (\Exception $e) {
                $this->logger->error("Module health check failed: {$moduleName}", [
                    'error' => $e->getMessage()
                ]);

                $modulesHealth[$moduleName] = [
                    'module' => $moduleName,
                    'status' => ModuleHealthDTO::STATUS_ERROR,
                    'test_passed' => false,
                    'error' => $e->getMessage(),
                    'last_check' => time()
                ];
            }
        }

        return $modulesHealth;
    }

    /**
     * Sammelt Error-Metriken
     */
    public function getErrorMetrics(): array
    {
        $logPath = __DIR__ . '/../../logs/app.log';
        
        $phpErrors24h = 0;
        $phpWarnings24h = 0;
        $httpErrors24h = 0;
        $lastErrorTimestamp = null;
        $errorLogSize = 0;

        if (file_exists($logPath)) {
            $errorLogSize = filesize($logPath);
            
            // Parse last 24h of logs (simplified - production should use better method)
            $logLines = @file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($logLines !== false) {
                $cutoff = time() - (24 * 3600);
                
                foreach (array_reverse($logLines) as $line) {
                    // Simple timestamp extraction (assumes ISO format)
                    if (preg_match('/\[(\d{4}-\d{2}-\d{2}[T\s]\d{2}:\d{2}:\d{2})/', $line, $matches)) {
                        $timestamp = strtotime($matches[1]);
                        
                        if ($timestamp < $cutoff) {
                            break; // Older than 24h
                        }

                        if (stripos($line, 'ERROR') !== false) {
                            $phpErrors24h++;
                            $lastErrorTimestamp = $lastErrorTimestamp ?? $timestamp;
                        } elseif (stripos($line, 'WARNING') !== false) {
                            $phpWarnings24h++;
                        }
                    }
                }
            }
        }

        return [
            'php_errors_24h' => $phpErrors24h,
            'php_warnings_24h' => $phpWarnings24h,
            'http_errors_24h' => $httpErrors24h, // TODO: Implement proper tracking
            'last_error_timestamp' => $lastErrorTimestamp,
            'error_log_size' => $errorLogSize,
            'error_log_size_mb' => round($errorLogSize / 1024 / 1024, 2)
        ];
    }

    /**
     * Sammelt Performance-Metriken
     */
    public function getPerformanceMetrics(): array
    {
        // TODO: Implement proper performance tracking with APM
        // For now, return placeholder data
        
        return [
            'avg_response_time_ms' => 0,
            'max_response_time_ms' => 0,
            'uptime_percentage' => 100.0,
            'uptime_last_check' => time(),
            'requests_24h' => 0
        ];
    }

    /**
     * Führt alle Module-Tests aus
     */
    public function runModuleTests(): array
    {
        $results = [];

        foreach ($this->modules as $moduleName => $module) {
            try {
                $passed = $module->runHealthTest();
                $results[$moduleName] = [
                    'passed' => $passed,
                    'timestamp' => time()
                ];
            } catch (\Exception $e) {
                $this->logger->error("Module test failed: {$moduleName}", [
                    'error' => $e->getMessage()
                ]);

                $results[$moduleName] = [
                    'passed' => false,
                    'error' => $e->getMessage(),
                    'timestamp' => time()
                ];
            }
        }

        return $results;
    }

    /**
     * Analysiert Health-Daten und gibt Gesamtbewertung zurück
     */
    public function analyzeHealth(array $healthData): HealthAnalysisDTO
    {
        return HealthAnalysisDTO::fromHealthData($healthData);
    }

    /**
     * Generiert Keep-it-easy kompatiblen Health-Report
     */
    public function generateUpdateServerReport(): array
    {
        $detailed = $this->getDetailedHealth();
        
        return [
            'installation_id' => $detailed['installation_id'],
            'timestamp' => $detailed['timestamp'],
            'version' => $detailed['version'],
            'system' => [
                'php_version' => $detailed['system']['php_version'],
                'memory_usage' => $detailed['system']['memory_usage'],
                'disk_free' => $detailed['system']['disk_free'],
                'disk_total' => $detailed['system']['disk_total']
            ],
            'data' => [
                'submissions_count' => $detailed['database']['threads_total'] ?? 0,
                'maintainers_count' => $detailed['database']['users_count'] ?? 0,
                'buildings_count' => $detailed['database']['imap_accounts_count'] ?? 0
            ],
            'backups' => [
                'last_backup' => 0, // TODO: Implement backup system
                'backup_count' => 0,
                'total_backup_size' => 0
            ],
            'mail' => [
                'sent_24h' => $this->getMailMetrics()['sent_24h'] ?? 0,
                'failed_24h' => $this->getMailMetrics()['failed_24h'] ?? 0
            ],
            'errors' => [
                'php_errors_24h' => $detailed['errors']['php_errors_24h'],
                'http_errors_24h' => $detailed['errors']['http_errors_24h']
            ]
        ];
    }

    /**
     * Sammelt Mail-Metriken (für Keep-it-easy Report)
     */
    private function getMailMetrics(): array
    {
        // TODO: Implement mail tracking
        return [
            'sent_24h' => 0,
            'failed_24h' => 0
        ];
    }

    /**
     * Parst memory_limit String zu Bytes
     */
    private function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int)$limit;

        switch ($last) {
            case 'g':
                $value *= 1024;
                // no break
            case 'm':
                $value *= 1024;
                // no break
            case 'k':
                $value *= 1024;
        }

        return $value;
    }
}

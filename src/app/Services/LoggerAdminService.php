<?php
/**
 * Logger Admin Service
 * 
 * Provides logger configuration and management functionality for admin interface.
 * Handles log level configuration, log viewing, and file management.
 */

declare(strict_types=1);

namespace CiInbox\App\Services;

use CiInbox\Modules\Logger\LoggerInterface;
use CiInbox\App\Repositories\SystemSettingRepository;

class LoggerAdminService
{
    private string $logDir;
    
    public function __construct(
        private LoggerInterface $logger,
        private SystemSettingRepository $settingsRepository
    ) {
        $this->logDir = __DIR__ . '/../../../logs';
    }
    
    /**
     * Get current log level
     * 
     * @return array{level: string, available_levels: array}
     */
    public function getLogLevel(): array
    {
        try {
            $level = $this->settingsRepository->get('logger.level', 'info');
            
            return [
                'level' => $level,
                'available_levels' => ['debug', 'info', 'warning', 'error', 'critical']
            ];
        } catch (\Exception $e) {
            $this->logger->error('[LoggerAdmin] Failed to get log level', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'level' => 'info',
                'available_levels' => ['debug', 'info', 'warning', 'error', 'critical']
            ];
        }
    }
    
    /**
     * Set log level
     * 
     * @param string $level
     * @return array{success: bool, level: string, message: string}
     */
    public function setLogLevel(string $level): array
    {
        $validLevels = ['debug', 'info', 'warning', 'error', 'critical'];
        
        if (!in_array(strtolower($level), $validLevels)) {
            return [
                'success' => false,
                'level' => $this->getLogLevel()['level'],
                'message' => 'Invalid log level. Must be one of: ' . implode(', ', $validLevels)
            ];
        }
        
        try {
            $this->settingsRepository->set('logger.level', strtolower($level));
            
            $this->logger->info('[LoggerAdmin] Log level changed', [
                'new_level' => $level
            ]);
            
            return [
                'success' => true,
                'level' => strtolower($level),
                'message' => 'Log level set to ' . strtoupper($level)
            ];
        } catch (\Exception $e) {
            $this->logger->error('[LoggerAdmin] Failed to set log level', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'level' => $this->getLogLevel()['level'],
                'message' => 'Failed to set log level: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get log entries
     * 
     * @param int $limit
     * @param string|null $level
     * @param string|null $search
     * @return array
     */
    public function getLogEntries(int $limit = 100, ?string $level = null, ?string $search = null): array
    {
        try {
            $entries = [];
            $logFiles = glob($this->logDir . '/app-*.log');
            
            if (empty($logFiles)) {
                return ['entries' => [], 'total' => 0];
            }
            
            // Sort by modification time (newest first)
            usort($logFiles, function ($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            // Read the latest log file
            $latestLog = $logFiles[0];
            $lines = file($latestLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            if ($lines === false) {
                return ['entries' => [], 'total' => 0];
            }
            
            // Process lines in reverse order (newest first)
            $lines = array_reverse($lines);
            $count = 0;
            
            foreach ($lines as $line) {
                if ($count >= $limit) {
                    break;
                }
                
                $json = json_decode($line, true);
                if (!$json) {
                    continue;
                }
                
                // Filter by level
                if ($level && strtolower($json['level'] ?? '') !== strtolower($level)) {
                    continue;
                }
                
                // Filter by search term
                if ($search && stripos($json['message'] ?? '', $search) === false) {
                    continue;
                }
                
                $entries[] = [
                    'time' => $json['timestamp'] ?? '',
                    'level' => $json['level'] ?? 'info',
                    'message' => $json['message'] ?? '',
                    'context' => $json['context'] ?? []
                ];
                
                $count++;
            }
            
            return [
                'entries' => $entries,
                'total' => count($entries)
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('[LoggerAdmin] Failed to get log entries', [
                'error' => $e->getMessage()
            ]);
            
            return ['entries' => [], 'total' => 0, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get log file statistics
     * 
     * @return array
     */
    public function getLogStats(): array
    {
        try {
            $logFiles = glob($this->logDir . '/app-*.log');
            $totalSize = 0;
            $oldestDate = null;
            $newestDate = null;
            
            foreach ($logFiles as $file) {
                $totalSize += filesize($file);
                $mtime = filemtime($file);
                
                if ($oldestDate === null || $mtime < $oldestDate) {
                    $oldestDate = $mtime;
                }
                if ($newestDate === null || $mtime > $newestDate) {
                    $newestDate = $mtime;
                }
            }
            
            // Count entries by level in latest log
            $levelCounts = [
                'debug' => 0,
                'info' => 0,
                'warning' => 0,
                'error' => 0,
                'critical' => 0
            ];
            
            if (!empty($logFiles)) {
                usort($logFiles, function ($a, $b) {
                    return filemtime($b) - filemtime($a);
                });
                
                $latestLog = $logFiles[0];
                $lines = file($latestLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                
                if ($lines !== false) {
                    foreach ($lines as $line) {
                        $json = json_decode($line, true);
                        if ($json && isset($json['level'])) {
                            $level = strtolower($json['level']);
                            if (isset($levelCounts[$level])) {
                                $levelCounts[$level]++;
                            }
                        }
                    }
                }
            }
            
            return [
                'file_count' => count($logFiles),
                'total_size_bytes' => $totalSize,
                'total_size_mb' => round($totalSize / 1024 / 1024, 2),
                'oldest_entry' => $oldestDate ? date('Y-m-d H:i:s', $oldestDate) : null,
                'newest_entry' => $newestDate ? date('Y-m-d H:i:s', $newestDate) : null,
                'level_counts' => $levelCounts
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('[LoggerAdmin] Failed to get log stats', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'file_count' => 0,
                'total_size_bytes' => 0,
                'total_size_mb' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Clear all log files
     * 
     * @return array{success: bool, files_deleted: int, message: string}
     */
    public function clearLogs(): array
    {
        try {
            $logFiles = glob($this->logDir . '/app-*.log');
            $deleted = 0;
            
            foreach ($logFiles as $file) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
            
            $this->logger->info('[LoggerAdmin] Logs cleared', [
                'files_deleted' => $deleted
            ]);
            
            return [
                'success' => true,
                'files_deleted' => $deleted,
                'message' => "Deleted {$deleted} log file(s)"
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('[LoggerAdmin] Failed to clear logs', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'files_deleted' => 0,
                'message' => 'Failed to clear logs: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Download logs as a single file
     * 
     * @return array{success: bool, content: string|null, filename: string|null}
     */
    public function downloadLogs(): array
    {
        try {
            $logFiles = glob($this->logDir . '/app-*.log');
            
            if (empty($logFiles)) {
                return [
                    'success' => false,
                    'content' => null,
                    'filename' => null,
                    'message' => 'No log files found'
                ];
            }
            
            // Combine all log files
            $content = '';
            foreach ($logFiles as $file) {
                $content .= "=== " . basename($file) . " ===\n";
                $content .= file_get_contents($file);
                $content .= "\n\n";
            }
            
            return [
                'success' => true,
                'content' => $content,
                'filename' => 'ci-inbox-logs-' . date('Y-m-d') . '.txt'
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('[LoggerAdmin] Failed to download logs', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'content' => null,
                'filename' => null,
                'message' => 'Failed to download logs: ' . $e->getMessage()
            ];
        }
    }
}

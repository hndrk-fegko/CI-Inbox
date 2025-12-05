<?php
declare(strict_types=1);

namespace CiInbox\Modules\Logger;

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use CiInbox\Modules\Logger\Formatters\JsonFormatter;
use CiInbox\App\Interfaces\ModuleHealthInterface;
use CiInbox\App\DTOs\ModuleHealthDTO;

/**
 * CI-Inbox Logger Service
 * 
 * Wraps Monolog with custom configuration and formatting.
 * Implements PSR-3 LoggerInterface for compatibility.
 */
class LoggerService implements LoggerInterface, ModuleHealthInterface
{
    private Logger $logger;
    private string $logPath;
    private string $logLevel;

    /**
     * Create a new Logger Service
     * 
     * @param string $logPath Path to log directory
     * @param string $logLevel Minimum log level (debug, info, warning, error, critical)
     * @param string $channel Logger channel name
     */
    public function __construct(
        string $logPath = __DIR__ . '/../../../../logs',
        string $logLevel = 'debug',
        string $channel = 'app'
    ) {
        $this->logPath = $logPath;
        $this->logLevel = $logLevel;
        
        $this->logger = new Logger($channel);
        $this->setupHandlers();
    }

    /**
     * Setup log handlers
     */
    protected function setupHandlers(): void
    {
        // Rotating File Handler (rotates daily, keeps 30 days)
        $handler = new RotatingFileHandler(
            $this->logPath . '/app.log',
            30, // Max files
            $this->getLevelFromString($this->logLevel),
            true, // Bubble
            0664 // File permissions
        );

        // Use custom JSON formatter
        $handler->setFormatter(new JsonFormatter());

        $this->logger->pushHandler($handler);
    }

    /**
     * Convert log level string to Monolog Level enum
     * 
     * @param string $level
     * @return Level
     */
    protected function getLevelFromString(string $level): Level
    {
        return match (strtolower($level)) {
            'debug' => Level::Debug,
            'info' => Level::Info,
            'notice' => Level::Notice,
            'warning' => Level::Warning,
            'error' => Level::Error,
            'critical' => Level::Critical,
            'alert' => Level::Alert,
            'emergency' => Level::Emergency,
            default => Level::Debug,
        };
    }

    /**
     * Get the underlying Monolog instance
     * 
     * @return Logger
     */
    public function getMonolog(): Logger
    {
        return $this->logger;
    }

    // ========================================
    // PSR-3 LoggerInterface Implementation
    // ========================================

    /**
     * {@inheritDoc}
     */
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->logger->emergency($message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->logger->alert($message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->logger->notice($message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }

    /**
     * Log a success message (custom level)
     * 
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    public function success(string $message, array $context = []): void
    {
        // Map success to info level with special marker
        $context['_success'] = true;
        $this->logger->info($message, $context);
    }

    // ========================================
    // ModuleHealthInterface Implementation
    // ========================================

    /**
     * {@inheritDoc}
     */
    public function getModuleName(): string
    {
        return 'logger';
    }

    /**
     * {@inheritDoc}
     */
    public function getHealthStatus(): ModuleHealthDTO
    {
        $logFile = $this->logPath . '/app.log';
        $writable = is_writable(dirname($logFile));
        $exists = file_exists($logFile);
        $size = $exists ? filesize($logFile) : 0;
        $lastModified = $exists ? filemtime($logFile) : null;

        $status = ModuleHealthDTO::STATUS_OK;
        $errorMessage = null;

        if (!$writable) {
            $status = ModuleHealthDTO::STATUS_CRITICAL;
            $errorMessage = 'Log directory not writable';
        } elseif ($size > 100 * 1024 * 1024) { // > 100 MB
            $status = ModuleHealthDTO::STATUS_WARNING;
            $errorMessage = 'Log file size exceeds 100 MB';
        }

        return new ModuleHealthDTO(
            moduleName: $this->getModuleName(),
            status: $status,
            testPassed: $writable && $exists,
            metrics: [
                'log_file' => $logFile,
                'log_file_writable' => $writable,
                'log_file_exists' => $exists,
                'log_size_mb' => round($size / 1024 / 1024, 2),
                'last_modified' => $lastModified,
                'log_level' => $this->logLevel
            ],
            errorMessage: $errorMessage
        );
    }

    /**
     * {@inheritDoc}
     */
    public function runHealthTest(): bool
    {
        try {
            // Test: Write test log entry
            $testMessage = 'Health check test log entry';
            $this->debug($testMessage, ['health_check' => true]);

            // Test: Verify log file is writable
            $logFile = $this->logPath . '/app.log';
            return is_writable(dirname($logFile));
        } catch (\Exception $e) {
            return false;
        }
    }
}


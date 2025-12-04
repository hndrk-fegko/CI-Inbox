<?php
declare(strict_types=1);

namespace CiInbox\Modules\Logger\Formatters;

use Monolog\Formatter\JsonFormatter as MonologJsonFormatter;
use Monolog\LogRecord;

/**
 * Custom JSON Formatter for CI-Inbox
 * 
 * Extends Monolog's JsonFormatter with additional fields:
 * - module (from context)
 * - file, line, function (backtrace)
 * - memory_usage, execution_time
 */
class JsonFormatter extends MonologJsonFormatter
{
    /**
     * Format a log record
     * 
     * @param LogRecord $record
     * @return string JSON-encoded log entry
     */
    public function format(LogRecord $record): string
    {
        $data = [
            'timestamp' => $record->datetime->format('Y-m-d\TH:i:s.uP'),
            'level' => $record->level->getName(),
            'message' => $record->message,
            'context' => $record->context,
            'extra' => $this->addExtraFields($record),
        ];

        // Add exception details if present
        if (isset($record->context['exception']) && $record->context['exception'] instanceof \Throwable) {
            $data['exception'] = $this->formatException($record->context['exception']);
            unset($data['context']['exception']); // Remove from context to avoid duplication
        }

        return $this->toJson($data) . "\n";
    }

    /**
     * Add extra fields to log entry
     * 
     * @param LogRecord $record
     * @return array<string, mixed>
     */
    protected function addExtraFields(LogRecord $record): array
    {
        $extra = $record->extra;

        // Add module from context if available
        if (isset($record->context['module'])) {
            $extra['module'] = $record->context['module'];
        }

        // Add backtrace info (find first non-logger call)
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        foreach ($backtrace as $trace) {
            if (
                isset($trace['file']) &&
                !str_contains($trace['file'], 'Logger') &&
                !str_contains($trace['file'], 'vendor')
            ) {
                $extra['file'] = $trace['file'] ?? 'unknown';
                $extra['line'] = $trace['line'] ?? 0;
                $extra['function'] = $trace['function'] ?? 'unknown';
                break;
            }
        }

        // Add performance metrics
        $extra['memory_usage'] = $this->formatBytes(memory_get_usage(true));
        $extra['memory_peak'] = $this->formatBytes(memory_get_peak_usage(true));

        return $extra;
    }

    /**
     * Format exception for logging
     * 
     * @param \Throwable $exception
     * @return array<string, mixed>
     */
    protected function formatException(\Throwable $exception): array
    {
        return [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];
    }

    /**
     * Format bytes to human-readable format
     * 
     * @param int $bytes
     * @return string
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

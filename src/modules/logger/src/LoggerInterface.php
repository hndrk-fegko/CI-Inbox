<?php
declare(strict_types=1);

namespace CiInbox\Modules\Logger;

use Psr\Log\LoggerInterface as PsrLoggerInterface;

/**
 * CI-Inbox Logger Interface
 * 
 * Extends PSR-3 LoggerInterface for compatibility.
 * All services should depend on this interface, not the implementation.
 */
interface LoggerInterface extends PsrLoggerInterface
{
    /**
     * Log a success message (custom level for CI-Inbox)
     * 
     * @param string $message
     * @param array<string, mixed> $context
     * @return void
     */
    public function success(string $message, array $context = []): void;

    /**
     * Get the underlying Monolog instance (for advanced usage)
     * 
     * @return \Monolog\Logger
     */
    public function getMonolog(): \Monolog\Logger;
}

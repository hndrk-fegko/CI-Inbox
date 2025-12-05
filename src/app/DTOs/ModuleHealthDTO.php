<?php

namespace CiInbox\App\DTOs;

/**
 * Data Transfer Object für Module Health Status
 * 
 * Repräsentiert den Gesundheitsstatus eines einzelnen Moduls
 * mit Status, Metriken und Fehlerinformationen.
 */
class ModuleHealthDTO
{
    public const STATUS_OK = 'ok';
    public const STATUS_WARNING = 'warning';
    public const STATUS_CRITICAL = 'critical';
    public const STATUS_ERROR = 'error';

    public function __construct(
        public readonly string $moduleName,
        public readonly string $status,
        public readonly bool $testPassed,
        public readonly array $metrics = [],
        public readonly ?string $errorMessage = null,
        public readonly ?int $lastCheck = null
    ) {
        if (!in_array($status, [self::STATUS_OK, self::STATUS_WARNING, self::STATUS_CRITICAL, self::STATUS_ERROR])) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }
    }

    public function toArray(): array
    {
        $data = [
            'module' => $this->moduleName,
            'status' => $this->status,
            'test_passed' => $this->testPassed,
            'metrics' => $this->metrics,
            'last_check' => $this->lastCheck ?? time()
        ];

        if ($this->errorMessage !== null) {
            $data['error'] = $this->errorMessage;
        }

        return $data;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            moduleName: $data['module'] ?? 'unknown',
            status: $data['status'] ?? self::STATUS_ERROR,
            testPassed: $data['test_passed'] ?? false,
            metrics: $data['metrics'] ?? [],
            errorMessage: $data['error'] ?? null,
            lastCheck: $data['last_check'] ?? null
        );
    }

    public function isHealthy(): bool
    {
        return $this->status === self::STATUS_OK;
    }

    public function isWarning(): bool
    {
        return $this->status === self::STATUS_WARNING;
    }

    public function isCritical(): bool
    {
        return $this->status === self::STATUS_CRITICAL;
    }

    public function hasError(): bool
    {
        return $this->status === self::STATUS_ERROR;
    }
}

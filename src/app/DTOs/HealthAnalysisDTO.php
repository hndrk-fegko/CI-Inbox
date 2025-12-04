<?php

namespace CiInbox\App\DTOs;

/**
 * Data Transfer Object für System Health Analysis
 * 
 * Enthält die Gesamtauswertung des System-Gesundheitsstatus
 * basierend auf allen gesammelten Metriken und Tests.
 */
class HealthAnalysisDTO
{
    public const STATUS_HEALTHY = 'healthy';
    public const STATUS_WARNING = 'warning';
    public const STATUS_CRITICAL = 'critical';
    public const STATUS_ERROR = 'error';

    public function __construct(
        public readonly string $overallStatus,
        public readonly bool $isHealthy,
        public readonly array $issues = [],
        public readonly array $warnings = [],
        public readonly array $recommendations = [],
        public readonly int $timestamp = 0
    ) {
        if (!in_array($overallStatus, [
            self::STATUS_HEALTHY, 
            self::STATUS_WARNING, 
            self::STATUS_CRITICAL, 
            self::STATUS_ERROR
        ])) {
            throw new \InvalidArgumentException("Invalid status: {$overallStatus}");
        }
    }

    public function toArray(): array
    {
        return [
            'overall_status' => $this->overallStatus,
            'is_healthy' => $this->isHealthy,
            'issues' => $this->issues,
            'warnings' => $this->warnings,
            'recommendations' => $this->recommendations,
            'timestamp' => $this->timestamp ?: time()
        ];
    }

    public static function fromHealthData(array $healthData): self
    {
        $issues = [];
        $warnings = [];
        $recommendations = [];
        $overallStatus = self::STATUS_HEALTHY;

        // Analyze system metrics
        if (isset($healthData['system'])) {
            $system = $healthData['system'];
            
            // Memory usage check
            if (isset($system['memory_usage_percentage']) && $system['memory_usage_percentage'] > 90) {
                $issues[] = "Memory usage critical: {$system['memory_usage_percentage']}%";
                $overallStatus = self::STATUS_CRITICAL;
            } elseif (isset($system['memory_usage_percentage']) && $system['memory_usage_percentage'] > 80) {
                $warnings[] = "Memory usage high: {$system['memory_usage_percentage']}%";
                if ($overallStatus === self::STATUS_HEALTHY) {
                    $overallStatus = self::STATUS_WARNING;
                }
            }

            // Disk usage check
            if (isset($system['disk_usage_percentage']) && $system['disk_usage_percentage'] > 90) {
                $issues[] = "Disk usage critical: {$system['disk_usage_percentage']}%";
                $overallStatus = self::STATUS_CRITICAL;
            } elseif (isset($system['disk_usage_percentage']) && $system['disk_usage_percentage'] > 80) {
                $warnings[] = "Disk usage high: {$system['disk_usage_percentage']}%";
                if ($overallStatus === self::STATUS_HEALTHY) {
                    $overallStatus = self::STATUS_WARNING;
                }
            }
        }

        // Analyze database metrics
        if (isset($healthData['database'])) {
            $db = $healthData['database'];
            
            if (isset($db['connection_status']) && $db['connection_status'] !== 'ok') {
                $issues[] = "Database connection failed";
                $overallStatus = self::STATUS_CRITICAL;
            }

            if (isset($db['migrations_pending']) && $db['migrations_pending'] > 0) {
                $warnings[] = "{$db['migrations_pending']} database migrations pending";
                if ($overallStatus === self::STATUS_HEALTHY) {
                    $overallStatus = self::STATUS_WARNING;
                }
            }
        }

        // Analyze module health
        if (isset($healthData['modules'])) {
            foreach ($healthData['modules'] as $moduleName => $moduleData) {
                $status = $moduleData['status'] ?? 'error';
                
                if ($status === ModuleHealthDTO::STATUS_ERROR) {
                    $issues[] = "Module {$moduleName} has errors";
                    $overallStatus = self::STATUS_CRITICAL;
                } elseif ($status === ModuleHealthDTO::STATUS_CRITICAL) {
                    $issues[] = "Module {$moduleName} in critical state";
                    $overallStatus = self::STATUS_CRITICAL;
                } elseif ($status === ModuleHealthDTO::STATUS_WARNING) {
                    $warnings[] = "Module {$moduleName} has warnings";
                    if ($overallStatus === self::STATUS_HEALTHY) {
                        $overallStatus = self::STATUS_WARNING;
                    }
                }
            }
        }

        // Analyze errors
        if (isset($healthData['errors'])) {
            $errors = $healthData['errors'];
            
            if (isset($errors['php_errors_24h']) && $errors['php_errors_24h'] > 50) {
                $issues[] = "High PHP error rate: {$errors['php_errors_24h']} errors in 24h";
                $overallStatus = self::STATUS_CRITICAL;
            } elseif (isset($errors['php_errors_24h']) && $errors['php_errors_24h'] > 10) {
                $warnings[] = "Elevated PHP error rate: {$errors['php_errors_24h']} errors in 24h";
                if ($overallStatus === self::STATUS_HEALTHY) {
                    $overallStatus = self::STATUS_WARNING;
                }
            }
        }

        // Generate recommendations
        if (!empty($warnings)) {
            $recommendations[] = "Review system warnings and address issues";
        }
        if (!empty($issues)) {
            $recommendations[] = "Immediate action required - critical issues detected";
        }

        return new self(
            overallStatus: $overallStatus,
            isHealthy: $overallStatus === self::STATUS_HEALTHY,
            issues: $issues,
            warnings: $warnings,
            recommendations: $recommendations,
            timestamp: time()
        );
    }
}

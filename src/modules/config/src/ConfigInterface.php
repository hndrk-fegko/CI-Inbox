<?php
declare(strict_types=1);

namespace CiInbox\Modules\Config;

/**
 * Configuration Service Interface
 * 
 * Provides type-safe access to configuration values from ENV and PHP config files.
 */
interface ConfigInterface
{
    /**
     * Get a configuration value
     * 
     * Supports dot notation: 'database.host'
     * 
     * @param string $key Configuration key
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Get a string configuration value
     * 
     * @param string $key
     * @param string|null $default
     * @return string
     * @throws \CiInbox\Modules\Config\Exceptions\ConfigException If value is not a string
     */
    public function getString(string $key, ?string $default = null): string;

    /**
     * Get an integer configuration value
     * 
     * @param string $key
     * @param int|null $default
     * @return int
     * @throws \CiInbox\Modules\Config\Exceptions\ConfigException If value is not an integer
     */
    public function getInt(string $key, ?int $default = null): int;

    /**
     * Get a boolean configuration value
     * 
     * @param string $key
     * @param bool|null $default
     * @return bool
     * @throws \CiInbox\Modules\Config\Exceptions\ConfigException If value is not a boolean
     */
    public function getBool(string $key, ?bool $default = null): bool;

    /**
     * Get an array configuration value
     * 
     * @param string $key
     * @param array|null $default
     * @return array
     * @throws \CiInbox\Modules\Config\Exceptions\ConfigException If value is not an array
     */
    public function getArray(string $key, ?array $default = null): array;

    /**
     * Check if a configuration key exists
     * 
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Get all configuration
     * 
     * @return array
     */
    public function all(): array;

    /**
     * Reload configuration from files
     * 
     * @return void
     */
    public function reload(): void;
}

<?php
declare(strict_types=1);

namespace CiInbox\Modules\Config\Exceptions;

/**
 * Configuration Exception
 * 
 * Thrown when configuration is invalid or missing.
 */
class ConfigException extends \Exception
{
    /**
     * Create exception for missing required key
     * 
     * @param string $key
     * @return self
     */
    public static function missingKey(string $key): self
    {
        return new self("Required configuration key '{$key}' is missing");
    }

    /**
     * Create exception for invalid type
     * 
     * @param string $key
     * @param string $expected
     * @param string $actual
     * @return self
     */
    public static function invalidType(string $key, string $expected, string $actual): self
    {
        return new self("Configuration key '{$key}' must be of type {$expected}, got {$actual}");
    }

    /**
     * Create exception for invalid ENV file
     * 
     * @param string $path
     * @return self
     */
    public static function invalidEnvFile(string $path): self
    {
        return new self(".env file not found at: {$path}");
    }
}

<?php
declare(strict_types=1);

namespace CiInbox\Modules\Config;

use Dotenv\Dotenv;
use CiInbox\Modules\Config\Exceptions\ConfigException;
use CiInbox\Modules\Logger\LoggerService;

/**
 * Configuration Service
 * 
 * Loads and manages configuration from:
 * 1. .env files (via vlucas/phpdotenv)
 * 2. PHP config files in src/config/
 * 
 * Provides type-safe access with dot notation support.
 */
class ConfigService implements ConfigInterface
{
    /** @var array<string, mixed> Cached configuration */
    private array $config = [];

    /** @var string Path to project root */
    private string $basePath;

    /** @var string Path to config directory */
    private string $configPath;

    /** @var bool Configuration loaded */
    private bool $loaded = false;

    /** @var LoggerService|null Logger service */
    private ?LoggerService $logger;
    
    /** @var array<string, string> Loaded environment variables */
    private array $envVars = [];

    /**
     * Create a new Config Service
     * 
     * @param string|null $envPath Path to directory containing .env (default: project root)
     * @param string|null $configPath Path to config directory (default: src/config)
     * @param LoggerService|null $logger Logger service (optional to avoid circular dependency)
     */
    public function __construct(?string $envPath = null, ?string $configPath = null, ?LoggerService $logger = null)
    {
        $this->logger = $logger;
        $this->basePath = $envPath ?? $this->detectBasePath();
        $this->configPath = $configPath ?? $this->basePath . '/src/config';
        
        $this->load();
    }

    /**
     * Detect project base path
     * 
     * @return string
     */
    private function detectBasePath(): string
    {
        // Go up from modules/config to project root
        return dirname(__DIR__, 4);
    }

    /**
     * Load configuration from .env and PHP files
     * 
     * @return void
     */
    private function load(): void
    {
        if ($this->loaded) {
            return;
        }

        // 1. Load .env file
        $this->loadEnv();

        // 2. Load PHP config files
        $this->loadPhpConfigs();

        $this->loaded = true;
    }

    /**
     * Load environment variables from .env
     * 
     * @return void
     */
    private function loadEnv(): void
    {
        $envFile = $this->basePath . '/.env';
        
        if (!file_exists($envFile)) {
            $this->logger?->debug('No .env file found', ['path' => $envFile]);
            return;
        }

        try {
            // Parse .env file manually and store in $this->envVars
            // This works even if variables_order doesn't include 'E'
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            
            foreach ($lines as $line) {
                // Skip comments and empty lines
                $line = trim($line);
                if (empty($line) || str_starts_with($line, '#')) {
                    continue;
                }
                
                // Parse KEY=VALUE format
                if (preg_match('/^([A-Z_][A-Z0-9_]*)\s*=\s*(.*)$/', $line, $matches)) {
                    $key = $matches[1];
                    $value = $matches[2];
                    
                    // Remove quotes if present
                    $value = trim($value);
                    if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                        (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                        $value = substr($value, 1, -1);
                    }
                    
                    $this->envVars[$key] = $value;
                }
            }
            
            $this->logger?->info('.env file loaded successfully', [
                'path' => $envFile,
                'keys' => count($this->envVars)
            ]);
        } catch (\Exception $e) {
            $this->logger?->warning('.env file loading failed', [
                'path' => $envFile,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Load PHP configuration files from src/config/
     * 
     * @return void
     */
    private function loadPhpConfigs(): void
    {
        if (!is_dir($this->configPath)) {
            $this->logger?->debug('Config directory not found', ['path' => $this->configPath]);
            return;
        }

        $files = glob($this->configPath . '/*.php');
        
        if (empty($files)) {
            $this->logger?->debug('No PHP config files found', ['path' => $this->configPath]);
            return;
        }

        foreach ($files as $file) {
            $key = basename($file, '.php');
            $this->config[$key] = require $file;
            $this->logger?->debug('Loaded config file', ['key' => $key, 'file' => $file]);
        }
        
        $this->logger?->info('PHP config files loaded', ['count' => count($files)]);
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->getNestedValue($key) ?? $default;
    }

    /**
     * {@inheritDoc}
     */
    public function getString(string $key, ?string $default = null): string
    {
        $value = $this->get($key, $default);
        
        if ($value === null) {
            $this->logger?->error('Config key missing', ['key' => $key]);
            throw ConfigException::missingKey($key);
        }
        
        if (!is_string($value)) {
            $this->logger?->error('Config type mismatch', [
                'key' => $key,
                'expected' => 'string',
                'actual' => gettype($value)
            ]);
            throw ConfigException::invalidType($key, 'string', gettype($value));
        }
        
        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getInt(string $key, ?int $default = null): int
    {
        $value = $this->get($key, $default);
        
        if ($value === null) {
            throw ConfigException::missingKey($key);
        }
        
        if (!is_int($value) && !is_numeric($value)) {
            throw ConfigException::invalidType($key, 'int', gettype($value));
        }
        
        return (int) $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getBool(string $key, ?bool $default = null): bool
    {
        $value = $this->get($key, $default);
        
        if ($value === null) {
            throw ConfigException::missingKey($key);
        }
        
        // Handle string booleans from ENV
        if (is_string($value)) {
            return in_array(strtolower($value), ['true', '1', 'yes', 'on'], true);
        }
        
        if (!is_bool($value)) {
            throw ConfigException::invalidType($key, 'bool', gettype($value));
        }
        
        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getArray(string $key, ?array $default = null): array
    {
        $value = $this->get($key, $default);
        
        if ($value === null) {
            throw ConfigException::missingKey($key);
        }
        
        if (!is_array($value)) {
            throw ConfigException::invalidType($key, 'array', gettype($value));
        }
        
        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        return $this->getNestedValue($key) !== null;
    }

    /**
     * {@inheritDoc}
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * {@inheritDoc}
     */
    public function reload(): void
    {
        $this->logger?->info('Reloading configuration');
        $this->loaded = false;
        $this->config = [];
        $this->load();
        $this->logger?->info('Configuration reloaded', ['keys' => count($this->config)]);
    }

    /**
     * Get nested value using dot notation
     * 
     * Example: 'database.host' -> $config['database']['host']
     * 
     * @param string $key
     * @return mixed
     */
    private function getNestedValue(string $key): mixed
    {
        // No dot = top-level key
        if (!str_contains($key, '.')) {
            // Check config array first
            if (array_key_exists($key, $this->config)) {
                return $this->config[$key];
            }
            
            // Fall back to envVars for top-level ENV variables
            return $this->envVars[$key] ?? null;
        }

        // Split by dot and traverse
        $segments = explode('.', $key);
        $value = $this->config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    // ========================================
    // ModuleHealthInterface Implementation
    // ========================================

    /**
     * {@inheritDoc}
     */
    public function getModuleName(): string
    {
        return 'config';
    }
}


<?php
/**
 * CI-Inbox Installation Verification Script
 * 
 * Verifies all requirements, configuration, and system health.
 * Suitable for production readiness checks and CI/CD pipelines.
 * 
 * Usage:
 *   php scripts/verify-installation.php [--verbose]
 * 
 * Exit Codes:
 *   0 - All checks passed
 *   1 - One or more critical checks failed
 *   2 - Script execution error
 */

declare(strict_types=1);

// Parse CLI arguments
$verbose = in_array('--verbose', $argv) || in_array('-v', $argv);

// Colors for terminal output
define('COLOR_GREEN', "\033[0;32m");
define('COLOR_RED', "\033[0;31m");
define('COLOR_YELLOW', "\033[1;33m");
define('COLOR_BLUE', "\033[0;34m");
define('COLOR_RESET', "\033[0m");

$failedChecks = 0;
$warnings = 0;
$totalChecks = 0;

function printHeader(string $text): void {
    echo "\n" . COLOR_BLUE . str_repeat('=', 50) . COLOR_RESET . "\n";
    echo COLOR_BLUE . $text . COLOR_RESET . "\n";
    echo COLOR_BLUE . str_repeat('=', 50) . COLOR_RESET . "\n\n";
}

function printCheck(string $name, bool $passed, ?string $detail = null, bool $isWarning = false): void {
    global $failedChecks, $warnings, $totalChecks, $verbose;
    
    $totalChecks++;
    
    if ($passed) {
        echo COLOR_GREEN . "✔ " . COLOR_RESET . $name;
        if ($verbose && $detail) {
            echo COLOR_GREEN . " → " . $detail . COLOR_RESET;
        }
        echo "\n";
    } else {
        if ($isWarning) {
            $warnings++;
            echo COLOR_YELLOW . "⚠ " . COLOR_RESET . $name;
            if ($detail) {
                echo COLOR_YELLOW . " → " . $detail . COLOR_RESET;
            }
        } else {
            $failedChecks++;
            echo COLOR_RED . "✘ " . COLOR_RESET . $name;
            if ($detail) {
                echo COLOR_RED . " → " . $detail . COLOR_RESET;
            }
        }
        echo "\n";
    }
}

try {
    echo "\n";
    echo COLOR_BLUE . "╔════════════════════════════════════════════════╗\n";
    echo "║  CI-Inbox Installation Verification           ║\n";
    echo "╚════════════════════════════════════════════════╝" . COLOR_RESET . "\n";

    // ========================================
    // 1. PHP Environment Checks
    // ========================================
    printHeader("PHP Environment");

    // PHP Version
    $phpVersion = PHP_VERSION;
    $phpVersionOk = version_compare($phpVersion, '8.1.0', '>=');
    printCheck("PHP Version >= 8.1", $phpVersionOk, "Current: $phpVersion");

    // Required Extensions
    $requiredExtensions = [
        'openssl' => 'Encryption support',
        'pdo' => 'Database connectivity',
        'pdo_mysql' => 'MySQL database driver',
        'imap' => 'IMAP email fetching',
        'mbstring' => 'Multi-byte string handling',
        'json' => 'JSON encoding/decoding',
        'curl' => 'HTTP requests',
        'fileinfo' => 'File type detection',
        'zip' => 'Archive handling'
    ];

    foreach ($requiredExtensions as $ext => $purpose) {
        $loaded = extension_loaded($ext);
        printCheck("Extension: $ext", $loaded, $purpose);
    }

    // Memory Limit
    $memoryLimit = ini_get('memory_limit');
    $memoryBytes = parse_memory_limit($memoryLimit);
    $memoryOk = $memoryBytes >= 128 * 1024 * 1024 || $memoryBytes === -1;
    printCheck("Memory Limit >= 128M", $memoryOk, "Current: $memoryLimit", !$memoryOk);

    // ========================================
    // 2. File System Checks
    // ========================================
    printHeader("File System");

    // Project root
    $root = dirname(__DIR__);
    printCheck("Project root detected", true, $verbose ? $root : null);

    // Required directories
    $requiredDirs = [
        'logs' => $root . '/logs',
        'data/cache' => $root . '/data/cache',
        'data/sessions' => $root . '/data/sessions',
        'data/uploads' => $root . '/data/uploads',
        'vendor' => $root . '/vendor',
    ];

    foreach ($requiredDirs as $name => $path) {
        $exists = is_dir($path);
        $writable = $exists && is_writable($path);
        
        if (!$exists) {
            printCheck("Directory: $name (exists)", false, $path);
        } else {
            printCheck("Directory: $name (writable)", $writable, $verbose ? $path : null);
        }
    }

    // ========================================
    // 3. Configuration Checks
    // ========================================
    printHeader("Configuration");

    // .env file
    $envFile = $root . '/.env';
    $envExists = file_exists($envFile);
    printCheck(".env file exists", $envExists, $verbose ? $envFile : null);

    if ($envExists) {
        // Load .env
        require_once $root . '/vendor/autoload.php';
        $dotenv = Dotenv\Dotenv::createImmutable($root);
        $dotenv->load();

        // Check required ENV variables
        $requiredEnvKeys = [
            'APP_ENV' => 'Application environment',
            'APP_DEBUG' => 'Debug mode',
            'DB_HOST' => 'Database host',
            'DB_DATABASE' => 'Database name',
            'DB_USERNAME' => 'Database user',
            'DB_PASSWORD' => 'Database password',
            'ENCRYPTION_KEY' => 'Encryption key',
        ];

        foreach ($requiredEnvKeys as $key => $purpose) {
            $value = $_ENV[$key] ?? null;
            $exists = !empty($value);
            printCheck("ENV: $key", $exists, $verbose ? $purpose : null);
        }

        // Check encryption key format
        if (!empty($_ENV['ENCRYPTION_KEY'])) {
            $keyString = $_ENV['ENCRYPTION_KEY'];
            if (str_starts_with($keyString, 'base64:')) {
                $keyString = substr($keyString, 7);
            }
            $key = base64_decode($keyString, true);
            $keyValid = $key !== false && strlen($key) === 32;
            printCheck("Encryption key valid (32 bytes)", $keyValid, $verbose ? "Length: " . strlen($key ?: '') : null);
        }
    }

    // ========================================
    // 4. Database Checks
    // ========================================
    printHeader("Database");

    if ($envExists && !empty($_ENV['DB_HOST'])) {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                $_ENV['DB_HOST'],
                $_ENV['DB_DATABASE']
            );
            
            $pdo = new PDO(
                $dsn,
                $_ENV['DB_USERNAME'],
                $_ENV['DB_PASSWORD'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            printCheck("Database connection", true, $_ENV['DB_DATABASE'] . '@' . $_ENV['DB_HOST']);

            // Check tables exist
            $tables = [
                'users', 'imap_accounts', 'threads', 'emails', 
                'labels', 'thread_assignments', 'thread_labels',
                'internal_notes', 'webhooks'
            ];

            foreach ($tables as $table) {
                $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                $exists = $stmt->rowCount() > 0;
                printCheck("Table: $table", $exists, null, false);
            }

            // Count records
            if ($verbose) {
                $stmt = $pdo->query("SELECT COUNT(*) FROM users");
                $userCount = $stmt->fetchColumn();
                printCheck("Users count", true, "$userCount users");

                $stmt = $pdo->query("SELECT COUNT(*) FROM imap_accounts");
                $accountCount = $stmt->fetchColumn();
                printCheck("IMAP accounts count", true, "$accountCount accounts");

                $stmt = $pdo->query("SELECT COUNT(*) FROM threads");
                $threadCount = $stmt->fetchColumn();
                printCheck("Threads count", true, "$threadCount threads");
            }

        } catch (PDOException $e) {
            printCheck("Database connection", false, $e->getMessage());
        }
    } else {
        printCheck("Database credentials configured", false, "Missing DB_* variables");
    }

    // ========================================
    // 5. System Health Check (via API)
    // ========================================
    printHeader("System Health (API)");

    if ($envExists) {
        // Initialize application
        require_once $root . '/vendor/autoload.php';
        require_once $root . '/src/bootstrap/database.php';
        
        $container = CiInbox\Core\Container::getInstance();
        
        try {
            $healthService = $container->get(\App\Services\SystemHealthService::class);
            
            // Get detailed health
            $health = $healthService->getDetailedHealth();
            $analysis = $healthService->analyzeHealth($health);
            
            printCheck("Overall system status", $analysis->isHealthy, $analysis->overallStatus);
            
            // Module health
            if (isset($health['modules'])) {
                foreach ($health['modules'] as $moduleName => $moduleData) {
                    $status = $moduleData['status'] ?? 'unknown';
                    $testPassed = $moduleData['test_passed'] ?? false;
                    $isOk = $status === 'ok' && $testPassed;
                    $isWarning = $status === 'warning';
                    printCheck("Module: $moduleName", $isOk, "Status: $status", $isWarning);
                }
            }
            
            // Show issues/warnings
            if (!empty($analysis->issues)) {
                echo COLOR_RED . "\nIssues:\n" . COLOR_RESET;
                foreach ($analysis->issues as $issue) {
                    echo COLOR_RED . "  • " . $issue . COLOR_RESET . "\n";
                }
            }
            
            if (!empty($analysis->warnings)) {
                echo COLOR_YELLOW . "\nWarnings:\n" . COLOR_RESET;
                foreach ($analysis->warnings as $warning) {
                    echo COLOR_YELLOW . "  • " . $warning . COLOR_RESET . "\n";
                }
            }
            
        } catch (Exception $e) {
            printCheck("Health Service", false, $e->getMessage());
        }
    }

    // ========================================
    // Summary
    // ========================================
    echo "\n";
    echo COLOR_BLUE . str_repeat('=', 50) . COLOR_RESET . "\n";
    echo "Total Checks: $totalChecks\n";
    echo COLOR_GREEN . "Passed: " . ($totalChecks - $failedChecks - $warnings) . COLOR_RESET . "\n";
    if ($warnings > 0) {
        echo COLOR_YELLOW . "Warnings: $warnings" . COLOR_RESET . "\n";
    }
    if ($failedChecks > 0) {
        echo COLOR_RED . "Failed: $failedChecks" . COLOR_RESET . "\n";
    }
    echo COLOR_BLUE . str_repeat('=', 50) . COLOR_RESET . "\n\n";

    if ($failedChecks === 0) {
        echo COLOR_GREEN . "✔ All critical checks passed!" . COLOR_RESET . "\n";
        if ($warnings > 0) {
            echo COLOR_YELLOW . "⚠ There are $warnings warnings that should be reviewed." . COLOR_RESET . "\n\n";
        } else {
            echo COLOR_GREEN . "Installation is ready for production!" . COLOR_RESET . "\n\n";
        }
        exit(0);
    } else {
        echo COLOR_RED . "✘ $failedChecks critical check(s) failed." . COLOR_RESET . "\n";
        echo COLOR_RED . "Please fix the issues before deploying to production." . COLOR_RESET . "\n\n";
        exit(1);
    }

} catch (Throwable $e) {
    echo COLOR_RED . "\n✘ Script execution failed:\n" . COLOR_RESET;
    echo COLOR_RED . $e->getMessage() . COLOR_RESET . "\n";
    echo COLOR_RED . $e->getTraceAsString() . COLOR_RESET . "\n\n";
    exit(2);
}

// Helper function
function parse_memory_limit(string $limit): int {
    if ($limit === '-1') {
        return -1;
    }
    
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

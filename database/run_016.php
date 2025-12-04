<?php

/**
 * Run only migration 016 (create system_settings table)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CiInbox\Modules\Config\ConfigService;

// Load config
$config = new ConfigService(__DIR__ . '/../');

// Initialize database
require_once __DIR__ . '/../src/bootstrap/database.php';
$capsule = initDatabase($config);

echo "=== Running Migration 016 ===" . PHP_EOL . PHP_EOL;

$migration = __DIR__ . '/migrations/016_create_system_settings_table.php';

try {
    require_once $migration;
    echo PHP_EOL . "✅ Migration 016 completed" . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

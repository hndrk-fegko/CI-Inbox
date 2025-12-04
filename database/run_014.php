<?php

/**
 * Run only migration 014 - Create signatures table
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CiInbox\Modules\Config\ConfigService;
use Illuminate\Database\Capsule\Manager as Capsule;

// Load config
$config = new ConfigService(__DIR__ . '/../');

// Initialize database
require_once __DIR__ . '/../src/bootstrap/database.php';
$capsule = initDatabase($config);

echo "=== Running Migration 014: Create signatures table ===" . PHP_EOL . PHP_EOL;

try {
    // Check if table already exists
    if (Capsule::schema()->hasTable('signatures')) {
        echo "Table 'signatures' already exists. Dropping..." . PHP_EOL;
        Capsule::schema()->dropIfExists('signatures');
    }
    
    $migration = require __DIR__ . '/migrations/014_create_signatures_table.php';
    $migration->up();
    echo PHP_EOL . "✅ Migration 014 completed successfully!" . PHP_EOL;
} catch (Exception $e) {
    echo PHP_EOL . "❌ Migration 014 failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

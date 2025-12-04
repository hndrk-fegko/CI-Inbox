<?php

/**
 * Run only Migration 008 (internal_notes)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CiInbox\Modules\Config\ConfigService;
use Illuminate\Database\Capsule\Manager as Capsule;

// Load config
$config = new ConfigService(__DIR__ . '/../');

// Initialize database
require_once __DIR__ . '/../src/bootstrap/database.php';
$capsule = initDatabase($config);

echo "=== Running Migration 008 (internal_notes) ===" . PHP_EOL . PHP_EOL;

try {
    // Check if table already exists
    if (Capsule::schema()->hasTable('internal_notes')) {
        echo "Table 'internal_notes' already exists. Dropping..." . PHP_EOL;
        Capsule::schema()->dropIfExists('internal_notes');
    }
    
    // Run migration
    $migration = require __DIR__ . '/migrations/008_create_internal_notes_table.php';
    $migration->up();
    
    echo "✅ Migration 008 completed successfully!" . PHP_EOL;
    
    // Verify table
    if (Capsule::schema()->hasTable('internal_notes')) {
        echo "✅ Table 'internal_notes' created" . PHP_EOL;
        
        // Get columns
        $columns = Capsule::schema()->getColumnListing('internal_notes');
        echo "   Columns: " . implode(', ', $columns) . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

echo PHP_EOL . "=== Done ===" . PHP_EOL;

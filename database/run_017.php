<?php
/**
 * Run migration 017: Create cron_executions table
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CiInbox\Modules\Config\ConfigService;
use Illuminate\Database\Capsule\Manager as Capsule;

// Initialize config
$config = new ConfigService(__DIR__ . '/../');

// Initialize database
require_once __DIR__ . '/../src/bootstrap/database.php';
initDatabase($config);

echo "Running migration 017: Create cron_executions table\n\n";

// Run migration
require __DIR__ . '/migrations/017_create_cron_executions_table.php';

echo "\nMigration completed.\n";

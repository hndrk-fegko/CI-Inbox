<?php

/**
 * Run only migration 018
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CiInbox\Modules\Config\ConfigService;
use Illuminate\Database\Capsule\Manager as Capsule;

// Initialize config
$config = new ConfigService(__DIR__ . '/../');

// Initialize database
require_once __DIR__ . '/../src/bootstrap/database.php';
initDatabase($config);

echo "Running migration 018...\n\n";

$migration = require __DIR__ . '/migrations/018_add_updated_by_to_internal_notes.php';
$migration->up();

echo "\n✅ Migration 018 completed\n";

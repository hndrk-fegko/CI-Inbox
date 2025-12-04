<?php
/**
 * Run Migration 019: Add avatar_color to users
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CiInbox\Modules\Config\ConfigService;

// Initialize
$config = new ConfigService(__DIR__ . '/../');

// Initialize database
require_once __DIR__ . '/../src/bootstrap/database.php';
initDatabase($config);

echo "=== Running Migration 019 ===" . PHP_EOL . PHP_EOL;

try {
    require_once __DIR__ . '/migrations/019_add_avatar_color_to_users.php';
    echo PHP_EOL . "✅ Migration 019 completed successfully!" . PHP_EOL;
} catch (Exception $e) {
    echo PHP_EOL . "❌ Migration 019 failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

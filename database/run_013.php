<?php
/**
 * Run single migration: 013_add_user_settings_fields.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CiInbox\Core\Container;

// Initialize container and database
$container = Container::getInstance();
$container->get('database');

echo "\ud83d\udd04 Running migration 013: Add user settings fields...\n\n";

try {
    require __DIR__ . '/migrations/013_add_user_settings_fields.php';
    echo "\n\u2705 Migration 013 completed successfully!\n";
} catch (\Exception $e) {
    echo "\n\u274c Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

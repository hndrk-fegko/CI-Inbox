<?php
/**
 * Run single migration: 012_add_archived_status.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CiInbox\Core\Container;

// Initialize container and database
$container = Container::getInstance();
$container->get('database');

echo "\ud83d\udd04 Running migration 012: Add archived status...\n\n";

try {
    require __DIR__ . '/migrations/012_add_archived_status.php';
    echo "\n\u2705 Migration 012 completed successfully!\n";
} catch (\Exception $e) {
    echo "\n\u274c Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

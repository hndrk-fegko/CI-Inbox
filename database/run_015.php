<?php
/**
 * Run single migration: 015_update_thread_status_enum.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CiInbox\Core\Container;

// Initialize container and database
$container = Container::getInstance();
$container->get('database');

echo "🔄 Running migration 015: Update thread status enum...\n\n";

try {
    require __DIR__ . '/migrations/015_update_thread_status_enum.php';
    echo "\n✅ Migration 015 completed successfully!\n";
} catch (\Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

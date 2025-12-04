<?php

/**
 * Migration Runner
 * 
 * Simple migration system for running SQL migrations.
 * Run with: php database/migrate.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CiInbox\Modules\Config\ConfigService;

// Load config
$config = new ConfigService(__DIR__ . '/../');

// Initialize database
require_once __DIR__ . '/../src/bootstrap/database.php';
$capsule = initDatabase($config);

echo "=== CI-Inbox Database Migration Runner ===" . PHP_EOL . PHP_EOL;

// Get all migration files
$migrationDir = __DIR__ . '/migrations';
$migrations = glob($migrationDir . '/*.php');
sort($migrations);

echo "Found " . count($migrations) . " migration(s)" . PHP_EOL . PHP_EOL;

// Run each migration
foreach ($migrations as $migration) {
    $filename = basename($migration);
    echo "Running: {$filename}... ";
    
    try {
        require_once $migration;
        echo "✅ Done" . PHP_EOL;
    } catch (Exception $e) {
        echo "❌ Failed: " . $e->getMessage() . PHP_EOL;
        exit(1);
    }
}

echo PHP_EOL . "=== All migrations completed ===" . PHP_EOL;

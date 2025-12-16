<?php

/**
 * Migration: Add Theme Mode to Users
 * 
 * Adds theme_mode column to users table for dark mode preference.
 * Values: 'auto' (system), 'light', 'dark'
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

echo "Running migration: 016_add_theme_mode_to_users.php\n";

try {
    Capsule::schema()->table('users', function ($table) {
        // Theme mode (auto, light, dark)
        if (!Capsule::schema()->hasColumn('users', 'theme_mode')) {
            $table->enum('theme_mode', ['auto', 'light', 'dark'])
                  ->default('auto')
                  ->after('language');
            echo "  ✓ Added theme_mode column\n";
        } else {
            echo "  - theme_mode already exists\n";
        }
    });
    
    echo "✅ Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

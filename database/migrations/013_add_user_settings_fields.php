<?php

/**
 * Migration: Add User Settings Fields
 * 
 * Adds avatar_path, timezone, language to users table
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use CiInbox\Core\Container;

// Initialize database connection
$container = Container::getInstance();
$container->get('database');

echo "Running migration: 013_add_user_settings_fields.php\n";

try {
    Capsule::schema()->table('users', function ($table) {
        // Avatar path (nullable)
        if (!Capsule::schema()->hasColumn('users', 'avatar_path')) {
            $table->string('avatar_path', 500)->nullable()->after('email');
            echo "  ✓ Added avatar_path column\n";
        } else {
            echo "  - avatar_path already exists\n";
        }
        
        // Timezone (default UTC)
        if (!Capsule::schema()->hasColumn('users', 'timezone')) {
            $table->string('timezone', 50)->default('UTC')->after('avatar_path');
            echo "  ✓ Added timezone column\n";
        } else {
            echo "  - timezone already exists\n";
        }
        
        // Language (default de)
        if (!Capsule::schema()->hasColumn('users', 'language')) {
            $table->string('language', 10)->default('de')->after('timezone');
            echo "  ✓ Added language column\n";
        } else {
            echo "  - language already exists\n";
        }
    });
    
    echo "✅ Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

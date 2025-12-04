<?php
/**
 * Migration: Add avatar_color to users table
 * 
 * Adds avatar_color field (1-8) to users table for consistent color coding.
 * Existing users get default color based on (id % 8) + 1
 */

use Illuminate\Database\Capsule\Manager as Capsule;

$schema = Capsule::schema();

if (!$schema->hasColumn('users', 'avatar_color')) {
    $schema->table('users', function ($table) {
        $table->tinyInteger('avatar_color')
            ->unsigned()
            ->default(1)
            ->after('avatar_path')
            ->comment('Avatar color (1-8) for consistent UI theming');
    });
    
    echo "✅ Column 'avatar_color' added to 'users' table.\n";
    
    // Initialize existing users with calculated color
    $users = Capsule::table('users')->get();
    foreach ($users as $user) {
        $color = ($user->id % 8) + 1;
        Capsule::table('users')
            ->where('id', $user->id)
            ->update(['avatar_color' => $color]);
    }
    
    echo "✅ Initialized avatar colors for " . count($users) . " existing users.\n";
} else {
    echo "⏭️  Column 'avatar_color' already exists in 'users' table.\n";
}

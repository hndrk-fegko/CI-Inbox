<?php

/**
 * Migration: Fix last_message_at auto-update behavior
 * 
 * Problem: The `last_message_at` column was defined with `ON UPDATE current_timestamp()`,
 * which caused it to be updated on ANY row change (status, assignments, labels, etc.).
 * 
 * Expected: `last_message_at` should only update when:
 * - New email is received
 * - Email is sent (reply/forward)
 * - Thread is created
 * 
 * Solution: Remove `ON UPDATE current_timestamp()` from the column definition.
 * 
 * @since 2025-11-28
 */

use Illuminate\Database\Capsule\Manager as Capsule;

return new class {
    public function up(): void
    {
        // Remove ON UPDATE current_timestamp() from last_message_at
        // Keep the DEFAULT current_timestamp() for new rows
        Capsule::statement("
            ALTER TABLE threads 
            MODIFY COLUMN last_message_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ");
        
        echo "✅ Fixed last_message_at column - removed auto-update behavior\n";
    }

    public function down(): void
    {
        // Restore original behavior (not recommended, but for rollback)
        Capsule::statement("
            ALTER TABLE threads 
            MODIFY COLUMN last_message_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ");
        
        echo "⚠️ Restored last_message_at auto-update behavior (bug restored)\n";
    }
};

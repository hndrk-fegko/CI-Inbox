<?php
/**
 * Migration: Create cron_executions table
 * 
 * Tracks webcron polling service execution history
 */

use Illuminate\Database\Capsule\Manager as Capsule;

$schema = Capsule::schema();

if (!$schema->hasTable('cron_executions')) {
    $schema->create('cron_executions', function ($table) {
        $table->id();
        $table->timestamp('execution_timestamp')->useCurrent();
        $table->integer('accounts_polled')->default(0);
        $table->integer('new_emails_found')->default(0);
        $table->integer('duration_ms')->default(0);
        $table->enum('status', ['success', 'error'])->default('success');
        $table->text('error_message')->nullable();
        
        $table->index('execution_timestamp');
        $table->index('status');
    });
    
    echo "✅ Table 'cron_executions' created successfully.\n";
} else {
    echo "⏭️  Table 'cron_executions' already exists.\n";
}

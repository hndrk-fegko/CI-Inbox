<?php
/**
 * Migration: Add Two-Factor Authentication (2FA/MFA)
 * 
 * Adds TOTP-based 2FA support to users table:
 * - totp_secret: The encrypted TOTP secret key
 * - totp_enabled: Whether 2FA is enabled
 * - totp_verified_at: When 2FA was verified/enabled
 * - backup_codes: JSON array of emergency backup codes
 * - last_2fa_at: Timestamp of last 2FA verification
 * 
 * Also creates onboarding tracking table.
 */

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

// 1. Add 2FA fields to users table
if (!Capsule::schema()->hasColumn('users', 'totp_secret')) {
    Capsule::schema()->table('users', function (Blueprint $table) {
        $table->string('totp_secret', 255)->nullable()->after('password_reset_expires_at');
        $table->boolean('totp_enabled')->default(false)->after('totp_secret');
        $table->timestamp('totp_verified_at')->nullable()->after('totp_enabled');
        $table->text('backup_codes')->nullable()->after('totp_verified_at'); // JSON array
        $table->timestamp('last_2fa_at')->nullable()->after('backup_codes');
    });
    
    echo "✅ Added 2FA columns to users table\n";
}

// 2. Create onboarding_progress table for user tours
if (!Capsule::schema()->hasTable('onboarding_progress')) {
    Capsule::schema()->create('onboarding_progress', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('tour_id', 50); // e.g., 'inbox_tour', 'settings_tour'
        $table->boolean('completed')->default(false);
        $table->integer('current_step')->default(0);
        $table->timestamp('started_at')->nullable();
        $table->timestamp('completed_at')->nullable();
        $table->timestamps();
        
        $table->unique(['user_id', 'tour_id']);
    });
    
    echo "✅ Created onboarding_progress table\n";
}

// 3. Create undo_actions table for undo functionality
if (!Capsule::schema()->hasTable('undo_actions')) {
    Capsule::schema()->create('undo_actions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('action_type', 50); // 'status_change', 'label_add', 'delete', etc.
        $table->string('entity_type', 50); // 'thread', 'email', 'label'
        $table->unsignedBigInteger('entity_id');
        $table->json('previous_state'); // Store previous state for rollback
        $table->json('new_state')->nullable(); // Store new state
        $table->string('undo_token', 64)->unique(); // Token for API undo
        $table->timestamp('expires_at'); // Undo window (e.g., 30 seconds)
        $table->boolean('undone')->default(false);
        $table->timestamps();
        
        $table->index(['user_id', 'expires_at']);
        $table->index('undo_token');
    });
    
    echo "✅ Created undo_actions table\n";
}

// 4. Create keyboard_shortcuts_preferences table
if (!Capsule::schema()->hasTable('user_preferences')) {
    Capsule::schema()->create('user_preferences', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('preference_key', 100);
        $table->text('preference_value');
        $table->timestamps();
        
        $table->unique(['user_id', 'preference_key']);
    });
    
    echo "✅ Created user_preferences table\n";
}

echo "\n✅ Migration 022 completed: 2FA and UX enhancements\n";

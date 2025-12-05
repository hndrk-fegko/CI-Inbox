<?php

/**
 * Migration: Add OAuth and Password Reset fields to users table
 * 
 * Adds support for:
 * - OAuth provider authentication (generic, supports custom providers)
 * - Password reset tokens
 */

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

// Add OAuth and password reset fields to users
if (!Capsule::schema()->hasColumn('users', 'oauth_provider')) {
    Capsule::schema()->table('users', function (Blueprint $table) {
        // OAuth fields
        $table->string('oauth_provider', 50)->nullable()->after('password_hash');
        $table->string('oauth_id', 255)->nullable()->after('oauth_provider');
        $table->text('oauth_token')->nullable()->after('oauth_id');
        $table->text('oauth_refresh_token')->nullable()->after('oauth_token');
        $table->timestamp('oauth_token_expires_at')->nullable()->after('oauth_refresh_token');
        
        // Password reset fields
        $table->string('password_reset_token', 64)->nullable()->after('oauth_token_expires_at');
        $table->timestamp('password_reset_expires_at')->nullable()->after('password_reset_token');
        
        // Index for faster lookups
        $table->index(['oauth_provider', 'oauth_id']);
        $table->index('password_reset_token');
    });
    
    echo "Added OAuth and password reset fields to users table.\n";
} else {
    echo "OAuth fields already exist in users table.\n";
}

// Create oauth_providers table for custom provider configuration
if (!Capsule::schema()->hasTable('oauth_providers')) {
    Capsule::schema()->create('oauth_providers', function (Blueprint $table) {
        $table->id();
        $table->string('name', 50)->unique(); // e.g., 'churchtools', 'google', 'custom'
        $table->string('display_name', 100);
        $table->string('client_id', 255);
        $table->text('client_secret'); // Encrypted
        $table->string('authorize_url', 500);
        $table->string('token_url', 500);
        $table->string('userinfo_url', 500)->nullable();
        $table->string('scopes', 500)->nullable();
        $table->string('icon', 50)->default('key'); // SVG icon name
        $table->string('button_color', 20)->default('#3B82F6'); // Hex color
        $table->boolean('is_active')->default(true);
        $table->integer('sort_order')->default(0);
        $table->timestamps();
    });
    
    echo "Created oauth_providers table.\n";
} else {
    echo "oauth_providers table already exists.\n";
}

// Create setup_status table to track installation
if (!Capsule::schema()->hasTable('setup_status')) {
    Capsule::schema()->create('setup_status', function (Blueprint $table) {
        $table->id();
        $table->string('step', 50);
        $table->boolean('completed')->default(false);
        $table->json('data')->nullable();
        $table->timestamps();
    });
    
    echo "Created setup_status table.\n";
} else {
    echo "setup_status table already exists.\n";
}

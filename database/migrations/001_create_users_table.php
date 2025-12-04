<?php

/**
 * Migration: Create users table
 * 
 * Stores user accounts for the application.
 */

use Illuminate\Database\Capsule\Manager as Capsule;

Capsule::schema()->dropIfExists('users');

Capsule::schema()->create('users', function ($table) {
    $table->id();
    $table->string('email', 255)->unique();
    $table->string('password_hash', 255);
    $table->string('name', 100);
    $table->enum('role', ['admin', 'agent'])->default('agent');
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_login_at')->nullable();
    $table->timestamps(); // created_at, updated_at
    
    $table->index('email');
    $table->index('role');
    $table->index('is_active');
});

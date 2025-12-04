<?php

/**
 * Migration: Create imap_accounts table
 * 
 * Stores IMAP account configurations.
 * Password is encrypted using EncryptionService.
 */

use Illuminate\Database\Capsule\Manager as Capsule;

Capsule::schema()->dropIfExists('imap_accounts');

Capsule::schema()->create('imap_accounts', function ($table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    $table->string('email', 255);
    $table->string('imap_host', 255);
    $table->integer('imap_port')->default(993);
    $table->string('imap_username', 255);
    $table->text('imap_password_encrypted'); // Encrypted with EncryptionService
    $table->enum('imap_encryption', ['ssl', 'tls', 'none'])->default('ssl');
    $table->boolean('is_default')->default(false);
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_sync_at')->nullable();
    $table->timestamps();
    
    $table->index('user_id');
    $table->index('email');
    $table->index('is_active');
});

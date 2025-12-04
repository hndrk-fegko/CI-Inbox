<?php

/**
 * Migration: Add sender fields to threads table
 * 
 * Adds sender_name and sender_email for easier display in thread list
 */

use Illuminate\Database\Capsule\Manager as Capsule;

Capsule::schema()->table('threads', function ($table) {
    $table->string('sender_name', 255)->nullable()->after('subject');
    $table->string('sender_email', 255)->nullable()->after('sender_name');
    
    $table->index('sender_email');
});

echo "✅ Migration 010: Added sender_name and sender_email to threads table\n";

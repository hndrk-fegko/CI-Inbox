<?php

/**
 * Migration: Create thread_assignments table
 * 
 * Pivot table: which threads are assigned to which users.
 */

use Illuminate\Database\Capsule\Manager as Capsule;

Capsule::schema()->dropIfExists('thread_assignments');

Capsule::schema()->create('thread_assignments', function ($table) {
    $table->id();
    $table->foreignId('thread_id')->constrained('threads')->onDelete('cascade');
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
    $table->timestamp('assigned_at')->useCurrent();
    
    $table->unique(['thread_id', 'user_id']); // One assignment per user per thread
    $table->index('thread_id');
    $table->index('user_id');
});

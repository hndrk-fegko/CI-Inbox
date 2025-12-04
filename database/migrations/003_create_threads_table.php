<?php

/**
 * Migration: Create threads table
 * 
 * Stores email conversation threads.
 */

use Illuminate\Database\Capsule\Manager as Capsule;

Capsule::schema()->dropIfExists('threads');

Capsule::schema()->create('threads', function ($table) {
    $table->id();
    $table->string('subject', 500);
    $table->json('participants'); // Array of email addresses
    $table->text('preview')->nullable(); // First 200 chars of latest email
    $table->enum('status', ['open', 'pending', 'closed'])->default('open');
    $table->timestamp('last_message_at');
    $table->integer('message_count')->default(0);
    $table->boolean('has_attachments')->default(false);
    $table->timestamps();
    
    $table->index('status');
    $table->index('last_message_at');
    $table->fullText('subject'); // For search
});

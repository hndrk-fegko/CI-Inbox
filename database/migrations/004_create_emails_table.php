<?php

/**
 * Migration: Create emails table
 * 
 * Stores individual emails within threads.
 */

use Illuminate\Database\Capsule\Manager as Capsule;

Capsule::schema()->dropIfExists('emails');

Capsule::schema()->create('emails', function ($table) {
    $table->id();
    $table->foreignId('thread_id')->constrained('threads')->onDelete('cascade');
    $table->foreignId('imap_account_id')->constrained('imap_accounts')->onDelete('cascade');
    $table->string('message_id', 500)->unique(); // IMAP Message-ID header
    $table->string('in_reply_to', 500)->nullable(); // For threading
    $table->string('from_email', 255);
    $table->string('from_name', 255)->nullable();
    $table->json('to_addresses'); // Array of recipients
    $table->json('cc_addresses')->nullable();
    $table->string('subject', 500);
    $table->text('body_plain')->nullable();
    $table->text('body_html')->nullable();
    $table->boolean('has_attachments')->default(false);
    $table->json('attachment_metadata')->nullable(); // Filenames, sizes, types
    $table->enum('direction', ['incoming', 'outgoing'])->default('incoming');
    $table->boolean('is_read')->default(false);
    $table->timestamp('sent_at');
    $table->timestamps();
    
    $table->index('thread_id');
    $table->index('message_id');
    $table->index('imap_account_id');
    $table->index('from_email');
    $table->index('sent_at');
    $table->fullText(['subject', 'body_plain']); // For search
});

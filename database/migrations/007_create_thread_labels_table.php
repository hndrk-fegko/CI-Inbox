<?php

/**
 * Migration: Create thread_labels table
 * 
 * Pivot table: which labels are applied to which threads.
 */

use Illuminate\Database\Capsule\Manager as Capsule;

Capsule::schema()->dropIfExists('thread_labels');

Capsule::schema()->create('thread_labels', function ($table) {
    $table->id();
    $table->foreignId('thread_id')->constrained('threads')->onDelete('cascade');
    $table->foreignId('label_id')->constrained('labels')->onDelete('cascade');
    $table->timestamp('applied_at')->useCurrent();
    
    $table->unique(['thread_id', 'label_id']); // One label per thread
    $table->index('thread_id');
    $table->index('label_id');
});

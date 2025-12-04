<?php

/**
 * Migration: Create labels table
 * 
 * Stores custom labels/tags for organizing threads.
 */

use Illuminate\Database\Capsule\Manager as Capsule;

Capsule::schema()->dropIfExists('labels');

Capsule::schema()->create('labels', function ($table) {
    $table->id();
    $table->string('name', 100);
    $table->string('color', 7)->default('#3B82F6'); // Hex color code
    $table->integer('display_order')->default(0);
    $table->timestamps();
    
    $table->unique('name');
    $table->index('display_order');
});

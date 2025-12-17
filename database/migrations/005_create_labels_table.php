<?php

/**
 * Migration: Create labels table
 * 
 * Stores custom labels/tags for organizing threads.
 */

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

return new class {
    public function up(): void
    {
        if (Capsule::schema()->hasTable('labels')) {
            return; // table already exists
        }

        Capsule::schema()->create('labels', function ($table) {
            $table->id();
            $table->string('name', 255);
            $table->string('color', 7)->default('#808080'); // HEX color
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false); // Add this column
            $table->timestamps();

            $table->unique('name');
            $table->index('is_system');
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('labels');
    }
};

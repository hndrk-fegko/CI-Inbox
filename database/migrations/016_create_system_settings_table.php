<?php

/**
 * Migration: Create system_settings table
 * 
 * Stores system-wide configuration as key-value pairs.
 * Supports encryption for sensitive values (passwords, tokens).
 */

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

return new class {
    public function up(): void
    {
        $schema = Capsule::schema();

        if ($schema->hasTable('system_settings')) {
            return; // table already exists
        }

        $schema->create('system_settings', function ($table) {
            $table->increments('id');
            $table->string('setting_key', 255);
            $table->text('setting_value')->nullable();
            $table->enum('setting_type', ['string', 'integer', 'boolean', 'json'])->default('string');
            $table->boolean('is_encrypted')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique('setting_key');
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('system_settings');
    }
};

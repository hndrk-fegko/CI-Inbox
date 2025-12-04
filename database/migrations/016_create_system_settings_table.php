<?php

/**
 * Migration: Create system_settings table
 * 
 * Stores system-wide configuration as key-value pairs.
 * Supports encryption for sensitive values (passwords, tokens).
 */

use Illuminate\Database\Capsule\Manager as Capsule;

try {
    echo "Creating system_settings table... ";
    
    Capsule::schema()->create('system_settings', function ($table) {
        $table->increments('id');
        $table->string('setting_key', 255)->unique();
        $table->text('setting_value')->nullable();
        $table->enum('setting_type', ['string', 'integer', 'boolean', 'json'])->default('string');
        $table->boolean('is_encrypted')->default(false);
        $table->text('description')->nullable();
        $table->timestamps();
        
        // Indexes
        $table->index('setting_key');
    });
    
    echo "✅ Created" . PHP_EOL;
    
    // Insert default IMAP/SMTP settings
    echo "Inserting default settings... ";
    
    $defaults = [
        // IMAP Settings
        ['setting_key' => 'imap.host', 'setting_value' => '', 'setting_type' => 'string', 'description' => 'IMAP server hostname'],
        ['setting_key' => 'imap.port', 'setting_value' => '993', 'setting_type' => 'integer', 'description' => 'IMAP server port'],
        ['setting_key' => 'imap.ssl', 'setting_value' => '1', 'setting_type' => 'boolean', 'description' => 'Use SSL/TLS'],
        ['setting_key' => 'imap.username', 'setting_value' => '', 'setting_type' => 'string', 'description' => 'IMAP username'],
        ['setting_key' => 'imap.password', 'setting_value' => '', 'setting_type' => 'string', 'is_encrypted' => true, 'description' => 'IMAP password (encrypted)'],
        ['setting_key' => 'imap.inbox_folder', 'setting_value' => 'INBOX', 'setting_type' => 'string', 'description' => 'Inbox folder name'],
        
        // SMTP Settings
        ['setting_key' => 'smtp.host', 'setting_value' => '', 'setting_type' => 'string', 'description' => 'SMTP server hostname'],
        ['setting_key' => 'smtp.port', 'setting_value' => '587', 'setting_type' => 'integer', 'description' => 'SMTP server port'],
        ['setting_key' => 'smtp.ssl', 'setting_value' => '1', 'setting_type' => 'boolean', 'description' => 'Use SSL/TLS'],
        ['setting_key' => 'smtp.auth', 'setting_value' => '1', 'setting_type' => 'boolean', 'description' => 'Use SMTP authentication'],
        ['setting_key' => 'smtp.username', 'setting_value' => '', 'setting_type' => 'string', 'description' => 'SMTP username'],
        ['setting_key' => 'smtp.password', 'setting_value' => '', 'setting_type' => 'string', 'is_encrypted' => true, 'description' => 'SMTP password (encrypted)'],
        ['setting_key' => 'smtp.from_name', 'setting_value' => 'CI-Inbox', 'setting_type' => 'string', 'description' => 'Default sender name'],
        ['setting_key' => 'smtp.from_email', 'setting_value' => '', 'setting_type' => 'string', 'description' => 'Default sender email'],
    ];
    
    foreach ($defaults as $setting) {
        Capsule::table('system_settings')->insert(array_merge($setting, [
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]));
    }
    
    echo "✅ Inserted default settings" . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . PHP_EOL;
    throw $e;
}

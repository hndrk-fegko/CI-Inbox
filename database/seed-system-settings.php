<?php

/**
 * Insert missing system settings
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CiInbox\Modules\Config\ConfigService;
use Illuminate\Database\Capsule\Manager as Capsule;

// Load config
$config = new ConfigService(__DIR__ . '/../');

// Initialize database
require_once __DIR__ . '/../src/bootstrap/database.php';
initDatabase($config);

echo "=== Adding missing system settings ===" . PHP_EOL . PHP_EOL;

$settings = [
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

$inserted = 0;
$skipped = 0;

foreach ($settings as $setting) {
    // Check if setting already exists
    $exists = Capsule::table('system_settings')
        ->where('setting_key', $setting['setting_key'])
        ->exists();
    
    if (!$exists) {
        Capsule::table('system_settings')->insert(array_merge($setting, [
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]));
        echo "✅ Added: {$setting['setting_key']}" . PHP_EOL;
        $inserted++;
    } else {
        echo "⏭️  Skipped (exists): {$setting['setting_key']}" . PHP_EOL;
        $skipped++;
    }
}

echo PHP_EOL . "✅ Done! Inserted: $inserted, Skipped: $skipped" . PHP_EOL;

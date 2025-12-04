<?php

/**
 * Create test IMAP account for testing
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use CiInbox\Modules\Config\ConfigService;
use CiInbox\App\Models\ImapAccount;

// Initialize
$config = new ConfigService(__DIR__ . '/../../');
require_once __DIR__ . '/../../src/bootstrap/database.php';
initDatabase($config);

echo "=== Creating Test IMAP Account ===" . PHP_EOL;

try {
    // Check if account exists
    $account = ImapAccount::find(1);
    
    if ($account) {
        echo "ℹ️  IMAP Account already exists: ID=1" . PHP_EOL;
    } else {
        // Create test account
        $account = new ImapAccount();
        $account->id = 1;
        $account->user_id = 1; // Assuming user 1 exists
        $account->email = 'test@localhost';
        $account->imap_host = 'localhost';
        $account->imap_port = 143;
        $account->imap_username = 'testuser';
        $account->imap_password_encrypted = 'test'; // Not encrypted for test
        $account->imap_encryption = 'none';
        $account->is_active = true;
        $account->save();
        
        echo "✅ Test IMAP Account created: ID={$account->id}, Email={$account->email}" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

echo "=== Done ===" . PHP_EOL;

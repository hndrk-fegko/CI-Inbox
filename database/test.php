<?php

/**
 * Manual Test: Database & Models
 * 
 * Tests database connection and basic CRUD operations.
 * Run with: C:\xampp\php\php.exe database/test.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CiInbox\Modules\Config\ConfigService;
use CiInbox\App\Models\User;
use CiInbox\App\Models\Label;
use CiInbox\App\Models\Thread;

// Load config and initialize database
$config = new ConfigService(__DIR__ . '/../');
require_once __DIR__ . '/../src/bootstrap/database.php';
$capsule = initDatabase($config);

echo "=== CI-Inbox Database & Models Test ===" . PHP_EOL . PHP_EOL;

try {
    // Cleanup first (in case previous test failed)
    echo "0. Cleaning up old test data..." . PHP_EOL;
    User::where('email', 'test@example.com')->delete();
    Label::whereIn('name', ['Urgent', 'Support', 'Sales'])->delete();
    echo "   ✅ Cleanup done" . PHP_EOL . PHP_EOL;

    // Test 1: Database connection
    echo "1. Testing database connection..." . PHP_EOL;
    $pdo = $capsule->getConnection()->getPdo();
    echo "   Database: " . $config->getString('database.connections.mysql.database') . PHP_EOL;
    echo "   ✅ Connected" . PHP_EOL . PHP_EOL;

    // Test 2: Create a user
    echo "2. Creating test user..." . PHP_EOL;
    $user = User::create([
        'email' => 'test@example.com',
        'password_hash' => password_hash('password123', PASSWORD_BCRYPT),
        'name' => 'Test User',
        'role' => 'agent',
        'is_active' => true,
    ]);
    echo "   User ID: " . $user->id . PHP_EOL;
    echo "   Email: " . $user->email . PHP_EOL;
    echo "   ✅ User created" . PHP_EOL . PHP_EOL;

    // Test 3: Create labels
    echo "3. Creating test labels..." . PHP_EOL;
    $labels = [
        ['name' => 'Urgent', 'color' => '#EF4444', 'display_order' => 1],
        ['name' => 'Support', 'color' => '#3B82F6', 'display_order' => 2],
        ['name' => 'Sales', 'color' => '#10B981', 'display_order' => 3],
    ];
    foreach ($labels as $labelData) {
        $label = Label::create($labelData);
        echo "   - " . $label->name . " (ID: " . $label->id . ")" . PHP_EOL;
    }
    echo "   ✅ Labels created" . PHP_EOL . PHP_EOL;

    // Test 4: Create thread
    echo "4. Creating test thread..." . PHP_EOL;
    $thread = Thread::create([
        'subject' => 'Test Email Thread',
        'participants' => ['customer@example.com', 'test@example.com'],
        'preview' => 'This is a test email message...',
        'status' => 'open',
        'last_message_at' => new \DateTime(),
        'message_count' => 1,
    ]);
    echo "   Thread ID: " . $thread->id . PHP_EOL;
    echo "   Subject: " . $thread->subject . PHP_EOL;
    echo "   ✅ Thread created" . PHP_EOL . PHP_EOL;

    // Test 5: Assign thread to user
    echo "5. Assigning thread to user..." . PHP_EOL;
    $thread->assignedUsers()->attach($user->id, ['assigned_at' => new \DateTime()]);
    echo "   ✅ Assignment created" . PHP_EOL . PHP_EOL;

    // Test 6: Add label to thread
    echo "6. Adding label to thread..." . PHP_EOL;
    $urgentLabel = Label::where('name', 'Urgent')->first();
    $thread->labels()->attach($urgentLabel->id, ['applied_at' => new \DateTime()]);
    echo "   ✅ Label applied" . PHP_EOL . PHP_EOL;

    // Test 7: Query with relationships
    echo "7. Testing relationships..." . PHP_EOL;
    $userWithThreads = User::with('assignedThreads')->find($user->id);
    echo "   User has " . $userWithThreads->assignedThreads->count() . " assigned thread(s)" . PHP_EOL;
    
    $threadWithLabels = Thread::with('labels')->find($thread->id);
    echo "   Thread has " . $threadWithLabels->labels->count() . " label(s)" . PHP_EOL;
    echo "   Label name: " . $threadWithLabels->labels->first()->name . PHP_EOL;
    echo "   ✅ Relationships work" . PHP_EOL . PHP_EOL;

    // Test 8: Count records
    echo "8. Counting records..." . PHP_EOL;
    echo "   Users: " . User::count() . PHP_EOL;
    echo "   Labels: " . Label::count() . PHP_EOL;
    echo "   Threads: " . Thread::count() . PHP_EOL;
    echo "   ✅ Queries work" . PHP_EOL . PHP_EOL;

    // Test 9: Update record
    echo "9. Testing update..." . PHP_EOL;
    $thread->update(['status' => 'closed']);
    $updatedThread = Thread::find($thread->id);
    echo "   Thread status: " . $updatedThread->status . PHP_EOL;
    echo "   ✅ Update works" . PHP_EOL . PHP_EOL;

    // Test 10: Delete records (cleanup)
    echo "10. Cleaning up test data..." . PHP_EOL;
    $thread->delete();
    $user->delete();
    Label::whereIn('name', ['Urgent', 'Support', 'Sales'])->delete();
    echo "    ✅ Cleanup done" . PHP_EOL . PHP_EOL;

    echo "===========================================" . PHP_EOL;
    echo "✅ ALL TESTS PASSED" . PHP_EOL;
    echo "===========================================" . PHP_EOL;

} catch (Exception $e) {
    echo "❌ TEST FAILED: " . $e->getMessage() . PHP_EOL;
    echo "   File: " . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
    echo "   Trace:" . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
    exit(1);
}

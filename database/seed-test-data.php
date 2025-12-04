<?php
/**
 * Seed Test Data for Development
 * 
 * Creates sample threads, emails, and users for testing the inbox UI
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CiInbox\Core\Container;
use CiInbox\App\Models\User;
use CiInbox\App\Models\ImapAccount;
use CiInbox\App\Models\Thread;
use CiInbox\App\Models\Email;
use CiInbox\App\Models\Label;

// Initialize container and database
$container = Container::getInstance();
$container->get('database');

echo "🌱 Seeding test data...\n\n";

// 1. Create test user
echo "👤 Creating test user...\n";
$user = User::firstOrCreate(
    ['email' => 'demo@c-imap.local'],
    [
        'name' => 'Demo User',
        'password_hash' => password_hash('demo123', PASSWORD_BCRYPT),
        'role' => 'admin',
        'is_active' => true
    ]
);
echo "   ✅ User: {$user->email} (ID: {$user->id})\n\n";

// 2. Create IMAP account
echo "📧 Creating IMAP account...\n";
$imapAccount = ImapAccount::firstOrCreate(
    ['user_id' => $user->id, 'email' => 'info@example.com'],
    [
        'imap_host' => 'imap.example.com',
        'imap_port' => 993,
        'imap_encryption' => 'ssl',
        'imap_username' => 'info@example.com',
        'imap_password' => 'encrypted_dummy',
        'display_name' => 'Company Info',
        'is_active' => true
    ]
);
echo "   ✅ IMAP Account: {$imapAccount->email}\n\n";

// 3. Create labels (global, not per account)
echo "🏷️  Creating labels...\n";
$labels = [
    ['name' => 'Wichtig', 'color' => '#dc2626'],
    ['name' => 'Warte auf Antwort', 'color' => '#ea580c'],
    ['name' => 'Erledigt', 'color' => '#16a34a'],
];

foreach ($labels as $labelData) {
    Label::firstOrCreate(
        ['name' => $labelData['name']],
        ['color' => $labelData['color']]
    );
    echo "   ✅ Label: {$labelData['name']}\n";
}
echo "\n";

// 4. Create test threads with emails
echo "📬 Creating test threads...\n";

$now = new DateTime();
$testThreads = [
    [
        'subject' => 'Test Thread 1',
        'sender_name' => 'John Doe',
        'sender_email' => 'john@example.com',
        'preview' => 'Dies ist eine Test-E-Mail für die Inbox-Ansicht. Sie enthält mehrere Zeilen Text um zu zeigen, wie die Preview funktioniert.',
        'is_read' => false,
        'message_count' => 3,
        'last_message_at' => (clone $now)->modify('-3 hours'),
    ],
    [
        'subject' => 'Test Thread 2',
        'sender_name' => 'Jane Smith',
        'sender_email' => 'jane@example.com',
        'preview' => 'Eine weitere Test-Nachricht. Diese E-Mail wurde bereits gelesen.',
        'is_read' => true,
        'message_count' => 1,
        'last_message_at' => (clone $now)->modify('-5 hours'),
    ],
    [
        'subject' => 'Split Thread Test',
        'sender_name' => 'Alice Johnson',
        'sender_email' => 'alice@example.com',
        'preview' => 'Diese Nachricht kann in mehrere Threads aufgeteilt werden.',
        'is_read' => false,
        'message_count' => 2,
        'last_message_at' => (clone $now)->modify('-1 hour'),
    ],
    [
        'subject' => 'Wichtige Anfrage',
        'sender_name' => 'Bob Wilson',
        'sender_email' => 'bob@example.com',
        'preview' => 'Diese Nachricht ist als wichtig markiert und wartet auf eine Antwort.',
        'is_read' => false,
        'message_count' => 1,
        'last_message_at' => (clone $now)->modify('-30 minutes'),
    ],
];

foreach ($testThreads as $index => $threadData) {
    $thread = Thread::create([
        'imap_account_id' => $imapAccount->id,
        'subject' => $threadData['subject'],
        'sender_name' => $threadData['sender_name'],
        'sender_email' => $threadData['sender_email'],
        'participants' => json_encode([$threadData['sender_email'], 'info@example.com']),
        'preview' => $threadData['preview'],
        'is_read' => $threadData['is_read'],
        'message_count' => $threadData['message_count'],
        'last_message_at' => $threadData['last_message_at']->format('Y-m-d H:i:s'),
        'assigned_to' => ($index === 0) ? $user->id : null, // Assign first thread
        'status' => ($index === 1) ? 'closed' : 'open',
    ]);
    
    // Create emails for this thread
    for ($i = 0; $i < $threadData['message_count']; $i++) {
        $receivedAt = clone $threadData['last_message_at'];
        $receivedAt->modify('-' . ($threadData['message_count'] - $i) . ' hours');
        
        Email::create([
            'thread_id' => $thread->id,
            'imap_account_id' => $imapAccount->id,
            'message_id' => 'test-' . uniqid() . '@example.com',
            'subject' => $threadData['subject'],
            'from_email' => $threadData['sender_email'],
            'from_name' => $threadData['sender_name'],
            'to_addresses' => json_encode(['info@example.com']),
            'cc_addresses' => json_encode([]),
            'body_html' => "<p>{$threadData['preview']}</p><p>E-Mail #{$i} im Thread.</p>",
            'body_plain' => $threadData['preview'] . "\n\nE-Mail #$i im Thread.",
            'sent_at' => $receivedAt->format('Y-m-d H:i:s'),
            'is_read' => $threadData['is_read'],
            'uid' => 1000 + ($index * 10) + $i,
        ]);
    }
    
    // Add labels to some threads
    if ($index === 3) {
        $wichtigLabel = Label::where('name', 'Wichtig')->first();
        $warteLabel = Label::where('name', 'Warte auf Antwort')->first();
        if ($wichtigLabel) $thread->labels()->attach($wichtigLabel->id);
        if ($warteLabel) $thread->labels()->attach($warteLabel->id);
    }
    
    echo "   ✅ Thread: {$threadData['subject']} ({$threadData['message_count']} emails)\n";
}

echo "\n✨ Seeding completed!\n\n";
echo "📊 Summary:\n";
echo "   Users: " . User::count() . "\n";
echo "   IMAP Accounts: " . ImapAccount::count() . "\n";
echo "   Threads: " . Thread::count() . "\n";
echo "   Emails: " . Email::count() . "\n";
echo "   Labels: " . Label::count() . "\n";
echo "\n🔑 Login credentials:\n";
echo "   Email: demo@c-imap.local\n";
echo "   Password: demo123\n";

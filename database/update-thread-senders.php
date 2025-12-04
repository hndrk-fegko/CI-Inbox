<?php
/**
 * Update existing threads with sender information from first email
 */

require_once __DIR__ . '/../vendor/autoload.php';

use CiInbox\Core\Container;
use CiInbox\App\Models\Thread;
use CiInbox\App\Models\Email;

// Initialize
$container = Container::getInstance();
$container->get('database');

echo "🔄 Updating threads with sender information...\n\n";

$threads = Thread::all();

foreach ($threads as $thread) {
    // Get first email of thread
    $firstEmail = Email::where('thread_id', $thread->id)
        ->orderBy('sent_at', 'asc')
        ->first();
    
    if ($firstEmail) {
        $thread->sender_name = $firstEmail->from_name;
        $thread->sender_email = $firstEmail->from_email;
        $thread->save();
        
        echo "✅ Thread #{$thread->id}: {$firstEmail->from_name} <{$firstEmail->from_email}>\n";
    } else {
        echo "⚠️  Thread #{$thread->id}: No emails found\n";
    }
}

echo "\n✨ Done! Updated " . $threads->count() . " threads.\n";

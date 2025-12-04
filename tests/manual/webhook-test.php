<?php

/**
 * Webhook System Manual Test
 * 
 * Tests webhook registration, event dispatch, and delivery tracking.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use CiInbox\Modules\Config\ConfigService;
use CiInbox\Modules\Logger\LoggerService;
use CiInbox\App\Services\WebhookService;
use CiInbox\App\Models\Webhook;
use CiInbox\App\Models\Thread;

// Initialize system
$config = new ConfigService(__DIR__ . '/../../');
require_once __DIR__ . '/../../src/bootstrap/database.php';
initDatabase($config);

$logger = new LoggerService(__DIR__ . '/../../logs/');
$webhookService = new WebhookService($logger);

echo "=== Webhook System Test ===" . PHP_EOL . PHP_EOL;

// Test 1: Register webhook
echo "TEST 1: Register webhook" . PHP_EOL;
try {
    $webhook = $webhookService->register([
        'url' => 'https://webhook.site/unique-id-here', // Replace with actual webhook.site URL
        'events' => ['thread.created', 'thread.updated', 'email.sent']
    ]);
    
    echo "✅ Webhook registered successfully" . PHP_EOL;
    echo "   ID: {$webhook->id}" . PHP_EOL;
    echo "   URL: {$webhook->url}" . PHP_EOL;
    echo "   Secret: {$webhook->secret}" . PHP_EOL;
    echo "   Events: " . implode(', ', $webhook->events) . PHP_EOL;
    echo PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Failed: {$e->getMessage()}" . PHP_EOL;
    exit(1);
}

// Test 2: List webhooks
echo "TEST 2: List all webhooks" . PHP_EOL;
try {
    $result = $webhookService->getAllWebhooks();
    echo "✅ Found {$result['total']} webhook(s)" . PHP_EOL;
    
    foreach ($result['data'] as $wh) {
        echo "   - ID {$wh->id}: {$wh->url}" . PHP_EOL;
    }
    echo PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Failed: {$e->getMessage()}" . PHP_EOL;
    exit(1);
}

// Test 3: Dispatch test event
echo "TEST 3: Dispatch test event (thread.created)" . PHP_EOL;
try {
    $webhookService->dispatch('thread.created', [
        'thread_id' => 999,
        'subject' => 'Test Thread for Webhook',
        'status' => 'open',
        'created_at' => date('c')
    ]);
    
    echo "✅ Event dispatched" . PHP_EOL;
    echo "   Check your webhook.site URL for incoming request" . PHP_EOL;
    echo "   Expected headers:" . PHP_EOL;
    echo "   - X-Webhook-Signature: HMAC SHA256 signature" . PHP_EOL;
    echo "   - X-Webhook-Event: thread.created" . PHP_EOL;
    echo PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Failed: {$e->getMessage()}" . PHP_EOL;
    exit(1);
}

// Test 4: Check deliveries
echo "TEST 4: Check delivery history" . PHP_EOL;
try {
    sleep(2); // Wait for async delivery
    
    $deliveries = $webhookService->getDeliveries($webhook->id, 10);
    echo "✅ Found {$deliveries->count()} delivery/deliveries" . PHP_EOL;
    
    foreach ($deliveries as $delivery) {
        $status = $delivery->isSuccessful() ? '✅' : '❌';
        echo "   {$status} Delivery #{$delivery->id}: {$delivery->event_type}" . PHP_EOL;
        echo "      Status: " . ($delivery->response_status ?? 'pending') . PHP_EOL;
        echo "      Attempts: {$delivery->attempts}" . PHP_EOL;
        echo "      Created: {$delivery->created_at}" . PHP_EOL;
    }
    echo PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Failed: {$e->getMessage()}" . PHP_EOL;
    exit(1);
}

// Test 5: Test with real thread (if available)
echo "TEST 5: Test with real thread creation" . PHP_EOL;
try {
    $thread = new Thread();
    $thread->subject = 'Webhook Test Thread';
    $thread->status = 'open';
    $thread->participants = ['test@example.com'];
    $thread->last_message_at = \Carbon\Carbon::now();
    $thread->message_count = 0;
    $thread->has_attachments = false;
    $thread->save();
    
    echo "✅ Thread created: ID {$thread->id}" . PHP_EOL;
    
    // Manually dispatch (since we're not going through ThreadApiService)
    $webhookService->dispatch('thread.created', [
        'thread_id' => $thread->id,
        'subject' => $thread->subject,
        'status' => $thread->status,
        'created_at' => $thread->created_at->toIso8601String()
    ]);
    
    echo "✅ Webhook dispatched for real thread" . PHP_EOL;
    echo "   Check webhook.site again" . PHP_EOL;
    echo PHP_EOL;
    
    // Cleanup
    $thread->delete();
    echo "✅ Test thread cleaned up" . PHP_EOL;
    echo PHP_EOL;
    
} catch (Exception $e) {
    echo "⚠️  Optional test failed: {$e->getMessage()}" . PHP_EOL;
    echo PHP_EOL;
}

// Test 6: Test event filtering
echo "TEST 6: Test event filtering" . PHP_EOL;
try {
    // Dispatch event not subscribed to
    $webhookService->dispatch('note.added', [
        'note_id' => 1,
        'thread_id' => 999,
        'content' => 'Test note'
    ]);
    
    echo "✅ Dispatched unsubscribed event (note.added)" . PHP_EOL;
    echo "   This should NOT trigger webhook (not in subscribed events)" . PHP_EOL;
    echo PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Failed: {$e->getMessage()}" . PHP_EOL;
}

// Test 7: Update webhook
echo "TEST 7: Update webhook" . PHP_EOL;
try {
    $updated = $webhookService->update($webhook->id, [
        'is_active' => false
    ]);
    
    echo "✅ Webhook deactivated" . PHP_EOL;
    echo "   Status: " . ($updated->is_active ? 'active' : 'inactive') . PHP_EOL;
    echo PHP_EOL;
    
    // Test dispatch with inactive webhook
    $webhookService->dispatch('thread.created', [
        'thread_id' => 998,
        'subject' => 'Should not trigger',
        'status' => 'open'
    ]);
    
    echo "✅ Dispatched event to inactive webhook" . PHP_EOL;
    echo "   This should NOT trigger (webhook is inactive)" . PHP_EOL;
    echo PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ Failed: {$e->getMessage()}" . PHP_EOL;
}

// Test 8: Cleanup (optional)
echo "TEST 8: Cleanup" . PHP_EOL;
$cleanup = readline("Delete test webhook? (y/n): ");
if (strtolower(trim($cleanup)) === 'y') {
    try {
        $webhookService->delete($webhook->id);
        echo "✅ Test webhook deleted" . PHP_EOL;
    } catch (Exception $e) {
        echo "❌ Failed: {$e->getMessage()}" . PHP_EOL;
    }
} else {
    echo "⚠️  Test webhook kept (ID: {$webhook->id})" . PHP_EOL;
    echo "   You can delete it manually via API: DELETE /api/webhooks/{$webhook->id}" . PHP_EOL;
}

echo PHP_EOL;
echo "=== All tests completed ===" . PHP_EOL;
echo PHP_EOL;
echo "IMPORTANT NOTES:" . PHP_EOL;
echo "1. Replace webhook.site URL with your actual test URL" . PHP_EOL;
echo "2. Check webhook.site for incoming requests and payloads" . PHP_EOL;
echo "3. Verify HMAC signature using the webhook secret shown above" . PHP_EOL;
echo "4. Test with production SMTP for email.sent events" . PHP_EOL;
echo "5. Integration with ThreadApiService happens automatically in production" . PHP_EOL;

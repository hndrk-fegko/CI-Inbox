<?php
/**
 * Cron Monitor E2E Test
 * 
 * End-to-end test for Cron Monitor feature:
 * 1. Check initial status (should be red)
 * 2. Trigger webhook poll (which logs execution)
 * 3. Check status again (should be yellow/green)
 * 4. View history to confirm execution logged
 * 
 * Usage: php tests/manual/test-cron-monitor-e2e.php
 */

// Configuration
$baseUrl = 'http://ci-inbox.local';
$secretToken = 'your-secret-token-here'; // From .env

function apiRequest(string $method, string $endpoint, array $data = null): array {
    global $baseUrl;
    
    $url = $baseUrl . $endpoint;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

echo "=== Cron Monitor E2E Test ===" . PHP_EOL . PHP_EOL;

// TEST 1: Check initial status
echo "TEST 1: Check initial cron status" . PHP_EOL;
$result = apiRequest('GET', '/api/admin/cron/status');

if ($result['status'] === 200) {
    echo "✅ Status API working" . PHP_EOL;
    echo "   Status: {$result['body']['data']['status']} ({$result['body']['data']['color']})" . PHP_EOL;
    echo "   Executions (1h): {$result['body']['data']['executions_last_hour']}" . PHP_EOL;
    echo "   Total executions: {$result['body']['data']['total_executions']}" . PHP_EOL;
    $initialCount = $result['body']['data']['total_executions'];
} else {
    echo "❌ Status API failed: HTTP {$result['status']}" . PHP_EOL;
    exit(1);
}
echo PHP_EOL;

// TEST 2: Trigger webhook poll
echo "TEST 2: Trigger webhook poll-emails" . PHP_EOL;
$result = apiRequest('POST', "/webhooks/poll-emails?token={$secretToken}");

if ($result['status'] === 200 && $result['body']['success']) {
    echo "✅ Webhook poll successful" . PHP_EOL;
    $accounts = $result['body']['data']['accounts_processed'] ?? 0;
    $emails = $result['body']['data']['emails_fetched'] ?? 0;
    echo "   Accounts polled: {$accounts}" . PHP_EOL;
    echo "   Emails fetched: {$emails}" . PHP_EOL;
} else {
    echo "❌ Webhook poll failed: HTTP {$result['status']}" . PHP_EOL;
    if (isset($result['body']['error'])) {
        echo "   Error: {$result['body']['error']}" . PHP_EOL;
    }
    exit(1);
}
echo PHP_EOL;

// Wait a moment for database write
sleep(1);

// TEST 3: Check status after execution
echo "TEST 3: Check cron status after execution" . PHP_EOL;
$result = apiRequest('GET', '/api/admin/cron/status');

if ($result['status'] === 200) {
    echo "✅ Status updated" . PHP_EOL;
    echo "   Status: {$result['body']['data']['status']} ({$result['body']['data']['color']})" . PHP_EOL;
    echo "   Executions (1h): {$result['body']['data']['executions_last_hour']}" . PHP_EOL;
    echo "   Total executions: {$result['body']['data']['total_executions']}" . PHP_EOL;
    
    $newCount = $result['body']['data']['total_executions'];
    if ($newCount > $initialCount) {
        echo "✅ Execution count increased from {$initialCount} to {$newCount}" . PHP_EOL;
    } else {
        echo "⚠️  Execution count did not increase" . PHP_EOL;
    }
    
    if ($result['body']['data']['last_execution']) {
        $lastExec = $result['body']['data']['last_execution'];
        echo "   Last execution: {$lastExec['relative_time']}" . PHP_EOL;
        echo "   Duration: {$lastExec['duration']}" . PHP_EOL;
        echo "   Status: {$lastExec['status']}" . PHP_EOL;
    }
} else {
    echo "❌ Status API failed: HTTP {$result['status']}" . PHP_EOL;
    exit(1);
}
echo PHP_EOL;

// TEST 4: View execution history
echo "TEST 4: View execution history" . PHP_EOL;
$result = apiRequest('GET', '/api/admin/cron/history?limit=5');

if ($result['status'] === 200) {
    $history = $result['body']['data'];
    echo "✅ History API working" . PHP_EOL;
    echo "   Recent executions: " . count($history) . PHP_EOL;
    
    if (count($history) > 0) {
        echo PHP_EOL . "   Recent executions:" . PHP_EOL;
        foreach (array_slice($history, 0, 3) as $exec) {
            echo "   - {$exec['relative_time']}: {$exec['accounts_polled']} accounts, ";
            echo "{$exec['new_emails_found']} emails, {$exec['duration']}, {$exec['status']}" . PHP_EOL;
        }
    }
} else {
    echo "❌ History API failed: HTTP {$result['status']}" . PHP_EOL;
    exit(1);
}
echo PHP_EOL;

// TEST 5: Check statistics
echo "TEST 5: Check cron statistics" . PHP_EOL;
$result = apiRequest('GET', '/api/admin/cron/statistics');

if ($result['status'] === 200) {
    $stats = $result['body']['data'];
    echo "✅ Statistics API working" . PHP_EOL;
    echo "   Average duration: {$stats['average_duration_ms']}ms" . PHP_EOL;
    echo "   Average accounts: " . round($stats['average_accounts_polled'], 1) . PHP_EOL;
    echo "   Average emails: " . round($stats['average_new_emails'], 1) . PHP_EOL;
    echo "   Success rate: {$stats['success_rate_percentage']}%" . PHP_EOL;
    echo "   Total emails found: {$stats['total_emails_found']}" . PHP_EOL;
} else {
    echo "❌ Statistics API failed: HTTP {$result['status']}" . PHP_EOL;
    exit(1);
}
echo PHP_EOL;

echo "=== All Tests Passed ✅ ===" . PHP_EOL;
echo PHP_EOL;
echo "Next Steps:" . PHP_EOL;
echo "1. Open browser: {$baseUrl}/admin-settings.php" . PHP_EOL;
echo "2. Check Cron Monitor card shows correct status" . PHP_EOL;
echo "3. Click 'View History' to see execution details" . PHP_EOL;
echo "4. Verify status auto-refreshes every 60 seconds" . PHP_EOL;

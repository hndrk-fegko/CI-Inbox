<?php
/**
 * Test SMTP Autodiscover
 */

$ch = curl_init('http://ci-inbox.local/api/admin/settings/smtp/autodiscover');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => 'test@gmail.com']));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP {$httpCode}\n";
echo $response . "\n";

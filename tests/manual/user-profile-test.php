<?php

/**
 * Manual Test: User Profile Settings
 * 
 * Tests user profile operations (get, update, avatar, password)
 */

require_once __DIR__ . '/../../vendor/autoload.php';

// Colors
const GREEN = "\033[32m";
const RED = "\033[31m";
const YELLOW = "\033[33m";
const RESET = "\033[0m";

$baseUrl = 'http://localhost/api/user/profile';
$passed = 0;
$failed = 0;

echo "\n" . YELLOW . "=== User Profile Settings API Tests ===\n" . RESET . "\n";

// Test 1: Get Profile
echo "1. GET /api/user/profile (Get current user profile)\n";
$ch = curl_init($baseUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
if ($httpCode === 200 && $data['success']) {
    echo GREEN . "   ✓ PASS" . RESET . " - Profile retrieved\n";
    echo "   User: {$data['data']['name']} ({$data['data']['email']})\n";
    echo "   Timezone: {$data['data']['timezone']}\n\n";
    $passed++;
} else {
    echo RED . "   ✗ FAIL" . RESET . " - HTTP $httpCode\n";
    echo "   Response: $response\n\n";
    $failed++;
}

// Test 2: Update Profile (Name)
echo "2. PUT /api/user/profile (Update name)\n";
$ch = curl_init($baseUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'name' => 'Updated Test User'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
if ($httpCode === 200 && $data['success'] && $data['data']['name'] === 'Updated Test User') {
    echo GREEN . "   ✓ PASS" . RESET . " - Name updated successfully\n\n";
    $passed++;
} else {
    echo RED . "   ✗ FAIL" . RESET . " - HTTP $httpCode\n";
    echo "   Response: $response\n\n";
    $failed++;
}

// Test 3: Update Timezone
echo "3. PUT /api/user/profile (Update timezone)\n";
$ch = curl_init($baseUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'timezone' => 'Europe/Berlin'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
if ($httpCode === 200 && $data['success'] && $data['data']['timezone'] === 'Europe/Berlin') {
    echo GREEN . "   ✓ PASS" . RESET . " - Timezone updated successfully\n\n";
    $passed++;
} else {
    echo RED . "   ✗ FAIL" . RESET . " - HTTP $httpCode\n";
    echo "   Response: $response\n\n";
    $failed++;
}

// Test 4: Invalid Email Format
echo "4. PUT /api/user/profile (Invalid email - should fail)\n";
$ch = curl_init($baseUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'email' => 'invalid-email'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
if ($httpCode === 400 && !$data['success']) {
    echo GREEN . "   ✓ PASS" . RESET . " - Validation works correctly\n";
    echo "   Error: {$data['error']}\n\n";
    $passed++;
} else {
    echo RED . "   ✗ FAIL" . RESET . " - Validation failed (HTTP $httpCode)\n";
    echo "   Response: $response\n\n";
    $failed++;
}

// Test 5: Change Password (without current - should fail)
echo "5. POST /api/user/profile/change-password (Missing current password)\n";
$ch = curl_init($baseUrl . '/change-password');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'new_password' => 'newpassword123'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
if ($httpCode === 400 && !$data['success']) {
    echo GREEN . "   ✓ PASS" . RESET . " - Validation works correctly\n";
    echo "   Error: {$data['error']}\n\n";
    $passed++;
} else {
    echo RED . "   ✗ FAIL" . RESET . " - Validation failed (HTTP $httpCode)\n";
    echo "   Response: $response\n\n";
    $failed++;
}

// Test 6: Change Password (short password - should fail)
echo "6. POST /api/user/profile/change-password (Password too short)\n";
$ch = curl_init($baseUrl . '/change-password');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'current_password' => 'test123',
    'new_password' => 'short'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
if ($httpCode === 400 && !$data['success']) {
    echo GREEN . "   ✓ PASS" . RESET . " - Validation works correctly\n";
    echo "   Error: {$data['error']}\n\n";
    $passed++;
} else {
    echo RED . "   ✗ FAIL" . RESET . " - Validation failed (HTTP $httpCode)\n";
    echo "   Response: $response\n\n";
    $failed++;
}

// Test 7: Delete Avatar (when no avatar exists)
echo "7. DELETE /api/user/profile/avatar (No avatar to delete)\n";
$ch = curl_init($baseUrl . '/avatar');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
if ($httpCode === 200 && $data['success']) {
    echo GREEN . "   ✓ PASS" . RESET . " - Avatar deletion handled gracefully\n\n";
    $passed++;
} else {
    echo RED . "   ✗ FAIL" . RESET . " - HTTP $httpCode\n";
    echo "   Response: $response\n\n";
    $failed++;
}

// Summary
echo "\n" . YELLOW . "=== Test Summary ===\n" . RESET;
echo GREEN . "Passed: $passed\n" . RESET;
echo ($failed > 0 ? RED : GREEN) . "Failed: $failed\n" . RESET;

if ($failed === 0) {
    echo "\n" . GREEN . "✓ All tests passed!\n" . RESET;
    exit(0);
} else {
    echo "\n" . RED . "✗ Some tests failed!\n" . RESET;
    exit(1);
}

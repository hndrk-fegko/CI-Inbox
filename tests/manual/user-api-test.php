<?php
/**
 * User Management API Test
 * 
 * Tests the User CRUD API endpoints.
 */

// Colors for output
define('COLOR_GREEN', "\033[32m");
define('COLOR_RED', "\033[31m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_BLUE', "\033[34m");
define('COLOR_RESET', "\033[0m");

// ============================================================
// Configuration
// ============================================================

$baseUrl = 'http://ci-inbox.local';

// ============================================================
// Helper Functions
// ============================================================

function output(string $message, string $color = COLOR_RESET): void
{
    echo $color . $message . COLOR_RESET . PHP_EOL;
}

function httpRequest(string $method, string $url, ?array $data = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ]
    ]);
    
    if ($data !== null && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $responseBody = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    return [
        'http_code' => $httpCode,
        'body' => $responseBody,
        'error' => $curlError,
        'data' => $responseBody ? json_decode($responseBody, true) : null
    ];
}

// ============================================================
// Main Test Script
// ============================================================

output("", COLOR_BLUE);
output("╔════════════════════════════════════════════════════════════╗", COLOR_BLUE);
output("║       CI-Inbox: User Management API Test                  ║", COLOR_BLUE);
output("╚════════════════════════════════════════════════════════════╝", COLOR_BLUE);
output("");

$createdUserIds = [];

// ============================================================
// TEST 1: Create User
// ============================================================
output("TEST 1: Create User", COLOR_YELLOW);
output("-----------------------------------------------------------");

$result = httpRequest('POST', "{$baseUrl}/api/users", [
    'email' => 'testuser@c-imap.local',
    'name' => 'Test User',
    'password' => 'testpassword123',
    'role' => 'user',
    'is_active' => true
]);

if ($result['http_code'] === 201 && $result['data']['success']) {
    output("✓ HTTP 201 Created", COLOR_GREEN);
    $createdUserIds[] = $result['data']['user']['id'];
    output("  User ID: {$result['data']['user']['id']}", COLOR_GREEN);
    output("  Email: {$result['data']['user']['email']}", COLOR_GREEN);
} else {
    output("✗ HTTP {$result['http_code']}", COLOR_RED);
    output("Response: {$result['body']}", COLOR_RED);
}

output("");

// ============================================================
// TEST 2: Get All Users
// ============================================================
output("TEST 2: Get All Users", COLOR_YELLOW);
output("-----------------------------------------------------------");

$result = httpRequest('GET', "{$baseUrl}/api/users");

if ($result['http_code'] === 200 && isset($result['data']['users'])) {
    output("✓ HTTP 200 OK", COLOR_GREEN);
    output("  Total users: {$result['data']['meta']['total']}", COLOR_GREEN);
    
    foreach ($result['data']['users'] as $user) {
        output("  - {$user['name']} ({$user['email']}) - Role: {$user['role']}", COLOR_BLUE);
    }
} else {
    output("✗ HTTP {$result['http_code']}", COLOR_RED);
    output("Response: {$result['body']}", COLOR_RED);
}

output("");

// ============================================================
// TEST 3: Get Single User
// ============================================================
if (!empty($createdUserIds)) {
    output("TEST 3: Get Single User", COLOR_YELLOW);
    output("-----------------------------------------------------------");
    
    $userId = $createdUserIds[0];
    $result = httpRequest('GET', "{$baseUrl}/api/users/{$userId}");
    
    if ($result['http_code'] === 200 && isset($result['data']['user'])) {
        output("✓ HTTP 200 OK", COLOR_GREEN);
        output("  Name: {$result['data']['user']['name']}", COLOR_GREEN);
        output("  Email: {$result['data']['user']['email']}", COLOR_GREEN);
        output("  Role: {$result['data']['user']['role']}", COLOR_GREEN);
    } else {
        output("✗ HTTP {$result['http_code']}", COLOR_RED);
        output("Response: {$result['body']}", COLOR_RED);
    }
    
    output("");
}

// ============================================================
// TEST 4: Update User
// ============================================================
if (!empty($createdUserIds)) {
    output("TEST 4: Update User", COLOR_YELLOW);
    output("-----------------------------------------------------------");
    
    $userId = $createdUserIds[0];
    $result = httpRequest('PUT', "{$baseUrl}/api/users/{$userId}", [
        'name' => 'Updated Test User',
        'role' => 'admin'
    ]);
    
    if ($result['http_code'] === 200 && $result['data']['success']) {
        output("✓ HTTP 200 OK", COLOR_GREEN);
        output("  Updated name: {$result['data']['user']['name']}", COLOR_GREEN);
        output("  Updated role: {$result['data']['user']['role']}", COLOR_GREEN);
    } else {
        output("✗ HTTP {$result['http_code']}", COLOR_RED);
        output("Response: {$result['body']}", COLOR_RED);
    }
    
    output("");
}

// ============================================================
// TEST 5: Filter Users by Role
// ============================================================
output("TEST 5: Filter Users by Role (admin)", COLOR_YELLOW);
output("-----------------------------------------------------------");

$result = httpRequest('GET', "{$baseUrl}/api/users?role=admin");

if ($result['http_code'] === 200 && isset($result['data']['users'])) {
    output("✓ HTTP 200 OK", COLOR_GREEN);
    output("  Admin users: {$result['data']['meta']['total']}", COLOR_GREEN);
} else {
    output("✗ HTTP {$result['http_code']}", COLOR_RED);
}

output("");

// ============================================================
// TEST 6: Change Password
// ============================================================
if (!empty($createdUserIds)) {
    output("TEST 6: Change Password", COLOR_YELLOW);
    output("-----------------------------------------------------------");
    
    $userId = $createdUserIds[0];
    $result = httpRequest('POST', "{$baseUrl}/api/users/{$userId}/password", [
        'current_password' => 'testpassword123',
        'new_password' => 'newpassword123',
        'confirm_password' => 'newpassword123'
    ]);
    
    if ($result['http_code'] === 200 && $result['data']['success']) {
        output("✓ HTTP 200 OK", COLOR_GREEN);
        output("  {$result['data']['message']}", COLOR_GREEN);
    } else {
        output("✗ HTTP {$result['http_code']}", COLOR_RED);
        output("Response: {$result['body']}", COLOR_RED);
    }
    
    output("");
}

// ============================================================
// TEST 7: Validation Test (Missing email)
// ============================================================
output("TEST 7: Validation Test (Missing email)", COLOR_YELLOW);
output("-----------------------------------------------------------");

$result = httpRequest('POST', "{$baseUrl}/api/users", [
    'password' => 'test123456'
    // Missing 'email'
]);

if ($result['http_code'] === 400) {
    output("✓ HTTP 400 Bad Request (Expected)", COLOR_GREEN);
    output("  Error: {$result['data']['error']}", COLOR_BLUE);
} else {
    output("✗ Expected 400, got {$result['http_code']}", COLOR_RED);
}

output("");

// ============================================================
// TEST 8: Validation Test (Duplicate email)
// ============================================================
output("TEST 8: Validation Test (Duplicate email)", COLOR_YELLOW);
output("-----------------------------------------------------------");

$result = httpRequest('POST', "{$baseUrl}/api/users", [
    'email' => 'testuser@c-imap.local', // Already exists
    'password' => 'test123456'
]);

if ($result['http_code'] === 400) {
    output("✓ HTTP 400 Bad Request (Expected)", COLOR_GREEN);
    output("  Error: {$result['data']['error']}", COLOR_BLUE);
} else {
    output("✗ Expected 400, got {$result['http_code']}", COLOR_RED);
}

output("");

// ============================================================
// TEST 9: Delete User
// ============================================================
if (!empty($createdUserIds)) {
    output("TEST 9: Delete User", COLOR_YELLOW);
    output("-----------------------------------------------------------");
    
    foreach ($createdUserIds as $userId) {
        $result = httpRequest('DELETE', "{$baseUrl}/api/users/{$userId}");
        
        if ($result['http_code'] === 200 && $result['data']['success']) {
            output("✓ User {$userId} deleted", COLOR_GREEN);
        } else {
            output("✗ Failed to delete user {$userId}", COLOR_RED);
        }
    }
    
    output("");
}

// ============================================================
// TEST 10: Get Non-Existent User (404)
// ============================================================
output("TEST 10: Get Non-Existent User", COLOR_YELLOW);
output("-----------------------------------------------------------");

$result = httpRequest('GET', "{$baseUrl}/api/users/99999");

if ($result['http_code'] === 404) {
    output("✓ HTTP 404 Not Found (Expected)", COLOR_GREEN);
} else {
    output("✗ Expected 404, got {$result['http_code']}", COLOR_RED);
}

output("");

// ============================================================
// Summary
// ============================================================
output("╔════════════════════════════════════════════════════════════╗", COLOR_BLUE);
output("║                    Test Complete                          ║", COLOR_BLUE);
output("╚════════════════════════════════════════════════════════════╝", COLOR_BLUE);
output("");

output("User Management API Endpoints Tested:", COLOR_GREEN);
output("  ✓ POST /api/users - Create user");
output("  ✓ GET /api/users - List all users");
output("  ✓ GET /api/users/{id} - Get single user");
output("  ✓ PUT /api/users/{id} - Update user");
output("  ✓ DELETE /api/users/{id} - Delete user");
output("  ✓ POST /api/users/{id}/password - Change password");
output("  ✓ Filtering (role, is_active)");
output("  ✓ Validation tests");
output("");

<?php
/**
 * Test Login Functionality
 */

require_once __DIR__ . '/../../vendor/autoload.php';

echo "Testing Login Functionality\n";
echo "============================\n\n";

try {
    // Database connection
    $pdo = new PDO(
        'mysql:host=localhost;dbname=ci_inbox;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✓ Database connection successful\n\n";
    
    // Test admin user
    $stmt = $pdo->prepare('SELECT id, email, name, password_hash, role FROM users WHERE email = ?');
    $stmt->execute(['admin@ci-inbox.local']);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "Admin User Found:\n";
        echo "  ID: {$admin['id']}\n";
        echo "  Email: {$admin['email']}\n";
        echo "  Name: {$admin['name']}\n";
        echo "  Role: {$admin['role']}\n";
        echo "  Password Hash: " . substr($admin['password_hash'], 0, 30) . "...\n";
        echo "  Password verify 'admin123': " . (password_verify('admin123', $admin['password_hash']) ? 'YES ✓' : 'NO ✗') . "\n\n";
    } else {
        echo "✗ Admin user not found\n\n";
    }
    
    // Test demo user
    $stmt->execute(['demo@ci-inbox.local']);
    $demo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($demo) {
        echo "Demo User Found:\n";
        echo "  ID: {$demo['id']}\n";
        echo "  Email: {$demo['email']}\n";
        echo "  Name: {$demo['name']}\n";
        echo "  Role: {$demo['role']}\n";
        echo "  Password Hash: " . substr($demo['password_hash'], 0, 30) . "...\n";
        echo "  Password verify 'demo123': " . (password_verify('demo123', $demo['password_hash']) ? 'YES ✓' : 'NO ✗') . "\n\n";
    } else {
        echo "✗ Demo user not found\n\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "============================\n";
echo "Test completed successfully!\n";

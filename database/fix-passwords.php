<?php
/**
 * Fix User Passwords
 */

require_once __DIR__ . '/../vendor/autoload.php';

echo "Fixing User Passwords\n";
echo "=====================\n\n";

try {
    // Database connection
    $pdo = new PDO(
        'mysql:host=localhost;dbname=ci_inbox;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Generate proper password hashes
    $demoHash = password_hash('demo123', PASSWORD_BCRYPT);
    $adminHash = password_hash('admin123', PASSWORD_BCRYPT);
    
    echo "Generated password hashes:\n";
    echo "Demo Hash: $demoHash\n";
    echo "Admin Hash: $adminHash\n\n";
    
    // Update demo user
    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
    $stmt->execute([$demoHash, 'demo@c-imap.local']);
    echo "✓ Updated demo@c-imap.local\n";
    
    // Update admin user
    $stmt->execute([$adminHash, 'admin@c-imap.local']);
    echo "✓ Updated admin@c-imap.local\n\n";
    
    // Verify
    echo "Verification:\n";
    $stmt = $pdo->prepare('SELECT email, password_hash FROM users WHERE email IN (?, ?)');
    $stmt->execute(['demo@c-imap.local', 'admin@c-imap.local']);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        $password = ($user['email'] === 'demo@c-imap.local') ? 'demo123' : 'admin123';
        $verifies = password_verify($password, $user['password_hash']);
        echo "{$user['email']}: " . ($verifies ? '✓ VALID' : '✗ INVALID') . "\n";
    }
    
    echo "\n=====================\n";
    echo "Passwords fixed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

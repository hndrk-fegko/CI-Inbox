<?php
/**
 * Create User with ID 1 for Testing
 */

require __DIR__ . '/../../vendor/autoload.php';

use CiInbox\Core\Container;
use CiInbox\App\Models\User;

// Initialize container and database
$container = Container::getInstance();
$container->get('database');

// Check if user with ID 1 exists
$existingUser = User::find(1);

if ($existingUser) {
    echo "✅ User with ID 1 already exists:\n";
    echo "   Name: {$existingUser->name}\n";
    echo "   Email: {$existingUser->email}\n";
} else {
    // Create user with ID 1
    $user = new User();
    $user->id = 1;
    $user->name = 'Test User';
    $user->email = 'test@c-imap.local';
    $user->password = password_hash('test1234', PASSWORD_BCRYPT);
    $user->timezone = 'UTC';
    $user->language = 'de';
    $user->save();
    
    echo "✅ User with ID 1 created:\n";
    echo "   Name: {$user->name}\n";
    echo "   Email: {$user->email}\n";
    echo "   Password: test1234\n";
}

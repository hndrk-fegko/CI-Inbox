<?php

// Simple debug script to test UserProfileController loading

require_once __DIR__ . '/../../vendor/autoload.php';

use CiInbox\Core\Container;
use CiInbox\App\Controllers\UserProfileController;

echo "Testing UserProfileController loading...\n\n";

try {
    $container = Container::getInstance();
    echo "✓ Container loaded\n";
    
    $ctrl = $container->get(UserProfileController::class);
    echo "✓ UserProfileController loaded\n";
    echo "✓ Class: " . get_class($ctrl) . "\n\n";
    
    echo "SUCCESS: All components loaded correctly!\n";
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

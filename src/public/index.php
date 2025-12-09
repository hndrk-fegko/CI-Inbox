<?php
declare(strict_types=1);

/**
 * CI-Inbox Entry Point
 * 
 * Bootstraps and runs the application using Application class.
 * 
 * Bug Fix: [HIGH] - Favicon 500 Error Prevention
 * Redirects to setup if vendor/ is missing instead of throwing fatal error.
 */

use CiInbox\Core\Application;

// Check if vendor exists BEFORE trying to autoload
$vendorAutoload = __DIR__ . '/../../vendor/autoload.php';
if (!file_exists($vendorAutoload)) {
    // Redirect to setup wizard
    $setupPath = '/setup/';
    
    // Handle subdirectory installations (e.g., IONOS)
    $scriptName = $_SERVER['SCRIPT_NAME']; // e.g., "/src/public/index.php"
    if (preg_match('#^(.*?)/public/#', $scriptName, $matches)) {
        $setupPath = $matches[1] . '/public/setup/';
    }
    
    header("Location: {$setupPath}");
    exit;
}

// Autoloader
require_once $vendorAutoload;

// Create and bootstrap application
$app = new Application();
$app->bootstrap();

// Run application
$app->run();

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
    // No-cache for redirect responses
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Redirect to setup/index.php (no wizard.php)
    // Compute base path from current script location
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    $setupPath = ($base === '' || $base === '/') ? '/setup/' : $base . '/setup/';

    header('Location: ' . $setupPath, true, 302);
    exit;
}

// Optional: small debug to confirm live file if needed
if (isset($_GET['__debug'])) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "ROOT PUBLIC INDEX\n";
    echo "FILE: " . __FILE__ . "\n";
    echo "MTIME: " . @date('c', @filemtime(__FILE__)) . "\n";
    echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? '') . "\n";
    exit;
}

// Autoloader
require_once $vendorAutoload;

// Create and bootstrap application
$app = new Application();
$app->bootstrap();

// Run application
$app->run();

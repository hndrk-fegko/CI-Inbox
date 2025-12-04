<?php
declare(strict_types=1);

/**
 * CI-Inbox Entry Point (with Debug)
 */

use CiInbox\Core\Application;

// Autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Debug: Log incoming request
error_log("======== INCOMING REQUEST ========");
error_log("REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A'));
error_log("REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A'));
error_log("SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A'));
error_log("PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'N/A'));
error_log("REDIRECT_URL: " . ($_SERVER['REDIRECT_URL'] ?? 'N/A'));
error_log("==================================");

// Create and bootstrap application
$app = new Application();
$app->bootstrap();

// Run application
$app->run();

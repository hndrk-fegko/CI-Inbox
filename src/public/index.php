<?php
declare(strict_types=1);

/**
 * CI-Inbox Entry Point
 * 
 * Bootstraps and runs the application using Application class.
 */

use CiInbox\Core\Application;

// Autoloader
require_once __DIR__ . '/../../vendor/autoload.php';

// Debug: Log what Slim will receive
error_log("=== INDEX.PHP CALLED ===");
error_log("REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A'));
error_log("REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A'));
error_log("SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A'));
error_log("SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'N/A'));
error_log("========================");

// Create and bootstrap application
$app = new Application();
$app->bootstrap();

// Run application
$app->run();

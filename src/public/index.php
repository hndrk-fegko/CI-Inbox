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

// Create and bootstrap application
$app = new Application();
$app->bootstrap();

// Run application
$app->run();

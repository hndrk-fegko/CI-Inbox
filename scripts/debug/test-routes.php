<?php
/**
 * Quick route test
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Slim\Factory\AppFactory;
use CiInbox\Core\Container;

// Get container
$container = Container::getInstance();

// Create Slim app
AppFactory::setContainer($container);
$app = AppFactory::create();

// Set base path for URL routing
$app->setBasePath('/test-routes.php');

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Simple test route
$app->get('/test', function ($request, $response) {
    $response->getBody()->write('TEST WORKS!');
    return $response;
});

// Root test
$app->get('/', function ($request, $response) {
    $response->getBody()->write('ROOT WORKS!');
    return $response;
});

// Load web routes
$webRoutes = __DIR__ . '/../routes/web.php';
if (file_exists($webRoutes)) {
    (require $webRoutes)($app);
    echo "Web routes loaded\n";
} else {
    echo "Web routes file not found!\n";
}

// Run app
$app->run();

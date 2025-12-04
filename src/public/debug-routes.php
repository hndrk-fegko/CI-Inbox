<?php
/**
 * Debug Routes - Show all registered Slim routes
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use CiInbox\Core\Application;

// Create application
$app = new Application();
$app->bootstrap();

// Get Slim app
$slimApp = $app->getSlimApp();

// Get route collector
$routeCollector = $slimApp->getRouteCollector();
$routes = $routeCollector->getRoutes();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Registered Routes - C-IMAP</title>
    <style>
        body { font-family: monospace; padding: 20px; }
        h1 { color: #3b82f6; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #3b82f6; color: white; }
        .method { font-weight: bold; padding: 4px 8px; border-radius: 4px; display: inline-block; }
        .get { background-color: #10b981; color: white; }
        .post { background-color: #3b82f6; color: white; }
        .put { background-color: #f59e0b; color: white; }
        .delete { background-color: #ef4444; color: white; }
    </style>
</head>
<body>
    <h1>🔍 Registered Slim Routes</h1>
    <p><strong>Total Routes:</strong> <?= count($routes) ?></p>
    
    <table>
        <thead>
            <tr>
                <th>Methods</th>
                <th>Pattern</th>
                <th>Name</th>
                <th>Identifier</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($routes as $route): ?>
                <tr>
                    <td>
                        <?php foreach ($route->getMethods() as $method): ?>
                            <span class="method <?= strtolower($method) ?>"><?= $method ?></span>
                        <?php endforeach; ?>
                    </td>
                    <td><code><?= htmlspecialchars($route->getPattern()) ?></code></td>
                    <td><?= htmlspecialchars($route->getName() ?? '-') ?></td>
                    <td><code><?= htmlspecialchars($route->getIdentifier()) ?></code></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <h2>🧪 Test Routes:</h2>
    <ul>
        <li><a href="/">Root /</a></li>
        <li><a href="/auth/login">GET /auth/login</a></li>
        <li><a href="/api">GET /api</a></li>
        <li><a href="/api/system/health">GET /api/system/health</a></li>
    </ul>
    
    <h2>📋 Server Info:</h2>
    <pre>
REQUEST_URI: <?= $_SERVER['REQUEST_URI'] ?? 'N/A' ?>

SCRIPT_NAME: <?= $_SERVER['SCRIPT_NAME'] ?? 'N/A' ?>

REDIRECT_URL: <?= $_SERVER['REDIRECT_URL'] ?? 'N/A' ?>
    </pre>
</body>
</html>

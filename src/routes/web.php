<?php

/**
 * Web Routes
 * 
 * Frontend web pages.
 */

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use CiInbox\App\Controllers\AuthController;
use CiInbox\Core\Container;

return function (App $app) {
    
    // Get container for dependency injection
    $container = Container::getInstance();
    
    // ============================================
    // Authentication Routes
    // ============================================
    
    // Redirect root to login or inbox
    $app->get('/', function (Request $request, Response $response) use ($container) {
        $authController = $container->get(AuthController::class);
        if ($authController->isAuthenticated()) {
            return $response
                ->withHeader('Location', '/inbox')
                ->withStatus(302);
        } else {
            return $response
                ->withHeader('Location', '/auth/login')
                ->withStatus(302);
        }
    });
    
    // GET /auth/login - Show login form
    $app->get('/auth/login', function (Request $request, Response $response) use ($container) {
        $authController = $container->get(AuthController::class);
        
        // Redirect if already logged in
        if ($authController->isAuthenticated()) {
            return $response
                ->withHeader('Location', '/inbox')
                ->withStatus(302);
        }
        
        // Render login view
        ob_start();
        $authController->showLogin();
        $html = ob_get_clean();
        
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    });
    
    // POST /auth/login - Process login
    $app->post('/auth/login', function (Request $request, Response $response) use ($container) {
        $authController = $container->get(AuthController::class);
        $authController->login();
        
        // Login method handles redirect
        return $response;
    });
    
    // POST /auth/logout - Logout
    $app->post('/auth/logout', function (Request $request, Response $response) use ($container) {
        $authController = $container->get(AuthController::class);
        $authController->logout();
        
        // Logout method handles redirect
        return $response;
    });
    
    // ============================================
    // Main Application Routes
    // ============================================
    
    // GET /inbox - Main inbox view
    $app->get('/inbox', function (Request $request, Response $response) use ($container) {
        $authController = $container->get(AuthController::class);
        
        // Check authentication
        if (!$authController->isAuthenticated()) {
            return $response
                ->withStatus(302)
                ->withHeader('Location', '/auth/login');
        }
        
        // Include inbox.php directly
        ob_start();
        include __DIR__ . '/../public/inbox.php';
        $html = ob_get_clean();
        
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    });
    
    // Legacy homepage (for reference)
    $app->get('/status', function (Request $request, Response $response) {
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>CI-Inbox</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        h1 { color: #3B82F6; }
        .status { color: #10B981; }
        a { color: #3B82F6; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>CI-Inbox</h1>
    <p class="status">✅ Application is running</p>
    <p>Collaborative IMAP Inbox - Foundation (M0) Complete</p>
    <h2>API Endpoints:</h2>
    <ul>
        <li><a href="/api">/api</a> - API Information</li>
        <li><a href="/api/system/health">/api/system/health</a> - Health Check</li>
    </ul>
    <h2>Status:</h2>
    <ul>
        <li>✅ Logger Module</li>
        <li>✅ Config Module</li>
        <li>✅ Encryption Module</li>
        <li>✅ Database Setup</li>
        <li>✅ Core Infrastructure</li>
    </ul>
    <p><em>M0 Foundation Milestone Complete!</em></p>
</body>
</html>
HTML;
        
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    });
};

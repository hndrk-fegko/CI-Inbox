<?php

namespace CiInbox\Core;

use Slim\Factory\AppFactory;
use Slim\App as SlimApp;
use CiInbox\Modules\Logger\LoggerInterface;
use CiInbox\Modules\Config\ConfigInterface;

/**
 * Application
 * 
 * Main application class that bootstraps and runs the app.
 */
class Application
{
    private SlimApp $app;
    private HookManager $hookManager;
    private ModuleLoader $moduleLoader;
    private LoggerInterface $logger;
    private ConfigInterface $config;

    public function __construct()
    {
        // Initialize container
        $container = Container::getInstance();
        
        // Get services from container
        $this->logger = $container->get(LoggerInterface::class);
        $this->config = $container->get(ConfigInterface::class);
        
        // Initialize database
        $container->get('database');
        
        // Create Slim app with ServerRequest from globals
        AppFactory::setContainer($container);
        
        // Create ServerRequestCreator to properly handle basePath
        $serverRequestCreator = \Slim\Factory\ServerRequestCreatorFactory::create();
        $request = $serverRequestCreator->createServerRequestFromGlobals();
        
        $this->app = AppFactory::create();
        
        // Detect and set basePath automatically
        // When using .htaccess rewriting, SCRIPT_NAME should be /index.php
        // When called directly as /index.php/route, we need to strip /index.php
        $scriptName = $request->getServerParams()['SCRIPT_NAME'] ?? '';
        $requestUri = $request->getServerParams()['REQUEST_URI'] ?? '';
        
        // If SCRIPT_NAME contains index.php but REQUEST_URI doesn't start with it,
        // then .htaccess rewriting is working and we don't need basePath
        // Otherwise, set basePath to the directory containing index.php
        if (str_contains($scriptName, 'index.php') && !str_starts_with($requestUri, '/index.php')) {
            // .htaccess rewriting is working, no basePath needed
            $this->logger->debug('Using .htaccess rewriting, no basePath needed');
        } elseif (str_contains($scriptName, 'index.php')) {
            // Direct access via index.php, set basePath
            $basePath = dirname($scriptName);
            if ($basePath === '/' || $basePath === '.') {
                $basePath = '/index.php';
            } else {
                $basePath .= '/index.php';
            }
            $this->app->setBasePath($basePath);
            $this->logger->debug('Set basePath', ['basePath' => $basePath]);
        }
        
        // Initialize hook system
        $this->hookManager = new HookManager();
        
        // Load modules
        $this->moduleLoader = new ModuleLoader(
            __DIR__ . '/../modules',
            $this->hookManager,
            $this->logger
        );
        
        $this->logger->info('Application initializing');
    }

    /**
     * Bootstrap the application
     */
    public function bootstrap(): void
    {
        // Load modules
        $this->moduleLoader->loadAll();
        
        // Execute onAppInit hooks
        $this->hookManager->execute('onAppInit', $this);
        
        // Add body parsing middleware (required for JSON requests)
        $this->app->addBodyParsingMiddleware();
        
        // Add error middleware
        $this->app->addErrorMiddleware(
            $this->config->getBool('app.debug', false),
            true,
            true
        );
        
        // Load routes
        $this->loadRoutes();
        
        $this->logger->info('Application bootstrapped');
    }

    /**
     * Load route files
     */
    private function loadRoutes(): void
    {
        // API routes
        $apiRoutes = __DIR__ . '/../routes/api.php';
        if (file_exists($apiRoutes)) {
            (require $apiRoutes)($this->app);
        }
        
        // Web routes
        $webRoutes = __DIR__ . '/../routes/web.php';
        if (file_exists($webRoutes)) {
            (require $webRoutes)($this->app);
        }
        
        // Webcron routes
        $webcronRoutes = __DIR__ . '/../routes/webcron.php';
        if (file_exists($webcronRoutes)) {
            $this->app->group('/webcron', require $webcronRoutes);
        }
        
        // Public webhook endpoint (for external cron services)
        $this->registerPublicWebhook();
    }
    
    /**
     * Register public webhook endpoint
     * 
     * This endpoint is separate from /api/webhooks (webhook management)
     * and /webcron/poll (internal polling service)
     */
    private function registerPublicWebhook(): void
    {
        $this->app->post('/webhooks/poll-emails', function ($request, $response) {
            $container = \CiInbox\Core\Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\WebhookController::class);
            return $controller->pollEmails($request, $response);
        });
    }

    /**
     * Run the application
     */
    public function run(): void
    {
        try {
            $this->app->run();
        } catch (\Throwable $e) {
            $this->logger->error('Application error', [
                'exception' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            // Execute onError hooks
            $this->hookManager->execute('onError', $e);
            
            throw $e;
        }
    }

    /**
     * Get Slim app instance
     */
    public function getSlimApp(): SlimApp
    {
        return $this->app;
    }

    /**
     * Get hook manager
     */
    public function getHookManager(): HookManager
    {
        return $this->hookManager;
    }

    /**
     * Get module loader
     */
    public function getModuleLoader(): ModuleLoader
    {
        return $this->moduleLoader;
    }
}

<?php

declare(strict_types=1);

namespace CiInbox\App\Controllers;

use CiInbox\App\Services\OAuthService;
use CiInbox\Modules\Logger\LoggerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * OAuth Controller
 * 
 * Handles OAuth authentication flows for any configured provider.
 * Supports custom providers like ChurchTools, Google, Microsoft, etc.
 */
class OAuthController
{
    private OAuthService $oauthService;
    private LoggerService $logger;
    
    /**
     * Allowed hosts for OAuth callbacks (whitelist)
     * Add your production domains here
     */
    private array $allowedHosts = [
        'localhost',
        '127.0.0.1',
        'ci-inbox.local',
    ];

    public function __construct(
        OAuthService $oauthService,
        LoggerService $logger
    ) {
        $this->oauthService = $oauthService;
        $this->logger = $logger;
    }
    
    /**
     * Get validated base URL for OAuth callbacks
     * Prevents Host Header Injection attacks
     */
    private function getValidatedBaseUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // Remove port from host for comparison
        $hostWithoutPort = explode(':', $host)[0];
        
        // Validate against whitelist
        // In production, also check APP_URL from environment
        $appUrl = getenv('APP_URL');
        if ($appUrl) {
            $parsedAppUrl = parse_url($appUrl);
            if (isset($parsedAppUrl['host'])) {
                $this->allowedHosts[] = $parsedAppUrl['host'];
            }
        }
        
        if (!in_array($hostWithoutPort, $this->allowedHosts, true)) {
            $this->logger->warning('Invalid host header detected', [
                'host' => $host,
                'allowed' => $this->allowedHosts
            ]);
            throw new \Exception('Invalid host');
        }
        
        return "{$protocol}://{$host}";
    }

    /**
     * GET /api/oauth/providers
     * List all active OAuth providers
     */
    public function listProviders(Request $request, Response $response): Response
    {
        try {
            $providers = $this->oauthService->getActiveProviders();
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'providers' => $providers
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to list OAuth providers', [
                'error' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Failed to load OAuth providers'
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * GET /api/oauth/authorize/{provider}
     * Initiate OAuth flow for a provider
     */
    public function authorize(Request $request, Response $response, array $args): Response
    {
        try {
            $provider = $args['provider'] ?? '';
            
            if (empty($provider)) {
                throw new \Exception('Provider not specified');
            }
            
            // Build callback URL with validated host
            $baseUrl = $this->getValidatedBaseUrl();
            $redirectUri = "{$baseUrl}/oauth/callback/{$provider}";
            
            $authData = $this->oauthService->initializeAuth($provider, $redirectUri);
            
            // Redirect to provider's authorization page
            return $response
                ->withHeader('Location', $authData['authorization_url'])
                ->withStatus(302);
            
        } catch (\Exception $e) {
            $this->logger->error('OAuth authorization failed', [
                'provider' => $provider ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            // Redirect to login with error
            return $response
                ->withHeader('Location', '/login.php?error=' . urlencode($e->getMessage()))
                ->withStatus(302);
        }
    }

    /**
     * GET /oauth/callback/{provider}
     * Handle OAuth callback from provider
     */
    public function callback(Request $request, Response $response, array $args): Response
    {
        try {
            $provider = $args['provider'] ?? '';
            $params = $request->getQueryParams();
            
            // Check for error from provider
            if (isset($params['error'])) {
                throw new \Exception($params['error_description'] ?? $params['error']);
            }
            
            $code = $params['code'] ?? '';
            $state = $params['state'] ?? '';
            
            if (empty($code)) {
                throw new \Exception('Authorization code not received');
            }
            
            // Build callback URL with validated host (must match authorize)
            $baseUrl = $this->getValidatedBaseUrl();
            $redirectUri = "{$baseUrl}/oauth/callback/{$provider}";
            
            // Handle callback and get user
            $user = $this->oauthService->handleCallback($provider, $code, $state, $redirectUri);
            
            // Create session
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_email'] = $user->email;
            $_SESSION['user_name'] = $user->name;
            $_SESSION['user_role'] = $user->role;
            $_SESSION['logged_in_at'] = time();
            $_SESSION['oauth_provider'] = $provider;
            
            $this->logger->info('OAuth login successful', [
                'provider' => $provider,
                'user_id' => $user->id,
                'email' => $user->email
            ]);
            
            // Redirect to inbox
            return $response
                ->withHeader('Location', '/inbox.php')
                ->withStatus(302);
            
        } catch (\Exception $e) {
            $this->logger->error('OAuth callback failed', [
                'provider' => $provider ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            // Redirect to login with error
            return $response
                ->withHeader('Location', '/login.php?error=' . urlencode('OAuth-Anmeldung fehlgeschlagen: ' . $e->getMessage()))
                ->withStatus(302);
        }
    }

    /**
     * POST /api/admin/oauth/providers
     * Create a new OAuth provider (admin only)
     */
    public function createProvider(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            
            // Validate required fields
            $required = ['name', 'display_name', 'client_id', 'client_secret', 'authorize_url', 'token_url'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new \Exception("Field '{$field}' is required");
                }
            }
            
            $provider = $this->oauthService->saveProvider($data);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'provider' => [
                    'id' => $provider->id,
                    'name' => $provider->name,
                    'display_name' => $provider->display_name
                ]
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to create OAuth provider', [
                'error' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
    }

    /**
     * PUT /api/admin/oauth/providers/{id}
     * Update an OAuth provider (admin only)
     */
    public function updateProvider(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) ($args['id'] ?? 0);
            $data = $request->getParsedBody();
            $data['id'] = $id;
            
            $provider = $this->oauthService->saveProvider($data);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'provider' => [
                    'id' => $provider->id,
                    'name' => $provider->name,
                    'display_name' => $provider->display_name
                ]
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to update OAuth provider', [
                'id' => $id ?? 0,
                'error' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
    }

    /**
     * DELETE /api/admin/oauth/providers/{id}
     * Delete an OAuth provider (admin only)
     */
    public function deleteProvider(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) ($args['id'] ?? 0);
            
            $this->oauthService->deleteProvider($id);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Provider deleted'
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete OAuth provider', [
                'id' => $id ?? 0,
                'error' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
    }
}

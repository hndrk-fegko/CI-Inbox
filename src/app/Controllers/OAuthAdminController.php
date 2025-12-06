<?php
/**
 * OAuth Admin Controller
 * 
 * Handles OAuth2/SSO configuration endpoints for admin interface.
 */

declare(strict_types=1);

namespace CiInbox\App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use CiInbox\App\Services\OAuthAdminService;
use CiInbox\Modules\Logger\LoggerInterface;

class OAuthAdminController
{
    public function __construct(
        private OAuthAdminService $service,
        private LoggerInterface $logger
    ) {}
    
    /**
     * GET /api/admin/oauth/config
     * Get OAuth configuration
     */
    public function getConfig(Request $request, Response $response): Response
    {
        try {
            $config = $this->service->getConfig();
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $config
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('[OAuthAdminController] getConfig failed', [
                'error' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    /**
     * PUT /api/admin/oauth/config
     * Update global OAuth settings
     */
    public function updateConfig(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            $config = $this->service->updateGlobalSettings($data);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $config,
                'message' => 'OAuth settings updated successfully'
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('[OAuthAdminController] updateConfig failed', [
                'error' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    /**
     * PUT /api/admin/oauth/providers/{provider}
     * Update specific provider configuration
     */
    public function updateProvider(Request $request, Response $response, array $args): Response
    {
        try {
            $provider = $args['provider'] ?? '';
            $data = $request->getParsedBody();
            
            if (empty($provider)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Provider not specified'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $config = $this->service->updateProvider($provider, $data);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $config,
                'message' => ucfirst($provider) . ' provider updated successfully'
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('[OAuthAdminController] updateProvider failed', [
                'provider' => $args['provider'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    /**
     * GET /api/admin/oauth/stats
     * Get OAuth statistics
     */
    public function getStats(Request $request, Response $response): Response
    {
        try {
            $stats = $this->service->getStats();
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $stats
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('[OAuthAdminController] getStats failed', [
                'error' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    /**
     * GET /api/admin/oauth/users
     * Get users with OAuth connections
     */
    public function getUsers(Request $request, Response $response): Response
    {
        try {
            $users = $this->service->getOAuthUsers();
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $users
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('[OAuthAdminController] getUsers failed', [
                'error' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}

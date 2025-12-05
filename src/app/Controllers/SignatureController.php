<?php

namespace CiInbox\App\Controllers;

use CiInbox\App\Services\SignatureService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class SignatureController
{
    private SignatureService $service;
    
    public function __construct(SignatureService $service)
    {
        $this->service = $service;
    }
    
    /**
     * Get the authenticated user ID from the request/session
     * 
     * @param Request $request
     * @return int User ID
     */
    private function getAuthenticatedUserId(Request $request): int
    {
        // Check for session user (primary auth method)
        if (isset($_SESSION['user']['id'])) {
            return (int) $_SESSION['user']['id'];
        }
        
        // Check for request attribute (set by middleware)
        $user = $request->getAttribute('user');
        if ($user && isset($user['id'])) {
            return (int) $user['id'];
        }
        
        // Fallback for development/testing (should be removed in production)
        // TODO: Remove fallback in production - require authentication
        return 1;
    }
    
    /**
     * Get all personal signatures for current user
     * GET /api/user/signatures
     */
    public function getPersonalSignatures(Request $request, Response $response): Response
    {
        $userId = $this->getAuthenticatedUserId($request);
        
        // Return all signatures (personal + global) for this user
        $result = $this->service->getAllSignaturesForUser($userId);
        
        $response->getBody()->write(json_encode($result));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($result['success'] ? 200 : 500);
    }
    
    /**
     * Get all global signatures (admin)
     * GET /api/admin/signatures
     */
    public function getGlobalSignatures(Request $request, Response $response): Response
    {
        // Return ALL signatures for admin (global + personal for monitoring)
        $result = $this->service->getAllSignatures();
        
        $response->getBody()->write(json_encode($result));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($result['success'] ? 200 : 500);
    }
    
    /**
     * Get single signature by ID
     * GET /api/user/signatures/{id}
     */
    public function getSignature(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $userId = $this->getAuthenticatedUserId($request);
        
        $result = $this->service->getSignature($id, $userId);
        
        $response->getBody()->write(json_encode($result));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($result['success'] ? 200 : 404);
    }
    
    /**
     * Create personal signature
     * POST /api/user/signatures
     */
    public function createPersonalSignature(Request $request, Response $response): Response
    {
        $userId = $this->getAuthenticatedUserId($request);
        $data = $request->getParsedBody();
        
        $result = $this->service->createPersonalSignature($userId, $data);
        
        $response->getBody()->write(json_encode($result));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($result['success'] ? 201 : 400);
    }
    
    /**
     * Create global signature (admin)
     * POST /api/admin/signatures
     */
    public function createGlobalSignature(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        $result = $this->service->createGlobalSignature($data);
        
        $response->getBody()->write(json_encode($result));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($result['success'] ? 201 : 400);
    }
    
    /**
     * Update signature
     * PUT /api/user/signatures/{id}
     */
    public function updateSignature(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $userId = $this->getAuthenticatedUserId($request);
        $data = $request->getParsedBody();
        
        $result = $this->service->updateSignature($id, $data, $userId);
        
        $response->getBody()->write(json_encode($result));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($result['success'] ? 200 : 400);
    }
    
    /**
     * Update global signature (admin)
     * PUT /api/admin/signatures/{id}
     */
    public function updateGlobalSignature(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $data = $request->getParsedBody();
        
        $result = $this->service->updateSignature($id, $data, null);
        
        $response->getBody()->write(json_encode($result));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($result['success'] ? 200 : 400);
    }
    
    /**
     * Delete signature
     * DELETE /api/user/signatures/{id}
     */
    public function deleteSignature(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $userId = $this->getAuthenticatedUserId($request);
        
        $result = $this->service->deleteSignature($id, $userId);
        
        $response->getBody()->write(json_encode($result));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($result['success'] ? 200 : 404);
    }
    
    /**
     * Delete global signature (admin)
     * DELETE /api/admin/signatures/{id}
     */
    public function deleteGlobalSignature(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        
        $result = $this->service->deleteSignature($id, null);
        
        $response->getBody()->write(json_encode($result));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($result['success'] ? 200 : 404);
    }
    
    /**
     * Set signature as default
     * POST /api/user/signatures/{id}/set-default
     */
    public function setAsDefault(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $userId = $this->getAuthenticatedUserId($request);
        
        $result = $this->service->setAsDefault($id, $userId);
        
        $response->getBody()->write(json_encode($result));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($result['success'] ? 200 : 404);
    }
    
    /**
     * Set global signature as default (admin)
     * POST /api/admin/signatures/{id}/set-default
     */
    public function setGlobalAsDefault(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        
        $result = $this->service->setAsDefault($id, null);
        
        $response->getBody()->write(json_encode($result));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($result['success'] ? 200 : 404);
    }
    
    /**
     * Check if SMTP is configured
     * GET /api/user/signatures/smtp-status
     */
    public function getSmtpStatus(Request $request, Response $response): Response
    {
        $configured = $this->service->isSmtpConfigured();
        
        $result = [
            'success' => true,
            'smtp_configured' => $configured
        ];
        
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }
}

<?php

declare(strict_types=1);

namespace CiInbox\App\Controllers;

use CiInbox\App\Services\PersonalImapAccountService;
use CiInbox\Modules\Logger\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Personal IMAP Account Controller
 * 
 * Endpoints: /api/user/imap-accounts
 * 
 * IMPORTANT: These are USER's personal IMAP accounts (Gmail, Outlook, etc.)
 * NOT the main shared inbox connection!
 */
class PersonalImapAccountController
{
    public function __construct(
        private PersonalImapAccountService $service,
        private LoggerInterface $logger
    ) {}

    /**
     * GET /api/user/imap-accounts
     * List all personal IMAP accounts for current user
     */
    public function index(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');
        
        $this->logger->debug('API: List personal IMAP accounts', [
            'user_id' => $userId
        ]);

        try {
            $accounts = $this->service->getUserAccounts($userId);
            
            // Remove sensitive data
            $publicAccounts = array_map(function($account) {
                return [
                    'id' => $account->id,
                    'email' => $account->email,
                    'imap_host' => $account->imap_host,
                    'imap_port' => $account->imap_port,
                    'imap_username' => $account->imap_username,
                    'imap_encryption' => $account->imap_encryption,
                    'is_default' => $account->is_default,
                    'is_active' => $account->is_active,
                    'last_sync_at' => $account->last_sync_at,
                    'created_at' => $account->created_at,
                    'updated_at' => $account->updated_at
                ];
            }, $accounts);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $publicAccounts,
                'count' => count($publicAccounts)
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
                
        } catch (\Exception $e) {
            $this->logger->error('API: Failed to list personal IMAP accounts', [
                'error' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * GET /api/user/imap-accounts/{id}
     * Get single personal IMAP account
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $accountId = (int)$args['id'];
        $userId = $request->getAttribute('user_id');
        
        $this->logger->debug('API: Get personal IMAP account', [
            'account_id' => $accountId,
            'user_id' => $userId
        ]);

        try {
            $account = $this->service->getAccount($accountId, $userId);
            
            if (!$account) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Account not found or access denied'
                ]));
                
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }
            
            // Remove sensitive data
            $publicAccount = [
                'id' => $account->id,
                'email' => $account->email,
                'imap_host' => $account->imap_host,
                'imap_port' => $account->imap_port,
                'imap_username' => $account->imap_username,
                'imap_encryption' => $account->imap_encryption,
                'is_default' => $account->is_default,
                'is_active' => $account->is_active,
                'last_sync_at' => $account->last_sync_at,
                'created_at' => $account->created_at,
                'updated_at' => $account->updated_at
            ];
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $publicAccount
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
                
        } catch (\Exception $e) {
            $this->logger->error('API: Failed to get personal IMAP account', [
                'account_id' => $accountId,
                'error' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * POST /api/user/imap-accounts
     * Create new personal IMAP account
     */
    public function create(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user_id');
        $data = $request->getParsedBody();
        
        $this->logger->debug('API: Create personal IMAP account', [
            'user_id' => $userId,
            'email' => $data['email'] ?? 'unknown'
        ]);

        try {
            $account = $this->service->createAccount($userId, $data);
            
            // Remove sensitive data
            $publicAccount = [
                'id' => $account->id,
                'email' => $account->email,
                'imap_host' => $account->imap_host,
                'imap_port' => $account->imap_port,
                'imap_username' => $account->imap_username,
                'imap_encryption' => $account->imap_encryption,
                'is_default' => $account->is_default,
                'is_active' => $account->is_active,
                'created_at' => $account->created_at
            ];
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $publicAccount
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
                
        } catch (\Exception $e) {
            $this->logger->error('API: Failed to create personal IMAP account', [
                'user_id' => $userId,
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
     * PUT /api/user/imap-accounts/{id}
     * Update personal IMAP account
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $accountId = (int)$args['id'];
        $userId = $request->getAttribute('user_id');
        $data = $request->getParsedBody();
        
        $this->logger->debug('API: Update personal IMAP account', [
            'account_id' => $accountId,
            'user_id' => $userId
        ]);

        try {
            $account = $this->service->updateAccount($accountId, $userId, $data);
            
            // Remove sensitive data
            $publicAccount = [
                'id' => $account->id,
                'email' => $account->email,
                'imap_host' => $account->imap_host,
                'imap_port' => $account->imap_port,
                'imap_username' => $account->imap_username,
                'imap_encryption' => $account->imap_encryption,
                'is_default' => $account->is_default,
                'is_active' => $account->is_active,
                'updated_at' => $account->updated_at
            ];
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $publicAccount
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
                
        } catch (\Exception $e) {
            $this->logger->error('API: Failed to update personal IMAP account', [
                'account_id' => $accountId,
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
     * DELETE /api/user/imap-accounts/{id}
     * Delete personal IMAP account
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        $accountId = (int)$args['id'];
        $userId = $request->getAttribute('user_id');
        
        $this->logger->debug('API: Delete personal IMAP account', [
            'account_id' => $accountId,
            'user_id' => $userId
        ]);

        try {
            $this->service->deleteAccount($accountId, $userId);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Account deleted successfully'
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
                
        } catch (\Exception $e) {
            $this->logger->error('API: Failed to delete personal IMAP account', [
                'account_id' => $accountId,
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
     * POST /api/user/imap-accounts/{id}/test-connection
     * Test IMAP connection
     */
    public function testConnection(Request $request, Response $response, array $args): Response
    {
        $accountId = (int)$args['id'];
        $userId = $request->getAttribute('user_id');
        
        $this->logger->debug('API: Test IMAP connection', [
            'account_id' => $accountId,
            'user_id' => $userId
        ]);

        try {
            $result = $this->service->testConnection($accountId, $userId);
            
            $response->getBody()->write(json_encode([
                'success' => $result['success'],
                'message' => $result['message']
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($result['success'] ? 200 : 400);
                
        } catch (\Exception $e) {
            $this->logger->error('API: Failed to test IMAP connection', [
                'account_id' => $accountId,
                'error' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}

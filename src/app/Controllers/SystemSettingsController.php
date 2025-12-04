<?php

namespace CiInbox\App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use CiInbox\App\Services\SystemSettingsService;
use CiInbox\App\Services\AutoDiscoverService;
use CiInbox\Modules\Logger\LoggerInterface;

/**
 * SystemSettings Controller
 * 
 * Handles system-wide configuration endpoints (admin only).
 */
class SystemSettingsController
{
    public function __construct(
        private SystemSettingsService $service,
        private AutoDiscoverService $autoDiscover,
        private LoggerInterface $logger
    ) {}
    
    /**
     * GET /api/admin/settings/imap
     * Get IMAP configuration
     */
    public function getImapConfig(Request $request, Response $response): Response
    {
        try {
            $config = $this->service->getImapConfig();
            
            // Mask password in response
            if (!empty($config['password'])) {
                $config['password'] = '********';
            }
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $config
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get IMAP config', [
                'exception' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    /**
     * PUT /api/admin/settings/imap
     * Update IMAP configuration
     */
    public function updateImapConfig(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            
            $config = $this->service->updateImapConfig($data);
            
            // Mask password in response
            if (!empty($config['password'])) {
                $config['password'] = '********';
            }
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $config,
                'message' => 'IMAP configuration updated successfully'
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to update IMAP config', [
                'exception' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    /**
     * POST /api/admin/settings/imap/test
     * Test IMAP connection
     */
    public function testImapConnection(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            
            // Test with provided config or use stored config
            $result = $this->service->testImapConnection($data ?? null);
            
            $response->getBody()->write(json_encode([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result['folders'] ?? []
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to test IMAP connection', [
                'exception' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    /**
     * GET /api/admin/settings/smtp
     * Get SMTP configuration
     */
    public function getSmtpConfig(Request $request, Response $response): Response
    {
        try {
            $config = $this->service->getSmtpConfig();
            
            // Mask password in response
            if (!empty($config['password'])) {
                $config['password'] = '********';
            }
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $config
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get SMTP config', [
                'exception' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    /**
     * PUT /api/admin/settings/smtp
     * Update SMTP configuration
     */
    public function updateSmtpConfig(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            
            $config = $this->service->updateSmtpConfig($data);
            
            // Mask password in response
            if (!empty($config['password'])) {
                $config['password'] = '********';
            }
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $config,
                'message' => 'SMTP configuration updated successfully'
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to update SMTP config', [
                'exception' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    /**
     * POST /api/admin/settings/smtp/test
     * Test SMTP connection
     */
    public function testSmtpConnection(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            
            $testEmail = $data['test_email'] ?? null;
            unset($data['test_email']);
            
            // Test with provided config or use stored config
            $result = $this->service->testSmtpConnection(
                empty($data) ? null : $data,
                $testEmail
            );
            
            $response->getBody()->write(json_encode([
                'success' => $result['success'],
                'message' => $result['message']
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to test SMTP connection', [
                'exception' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    /**
     * GET /api/admin/settings
     * Get all settings (admin view)
     */
    public function getAllSettings(Request $request, Response $response): Response
    {
        try {
            $settings = $this->service->getAllSettings();
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $settings
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get all settings', [
                'exception' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    /**
     * POST /api/admin/settings/imap/autodiscover
     * Auto-discover IMAP configuration from email address
     */
    public function autodiscoverImap(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            $email = $data['email'] ?? null;
            
            if (empty($email)) {
                throw new \InvalidArgumentException('Email address is required');
            }
            
            $result = $this->autoDiscover->discoverImap($email);
            
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('IMAP autodiscover failed', [
                'exception' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
    /**
     * POST /api/admin/settings/smtp/autodiscover
     * Auto-discover SMTP configuration from email address
     */
    public function autodiscoverSmtp(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            $email = $data['email'] ?? null;
            
            if (empty($email)) {
                throw new \InvalidArgumentException('Email address is required');
            }
            
            $result = $this->autoDiscover->discoverSmtp($email);
            
            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('SMTP autodiscover failed', [
                'exception' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}

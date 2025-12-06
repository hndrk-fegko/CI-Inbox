<?php
/**
 * Logger Admin Controller
 * 
 * Handles logger configuration and viewing endpoints for admin interface.
 */

declare(strict_types=1);

namespace CiInbox\App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use CiInbox\App\Services\LoggerAdminService;
use CiInbox\Modules\Logger\LoggerInterface;

class LoggerAdminController
{
    public function __construct(
        private LoggerAdminService $service,
        private LoggerInterface $logger
    ) {}
    
    /**
     * GET /api/admin/logger/level
     * Get current log level configuration
     */
    public function getLevel(Request $request, Response $response): Response
    {
        try {
            $result = $this->service->getLogLevel();
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $result
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('[LoggerAdminController] getLevel failed', [
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
     * PUT /api/admin/logger/level
     * Set log level
     */
    public function setLevel(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            $level = $data['level'] ?? null;
            
            if (!$level) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Log level is required'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $result = $this->service->setLogLevel($level);
            
            $response->getBody()->write(json_encode([
                'success' => $result['success'],
                'data' => $result,
                'message' => $result['message']
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('[LoggerAdminController] setLevel failed', [
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
     * GET /api/admin/logger/stream
     * Get log entries (for live viewer)
     */
    public function getStream(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $limit = isset($params['limit']) ? (int)$params['limit'] : 100;
            $level = $params['level'] ?? null;
            $search = $params['search'] ?? null;
            
            if ($level === 'all') {
                $level = null;
            }
            
            $result = $this->service->getLogEntries($limit, $level, $search);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $result
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('[LoggerAdminController] getStream failed', [
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
     * GET /api/admin/logger/stats
     * Get log file statistics
     */
    public function getStats(Request $request, Response $response): Response
    {
        try {
            $stats = $this->service->getLogStats();
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $stats
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('[LoggerAdminController] getStats failed', [
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
     * POST /api/admin/logger/clear
     * Clear all log files
     */
    public function clearLogs(Request $request, Response $response): Response
    {
        try {
            $result = $this->service->clearLogs();
            
            $response->getBody()->write(json_encode([
                'success' => $result['success'],
                'data' => $result,
                'message' => $result['message']
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('[LoggerAdminController] clearLogs failed', [
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
     * POST /api/admin/logger/download
     * Download logs as a single file
     */
    public function downloadLogs(Request $request, Response $response): Response
    {
        try {
            $result = $this->service->downloadLogs();
            
            if (!$result['success']) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => $result['message'] ?? 'Failed to download logs'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
            
            $response->getBody()->write($result['content']);
            
            return $response
                ->withHeader('Content-Type', 'text/plain')
                ->withHeader('Content-Disposition', 'attachment; filename="' . $result['filename'] . '"');
            
        } catch (\Exception $e) {
            $this->logger->error('[LoggerAdminController] downloadLogs failed', [
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

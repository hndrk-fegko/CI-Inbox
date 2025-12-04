<?php
/**
 * Cron Monitor Controller
 * 
 * Handles cron monitoring endpoints (admin only)
 */

declare(strict_types=1);

namespace CiInbox\App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use CiInbox\App\Services\CronMonitorService;
use CiInbox\Modules\Logger\LoggerInterface;

class CronMonitorController
{
    public function __construct(
        private CronMonitorService $service,
        private LoggerInterface $logger
    ) {}
    
    /**
     * GET /api/admin/cron/status
     * Get current cron service status
     */
    public function getStatus(Request $request, Response $response): Response
    {
        try {
            $status = $this->service->getStatus();
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $status
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('[CronMonitorController] Failed to get status', [
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
     * GET /api/admin/cron/history
     * Get execution history
     */
    public function getHistory(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $limit = isset($params['limit']) ? (int)$params['limit'] : 20;
            
            $history = $this->service->getHistory($limit);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $history
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('[CronMonitorController] Failed to get history', [
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
     * GET /api/admin/cron/statistics
     * Get cron execution statistics
     */
    public function getStatistics(Request $request, Response $response): Response
    {
        try {
            $stats = $this->service->getStatistics();
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $stats
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('[CronMonitorController] Failed to get statistics', [
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

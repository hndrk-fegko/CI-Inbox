<?php
/**
 * Health Admin Controller
 * 
 * API endpoints for system health monitoring, tests, and self-healing.
 */

declare(strict_types=1);

namespace CiInbox\App\Controllers;

use CiInbox\App\Services\HealthAdminService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HealthAdminController
{
    public function __construct(
        private HealthAdminService $healthService
    ) {}
    
    /**
     * GET /api/admin/health/summary
     * Get overall health summary
     */
    public function getSummary(Request $request, Response $response): Response
    {
        try {
            $summary = $this->healthService->getSummary();
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $summary
            ]));
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
        }
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    /**
     * GET /api/admin/health/status
     * Get detailed health status
     */
    public function getStatus(Request $request, Response $response): Response
    {
        try {
            $status = $this->healthService->getStatus();
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $status
            ]));
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
        }
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    /**
     * GET /api/admin/health/schedule
     * Get health check schedule
     */
    public function getSchedule(Request $request, Response $response): Response
    {
        try {
            $schedule = $this->healthService->getSchedule();
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $schedule
            ]));
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
        }
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    /**
     * PUT /api/admin/health/schedule
     * Update health check schedule
     */
    public function updateSchedule(Request $request, Response $response): Response
    {
        try {
            $body = json_decode((string)$request->getBody(), true);
            
            if (!$body) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Invalid request body'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $schedule = $this->healthService->updateSchedule($body);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $schedule
            ]));
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
        }
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    /**
     * POST /api/admin/health/test/{testName}
     * Run a specific health test
     */
    public function runTest(Request $request, Response $response, array $args): Response
    {
        try {
            $testName = $args['testName'] ?? '';
            
            if (empty($testName)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Test name is required'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            // Validate test name
            $validTests = ['database', 'imap', 'smtp', 'disk', 'cron', 'queue', 'sessions'];
            if (!in_array($testName, $validTests)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Invalid test name. Valid tests: ' . implode(', ', $validTests)
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $result = $this->healthService->runTest($testName);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $result
            ]));
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
        }
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    /**
     * GET /api/admin/health/reports
     * Get test reports
     */
    public function getReports(Request $request, Response $response): Response
    {
        try {
            $params = $request->getQueryParams();
            $limit = isset($params['limit']) ? (int)$params['limit'] : 20;
            
            $reports = $this->healthService->getReports($limit);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $reports
            ]));
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
        }
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    /**
     * POST /api/admin/health/heal/{healType}
     * Execute self-healing action
     */
    public function selfHeal(Request $request, Response $response, array $args): Response
    {
        try {
            $healType = $args['healType'] ?? '';
            
            if (empty($healType)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Heal type is required'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            // Validate heal type
            $validTypes = ['disk', 'queue', 'sessions'];
            if (!in_array($healType, $validTypes)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Invalid heal type. Valid types: ' . implode(', ', $validTypes)
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            
            $result = $this->healthService->selfHeal($healType);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $result
            ]));
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
        }
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    /**
     * GET /api/admin/health/healing-log
     * Get self-healing action log
     */
    public function getHealingLog(Request $request, Response $response): Response
    {
        try {
            $log = $this->healthService->getHealingLog();
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $log
            ]));
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
        }
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    /**
     * DELETE /api/admin/health/healing-log
     * Clear self-healing log
     */
    public function clearHealingLog(Request $request, Response $response): Response
    {
        try {
            $this->healthService->clearHealingLog();
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Healing log cleared'
            ]));
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
        }
        
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    /**
     * GET /api/admin/health/export
     * Export complete health report
     */
    public function exportReport(Request $request, Response $response): Response
    {
        try {
            $report = $this->healthService->exportReport();
            
            $filename = 'health-report-' . date('Y-m-d_H-i-s') . '.json';
            
            $response->getBody()->write(json_encode($report, JSON_PRETTY_PRINT));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
        }
    }
}

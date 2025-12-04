<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\SystemHealthService;
use Psr\Log\LoggerInterface;

/**
 * System Health Controller
 * 
 * Exposes health check endpoints for monitoring and Keep-it-easy UpdateServer integration.
 * 
 * Endpoints:
 * - GET /api/system/health - Basic public health check
 * - GET /api/system/health/detailed - Detailed health check (requires auth)
 */
class SystemHealthController
{
    private SystemHealthService $healthService;
    private LoggerInterface $logger;

    public function __construct(SystemHealthService $healthService, LoggerInterface $logger)
    {
        $this->healthService = $healthService;
        $this->logger = $logger;
    }

    /**
     * Basic health check endpoint (public)
     * 
     * Returns simple ok/error status for load balancers and monitoring tools.
     * 
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function getBasicHealth(Request $request, Response $response): Response
    {
        try {
            $health = $this->healthService->getBasicHealth();
            
            $statusCode = $health['status'] === 'ok' ? 200 : 503;
            
            $response->getBody()->write(json_encode($health));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($statusCode);
        } catch (\Exception $e) {
            $this->logger->error('Basic health check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $response->getBody()->write(json_encode([
                'status' => 'error',
                'error' => 'Health check failed',
                'timestamp' => time()
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(503);
        }
    }

    /**
     * Detailed health check endpoint (authenticated)
     * 
     * Returns comprehensive health data including all modules, metrics, and analysis.
     * Compatible with Keep-it-easy UpdateServer protocol.
     * 
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function getDetailedHealth(Request $request, Response $response): Response
    {
        try {
            // Get detailed health data
            $health = $this->healthService->getDetailedHealth();
            
            // Analyze health and add assessment
            $analysis = $this->healthService->analyzeHealth($health);
            $health['analysis'] = $analysis->toArray();
            
            $statusCode = $analysis->isHealthy ? 200 : ($analysis->overallStatus === 'critical' ? 503 : 200);
            
            $response->getBody()->write(json_encode($health, JSON_PRETTY_PRINT));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($statusCode);
        } catch (\Exception $e) {
            $this->logger->error('Detailed health check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $response->getBody()->write(json_encode([
                'status' => 'error',
                'error' => 'Detailed health check failed: ' . $e->getMessage(),
                'timestamp' => time()
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Generate Keep-it-easy compatible report
     * 
     * Returns health data in the format expected by Keep-it-easy UpdateServer.
     * Used when UpdateServer pulls health status from this installation.
     * 
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function getUpdateServerReport(Request $request, Response $response): Response
    {
        try {
            $report = $this->healthService->generateUpdateServerReport();
            
            $this->logger->info('Generated UpdateServer health report', [
                'installation_id' => $report['installation_id']
            ]);
            
            $response->getBody()->write(json_encode($report, JSON_PRETTY_PRINT));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (\Exception $e) {
            $this->logger->error('UpdateServer report generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $response->getBody()->write(json_encode([
                'error' => 'Report generation failed',
                'timestamp' => time()
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * Send health report to UpdateServer (push mode)
     * 
     * Manually triggers sending a health report to the configured UpdateServer.
     * Normally this is done via cron, but this endpoint allows manual triggering.
     * 
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function sendReportToUpdateServer(Request $request, Response $response): Response
    {
        try {
            // Check if UpdateServer integration is enabled
            if (empty($_ENV['UPDATE_SERVER_ENABLED']) || $_ENV['UPDATE_SERVER_ENABLED'] !== 'true') {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'UpdateServer integration not enabled'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
            }

            if (empty($_ENV['UPDATE_SERVER_URL']) || empty($_ENV['UPDATE_SERVER_TOKEN'])) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'UpdateServer URL or TOKEN not configured'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
            }

            // Generate report
            $report = $this->healthService->generateUpdateServerReport();

            // Send to UpdateServer
            $updateServerUrl = $_ENV['UPDATE_SERVER_URL'] . '/api/v1/health_api.php';
            $updateServerToken = $_ENV['UPDATE_SERVER_TOKEN'];

            $ch = curl_init($updateServerUrl);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($report),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'X-Server-Token: ' . $updateServerToken
                ],
                CURLOPT_TIMEOUT => 10
            ]);

            $curlResponse = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                throw new \Exception('Curl error: ' . $curlError);
            }

            if ($httpCode !== 200) {
                throw new \Exception('UpdateServer returned HTTP ' . $httpCode);
            }

            $serverResponse = json_decode($curlResponse, true);

            $this->logger->info('Health report sent to UpdateServer', [
                'installation_id' => $report['installation_id'],
                'http_code' => $httpCode,
                'server_response' => $serverResponse
            ]);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Report sent successfully',
                'installation_id' => $report['installation_id'],
                'server_response' => $serverResponse
            ]));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send report to UpdateServer', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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

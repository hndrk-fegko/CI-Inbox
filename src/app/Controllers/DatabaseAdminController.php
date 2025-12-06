<?php
/**
 * Database Admin Controller
 * 
 * Handles database administration endpoints for admin interface.
 */

declare(strict_types=1);

namespace CiInbox\App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use CiInbox\App\Services\DatabaseAdminService;
use CiInbox\Modules\Logger\LoggerInterface;

class DatabaseAdminController
{
    public function __construct(
        private DatabaseAdminService $service,
        private LoggerInterface $logger
    ) {}
    
    /**
     * GET /api/admin/database/status
     * Get database connection status and statistics
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
            $this->logger->error('[DatabaseAdminController] getStatus failed', [
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
     * GET /api/admin/database/tables
     * List all tables with sizes and row counts
     */
    public function getTables(Request $request, Response $response): Response
    {
        try {
            $tables = $this->service->getTables();
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $tables
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('[DatabaseAdminController] getTables failed', [
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
     * POST /api/admin/database/optimize
     * Optimize all database tables
     */
    public function optimize(Request $request, Response $response): Response
    {
        try {
            $result = $this->service->optimizeTables();
            
            $response->getBody()->write(json_encode([
                'success' => $result['success'],
                'data' => $result,
                'message' => $result['message']
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('[DatabaseAdminController] optimize failed', [
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
     * POST /api/admin/database/analyze
     * Analyze all database tables
     */
    public function analyze(Request $request, Response $response): Response
    {
        try {
            $result = $this->service->analyzeTables();
            
            $response->getBody()->write(json_encode([
                'success' => $result['success'],
                'data' => $result,
                'message' => $result['message']
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('[DatabaseAdminController] analyze failed', [
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
     * GET /api/admin/database/orphaned
     * Check for orphaned data in database
     */
    public function checkOrphaned(Request $request, Response $response): Response
    {
        try {
            $result = $this->service->checkOrphanedData();
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $result
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('[DatabaseAdminController] checkOrphaned failed', [
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
     * GET /api/admin/database/migrations
     * Get migration status
     */
    public function getMigrations(Request $request, Response $response): Response
    {
        try {
            $result = $this->service->getMigrationStatus();
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $result
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('[DatabaseAdminController] getMigrations failed', [
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

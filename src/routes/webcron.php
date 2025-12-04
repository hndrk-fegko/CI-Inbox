<?php
declare(strict_types=1);

/**
 * Webcron Routes
 * 
 * Endpoints für E-Mail-Polling via Webcron
 */

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;
use CiInbox\Modules\Webcron\WebcronManagerInterface;
use CiInbox\Modules\Logger\LoggerInterface;

return function (RouteCollectorProxy $group) {
    
    /**
     * GET /webcron/poll
     * 
     * Triggert E-Mail-Polling für alle aktiven Accounts
     * 
     * Query-Parameter:
     * - api_key: Authentifizierung (required)
     * - account_id: Nur einen Account abrufen (optional)
     * 
     * Response:
     * {
     *   "success": true,
     *   "result": {
     *     "accounts_processed": 2,
     *     "total_emails": 5,
     *     "errors": []
     *   }
     * }
     */
    $group->get('/poll', function (Request $request, Response $response) {
        /** @var WebcronManagerInterface $webcronManager */
        $webcronManager = $this->get(WebcronManagerInterface::class);
        $config = $this->get('webcron.config');
        $logger = $this->get(LoggerInterface::class);
        
        // 1. API-Key validieren
        $apiKey = $request->getQueryParams()['api_key'] ?? null;
        if ($apiKey !== $config['api_key']) {
            $logger->warning('Webcron poll: Invalid API key', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Invalid API key'
            ]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        
        // 2. IP-Whitelist prüfen
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
        if (!in_array($clientIp, $config['allowed_ips'])) {
            $logger->warning('Webcron poll: IP not allowed', [
                'ip' => $clientIp,
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'IP not allowed'
            ]));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }
        
        // 3. Polling durchführen
        try {
            $accountId = $request->getQueryParams()['account_id'] ?? null;
            
            if ($accountId) {
                $logger->info('Webcron poll triggered for single account', [
                    'account_id' => $accountId,
                    'ip' => $clientIp,
                ]);
                
                $result = $webcronManager->pollAccount((int)$accountId);
            } else {
                $logger->info('Webcron poll triggered for all accounts', [
                    'ip' => $clientIp,
                ]);
                
                $result = $webcronManager->runPollingJob();
            }
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'result' => $result
            ]));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $logger->error('Webcron poll failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });
    
    /**
     * GET /webcron/status
     * 
     * Gibt Webcron-Status zurück (ohne Authentifizierung für Monitoring)
     * 
     * Response:
     * {
     *   "success": true,
     *   "status": {
     *     "is_running": false,
     *     "active_accounts": 2,
     *     "last_run": "2025-11-17 12:30:00",
     *     "last_run_result": {
     *       "accounts_processed": 2,
     *       "emails_fetched": 5,
     *       "duration_seconds": 3.45,
     *       "errors_count": 0
     *     }
     *   }
     * }
     */
    $group->get('/status', function (Request $request, Response $response) {
        /** @var WebcronManagerInterface $webcronManager */
        $webcronManager = $this->get(WebcronManagerInterface::class);
        
        try {
            $status = $webcronManager->getJobStatus();
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'status' => $status
            ]));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });
    
    /**
     * GET /webcron/test
     * 
     * Testet Webcron-Setup ohne E-Mails abzurufen
     * Authentifizierung erforderlich
     * 
     * Response:
     * {
     *   "success": true,
     *   "checks": {
     *     "config_loaded": true,
     *     "active_accounts": true,
     *     "active_accounts_count": 2,
     *     "polling_service_available": true,
     *     "logger_available": true,
     *     "config_key_polling_interval": true,
     *     "config_key_max_emails_per_run": true,
     *     "config_key_folders": true
     *   }
     * }
     */
    $group->get('/test', function (Request $request, Response $response) {
        /** @var WebcronManagerInterface $webcronManager */
        $webcronManager = $this->get(WebcronManagerInterface::class);
        $config = $this->get('webcron.config');
        $logger = $this->get(LoggerInterface::class);
        
        // API-Key validieren
        $apiKey = $request->getQueryParams()['api_key'] ?? null;
        if ($apiKey !== $config['api_key']) {
            $logger->warning('Webcron test: Invalid API key', [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Invalid API key'
            ]));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
        
        try {
            $testResult = $webcronManager->testSetup();
            
            $response->getBody()->write(json_encode($testResult));
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    });
};

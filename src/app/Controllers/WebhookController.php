<?php

declare(strict_types=1);

namespace CiInbox\App\Controllers;

use CiInbox\App\Services\WebhookService;
use CiInbox\Modules\Logger\LoggerService;
use CiInbox\Modules\Webcron\WebcronManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * WebhookController
 * 
 * REST API endpoints for webhook management.
 */
class WebhookController
{
    private WebhookService $webhookService;
    private LoggerService $logger;
    private WebcronManager $webcronManager;
    
    // Valid event types
    private const VALID_EVENTS = [
        'thread.created',
        'thread.updated',
        'thread.deleted',
        'email.received',
        'email.sent',
        'note.added'
    ];
    
    public function __construct(
        WebhookService $webhookService, 
        LoggerService $logger,
        WebcronManager $webcronManager
    ) {
        $this->webhookService = $webhookService;
        $this->logger = $logger;
        $this->webcronManager = $webcronManager;
    }
    
    /**
     * POST /api/webhooks - Register new webhook
     */
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            
            // Validate required fields
            $errors = $this->validateWebhookData($data);
            if (!empty($errors)) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'errors' => $errors
                ], 400);
            }
            
            $webhook = $this->webhookService->register($data);
            
            $this->logger->info("Webhook created via API", [
                'webhook_id' => $webhook->id,
                'url' => $webhook->url
            ]);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'webhook' => [
                    'id' => $webhook->id,
                    'url' => $webhook->url,
                    'events' => $webhook->events,
                    'secret' => $webhook->secret,
                    'is_active' => $webhook->is_active,
                    'created_at' => $webhook->created_at->toIso8601String()
                ]
            ], 201);
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to create webhook", [
                'error' => $e->getMessage()
            ]);
            
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Failed to create webhook: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /api/webhooks - List all webhooks
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            $page = (int) ($queryParams['page'] ?? 1);
            $perPage = min((int) ($queryParams['per_page'] ?? 20), 100); // Max 100
            
            $result = $this->webhookService->getAllWebhooks($page, $perPage);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'webhooks' => $result['data']->map(fn($w) => [
                    'id' => $w->id,
                    'url' => $w->url,
                    'events' => $w->events,
                    'is_active' => $w->is_active,
                    'failed_attempts' => $w->failed_attempts,
                    'last_triggered_at' => $w->last_triggered_at?->toIso8601String(),
                    'created_at' => $w->created_at->toIso8601String()
                ])->toArray(),
                'pagination' => [
                    'total' => $result['total'],
                    'page' => $result['page'],
                    'per_page' => $result['per_page'],
                    'last_page' => $result['last_page']
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to list webhooks", [
                'error' => $e->getMessage()
            ]);
            
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Failed to list webhooks: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /api/webhooks/{id} - Get webhook details
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            $webhook = \CiInbox\App\Models\Webhook::findOrFail($id);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'webhook' => [
                    'id' => $webhook->id,
                    'url' => $webhook->url,
                    'events' => $webhook->events,
                    'secret' => $webhook->secret,
                    'is_active' => $webhook->is_active,
                    'failed_attempts' => $webhook->failed_attempts,
                    'last_triggered_at' => $webhook->last_triggered_at?->toIso8601String(),
                    'created_at' => $webhook->created_at->toIso8601String(),
                    'updated_at' => $webhook->updated_at->toIso8601String()
                ]
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Webhook not found'
            ], 404);
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to get webhook", [
                'webhook_id' => $args['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Failed to get webhook: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * PUT /api/webhooks/{id} - Update webhook
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            $data = $request->getParsedBody();
            
            // Validate if events provided
            if (isset($data['events'])) {
                $errors = $this->validateEvents($data['events']);
                if (!empty($errors)) {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'errors' => $errors
                    ], 400);
                }
            }
            
            $webhook = $this->webhookService->update($id, $data);
            
            $this->logger->info("Webhook updated via API", [
                'webhook_id' => $id,
                'changes' => array_keys($data)
            ]);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'webhook' => [
                    'id' => $webhook->id,
                    'url' => $webhook->url,
                    'events' => $webhook->events,
                    'is_active' => $webhook->is_active,
                    'updated_at' => $webhook->updated_at->toIso8601String()
                ]
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Webhook not found'
            ], 404);
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to update webhook", [
                'webhook_id' => $args['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Failed to update webhook: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * DELETE /api/webhooks/{id} - Delete webhook
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            $this->webhookService->delete($id);
            
            $this->logger->info("Webhook deleted via API", ['webhook_id' => $id]);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Webhook deleted successfully'
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Webhook not found'
            ], 404);
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to delete webhook", [
                'webhook_id' => $args['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Failed to delete webhook: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /api/webhooks/{id}/deliveries - Get delivery history
     */
    public function deliveries(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            $queryParams = $request->getQueryParams();
            $limit = min((int) ($queryParams['limit'] ?? 50), 100); // Max 100
            
            $deliveries = $this->webhookService->getDeliveries($id, $limit);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'deliveries' => $deliveries->map(fn($d) => [
                    'id' => $d->id,
                    'event_type' => $d->event_type,
                    'response_status' => $d->response_status,
                    'attempts' => $d->attempts,
                    'delivered_at' => $d->delivered_at?->toIso8601String(),
                    'created_at' => $d->created_at->toIso8601String(),
                    'is_successful' => $d->isSuccessful()
                ])->toArray()
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to get webhook deliveries", [
                'webhook_id' => $args['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Failed to get deliveries: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * POST /api/webhooks/deliveries/{id}/retry - Retry failed delivery
     */
    public function retry(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int) $args['id'];
            $this->webhookService->retry($id);
            
            $this->logger->info("Webhook delivery retried via API", [
                'delivery_id' => $id
            ]);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Webhook delivery retry initiated'
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Delivery not found'
            ], 404);
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to retry webhook delivery", [
                'delivery_id' => $args['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Failed to retry delivery: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Validate webhook data
     */
    private function validateWebhookData(array $data): array
    {
        $errors = [];
        
        // URL required
        if (empty($data['url'])) {
            $errors[] = 'URL is required';
        } elseif (!filter_var($data['url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Invalid URL format';
        }
        
        // Events required
        if (empty($data['events']) || !is_array($data['events'])) {
            $errors[] = 'Events array is required';
        } else {
            $eventErrors = $this->validateEvents($data['events']);
            $errors = array_merge($errors, $eventErrors);
        }
        
        return $errors;
    }
    
    /**
     * Validate event types
     */
    private function validateEvents(array $events): array
    {
        $errors = [];
        
        if (empty($events)) {
            $errors[] = 'At least one event is required';
        }
        
        foreach ($events as $event) {
            if (!in_array($event, self::VALID_EVENTS)) {
                $errors[] = "Invalid event: {$event}. Valid events: " . implode(', ', self::VALID_EVENTS);
            }
        }
        
        return $errors;
    }
    
    /**
     * Create JSON response
     */
    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
    
    /**
     * POST /webhooks/poll-emails
     * 
     * External webhook endpoint for cron services (cron-job.org, EasyCron, etc.)
     * Triggers email polling for all active IMAP accounts
     * 
     * Authentication: Secret token in X-Webhook-Token header or request body
     */
    public function pollEmails(Request $request, Response $response): Response
    {
        $startTime = microtime(true);
        
        $this->logger->info('Webhook: Poll emails triggered', [
            'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $request->getHeaderLine('User-Agent')
        ]);

        try {
            // Authentication check
            $token = $this->getAuthToken($request);
            $expectedToken = getenv('WEBCRON_SECRET_TOKEN') ?: 'your-secret-token-here';

            if ($token !== $expectedToken) {
                $this->logger->warning('Webhook: Invalid authentication token', [
                    'provided_token' => substr($token, 0, 8) . '***',
                    'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown'
                ]);

                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => 'Invalid authentication token'
                ], 401);
            }

            // Run polling job
            $result = $this->webcronManager->runPollingJob();
            
            // Calculate duration
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            // Log execution to Cron Monitor
            try {
                $container = \CiInbox\Core\Container::getInstance();
                $cronMonitorService = $container->get(\CiInbox\App\Services\CronMonitorService::class);
                
                $cronMonitorService->logExecution(
                    accountsPolled: $result['accounts_processed'] ?? 0,
                    newEmailsFound: $result['emails_fetched'] ?? 0,
                    durationMs: $durationMs,
                    status: 'success'
                );
                
                $this->logger->debug('Cron execution logged', [
                    'accounts' => $result['accounts_processed'] ?? 0,
                    'emails' => $result['emails_fetched'] ?? 0,
                    'duration_ms' => $durationMs
                ]);
                
            } catch (\Exception $logEx) {
                // Don't fail the whole request if logging fails
                $this->logger->error('Failed to log cron execution', [
                    'error' => $logEx->getMessage()
                ]);
            }

            $this->logger->info('Webhook: Email polling completed', [
                'accounts_polled' => $result['accounts_processed'] ?? 0,
                'emails_processed' => $result['emails_fetched'] ?? 0,
                'duration_ms' => $durationMs
            ]);

            return $this->jsonResponse($response, [
                'success' => true,
                'data' => $result
            ], 200);

        } catch (\Exception $e) {
            // Calculate duration
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            
            // Log failed execution
            try {
                $container = \CiInbox\Core\Container::getInstance();
                $cronMonitorService = $container->get(\CiInbox\App\Services\CronMonitorService::class);
                
                $cronMonitorService->logExecution(
                    accountsPolled: 0,
                    newEmailsFound: 0,
                    durationMs: $durationMs,
                    status: 'error',
                    errorMessage: $e->getMessage()
                );
            } catch (\Exception $logEx) {
                $this->logger->error('Failed to log cron execution error', [
                    'error' => $logEx->getMessage()
                ]);
            }
            
            $this->logger->error('Webhook: Email polling failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extract authentication token from request
     * Checks: X-Webhook-Token header, Authorization header, 'token' in body/query
     */
    private function getAuthToken(Request $request): string
    {
        // Check X-Webhook-Token header
        $headerToken = $request->getHeaderLine('X-Webhook-Token');
        if (!empty($headerToken)) {
            return $headerToken;
        }

        // Check Authorization header (Bearer token)
        $authHeader = $request->getHeaderLine('Authorization');
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        // Check request body
        $body = $request->getParsedBody();
        if (is_array($body) && isset($body['token'])) {
            return $body['token'];
        }

        // Check query parameters
        $queryParams = $request->getQueryParams();
        if (isset($queryParams['token'])) {
            return $queryParams['token'];
        }

        return '';
    }
}

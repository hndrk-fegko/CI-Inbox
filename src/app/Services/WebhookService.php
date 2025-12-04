<?php

declare(strict_types=1);

namespace CiInbox\App\Services;

use CiInbox\App\Models\Webhook;
use CiInbox\App\Models\WebhookDelivery;
use CiInbox\Modules\Logger\LoggerService;
use Illuminate\Support\Collection;

/**
 * WebhookService
 * 
 * Handles webhook registration, delivery, and retry logic.
 */
class WebhookService
{
    private LoggerService $logger;
    
    public function __construct(LoggerService $logger)
    {
        $this->logger = $logger;
    }
    
    /**
     * Dispatch event to all subscribed webhooks
     * 
     * @param string $eventType Event name (e.g., 'thread.created')
     * @param array $payload Event data
     */
    public function dispatch(string $eventType, array $payload): void
    {
        $this->logger->info("Dispatching webhook event: {$eventType}", [
            'event' => $eventType,
            'payload_size' => count($payload)
        ]);
        
        // Find active webhooks subscribed to this event
        $webhooks = Webhook::where('is_active', true)
            ->get()
            ->filter(fn($webhook) => $webhook->subscribesTo($eventType) && $webhook->isEnabled());
        
        if ($webhooks->isEmpty()) {
            $this->logger->debug("No webhooks subscribed to event: {$eventType}");
            return;
        }
        
        $this->logger->info("Found {$webhooks->count()} webhook(s) for event: {$eventType}");
        
        // Send to each webhook
        foreach ($webhooks as $webhook) {
            $this->sendWebhook($webhook, $eventType, $payload);
        }
    }
    
    /**
     * Send webhook HTTP request
     */
    private function sendWebhook(Webhook $webhook, string $eventType, array $payload): void
    {
        $this->logger->info("Sending webhook to: {$webhook->url}", [
            'webhook_id' => $webhook->id,
            'event' => $eventType
        ]);
        
        // Create delivery record
        $delivery = WebhookDelivery::create([
            'webhook_id' => $webhook->id,
            'event_type' => $eventType,
            'payload' => $payload,
            'attempts' => 1
        ]);
        
        try {
            // Generate HMAC signature
            $signature = $this->generateSignature($payload, $webhook->secret);
            
            // Prepare payload
            $jsonPayload = json_encode([
                'event' => $eventType,
                'data' => $payload,
                'timestamp' => time()
            ]);
            
            // Send HTTP POST request
            $ch = curl_init($webhook->url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $jsonPayload,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'X-Webhook-Signature: ' . $signature,
                    'X-Webhook-Event: ' . $eventType
                ],
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 5
            ]);
            
            $response = curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                throw new \Exception("cURL error: {$error}");
            }
            
            // Update delivery record
            $delivery->markDelivered($statusCode, $response);
            
            if ($delivery->isSuccessful()) {
                $this->logger->info("Webhook delivered successfully", [
                    'webhook_id' => $webhook->id,
                    'delivery_id' => $delivery->id,
                    'status' => $statusCode
                ]);
                
                $webhook->resetFailedAttempts();
                $webhook->markTriggered();
            } else {
                $this->logger->warning("Webhook delivery failed", [
                    'webhook_id' => $webhook->id,
                    'delivery_id' => $delivery->id,
                    'status' => $statusCode,
                    'response' => substr($response, 0, 200)
                ]);
                
                $webhook->incrementFailedAttempts();
            }
            
        } catch (\Exception $e) {
            $this->logger->error("Webhook delivery exception", [
                'webhook_id' => $webhook->id,
                'delivery_id' => $delivery->id,
                'error' => $e->getMessage()
            ]);
            
            $delivery->markDelivered(0, $e->getMessage());
            $webhook->incrementFailedAttempts();
        }
    }
    
    /**
     * Generate HMAC signature for webhook payload
     */
    private function generateSignature(array $payload, string $secret): string
    {
        $jsonPayload = json_encode($payload);
        return hash_hmac('sha256', $jsonPayload, $secret);
    }
    
    /**
     * Register new webhook
     * 
     * @param array $data Webhook data (url, events)
     * @return Webhook
     */
    public function register(array $data): Webhook
    {
        $this->logger->info("Registering new webhook", [
            'url' => $data['url'] ?? 'unknown',
            'events' => $data['events'] ?? []
        ]);
        
        // Generate secret
        $data['secret'] = bin2hex(random_bytes(32));
        $data['is_active'] = $data['is_active'] ?? true;
        $data['failed_attempts'] = 0;
        
        $webhook = Webhook::create($data);
        
        $this->logger->info("Webhook registered successfully", [
            'webhook_id' => $webhook->id,
            'url' => $webhook->url
        ]);
        
        return $webhook;
    }
    
    /**
     * Update existing webhook
     * 
     * @param int $id Webhook ID
     * @param array $data Update data
     * @return Webhook
     */
    public function update(int $id, array $data): Webhook
    {
        $webhook = Webhook::findOrFail($id);
        
        $this->logger->info("Updating webhook", [
            'webhook_id' => $id,
            'changes' => array_keys($data)
        ]);
        
        // Don't allow secret update via this method
        unset($data['secret']);
        
        $webhook->update($data);
        
        $this->logger->info("Webhook updated successfully", ['webhook_id' => $id]);
        
        return $webhook->fresh();
    }
    
    /**
     * Delete webhook
     * 
     * @param int $id Webhook ID
     */
    public function delete(int $id): void
    {
        $webhook = Webhook::findOrFail($id);
        
        $this->logger->info("Deleting webhook", [
            'webhook_id' => $id,
            'url' => $webhook->url
        ]);
        
        $webhook->delete();
        
        $this->logger->info("Webhook deleted successfully", ['webhook_id' => $id]);
    }
    
    /**
     * Retry failed webhook delivery
     * 
     * @param int $deliveryId Delivery ID
     */
    public function retry(int $deliveryId): void
    {
        $delivery = WebhookDelivery::with('webhook')->findOrFail($deliveryId);
        
        $this->logger->info("Retrying webhook delivery", [
            'delivery_id' => $deliveryId,
            'webhook_id' => $delivery->webhook_id,
            'attempts' => $delivery->attempts
        ]);
        
        if ($delivery->attempts >= 3) {
            $this->logger->warning("Max retry attempts reached", [
                'delivery_id' => $deliveryId
            ]);
            throw new \Exception("Maximum retry attempts (3) reached");
        }
        
        if (!$delivery->webhook) {
            $this->logger->error("Webhook not found for delivery", [
                'delivery_id' => $deliveryId
            ]);
            throw new \Exception("Webhook not found");
        }
        
        // Increment attempts
        $delivery->attempts++;
        $delivery->save();
        
        // Retry sending
        $this->sendWebhook(
            $delivery->webhook,
            $delivery->event_type,
            $delivery->payload
        );
    }
    
    /**
     * Get delivery history for webhook
     * 
     * @param int $webhookId Webhook ID
     * @param int $limit Maximum number of deliveries to return
     * @return Collection
     */
    public function getDeliveries(int $webhookId, int $limit = 50): Collection
    {
        return WebhookDelivery::where('webhook_id', $webhookId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Get all active webhooks
     * 
     * @return Collection
     */
    public function getActiveWebhooks(): Collection
    {
        return Webhook::where('is_active', true)->get();
    }
    
    /**
     * Get all webhooks (with pagination support)
     * 
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array ['data' => Collection, 'total' => int, 'page' => int]
     */
    public function getAllWebhooks(int $page = 1, int $perPage = 20): array
    {
        $query = Webhook::query();
        
        $total = $query->count();
        $data = $query->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();
        
        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($total / $perPage)
        ];
    }
}

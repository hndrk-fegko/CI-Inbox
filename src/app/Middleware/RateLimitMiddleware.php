<?php

declare(strict_types=1);

namespace CiInbox\App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Response as SlimResponse;

/**
 * Rate Limiting Middleware
 * 
 * Implements rate limiting to prevent abuse and DDoS attacks.
 * Uses file-based storage for simplicity (can be replaced with Redis).
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;
    private int $maxRequests;
    private int $windowSeconds;
    private string $storagePath;
    
    /**
     * @param LoggerInterface $logger
     * @param int $maxRequests Maximum requests per window (default: 100)
     * @param int $windowSeconds Time window in seconds (default: 60)
     * @param string|null $storagePath Path for rate limit data storage
     */
    public function __construct(
        LoggerInterface $logger,
        int $maxRequests = 100,
        int $windowSeconds = 60,
        ?string $storagePath = null
    ) {
        $this->logger = $logger;
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
        $this->storagePath = $storagePath ?? sys_get_temp_dir() . '/ci-inbox-rate-limits';
        
        // Ensure storage directory exists
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }
    
    /**
     * Process the request through the middleware
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $clientId = $this->getClientIdentifier($request);
        $rateLimitKey = $this->getRateLimitKey($clientId);
        
        // Get current request count
        $requestData = $this->getRequestData($rateLimitKey);
        $currentTime = time();
        
        // Check if we're in a new window
        if ($requestData['window_start'] < $currentTime - $this->windowSeconds) {
            // Reset for new window
            $requestData = [
                'window_start' => $currentTime,
                'request_count' => 0
            ];
        }
        
        // Increment request count
        $requestData['request_count']++;
        
        // Save updated data
        $this->saveRequestData($rateLimitKey, $requestData);
        
        // Check if rate limit exceeded
        if ($requestData['request_count'] > $this->maxRequests) {
            $this->logger->warning('Rate limit exceeded', [
                'client_id' => $clientId,
                'request_count' => $requestData['request_count'],
                'max_requests' => $this->maxRequests,
                'path' => $request->getUri()->getPath()
            ]);
            
            return $this->rateLimitExceededResponse($requestData);
        }
        
        // Add rate limit headers to response
        $response = $handler->handle($request);
        
        return $response
            ->withHeader('X-RateLimit-Limit', (string) $this->maxRequests)
            ->withHeader('X-RateLimit-Remaining', (string) max(0, $this->maxRequests - $requestData['request_count']))
            ->withHeader('X-RateLimit-Reset', (string) ($requestData['window_start'] + $this->windowSeconds));
    }
    
    /**
     * Get client identifier (IP address with optional session)
     */
    private function getClientIdentifier(Request $request): string
    {
        // Get IP address (handle proxies)
        $serverParams = $request->getServerParams();
        $ip = $serverParams['HTTP_X_FORWARDED_FOR'] ?? 
              $serverParams['HTTP_X_REAL_IP'] ?? 
              $serverParams['REMOTE_ADDR'] ?? 
              'unknown';
        
        // Take first IP if multiple (proxy chain)
        if (str_contains($ip, ',')) {
            $ip = trim(explode(',', $ip)[0]);
        }
        
        // Hash for privacy
        return hash('sha256', $ip . ($_SESSION['user_id'] ?? ''));
    }
    
    /**
     * Get rate limit key for storage
     */
    private function getRateLimitKey(string $clientId): string
    {
        return $this->storagePath . '/rate_' . substr($clientId, 0, 16);
    }
    
    /**
     * Get request data from storage
     */
    private function getRequestData(string $key): array
    {
        if (file_exists($key)) {
            $data = @file_get_contents($key);
            if ($data !== false) {
                $decoded = json_decode($data, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }
        
        return [
            'window_start' => time(),
            'request_count' => 0
        ];
    }
    
    /**
     * Save request data to storage
     */
    private function saveRequestData(string $key, array $data): void
    {
        @file_put_contents($key, json_encode($data), LOCK_EX);
    }
    
    /**
     * Generate rate limit exceeded response
     */
    private function rateLimitExceededResponse(array $requestData): Response
    {
        $response = new SlimResponse();
        $retryAfter = ($requestData['window_start'] + $this->windowSeconds) - time();
        
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => 'Too Many Requests',
            'message' => 'Rate limit exceeded. Please try again later.',
            'retry_after' => max(1, $retryAfter)
        ]));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Retry-After', (string) max(1, $retryAfter))
            ->withHeader('X-RateLimit-Limit', (string) $this->maxRequests)
            ->withHeader('X-RateLimit-Remaining', '0')
            ->withHeader('X-RateLimit-Reset', (string) ($requestData['window_start'] + $this->windowSeconds))
            ->withStatus(429);
    }
    
    /**
     * Clean up old rate limit files (should be called periodically)
     */
    public function cleanup(): int
    {
        $deleted = 0;
        $cutoff = time() - ($this->windowSeconds * 2);
        
        foreach (glob($this->storagePath . '/rate_*') as $file) {
            if (filemtime($file) < $cutoff) {
                @unlink($file);
                $deleted++;
            }
        }
        
        return $deleted;
    }
}

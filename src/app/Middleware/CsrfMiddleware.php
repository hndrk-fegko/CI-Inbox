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
 * CSRF Protection Middleware
 * 
 * Validates CSRF tokens for state-changing requests (POST, PUT, DELETE, PATCH).
 * Generates and manages CSRF tokens in session.
 */
class CsrfMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;
    private string $tokenName;
    private int $tokenLength;
    private array $exemptPaths;
    
    /**
     * @param LoggerInterface $logger
     * @param string $tokenName Name for the CSRF token field
     * @param int $tokenLength Length of generated tokens
     * @param array $exemptPaths Paths exempt from CSRF validation (e.g., webhooks)
     */
    public function __construct(
        LoggerInterface $logger,
        string $tokenName = 'csrf_token',
        int $tokenLength = 32,
        array $exemptPaths = []
    ) {
        $this->logger = $logger;
        $this->tokenName = $tokenName;
        $this->tokenLength = $tokenLength;
        $this->exemptPaths = array_merge([
            '/api/webhooks/poll-emails',  // External cron webhook
            '/webhooks/',                  // All webhooks
        ], $exemptPaths);
    }
    
    /**
     * Process the request through the middleware
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Generate token if not exists
        if (!isset($_SESSION[$this->tokenName])) {
            $_SESSION[$this->tokenName] = $this->generateToken();
        }
        
        // Check if this is a state-changing request
        $method = strtoupper($request->getMethod());
        $needsValidation = in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH']);
        
        // Skip validation for exempt paths
        $path = $request->getUri()->getPath();
        if ($needsValidation && $this->isExemptPath($path)) {
            $needsValidation = false;
        }
        
        // Validate CSRF token for state-changing requests
        if ($needsValidation && !$this->validateToken($request)) {
            $this->logger->warning('CSRF validation failed', [
                'method' => $method,
                'path' => $path,
                'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            return $this->csrfFailedResponse();
        }
        
        // Add token to request attributes for views
        $request = $request->withAttribute('csrf_token', $_SESSION[$this->tokenName]);
        $request = $request->withAttribute('csrf_token_name', $this->tokenName);
        
        // Process request
        $response = $handler->handle($request);
        
        // Add CSRF token to response headers (for SPA/JavaScript)
        return $response->withHeader('X-CSRF-Token', $_SESSION[$this->tokenName]);
    }
    
    /**
     * Generate a new CSRF token
     */
    private function generateToken(): string
    {
        return bin2hex(random_bytes($this->tokenLength));
    }
    
    /**
     * Validate CSRF token from request
     */
    private function validateToken(Request $request): bool
    {
        $sessionToken = $_SESSION[$this->tokenName] ?? null;
        
        if ($sessionToken === null) {
            return false;
        }
        
        // Check header first (for AJAX requests)
        $headerToken = $request->getHeaderLine('X-CSRF-Token');
        if (!empty($headerToken) && hash_equals($sessionToken, $headerToken)) {
            return true;
        }
        
        // Check form body
        $body = $request->getParsedBody();
        if (is_array($body) && isset($body[$this->tokenName])) {
            return hash_equals($sessionToken, $body[$this->tokenName]);
        }
        
        // Check query params (less secure, but sometimes needed)
        $params = $request->getQueryParams();
        if (isset($params[$this->tokenName])) {
            return hash_equals($sessionToken, $params[$this->tokenName]);
        }
        
        return false;
    }
    
    /**
     * Check if path is exempt from CSRF validation
     */
    private function isExemptPath(string $path): bool
    {
        foreach ($this->exemptPaths as $exemptPath) {
            if (str_starts_with($path, $exemptPath)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Generate CSRF failed response
     */
    private function csrfFailedResponse(): Response
    {
        $response = new SlimResponse();
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => 'CSRF validation failed',
            'message' => 'Invalid or missing CSRF token. Please refresh the page and try again.'
        ]));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(403);
    }
    
    /**
     * Get the current CSRF token (for use in views)
     */
    public static function getToken(): ?string
    {
        return $_SESSION['csrf_token'] ?? null;
    }
    
    /**
     * Get HTML hidden input field for forms
     */
    public static function getHiddenInput(): string
    {
        $token = self::getToken();
        if ($token === null) {
            return '';
        }
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    /**
     * Regenerate CSRF token (e.g., after login)
     */
    public static function regenerateToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }
}

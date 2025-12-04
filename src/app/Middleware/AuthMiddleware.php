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
 * Authentication Middleware
 * 
 * PSR-15 compliant middleware for session-based authentication.
 * Validates user session and sets user attributes on request.
 */
class AuthMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
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
        
        // Check if user is authenticated
        if (!$this->isAuthenticated()) {
            return $this->unauthorizedResponse();
        }
        
        // Get user data from session
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $userEmail = $_SESSION['user_email'] ?? null;
        $userRole = $_SESSION['user_role'] ?? 'user';
        
        if ($userId === 0 || $userEmail === null) {
            $this->logger->warning('Invalid session data', [
                'session_id' => session_id(),
                'has_user_id' => isset($_SESSION['user_id']),
                'has_user_email' => isset($_SESSION['user_email'])
            ]);
            return $this->unauthorizedResponse();
        }
        
        // Add user attributes to request
        $request = $request
            ->withAttribute('user_id', $userId)
            ->withAttribute('user_email', $userEmail)
            ->withAttribute('user_role', $userRole)
            ->withAttribute('user', [
                'id' => $userId,
                'email' => $userEmail,
                'role' => $userRole
            ]);
        
        $this->logger->debug('User authenticated', [
            'user_id' => $userId,
            'user_email' => $userEmail,
            'path' => $request->getUri()->getPath()
        ]);
        
        // Continue to next middleware/handler
        return $handler->handle($request);
    }
    
    /**
     * Check if the current session is authenticated
     */
    private function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']) && 
               isset($_SESSION['user_email']) && 
               !empty($_SESSION['user_id']);
    }
    
    /**
     * Generate unauthorized response
     */
    private function unauthorizedResponse(): Response
    {
        $response = new SlimResponse();
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => 'Unauthorized',
            'message' => 'Authentication required'
        ]));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    }
}

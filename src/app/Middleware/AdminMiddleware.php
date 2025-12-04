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
 * Admin Authorization Middleware
 * 
 * Ensures the authenticated user has admin privileges.
 * Must be used after AuthMiddleware in the middleware stack.
 */
class AdminMiddleware implements MiddlewareInterface
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
        // Get user role from request (set by AuthMiddleware)
        $userRole = $request->getAttribute('user_role', 'user');
        $userId = $request->getAttribute('user_id');
        
        if ($userRole !== 'admin') {
            $this->logger->warning('Admin access denied', [
                'user_id' => $userId,
                'user_role' => $userRole,
                'path' => $request->getUri()->getPath()
            ]);
            
            return $this->forbiddenResponse();
        }
        
        $this->logger->debug('Admin access granted', [
            'user_id' => $userId,
            'path' => $request->getUri()->getPath()
        ]);
        
        return $handler->handle($request);
    }
    
    /**
     * Generate forbidden response
     */
    private function forbiddenResponse(): Response
    {
        $response = new SlimResponse();
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => 'Forbidden',
            'message' => 'Admin privileges required'
        ]));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(403);
    }
}

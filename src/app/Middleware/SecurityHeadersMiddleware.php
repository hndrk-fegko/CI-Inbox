<?php

declare(strict_types=1);

namespace CiInbox\App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Security Headers Middleware
 * 
 * Adds security-related HTTP headers to all responses.
 * Essential for production deployments.
 */
class SecurityHeadersMiddleware implements MiddlewareInterface
{
    /**
     * @var array Custom header configuration
     */
    private array $config;
    
    /**
     * @param array $config Override default headers
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }
    
    /**
     * Process the request and add security headers to response
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $response = $handler->handle($request);
        
        // Add all configured security headers
        foreach ($this->config as $header => $value) {
            if ($value !== null && $value !== '') {
                $response = $response->withHeader($header, $value);
            }
        }
        
        return $response;
    }
    
    /**
     * Get default security header configuration
     */
    private function getDefaultConfig(): array
    {
        return [
            // Prevent clickjacking attacks
            'X-Frame-Options' => 'SAMEORIGIN',
            
            // Prevent MIME type sniffing
            'X-Content-Type-Options' => 'nosniff',
            
            // Enable XSS filtering (legacy, but still useful)
            'X-XSS-Protection' => '1; mode=block',
            
            // Referrer policy - don't leak paths to external sites
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            
            // Permissions policy - restrict features
            'Permissions-Policy' => 'geolocation=(), camera=(), microphone=()',
            
            // Content Security Policy
            // Adjust based on your needs (inline scripts, external CDNs, etc.)
            'Content-Security-Policy' => $this->getDefaultCSP(),
            
            // Strict Transport Security (HTTPS only)
            // Note: Only enable in production with proper HTTPS setup
            // 'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            
            // Prevent browser from caching sensitive responses
            // Note: Only for API responses, not for assets
            // 'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ];
    }
    
    /**
     * Build Content Security Policy header
     */
    private function getDefaultCSP(): string
    {
        $directives = [
            // Default: Only allow same-origin
            "default-src 'self'",
            
            // Scripts: Allow same-origin and inline (needed for PHP views)
            // In production, consider using nonces instead of 'unsafe-inline'
            "script-src 'self' 'unsafe-inline'",
            
            // Styles: Allow same-origin and inline styles
            "style-src 'self' 'unsafe-inline'",
            
            // Images: Allow same-origin and data URIs (for inline images)
            "img-src 'self' data:",
            
            // Fonts: Allow same-origin
            "font-src 'self'",
            
            // Connections: Allow same-origin (for API calls)
            "connect-src 'self'",
            
            // Media: Allow same-origin
            "media-src 'self'",
            
            // Objects (Flash, etc.): Disallow
            "object-src 'none'",
            
            // Frame ancestors: Only same-origin (prevents clickjacking)
            "frame-ancestors 'self'",
            
            // Base URI: Only same-origin
            "base-uri 'self'",
            
            // Form actions: Only same-origin
            "form-action 'self'",
        ];
        
        return implode('; ', $directives);
    }
    
    /**
     * Get header configuration (for testing/debugging)
     */
    public function getConfig(): array
    {
        return $this->config;
    }
    
    /**
     * Enable HSTS (Strict-Transport-Security)
     * Only call this in production with proper HTTPS setup
     * 
     * @param int $maxAge Max-age in seconds (default: 1 year)
     * @param bool $includeSubDomains Include subdomains
     * @param bool $preload Allow preload list inclusion
     */
    public function enableHSTS(int $maxAge = 31536000, bool $includeSubDomains = true, bool $preload = false): self
    {
        $value = "max-age={$maxAge}";
        
        if ($includeSubDomains) {
            $value .= '; includeSubDomains';
        }
        
        if ($preload) {
            $value .= '; preload';
        }
        
        $this->config['Strict-Transport-Security'] = $value;
        
        return $this;
    }
    
    /**
     * Disable caching for sensitive responses
     */
    public function disableCaching(): self
    {
        $this->config['Cache-Control'] = 'no-store, no-cache, must-revalidate, private';
        $this->config['Pragma'] = 'no-cache';
        $this->config['Expires'] = '0';
        
        return $this;
    }
}

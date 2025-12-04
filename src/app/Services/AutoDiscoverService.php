<?php
/**
 * Auto-Discovery Service
 * 
 * Automatically detects IMAP/SMTP configuration from email address
 * Based on setup-autodiscover.php logic
 */

declare(strict_types=1);

namespace CiInbox\App\Services;

use CiInbox\Modules\Imap\ImapClient;
use CiInbox\Modules\Logger\LoggerInterface;
use Exception;

class AutoDiscoverService
{
    public function __construct(
        private LoggerInterface $logger
    ) {}
    
    /**
     * Auto-detect IMAP configuration from email address
     * 
     * @param string $email Email address to extract domain from
     * @return array{success: bool, config?: array, error?: string}
     */
    public function discoverImap(string $email): array
    {
        $this->logger->info('Starting IMAP auto-discovery', ['email' => $email]);
        
        try {
            $domain = $this->extractDomain($email);
            $candidates = $this->getImapHostCandidates($domain);
            
            $this->logger->debug('Testing IMAP host candidates', [
                'domain' => $domain,
                'candidates' => $candidates
            ]);
            
            foreach ($candidates as $host) {
                // Test standard IMAP SSL port (993)
                $config = $this->testImapHost($host, 993, true);
                if ($config !== null) {
                    $this->logger->info('[SUCCESS] IMAP auto-discovery successful', [
                        'host' => $config['host'],
                        'port' => 993,
                        'original_candidate' => $host
                    ]);
                    
                    return [
                        'success' => true,
                        'config' => [
                            'host' => $config['host'], // Use actual hostname from test
                            'port' => 993,
                            'ssl' => true,
                            'inbox_folder' => 'INBOX',
                            'detected_folders' => $config['folders'] ?? []
                        ]
                    ];
                }
                
                // Fallback: try non-SSL port (143)
                $config = $this->testImapHost($host, 143, false);
                if ($config !== null) {
                    $this->logger->warning('IMAP auto-discovery successful (non-SSL)', [
                        'host' => $config['host'],
                        'port' => 143,
                        'original_candidate' => $host
                    ]);
                    
                    return [
                        'success' => true,
                        'config' => [
                            'host' => $config['host'], // Use actual hostname from test
                            'port' => 143,
                            'ssl' => false,
                            'inbox_folder' => 'INBOX',
                            'detected_folders' => $config['folders'] ?? []
                        ]
                    ];
                }
            }
            
            $this->logger->warning('IMAP auto-discovery failed', [
                'email' => $email,
                'tested_candidates' => $candidates
            ]);
            
            return [
                'success' => false,
                'error' => 'Could not detect IMAP configuration. Please enter settings manually.'
            ];
            
        } catch (Exception $e) {
            $this->logger->error('IMAP auto-discovery error', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Auto-discovery failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Auto-detect SMTP configuration from email address
     * 
     * @param string $email Email address to extract domain from
     * @return array{success: bool, config?: array, error?: string}
     */
    public function discoverSmtp(string $email): array
    {
        $this->logger->info('Starting SMTP auto-discovery', ['email' => $email]);
        
        try {
            $domain = $this->extractDomain($email);
            $candidates = $this->getSmtpHostCandidates($domain);
            
            $this->logger->debug('Testing SMTP host candidates', [
                'domain' => $domain,
                'candidates' => $candidates
            ]);
            
            foreach ($candidates as $host) {
                // Test submission port with SSL (465)
                $config = $this->testSmtpHost($host, 465, true);
                if ($config !== null) {
                    $this->logger->info('[SUCCESS] SMTP auto-discovery successful', [
                        'host' => $config['host'],
                        'port' => 465,
                        'original_candidate' => $host
                    ]);
                    
                    return [
                        'success' => true,
                        'config' => [
                            'host' => $config['host'], // Use actual hostname from test (may differ due to cert)
                            'port' => 465,
                            'ssl' => true,
                            'auth' => true
                        ]
                    ];
                }
                
                // Try STARTTLS port (587)
                $config = $this->testSmtpHost($host, 587, false);
                if ($config !== null) {
                    // Extract real hostname from SMTP greeting if different
                    $detectedHost = $config['detected_host'] ?? $config['host'];
                    
                    if ($detectedHost !== $host) {
                        $this->logger->info('Hostname mismatch detected in SMTP greeting (port 587)', [
                            'requested' => $host,
                            'detected' => $detectedHost
                        ]);
                    }
                    
                    $this->logger->info('[SUCCESS] SMTP auto-discovery successful (STARTTLS)', [
                        'host' => $detectedHost,
                        'port' => 587,
                        'original_candidate' => $host,
                        'greeting_checked' => true
                    ]);
                    
                    return [
                        'success' => true,
                        'config' => [
                            'host' => $detectedHost, // Use detected hostname from greeting
                            'port' => 587,
                            'ssl' => false,
                            'auth' => true
                        ]
                    ];
                }
            }
            
            $this->logger->warning('SMTP auto-discovery failed', [
                'email' => $email,
                'tested_candidates' => $candidates
            ]);
            
            return [
                'success' => false,
                'error' => 'Could not detect SMTP configuration. Please enter settings manually.'
            ];
            
        } catch (Exception $e) {
            $this->logger->error('SMTP auto-discovery error', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Auto-discovery failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Extract domain from email address
     */
    private function extractDomain(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            throw new Exception('Invalid email address format');
        }
        return $parts[1];
    }
    
    /**
     * Get IMAP host candidates for domain
     * 
     * @return string[]
     */
    private function getImapHostCandidates(string $domain): array
    {
        return [
            "imap.{$domain}",
            "mail.{$domain}",
            $domain
        ];
    }
    
    /**
     * Get SMTP host candidates for domain
     * 
     * @return string[]
     */
    private function getSmtpHostCandidates(string $domain): array
    {
        return [
            "smtp.{$domain}",
            "mail.{$domain}",
            $domain
        ];
    }
    
    /**
     * Test IMAP host connectivity
     * 
     * @return array|null Returns config array on success, null on failure
     */
    private function testImapHost(string $host, int $port, bool $ssl): ?array
    {
        $protocol = $ssl ? 'ssl' : 'tcp';
        $errno = 0;
        $errstr = '';
        
        // For SSL connections, use stream_socket_client to capture certificate info
        if ($ssl) {
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'capture_peer_cert' => true,
                    'allow_self_signed' => false
                ]
            ]);
            
            $socket = @stream_socket_client(
                "{$protocol}://{$host}:{$port}",
                $errno,
                $errstr,
                3,
                STREAM_CLIENT_CONNECT,
                $context
            );
            
            // If connection failed due to certificate mismatch, try to extract real hostname
            if (!$socket && (str_contains($errstr, 'certificate') || str_contains($errstr, 'CN='))) {
                $realHost = $this->extractRealHostFromCertificate($host, $port);
                
                if ($realHost && $realHost !== $host) {
                    $this->logger->info('Certificate hostname mismatch detected (IMAP)', [
                        'requested' => $host,
                        'certificate' => $realHost
                    ]);
                    
                    // Retry with real hostname from certificate
                    return $this->testImapHost($realHost, $port, $ssl);
                }
            }
        } else {
            // Non-SSL: use simple fsockopen
            $socket = @fsockopen("{$protocol}://{$host}", $port, $errno, $errstr, 3);
        }
        
        if (!$socket) {
            return null;
        }
        
        // Read IMAP greeting
        $greeting = fgets($socket);
        fclose($socket);
        
        // Check for IMAP greeting (* OK)
        if ($greeting && str_starts_with($greeting, '* OK')) {
            return [
                'host' => $host,
                'port' => $port,
                'ssl' => $ssl
            ];
        }
        
        return null;
    }
    
    /**
     * Test SMTP host connectivity
     * 
     * @return array|null Returns config array on success, null on failure
     */
    private function testSmtpHost(string $host, int $port, bool $ssl): ?array
    {
        $this->logger->debug('Testing SMTP host', [
            'host' => $host,
            'port' => $port,
            'ssl' => $ssl
        ]);
        
        $protocol = $ssl ? 'ssl' : 'tcp';
        $errno = 0;
        $errstr = '';
        
        // For SSL connections, use stream_socket_client to capture certificate info
        if ($ssl) {
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'capture_peer_cert' => true,
                    'allow_self_signed' => false
                ]
            ]);
            
            $socket = @stream_socket_client(
                "{$protocol}://{$host}:{$port}",
                $errno,
                $errstr,
                3,
                STREAM_CLIENT_CONNECT,
                $context
            );
            
            // If connection failed due to certificate mismatch, try to extract real hostname
            if (!$socket && (str_contains($errstr, 'certificate') || str_contains($errstr, 'CN='))) {
                $realHost = $this->extractRealHostFromCertificate($host, $port);
                
                if ($realHost && $realHost !== $host) {
                    $this->logger->info('Certificate hostname mismatch detected', [
                        'requested' => $host,
                        'certificate' => $realHost
                    ]);
                    
                    // Retry with real hostname from certificate
                    return $this->testSmtpHost($realHost, $port, $ssl);
                }
            }
        } else {
            // Non-SSL: use simple fsockopen
            $socket = @fsockopen("{$protocol}://{$host}", $port, $errno, $errstr, 3);
        }
        
        if (!$socket) {
            $this->logger->debug('SMTP connection failed', [
                'host' => $host,
                'port' => $port,
                'errno' => $errno,
                'error' => $errstr
            ]);
            return null;
        }
        
        // Read SMTP greeting
        $greeting = fgets($socket);
        fclose($socket);
        
        $this->logger->debug('SMTP greeting received', [
            'host' => $host,
            'port' => $port,
            'greeting' => trim($greeting)
        ]);
        
        // Check for SMTP greeting (220)
        if ($greeting && str_starts_with($greeting, '220')) {
            $this->logger->debug('Valid SMTP greeting (220)', ['host' => $host, 'port' => $port]);
            
            // Try to extract actual hostname from greeting banner
            // Example: "220 psa22.webhoster.ag ESMTP Postfix"
            $detectedHost = null;
            if (preg_match('/^220\s+([^\s]+)\s+/', $greeting, $matches)) {
                $detectedHost = $matches[1];
                if ($detectedHost !== $host) {
                    $this->logger->debug('Detected different hostname in SMTP banner', [
                        'requested' => $host,
                        'detected' => $detectedHost
                    ]);
                }
            }
            
            return [
                'host' => $host,
                'port' => $port,
                'ssl' => $ssl,
                'detected_host' => $detectedHost ?? $host
            ];
        }
        
        $this->logger->debug('Invalid SMTP greeting', [
            'host' => $host,
            'port' => $port,
            'greeting' => $greeting
        ]);
        
        return null;
    }
    
    /**
     * Extract real hostname from SSL certificate
     * 
     * @param string $host Hostname to connect to
     * @param int $port Port to connect to
     * @return string|null Real hostname from certificate CN, or null if extraction failed
     */
    private function extractRealHostFromCertificate(string $host, int $port): ?string
    {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'capture_peer_cert' => true
            ]
        ]);
        
        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client(
            "ssl://{$host}:{$port}",
            $errno,
            $errstr,
            3,
            STREAM_CLIENT_CONNECT,
            $context
        );
        
        if ($socket) {
            $params = stream_context_get_params($socket);
            fclose($socket);
            
            if (isset($params['options']['ssl']['peer_certificate'])) {
                $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
                
                // Extract CN from subject
                if (isset($cert['subject']['CN'])) {
                    $this->logger->debug('Extracted hostname from certificate', [
                        'original_host' => $host,
                        'cert_cn' => $cert['subject']['CN']
                    ]);
                    return $cert['subject']['CN'];
                }
            }
        }
        
        return null;
    }
}

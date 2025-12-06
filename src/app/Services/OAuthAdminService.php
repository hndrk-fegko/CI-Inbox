<?php
/**
 * OAuth Admin Service
 * 
 * Handles OAuth2/SSO configuration management for admin interface.
 * Manages provider credentials, settings, and user OAuth connections.
 */

declare(strict_types=1);

namespace CiInbox\App\Services;

use CiInbox\Modules\Logger\LoggerInterface;
use CiInbox\App\Repositories\SystemSettingRepository;

class OAuthAdminService
{
    private string $configDir;
    
    // Supported OAuth providers
    private const PROVIDERS = [
        'google' => [
            'name' => 'Google',
            'auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token_url' => 'https://oauth2.googleapis.com/token',
            'scope' => 'openid email profile'
        ],
        'microsoft' => [
            'name' => 'Microsoft / Azure AD',
            'auth_url' => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
            'token_url' => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
            'scope' => 'openid email profile'
        ],
        'github' => [
            'name' => 'GitHub',
            'auth_url' => 'https://github.com/login/oauth/authorize',
            'token_url' => 'https://github.com/login/oauth/access_token',
            'scope' => 'user:email read:user'
        ],
        'custom' => [
            'name' => 'Custom OIDC',
            'auth_url' => '',
            'token_url' => '',
            'scope' => 'openid email profile'
        ]
    ];
    
    public function __construct(
        private LoggerInterface $logger,
        private ?SystemSettingRepository $settingsRepository = null
    ) {
        $this->configDir = __DIR__ . '/../../../data';
        
        if (!is_dir($this->configDir)) {
            mkdir($this->configDir, 0755, true);
        }
    }
    
    /**
     * Get OAuth configuration (global + providers)
     * 
     * @return array Configuration array
     */
    public function getConfig(): array
    {
        try {
            $configFile = $this->configDir . '/oauth-config.json';
            
            if (file_exists($configFile)) {
                $config = json_decode(file_get_contents($configFile), true);
                if ($config) {
                    // Mask sensitive data
                    foreach ($config['providers'] ?? [] as $key => $provider) {
                        if (!empty($provider['client_secret'])) {
                            $config['providers'][$key]['client_secret'] = '********';
                        }
                    }
                    return $config;
                }
            }
            
            // Return default configuration
            return [
                'enabled' => false,
                'allow_registration' => false,
                'default_role' => 'user',
                'providers' => [
                    'google' => [
                        'enabled' => false,
                        'client_id' => '',
                        'client_secret' => '',
                        'redirect_uri' => $this->getDefaultRedirectUri('google')
                    ],
                    'microsoft' => [
                        'enabled' => false,
                        'client_id' => '',
                        'client_secret' => '',
                        'redirect_uri' => $this->getDefaultRedirectUri('microsoft'),
                        'tenant_id' => 'common'
                    ],
                    'github' => [
                        'enabled' => false,
                        'client_id' => '',
                        'client_secret' => '',
                        'redirect_uri' => $this->getDefaultRedirectUri('github')
                    ],
                    'custom' => [
                        'enabled' => false,
                        'name' => 'Custom OIDC Provider',
                        'client_id' => '',
                        'client_secret' => '',
                        'redirect_uri' => $this->getDefaultRedirectUri('custom'),
                        'auth_url' => '',
                        'token_url' => '',
                        'userinfo_url' => '',
                        'scope' => 'openid email profile'
                    ]
                ],
                'supported_providers' => self::PROVIDERS
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('[OAuthAdmin] Failed to get config', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'enabled' => false,
                'providers' => [],
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update OAuth global settings
     * 
     * @param array $settings Global settings
     * @return array Updated configuration
     */
    public function updateGlobalSettings(array $settings): array
    {
        try {
            $config = $this->getFullConfig();
            
            $config['enabled'] = $settings['enabled'] ?? false;
            $config['allow_registration'] = $settings['allow_registration'] ?? false;
            $config['default_role'] = $settings['default_role'] ?? 'user';
            
            $this->saveConfig($config);
            
            $this->logger->info('[OAuthAdmin] Global settings updated', [
                'enabled' => $config['enabled']
            ]);
            
            return $this->getConfig();
            
        } catch (\Exception $e) {
            $this->logger->error('[OAuthAdmin] Failed to update global settings', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Update provider configuration
     * 
     * @param string $provider Provider key (google, microsoft, github, custom)
     * @param array $providerConfig Provider configuration
     * @return array Updated configuration
     */
    public function updateProvider(string $provider, array $providerConfig): array
    {
        try {
            // Validate provider is in whitelist
            $validProviders = array_keys(self::PROVIDERS);
            if (!in_array($provider, $validProviders, true)) {
                throw new \Exception("Invalid provider. Must be one of: " . implode(', ', $validProviders));
            }
            
            $config = $this->getFullConfig();
            
            // Initialize provider config if not exists
            if (!isset($config['providers'][$provider])) {
                $config['providers'][$provider] = [];
            }
            
            // Update provider settings
            $config['providers'][$provider]['enabled'] = $providerConfig['enabled'] ?? false;
            $config['providers'][$provider]['client_id'] = $providerConfig['client_id'] ?? '';
            
            // Only update secret if new value provided
            if (!empty($providerConfig['client_secret']) && $providerConfig['client_secret'] !== '********') {
                $config['providers'][$provider]['client_secret'] = $providerConfig['client_secret'];
            }
            
            $config['providers'][$provider]['redirect_uri'] = $providerConfig['redirect_uri'] 
                ?? $this->getDefaultRedirectUri($provider);
            
            // Provider-specific fields
            if ($provider === 'microsoft') {
                $config['providers'][$provider]['tenant_id'] = $providerConfig['tenant_id'] ?? 'common';
            }
            
            if ($provider === 'custom') {
                $config['providers'][$provider]['name'] = $providerConfig['name'] ?? 'Custom OIDC';
                $config['providers'][$provider]['auth_url'] = $providerConfig['auth_url'] ?? '';
                $config['providers'][$provider]['token_url'] = $providerConfig['token_url'] ?? '';
                $config['providers'][$provider]['userinfo_url'] = $providerConfig['userinfo_url'] ?? '';
                $config['providers'][$provider]['scope'] = $providerConfig['scope'] ?? 'openid email profile';
            }
            
            $this->saveConfig($config);
            
            $this->logger->info('[OAuthAdmin] Provider updated', [
                'provider' => $provider,
                'enabled' => $config['providers'][$provider]['enabled']
            ]);
            
            return $this->getConfig();
            
        } catch (\Exception $e) {
            $this->logger->error('[OAuthAdmin] Failed to update provider', [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Get OAuth statistics
     * 
     * @return array Statistics
     */
    public function getStats(): array
    {
        try {
            $config = $this->getConfig();
            
            $enabledProviders = 0;
            foreach ($config['providers'] ?? [] as $provider) {
                if (!empty($provider['enabled'])) {
                    $enabledProviders++;
                }
            }
            
            // Count users with OAuth connections (would query database in production)
            $oauthUsers = 0;
            
            return [
                'enabled' => $config['enabled'] ?? false,
                'active_providers' => $enabledProviders,
                'oauth_users' => $oauthUsers,
                'allow_registration' => $config['allow_registration'] ?? false
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('[OAuthAdmin] Failed to get stats', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'enabled' => false,
                'active_providers' => 0,
                'oauth_users' => 0
            ];
        }
    }
    
    /**
     * Get users with OAuth connections
     * 
     * @return array List of users with OAuth info
     */
    public function getOAuthUsers(): array
    {
        try {
            // In production, this would query the database for users with OAuth links
            // For now, return empty array
            return [];
            
        } catch (\Exception $e) {
            $this->logger->error('[OAuthAdmin] Failed to get OAuth users', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Get default redirect URI for a provider
     * 
     * @param string $provider Provider key
     * @return string Redirect URI
     */
    private function getDefaultRedirectUri(string $provider): string
    {
        $baseUrl = getenv('APP_URL') ?: 'http://localhost';
        return rtrim($baseUrl, '/') . '/auth/oauth/callback/' . $provider;
    }
    
    /**
     * Get full configuration (including secrets)
     * 
     * @return array Full configuration
     */
    private function getFullConfig(): array
    {
        $configFile = $this->configDir . '/oauth-config.json';
        
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            if ($config) {
                return $config;
            }
        }
        
        return [
            'enabled' => false,
            'allow_registration' => false,
            'default_role' => 'user',
            'providers' => []
        ];
    }
    
    /**
     * Save configuration to file
     * 
     * @param array $config Configuration array
     */
    private function saveConfig(array $config): void
    {
        $configFile = $this->configDir . '/oauth-config.json';
        
        if (!is_dir($this->configDir)) {
            mkdir($this->configDir, 0755, true);
        }
        
        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    }
}

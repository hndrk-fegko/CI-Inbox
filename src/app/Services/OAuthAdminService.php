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
     * Find existing user by email address
     * 
     * When a user authenticates via OAuth, we check if their email already exists
     * in our user database. If so, we link the OAuth account to the existing user
     * instead of creating a new account.
     * 
     * @param string $email Email address from OAuth provider
     * @return array|null User data if found, null otherwise
     */
    public function findExistingUserByEmail(string $email): ?array
    {
        try {
            // Get PDO connection from config or environment
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                getenv('DB_HOST') ?: 'localhost',
                getenv('DB_NAME') ?: 'ci_inbox'
            );
            
            $pdo = new \PDO(
                $dsn,
                getenv('DB_USER') ?: 'root',
                getenv('DB_PASS') ?: '',
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
            
            $stmt = $pdo->prepare('SELECT id, email, name, role, is_active FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($user) {
                $this->logger->info('[OAuthAdmin] Found existing user for OAuth email', [
                    'email' => $email,
                    'user_id' => $user['id']
                ]);
                return $user;
            }
            
            return null;
            
        } catch (\Exception $e) {
            $this->logger->error('[OAuthAdmin] Failed to find user by email', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Link OAuth account to existing user
     * 
     * @param int $userId Existing user ID
     * @param string $provider OAuth provider (google, microsoft, github, custom)
     * @param string $providerUserId User ID from the OAuth provider
     * @param array $providerData Additional data from OAuth provider
     * @return bool Success status
     */
    public function linkOAuthToUser(int $userId, string $provider, string $providerUserId, array $providerData = []): bool
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                getenv('DB_HOST') ?: 'localhost',
                getenv('DB_NAME') ?: 'ci_inbox'
            );
            
            $pdo = new \PDO(
                $dsn,
                getenv('DB_USER') ?: 'root',
                getenv('DB_PASS') ?: '',
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
            
            // Check if link already exists
            $stmt = $pdo->prepare('SELECT id FROM user_oauth WHERE user_id = ? AND provider = ?');
            $stmt->execute([$userId, $provider]);
            
            if ($stmt->fetch()) {
                // Update existing link
                $stmt = $pdo->prepare(
                    'UPDATE user_oauth SET provider_user_id = ?, provider_data = ?, updated_at = NOW() WHERE user_id = ? AND provider = ?'
                );
                $stmt->execute([
                    $providerUserId,
                    json_encode($providerData),
                    $userId,
                    $provider
                ]);
            } else {
                // Create new link
                $stmt = $pdo->prepare(
                    'INSERT INTO user_oauth (user_id, provider, provider_user_id, provider_data, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())'
                );
                $stmt->execute([
                    $userId,
                    $provider,
                    $providerUserId,
                    json_encode($providerData)
                ]);
            }
            
            $this->logger->info('[OAuthAdmin] Linked OAuth account to user', [
                'user_id' => $userId,
                'provider' => $provider
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('[OAuthAdmin] Failed to link OAuth to user', [
                'user_id' => $userId,
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Process OAuth callback - handles user lookup/creation and session
     * 
     * This is the main method called when a user completes OAuth authentication.
     * It checks if a user with the OAuth email already exists, and either:
     * 1. Links the OAuth account to the existing user
     * 2. Creates a new user (if auto-registration is enabled)
     * 3. Rejects the login (if auto-registration is disabled and user doesn't exist)
     * 
     * @param string $provider OAuth provider name
     * @param array $oauthUser User data from OAuth provider (email, name, id, etc.)
     * @return array Result with success status and user data or error
     */
    public function processOAuthCallback(string $provider, array $oauthUser): array
    {
        try {
            $email = $oauthUser['email'] ?? null;
            $providerUserId = $oauthUser['id'] ?? $oauthUser['sub'] ?? null;
            
            if (!$email) {
                return [
                    'success' => false,
                    'error' => 'OAuth provider did not return email address'
                ];
            }
            
            // Check if user already exists
            $existingUser = $this->findExistingUserByEmail($email);
            
            if ($existingUser) {
                // User exists - link OAuth account and return user
                if ($providerUserId) {
                    $this->linkOAuthToUser(
                        (int)$existingUser['id'],
                        $provider,
                        $providerUserId,
                        $oauthUser
                    );
                }
                
                $this->logger->info('[OAuthAdmin] OAuth login linked to existing user', [
                    'email' => $email,
                    'provider' => $provider,
                    'user_id' => $existingUser['id']
                ]);
                
                return [
                    'success' => true,
                    'user' => $existingUser,
                    'is_new' => false
                ];
            }
            
            // User doesn't exist - check if auto-registration is allowed
            $config = $this->getConfig();
            
            if (!($config['allow_registration'] ?? false)) {
                $this->logger->warning('[OAuthAdmin] OAuth login rejected - user not found and auto-registration disabled', [
                    'email' => $email,
                    'provider' => $provider
                ]);
                
                return [
                    'success' => false,
                    'error' => 'User not found. Please contact an administrator to create your account.'
                ];
            }
            
            // Create new user
            $newUser = $this->createUserFromOAuth($provider, $oauthUser, $config['default_role'] ?? 'user');
            
            if ($newUser) {
                return [
                    'success' => true,
                    'user' => $newUser,
                    'is_new' => true
                ];
            }
            
            return [
                'success' => false,
                'error' => 'Failed to create user account'
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('[OAuthAdmin] OAuth callback processing failed', [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Authentication failed. Please try again.'
            ];
        }
    }
    
    /**
     * Create new user from OAuth data
     * 
     * @param string $provider OAuth provider
     * @param array $oauthUser User data from provider
     * @param string $role Default role for new user
     * @return array|null Created user data or null on failure
     */
    private function createUserFromOAuth(string $provider, array $oauthUser, string $role = 'user'): ?array
    {
        try {
            $email = $oauthUser['email'];
            $name = $oauthUser['name'] ?? $oauthUser['displayName'] ?? explode('@', $email)[0];
            $providerUserId = $oauthUser['id'] ?? $oauthUser['sub'] ?? '';
            
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                getenv('DB_HOST') ?: 'localhost',
                getenv('DB_NAME') ?: 'ci_inbox'
            );
            
            $pdo = new \PDO(
                $dsn,
                getenv('DB_USER') ?: 'root',
                getenv('DB_PASS') ?: '',
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
            
            // Create user (no password - OAuth only)
            $stmt = $pdo->prepare(
                'INSERT INTO users (email, name, role, is_active, created_at, updated_at) VALUES (?, ?, ?, 1, NOW(), NOW())'
            );
            $stmt->execute([$email, $name, $role]);
            
            $userId = (int)$pdo->lastInsertId();
            
            // Link OAuth account
            if ($providerUserId) {
                $this->linkOAuthToUser($userId, $provider, $providerUserId, $oauthUser);
            }
            
            $this->logger->info('[OAuthAdmin] Created new user from OAuth', [
                'user_id' => $userId,
                'email' => $email,
                'provider' => $provider,
                'role' => $role
            ]);
            
            return [
                'id' => $userId,
                'email' => $email,
                'name' => $name,
                'role' => $role,
                'is_active' => true
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('[OAuthAdmin] Failed to create user from OAuth', [
                'email' => $oauthUser['email'] ?? 'unknown',
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
            return null;
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

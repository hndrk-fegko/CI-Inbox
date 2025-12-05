<?php

declare(strict_types=1);

namespace CiInbox\App\Services;

use Carbon\Carbon;
use CiInbox\App\Models\OAuthProvider;
use CiInbox\App\Models\User;
use CiInbox\Modules\Logger\LoggerService;
use CiInbox\Modules\Encryption\EncryptionInterface;

/**
 * OAuth Service
 * 
 * Handles OAuth authentication with custom providers.
 * Supports any OAuth 2.0 compatible provider (ChurchTools, Google, Microsoft, etc.)
 */
class OAuthService
{
    private LoggerService $logger;
    private EncryptionInterface $encryption;

    public function __construct(
        LoggerService $logger,
        EncryptionInterface $encryption
    ) {
        $this->logger = $logger;
        $this->encryption = $encryption;
    }

    /**
     * Get all active OAuth providers
     * 
     * @return array Array of provider data for display
     */
    public function getActiveProviders(): array
    {
        $providers = OAuthProvider::getActive();
        
        return $providers->map(function ($provider) {
            return [
                'name' => $provider->name,
                'display_name' => $provider->display_name,
                'icon' => $provider->icon,
                'button_color' => $provider->button_color,
            ];
        })->toArray();
    }

    /**
     * Initialize OAuth flow
     * 
     * @param string $providerName Provider identifier
     * @param string $redirectUri Callback URL
     * @return array Authorization URL and state
     */
    public function initializeAuth(string $providerName, string $redirectUri): array
    {
        $provider = OAuthProvider::findByName($providerName);
        
        if (!$provider) {
            throw new \Exception("OAuth provider not found: {$providerName}");
        }

        // Generate CSRF state token
        $state = bin2hex(random_bytes(16));
        
        // Store state in session for verification
        $_SESSION['oauth_state'] = $state;
        $_SESSION['oauth_provider'] = $providerName;

        $authUrl = $provider->buildAuthorizationUrl($redirectUri, $state);

        $this->logger->info('OAuth flow initialized', [
            'provider' => $providerName,
            'redirect_uri' => $redirectUri
        ]);

        return [
            'authorization_url' => $authUrl,
            'state' => $state
        ];
    }

    /**
     * Handle OAuth callback
     * 
     * @param string $providerName Provider identifier
     * @param string $code Authorization code
     * @param string $state State token for CSRF verification
     * @param string $redirectUri Callback URL (must match init)
     * @return User Authenticated user
     */
    public function handleCallback(
        string $providerName,
        string $code,
        string $state,
        string $redirectUri
    ): User {
        // Verify state token
        if (!isset($_SESSION['oauth_state']) || !hash_equals($_SESSION['oauth_state'], $state)) {
            throw new \Exception('Invalid OAuth state - possible CSRF attack');
        }

        // Verify provider matches
        if (!isset($_SESSION['oauth_provider']) || $_SESSION['oauth_provider'] !== $providerName) {
            throw new \Exception('OAuth provider mismatch');
        }

        // Clear session state
        unset($_SESSION['oauth_state'], $_SESSION['oauth_provider']);

        $provider = OAuthProvider::findByName($providerName);
        if (!$provider) {
            throw new \Exception("OAuth provider not found: {$providerName}");
        }

        // Exchange code for tokens
        $tokens = $this->exchangeCodeForTokens($provider, $code, $redirectUri);

        // Get user info from provider
        $userInfo = $this->getUserInfo($provider, $tokens['access_token']);

        // Find or create user
        $user = User::findOrCreateFromOAuth($providerName, [
            'id' => $userInfo['id'],
            'email' => $userInfo['email'],
            'name' => $userInfo['name'] ?? null,
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'] ?? null,
            'expires_at' => isset($tokens['expires_in']) 
                ? Carbon::now()->addSeconds($tokens['expires_in']) 
                : null,
        ]);

        $this->logger->info('OAuth authentication successful', [
            'provider' => $providerName,
            'user_id' => $user->id,
            'email' => $user->email
        ]);

        return $user;
    }

    /**
     * Exchange authorization code for tokens
     */
    private function exchangeCodeForTokens(OAuthProvider $provider, string $code, string $redirectUri): array
    {
        $postData = [
            'grant_type' => 'authorization_code',
            'client_id' => $provider->client_id,
            'client_secret' => $this->decryptSecret($provider->client_secret),
            'code' => $code,
            'redirect_uri' => $redirectUri,
        ];

        $ch = curl_init($provider->token_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->logger->error('OAuth token exchange failed', ['error' => $error]);
            throw new \Exception('Failed to connect to OAuth provider');
        }

        $data = json_decode($response, true);

        if ($statusCode !== 200 || isset($data['error'])) {
            $errorMsg = $data['error_description'] ?? $data['error'] ?? 'Unknown error';
            $this->logger->error('OAuth token exchange error', [
                'status' => $statusCode,
                'error' => $errorMsg
            ]);
            throw new \Exception("OAuth token error: {$errorMsg}");
        }

        return $data;
    }

    /**
     * Get user info from OAuth provider
     */
    private function getUserInfo(OAuthProvider $provider, string $accessToken): array
    {
        if (empty($provider->userinfo_url)) {
            // Some providers include user info in the token response
            throw new \Exception('Provider does not have a userinfo URL configured');
        }

        $ch = curl_init($provider->userinfo_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->logger->error('OAuth userinfo request failed', ['error' => $error]);
            throw new \Exception('Failed to get user info from OAuth provider');
        }

        $data = json_decode($response, true);

        if ($statusCode !== 200) {
            $this->logger->error('OAuth userinfo error', [
                'status' => $statusCode,
                'response' => substr($response, 0, 200)
            ]);
            throw new \Exception('Failed to get user info from OAuth provider');
        }

        // Normalize user data (different providers use different field names)
        return $this->normalizeUserInfo($data);
    }

    /**
     * Normalize user info from different providers
     */
    private function normalizeUserInfo(array $data): array
    {
        return [
            'id' => $data['id'] ?? $data['sub'] ?? $data['user_id'] ?? null,
            'email' => $data['email'] ?? $data['mail'] ?? null,
            'name' => $data['name'] ?? $data['displayName'] ?? $data['display_name'] ?? null,
        ];
    }

    /**
     * Decrypt provider client secret
     */
    private function decryptSecret(string $encrypted): string
    {
        try {
            return $this->encryption->decrypt($encrypted);
        } catch (\Exception $e) {
            // Secret might not be encrypted (legacy or plain text)
            return $encrypted;
        }
    }

    /**
     * Create or update OAuth provider
     */
    public function saveProvider(array $data): OAuthProvider
    {
        // Encrypt client secret
        if (isset($data['client_secret']) && !empty($data['client_secret'])) {
            $data['client_secret'] = $this->encryption->encrypt($data['client_secret']);
        }

        if (isset($data['id'])) {
            $provider = OAuthProvider::findOrFail($data['id']);
            
            // Don't update secret if not provided
            if (empty($data['client_secret'])) {
                unset($data['client_secret']);
            }
            
            $provider->update($data);
            $this->logger->info('OAuth provider updated', ['name' => $provider->name]);
        } else {
            $provider = OAuthProvider::create($data);
            $this->logger->info('OAuth provider created', ['name' => $provider->name]);
        }

        return $provider;
    }

    /**
     * Delete OAuth provider
     */
    public function deleteProvider(int $id): void
    {
        $provider = OAuthProvider::findOrFail($id);
        $name = $provider->name;
        $provider->delete();
        
        $this->logger->info('OAuth provider deleted', ['name' => $name]);
    }
}

<?php

namespace CiInbox\App\Models;

/**
 * OAuthProvider Model
 * 
 * Represents configurable OAuth providers for authentication.
 * Supports custom providers like ChurchTools, Google, Microsoft, etc.
 */
class OAuthProvider extends BaseModel
{
    protected $table = 'oauth_providers';

    protected $fillable = [
        'name',
        'display_name',
        'client_id',
        'client_secret',
        'authorize_url',
        'token_url',
        'userinfo_url',
        'scopes',
        'icon',
        'button_color',
        'is_active',
        'sort_order',
    ];

    protected $hidden = [
        'client_secret',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get all active providers ordered by sort_order
     */
    public static function getActive()
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get provider by name
     */
    public static function findByName(string $name): ?self
    {
        return static::where('name', $name)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get scopes as array
     */
    public function getScopesArray(): array
    {
        if (empty($this->scopes)) {
            return [];
        }
        return array_map('trim', explode(',', $this->scopes));
    }

    /**
     * Build authorization URL with parameters
     */
    public function buildAuthorizationUrl(string $redirectUri, string $state): string
    {
        $params = [
            'client_id' => $this->client_id,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'state' => $state,
        ];

        if (!empty($this->scopes)) {
            $params['scope'] = $this->scopes;
        }

        return $this->authorize_url . '?' . http_build_query($params);
    }
}

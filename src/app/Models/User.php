<?php

namespace CiInbox\App\Models;

use Carbon\Carbon;

/**
 * User Model
 * 
 * Represents application users (admins and agents).
 * Supports both password-based and OAuth authentication.
 */
class User extends BaseModel
{
    protected $table = 'users';

    protected $fillable = [
        'email',
        'password_hash',
        'name',
        'role',
        'is_active',
        'avatar_path',
        'avatar_color',
        'timezone',
        'language',
        'theme_mode',
        // OAuth fields
        'oauth_provider',
        'oauth_id',
        'oauth_token',
        'oauth_refresh_token',
        'oauth_token_expires_at',
        // Password reset fields
        'password_reset_token',
        'password_reset_expires_at',
    ];

    protected $hidden = [
        'password_hash',
        'oauth_token',
        'oauth_refresh_token',
        'password_reset_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'oauth_token_expires_at' => 'datetime',
        'password_reset_expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Check if user authenticates via OAuth
     */
    public function isOAuthUser(): bool
    {
        return !empty($this->oauth_provider) && !empty($this->oauth_id);
    }

    /**
     * Check if password reset token is valid
     */
    public function hasValidPasswordResetToken(): bool
    {
        return !empty($this->password_reset_token) 
            && $this->password_reset_expires_at 
            && $this->password_reset_expires_at->isFuture();
    }

    /**
     * Generate password reset token
     */
    public function generatePasswordResetToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->password_reset_token = hash('sha256', $token);
        $this->password_reset_expires_at = Carbon::now()->addHours(1);
        $this->save();
        
        return $token; // Return unhashed token for email
    }

    /**
     * Clear password reset token
     */
    public function clearPasswordResetToken(): void
    {
        $this->password_reset_token = null;
        $this->password_reset_expires_at = null;
        $this->save();
    }

    /**
     * Find user by password reset token
     */
    public static function findByPasswordResetToken(string $token): ?self
    {
        $hashedToken = hash('sha256', $token);
        return static::where('password_reset_token', $hashedToken)
            ->where('password_reset_expires_at', '>', Carbon::now())
            ->first();
    }

    /**
     * Find or create user from OAuth data
     */
    public static function findOrCreateFromOAuth(string $provider, array $userData): self
    {
        $user = static::where('oauth_provider', $provider)
            ->where('oauth_id', $userData['id'])
            ->first();

        if ($user) {
            // Update OAuth tokens
            $user->update([
                'oauth_token' => $userData['access_token'] ?? null,
                'oauth_refresh_token' => $userData['refresh_token'] ?? null,
                'oauth_token_expires_at' => $userData['expires_at'] ?? null,
            ]);
            return $user;
        }

        // Check if user exists with same email
        $existingUser = static::where('email', $userData['email'])->first();
        if ($existingUser) {
            // Link OAuth to existing account
            $existingUser->update([
                'oauth_provider' => $provider,
                'oauth_id' => $userData['id'],
                'oauth_token' => $userData['access_token'] ?? null,
                'oauth_refresh_token' => $userData['refresh_token'] ?? null,
                'oauth_token_expires_at' => $userData['expires_at'] ?? null,
            ]);
            return $existingUser;
        }

        // Create new user
        return static::create([
            'email' => $userData['email'],
            'name' => $userData['name'] ?? $userData['email'],
            'oauth_provider' => $provider,
            'oauth_id' => $userData['id'],
            'oauth_token' => $userData['access_token'] ?? null,
            'oauth_refresh_token' => $userData['refresh_token'] ?? null,
            'oauth_token_expires_at' => $userData['expires_at'] ?? null,
            'role' => 'user',
            'is_active' => true,
        ]);
    }

    /**
     * Get avatar color with fallback
     * 
     * @return int Color number (1-8)
     */
    public function getAvatarColorAttribute($value)
    {
        // Fallback to calculated color if not set
        return $value ?? (($this->id % 8) + 1);
    }

    /**
     * Get IMAP accounts for this user
     */
    public function imapAccounts()
    {
        return $this->hasMany(ImapAccount::class);
    }

    /**
     * Get thread assignments for this user
     */
    public function assignedThreads()
    {
        return $this->belongsToMany(Thread::class, 'thread_assignments')
            ->withPivot('assigned_at');
    }
}

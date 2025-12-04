<?php

declare(strict_types=1);

namespace CiInbox\Modules\Theme;

use CiInbox\App\Models\User;
use CiInbox\Modules\Logger\LoggerInterface;
use CiInbox\Modules\Config\ConfigInterface;

/**
 * Theme Service
 * 
 * Manages user theme preferences (auto/light/dark mode).
 */
class ThemeService implements ThemeServiceInterface
{
    private const VALID_THEMES = ['auto', 'light', 'dark'];
    private const DEFAULT_THEME = 'auto';

    public function __construct(
        private LoggerInterface $logger,
        private ConfigInterface $config
    ) {}

    /**
     * Get user's theme preference
     */
    public function getUserTheme(int $userId): string
    {
        try {
            $user = User::find($userId);
            
            if (!$user) {
                $this->logger->warning('User not found for theme retrieval', [
                    'user_id' => $userId,
                ]);
                return self::DEFAULT_THEME;
            }

            // Check if theme_mode column exists
            if (!isset($user->theme_mode)) {
                $this->logger->debug('theme_mode column not found, using default', [
                    'user_id' => $userId,
                ]);
                return self::DEFAULT_THEME;
            }

            $themeMode = $user->theme_mode ?? self::DEFAULT_THEME;

            $this->logger->debug('Retrieved user theme preference', [
                'user_id' => $userId,
                'theme_mode' => $themeMode,
            ]);

            return $themeMode;

        } catch (\Exception $e) {
            $this->logger->error('Failed to get user theme', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return self::DEFAULT_THEME;
        }
    }

    /**
     * Set user's theme preference
     */
    public function setUserTheme(int $userId, string $themeMode): bool
    {
        if (!$this->isValidThemeMode($themeMode)) {
            $this->logger->warning('Invalid theme mode provided', [
                'user_id' => $userId,
                'theme_mode' => $themeMode,
                'valid_modes' => self::VALID_THEMES,
            ]);
            return false;
        }

        try {
            $user = User::find($userId);
            
            if (!$user) {
                $this->logger->error('User not found for theme update', [
                    'user_id' => $userId,
                ]);
                return false;
            }

            $previousTheme = $user->theme_mode ?? self::DEFAULT_THEME;
            $user->theme_mode = $themeMode;
            $user->save();

            $this->logger->info('User theme preference updated', [
                'user_id' => $userId,
                'previous_theme' => $previousTheme,
                'new_theme' => $themeMode,
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Failed to update user theme', [
                'user_id' => $userId,
                'theme_mode' => $themeMode,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Validate theme mode value
     */
    public function isValidThemeMode(string $themeMode): bool
    {
        return in_array($themeMode, self::VALID_THEMES, true);
    }

    /**
     * Get default theme mode
     */
    public function getDefaultTheme(): string
    {
        return $this->config->get('theme.default_mode', self::DEFAULT_THEME);
    }

    /**
     * Get all valid theme modes
     * 
     * @return array<string>
     */
    public function getValidThemes(): array
    {
        return self::VALID_THEMES;
    }
}

<?php

namespace CiInbox\Modules\Theme;

/**
 * Theme Service Interface
 * 
 * Contract for theme management operations.
 */
interface ThemeServiceInterface
{
    /**
     * Get user's theme preference
     * 
     * @param int $userId User ID
     * @return string Theme mode: 'auto', 'light', 'dark'
     */
    public function getUserTheme(int $userId): string;

    /**
     * Set user's theme preference
     * 
     * @param int $userId User ID
     * @param string $themeMode Theme mode: 'auto', 'light', 'dark'
     * @return bool Success status
     */
    public function setUserTheme(int $userId, string $themeMode): bool;

    /**
     * Validate theme mode value
     * 
     * @param string $themeMode Theme mode to validate
     * @return bool True if valid
     */
    public function isValidThemeMode(string $themeMode): bool;

    /**
     * Get default theme mode
     * 
     * @return string Default theme mode
     */
    public function getDefaultTheme(): string;
}

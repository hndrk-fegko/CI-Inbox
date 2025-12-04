/**
 * Theme Switcher - Client-Side Theme Management
 * 
 * Automatically applies user's theme preference on page load.
 * Handles system preference detection for 'auto' mode.
 */

(function() {
  'use strict';

  const THEME_STORAGE_KEY = 'ciinbox_theme_override';
  const VALID_THEMES = ['auto', 'light', 'dark'];

  /**
   * Theme Manager
   */
  class ThemeManager {
    constructor() {
      this.currentTheme = null;
      this.systemTheme = this.detectSystemTheme();
      
      // Listen for system theme changes
      if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
          this.systemTheme = e.matches ? 'dark' : 'light';
          if (this.currentTheme === 'auto') {
            this.applyTheme('auto');
          }
        });
      }
    }

    /**
     * Detect system color scheme preference
     */
    detectSystemTheme() {
      if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        return 'dark';
      }
      return 'light';
    }

    /**
     * Get theme from user settings (set via PHP in data attribute)
     */
    getUserTheme() {
      const htmlElement = document.documentElement;
      const userTheme = htmlElement.getAttribute('data-user-theme');
      
      if (userTheme && VALID_THEMES.includes(userTheme)) {
        console.log('[Theme] User preference from database:', userTheme);
        return userTheme;
      }
      
      // Fallback to localStorage override
      const storedTheme = localStorage.getItem(THEME_STORAGE_KEY);
      if (storedTheme && VALID_THEMES.includes(storedTheme)) {
        console.log('[Theme] Using localStorage override:', storedTheme);
        return storedTheme;
      }
      
      return 'auto';
    }

    /**
     * Apply theme to document
     */
    applyTheme(theme) {
      if (!VALID_THEMES.includes(theme)) {
        console.warn('[Theme] Invalid theme:', theme);
        theme = 'auto';
      }

      this.currentTheme = theme;
      const htmlElement = document.documentElement;
      
      // Determine effective theme (resolve 'auto' to actual theme)
      let effectiveTheme = theme;
      if (theme === 'auto') {
        effectiveTheme = this.systemTheme;
        console.log('[Theme] Auto mode - using system theme:', effectiveTheme);
      }
      
      // Set data attribute
      htmlElement.setAttribute('data-theme', effectiveTheme);
      
      console.log('[Theme] Applied theme:', {
        userPreference: theme,
        effectiveTheme: effectiveTheme,
        systemTheme: this.systemTheme
      });
    }

    /**
     * Set user theme preference (call API to persist)
     */
    async setUserTheme(theme) {
      if (!VALID_THEMES.includes(theme)) {
        console.error('[Theme] Invalid theme mode:', theme);
        return false;
      }

      try {
        const response = await fetch('/api/user/theme', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ theme_mode: theme })
        });

        const result = await response.json();

        if (result.success) {
          console.log('[Theme] Theme preference saved:', theme);
          
          // Apply immediately
          this.applyTheme(theme);
          
          // Store in localStorage as backup
          localStorage.setItem(THEME_STORAGE_KEY, theme);
          
          return true;
        } else {
          console.error('[Theme] Failed to save theme:', result.error);
          return false;
        }
      } catch (error) {
        console.error('[Theme] Error saving theme:', error);
        return false;
      }
    }

    /**
     * Initialize theme on page load
     */
    init() {
      const userTheme = this.getUserTheme();
      this.applyTheme(userTheme);
      console.log('[Theme] Initialized with theme:', userTheme);
    }
  }

  // Global instance
  window.themeManager = new ThemeManager();
  
  // Auto-initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      window.themeManager.init();
    });
  } else {
    window.themeManager.init();
  }

  console.log('[Theme] Theme switcher loaded');
})();

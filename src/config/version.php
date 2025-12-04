<?php
/**
 * Asset Versioning Configuration
 * 
 * This file provides a centralized cache-busting mechanism for CSS and JS files.
 * 
 * USAGE IN VIEWS:
 * ===============
 * Include this file at the top of any PHP view that needs asset versioning:
 *   require_once __DIR__ . '/../config/version.php';
 * 
 * Then use the asset_version() function:
 *   <link rel="stylesheet" href="/assets/css/main.css<?= asset_version() ?>">
 *   <script src="/assets/js/app.js<?= asset_version() ?>"></script>
 * 
 * REGEX PATTERN FOR UPDATES:
 * ==========================
 * To update all cache-busters via regex find/replace:
 *   Find:    \?v=\d+
 *   Replace: ?v=NEW_VERSION
 * 
 * Or simply update ASSET_VERSION below - all views will pick up the change.
 * 
 * @version 1.0.0
 */

declare(strict_types=1);

/**
 * Asset version number
 * 
 * DEVELOPMENT: Use time() for always-fresh assets
 * PRODUCTION:  Use fixed version string like '20251128.1' or timestamp
 * 
 * Format recommendation: YYYYMMDD.revision (e.g., '20251128.1')
 */
if (!defined('ASSET_VERSION')) {
    // Development mode: always fresh assets (no browser caching)
    // Production mode: uncomment the fixed version below
    
    $isDevelopment = getenv('APP_ENV') !== 'production';
    
    if ($isDevelopment) {
        // Development: Timestamp for fresh assets on every page load
        define('ASSET_VERSION', time());
    } else {
        // Production: Fixed version - update this when deploying new assets
        define('ASSET_VERSION', '20251128.1');
    }
}

/**
 * Get asset version query string
 * 
 * Returns a query string for cache busting: ?v=VERSION
 * 
 * @return string Query string like "?v=1732800000"
 */
if (!function_exists('asset_version')) {
    function asset_version(): string
    {
        return '?v=' . ASSET_VERSION;
    }
}

/**
 * Get versioned asset URL
 * 
 * @param string $path Asset path (e.g., '/assets/css/main.css')
 * @return string Full path with version (e.g., '/assets/css/main.css?v=1732800000')
 */
if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return $path . asset_version();
    }
}

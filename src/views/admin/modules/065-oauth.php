<?php
/**
 * Admin Tab Module: OAuth2 Configuration
 * 
 * Provides:
 * - OAuth2 provider configuration (Google, Microsoft, GitHub, etc.)
 * - Client credentials management
 * - Redirect URI configuration
 * - Provider enable/disable toggles
 * - Token management and session overview
 * 
 * Auto-discovered by admin dashboard
 */

return [
    'id' => 'oauth',
    'title' => 'OAuth2',
    'priority' => 65,
    'icon' => '<path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/>',
    
    'card' => function() {
        ?>
        <div class="c-admin-card" data-module="oauth" style="cursor: pointer;">
            <div class="c-admin-card__header">
                <div class="c-admin-card__icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="c-admin-card__title">OAuth2 / SSO</h3>
                </div>
            </div>
            <p class="c-admin-card__description">Configure Single Sign-On with Google, Microsoft, or other OAuth2 providers.</p>
            <div class="c-admin-card__content">
                <div class="c-info-row">
                    <span class="c-info-row__label">Status</span>
                    <span id="oauth-status-badge" class="c-status-badge c-status-badge--warning">
                        <span class="status-dot"></span>
                        Not Configured
                    </span>
                </div>
                <div class="c-info-row">
                    <span class="c-info-row__label">Active Providers</span>
                    <span class="c-info-row__value" id="oauth-providers-count">0</span>
                </div>
                <div class="c-info-row">
                    <span class="c-info-row__label">OAuth Users</span>
                    <span class="c-info-row__value" id="oauth-users-count">0</span>
                </div>
            </div>
        </div>
        <?php
    },
    
    'content' => function() {
        ?>
        <div style="margin-bottom: 2rem;">
            <h3 style="margin: 0 0 0.5rem 0; font-size: 1.25rem; font-weight: 600;">OAuth2 / Single Sign-On</h3>
            <p style="margin: 0; color: #666; font-size: 0.875rem;">Configure OAuth2 providers to allow users to sign in with their existing accounts.</p>
        </div>
        
        <!-- Info Box -->
        <div style="background: #E3F2FD; border-left: 4px solid #2196F3; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="#1976D2" style="flex-shrink: 0; margin-top: 2px;">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <strong style="color: #1565C0;">About OAuth2 / SSO</strong>
                    <p style="margin: 0.5rem 0 0 0; color: #1976D2; font-size: 0.875rem;">
                        OAuth2 allows users to sign in using their existing accounts from providers like Google or Microsoft.
                        You'll need to register your application with each provider to obtain Client ID and Secret.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Alert Container -->
        <div id="oauth-alert" style="margin-bottom: 1rem;"></div>
        
        <!-- Global OAuth Settings -->
        <div style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 1.5rem;">
            <h4 style="margin: 0 0 1rem 0; display: flex; align-items: center; gap: 0.5rem;">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                </svg>
                Global Settings
            </h4>
            
            <div class="c-input-group" style="margin-bottom: 1rem;">
                <label>
                    <input type="checkbox" id="oauth-enabled">
                    Enable OAuth2 Authentication
                </label>
                <small style="display: block; color: #666; margin-top: 0.25rem;">
                    When enabled, the login page will show OAuth provider buttons
                </small>
            </div>
            
            <div class="c-input-group" style="margin-bottom: 1rem;">
                <label for="oauth-callback-url">Callback URL (Redirect URI)</label>
                <div style="display: flex; gap: 0.5rem;">
                    <input type="text" id="oauth-callback-url" class="c-input" readonly 
                           style="flex: 1; font-family: monospace; font-size: 0.875rem; background: #f5f5f5;"
                           value="Loading...">
                    <button type="button" id="oauth-copy-callback-btn" class="c-button c-button--secondary" style="white-space: nowrap;">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                            <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"/>
                            <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z"/>
                        </svg>
                        Copy
                    </button>
                </div>
                <small style="color: #666;">Use this URL when registering your app with OAuth providers</small>
            </div>
            
            <div class="c-input-group" style="margin-bottom: 1rem;">
                <label>
                    <input type="checkbox" id="oauth-auto-register">
                    Auto-register new users
                </label>
                <small style="display: block; color: #666; margin-top: 0.25rem;">
                    Automatically create accounts for users who sign in via OAuth for the first time
                </small>
            </div>
            
            <div class="c-input-group" style="margin-bottom: 1rem;">
                <label for="oauth-default-role">Default role for new OAuth users</label>
                <select id="oauth-default-role" class="c-input" style="max-width: 200px;">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            
            <button type="button" id="oauth-save-global-btn" class="c-button c-button--primary">
                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                    <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z"/>
                </svg>
                Save Global Settings
            </button>
        </div>
        
        <!-- OAuth Providers -->
        <div style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 1.5rem;">
            <h4 style="margin: 0 0 1rem 0; display: flex; align-items: center; gap: 0.5rem;">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                </svg>
                OAuth Providers
            </h4>
            
            <!-- Google Provider -->
            <div style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 1.25rem; margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="width: 40px; height: 40px; background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <svg width="24" height="24" viewBox="0 0 24 24">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                            </svg>
                        </div>
                        <div>
                            <h5 style="margin: 0; font-size: 1rem;">Google</h5>
                            <small style="color: #666;">Sign in with Google Account</small>
                        </div>
                    </div>
                    <label class="c-toggle">
                        <input type="checkbox" id="oauth-google-enabled">
                        <span class="c-toggle__slider"></span>
                    </label>
                </div>
                
                <div id="oauth-google-config" style="display: none;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                        <div class="c-input-group">
                            <label for="oauth-google-client-id">Client ID <span style="color: #f44336;">*</span></label>
                            <input type="text" id="oauth-google-client-id" class="c-input" placeholder="xxxx.apps.googleusercontent.com">
                        </div>
                        <div class="c-input-group">
                            <label for="oauth-google-client-secret">Client Secret <span style="color: #f44336;">*</span></label>
                            <input type="password" id="oauth-google-client-secret" class="c-input" placeholder="Leave empty to keep current">
                        </div>
                    </div>
                    <small style="color: #666; display: block; margin-top: 0.5rem;">
                        Get credentials from <a href="https://console.cloud.google.com/apis/credentials" target="_blank" style="color: #2196F3;">Google Cloud Console</a>
                    </small>
                </div>
            </div>
            
            <!-- Microsoft Provider -->
            <div style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 1.25rem; margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="width: 40px; height: 40px; background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <svg width="21" height="21" viewBox="0 0 21 21">
                                <rect x="1" y="1" width="9" height="9" fill="#f25022"/>
                                <rect x="11" y="1" width="9" height="9" fill="#7fba00"/>
                                <rect x="1" y="11" width="9" height="9" fill="#00a4ef"/>
                                <rect x="11" y="11" width="9" height="9" fill="#ffb900"/>
                            </svg>
                        </div>
                        <div>
                            <h5 style="margin: 0; font-size: 1rem;">Microsoft</h5>
                            <small style="color: #666;">Sign in with Microsoft / Azure AD</small>
                        </div>
                    </div>
                    <label class="c-toggle">
                        <input type="checkbox" id="oauth-microsoft-enabled">
                        <span class="c-toggle__slider"></span>
                    </label>
                </div>
                
                <div id="oauth-microsoft-config" style="display: none;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                        <div class="c-input-group">
                            <label for="oauth-microsoft-client-id">Application (Client) ID <span style="color: #f44336;">*</span></label>
                            <input type="text" id="oauth-microsoft-client-id" class="c-input" placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx">
                        </div>
                        <div class="c-input-group">
                            <label for="oauth-microsoft-client-secret">Client Secret <span style="color: #f44336;">*</span></label>
                            <input type="password" id="oauth-microsoft-client-secret" class="c-input" placeholder="Leave empty to keep current">
                        </div>
                        <div class="c-input-group">
                            <label for="oauth-microsoft-tenant">Tenant ID</label>
                            <input type="text" id="oauth-microsoft-tenant" class="c-input" placeholder="common (or specific tenant ID)">
                            <small style="color: #666;">Use "common" for any Microsoft account, or specific tenant ID for organization-only</small>
                        </div>
                    </div>
                    <small style="color: #666; display: block; margin-top: 0.5rem;">
                        Get credentials from <a href="https://portal.azure.com/#blade/Microsoft_AAD_RegisteredApps/ApplicationsListBlade" target="_blank" style="color: #2196F3;">Azure Portal - App Registrations</a>
                    </small>
                </div>
            </div>
            
            <!-- GitHub Provider -->
            <div style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 1.25rem; margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="width: 40px; height: 40px; background: #24292e; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
                                <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                            </svg>
                        </div>
                        <div>
                            <h5 style="margin: 0; font-size: 1rem;">GitHub</h5>
                            <small style="color: #666;">Sign in with GitHub Account</small>
                        </div>
                    </div>
                    <label class="c-toggle">
                        <input type="checkbox" id="oauth-github-enabled">
                        <span class="c-toggle__slider"></span>
                    </label>
                </div>
                
                <div id="oauth-github-config" style="display: none;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                        <div class="c-input-group">
                            <label for="oauth-github-client-id">Client ID <span style="color: #f44336;">*</span></label>
                            <input type="text" id="oauth-github-client-id" class="c-input" placeholder="Iv1.xxxxxxxxxx">
                        </div>
                        <div class="c-input-group">
                            <label for="oauth-github-client-secret">Client Secret <span style="color: #f44336;">*</span></label>
                            <input type="password" id="oauth-github-client-secret" class="c-input" placeholder="Leave empty to keep current">
                        </div>
                    </div>
                    <small style="color: #666; display: block; margin-top: 0.5rem;">
                        Get credentials from <a href="https://github.com/settings/developers" target="_blank" style="color: #2196F3;">GitHub Developer Settings</a>
                    </small>
                </div>
            </div>
            
            <!-- Custom OIDC Provider -->
            <div style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 1.25rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="width: 40px; height: 40px; background: #9c27b0; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
                                <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                            </svg>
                        </div>
                        <div>
                            <h5 style="margin: 0; font-size: 1rem;">Custom OIDC Provider</h5>
                            <small style="color: #666;">Any OpenID Connect compatible provider</small>
                        </div>
                    </div>
                    <label class="c-toggle">
                        <input type="checkbox" id="oauth-custom-enabled">
                        <span class="c-toggle__slider"></span>
                    </label>
                </div>
                
                <div id="oauth-custom-config" style="display: none;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                        <div class="c-input-group">
                            <label for="oauth-custom-name">Provider Name <span style="color: #f44336;">*</span></label>
                            <input type="text" id="oauth-custom-name" class="c-input" placeholder="My SSO Provider">
                        </div>
                        <div class="c-input-group">
                            <label for="oauth-custom-discovery-url">Discovery URL <span style="color: #f44336;">*</span></label>
                            <input type="url" id="oauth-custom-discovery-url" class="c-input" placeholder="https://example.com/.well-known/openid-configuration">
                        </div>
                        <div class="c-input-group">
                            <label for="oauth-custom-client-id">Client ID <span style="color: #f44336;">*</span></label>
                            <input type="text" id="oauth-custom-client-id" class="c-input" placeholder="client_id">
                        </div>
                        <div class="c-input-group">
                            <label for="oauth-custom-client-secret">Client Secret <span style="color: #f44336;">*</span></label>
                            <input type="password" id="oauth-custom-client-secret" class="c-input" placeholder="Leave empty to keep current">
                        </div>
                        <div class="c-input-group">
                            <label for="oauth-custom-scopes">Scopes</label>
                            <input type="text" id="oauth-custom-scopes" class="c-input" placeholder="openid profile email" value="openid profile email">
                        </div>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 1.5rem;">
                <button type="button" id="oauth-save-providers-btn" class="c-button c-button--primary">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                        <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z"/>
                    </svg>
                    Save Provider Configuration
                </button>
            </div>
        </div>
        
        <!-- Active Sessions -->
        <div style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h4 style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 0l-2 2a1 1 0 101.414 1.414L8 10.414l1.293 1.293a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    OAuth Users & Sessions
                </h4>
                <button type="button" id="oauth-refresh-sessions-btn" class="c-button c-button--secondary" style="font-size: 0.875rem;">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                        <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                    </svg>
                    Refresh
                </button>
            </div>
            
            <div id="oauth-sessions-container">
                <div style="padding: 2rem; text-align: center; color: #666;">
                    <svg width="48" height="48" viewBox="0 0 20 20" fill="currentColor" style="opacity: 0.3; margin-bottom: 0.5rem;">
                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                    </svg>
                    <p style="margin: 0;">No OAuth users found</p>
                    <p style="margin: 0.25rem 0 0 0; font-size: 0.875rem;">Configure providers above to enable OAuth login</p>
                </div>
            </div>
        </div>
        
        <!-- Toggle Styles -->
        <style>
            .c-toggle {
                position: relative;
                display: inline-block;
                width: 50px;
                height: 26px;
            }
            .c-toggle input {
                opacity: 0;
                width: 0;
                height: 0;
            }
            .c-toggle__slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                transition: 0.3s;
                border-radius: 26px;
            }
            .c-toggle__slider:before {
                position: absolute;
                content: "";
                height: 20px;
                width: 20px;
                left: 3px;
                bottom: 3px;
                background-color: white;
                transition: 0.3s;
                border-radius: 50%;
            }
            .c-toggle input:checked + .c-toggle__slider {
                background-color: #4CAF50;
            }
            .c-toggle input:checked + .c-toggle__slider:before {
                transform: translateX(24px);
            }
        </style>
        <?php
    },
    
    'script' => function() {
        ?>
        // OAuth Module State
        const OAuthModule = {
            config: {},
            
            init() {
                console.log('[OAuth] Initializing module...');
                this.loadConfig();
                this.bindEvents();
                this.updateCallbackUrl();
            },
            
            bindEvents() {
                // Global settings save
                const saveGlobalBtn = document.getElementById('oauth-save-global-btn');
                if (saveGlobalBtn) {
                    saveGlobalBtn.addEventListener('click', () => this.saveGlobalSettings());
                }
                
                // Provider toggles
                ['google', 'microsoft', 'github', 'custom'].forEach(provider => {
                    const toggle = document.getElementById(`oauth-${provider}-enabled`);
                    const config = document.getElementById(`oauth-${provider}-config`);
                    
                    if (toggle && config) {
                        toggle.addEventListener('change', () => {
                            config.style.display = toggle.checked ? 'block' : 'none';
                        });
                    }
                });
                
                // Save providers
                const saveProvidersBtn = document.getElementById('oauth-save-providers-btn');
                if (saveProvidersBtn) {
                    saveProvidersBtn.addEventListener('click', () => this.saveProviders());
                }
                
                // Copy callback URL
                const copyCallbackBtn = document.getElementById('oauth-copy-callback-btn');
                if (copyCallbackBtn) {
                    copyCallbackBtn.addEventListener('click', () => this.copyCallbackUrl());
                }
                
                // Refresh sessions
                const refreshSessionsBtn = document.getElementById('oauth-refresh-sessions-btn');
                if (refreshSessionsBtn) {
                    refreshSessionsBtn.addEventListener('click', () => this.loadSessions());
                }
            },
            
            updateCallbackUrl() {
                const callbackInput = document.getElementById('oauth-callback-url');
                if (callbackInput) {
                    callbackInput.value = window.location.origin + '/auth/oauth/callback';
                }
            },
            
            async loadConfig() {
                try {
                    const response = await fetch('/api/admin/oauth/config');
                    const data = await response.json();
                    
                    if (data.success && data.data) {
                        this.config = data.data;
                        this.populateForm(data.data);
                        this.updateCardStatus(data.data);
                    }
                } catch (error) {
                    console.error('[OAuth] Failed to load config:', error);
                }
                
                this.loadSessions();
            },
            
            populateForm(config) {
                // Global settings
                const enabledCheckbox = document.getElementById('oauth-enabled');
                const autoRegisterCheckbox = document.getElementById('oauth-auto-register');
                const defaultRoleSelect = document.getElementById('oauth-default-role');
                
                if (enabledCheckbox) enabledCheckbox.checked = config.enabled || false;
                if (autoRegisterCheckbox) autoRegisterCheckbox.checked = config.auto_register || false;
                if (defaultRoleSelect) defaultRoleSelect.value = config.default_role || 'user';
                
                // Provider settings
                const providers = config.providers || {};
                
                ['google', 'microsoft', 'github', 'custom'].forEach(provider => {
                    const providerConfig = providers[provider] || {};
                    const toggle = document.getElementById(`oauth-${provider}-enabled`);
                    const configDiv = document.getElementById(`oauth-${provider}-config`);
                    
                    if (toggle) {
                        toggle.checked = providerConfig.enabled || false;
                        if (configDiv) {
                            configDiv.style.display = toggle.checked ? 'block' : 'none';
                        }
                    }
                    
                    // Fill provider-specific fields
                    const clientIdInput = document.getElementById(`oauth-${provider}-client-id`);
                    if (clientIdInput && providerConfig.client_id) {
                        clientIdInput.value = providerConfig.client_id;
                    }
                    
                    // Special fields for certain providers
                    if (provider === 'microsoft') {
                        const tenantInput = document.getElementById('oauth-microsoft-tenant');
                        if (tenantInput && providerConfig.tenant) {
                            tenantInput.value = providerConfig.tenant;
                        }
                    }
                    
                    if (provider === 'custom') {
                        const nameInput = document.getElementById('oauth-custom-name');
                        const discoveryInput = document.getElementById('oauth-custom-discovery-url');
                        const scopesInput = document.getElementById('oauth-custom-scopes');
                        
                        if (nameInput && providerConfig.name) nameInput.value = providerConfig.name;
                        if (discoveryInput && providerConfig.discovery_url) discoveryInput.value = providerConfig.discovery_url;
                        if (scopesInput && providerConfig.scopes) scopesInput.value = providerConfig.scopes;
                    }
                });
            },
            
            updateCardStatus(config) {
                const statusBadge = document.getElementById('oauth-status-badge');
                const providersCount = document.getElementById('oauth-providers-count');
                
                const enabledProviders = Object.values(config.providers || {}).filter(p => p.enabled).length;
                
                if (providersCount) {
                    providersCount.textContent = enabledProviders;
                }
                
                if (statusBadge) {
                    if (config.enabled && enabledProviders > 0) {
                        statusBadge.className = 'c-status-badge c-status-badge--success';
                        statusBadge.innerHTML = '<span class="status-dot"></span>Active';
                    } else if (enabledProviders > 0) {
                        statusBadge.className = 'c-status-badge c-status-badge--warning';
                        statusBadge.innerHTML = '<span class="status-dot"></span>Disabled';
                    } else {
                        statusBadge.className = 'c-status-badge c-status-badge--warning';
                        statusBadge.innerHTML = '<span class="status-dot"></span>Not Configured';
                    }
                }
            },
            
            async saveGlobalSettings() {
                const saveBtn = document.getElementById('oauth-save-global-btn');
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
                
                try {
                    const data = {
                        enabled: document.getElementById('oauth-enabled')?.checked || false,
                        auto_register: document.getElementById('oauth-auto-register')?.checked || false,
                        default_role: document.getElementById('oauth-default-role')?.value || 'user'
                    };
                    
                    const response = await fetch('/api/admin/oauth/config', {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.showAlert('oauth-alert', 'Global settings saved successfully!', 'success');
                        this.loadConfig();
                    } else {
                        this.showAlert('oauth-alert', result.error || 'Failed to save settings', 'error');
                    }
                } catch (error) {
                    console.error('[OAuth] Save failed:', error);
                    this.showAlert('oauth-alert', 'Failed to save settings: ' + error.message, 'error');
                } finally {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;"><path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z"/></svg> Save Global Settings';
                }
            },
            
            async saveProviders() {
                const saveBtn = document.getElementById('oauth-save-providers-btn');
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
                
                try {
                    const providers = {};
                    
                    // Google
                    providers.google = {
                        enabled: document.getElementById('oauth-google-enabled')?.checked || false,
                        client_id: document.getElementById('oauth-google-client-id')?.value || '',
                        client_secret: document.getElementById('oauth-google-client-secret')?.value || ''
                    };
                    
                    // Microsoft
                    providers.microsoft = {
                        enabled: document.getElementById('oauth-microsoft-enabled')?.checked || false,
                        client_id: document.getElementById('oauth-microsoft-client-id')?.value || '',
                        client_secret: document.getElementById('oauth-microsoft-client-secret')?.value || '',
                        tenant: document.getElementById('oauth-microsoft-tenant')?.value || 'common'
                    };
                    
                    // GitHub
                    providers.github = {
                        enabled: document.getElementById('oauth-github-enabled')?.checked || false,
                        client_id: document.getElementById('oauth-github-client-id')?.value || '',
                        client_secret: document.getElementById('oauth-github-client-secret')?.value || ''
                    };
                    
                    // Custom
                    providers.custom = {
                        enabled: document.getElementById('oauth-custom-enabled')?.checked || false,
                        name: document.getElementById('oauth-custom-name')?.value || '',
                        discovery_url: document.getElementById('oauth-custom-discovery-url')?.value || '',
                        client_id: document.getElementById('oauth-custom-client-id')?.value || '',
                        client_secret: document.getElementById('oauth-custom-client-secret')?.value || '',
                        scopes: document.getElementById('oauth-custom-scopes')?.value || 'openid profile email'
                    };
                    
                    const response = await fetch('/api/admin/oauth/providers', {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ providers })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.showAlert('oauth-alert', 'Provider configuration saved successfully!', 'success');
                        this.loadConfig();
                    } else {
                        this.showAlert('oauth-alert', result.error || 'Failed to save providers', 'error');
                    }
                } catch (error) {
                    console.error('[OAuth] Save providers failed:', error);
                    this.showAlert('oauth-alert', 'Failed to save providers: ' + error.message, 'error');
                } finally {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;"><path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z"/></svg> Save Provider Configuration';
                }
            },
            
            copyCallbackUrl() {
                const input = document.getElementById('oauth-callback-url');
                if (!input) return;
                
                navigator.clipboard.writeText(input.value).then(() => {
                    this.showAlert('oauth-alert', 'Callback URL copied to clipboard!', 'success');
                }).catch(err => {
                    input.select();
                    document.execCommand('copy');
                    this.showAlert('oauth-alert', 'Callback URL copied to clipboard!', 'success');
                });
            },
            
            async loadSessions() {
                const container = document.getElementById('oauth-sessions-container');
                
                try {
                    const response = await fetch('/api/admin/oauth/users');
                    const data = await response.json();
                    
                    if (data.success && data.data && data.data.length > 0) {
                        this.renderSessions(data.data);
                        
                        // Update card count
                        const usersCount = document.getElementById('oauth-users-count');
                        if (usersCount) usersCount.textContent = data.data.length;
                    } else {
                        container.innerHTML = `
                            <div style="padding: 2rem; text-align: center; color: #666;">
                                <svg width="48" height="48" viewBox="0 0 20 20" fill="currentColor" style="opacity: 0.3; margin-bottom: 0.5rem;">
                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                </svg>
                                <p style="margin: 0;">No OAuth users found</p>
                                <p style="margin: 0.25rem 0 0 0; font-size: 0.875rem;">Configure providers above to enable OAuth login</p>
                            </div>
                        `;
                    }
                } catch (error) {
                    console.error('[OAuth] Failed to load sessions:', error);
                }
            },
            
            renderSessions(users) {
                const container = document.getElementById('oauth-sessions-container');
                
                container.innerHTML = `
                    <div class="table-responsive">
                        <table class="table" style="margin: 0;">
                            <thead style="background: #f8f9fa;">
                                <tr>
                                    <th style="font-weight: 600; color: #666; text-transform: uppercase; font-size: 0.75rem; padding: 0.75rem 1rem; border: none;">User</th>
                                    <th style="font-weight: 600; color: #666; text-transform: uppercase; font-size: 0.75rem; padding: 0.75rem 1rem; border: none;">Provider</th>
                                    <th style="font-weight: 600; color: #666; text-transform: uppercase; font-size: 0.75rem; padding: 0.75rem 1rem; border: none;">Linked</th>
                                    <th style="font-weight: 600; color: #666; text-transform: uppercase; font-size: 0.75rem; padding: 0.75rem 1rem; border: none;">Last Login</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${users.map(user => `
                                    <tr>
                                        <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #eee; font-size: 0.875rem;">
                                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #e0e0e0; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.75rem;">
                                                    ${this.escapeHtml((user.name || user.email || '?').charAt(0).toUpperCase())}
                                                </div>
                                                <div>
                                                    <div style="font-weight: 500;">${this.escapeHtml(user.name || 'Unknown')}</div>
                                                    <div style="font-size: 0.75rem; color: #666;">${this.escapeHtml(user.email || '')}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #eee; font-size: 0.875rem;">
                                            <span class="c-badge" style="background: #e3f2fd; color: #1565c0;">${this.escapeHtml(user.provider || 'Unknown')}</span>
                                        </td>
                                        <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #eee; font-size: 0.875rem;">
                                            ${this.escapeHtml(user.linked_at || '—')}
                                        </td>
                                        <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #eee; font-size: 0.875rem;">
                                            ${this.escapeHtml(user.last_login || '—')}
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            },
            
            showAlert(containerId, message, type = 'info') {
                const container = document.getElementById(containerId);
                if (!container) return;
                
                const alertClass = type === 'success' ? 'c-alert--success' : 
                                   type === 'error' ? 'c-alert--error' : 'c-alert--info';
                
                container.innerHTML = `
                    <div class="c-alert ${alertClass} is-visible">
                        ${this.escapeHtml(message)}
                    </div>
                `;
                
                if (type !== 'error') {
                    setTimeout(() => {
                        container.innerHTML = '';
                    }, 5000);
                }
            },
            
            escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        };
        
        // Initialize on DOMContentLoaded or immediately if already loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => OAuthModule.init());
        } else {
            OAuthModule.init();
        }
        <?php
    }
];

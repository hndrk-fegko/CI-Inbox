<?php
/**
 * Admin Tab Module: IMAP Configuration
 * 
 * Auto-discovered by admin dashboard
 */

return [
    'id' => 'imap',
    'title' => 'IMAP',
    'priority' => 10, // Lower = earlier in list
    'icon' => '<path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>',
    
    // Dashboard card content
    'card' => function() {
        ?>
        <div class="c-admin-card" data-module="imap" style="cursor: pointer;">
            <div class="c-admin-card__header">
                <div class="c-admin-card__icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="c-admin-card__title">Global IMAP</h3>
                </div>
            </div>
            <p class="c-admin-card__description">Configure default IMAP settings and autodiscover service for all users.</p>
            <div class="c-admin-card__content">
                <div id="imap-alert"></div>
                <div id="imap-configured-info" style="display: none; background: #E8F5E9; border: 1px solid #4CAF50; border-radius: 8px; padding: 0.75rem; margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="#4CAF50">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <strong style="color: #2E7D32;">IMAP Configured</strong>
                    </div>
                    <div style="color: #2E7D32; font-size: 0.875rem;">
                        <div>Host: <strong id="imap-configured-host">—</strong></div>
                        <div>User: <strong id="imap-configured-user">—</strong></div>
                    </div>
                </div>
                <div class="c-info-row">
                    <span class="c-info-row__label">Status</span>
                    <span id="imap-status-badge" class="c-status-badge c-status-badge--warning">
                        <span class="status-dot"></span>
                        Not Configured
                    </span>
                </div>
                <div class="c-info-row">
                    <span class="c-info-row__label">Autodiscover</span>
                    <span class="c-info-row__value">Available</span>
                </div>
            </div>
        </div>
        <?php
    },
    
    // Tab content
    'content' => function() {
        ?>
        <div class="c-tabs__content" id="imap-tab">
            <div style="margin-bottom: 2rem;">
                <h3 style="margin: 0 0 0.5rem 0; font-size: 1.25rem; font-weight: 600;">IMAP Configuration</h3>
                <p style="margin: 0; color: #666; font-size: 0.875rem;">Configure global IMAP settings for email polling and autodiscovery.</p>
            </div>
            
            <!-- Coming soon placeholder -->
            <div class="c-alert c-alert--info is-visible">
                <strong>Coming Soon:</strong> IMAP configuration interface is currently in development.
            </div>
            
            <div style="background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                <h4 style="margin-top: 0;">Planned Features</h4>
                <ul>
                    <li>Host, port, and encryption configuration</li>
                    <li>Connection testing</li>
                    <li>Autodiscover service configuration</li>
                    <li>Multiple IMAP account support</li>
                </ul>
            </div>
        </div>
        <?php
    },
    
    // JavaScript initialization
    'script' => function() {
        ?>
        // Load IMAP status on page load
        async function loadImapStatus() {
            try {
                const response = await fetch('/api/admin/settings/imap');
                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.data) {
                        const badge = document.getElementById('imap-status-badge');
                        const info = document.getElementById('imap-configured-info');
                        
                        if (data.data.configured) {
                            badge.className = 'c-status-badge c-status-badge--success';
                            badge.innerHTML = '<span class="status-dot"></span>Configured';
                            info.style.display = 'block';
                            document.getElementById('imap-configured-host').textContent = data.data.host || '—';
                            document.getElementById('imap-configured-user').textContent = data.data.username || '—';
                        }
                    }
                }
            } catch (error) {
                console.error('[IMAP] Failed to load status:', error);
            }
        }
        
        // Auto-load on page ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', loadImapStatus);
        } else {
            loadImapStatus();
        }
        <?php
    }
];

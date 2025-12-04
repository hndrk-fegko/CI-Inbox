<?php
/**
 * Admin Tab Module: SMTP Configuration
 * 
 * Auto-discovered by admin dashboard
 */

return [
    'id' => 'smtp',
    'title' => 'SMTP',
    'priority' => 20,
    'icon' => '<path d="M20 8l-8 5-8-5V6l8 5 8-5m0-2H4c-1.11 0-2 .89-2 2v12a2 2 0 002 2h16a2 2 0 002-2V6a2 2 0 00-2-2z"/>',
    
    'card' => function() {
        ?>
        <div class="c-admin-card" data-module="smtp" style="cursor: pointer;">
            <div class="c-admin-card__header">
                <div class="c-admin-card__icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M20 8l-8 5-8-5V6l8 5 8-5m0-2H4c-1.11 0-2 .89-2 2v12a2 2 0 002 2h16a2 2 0 002-2V6a2 2 0 00-2-2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="c-admin-card__title">Global SMTP</h3>
                </div>
            </div>
            <p class="c-admin-card__description">Configure default SMTP settings for outgoing emails and replies.</p>
            <div class="c-admin-card__content">
                <div id="smtp-alert"></div>
                <div id="smtp-configured-info" style="display: none; background: #E8F5E9; border: 1px solid #4CAF50; border-radius: 8px; padding: 0.75rem; margin-bottom: 1rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="#4CAF50">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <strong style="color: #2E7D32;">SMTP Configured</strong>
                    </div>
                    <div style="color: #2E7D32; font-size: 0.875rem;">
                        <div>Host: <strong id="smtp-configured-host">—</strong></div>
                        <div>From: <strong id="smtp-configured-from">—</strong></div>
                    </div>
                </div>
                <div class="c-info-row">
                    <span class="c-info-row__label">Status</span>
                    <span id="smtp-status-badge" class="c-status-badge c-status-badge--warning">
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
    
    'content' => function() {
        ?>
        <div class="c-tabs__content" id="smtp-tab">
            <div style="margin-bottom: 2rem;">
                <h3 style="margin: 0 0 0.5rem 0; font-size: 1.25rem; font-weight: 600;">SMTP Configuration</h3>
                <p style="margin: 0; color: #666; font-size: 0.875rem;">Configure global SMTP settings for sending emails.</p>
            </div>
            
            <div class="c-alert c-alert--info is-visible">
                <strong>Coming Soon:</strong> SMTP configuration interface is currently in development.
            </div>
            
            <div style="background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                <h4 style="margin-top: 0;">Planned Features</h4>
                <ul>
                    <li>Host, port, and encryption configuration</li>
                    <li>SMTP authentication settings</li>
                    <li>Test email sending</li>
                    <li>From address and name configuration</li>
                </ul>
            </div>
        </div>
        <?php
    },
    
    'script' => function() {
        ?>
        async function loadSmtpStatus() {
            try {
                const response = await fetch('/api/admin/settings/smtp');
                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.data) {
                        const badge = document.getElementById('smtp-status-badge');
                        const info = document.getElementById('smtp-configured-info');
                        
                        if (data.data.configured) {
                            badge.className = 'c-status-badge c-status-badge--success';
                            badge.innerHTML = '<span class="status-dot"></span>Configured';
                            info.style.display = 'block';
                            document.getElementById('smtp-configured-host').textContent = data.data.host || '—';
                            document.getElementById('smtp-configured-from').textContent = data.data.from_email || '—';
                        }
                    }
                }
            } catch (error) {
                console.error('[SMTP] Failed to load status:', error);
            }
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', loadSmtpStatus);
        } else {
            loadSmtpStatus();
        }
        <?php
    }
];

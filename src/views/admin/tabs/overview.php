<?php
/**
 * Admin Settings - Overview Tab
 * 
 * Dashboard with configuration cards for all major system components
 */
?>

<div class="c-tabs__content is-active" id="overview-tab">
    <div class="c-alert c-alert--info is-visible">
        <strong>System Configuration:</strong> Click on any card below to configure the respective component. Cards link to their detailed settings tabs.
    </div>

    <div class="c-admin-grid">
        <!-- IMAP Configuration Card -->
        <div class="c-admin-card" onclick="switchToTab('imap')" style="cursor: pointer;">
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
        
        <!-- SMTP Configuration Card -->
        <div class="c-admin-card" onclick="switchToTab('smtp')" style="cursor: pointer;">
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
        
        <!-- Cron Monitor Card -->
        <div class="c-admin-card" onclick="switchToTab('cron')" style="cursor: pointer;">
            <div class="c-admin-card__header">
                <div class="c-admin-card__icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="c-admin-card__title">Cron Monitor</h3>
                </div>
            </div>
            <p class="c-admin-card__description">Monitor webhook polling service health and execution status.</p>
            <div class="c-admin-card__content">
                <div id="cron-alert"></div>
                <div class="c-info-row">
                    <span class="c-info-row__label">Service Status</span>
                    <span id="cron-status-badge" class="c-status-badge c-status-badge--warning">
                        <span class="status-dot"></span>
                        Loading...
                    </span>
                </div>
                <div class="c-info-row">
                    <span class="c-info-row__label">Last Execution</span>
                    <span id="cron-last-execution" class="c-info-row__value">—</span>
                </div>
                <div class="c-info-row">
                    <span class="c-info-row__label">Executions (Last Hour)</span>
                    <span id="cron-executions-count" class="c-info-row__value">0</span>
                </div>
            </div>
        </div>
        
        <!-- Backup Configuration Card -->
        <div class="c-admin-card" onclick="switchToTab('backup')" style="cursor: pointer;">
            <div class="c-admin-card__header">
                <div class="c-admin-card__icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="c-admin-card__title">Backup System</h3>
                </div>
            </div>
            <p class="c-admin-card__description">Manage database backups and configure automated backup schedules.</p>
            <div class="c-admin-card__content">
                <div class="c-info-row">
                    <span class="c-info-row__label">Latest Backup</span>
                    <span class="c-info-row__value" id="latest-backup-date">Never</span>
                </div>
                <div class="c-info-row">
                    <span class="c-info-row__label">Total Backups</span>
                    <span class="c-info-row__value" id="total-backups-count">0</span>
                </div>
            </div>
        </div>
        
        <!-- Database Info Card -->
        <div class="c-admin-card" onclick="switchToTab('database')" style="cursor: pointer;">
            <div class="c-admin-card__header">
                <div class="c-admin-card__icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 3C7.58 3 4 4.79 4 7s3.58 4 8 4 8-1.79 8-4-3.58-4-8-4zM4 9v3c0 2.21 3.58 4 8 4s8-1.79 8-4V9c0 2.21-3.58 4-8 4s-8-1.79-8-4zm0 5v3c0 2.21 3.58 4 8 4s8-1.79 8-4v-3c0 2.21-3.58 4-8 4s-8-1.79-8-4z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="c-admin-card__title">Database</h3>
                </div>
            </div>
            <p class="c-admin-card__description">Database connection status, migrations, and maintenance tools.</p>
            <div class="c-admin-card__content">
                <div class="c-info-row">
                    <span class="c-info-row__label">Connection</span>
                    <span class="c-status-badge c-status-badge--success">
                        <span class="status-dot"></span>
                        Connected
                    </span>
                </div>
                <div class="c-info-row">
                    <span class="c-info-row__label">Migration Version</span>
                    <span class="c-info-row__value" id="migration-version">—</span>
                </div>
            </div>
        </div>
        
        <!-- Users Management Card -->
        <div class="c-admin-card" onclick="switchToTab('users')" style="cursor: pointer;">
            <div class="c-admin-card__header">
                <div class="c-admin-card__icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="c-admin-card__title">User Management</h3>
                </div>
            </div>
            <p class="c-admin-card__description">Manage user accounts, roles, and permissions.</p>
            <div class="c-admin-card__content">
                <div class="c-info-row">
                    <span class="c-info-row__label">Total Users</span>
                    <span class="c-info-row__value" id="total-users-count">—</span>
                </div>
                <div class="c-info-row">
                    <span class="c-info-row__label">Active Users</span>
                    <span class="c-info-row__value" id="active-users-count">—</span>
                </div>
            </div>
        </div>
        
        <!-- Email Signatures Card -->
        <div class="c-admin-card" onclick="switchToTab('signatures')" style="cursor: pointer;">
            <div class="c-admin-card__header">
                <div class="c-admin-card__icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="c-admin-card__title">Email Signatures</h3>
                </div>
            </div>
            <p class="c-admin-card__description">Manage global email signatures and monitor user signatures.</p>
            <div class="c-admin-card__content">
                <div class="c-info-row">
                    <span class="c-info-row__label">Global Signatures</span>
                    <span class="c-info-row__value" id="global-signature-count-card">—</span>
                </div>
                <div class="c-info-row">
                    <span class="c-info-row__label">User Signatures</span>
                    <span class="c-info-row__value" id="user-signature-count-card">—</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Load overview metrics
async function loadOverviewMetrics() {
    try {
        // Load backup count
        const backupResponse = await fetch('/api/admin/backup/list');
        if (backupResponse.ok) {
            const backupData = await backupResponse.json();
            if (backupData.success && backupData.data.length > 0) {
                document.getElementById('total-backups-count').textContent = backupData.data.length;
                document.getElementById('latest-backup-date').textContent = backupData.data[0].created_at_human;
            }
        }
        
        // Load cron status
        const cronResponse = await fetch('/api/system/cron-status');
        if (cronResponse.ok) {
            const cronData = await cronResponse.json();
            if (cronData.success) {
                document.getElementById('cron-last-execution').textContent = cronData.data.last_poll || '—';
                const badge = document.getElementById('cron-status-badge');
                if (cronData.data.status === 'healthy') {
                    badge.className = 'c-status-badge c-status-badge--success';
                    badge.innerHTML = '<span class="status-dot"></span>Healthy';
                }
            }
        }
    } catch (error) {
        console.error('[Overview] Failed to load metrics:', error);
    }
}

// Load on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadOverviewMetrics);
} else {
    loadOverviewMetrics();
}
</script>

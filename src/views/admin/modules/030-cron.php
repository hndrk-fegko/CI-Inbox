<?php
/**
 * Admin Tab Module: Cron Monitor
 * 
 * Auto-discovered by admin dashboard
 */

return [
    'id' => 'cron',
    'title' => 'Cron',
    'priority' => 30,
    'icon' => '<path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>',
    
    'card' => function() {
        ?>
        <div class="c-admin-card" data-module="cron" style="cursor: pointer;">
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
        <?php
    },
    
    'content' => function() {
        ?>
        <div class="c-tabs__content" id="cron-tab">
            <div style="margin-bottom: 2rem;">
                <h3 style="margin: 0 0 0.5rem 0; font-size: 1.25rem; font-weight: 600;">Cron Job Monitoring</h3>
                <p style="margin: 0; color: #666; font-size: 0.875rem;">Monitor webhook polling service and execution history.</p>
            </div>
            
            <div class="c-alert c-alert--info is-visible">
                <strong>Coming Soon:</strong> Full cron monitoring dashboard with execution history and charts.
            </div>
            
            <div style="background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                <h4 style="margin-top: 0;">Planned Features</h4>
                <ul>
                    <li>Execution history table with pagination</li>
                    <li>Performance charts (executions per hour, emails per day)</li>
                    <li>Manual trigger for testing</li>
                    <li>Email alerts configuration</li>
                    <li>Export execution log to CSV</li>
                </ul>
            </div>
        </div>
        <?php
    },
    
    'script' => function() {
        ?>
        async function loadCronStatus() {
            try {
                const response = await fetch('/api/system/cron-status');
                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.data) {
                        const badge = document.getElementById('cron-status-badge');
                        const lastExec = document.getElementById('cron-last-execution');
                        const execCount = document.getElementById('cron-executions-count');
                        
                        // Update status badge
                        if (data.data.status === 'healthy') {
                            badge.className = 'c-status-badge c-status-badge--success';
                            badge.innerHTML = '<span class="status-dot"></span>Healthy';
                        } else if (data.data.status === 'warning') {
                            badge.className = 'c-status-badge c-status-badge--warning';
                            badge.innerHTML = '<span class="status-dot"></span>Warning';
                        } else {
                            badge.className = 'c-status-badge c-status-badge--error';
                            badge.innerHTML = '<span class="status-dot"></span>Error';
                        }
                        
                        // Update last execution
                        lastExec.textContent = data.data.last_poll || '—';
                        
                        // Update execution count (calculate from success rate if available)
                        if (data.data.emails_today !== undefined) {
                            execCount.textContent = data.data.emails_today;
                        }
                    }
                }
            } catch (error) {
                console.error('[Cron] Failed to load status:', error);
            }
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', loadCronStatus);
        } else {
            loadCronStatus();
        }
        
        // Auto-refresh every 30 seconds
        setInterval(loadCronStatus, 30000);
        <?php
    }
];

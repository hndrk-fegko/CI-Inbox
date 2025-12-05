<?php
/**
 * Admin Tab Module: Cron Monitor
 * 
 * Provides:
 * - Webcron service status monitoring
 * - Execution history with pagination
 * - Performance statistics
 * - Manual trigger capability
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
                    <span class="c-info-row__label">Emails Today</span>
                    <span id="cron-executions-count" class="c-info-row__value">0</span>
                </div>
            </div>
        </div>
        <?php
    },
    
    'content' => function() {
        ?>
        <div style="margin-bottom: 2rem;">
            <h3 style="margin: 0 0 0.5rem 0; font-size: 1.25rem; font-weight: 600;">Cron Job Monitoring</h3>
            <p style="margin: 0; color: #666; font-size: 0.875rem;">Monitor webhook polling service and execution history.</p>
        </div>
        
        <!-- Info Box -->
        <div style="background: #E3F2FD; border-left: 4px solid #2196F3; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="#1976D2" style="flex-shrink: 0; margin-top: 2px;">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <strong style="color: #1565C0;">About Webcron Polling</strong>
                    <p style="margin: 0.5rem 0 0 0; color: #1976D2; font-size: 0.875rem;">
                        The webcron service polls your IMAP accounts at regular intervals to fetch new emails. 
                        It's triggered by an external cron job calling the webhook endpoint.
                    </p>
                    <p style="margin: 0.5rem 0 0 0; color: #1976D2; font-size: 0.875rem;">
                        <strong>Health Thresholds (for minutely cron):</strong><br>
                        🟢 <strong>Healthy:</strong> &gt;55 executions/hour • 
                        🟡 <strong>Delayed:</strong> &lt;30 executions/hour • 
                        🔴 <strong>Stale:</strong> &lt;1 execution/hour
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Alert Container -->
        <div id="cron-config-alert" style="margin-bottom: 1rem;"></div>
        
        <!-- Status Cards Row -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
            <div style="background: white; border-radius: 12px; padding: 1.25rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                <div style="font-size: 0.875rem; color: #666; margin-bottom: 0.5rem;">Service Status</div>
                <div id="cron-detail-status" style="font-size: 1.5rem; font-weight: 600; color: #666;">—</div>
            </div>
            <div style="background: white; border-radius: 12px; padding: 1.25rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                <div style="font-size: 0.875rem; color: #666; margin-bottom: 0.5rem;">Last Poll</div>
                <div id="cron-detail-last" style="font-size: 1.5rem; font-weight: 600; color: #333;">—</div>
            </div>
            <div style="background: white; border-radius: 12px; padding: 1.25rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                <div style="font-size: 0.875rem; color: #666; margin-bottom: 0.5rem;">Executions/Hour</div>
                <div id="cron-detail-success" style="font-size: 1.5rem; font-weight: 600; color: #4CAF50;">—</div>
            </div>
            <div style="background: white; border-radius: 12px; padding: 1.25rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                <div style="font-size: 0.875rem; color: #666; margin-bottom: 0.5rem;">Emails Today</div>
                <div id="cron-detail-emails" style="font-size: 1.5rem; font-weight: 600; color: #2196F3;">—</div>
            </div>
        </div>
        
        <!-- Execution History -->
        <div style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h4 style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                    </svg>
                    Execution History
                </h4>
                <button type="button" id="cron-refresh-btn" class="c-button c-button--secondary" style="font-size: 0.875rem;">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                        <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                    </svg>
                    Refresh
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table" id="cron-history-table" style="margin: 0;">
                    <thead style="background: #f8f9fa;">
                        <tr>
                            <th style="font-weight: 600; color: #666; text-transform: uppercase; font-size: 0.75rem; padding: 0.75rem 1rem; border: none;">Timestamp</th>
                            <th style="font-weight: 600; color: #666; text-transform: uppercase; font-size: 0.75rem; padding: 0.75rem 1rem; border: none;">Accounts</th>
                            <th style="font-weight: 600; color: #666; text-transform: uppercase; font-size: 0.75rem; padding: 0.75rem 1rem; border: none;">New Emails</th>
                            <th style="font-weight: 600; color: #666; text-transform: uppercase; font-size: 0.75rem; padding: 0.75rem 1rem; border: none;">Duration</th>
                            <th style="font-weight: 600; color: #666; text-transform: uppercase; font-size: 0.75rem; padding: 0.75rem 1rem; border: none;">Status</th>
                        </tr>
                    </thead>
                    <tbody id="cron-history-body">
                        <tr>
                            <td colspan="5" style="padding: 2rem; text-align: center; color: #666;">
                                Loading execution history...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div id="cron-pagination" style="display: none; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.875rem; color: #666;">
                        Showing <span id="cron-page-info">0-0</span> of <span id="cron-total-count">0</span> executions
                    </span>
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="button" id="cron-prev-btn" class="c-button c-button--secondary" style="font-size: 0.875rem; padding: 0.375rem 0.75rem;" disabled>← Previous</button>
                        <button type="button" id="cron-next-btn" class="c-button c-button--secondary" style="font-size: 0.875rem; padding: 0.375rem 0.75rem;" disabled>Next →</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistics -->
        <div style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h4 style="margin: 0 0 1rem 0; display: flex; align-items: center; gap: 0.5rem;">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
                </svg>
                Performance Statistics
            </h4>
            
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                <div style="background: #f5f5f5; padding: 1rem; border-radius: 8px;">
                    <div style="font-size: 0.875rem; color: #666; margin-bottom: 0.25rem;">Avg Duration</div>
                    <div id="cron-stat-avg-duration" style="font-size: 1.25rem; font-weight: 600;">—</div>
                </div>
                <div style="background: #f5f5f5; padding: 1rem; border-radius: 8px;">
                    <div style="font-size: 0.875rem; color: #666; margin-bottom: 0.25rem;">Total Polls (7 days)</div>
                    <div id="cron-stat-total-polls" style="font-size: 1.25rem; font-weight: 600;">—</div>
                </div>
                <div style="background: #f5f5f5; padding: 1rem; border-radius: 8px;">
                    <div style="font-size: 0.875rem; color: #666; margin-bottom: 0.25rem;">Total Emails (7 days)</div>
                    <div id="cron-stat-total-emails" style="font-size: 1.25rem; font-weight: 600;">—</div>
                </div>
            </div>
        </div>
        
        <!-- Webhook Configuration -->
        <div style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h4 style="margin: 0 0 1rem 0; display: flex; align-items: center; gap: 0.5rem;">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd"/>
                </svg>
                Webhook Configuration
            </h4>
            
            <p style="margin: 0 0 1rem 0; color: #666; font-size: 0.875rem;">
                Configure your external cron service to call this webhook URL at regular intervals (recommended: every minute).
            </p>
            
            <!-- Webhook URL -->
            <div class="c-input-group" style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #333;">Webhook URL</label>
                <div style="display: flex; gap: 0.5rem;">
                    <input type="text" id="cron-webhook-url" class="c-input" readonly 
                           style="flex: 1; font-family: monospace; font-size: 0.875rem; background: #f5f5f5;"
                           value="Loading...">
                    <button type="button" id="cron-copy-url-btn" class="c-button c-button--secondary" style="white-space: nowrap;">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                            <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"/>
                            <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z"/>
                        </svg>
                        Copy
                    </button>
                </div>
                <small style="color: #666; display: block; margin-top: 0.25rem;">
                    Use this URL in your cron service (cron-job.org, Uptime Robot, etc.)
                </small>
            </div>
            
            <!-- Secret Token -->
            <div class="c-input-group" style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #333;">Secret Token</label>
                <div style="display: flex; gap: 0.5rem;">
                    <input type="text" id="cron-secret-token" class="c-input" readonly 
                           style="flex: 1; font-family: monospace; font-size: 0.875rem; background: #f5f5f5;"
                           value="Loading...">
                    <button type="button" id="cron-copy-token-btn" class="c-button c-button--secondary" style="white-space: nowrap;">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                            <path d="M8 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"/>
                            <path d="M6 3a2 2 0 00-2 2v11a2 2 0 002 2h8a2 2 0 002-2V5a2 2 0 00-2-2 3 3 0 01-3 3H9a3 3 0 01-3-3z"/>
                        </svg>
                        Copy
                    </button>
                </div>
                <small style="color: #666; display: block; margin-top: 0.25rem;">
                    This token authenticates the webhook request
                </small>
            </div>
            
            <!-- Regenerate Token -->
            <div style="background: #FFF3E0; border-left: 4px solid #FF9800; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem;">
                <strong style="color: #E65100;">⚠️ Security Notice:</strong>
                <p style="margin: 0.25rem 0 0 0; color: #E65100; font-size: 0.875rem;">
                    Regenerating the token will invalidate the current webhook URL. You'll need to update your external cron service with the new URL.
                </p>
            </div>
            
            <button type="button" id="cron-regenerate-token-btn" class="c-button c-button--danger">
                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                </svg>
                Regenerate Token
            </button>
        </div>
        
        <!-- Regenerate Token Confirmation Modal -->
        <div class="c-modal" id="cron-regenerate-modal">
            <div class="c-modal__content" style="max-width: 450px;">
                <div class="c-modal__header">
                    <h2>Regenerate Webhook Token</h2>
                    <button class="c-modal__close" id="cron-regenerate-close">&times;</button>
                </div>
                <div class="c-modal__body">
                    <p style="color: #666;">Are you sure you want to regenerate the webhook token?</p>
                    <p style="color: #f44336;"><strong>This will invalidate the current webhook URL.</strong></p>
                    <p style="color: #666; font-size: 0.875rem;">You will need to update your external cron service with the new URL after regeneration.</p>
                </div>
                <div class="c-modal__footer">
                    <button type="button" class="c-button c-button--secondary" id="cron-regenerate-cancel">Cancel</button>
                    <button type="button" class="c-button c-button--danger" id="cron-regenerate-confirm">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                        </svg>
                        Regenerate Token
                    </button>
                </div>
            </div>
        </div>
        <?php
    },
    
    'script' => function() {
        ?>
        // Cron Module State
        const CronModule = {
            currentPage: 1,
            perPage: 10,
            totalCount: 0,
            webhookToken: null,
            
            init() {
                console.log('[Cron] Initializing module...');
                this.loadStatus();
                this.loadHistory();
                this.loadStatistics();
                this.loadWebhookConfig();
                this.bindEvents();
                
                // Auto-refresh every 30 seconds
                setInterval(() => this.loadStatus(), 30000);
            },
            
            bindEvents() {
                const refreshBtn = document.getElementById('cron-refresh-btn');
                if (refreshBtn) {
                    refreshBtn.addEventListener('click', () => {
                        this.loadStatus();
                        this.loadHistory();
                        this.loadStatistics();
                    });
                }
                
                const prevBtn = document.getElementById('cron-prev-btn');
                const nextBtn = document.getElementById('cron-next-btn');
                
                if (prevBtn) {
                    prevBtn.addEventListener('click', () => {
                        if (this.currentPage > 1) {
                            this.currentPage--;
                            this.loadHistory();
                        }
                    });
                }
                
                if (nextBtn) {
                    nextBtn.addEventListener('click', () => {
                        const totalPages = Math.ceil(this.totalCount / this.perPage);
                        if (this.currentPage < totalPages) {
                            this.currentPage++;
                            this.loadHistory();
                        }
                    });
                }
                
                // Webhook configuration buttons
                const copyUrlBtn = document.getElementById('cron-copy-url-btn');
                const copyTokenBtn = document.getElementById('cron-copy-token-btn');
                const regenerateBtn = document.getElementById('cron-regenerate-token-btn');
                
                if (copyUrlBtn) {
                    copyUrlBtn.addEventListener('click', () => this.copyToClipboard('cron-webhook-url', 'Webhook URL copied!'));
                }
                
                if (copyTokenBtn) {
                    copyTokenBtn.addEventListener('click', () => this.copyToClipboard('cron-secret-token', 'Token copied!'));
                }
                
                if (regenerateBtn) {
                    regenerateBtn.addEventListener('click', () => this.openRegenerateModal());
                }
                
                // Regenerate modal
                const regenerateClose = document.getElementById('cron-regenerate-close');
                const regenerateCancel = document.getElementById('cron-regenerate-cancel');
                const regenerateConfirm = document.getElementById('cron-regenerate-confirm');
                
                if (regenerateClose) regenerateClose.addEventListener('click', () => this.closeRegenerateModal());
                if (regenerateCancel) regenerateCancel.addEventListener('click', () => this.closeRegenerateModal());
                if (regenerateConfirm) regenerateConfirm.addEventListener('click', () => this.regenerateToken());
            },
            
            async loadWebhookConfig() {
                try {
                    const response = await fetch('/api/admin/cron/webhook');
                    const data = await response.json();
                    
                    if (data.success && data.data) {
                        this.webhookToken = data.data.token;
                        this.updateWebhookDisplay(data.data);
                    } else {
                        // Fallback: Generate URL with placeholder
                        this.updateWebhookDisplay({
                            token: 'Not configured',
                            url: window.location.origin + '/api/webcron/poll?token=YOUR_TOKEN'
                        });
                    }
                } catch (error) {
                    console.error('[Cron] Failed to load webhook config:', error);
                    // Show placeholder on error
                    document.getElementById('cron-webhook-url').value = 'Failed to load';
                    document.getElementById('cron-secret-token').value = 'Failed to load';
                }
            },
            
            updateWebhookDisplay(data) {
                const urlInput = document.getElementById('cron-webhook-url');
                const tokenInput = document.getElementById('cron-secret-token');
                
                if (urlInput) {
                    urlInput.value = data.url || window.location.origin + '/api/webcron/poll?token=' + (data.token || 'YOUR_TOKEN');
                }
                
                if (tokenInput) {
                    tokenInput.value = data.token || 'Not configured';
                }
            },
            
            copyToClipboard(inputId, successMessage) {
                const input = document.getElementById(inputId);
                if (!input) return;
                
                navigator.clipboard.writeText(input.value).then(() => {
                    this.showAlert('cron-config-alert', successMessage, 'success');
                }).catch(err => {
                    console.error('[Cron] Copy failed:', err);
                    // Fallback for older browsers
                    input.select();
                    document.execCommand('copy');
                    this.showAlert('cron-config-alert', successMessage, 'success');
                });
            },
            
            openRegenerateModal() {
                document.getElementById('cron-regenerate-modal').classList.add('show');
            },
            
            closeRegenerateModal() {
                document.getElementById('cron-regenerate-modal').classList.remove('show');
            },
            
            async regenerateToken() {
                const confirmBtn = document.getElementById('cron-regenerate-confirm');
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Regenerating...';
                
                try {
                    const response = await fetch('/api/admin/cron/webhook/regenerate', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' }
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.closeRegenerateModal();
                        this.webhookToken = data.data.token;
                        this.updateWebhookDisplay(data.data);
                        this.showAlert('cron-config-alert', 'Token regenerated successfully! Update your cron service with the new URL.', 'success');
                    } else {
                        this.showAlert('cron-config-alert', data.error || 'Failed to regenerate token', 'error');
                    }
                } catch (error) {
                    console.error('[Cron] Regenerate failed:', error);
                    this.showAlert('cron-config-alert', 'Failed to regenerate token: ' + error.message, 'error');
                } finally {
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;"><path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/></svg> Regenerate Token';
                }
            },
            
            async loadStatus() {
                try {
                    const response = await fetch('/api/system/cron-status');
                    const data = await response.json();
                    
                    if (data.success && data.data) {
                        this.updateStatusDisplay(data.data);
                    }
                } catch (error) {
                    console.error('[Cron] Failed to load status:', error);
                }
            },
            
            updateStatusDisplay(data) {
                // Card status
                const badge = document.getElementById('cron-status-badge');
                const lastExec = document.getElementById('cron-last-execution');
                const execCount = document.getElementById('cron-executions-count');
                
                // Determine status based on executions in last hour
                // For minutely cron: Healthy >55, Delayed <30, Stale <1
                let status = 'warning';
                let statusText = 'Unknown';
                
                const execsLastHour = data.executions_last_hour ?? null;
                
                if (execsLastHour !== null) {
                    if (execsLastHour > 55) {
                        status = 'success';
                        statusText = 'Healthy';
                    } else if (execsLastHour >= 1 && execsLastHour < 30) {
                        status = 'warning';
                        statusText = 'Delayed';
                    } else if (execsLastHour < 1) {
                        status = 'error';
                        statusText = 'Stale';
                    } else {
                        // 30-55 range - still acceptable but borderline
                        status = 'warning';
                        statusText = 'Degraded';
                    }
                } else if (data.minutes_ago !== null) {
                    // Fallback to time-based if executions_last_hour not available
                    if (data.minutes_ago <= 5) {
                        status = 'success';
                        statusText = 'Healthy';
                    } else if (data.minutes_ago <= 30) {
                        status = 'warning';
                        statusText = 'Delayed';
                    } else {
                        status = 'error';
                        statusText = 'Stale';
                    }
                } else if (data.status === 'never_run') {
                    status = 'error';
                    statusText = 'Never Run';
                }
                
                badge.className = `c-status-badge c-status-badge--${status}`;
                badge.innerHTML = `<span class="status-dot"></span>${statusText}`;
                
                // Last execution
                if (data.last_poll_at) {
                    lastExec.textContent = data.minutes_ago !== null ? 
                        `${data.minutes_ago} min ago` : data.last_poll_at;
                } else {
                    lastExec.textContent = 'Never';
                }
                
                // Emails today
                execCount.textContent = data.emails_today || '0';
                
                // Detail cards
                const detailStatus = document.getElementById('cron-detail-status');
                const detailLast = document.getElementById('cron-detail-last');
                const detailSuccess = document.getElementById('cron-detail-success');
                const detailEmails = document.getElementById('cron-detail-emails');
                
                if (detailStatus) {
                    detailStatus.textContent = statusText;
                    detailStatus.style.color = status === 'success' ? '#4CAF50' : 
                                               status === 'warning' ? '#FF9800' : '#f44336';
                }
                
                if (detailLast) {
                    detailLast.textContent = data.minutes_ago !== null ? 
                        `${data.minutes_ago} min` : 'Never';
                }
                
                if (detailSuccess) {
                    // Show executions in last hour if available
                    if (execsLastHour !== null) {
                        detailSuccess.textContent = `${execsLastHour}/60`;
                    } else if (data.success_rate !== undefined) {
                        detailSuccess.textContent = `${data.success_rate}%`;
                    } else {
                        detailSuccess.textContent = '—';
                    }
                }
                
                if (detailEmails) {
                    detailEmails.textContent = data.emails_today || '0';
                }
            },
            
            async loadHistory() {
                const tbody = document.getElementById('cron-history-body');
                tbody.innerHTML = '<tr><td colspan="5" style="padding: 2rem; text-align: center; color: #666;">Loading...</td></tr>';
                
                try {
                    const response = await fetch(`/api/admin/cron/history?page=${this.currentPage}&per_page=${this.perPage}`);
                    const data = await response.json();
                    
                    if (data.success && data.data) {
                        this.totalCount = data.meta?.total || data.data.length;
                        this.renderHistory(data.data);
                        this.updatePagination();
                    } else {
                        tbody.innerHTML = '<tr><td colspan="5" style="padding: 2rem; text-align: center; color: #666;">No execution history available</td></tr>';
                    }
                } catch (error) {
                    console.error('[Cron] Failed to load history:', error);
                    tbody.innerHTML = '<tr><td colspan="5" style="padding: 2rem; text-align: center; color: #f44336;">Failed to load history</td></tr>';
                }
            },
            
            renderHistory(executions) {
                const tbody = document.getElementById('cron-history-body');
                
                if (!executions || executions.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" style="padding: 2rem; text-align: center; color: #666;">No execution history available</td></tr>';
                    return;
                }
                
                tbody.innerHTML = executions.map(exec => {
                    const statusClass = exec.status === 'success' ? 'c-status-badge--success' : 
                                       exec.status === 'error' ? 'c-status-badge--error' : 'c-status-badge--warning';
                    const statusText = exec.status.charAt(0).toUpperCase() + exec.status.slice(1);
                    
                    return `
                        <tr>
                            <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #eee; font-size: 0.875rem;">
                                ${this.escapeHtml(exec.started_at || '—')}
                            </td>
                            <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #eee; font-size: 0.875rem;">
                                ${exec.accounts_polled || 0}
                            </td>
                            <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #eee; font-size: 0.875rem;">
                                ${exec.emails_fetched || 0}
                            </td>
                            <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #eee; font-size: 0.875rem;">
                                ${exec.duration_ms ? `${exec.duration_ms}ms` : '—'}
                            </td>
                            <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #eee;">
                                <span class="c-status-badge ${statusClass}" style="font-size: 0.75rem;">
                                    <span class="status-dot"></span>${statusText}
                                </span>
                            </td>
                        </tr>
                    `;
                }).join('');
            },
            
            updatePagination() {
                const pagination = document.getElementById('cron-pagination');
                const pageInfo = document.getElementById('cron-page-info');
                const totalCountEl = document.getElementById('cron-total-count');
                const prevBtn = document.getElementById('cron-prev-btn');
                const nextBtn = document.getElementById('cron-next-btn');
                
                const totalPages = Math.ceil(this.totalCount / this.perPage);
                const start = (this.currentPage - 1) * this.perPage + 1;
                const end = Math.min(this.currentPage * this.perPage, this.totalCount);
                
                if (this.totalCount > 0) {
                    pagination.style.display = 'block';
                    pageInfo.textContent = `${start}-${end}`;
                    totalCountEl.textContent = this.totalCount;
                    prevBtn.disabled = this.currentPage <= 1;
                    nextBtn.disabled = this.currentPage >= totalPages;
                } else {
                    pagination.style.display = 'none';
                }
            },
            
            async loadStatistics() {
                try {
                    const response = await fetch('/api/admin/cron/statistics');
                    const data = await response.json();
                    
                    if (data.success && data.data) {
                        document.getElementById('cron-stat-avg-duration').textContent = 
                            data.data.avg_duration_ms ? `${Math.round(data.data.avg_duration_ms)}ms` : '—';
                        document.getElementById('cron-stat-total-polls').textContent = 
                            data.data.total_polls || '0';
                        document.getElementById('cron-stat-total-emails').textContent = 
                            data.data.total_emails || '0';
                    }
                } catch (error) {
                    console.error('[Cron] Failed to load statistics:', error);
                }
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
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        };
        
        // Initialize on DOMContentLoaded or immediately if already loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => CronModule.init());
        } else {
            CronModule.init();
        }
        <?php
    }
];

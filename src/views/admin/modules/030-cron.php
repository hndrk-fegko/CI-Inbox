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
                        It's triggered by an external cron job calling the webhook endpoint. A healthy service 
                        should run every 5-15 minutes.
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
                <div style="font-size: 0.875rem; color: #666; margin-bottom: 0.5rem;">Success Rate (24h)</div>
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
        <?php
    },
    
    'script' => function() {
        ?>
        // Cron Module State
        const CronModule = {
            currentPage: 1,
            perPage: 10,
            totalCount: 0,
            
            init() {
                console.log('[Cron] Initializing module...');
                this.loadStatus();
                this.loadHistory();
                this.loadStatistics();
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
                
                // Determine status
                let status = 'warning';
                let statusText = 'Unknown';
                
                if (data.minutes_ago !== null) {
                    if (data.minutes_ago <= 20) {
                        status = 'success';
                        statusText = 'Healthy';
                    } else if (data.minutes_ago <= 60) {
                        status = 'warning';
                        statusText = 'Delayed';
                    } else {
                        status = 'error';
                        statusText = 'Stale';
                    }
                } else if (data.status === 'never_run') {
                    status = 'warning';
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
                    detailSuccess.textContent = data.success_rate !== undefined ? 
                        `${data.success_rate}%` : '—';
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

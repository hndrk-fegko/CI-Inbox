<?php
/**
 * Health Module for Admin Settings
 * 
 * System health monitoring, automated tests, and self-healing.
 * Priority 90 - After Logger, before custom modules.
 * 
 * Features:
 * - Automated health checks (cron-based)
 * - Test reports summary
 * - Self-healing actions
 * - Status dashboard
 */

declare(strict_types=1);

return [
    'id' => 'health',
    'title' => 'System Health',
    'priority' => 90,
    'icon' => '<path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>',
    
    /**
     * Dashboard Card
     */
    'card' => function() {
        return <<<HTML
        <div class="c-settings-card" onclick="switchToTab('health')">
            <div class="c-settings-card__icon c-settings-card__icon--success">
                <svg width="24" height="24" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="c-settings-card__content">
                <h3 class="c-settings-card__title">System Health</h3>
                <p class="c-settings-card__description">Automated monitoring & self-healing</p>
            </div>
            <div class="c-settings-card__status" id="health-card-status">
                <span class="c-badge c-badge--success">Loading...</span>
            </div>
        </div>
HTML;
    },
    
    /**
     * Full Tab Content
     */
    'content' => function() {
        return <<<'HTML'
        <div class="c-settings-section">
            <div class="c-settings-section__header">
                <h2 class="c-settings-section__title">System Health Overview</h2>
                <button class="c-button c-button--primary" onclick="HealthModule.runAllTests()">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                    </svg>
                    Run All Tests
                </button>
            </div>
            
            <!-- Overall Status -->
            <div class="c-info-box c-info-box--info" style="margin-bottom: 1.5rem;">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <strong>Health Monitoring</strong><br>
                    Automated tests run periodically to detect issues. Self-healing actions can fix common problems automatically.
                </div>
            </div>
            
            <!-- Status Cards Grid -->
            <div class="c-health-grid" id="health-status-grid">
                <!-- Populated by JavaScript -->
                <div class="c-health-card c-health-card--loading">
                    <div class="c-health-card__icon">⏳</div>
                    <div class="c-health-card__content">Loading status...</div>
                </div>
            </div>
        </div>
        
        <!-- Automated Tests Schedule -->
        <div class="c-settings-section">
            <div class="c-settings-section__header">
                <h2 class="c-settings-section__title">Automated Test Schedule</h2>
            </div>
            
            <form id="health-schedule-form" class="c-form">
                <div class="c-form-grid">
                    <div class="c-input-group">
                        <label class="c-input-group__label">
                            <input type="checkbox" id="health-enabled" name="enabled">
                            Enable Automated Health Checks
                        </label>
                    </div>
                    
                    <div class="c-input-group">
                        <label class="c-input-group__label" for="health-interval">Check Interval</label>
                        <select id="health-interval" name="interval" class="c-select">
                            <option value="5">Every 5 minutes</option>
                            <option value="15" selected>Every 15 minutes</option>
                            <option value="30">Every 30 minutes</option>
                            <option value="60">Every hour</option>
                            <option value="360">Every 6 hours</option>
                            <option value="1440">Daily</option>
                        </select>
                    </div>
                    
                    <div class="c-input-group">
                        <label class="c-input-group__label">
                            <input type="checkbox" id="health-self-heal" name="self_heal" checked>
                            Enable Self-Healing (auto-fix detected issues)
                        </label>
                    </div>
                    
                    <div class="c-input-group">
                        <label class="c-input-group__label">
                            <input type="checkbox" id="health-notify" name="notify_admin" checked>
                            Notify Admin on Critical Issues
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="c-button c-button--primary">Save Schedule</button>
            </form>
        </div>
        
        <!-- Test Selection -->
        <div class="c-settings-section">
            <div class="c-settings-section__header">
                <h2 class="c-settings-section__title">Health Tests</h2>
            </div>
            
            <div class="c-health-tests" id="health-tests-list">
                <!-- Database Tests -->
                <div class="c-health-test">
                    <div class="c-health-test__header">
                        <label class="c-health-test__label">
                            <input type="checkbox" checked data-test="database">
                            <span class="c-health-test__name">Database Connection</span>
                        </label>
                        <span class="c-health-test__status" id="test-database-status">—</span>
                    </div>
                    <p class="c-health-test__description">Tests database connectivity and query execution time.</p>
                    <div class="c-health-test__actions">
                        <button class="c-button c-button--sm" onclick="HealthModule.runTest('database')">Run Test</button>
                    </div>
                </div>
                
                <!-- IMAP Tests -->
                <div class="c-health-test">
                    <div class="c-health-test__header">
                        <label class="c-health-test__label">
                            <input type="checkbox" checked data-test="imap">
                            <span class="c-health-test__name">IMAP Connectivity</span>
                        </label>
                        <span class="c-health-test__status" id="test-imap-status">—</span>
                    </div>
                    <p class="c-health-test__description">Tests connection to all configured IMAP accounts.</p>
                    <div class="c-health-test__actions">
                        <button class="c-button c-button--sm" onclick="HealthModule.runTest('imap')">Run Test</button>
                    </div>
                </div>
                
                <!-- SMTP Tests -->
                <div class="c-health-test">
                    <div class="c-health-test__header">
                        <label class="c-health-test__label">
                            <input type="checkbox" checked data-test="smtp">
                            <span class="c-health-test__name">SMTP Connectivity</span>
                        </label>
                        <span class="c-health-test__status" id="test-smtp-status">—</span>
                    </div>
                    <p class="c-health-test__description">Tests outgoing email server connection.</p>
                    <div class="c-health-test__actions">
                        <button class="c-button c-button--sm" onclick="HealthModule.runTest('smtp')">Run Test</button>
                    </div>
                </div>
                
                <!-- Disk Space Tests -->
                <div class="c-health-test">
                    <div class="c-health-test__header">
                        <label class="c-health-test__label">
                            <input type="checkbox" checked data-test="disk">
                            <span class="c-health-test__name">Disk Space</span>
                        </label>
                        <span class="c-health-test__status" id="test-disk-status">—</span>
                    </div>
                    <p class="c-health-test__description">Checks available disk space and log directory sizes.</p>
                    <div class="c-health-test__actions">
                        <button class="c-button c-button--sm" onclick="HealthModule.runTest('disk')">Run Test</button>
                        <button class="c-button c-button--sm c-button--warning" onclick="HealthModule.selfHeal('disk')">Clean Logs</button>
                    </div>
                </div>
                
                <!-- Cron Tests -->
                <div class="c-health-test">
                    <div class="c-health-test__header">
                        <label class="c-health-test__label">
                            <input type="checkbox" checked data-test="cron">
                            <span class="c-health-test__name">Cron/Webcron Status</span>
                        </label>
                        <span class="c-health-test__status" id="test-cron-status">—</span>
                    </div>
                    <p class="c-health-test__description">Verifies cron job execution and timing.</p>
                    <div class="c-health-test__actions">
                        <button class="c-button c-button--sm" onclick="HealthModule.runTest('cron')">Run Test</button>
                    </div>
                </div>
                
                <!-- Queue Tests -->
                <div class="c-health-test">
                    <div class="c-health-test__header">
                        <label class="c-health-test__label">
                            <input type="checkbox" checked data-test="queue">
                            <span class="c-health-test__name">Email Queue</span>
                        </label>
                        <span class="c-health-test__status" id="test-queue-status">—</span>
                    </div>
                    <p class="c-health-test__description">Checks for stuck or failed email processing jobs.</p>
                    <div class="c-health-test__actions">
                        <button class="c-button c-button--sm" onclick="HealthModule.runTest('queue')">Run Test</button>
                        <button class="c-button c-button--sm c-button--warning" onclick="HealthModule.selfHeal('queue')">Retry Failed</button>
                    </div>
                </div>
                
                <!-- Session/Auth Tests -->
                <div class="c-health-test">
                    <div class="c-health-test__header">
                        <label class="c-health-test__label">
                            <input type="checkbox" checked data-test="sessions">
                            <span class="c-health-test__name">Sessions & Auth</span>
                        </label>
                        <span class="c-health-test__status" id="test-sessions-status">—</span>
                    </div>
                    <p class="c-health-test__description">Validates session storage and authentication system.</p>
                    <div class="c-health-test__actions">
                        <button class="c-button c-button--sm" onclick="HealthModule.runTest('sessions')">Run Test</button>
                        <button class="c-button c-button--sm c-button--warning" onclick="HealthModule.selfHeal('sessions')">Clear Old Sessions</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Test Reports -->
        <div class="c-settings-section">
            <div class="c-settings-section__header">
                <h2 class="c-settings-section__title">Recent Test Reports</h2>
                <button class="c-button c-button--secondary" onclick="HealthModule.exportReport()">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                    Export Report
                </button>
            </div>
            
            <div class="c-health-reports" id="health-reports-list">
                <div class="c-empty-state">
                    <p>No test reports yet. Run tests to generate reports.</p>
                </div>
            </div>
        </div>
        
        <!-- Self-Healing Log -->
        <div class="c-settings-section">
            <div class="c-settings-section__header">
                <h2 class="c-settings-section__title">Self-Healing Actions Log</h2>
                <button class="c-button c-button--secondary" onclick="HealthModule.clearHealingLog()">Clear Log</button>
            </div>
            
            <div class="c-health-log" id="self-healing-log">
                <div class="c-empty-state">
                    <p>No self-healing actions recorded.</p>
                </div>
            </div>
        </div>
        
        <style>
            .c-health-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1rem;
                margin-bottom: 1.5rem;
            }
            
            .c-health-card {
                background: white;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 1.25rem;
                display: flex;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .c-health-card--healthy {
                border-left: 4px solid #10b981;
            }
            
            .c-health-card--warning {
                border-left: 4px solid #f59e0b;
            }
            
            .c-health-card--critical {
                border-left: 4px solid #ef4444;
            }
            
            .c-health-card--loading {
                opacity: 0.6;
            }
            
            .c-health-card__icon {
                font-size: 1.5rem;
                flex-shrink: 0;
            }
            
            .c-health-card__content {
                flex: 1;
            }
            
            .c-health-card__title {
                font-weight: 600;
                margin-bottom: 0.25rem;
            }
            
            .c-health-card__value {
                font-size: 1.5rem;
                font-weight: 700;
                color: #111;
            }
            
            .c-health-card__subtext {
                font-size: 0.75rem;
                color: #666;
            }
            
            .c-health-tests {
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }
            
            .c-health-test {
                background: white;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 1rem;
            }
            
            .c-health-test__header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 0.5rem;
            }
            
            .c-health-test__label {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                cursor: pointer;
            }
            
            .c-health-test__name {
                font-weight: 600;
            }
            
            .c-health-test__status {
                padding: 0.25rem 0.75rem;
                border-radius: 9999px;
                font-size: 0.75rem;
                font-weight: 500;
            }
            
            .c-health-test__status--pass {
                background: #dcfce7;
                color: #166534;
            }
            
            .c-health-test__status--fail {
                background: #fee2e2;
                color: #991b1b;
            }
            
            .c-health-test__status--warning {
                background: #fef3c7;
                color: #92400e;
            }
            
            .c-health-test__status--running {
                background: #dbeafe;
                color: #1e40af;
            }
            
            .c-health-test__description {
                color: #666;
                font-size: 0.875rem;
                margin-bottom: 0.75rem;
            }
            
            .c-health-test__actions {
                display: flex;
                gap: 0.5rem;
            }
            
            .c-health-reports {
                max-height: 400px;
                overflow-y: auto;
            }
            
            .c-health-report {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.75rem 1rem;
                border-bottom: 1px solid #e5e7eb;
            }
            
            .c-health-report:last-child {
                border-bottom: none;
            }
            
            .c-health-report__time {
                color: #666;
                font-size: 0.875rem;
            }
            
            .c-health-report__summary {
                flex: 1;
                margin: 0 1rem;
            }
            
            .c-health-log {
                background: #f9fafb;
                border-radius: 8px;
                padding: 1rem;
                max-height: 300px;
                overflow-y: auto;
                font-family: monospace;
                font-size: 0.875rem;
            }
            
            .c-health-log__entry {
                padding: 0.5rem 0;
                border-bottom: 1px solid #e5e7eb;
            }
            
            .c-health-log__entry:last-child {
                border-bottom: none;
            }
            
            .c-health-log__entry--success {
                color: #166534;
            }
            
            .c-health-log__entry--error {
                color: #991b1b;
            }
        </style>
HTML;
    },
    
    /**
     * JavaScript
     */
    'script' => function() {
        return <<<'JS'
        const HealthModule = {
            API_BASE: '/api/admin/health',
            
            init: function() {
                console.log('[Health] Initializing module...');
                this.loadStatus();
                this.loadSchedule();
                this.loadReports();
                this.loadHealingLog();
                this.updateCardStatus();
                
                // Schedule form submission
                document.getElementById('health-schedule-form')?.addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.saveSchedule();
                });
            },
            
            updateCardStatus: async function() {
                try {
                    const response = await fetch(`${this.API_BASE}/summary`);
                    const result = await response.json();
                    
                    const cardStatus = document.getElementById('health-card-status');
                    if (!cardStatus) return;
                    
                    if (result.success) {
                        const status = result.data.overall_status || 'unknown';
                        const badgeClass = status === 'healthy' ? 'success' : 
                                          status === 'warning' ? 'warning' : 'danger';
                        cardStatus.innerHTML = `<span class="c-badge c-badge--${badgeClass}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
                    }
                } catch (error) {
                    console.error('[Health] Failed to update card status:', error);
                }
            },
            
            loadStatus: async function() {
                const grid = document.getElementById('health-status-grid');
                if (!grid) return;
                
                try {
                    const response = await fetch(`${this.API_BASE}/status`);
                    const result = await response.json();
                    
                    if (result.success) {
                        this.renderStatusGrid(result.data);
                    } else {
                        grid.innerHTML = '<div class="c-health-card"><div class="c-health-card__content">Failed to load status</div></div>';
                    }
                } catch (error) {
                    console.error('[Health] Failed to load status:', error);
                    grid.innerHTML = '<div class="c-health-card"><div class="c-health-card__content">Error loading status</div></div>';
                }
            },
            
            renderStatusGrid: function(data) {
                const grid = document.getElementById('health-status-grid');
                if (!grid) return;
                
                const items = [
                    { name: 'Database', status: data.database || 'unknown', icon: '🗄️' },
                    { name: 'IMAP', status: data.imap || 'unknown', icon: '📧' },
                    { name: 'SMTP', status: data.smtp || 'unknown', icon: '📤' },
                    { name: 'Disk', status: data.disk || 'unknown', icon: '💾', value: data.disk_free },
                    { name: 'Cron', status: data.cron || 'unknown', icon: '⏱️' },
                    { name: 'Queue', status: data.queue || 'unknown', icon: '📋', value: data.queue_size }
                ];
                
                grid.innerHTML = items.map(item => {
                    const statusClass = item.status === 'healthy' ? 'healthy' :
                                       item.status === 'warning' ? 'warning' : 'critical';
                    return `
                        <div class="c-health-card c-health-card--${statusClass}">
                            <div class="c-health-card__icon">${item.icon}</div>
                            <div class="c-health-card__content">
                                <div class="c-health-card__title">${item.name}</div>
                                ${item.value ? `<div class="c-health-card__value">${item.value}</div>` : ''}
                                <div class="c-health-card__subtext">${item.status}</div>
                            </div>
                        </div>
                    `;
                }).join('');
            },
            
            loadSchedule: async function() {
                try {
                    const response = await fetch(`${this.API_BASE}/schedule`);
                    const result = await response.json();
                    
                    if (result.success) {
                        document.getElementById('health-enabled').checked = result.data.enabled || false;
                        document.getElementById('health-interval').value = result.data.interval || '15';
                        document.getElementById('health-self-heal').checked = result.data.self_heal !== false;
                        document.getElementById('health-notify').checked = result.data.notify_admin !== false;
                    }
                } catch (error) {
                    console.error('[Health] Failed to load schedule:', error);
                }
            },
            
            saveSchedule: async function() {
                const data = {
                    enabled: document.getElementById('health-enabled').checked,
                    interval: parseInt(document.getElementById('health-interval').value),
                    self_heal: document.getElementById('health-self-heal').checked,
                    notify_admin: document.getElementById('health-notify').checked
                };
                
                try {
                    const response = await fetch(`${this.API_BASE}/schedule`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        alert('Schedule saved successfully!');
                    } else {
                        alert('Failed to save schedule: ' + (result.error || 'Unknown error'));
                    }
                } catch (error) {
                    alert('Error saving schedule');
                    console.error('[Health] Save schedule error:', error);
                }
            },
            
            runTest: async function(testName) {
                const statusEl = document.getElementById(`test-${testName}-status`);
                if (statusEl) {
                    statusEl.className = 'c-health-test__status c-health-test__status--running';
                    statusEl.textContent = 'Running...';
                }
                
                try {
                    const response = await fetch(`${this.API_BASE}/test/${testName}`, {
                        method: 'POST'
                    });
                    
                    const result = await response.json();
                    
                    if (statusEl) {
                        const status = result.success && result.data?.passed ? 'pass' : 'fail';
                        statusEl.className = `c-health-test__status c-health-test__status--${status}`;
                        statusEl.textContent = status === 'pass' ? '✓ Passed' : '✗ Failed';
                    }
                    
                    // Refresh status grid
                    this.loadStatus();
                    this.loadReports();
                    
                } catch (error) {
                    if (statusEl) {
                        statusEl.className = 'c-health-test__status c-health-test__status--fail';
                        statusEl.textContent = 'Error';
                    }
                    console.error(`[Health] Test ${testName} error:`, error);
                }
            },
            
            runAllTests: async function() {
                const tests = ['database', 'imap', 'smtp', 'disk', 'cron', 'queue', 'sessions'];
                
                for (const test of tests) {
                    await this.runTest(test);
                }
                
                alert('All tests completed!');
            },
            
            selfHeal: async function(healType) {
                if (!confirm(`Run self-healing for ${healType}? This may modify system state.`)) {
                    return;
                }
                
                try {
                    const response = await fetch(`${this.API_BASE}/heal/${healType}`, {
                        method: 'POST'
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        alert(`Self-healing completed: ${result.data?.message || 'Success'}`);
                        this.loadStatus();
                        this.loadHealingLog();
                    } else {
                        alert('Self-healing failed: ' + (result.error || 'Unknown error'));
                    }
                } catch (error) {
                    alert('Error during self-healing');
                    console.error('[Health] Self-heal error:', error);
                }
            },
            
            loadReports: async function() {
                const container = document.getElementById('health-reports-list');
                if (!container) return;
                
                try {
                    const response = await fetch(`${this.API_BASE}/reports`);
                    const result = await response.json();
                    
                    if (result.success && result.data?.reports?.length > 0) {
                        container.innerHTML = result.data.reports.map(report => `
                            <div class="c-health-report">
                                <span class="c-health-report__time">${report.timestamp}</span>
                                <span class="c-health-report__summary">
                                    ${report.passed}/${report.total} tests passed
                                </span>
                                <span class="c-badge c-badge--${report.passed === report.total ? 'success' : 'warning'}">
                                    ${report.passed === report.total ? 'All Passed' : 'Issues Found'}
                                </span>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = '<div class="c-empty-state"><p>No test reports yet.</p></div>';
                    }
                } catch (error) {
                    console.error('[Health] Failed to load reports:', error);
                }
            },
            
            loadHealingLog: async function() {
                const container = document.getElementById('self-healing-log');
                if (!container) return;
                
                try {
                    const response = await fetch(`${this.API_BASE}/healing-log`);
                    const result = await response.json();
                    
                    if (result.success && result.data?.entries?.length > 0) {
                        container.innerHTML = result.data.entries.map(entry => `
                            <div class="c-health-log__entry c-health-log__entry--${entry.success ? 'success' : 'error'}">
                                [${entry.timestamp}] ${entry.action}: ${entry.message}
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = '<div class="c-empty-state"><p>No self-healing actions recorded.</p></div>';
                    }
                } catch (error) {
                    console.error('[Health] Failed to load healing log:', error);
                }
            },
            
            clearHealingLog: async function() {
                if (!confirm('Clear the self-healing log?')) return;
                
                try {
                    await fetch(`${this.API_BASE}/healing-log`, { method: 'DELETE' });
                    this.loadHealingLog();
                } catch (error) {
                    console.error('[Health] Failed to clear log:', error);
                }
            },
            
            exportReport: async function() {
                try {
                    window.location.href = `${this.API_BASE}/export`;
                } catch (error) {
                    alert('Failed to export report');
                }
            }
        };
        
        // Initialize when tab becomes active
        if (document.getElementById('health-tab')?.classList.contains('is-active')) {
            HealthModule.init();
        }
JS;
    },
    
    /**
     * Help Content
     */
    'help' => function() {
        return <<<'HTML'
        <div class="help-section">
            <h3 class="help-section__title">System Health</h3>
            <div class="help-section__content">
                <p>Monitor system health and run automated diagnostics:</p>
                <ul>
                    <li><strong>Status Grid:</strong> Overview of all system components</li>
                    <li><strong>Automated Schedule:</strong> Run tests on a cron pattern</li>
                    <li><strong>Self-Healing:</strong> Auto-fix common issues</li>
                    <li><strong>Reports:</strong> Historical test results</li>
                </ul>
                
                <div class="help-tip">
                    <strong>Tip:</strong> Enable self-healing to automatically clean logs, retry failed jobs, and clear old sessions.
                </div>
                
                <div class="help-warning">
                    <strong>Warning:</strong> Self-healing actions modify system state. Review the log regularly.
                </div>
            </div>
        </div>
HTML;
    }
];

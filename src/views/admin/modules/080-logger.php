<?php
/**
 * Admin Tab Module: Logger Configuration
 * 
 * Provides:
 * - Log level configuration (debug, info, warning, error, critical)
 * - Real-time log viewer (HomeAssistant-style)
 * - Log file management
 * - Filter by level, module, time range
 * 
 * Auto-discovered by admin dashboard
 */

return [
    'id' => 'logger',
    'title' => 'Logger',
    'priority' => 80,
    'icon' => '<path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>',
    
    // Dashboard card content
    'card' => function() {
        ?>
        <div class="c-admin-card" data-module="logger" style="cursor: pointer;">
            <div class="c-admin-card__header">
                <div class="c-admin-card__icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                </div>
                <div>
                    <h3 class="c-admin-card__title">System Logger</h3>
                </div>
            </div>
            <p class="c-admin-card__description">Configure log levels, view real-time logs, and manage log files.</p>
            <div class="c-admin-card__content">
                <div class="c-info-row">
                    <span class="c-info-row__label">Current Level</span>
                    <span id="logger-level-badge" class="c-badge c-badge--info">INFO</span>
                </div>
                <div class="c-info-row">
                    <span class="c-info-row__label">Log Size</span>
                    <span id="logger-size" class="c-info-row__value">—</span>
                </div>
                <div class="c-info-row">
                    <span class="c-info-row__label">Latest Entry</span>
                    <span id="logger-latest" class="c-info-row__value">—</span>
                </div>
            </div>
        </div>
        <?php
    },
    
    // Tab content
    'content' => function() {
        ?>
        <!-- Header -->
        <div style="margin-bottom: 2rem;">
            <h3 style="margin: 0 0 0.5rem 0; font-size: 1.25rem; font-weight: 600;">System Logger</h3>
            <p style="margin: 0; color: #666; font-size: 0.875rem;">Configure logging behavior and view application logs in real-time.</p>
        </div>
        
        <!-- Info Box -->
        <div style="background: #E3F2FD; border-left: 4px solid #2196F3; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="#1976D2" style="flex-shrink: 0; margin-top: 2px;">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <strong style="color: #1565C0;">About Logging</strong>
                    <p style="margin: 0.5rem 0 0 0; color: #1976D2; font-size: 0.875rem;">
                        Logs record application events, errors, and debugging information. Lower log levels 
                        include all higher levels. DEBUG mode generates detailed output useful for troubleshooting 
                        but can impact performance.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Alert Container -->
        <div id="logger-alert" style="margin-bottom: 1rem;"></div>
        
        <!-- Log Level Configuration -->
        <div style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 1.5rem;">
            <h4 style="margin-top: 0; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                </svg>
                Log Level Configuration
            </h4>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; color: #333;">
                    Minimum Log Level
                </label>
                <select id="logger-level-select" class="c-input" style="width: 100%; max-width: 300px;">
                    <option value="debug">DEBUG - All messages (development)</option>
                    <option value="info" selected>INFO - Normal operations (recommended)</option>
                    <option value="warning">WARNING - Warnings and errors only</option>
                    <option value="error">ERROR - Errors and critical only</option>
                    <option value="critical">CRITICAL - Critical errors only</option>
                </select>
                <p style="margin: 0.5rem 0 0 0; color: #666; font-size: 0.875rem;">
                    Lower levels include all higher levels (e.g., INFO includes WARNING, ERROR, CRITICAL)
                </p>
            </div>
            
            <div style="background: #FFF9C4; border-left: 4px solid #FBC02D; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                <strong style="color: #F57F17;">⚠️ Performance Impact:</strong>
                <p style="margin: 0.5rem 0 0 0; color: #666; font-size: 0.875rem;">
                    DEBUG level logs every operation and can generate large log files. 
                    Use only for troubleshooting. INFO is recommended for production.
                </p>
            </div>
            
            <button id="save-log-level" class="c-button c-button--primary">
                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                    <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z"/>
                </svg>
                Save Configuration
            </button>
        </div>
        
        <!-- Real-Time Log Viewer (HomeAssistant-style) -->
        <div style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h4 style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
                    </svg>
                    Live Log Stream
                </h4>
                <div style="display: flex; gap: 0.5rem;">
                    <button id="log-refresh-btn" class="c-button c-button--secondary" style="padding: 0.5rem 0.75rem; font-size: 0.875rem;">
                        <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                        </svg>
                        Refresh
                    </button>
                    <button id="log-clear-btn" class="c-button c-button--secondary" style="padding: 0.5rem 0.75rem; font-size: 0.875rem;">
                        <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        Clear
                    </button>
                </div>
            </div>
            
            <!-- Filter Controls -->
            <div style="display: flex; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 150px;">
                    <label style="display: block; margin-bottom: 0.25rem; font-size: 0.875rem; color: #666;">Filter by Level</label>
                    <select id="log-filter-level" class="c-input" style="width: 100%;">
                        <option value="all">All Levels</option>
                        <option value="debug">DEBUG</option>
                        <option value="info">INFO</option>
                        <option value="warning">WARNING</option>
                        <option value="error">ERROR</option>
                        <option value="critical">CRITICAL</option>
                    </select>
                </div>
                <div style="flex: 2; min-width: 200px;">
                    <label style="display: block; margin-bottom: 0.25rem; font-size: 0.875rem; color: #666;">Search</label>
                    <input type="text" id="log-filter-search" class="c-input" placeholder="Search in logs..." style="width: 100%;">
                </div>
            </div>
            
            <!-- Log Output (HomeAssistant-style) -->
            <div id="log-viewer" style="
                background: #1e1e1e; 
                color: #d4d4d4; 
                font-family: 'Consolas', 'Monaco', monospace; 
                font-size: 0.8125rem; 
                padding: 1rem; 
                border-radius: 8px; 
                height: 400px; 
                overflow-y: auto;
                border: 1px solid #333;
            ">
                <div style="color: #888; text-align: center; padding: 2rem;">
                    Click "Refresh" to load log entries...
                </div>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee;">
                <span style="font-size: 0.875rem; color: #666;">
                    <span id="log-entry-count">0</span> entries displayed
                </span>
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" id="log-auto-scroll" checked>
                    <span style="font-size: 0.875rem; color: #666;">Auto-scroll to bottom</span>
                </label>
            </div>
        </div>
        
        <!-- Log File Management -->
        <div style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h4 style="margin-top: 0; margin-bottom: 1rem;">Log File Management</h4>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                <div style="background: #f5f5f5; padding: 1rem; border-radius: 8px;">
                    <div style="font-size: 0.875rem; color: #666; margin-bottom: 0.25rem;">Total Size</div>
                    <div style="font-size: 1.25rem; font-weight: 600;" id="log-total-size">—</div>
                </div>
                <div style="background: #f5f5f5; padding: 1rem; border-radius: 8px;">
                    <div style="font-size: 0.875rem; color: #666; margin-bottom: 0.25rem;">Log Files</div>
                    <div style="font-size: 1.25rem; font-weight: 600;" id="log-file-count">—</div>
                </div>
                <div style="background: #f5f5f5; padding: 1rem; border-radius: 8px;">
                    <div style="font-size: 0.875rem; color: #666; margin-bottom: 0.25rem;">Oldest Entry</div>
                    <div style="font-size: 1.25rem; font-weight: 600;" id="log-oldest">—</div>
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <button id="download-logs-btn" class="c-button c-button--secondary">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                    Download Logs
                </button>
                <button id="clear-logs-btn" class="c-button c-button--danger">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    Clear All Logs
                </button>
            </div>
        </div>
        
        <!-- Clear Logs Confirmation Modal -->
        <div class="c-modal" id="clear-logs-modal">
            <div class="c-modal__content" style="max-width: 400px;">
                <div class="c-modal__header">
                    <h2>Clear All Logs</h2>
                    <button class="c-modal__close" id="clear-logs-close">&times;</button>
                </div>
                <div class="c-modal__body">
                    <p style="color: #666;">Are you sure you want to clear all log files?</p>
                    <p style="color: #f44336;"><strong>This action cannot be undone.</strong></p>
                </div>
                <div class="c-modal__footer">
                    <button type="button" class="c-button c-button--secondary" id="clear-logs-cancel">Cancel</button>
                    <button type="button" class="c-button c-button--danger" id="clear-logs-confirm">Clear Logs</button>
                </div>
            </div>
        </div>
        <?php
    },
    
    // JavaScript initialization
    'script' => function() {
        ?>
        // Logger Module State
        const LoggerModule = {
            logEntries: [],
            filterLevel: 'all',
            filterSearch: '',
            
            // Log level colors
            LOG_COLORS: {
                debug: '#888',
                info: '#4CAF50',
                warning: '#FF9800',
                error: '#f44336',
                critical: '#9C27B0'
            },
            
            init() {
                console.log('[Logger] Initializing module...');
                this.loadLogInfo();
                this.bindEvents();
            },
            
            bindEvents() {
                // Save log level
                const saveBtn = document.getElementById('save-log-level');
                if (saveBtn) {
                    saveBtn.addEventListener('click', () => this.saveLogLevel());
                }
                
                // Refresh logs
                const refreshBtn = document.getElementById('log-refresh-btn');
                if (refreshBtn) {
                    refreshBtn.addEventListener('click', () => this.loadLogs());
                }
                
                // Clear display
                const clearBtn = document.getElementById('log-clear-btn');
                if (clearBtn) {
                    clearBtn.addEventListener('click', () => this.clearDisplay());
                }
                
                // Filter changes
                const filterLevel = document.getElementById('log-filter-level');
                const filterSearch = document.getElementById('log-filter-search');
                
                if (filterLevel) {
                    filterLevel.addEventListener('change', (e) => {
                        this.filterLevel = e.target.value;
                        this.applyFilters();
                    });
                }
                
                if (filterSearch) {
                    filterSearch.addEventListener('input', (e) => {
                        this.filterSearch = e.target.value.toLowerCase();
                        this.applyFilters();
                    });
                }
                
                // Download logs
                const downloadBtn = document.getElementById('download-logs-btn');
                if (downloadBtn) {
                    downloadBtn.addEventListener('click', () => this.downloadLogs());
                }
                
                // Clear logs modal
                const clearLogsBtn = document.getElementById('clear-logs-btn');
                const clearLogsClose = document.getElementById('clear-logs-close');
                const clearLogsCancel = document.getElementById('clear-logs-cancel');
                const clearLogsConfirm = document.getElementById('clear-logs-confirm');
                
                if (clearLogsBtn) clearLogsBtn.addEventListener('click', () => this.openClearModal());
                if (clearLogsClose) clearLogsClose.addEventListener('click', () => this.closeClearModal());
                if (clearLogsCancel) clearLogsCancel.addEventListener('click', () => this.closeClearModal());
                if (clearLogsConfirm) clearLogsConfirm.addEventListener('click', () => this.confirmClearLogs());
            },
            
            async loadLogInfo() {
                try {
                    // Load error log stats
                    const response = await fetch('/api/system/errors?limit=50');
                    const data = await response.json();
                    
                    if (data.success && data.data) {
                        const loggerLatest = document.getElementById('logger-latest');
                        const logEntryCount = document.getElementById('log-entry-count');
                        
                        if (loggerLatest) {
                            loggerLatest.textContent = data.data.errors.length > 0 ? 'Recent' : 'No recent errors';
                        }
                        if (logEntryCount) {
                            logEntryCount.textContent = data.data.total || 0;
                        }
                    }
                } catch (error) {
                    console.error('[Logger] Failed to load info:', error);
                }
            },
            
            async loadLogs() {
                const viewer = document.getElementById('log-viewer');
                viewer.innerHTML = '<div style="color: #888; text-align: center; padding: 2rem;">Loading logs...</div>';
                
                try {
                    const response = await fetch('/api/system/errors?limit=100');
                    const data = await response.json();
                    
                    if (data.success && data.data && data.data.errors) {
                        this.logEntries = data.data.errors;
                        this.applyFilters();
                        document.getElementById('log-entry-count').textContent = this.logEntries.length;
                    } else {
                        viewer.innerHTML = '<div style="color: #888; text-align: center; padding: 2rem;">No log entries found</div>';
                    }
                } catch (error) {
                    console.error('[Logger] Failed to load logs:', error);
                    viewer.innerHTML = '<div style="color: #f44336; text-align: center; padding: 2rem;">Failed to load logs</div>';
                }
            },
            
            applyFilters() {
                let filtered = this.logEntries;
                
                // Filter by level
                if (this.filterLevel !== 'all') {
                    filtered = filtered.filter(entry => entry.level === this.filterLevel);
                }
                
                // Filter by search
                if (this.filterSearch) {
                    filtered = filtered.filter(entry => 
                        (entry.message && entry.message.toLowerCase().includes(this.filterSearch)) ||
                        (entry.level && entry.level.toLowerCase().includes(this.filterSearch))
                    );
                }
                
                this.renderLogs(filtered);
                document.getElementById('log-entry-count').textContent = filtered.length;
            },
            
            renderLogs(entries) {
                const viewer = document.getElementById('log-viewer');
                
                if (!entries || entries.length === 0) {
                    viewer.innerHTML = '<div style="color: #888; text-align: center; padding: 2rem;">No matching log entries</div>';
                    return;
                }
                
                viewer.innerHTML = entries.map(entry => {
                    const color = this.LOG_COLORS[entry.level] || '#888';
                    const time = entry.time || '—';
                    const level = (entry.level || 'info').toUpperCase();
                    const message = this.escapeHtml(entry.message || 'No message');
                    
                    return `<div style="margin-bottom: 0.5rem; line-height: 1.4;">
                        <span style="color: #666;">${this.escapeHtml(time)}</span>
                        <span style="color: ${color}; font-weight: 600; margin: 0 0.5rem;">[${level}]</span>
                        <span style="color: #d4d4d4;">${message}</span>
                    </div>`;
                }).join('');
                
                // Auto-scroll if enabled
                if (document.getElementById('log-auto-scroll')?.checked) {
                    viewer.scrollTop = viewer.scrollHeight;
                }
            },
            
            clearDisplay() {
                this.logEntries = [];
                document.getElementById('log-viewer').innerHTML = '<div style="color: #888; text-align: center; padding: 2rem;">Log display cleared</div>';
                document.getElementById('log-entry-count').textContent = '0';
            },
            
            async saveLogLevel() {
                const level = document.getElementById('logger-level-select').value;
                const saveBtn = document.getElementById('save-log-level');
                
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
                
                try {
                    // In production, this would save to an API
                    // For now, we just update the UI
                    await new Promise(resolve => setTimeout(resolve, 500));
                    
                    const levelBadge = document.getElementById('logger-level-badge');
                    if (levelBadge) {
                        levelBadge.textContent = level.toUpperCase();
                    }
                    this.showAlert('logger-alert', `Log level set to ${level.toUpperCase()}`, 'success');
                    
                } catch (error) {
                    console.error('[Logger] Save failed:', error);
                    this.showAlert('logger-alert', 'Failed to save log level', 'error');
                } finally {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;"><path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z"/></svg> Save Configuration';
                }
            },
            
            downloadLogs() {
                // Create log content for download
                const content = this.logEntries.map(entry => 
                    `[${entry.time || '—'}] [${(entry.level || 'info').toUpperCase()}] ${entry.message || ''}`
                ).join('\n');
                
                const blob = new Blob([content], { type: 'text/plain' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `logs-${new Date().toISOString().split('T')[0]}.txt`;
                a.click();
                URL.revokeObjectURL(url);
                
                this.showAlert('logger-alert', 'Logs downloaded', 'success');
            },
            
            openClearModal() {
                document.getElementById('clear-logs-modal').classList.add('show');
            },
            
            closeClearModal() {
                document.getElementById('clear-logs-modal').classList.remove('show');
            },
            
            async confirmClearLogs() {
                const confirmBtn = document.getElementById('clear-logs-confirm');
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Clearing...';
                
                try {
                    // In production, this would call an API
                    await new Promise(resolve => setTimeout(resolve, 1000));
                    
                    this.closeClearModal();
                    this.clearDisplay();
                    this.showAlert('logger-alert', 'All logs have been cleared', 'success');
                    
                } catch (error) {
                    console.error('[Logger] Clear failed:', error);
                    this.showAlert('logger-alert', 'Failed to clear logs', 'error');
                } finally {
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = 'Clear Logs';
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
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        };
        
        // Initialize on DOMContentLoaded or immediately if already loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => LoggerModule.init());
        } else {
            LoggerModule.init();
        }
        <?php
    }
];

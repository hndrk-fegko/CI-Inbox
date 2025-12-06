<?php
/**
 * Admin Tab Module: IMAP Configuration
 * 
 * Provides:
 * - Global IMAP configuration for email polling
 * - Connection testing with real-time feedback
 * - Auto-discovery from email address
 * - Folder selection and inbox configuration
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
        <div style="margin-bottom: 2rem;">
            <h3 style="margin: 0 0 0.5rem 0; font-size: 1.25rem; font-weight: 600;">IMAP Configuration</h3>
            <p style="margin: 0; color: #666; font-size: 0.875rem;">Configure global IMAP settings for email polling and autodiscovery.</p>
        </div>
        
        <!-- Info Box -->
        <div style="background: #E3F2FD; border-left: 4px solid #2196F3; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="#1976D2" style="flex-shrink: 0; margin-top: 2px;">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <strong style="color: #1565C0;">About IMAP Configuration</strong>
                    <p style="margin: 0.5rem 0 0 0; color: #1976D2; font-size: 0.875rem;">
                        The global IMAP account is used for polling the shared team inbox. Emails received here are processed, 
                        threaded, and made available to all team members. Use autodiscover for quick setup or configure manually.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Alert Container -->
        <div id="imap-config-alert" style="margin-bottom: 1rem;"></div>
        
        <!-- Main Configuration Panel -->
        <div style="background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h4 style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                    </svg>
                    Server Configuration
                </h4>
                <button type="button" id="imap-autodiscover-btn" class="c-button c-button--secondary" style="font-size: 0.875rem;">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                    </svg>
                    Auto-discover
                </button>
            </div>
            
            <form id="imap-config-form">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <!-- Left Column -->
                    <div>
                        <div class="c-input-group" style="margin-bottom: 1rem;">
                            <label for="imap-host">IMAP Host <span style="color: #f44336;">*</span></label>
                            <input type="text" id="imap-host" name="host" class="c-input" placeholder="imap.example.com" required>
                            <small style="color: #666;">IMAP server hostname (e.g., imap.gmail.com)</small>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="c-input-group" style="margin-bottom: 1rem;">
                                <label for="imap-port">Port <span style="color: #f44336;">*</span></label>
                                <input type="number" id="imap-port" name="port" class="c-input" value="993" placeholder="993" required>
                            </div>
                            <div class="c-input-group" style="margin-bottom: 1rem;">
                                <label for="imap-encryption">Encryption</label>
                                <select id="imap-encryption" name="encryption" class="c-input">
                                    <option value="ssl">SSL/TLS (Port 993)</option>
                                    <option value="tls">STARTTLS (Port 143)</option>
                                    <option value="none">None (Not Recommended)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="c-input-group" style="margin-bottom: 1rem;">
                            <label for="imap-inbox-folder">Inbox Folder</label>
                            <input type="text" id="imap-inbox-folder" name="inbox_folder" class="c-input" value="INBOX" placeholder="INBOX">
                            <small style="color: #666;">Folder to monitor for new emails</small>
                        </div>
                    </div>
                    
                    <!-- Right Column -->
                    <div>
                        <div class="c-input-group" style="margin-bottom: 1rem;">
                            <label for="imap-username">Username <span style="color: #f44336;">*</span></label>
                            <input type="text" id="imap-username" name="username" class="c-input" placeholder="user@example.com" required>
                            <small style="color: #666;">Usually your full email address</small>
                        </div>
                        
                        <div class="c-input-group" style="margin-bottom: 1rem;">
                            <label for="imap-password">Password <span style="color: #f44336;">*</span></label>
                            <input type="password" id="imap-password" name="password" class="c-input" placeholder="Enter password or leave empty to keep current">
                            <small style="color: #666;">Leave empty to keep existing password</small>
                        </div>
                        
                        <div class="c-input-group" style="margin-bottom: 1rem;">
                            <label>
                                <input type="checkbox" id="imap-validate-cert" name="validate_cert" checked>
                                Validate SSL Certificate
                            </label>
                            <small style="color: #666; display: block; margin-top: 0.25rem;">Disable only for self-signed certificates</small>
                        </div>
                    </div>
                </div>
                
                <div style="border-top: 1px solid #eee; margin-top: 1.5rem; padding-top: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                    <button type="button" id="imap-test-btn" class="c-button c-button--secondary">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Test Connection
                    </button>
                    <button type="submit" id="imap-save-btn" class="c-button c-button--primary">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                            <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z"/>
                        </svg>
                        Save Configuration
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Test Results Panel -->
        <div id="imap-test-results" style="display: none; background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 1.5rem;">
            <h4 style="margin: 0 0 1rem 0; display: flex; align-items: center; gap: 0.5rem;">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                Connection Test Results
            </h4>
            <div id="imap-test-content"></div>
        </div>
        
        <!-- Autodiscover Modal -->
        <div class="c-modal" id="imap-autodiscover-modal">
            <div class="c-modal__content" style="max-width: 500px;">
                <div class="c-modal__header">
                    <h2>Auto-discover IMAP Settings</h2>
                    <button class="c-modal__close" id="imap-autodiscover-close">&times;</button>
                </div>
                <div class="c-modal__body">
                    <p style="color: #666; margin-bottom: 1.5rem;">Enter your email address to automatically detect IMAP server settings.</p>
                    <div class="c-input-group">
                        <label for="imap-autodiscover-email">Email Address</label>
                        <input type="email" id="imap-autodiscover-email" class="c-input" placeholder="user@example.com" required>
                    </div>
                    <div id="imap-autodiscover-result" style="margin-top: 1rem;"></div>
                </div>
                <div class="c-modal__footer">
                    <button type="button" class="c-button c-button--secondary" id="imap-autodiscover-cancel">Cancel</button>
                    <button type="button" class="c-button c-button--primary" id="imap-autodiscover-submit">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                        </svg>
                        Discover
                    </button>
                </div>
            </div>
        </div>
        <?php
    },
    
    // JavaScript initialization
    'script' => function() {
        ?>
        // IMAP Module State
        const ImapModule = {
            currentConfig: null,
            
            init() {
                console.log('[IMAP] Initializing module...');
                this.loadConfig();
                this.bindEvents();
            },
            
            bindEvents() {
                // Form submission
                const form = document.getElementById('imap-config-form');
                if (form) {
                    form.addEventListener('submit', (e) => {
                        e.preventDefault();
                        this.saveConfig();
                    });
                }
                
                // Test connection button
                const testBtn = document.getElementById('imap-test-btn');
                if (testBtn) {
                    testBtn.addEventListener('click', () => this.testConnection());
                }
                
                // Autodiscover button
                const autodiscoverBtn = document.getElementById('imap-autodiscover-btn');
                if (autodiscoverBtn) {
                    autodiscoverBtn.addEventListener('click', () => this.openAutodiscoverModal());
                }
                
                // Autodiscover modal events
                const autodiscoverSubmit = document.getElementById('imap-autodiscover-submit');
                if (autodiscoverSubmit) {
                    autodiscoverSubmit.addEventListener('click', () => this.runAutodiscover());
                }
                
                const autodiscoverClose = document.getElementById('imap-autodiscover-close');
                const autodiscoverCancel = document.getElementById('imap-autodiscover-cancel');
                if (autodiscoverClose) autodiscoverClose.addEventListener('click', () => this.closeAutodiscoverModal());
                if (autodiscoverCancel) autodiscoverCancel.addEventListener('click', () => this.closeAutodiscoverModal());
                
                // Port auto-update based on encryption
                const encryptionSelect = document.getElementById('imap-encryption');
                if (encryptionSelect) {
                    encryptionSelect.addEventListener('change', (e) => {
                        const portInput = document.getElementById('imap-port');
                        if (e.target.value === 'ssl') portInput.value = 993;
                        else if (e.target.value === 'tls') portInput.value = 143;
                    });
                }
            },
            
            async loadConfig() {
                try {
                    const response = await fetch('/api/admin/settings/imap');
                    const data = await response.json();
                    
                    if (data.success && data.data) {
                        this.currentConfig = data.data;
                        this.populateForm(data.data);
                        this.updateCardStatus(data.data);
                    }
                } catch (error) {
                    console.error('[IMAP] Failed to load config:', error);
                }
            },
            
            populateForm(config) {
                if (config.host) document.getElementById('imap-host').value = config.host;
                if (config.port) document.getElementById('imap-port').value = config.port;
                if (config.encryption) document.getElementById('imap-encryption').value = config.encryption;
                if (config.username) document.getElementById('imap-username').value = config.username;
                if (config.inbox_folder) document.getElementById('imap-inbox-folder').value = config.inbox_folder;
                
                const validateCert = document.getElementById('imap-validate-cert');
                if (validateCert && config.validate_cert !== undefined) {
                    validateCert.checked = config.validate_cert;
                }
            },
            
            updateCardStatus(config) {
                const badge = document.getElementById('imap-status-badge');
                const info = document.getElementById('imap-configured-info');
                
                if (config.configured) {
                    badge.className = 'c-status-badge c-status-badge--success';
                    badge.innerHTML = '<span class="status-dot"></span>Configured';
                    info.style.display = 'block';
                    document.getElementById('imap-configured-host').textContent = config.host || '—';
                    document.getElementById('imap-configured-user').textContent = config.username || '—';
                } else {
                    badge.className = 'c-status-badge c-status-badge--warning';
                    badge.innerHTML = '<span class="status-dot"></span>Not Configured';
                    info.style.display = 'none';
                }
            },
            
            async saveConfig() {
                const saveBtn = document.getElementById('imap-save-btn');
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
                
                try {
                    const formData = {
                        host: document.getElementById('imap-host').value,
                        port: parseInt(document.getElementById('imap-port').value),
                        encryption: document.getElementById('imap-encryption').value,
                        username: document.getElementById('imap-username').value,
                        inbox_folder: document.getElementById('imap-inbox-folder').value,
                        validate_cert: document.getElementById('imap-validate-cert').checked
                    };
                    
                    // Only include password if it was entered
                    const password = document.getElementById('imap-password').value;
                    if (password) {
                        formData.password = password;
                    }
                    
                    const response = await fetch('/api/admin/settings/imap', {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(formData)
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showAlert('imap-config-alert', 'IMAP configuration saved successfully!', 'success');
                        this.currentConfig = data.data;
                        this.updateCardStatus(data.data);
                        document.getElementById('imap-password').value = '';
                    } else {
                        this.showAlert('imap-config-alert', data.error || 'Failed to save configuration', 'error');
                    }
                } catch (error) {
                    console.error('[IMAP] Save failed:', error);
                    this.showAlert('imap-config-alert', 'Failed to save configuration: ' + error.message, 'error');
                } finally {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;"><path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z"/></svg> Save Configuration';
                }
            },
            
            async testConnection() {
                const testBtn = document.getElementById('imap-test-btn');
                testBtn.disabled = true;
                testBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Testing...';
                
                const resultsPanel = document.getElementById('imap-test-results');
                const resultsContent = document.getElementById('imap-test-content');
                
                try {
                    const formData = {
                        host: document.getElementById('imap-host').value,
                        port: parseInt(document.getElementById('imap-port').value),
                        encryption: document.getElementById('imap-encryption').value,
                        username: document.getElementById('imap-username').value,
                        validate_cert: document.getElementById('imap-validate-cert').checked
                    };
                    
                    // Include password if entered, otherwise use stored
                    const password = document.getElementById('imap-password').value;
                    if (password) {
                        formData.password = password;
                    }
                    
                    const response = await fetch('/api/admin/settings/imap/test', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(formData)
                    });
                    
                    const data = await response.json();
                    
                    resultsPanel.style.display = 'block';
                    
                    if (data.success) {
                        let foldersHtml = '';
                        if (data.data && data.data.length > 0) {
                            foldersHtml = '<div style="margin-top: 1rem;"><strong>Available Folders:</strong><ul style="margin: 0.5rem 0 0 1.5rem;">';
                            data.data.forEach(folder => {
                                foldersHtml += `<li>${this.escapeHtml(folder)}</li>`;
                            });
                            foldersHtml += '</ul></div>';
                        }
                        
                        resultsContent.innerHTML = `
                            <div style="background: #E8F5E9; border: 1px solid #4CAF50; border-radius: 8px; padding: 1rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; color: #2E7D32;">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <strong>Connection Successful!</strong>
                                </div>
                                <p style="margin: 0.5rem 0 0 0; color: #2E7D32;">${this.escapeHtml(data.message || 'Connected to IMAP server')}</p>
                                ${foldersHtml}
                            </div>
                        `;
                    } else {
                        resultsContent.innerHTML = `
                            <div style="background: #FFEBEE; border: 1px solid #f44336; border-radius: 8px; padding: 1rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; color: #c62828;">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                    <strong>Connection Failed</strong>
                                </div>
                                <p style="margin: 0.5rem 0 0 0; color: #c62828;">${this.escapeHtml(data.error || 'Could not connect to IMAP server')}</p>
                            </div>
                        `;
                    }
                } catch (error) {
                    console.error('[IMAP] Test failed:', error);
                    resultsPanel.style.display = 'block';
                    resultsContent.innerHTML = `
                        <div style="background: #FFEBEE; border: 1px solid #f44336; border-radius: 8px; padding: 1rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; color: #c62828;">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                <strong>Connection Test Error</strong>
                            </div>
                            <p style="margin: 0.5rem 0 0 0; color: #c62828;">${this.escapeHtml(error.message)}</p>
                        </div>
                    `;
                } finally {
                    testBtn.disabled = false;
                    testBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg> Test Connection';
                }
            },
            
            openAutodiscoverModal() {
                document.getElementById('imap-autodiscover-modal').classList.add('show');
                document.getElementById('imap-autodiscover-email').focus();
            },
            
            closeAutodiscoverModal() {
                document.getElementById('imap-autodiscover-modal').classList.remove('show');
                document.getElementById('imap-autodiscover-result').innerHTML = '';
            },
            
            async runAutodiscover() {
                const email = document.getElementById('imap-autodiscover-email').value;
                if (!email) return;
                
                const submitBtn = document.getElementById('imap-autodiscover-submit');
                const resultDiv = document.getElementById('imap-autodiscover-result');
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Discovering...';
                resultDiv.innerHTML = '<p style="color: #666;">Detecting IMAP settings...</p>';
                
                try {
                    const response = await fetch('/api/admin/settings/imap/autodiscover', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ email })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success && data.data) {
                        // Apply discovered settings
                        if (data.data.host) document.getElementById('imap-host').value = data.data.host;
                        if (data.data.port) document.getElementById('imap-port').value = data.data.port;
                        if (data.data.encryption) document.getElementById('imap-encryption').value = data.data.encryption;
                        document.getElementById('imap-username').value = email;
                        
                        resultDiv.innerHTML = `
                            <div style="background: #E8F5E9; border: 1px solid #4CAF50; border-radius: 8px; padding: 1rem; margin-top: 1rem;">
                                <strong style="color: #2E7D32;">Settings detected!</strong>
                                <p style="margin: 0.5rem 0 0 0; color: #2E7D32; font-size: 0.875rem;">
                                    Host: ${this.escapeHtml(data.data.host)}<br>
                                    Port: ${data.data.port}<br>
                                    Encryption: ${data.data.encryption}
                                </p>
                            </div>
                        `;
                        
                        setTimeout(() => this.closeAutodiscoverModal(), 2000);
                    } else {
                        resultDiv.innerHTML = `
                            <div style="background: #FFF3E0; border: 1px solid #FF9800; border-radius: 8px; padding: 1rem; margin-top: 1rem;">
                                <strong style="color: #E65100;">Could not auto-detect settings</strong>
                                <p style="margin: 0.5rem 0 0 0; color: #E65100; font-size: 0.875rem;">
                                    ${this.escapeHtml(data.error || 'Please configure manually')}
                                </p>
                            </div>
                        `;
                    }
                } catch (error) {
                    resultDiv.innerHTML = `
                        <div style="background: #FFEBEE; border: 1px solid #f44336; border-radius: 8px; padding: 1rem; margin-top: 1rem;">
                            <strong style="color: #c62828;">Auto-discover failed</strong>
                            <p style="margin: 0.5rem 0 0 0; color: #c62828; font-size: 0.875rem;">${this.escapeHtml(error.message)}</p>
                        </div>
                    `;
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/></svg> Discover';
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
            document.addEventListener('DOMContentLoaded', () => ImapModule.init());
        } else {
            ImapModule.init();
        }
        <?php
    }
];

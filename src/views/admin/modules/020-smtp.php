<?php
/**
 * Admin Tab Module: SMTP Configuration
 * 
 * Provides:
 * - Global SMTP configuration for outgoing emails
 * - Connection testing with test email
 * - Auto-discovery from email address
 * - Sender identity configuration
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
        <div style="margin-bottom: 2rem;">
            <h3 style="margin: 0 0 0.5rem 0; font-size: 1.25rem; font-weight: 600;">SMTP Configuration</h3>
            <p style="margin: 0; color: #666; font-size: 0.875rem;">Configure global SMTP settings for sending emails.</p>
        </div>
        
        <!-- Info Box -->
        <div style="background: #E3F2FD; border-left: 4px solid #2196F3; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="#1976D2" style="flex-shrink: 0; margin-top: 2px;">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <strong style="color: #1565C0;">About SMTP Configuration</strong>
                    <p style="margin: 0.5rem 0 0 0; color: #1976D2; font-size: 0.875rem;">
                        The global SMTP server is used for sending email replies and notifications. Configure your 
                        outgoing mail server to enable email sending features across the application.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Alert Container -->
        <div id="smtp-config-alert" style="margin-bottom: 1rem;"></div>
        
        <!-- Main Configuration Panel -->
        <div style="background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h4 style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                    </svg>
                    Server Configuration
                </h4>
                <button type="button" id="smtp-autodiscover-btn" class="c-button c-button--secondary" style="font-size: 0.875rem;">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                    </svg>
                    Auto-discover
                </button>
            </div>
            
            <form id="smtp-config-form">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <!-- Left Column -->
                    <div>
                        <div class="c-input-group" style="margin-bottom: 1rem;">
                            <label for="smtp-host">SMTP Host <span style="color: #f44336;">*</span></label>
                            <input type="text" id="smtp-host" name="host" class="c-input" placeholder="smtp.example.com" required>
                            <small style="color: #666;">SMTP server hostname (e.g., smtp.gmail.com)</small>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="c-input-group" style="margin-bottom: 1rem;">
                                <label for="smtp-port">Port <span style="color: #f44336;">*</span></label>
                                <input type="number" id="smtp-port" name="port" class="c-input" value="465" placeholder="465" required>
                            </div>
                            <div class="c-input-group" style="margin-bottom: 1rem;">
                                <label for="smtp-encryption">Encryption</label>
                                <select id="smtp-encryption" name="encryption" class="c-input">
                                    <option value="ssl">SSL/TLS (Port 465)</option>
                                    <option value="tls">STARTTLS (Port 587)</option>
                                    <option value="none">None (Not Recommended)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="c-input-group" style="margin-bottom: 1rem;">
                            <label>
                                <input type="checkbox" id="smtp-auth-required" name="auth_required" checked>
                                Requires Authentication
                            </label>
                            <small style="color: #666; display: block; margin-top: 0.25rem;">Most SMTP servers require authentication</small>
                        </div>
                    </div>
                    
                    <!-- Right Column -->
                    <div>
                        <div class="c-input-group" style="margin-bottom: 1rem;">
                            <label for="smtp-username">Username <span style="color: #f44336;">*</span></label>
                            <input type="text" id="smtp-username" name="username" class="c-input" placeholder="user@example.com" required>
                            <small style="color: #666;">Usually your full email address</small>
                        </div>
                        
                        <div class="c-input-group" style="margin-bottom: 1rem;">
                            <label for="smtp-password">Password <span style="color: #f44336;">*</span></label>
                            <input type="password" id="smtp-password" name="password" class="c-input" placeholder="Enter password or leave empty to keep current">
                            <small style="color: #666;">Leave empty to keep existing password</small>
                        </div>
                    </div>
                </div>
                
                <!-- Sender Identity Section -->
                <div style="border-top: 1px solid #eee; margin-top: 1.5rem; padding-top: 1.5rem;">
                    <h5 style="margin: 0 0 1rem 0; font-size: 0.9375rem; font-weight: 600;">Default Sender Identity</h5>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                        <div class="c-input-group" style="margin-bottom: 1rem;">
                            <label for="smtp-from-name">From Name <span style="color: #f44336;">*</span></label>
                            <input type="text" id="smtp-from-name" name="from_name" class="c-input" placeholder="Support Team" required>
                            <small style="color: #666;">Display name for outgoing emails</small>
                        </div>
                        <div class="c-input-group" style="margin-bottom: 1rem;">
                            <label for="smtp-from-email">From Email <span style="color: #f44336;">*</span></label>
                            <input type="email" id="smtp-from-email" name="from_email" class="c-input" placeholder="support@example.com" required>
                            <small style="color: #666;">Sender email address</small>
                        </div>
                    </div>
                </div>
                
                <div style="border-top: 1px solid #eee; margin-top: 1.5rem; padding-top: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                    <button type="button" id="smtp-test-btn" class="c-button c-button--secondary">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                        </svg>
                        Send Test Email
                    </button>
                    <button type="submit" id="smtp-save-btn" class="c-button c-button--primary">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                            <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z"/>
                        </svg>
                        Save Configuration
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Test Results Panel -->
        <div id="smtp-test-results" style="display: none; background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 1.5rem;">
            <h4 style="margin: 0 0 1rem 0; display: flex; align-items: center; gap: 0.5rem;">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                </svg>
                Test Email Results
            </h4>
            <div id="smtp-test-content"></div>
        </div>
        
        <!-- Autodiscover Modal -->
        <div class="c-modal" id="smtp-autodiscover-modal">
            <div class="c-modal__content" style="max-width: 500px;">
                <div class="c-modal__header">
                    <h2>Auto-discover SMTP Settings</h2>
                    <button class="c-modal__close" id="smtp-autodiscover-close">&times;</button>
                </div>
                <div class="c-modal__body">
                    <p style="color: #666; margin-bottom: 1.5rem;">Enter your email address to automatically detect SMTP server settings.</p>
                    <div class="c-input-group">
                        <label for="smtp-autodiscover-email">Email Address</label>
                        <input type="email" id="smtp-autodiscover-email" class="c-input" placeholder="user@example.com" required>
                    </div>
                    <div id="smtp-autodiscover-result" style="margin-top: 1rem;"></div>
                </div>
                <div class="c-modal__footer">
                    <button type="button" class="c-button c-button--secondary" id="smtp-autodiscover-cancel">Cancel</button>
                    <button type="button" class="c-button c-button--primary" id="smtp-autodiscover-submit">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"/>
                        </svg>
                        Discover
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Test Email Modal -->
        <div class="c-modal" id="smtp-test-modal">
            <div class="c-modal__content" style="max-width: 500px;">
                <div class="c-modal__header">
                    <h2>Send Test Email</h2>
                    <button class="c-modal__close" id="smtp-test-close">&times;</button>
                </div>
                <div class="c-modal__body">
                    <p style="color: #666; margin-bottom: 1.5rem;">Send a test email to verify your SMTP configuration is working correctly.</p>
                    <div class="c-input-group">
                        <label for="smtp-test-email">Recipient Email Address</label>
                        <input type="email" id="smtp-test-email" class="c-input" placeholder="test@example.com" required>
                        <small style="color: #666;">The test email will be sent to this address</small>
                    </div>
                </div>
                <div class="c-modal__footer">
                    <button type="button" class="c-button c-button--secondary" id="smtp-test-cancel">Cancel</button>
                    <button type="button" class="c-button c-button--primary" id="smtp-test-submit">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                        </svg>
                        Send Test
                    </button>
                </div>
            </div>
        </div>
        <?php
    },
    
    'script' => function() {
        ?>
        // SMTP Module State
        const SmtpModule = {
            currentConfig: null,
            
            init() {
                console.log('[SMTP] Initializing module...');
                this.loadConfig();
                this.bindEvents();
            },
            
            bindEvents() {
                // Form submission
                const form = document.getElementById('smtp-config-form');
                if (form) {
                    form.addEventListener('submit', (e) => {
                        e.preventDefault();
                        this.saveConfig();
                    });
                }
                
                // Test connection button
                const testBtn = document.getElementById('smtp-test-btn');
                if (testBtn) {
                    testBtn.addEventListener('click', () => this.openTestModal());
                }
                
                // Autodiscover button
                const autodiscoverBtn = document.getElementById('smtp-autodiscover-btn');
                if (autodiscoverBtn) {
                    autodiscoverBtn.addEventListener('click', () => this.openAutodiscoverModal());
                }
                
                // Autodiscover modal events
                const autodiscoverSubmit = document.getElementById('smtp-autodiscover-submit');
                if (autodiscoverSubmit) {
                    autodiscoverSubmit.addEventListener('click', () => this.runAutodiscover());
                }
                
                const autodiscoverClose = document.getElementById('smtp-autodiscover-close');
                const autodiscoverCancel = document.getElementById('smtp-autodiscover-cancel');
                if (autodiscoverClose) autodiscoverClose.addEventListener('click', () => this.closeAutodiscoverModal());
                if (autodiscoverCancel) autodiscoverCancel.addEventListener('click', () => this.closeAutodiscoverModal());
                
                // Test modal events
                const testSubmit = document.getElementById('smtp-test-submit');
                if (testSubmit) {
                    testSubmit.addEventListener('click', () => this.sendTestEmail());
                }
                
                const testClose = document.getElementById('smtp-test-close');
                const testCancel = document.getElementById('smtp-test-cancel');
                if (testClose) testClose.addEventListener('click', () => this.closeTestModal());
                if (testCancel) testCancel.addEventListener('click', () => this.closeTestModal());
                
                // Port auto-update based on encryption
                const encryptionSelect = document.getElementById('smtp-encryption');
                if (encryptionSelect) {
                    encryptionSelect.addEventListener('change', (e) => {
                        const portInput = document.getElementById('smtp-port');
                        if (e.target.value === 'ssl') portInput.value = 465;
                        else if (e.target.value === 'tls') portInput.value = 587;
                    });
                }
            },
            
            async loadConfig() {
                try {
                    const response = await fetch('/api/admin/settings/smtp');
                    const data = await response.json();
                    
                    if (data.success && data.data) {
                        this.currentConfig = data.data;
                        this.populateForm(data.data);
                        this.updateCardStatus(data.data);
                    }
                } catch (error) {
                    console.error('[SMTP] Failed to load config:', error);
                }
            },
            
            populateForm(config) {
                if (config.host) document.getElementById('smtp-host').value = config.host;
                if (config.port) document.getElementById('smtp-port').value = config.port;
                if (config.encryption) document.getElementById('smtp-encryption').value = config.encryption;
                if (config.username) document.getElementById('smtp-username').value = config.username;
                if (config.from_name) document.getElementById('smtp-from-name').value = config.from_name;
                if (config.from_email) document.getElementById('smtp-from-email').value = config.from_email;
                
                const authRequired = document.getElementById('smtp-auth-required');
                if (authRequired && config.auth_required !== undefined) {
                    authRequired.checked = config.auth_required;
                }
            },
            
            updateCardStatus(config) {
                const badge = document.getElementById('smtp-status-badge');
                const info = document.getElementById('smtp-configured-info');
                
                if (config.configured) {
                    badge.className = 'c-status-badge c-status-badge--success';
                    badge.innerHTML = '<span class="status-dot"></span>Configured';
                    info.style.display = 'block';
                    document.getElementById('smtp-configured-host').textContent = config.host || '—';
                    document.getElementById('smtp-configured-from').textContent = config.from_email || '—';
                } else {
                    badge.className = 'c-status-badge c-status-badge--warning';
                    badge.innerHTML = '<span class="status-dot"></span>Not Configured';
                    info.style.display = 'none';
                }
            },
            
            async saveConfig() {
                const saveBtn = document.getElementById('smtp-save-btn');
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
                
                try {
                    const formData = {
                        host: document.getElementById('smtp-host').value,
                        port: parseInt(document.getElementById('smtp-port').value),
                        encryption: document.getElementById('smtp-encryption').value,
                        username: document.getElementById('smtp-username').value,
                        from_name: document.getElementById('smtp-from-name').value,
                        from_email: document.getElementById('smtp-from-email').value,
                        auth_required: document.getElementById('smtp-auth-required').checked
                    };
                    
                    // Only include password if it was entered
                    const password = document.getElementById('smtp-password').value;
                    if (password) {
                        formData.password = password;
                    }
                    
                    const response = await fetch('/api/admin/settings/smtp', {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(formData)
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showAlert('smtp-config-alert', 'SMTP configuration saved successfully!', 'success');
                        this.currentConfig = data.data;
                        this.updateCardStatus(data.data);
                        document.getElementById('smtp-password').value = '';
                    } else {
                        this.showAlert('smtp-config-alert', data.error || 'Failed to save configuration', 'error');
                    }
                } catch (error) {
                    console.error('[SMTP] Save failed:', error);
                    this.showAlert('smtp-config-alert', 'Failed to save configuration: ' + error.message, 'error');
                } finally {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;"><path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z"/></svg> Save Configuration';
                }
            },
            
            openTestModal() {
                document.getElementById('smtp-test-modal').classList.add('show');
                document.getElementById('smtp-test-email').focus();
            },
            
            closeTestModal() {
                document.getElementById('smtp-test-modal').classList.remove('show');
            },
            
            async sendTestEmail() {
                const testEmail = document.getElementById('smtp-test-email').value;
                if (!testEmail) return;
                
                const submitBtn = document.getElementById('smtp-test-submit');
                const resultsPanel = document.getElementById('smtp-test-results');
                const resultsContent = document.getElementById('smtp-test-content');
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sending...';
                
                try {
                    const formData = {
                        host: document.getElementById('smtp-host').value,
                        port: parseInt(document.getElementById('smtp-port').value),
                        encryption: document.getElementById('smtp-encryption').value,
                        username: document.getElementById('smtp-username').value,
                        from_name: document.getElementById('smtp-from-name').value,
                        from_email: document.getElementById('smtp-from-email').value,
                        test_email: testEmail
                    };
                    
                    // Include password if entered
                    const password = document.getElementById('smtp-password').value;
                    if (password) {
                        formData.password = password;
                    }
                    
                    const response = await fetch('/api/admin/settings/smtp/test', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(formData)
                    });
                    
                    const data = await response.json();
                    
                    this.closeTestModal();
                    resultsPanel.style.display = 'block';
                    
                    if (data.success) {
                        resultsContent.innerHTML = `
                            <div style="background: #E8F5E9; border: 1px solid #4CAF50; border-radius: 8px; padding: 1rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; color: #2E7D32;">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                    <strong>Test Email Sent Successfully!</strong>
                                </div>
                                <p style="margin: 0.5rem 0 0 0; color: #2E7D32;">
                                    ${this.escapeHtml(data.message || 'Check your inbox for the test email.')}
                                </p>
                            </div>
                        `;
                    } else {
                        resultsContent.innerHTML = `
                            <div style="background: #FFEBEE; border: 1px solid #f44336; border-radius: 8px; padding: 1rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; color: #c62828;">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                    <strong>Test Email Failed</strong>
                                </div>
                                <p style="margin: 0.5rem 0 0 0; color: #c62828;">${this.escapeHtml(data.error || 'Could not send test email')}</p>
                            </div>
                        `;
                    }
                } catch (error) {
                    console.error('[SMTP] Test failed:', error);
                    this.closeTestModal();
                    resultsPanel.style.display = 'block';
                    resultsContent.innerHTML = `
                        <div style="background: #FFEBEE; border: 1px solid #f44336; border-radius: 8px; padding: 1rem;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; color: #c62828;">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                <strong>Test Failed</strong>
                            </div>
                            <p style="margin: 0.5rem 0 0 0; color: #c62828;">${this.escapeHtml(error.message)}</p>
                        </div>
                    `;
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg> Send Test';
                }
            },
            
            openAutodiscoverModal() {
                document.getElementById('smtp-autodiscover-modal').classList.add('show');
                document.getElementById('smtp-autodiscover-email').focus();
            },
            
            closeAutodiscoverModal() {
                document.getElementById('smtp-autodiscover-modal').classList.remove('show');
                document.getElementById('smtp-autodiscover-result').innerHTML = '';
            },
            
            async runAutodiscover() {
                const email = document.getElementById('smtp-autodiscover-email').value;
                if (!email) return;
                
                const submitBtn = document.getElementById('smtp-autodiscover-submit');
                const resultDiv = document.getElementById('smtp-autodiscover-result');
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Discovering...';
                resultDiv.innerHTML = '<p style="color: #666;">Detecting SMTP settings...</p>';
                
                try {
                    const response = await fetch('/api/admin/settings/smtp/autodiscover', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ email })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success && data.data) {
                        // Apply discovered settings
                        if (data.data.host) document.getElementById('smtp-host').value = data.data.host;
                        if (data.data.port) document.getElementById('smtp-port').value = data.data.port;
                        if (data.data.encryption) document.getElementById('smtp-encryption').value = data.data.encryption;
                        document.getElementById('smtp-username').value = email;
                        document.getElementById('smtp-from-email').value = email;
                        
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
            document.addEventListener('DOMContentLoaded', () => SmtpModule.init());
        } else {
            SmtpModule.init();
        }
        <?php
    }
];

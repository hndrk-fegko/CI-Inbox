/**
 * Admin Settings JavaScript
 * 
 * Handles system-wide configuration:
 * - Global IMAP Configuration
 * - SMTP Configuration
 * - Cron Monitor
 * - Backup System
 */

// API Base URL
const API_BASE = '/api/admin/settings';

console.log('[AdminSettings] Script loaded');

// ============================================================================
// MODAL HELPER FUNCTIONS (Custom CSS Modals)
// ============================================================================

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('c-modal--open');
        console.log(`[AdminSettings] Opened modal: ${modalId}`);
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('c-modal--open');
        console.log(`[AdminSettings] Closed modal: ${modalId}`);
    }
}

// Close modal on background click
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('c-modal')) {
        e.target.classList.remove('c-modal--open');
    }
});

// Close modal on ESC key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.c-modal--open').forEach(modal => {
            modal.classList.remove('c-modal--open');
        });
    }
});

// ============================================================================
// IMAP CONFIGURATION
// ============================================================================

let imapModalMode = 'view'; // 'view' or 'edit'

// Load IMAP config on page load
async function loadImapConfig() {
    console.log('[AdminSettings] Loading IMAP config...');
    try {
        const response = await fetch(`${API_BASE}/imap`);
        const result = await response.json();
        
        if (result.success) {
            const config = result.data;
            console.log('[AdminSettings] IMAP config loaded:', { 
                host: config.host, 
                configured: config.configured 
            });
            
            // Update status badge
            const badge = document.getElementById('imap-status-badge');
            const configButton = document.getElementById('imap-config-button');
            
            if (config.configured) {
                badge.textContent = 'Configured';
                badge.className = 'badge badge-success';
                configButton.textContent = 'Edit Configuration';
            } else {
                badge.textContent = 'Not Configured';
                badge.className = 'badge badge-warning';
                configButton.textContent = 'Configure';
            }
            
            configButton.disabled = false;
            
            // Fill form fields
            document.getElementById('imap-host').value = config.host || '';
            document.getElementById('imap-port').value = config.port || 993;
            document.getElementById('imap-ssl').checked = config.ssl === true;
            document.getElementById('imap-username').value = config.username || '';
            document.getElementById('imap-password').value = ''; // Never show password
            document.getElementById('imap-inbox-folder').value = config.inbox_folder || 'INBOX';
            
            // Show configured info
            if (config.configured) {
                document.getElementById('imap-configured-info').style.display = 'block';
                document.getElementById('imap-configured-host').textContent = config.host;
                document.getElementById('imap-configured-user').textContent = config.username;
            }
        } else {
            console.error('[AdminSettings] Failed to load IMAP config:', result.error);
            showAlert('imap-alert', result.error, 'error');
        }
    } catch (error) {
        console.error('[AdminSettings] Error loading IMAP config:', error);
        showAlert('imap-alert', 'Failed to load configuration: ' + error.message, 'error');
    }
}

// Open IMAP configuration modal
document.getElementById('imap-config-button').addEventListener('click', () => {
    console.log('[AdminSettings] Opening IMAP config modal...');
    imapModalMode = 'edit';
    openModal('imapConfigModal');
});

// Auto-discover IMAP settings
async function autodiscoverImap() {
    const email = prompt('Enter your email address for auto-discovery:');
    if (!email || !email.includes('@')) {
        showAlert('imap-modal-alert', 'Please enter a valid email address', 'error');
        return;
    }
    
    console.log('[AdminSettings] Auto-discovering IMAP settings...', { email });
    showAlert('imap-modal-alert', 'Detecting IMAP settings...', 'info');
    
    try {
        const response = await fetch(`${API_BASE}/imap/autodiscover`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email })
        });
        
        const result = await response.json();
        
        if (result.success && result.config) {
            console.log('[AdminSettings] IMAP auto-discovery successful:', result.config);
            
            // Fill form with detected settings
            document.getElementById('imap-host').value = result.config.host;
            document.getElementById('imap-port').value = result.config.port;
            document.getElementById('imap-ssl').checked = result.config.ssl;
            document.getElementById('imap-inbox-folder').value = result.config.inbox_folder || 'INBOX';
            
            // Pre-fill username with email
            const username = document.getElementById('imap-username').value;
            if (!username) {
                document.getElementById('imap-username').value = email;
            }
            
            showAlert('imap-modal-alert', `Settings detected! Host: ${result.config.host}, Port: ${result.config.port}. Please enter your password.`, 'success');
        } else {
            console.error('[AdminSettings] IMAP auto-discovery failed:', result.error);
            showAlert('imap-modal-alert', result.error || 'Could not detect settings. Please enter manually.', 'error');
        }
    } catch (error) {
        console.error('[AdminSettings] IMAP auto-discovery error:', error);
        showAlert('imap-modal-alert', 'Auto-discovery failed: ' + error.message, 'error');
    }
}

// Add autodiscover button event listener when modal loads
document.addEventListener('DOMContentLoaded', () => {
    const autodiscoverBtn = document.getElementById('imap-autodiscover-button');
    if (autodiscoverBtn) {
        autodiscoverBtn.addEventListener('click', autodiscoverImap);
    }
});

// Test IMAP connection
document.getElementById('imap-test-button').addEventListener('click', async () => {
    const button = document.getElementById('imap-test-button');
    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'Testing...';
    
    console.log('[AdminSettings] Testing IMAP connection...');
    
    const config = {
        host: document.getElementById('imap-host').value,
        port: parseInt(document.getElementById('imap-port').value),
        ssl: document.getElementById('imap-ssl').checked,
        username: document.getElementById('imap-username').value,
        password: document.getElementById('imap-password').value,
        inbox_folder: document.getElementById('imap-inbox-folder').value
    };
    
    try {
        const response = await fetch(`${API_BASE}/imap/test`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(config)
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('[AdminSettings] IMAP connection successful, folders:', result.data.length);
            showAlert('imap-modal-alert', '✅ Connection successful! Found ' + result.data.length + ' folders.', 'success');
        } else {
            console.error('[AdminSettings] IMAP connection failed:', result.message);
            showAlert('imap-modal-alert', '❌ ' + result.message, 'error');
        }
    } catch (error) {
        console.error('[AdminSettings] Error testing IMAP:', error);
        showAlert('imap-modal-alert', 'Failed to test connection: ' + error.message, 'error');
    } finally {
        button.disabled = false;
        button.textContent = originalText;
    }
});

// Save IMAP configuration
document.getElementById('imap-save-button').addEventListener('click', async () => {
    const button = document.getElementById('imap-save-button');
    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'Saving...';
    
    console.log('[AdminSettings] Saving IMAP configuration...');
    
    const config = {
        host: document.getElementById('imap-host').value,
        port: parseInt(document.getElementById('imap-port').value),
        ssl: document.getElementById('imap-ssl').checked,
        username: document.getElementById('imap-username').value,
        inbox_folder: document.getElementById('imap-inbox-folder').value
    };
    
    // Only include password if changed
    const password = document.getElementById('imap-password').value;
    if (password) {
        config.password = password;
    }
    
    try {
        const response = await fetch(`${API_BASE}/imap`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(config)
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('[AdminSettings] IMAP config saved successfully');
            showAlert('imap-modal-alert', '✅ Configuration saved successfully!', 'success');
            
            // Close modal after 1.5s
            setTimeout(() => {
                closeModal('imapConfigModal');
                loadImapConfig(); // Reload to update status
            }, 1500);
        } else {
            console.error('[AdminSettings] Failed to save IMAP config:', result.error);
            showAlert('imap-modal-alert', result.error, 'error');
        }
    } catch (error) {
        console.error('[AdminSettings] Error saving IMAP config:', error);
        showAlert('imap-modal-alert', 'Failed to save configuration: ' + error.message, 'error');
    } finally {
        button.disabled = false;
        button.textContent = originalText;
    }
});

// ============================================================================
// SMTP CONFIGURATION
// ============================================================================

// Load SMTP config on page load
async function loadSmtpConfig() {
    console.log('[AdminSettings] Loading SMTP config...');
    try {
        const response = await fetch(`${API_BASE}/smtp`);
        const result = await response.json();
        
        if (result.success) {
            const config = result.data;
            console.log('[AdminSettings] SMTP config loaded:', { 
                host: config.host, 
                configured: config.configured 
            });
            
            // (Add similar UI updates as IMAP when SMTP UI is implemented)
        } else {
            console.error('[AdminSettings] Failed to load SMTP config:', result.error);
        }
    } catch (error) {
        console.error('[AdminSettings] Error loading SMTP config:', error);
    }
}

// ============================================================================
// SMTP CONFIGURATION
// ============================================================================

let smtpModalMode = 'view'; // 'view' or 'edit'

// Load SMTP config on page load
async function loadSmtpConfig() {
    console.log('[AdminSettings] Loading SMTP config...');
    try {
        const response = await fetch(`${API_BASE}/smtp`);
        const result = await response.json();
        
        if (result.success) {
            const config = result.data;
            console.log('[AdminSettings] SMTP config loaded:', { 
                host: config.host, 
                configured: config.configured 
            });
            
            // Update status badge
            const badge = document.getElementById('smtp-status-badge');
            const configButton = document.getElementById('smtp-config-button');
            
            if (config.configured) {
                badge.textContent = 'Configured';
                badge.className = 'badge badge-success';
                configButton.textContent = 'Edit Configuration';
            } else {
                badge.textContent = 'Not Configured';
                badge.className = 'badge badge-warning';
                configButton.textContent = 'Configure';
            }
            
            configButton.disabled = false;
            
            // Fill form fields
            document.getElementById('smtp-host').value = config.host || '';
            document.getElementById('smtp-port').value = config.port || 465;
            document.getElementById('smtp-ssl').checked = config.ssl === true;
            document.getElementById('smtp-auth').checked = config.auth === true;
            document.getElementById('smtp-username').value = config.username || '';
            document.getElementById('smtp-password').value = ''; // Never show password
            document.getElementById('smtp-from-name').value = config.from_name || '';
            document.getElementById('smtp-from-email').value = config.from_email || '';
            
            // Toggle auth fields visibility
            toggleSmtpAuthFields();
            
            // Show configured info
            if (config.configured) {
                document.getElementById('smtp-configured-info').style.display = 'block';
                document.getElementById('smtp-configured-host').textContent = config.host;
                document.getElementById('smtp-configured-from').textContent = config.from_email;
            }
        } else {
            console.error('[AdminSettings] Failed to load SMTP config:', result.error);
            showAlert('smtp-alert', result.error, 'error');
        }
    } catch (error) {
        console.error('[AdminSettings] Error loading SMTP config:', error);
        showAlert('smtp-alert', 'Failed to load configuration: ' + error.message, 'error');
    }
}

// Open SMTP configuration modal
document.addEventListener('DOMContentLoaded', () => {
    const smtpConfigButton = document.getElementById('smtp-config-button');
    if (smtpConfigButton) {
        smtpConfigButton.addEventListener('click', () => {
            console.log('[AdminSettings] Opening SMTP config modal...');
            smtpModalMode = 'edit';
            openModal('smtpConfigModal');
        });
    }
});

// Toggle SMTP auth fields based on auth checkbox
function toggleSmtpAuthFields() {
    const authEnabled = document.getElementById('smtp-auth').checked;
    const usernameRow = document.getElementById('smtp-auth-row-username');
    const passwordRow = document.getElementById('smtp-auth-row-password');
    
    if (usernameRow) {
        usernameRow.style.display = authEnabled ? '' : 'none';
    }
    if (passwordRow) {
        passwordRow.style.display = authEnabled ? '' : 'none';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const smtpAuthCheckbox = document.getElementById('smtp-auth');
    if (smtpAuthCheckbox) {
        smtpAuthCheckbox.addEventListener('change', toggleSmtpAuthFields);
    }
});

// Auto-discover SMTP settings
async function autodiscoverSmtp() {
    const email = prompt('Enter your email address for auto-discovery:');
    if (!email || !email.includes('@')) {
        showAlert('smtp-modal-alert', 'Please enter a valid email address', 'error');
        return;
    }
    
    console.log('[AdminSettings] Auto-discovering SMTP settings...', { email });
    showAlert('smtp-modal-alert', 'Detecting SMTP settings...', 'info');
    
    try {
        const response = await fetch(`${API_BASE}/smtp/autodiscover`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email })
        });
        
        const result = await response.json();
        
        if (result.success && result.config) {
            console.log('[AdminSettings] SMTP auto-discovery successful:', result.config);
            
            // Fill form with detected settings
            document.getElementById('smtp-host').value = result.config.host;
            document.getElementById('smtp-port').value = result.config.port;
            document.getElementById('smtp-ssl').checked = result.config.ssl;
            document.getElementById('smtp-auth').checked = result.config.auth;
            toggleSmtpAuthFields();
            
            // Pre-fill username with email
            const username = document.getElementById('smtp-username').value;
            if (!username) {
                document.getElementById('smtp-username').value = email;
            }
            
            // Pre-fill from email if empty
            const fromEmail = document.getElementById('smtp-from-email').value;
            if (!fromEmail) {
                document.getElementById('smtp-from-email').value = email;
            }
            
            showAlert('smtp-modal-alert', `Settings detected! Host: ${result.config.host}, Port: ${result.config.port}. Please enter your password.`, 'success');
        } else {
            console.error('[AdminSettings] SMTP auto-discovery failed:', result.error);
            showAlert('smtp-modal-alert', result.error || 'Could not detect settings. Please enter manually.', 'error');
        }
    } catch (error) {
        console.error('[AdminSettings] SMTP auto-discovery error:', error);
        showAlert('smtp-modal-alert', 'Auto-discovery failed: ' + error.message, 'error');
    }
}

// Add autodiscover button event listener
document.addEventListener('DOMContentLoaded', () => {
    const autodiscoverBtn = document.getElementById('smtp-autodiscover-button');
    if (autodiscoverBtn) {
        autodiscoverBtn.addEventListener('click', autodiscoverSmtp);
    }
});

// Test SMTP connection
document.addEventListener('DOMContentLoaded', () => {
    const testButton = document.getElementById('smtp-test-button');
    if (testButton) {
        testButton.addEventListener('click', async () => {
            const button = testButton;
            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = 'Testing...';
            
            console.log('[AdminSettings] Testing SMTP connection...');
            
            const config = {
                host: document.getElementById('smtp-host').value,
                port: parseInt(document.getElementById('smtp-port').value),
                ssl: document.getElementById('smtp-ssl').checked,
                auth: document.getElementById('smtp-auth').checked,
                username: document.getElementById('smtp-username').value,
                password: document.getElementById('smtp-password').value,
                from_name: document.getElementById('smtp-from-name').value,
                from_email: document.getElementById('smtp-from-email').value
            };
            
            try {
                const response = await fetch(`${API_BASE}/smtp/test`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(config)
                });
                
                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('[AdminSettings] Non-JSON response:', text);
                    throw new Error('Server returned non-JSON response. Check logs for details.');
                }
                
                const result = await response.json();
                
                if (result.success) {
                    console.log('[AdminSettings] SMTP test successful');
                    showAlert('smtp-modal-alert', '✅ Connection successful! SMTP server is reachable.', 'success');
                } else {
                    console.error('[AdminSettings] SMTP test failed:', result.message);
                    showAlert('smtp-modal-alert', '❌ Connection failed: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('[AdminSettings] SMTP test error:', error);
                showAlert('smtp-modal-alert', '❌ Test failed: ' + error.message, 'error');
            } finally {
                button.disabled = false;
                button.textContent = originalText;
            }
        });
    }
});

// Save SMTP configuration
document.addEventListener('DOMContentLoaded', () => {
    const saveButton = document.getElementById('smtp-save-button');
    if (saveButton) {
        saveButton.addEventListener('click', async () => {
            const button = saveButton;
            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = 'Saving...';
            
            console.log('[AdminSettings] Saving SMTP configuration...');
            
            const config = {
                host: document.getElementById('smtp-host').value,
                port: parseInt(document.getElementById('smtp-port').value),
                ssl: document.getElementById('smtp-ssl').checked,
                auth: document.getElementById('smtp-auth').checked,
                username: document.getElementById('smtp-username').value,
                password: document.getElementById('smtp-password').value,
                from_name: document.getElementById('smtp-from-name').value,
                from_email: document.getElementById('smtp-from-email').value
            };
            
            try {
                const response = await fetch(`${API_BASE}/smtp`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(config)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    console.log('[AdminSettings] SMTP config saved successfully');
                    showAlert('smtp-modal-alert', '✅ Configuration saved successfully!', 'success');
                    
                    // Reload config to update UI
                    setTimeout(() => {
                        closeModal('smtpConfigModal');
                        loadSmtpConfig();
                    }, 1500);
                } else {
                    console.error('[AdminSettings] Failed to save SMTP config:', result.error);
                    showAlert('smtp-modal-alert', '❌ Save failed: ' + result.error, 'error');
                }
            } catch (error) {
                console.error('[AdminSettings] Error saving SMTP config:', error);
                showAlert('smtp-modal-alert', '❌ Save failed: ' + error.message, 'error');
            } finally {
                button.disabled = false;
                button.textContent = originalText;
            }
        });
    }
});

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function showAlert(containerId, message, type = 'info') {
    const container = document.getElementById(containerId);
    if (!container) {
        console.warn('[AdminSettings] Alert container not found:', containerId);
        return;
    }
    
    const alertClass = type === 'success' ? 'c-alert--success' : 
                       type === 'error' ? 'c-alert--error' : 
                       type === 'warning' ? 'c-alert--warning' : 'c-alert--info';
    
    container.innerHTML = `
        <div class="c-alert ${alertClass} is-visible" role="alert">
            ${message}
        </div>
    `;
    
    // Auto-hide success messages after 5 seconds
    if (type === 'success') {
        setTimeout(() => {
            const alert = container.querySelector('.alert');
            if (alert) {
                alert.style.display = 'none';
            }
        }, 5000);
    }
}

// ============================================================================
// CRON MONITOR
// ============================================================================

let cronRefreshInterval = null;

// Load Cron Monitor status
async function loadCronStatus() {
    console.log('[AdminSettings] Loading cron status...');
    try {
        const response = await fetch('/api/admin/cron/status');
        const result = await response.json();
        
        if (result.success) {
            const data = result.data;
            console.log('[AdminSettings] Cron status loaded:', data);
            
            // Update status badge
            const badge = document.getElementById('cron-status-badge');
            const statusColors = {
                'green': 'c-status-badge--success',
                'yellow': 'c-status-badge--warning',
                'red': 'c-status-badge--danger'
            };
            
            badge.className = `c-status-badge ${statusColors[data.color] || 'c-status-badge--warning'}`;
            badge.innerHTML = `<span class="status-dot"></span>${data.status}`;
            
            // Update last execution
            const lastExec = document.getElementById('cron-last-execution');
            if (data.last_execution) {
                lastExec.textContent = `${data.last_execution.relative_time} (${data.last_execution.duration})`;
            } else {
                lastExec.textContent = '—';
            }
            
            // Update executions count
            const execCount = document.getElementById('cron-executions-count');
            execCount.textContent = data.executions_last_hour;
            
            // Enable history button if there are executions
            const historyButton = document.getElementById('cron-view-history-button');
            if (data.total_executions > 0) {
                historyButton.disabled = false;
            }
            
        } else {
            console.error('[AdminSettings] Failed to load cron status:', result.error);
            showCronAlert('Failed to load cron status: ' + result.error, 'danger');
        }
    } catch (error) {
        console.error('[AdminSettings] Error loading cron status:', error);
        showCronAlert('Error loading cron status: ' + error.message, 'danger');
    }
}

// View cron history
async function viewCronHistory() {
    console.log('[AdminSettings] Loading cron history...');
    
    // Show modal
    openModal('cronHistoryModal');
    
    // Load history
    const tbody = document.getElementById('cron-history-table-body');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center">Loading...</td></tr>';
    
    try {
        const response = await fetch('/api/admin/cron/history?limit=20');
        const result = await response.json();
        
        if (result.success) {
            const history = result.data;
            console.log('[AdminSettings] Cron history loaded:', history.length, 'executions');
            
            if (history.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center">No executions recorded yet</td></tr>';
            } else {
                tbody.innerHTML = history.map(exec => `
                    <tr>
                        <td>${exec.relative_time}</td>
                        <td>${exec.accounts_polled}</td>
                        <td>${exec.new_emails_found}</td>
                        <td>${exec.duration}</td>
                        <td>
                            <span class="badge ${exec.status === 'success' ? 'bg-success' : 'bg-danger'}">
                                ${exec.status}
                            </span>
                        </td>
                    </tr>
                `).join('');
            }
        } else {
            console.error('[AdminSettings] Failed to load cron history:', result.error);
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Failed to load history</td></tr>';
        }
    } catch (error) {
        console.error('[AdminSettings] Error loading cron history:', error);
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading history</td></tr>';
    }
}

// Start auto-refresh for cron status (every 60 seconds)
function startCronAutoRefresh() {
    if (cronRefreshInterval) {
        clearInterval(cronRefreshInterval);
    }
    
    cronRefreshInterval = setInterval(() => {
        console.log('[AdminSettings] Auto-refreshing cron status...');
        loadCronStatus();
    }, 60000); // 60 seconds
}

// Stop auto-refresh
function stopCronAutoRefresh() {
    if (cronRefreshInterval) {
        clearInterval(cronRefreshInterval);
        cronRefreshInterval = null;
    }
}

// Show alert in cron card
function showCronAlert(message, type = 'info') {
    const alertDiv = document.getElementById('cron-alert');
    alertDiv.innerHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
}

// ============================================================================
// USER MANAGEMENT
// ============================================================================

const USER_API_BASE = '/api/users';
let users = [];
let currentUserId = null;

// Load users
async function loadUsers() {
    console.log('[AdminSettings] Loading users...');
    const tbody = document.getElementById('users-table-body');
    if (!tbody) return;
    
    try {
        const response = await fetch(USER_API_BASE);
        const result = await response.json();
        
        if (response.ok && result.users) {
            users = result.users;
            console.log('[AdminSettings] Loaded users:', users.length);
            
            if (users.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" style="padding: 3rem; text-align: center; color: #999;">
                            <svg width="64" height="64" viewBox="0 0 20 20" fill="currentColor" style="opacity: 0.5; margin-bottom: 1rem;">
                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                            </svg>
                            <h4>No users yet</h4>
                            <p>Click "Add User" to create your first user</p>
                        </td>
                    </tr>
                `;
            } else {
                tbody.innerHTML = users.map(user => {
                    const lastLogin = user.last_login_at ? formatRelativeDate(user.last_login_at) : '—';
                    return `
                        <tr style="border-top: 1px solid #f0f0f0;">
                            <td style="padding: 1rem 1.5rem;"><strong>${escapeHtml(user.name)}</strong></td>
                            <td style="padding: 1rem 1.5rem;">${escapeHtml(user.email)}</td>
                            <td style="padding: 1rem 1.5rem;">
                                <span class="badge ${user.role === 'admin' ? 'bg-primary' : 'bg-secondary'}">
                                    ${user.role}
                                </span>
                            </td>
                            <td style="padding: 1rem 1.5rem;">
                                <span class="badge ${user.is_active ? 'bg-success' : 'bg-danger'}">
                                    ${user.is_active ? 'Active' : 'Inactive'}
                                </span>
                            </td>
                            <td style="padding: 1rem 1.5rem;">${lastLogin}</td>
                            <td style="padding: 1rem 1.5rem;">
                                <div style="display: flex; gap: 0.5rem;">
                                    <button class="btn btn-sm btn-secondary" onclick="editUser(${user.id})">
                                        Edit
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="confirmDeleteUser(${user.id})">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            }
        } else {
            throw new Error(result.error || 'Failed to load users');
        }
    } catch (error) {
        console.error('[AdminSettings] Error loading users:', error);
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="padding: 2rem; text-align: center;" class="text-danger">
                    Failed to load users. Please refresh the page.
                </td>
            </tr>
        `;
        showUserAlert('Failed to load users: ' + error.message, 'danger');
    }
}

// Open add user modal
function openAddUserModal() {
    console.log('[AdminSettings] Opening add user modal');
    currentUserId = null;
    
    document.getElementById('userModalTitle').textContent = 'Add User';
    document.getElementById('user-form').reset();
    document.getElementById('user-id').value = '';
    document.getElementById('user-active').checked = true;
    document.getElementById('password-group').style.display = 'block';
    document.getElementById('user-password').required = true;
    
    openModal('userModal');
}

// Edit user
window.editUser = function(userId) {
    console.log('[AdminSettings] Editing user:', userId);
    currentUserId = userId;
    
    const user = users.find(u => u.id === userId);
    if (!user) {
        showUserAlert('User not found', 'danger');
        return;
    }
    
    document.getElementById('userModalTitle').textContent = 'Edit User';
    document.getElementById('user-id').value = user.id;
    document.getElementById('user-name').value = user.name;
    document.getElementById('user-email').value = user.email;
    document.getElementById('user-role').value = user.role;
    document.getElementById('user-active').checked = user.is_active;
    
    // Hide password field for edit
    document.getElementById('password-group').style.display = 'none';
    document.getElementById('user-password').required = false;
    document.getElementById('user-password').value = '';
    
    openModal('userModal');
};

// Save user
async function saveUser() {
    console.log('[AdminSettings] Saving user...');
    
    const form = document.getElementById('user-form');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const userId = document.getElementById('user-id').value;
    const data = {
        name: document.getElementById('user-name').value,
        email: document.getElementById('user-email').value,
        role: document.getElementById('user-role').value,
        is_active: document.getElementById('user-active').checked
    };
    
    // Add password only for new users or if provided in edit
    const password = document.getElementById('user-password').value;
    if (password) {
        data.password = password;
    }
    
    const saveBtn = document.getElementById('btn-save-user');
    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving...';
    
    try {
        const url = userId ? `${USER_API_BASE}/${userId}` : USER_API_BASE;
        const method = userId ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (response.ok) {
            console.log('[AdminSettings] User saved:', result);
            showUserAlert(userId ? 'User updated successfully' : 'User created successfully', 'success');
            userModal.hide();
            loadUsers();
        } else {
            throw new Error(result.error || 'Failed to save user');
        }
    } catch (error) {
        console.error('[AdminSettings] Error saving user:', error);
        showUserAlert('Failed to save user: ' + error.message, 'danger');
    } finally {
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save';
    }
}

// Confirm delete user
window.confirmDeleteUser = function(userId) {
    console.log('[AdminSettings] Confirming delete for user:', userId);
    currentUserId = userId;
    
    const user = users.find(u => u.id === userId);
    if (!user) {
        showUserAlert('User not found', 'danger');
        return;
    }
    
    document.getElementById('delete-user-info').innerHTML = `
        <strong>${escapeHtml(user.name)}</strong><br>
        ${escapeHtml(user.email)}
    `;
    
    openModal('deleteUserModal');
};

// Delete user
async function deleteUser() {
    console.log('[AdminSettings] Deleting user:', currentUserId);
    
    const deleteBtn = document.getElementById('btn-confirm-delete');
    deleteBtn.disabled = true;
    deleteBtn.textContent = 'Deleting...';
    
    try {
        const response = await fetch(`${USER_API_BASE}/${currentUserId}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (response.ok) {
            console.log('[AdminSettings] User deleted');
            showUserAlert('User deleted successfully', 'success');
            deleteUserModal.hide();
            loadUsers();
        } else {
            throw new Error(result.error || 'Failed to delete user');
        }
    } catch (error) {
        console.error('[AdminSettings] Error deleting user:', error);
        showUserAlert('Failed to delete user: ' + error.message, 'danger');
    } finally {
        deleteBtn.disabled = false;
        deleteBtn.textContent = 'Delete';
    }
}

// Show user alert
function showUserAlert(message, type = 'info') {
    const container = document.getElementById('user-alert-container');
    if (!container) return;
    
    const alertId = 'user-alert-' + Date.now();
    
    const alert = document.createElement('div');
    alert.id = alertId;
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.role = 'alert';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    container.appendChild(alert);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        const alertElement = document.getElementById(alertId);
        if (alertElement) {
            alertElement.remove();
        }
    }, 5000);
}

// Format relative date
function formatRelativeDate(isoString) {
    const date = new Date(isoString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    
    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins} min ago`;
    if (diffHours < 24) return `${diffHours} hours ago`;
    if (diffDays < 7) return `${diffDays} days ago`;
    
    return date.toLocaleDateString('de-DE', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================================================
// INITIALIZATION
// ============================================================================

document.addEventListener('DOMContentLoaded', () => {
    console.log('[AdminSettings] DOM loaded, initializing...');
    
    // Load configurations
    loadImapConfig();
    loadSmtpConfig();
    loadCronStatus();
    
    // Start cron auto-refresh
    startCronAutoRefresh();
    
    // Cron history button
    const cronHistoryBtn = document.getElementById('cron-view-history-button');
    if (cronHistoryBtn) {
        cronHistoryBtn.addEventListener('click', viewCronHistory);
    }
    
    // User modals use custom CSS modal system (no initialization needed)
    
    // Modal close buttons
    const modalCloseButtons = [
        'imapConfigModal-close',
        'imapConfigModal-cancel',
        'smtpConfigModal-close',
        'smtpConfigModal-cancel',
        'cronHistoryModal-close',
        'cronHistoryModal-cancel',
        'userModal-close',
        'userModal-cancel',
        'deleteUserModal-close',
        'deleteUserModal-cancel',
        'signature-modal-close',
        'signature-cancel-btn'
    ];
    
    modalCloseButtons.forEach(buttonId => {
        const button = document.getElementById(buttonId);
        if (button) {
            button.addEventListener('click', () => {
                // Find parent modal
                const modal = button.closest('.c-modal');
                if (modal) {
                    closeModal(modal.id);
                }
            });
        }
    });
    
    // User management event listeners
    const btnAddUser = document.getElementById('btn-add-user');
    if (btnAddUser) {
        btnAddUser.addEventListener('click', openAddUserModal);
    }
    
    const btnSaveUser = document.getElementById('btn-save-user');
    if (btnSaveUser) {
        btnSaveUser.addEventListener('click', saveUser);
    }
    
    const btnConfirmDelete = document.getElementById('btn-confirm-delete');
    if (btnConfirmDelete) {
        btnConfirmDelete.addEventListener('click', deleteUser);
    }
    
    const userForm = document.getElementById('user-form');
    if (userForm) {
        userForm.addEventListener('submit', (e) => {
            e.preventDefault();
            saveUser();
        });
    }
    
    // Load users when Users tab is shown
    const usersTab = document.querySelector('[data-tab="users"]');
    if (usersTab) {
        usersTab.addEventListener('click', () => {
            setTimeout(loadUsers, 100); // Small delay to ensure tab is visible
        });
    }
});

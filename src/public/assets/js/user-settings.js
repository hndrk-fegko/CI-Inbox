/**
 * User Settings JavaScript
 * 
 * Handles:
 * - Tab switching
 * - Profile updates
 * - Password changes
 * - Personal IMAP account CRUD
 * - Connection testing
 */

// API Base URL
const API_BASE = '/api/user';

// ============================================================================
// TAB SWITCHING
// ============================================================================

document.querySelectorAll('.c-tabs__tab').forEach(tab => {
    tab.addEventListener('click', () => {
        const tabId = tab.dataset.tab;
        console.log('[UserSettings] Tab switched:', tabId);
        
        // Remove is-active class from all tabs
        document.querySelectorAll('.c-tabs__tab').forEach(t => t.classList.remove('is-active'));
        document.querySelectorAll('.c-tabs__content').forEach(c => c.classList.remove('is-active'));
        
        // Add is-active class to clicked tab
        tab.classList.add('is-active');
        document.getElementById(`${tabId}-tab`).classList.add('is-active');
        
        // Load data for tab
        if (tabId === 'profile') {
            loadProfile();
        } else if (tabId === 'imap') {
            loadImapAccounts();
        } else if (tabId === 'signatures') {
            loadSignatures();
        }
    });
});

// ============================================================================
// PROFILE MANAGEMENT
// ============================================================================

async function loadProfile() {
    console.log('[UserSettings] Loading profile...');
    try {
        const response = await fetch(`${API_BASE}/profile`);
        const result = await response.json();
        
        if (result.success) {
            const profile = result.data;
            console.log('[UserSettings] Profile loaded:', { name: profile.name, email: profile.email, timezone: profile.timezone });
            document.getElementById('profile-name').value = profile.name || '';
            document.getElementById('profile-email').value = profile.email || '';
            document.getElementById('profile-timezone').value = profile.timezone || 'UTC';
            document.getElementById('profile-language').value = profile.language || 'de';
            document.getElementById('profile-theme').value = profile.theme_mode || 'auto';
            
            // Set avatar color
            const avatarColor = profile.avatar_color || ((profile.id % 8) + 1);
            console.log('[UserSettings] Setting avatar color:', avatarColor);
            const colorRadio = document.querySelector(`input[name="avatar_color"][value="${avatarColor}"]`);
            if (colorRadio) {
                colorRadio.checked = true;
                // CSS :checked pseudo-class handles the visual feedback automatically
            } else {
                console.warn('[UserSettings] Avatar color radio not found:', avatarColor);
            }
        } else {
            console.error('[UserSettings] Failed to load profile:', result.error);
            showAlert('profile-alert', result.error, 'error');
        }
    } catch (error) {
        console.error('[UserSettings] Error loading profile:', error);
        showAlert('profile-alert', 'Failed to load profile: ' + error.message, 'error');
    }
}

document.getElementById('profile-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = {
        name: document.getElementById('profile-name').value,
        email: document.getElementById('profile-email').value,
        timezone: document.getElementById('profile-timezone').value,
        language: document.getElementById('profile-language').value,
        avatar_color: parseInt(document.querySelector('input[name="avatar_color"]:checked')?.value || 1)
    };
    
    const themeMode = document.getElementById('profile-theme').value;
    
    console.log('[UserSettings] Updating profile:', formData);
    
    try {
        // Update profile
        const response = await fetch(`${API_BASE}/profile`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('[UserSettings] Profile updated successfully');
            
            // Update theme separately
            const themeResponse = await fetch(`${API_BASE}/theme`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ theme_mode: themeMode })
            });
            
            const themeResult = await themeResponse.json();
            
            if (themeResult.success) {
                console.log('[UserSettings] Theme updated:', themeMode);
                
                // Update data-user-theme attribute for persistence
                document.documentElement.setAttribute('data-user-theme', themeMode);
                
                // Apply theme immediately via global themeManager
                if (window.themeManager) {
                    window.themeManager.applyTheme(themeMode);
                } else {
                    console.warn('[UserSettings] themeManager not available, setting data-theme directly');
                    const effectiveTheme = themeMode === 'auto' 
                        ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                        : themeMode;
                    document.documentElement.setAttribute('data-theme', effectiveTheme);
                }
                
                showAlert('profile-alert', 'Profile and theme updated successfully!', 'success');
            } else {
                console.error('[UserSettings] Theme update failed:', themeResult.error);
                showAlert('profile-alert', 'Profile updated, but theme update failed: ' + themeResult.error, 'warning');
            }
        } else {
            console.error('[UserSettings] Profile update failed:', result.error);
            showAlert('profile-alert', result.error, 'error');
        }
    } catch (error) {
        console.error('[UserSettings] Error updating profile:', error);
        showAlert('profile-alert', 'Failed to update profile: ' + error.message, 'error');
    }
});

// ============================================================================
// PASSWORD CHANGE
// ============================================================================

document.getElementById('password-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const currentPassword = document.getElementById('current-password').value;
    const newPassword = document.getElementById('new-password').value;
    const confirmPassword = document.getElementById('confirm-password').value;
    
    console.log('[UserSettings] Password change requested');
    
    // Validate passwords match
    if (newPassword !== confirmPassword) {
        console.warn('[UserSettings] Password validation failed: passwords do not match');
        showAlert('password-alert', 'New passwords do not match!', 'error');
        return;
    }
    
    // Validate password length
    if (newPassword.length < 8) {
        console.warn('[UserSettings] Password validation failed: too short (length:', newPassword.length, ')');
        showAlert('password-alert', 'Password must be at least 8 characters!', 'error');
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/profile/change-password`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                current_password: currentPassword,
                new_password: newPassword
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('[UserSettings] Password changed successfully');
            showAlert('password-alert', 'Password changed successfully!', 'success');
            // Reset form
            document.getElementById('password-form').reset();
        } else {
            console.error('[UserSettings] Password change failed:', result.error);
            showAlert('password-alert', result.error, 'error');
        }
    } catch (error) {
        console.error('[UserSettings] Error changing password:', error);
        showAlert('password-alert', 'Failed to change password: ' + error.message, 'error');
    }
});

// ============================================================================
// PERSONAL IMAP ACCOUNTS
// ============================================================================

let currentImapAccountId = null;

async function loadImapAccounts() {
    console.log('[UserSettings] Loading IMAP accounts...');
    try {
        const response = await fetch(`${API_BASE}/imap-accounts`);
        const result = await response.json();
        
        if (result.success) {
            const accounts = result.data;
            console.log('[UserSettings] IMAP accounts loaded:', accounts.length, 'accounts');
            const list = document.getElementById('imap-accounts-list');
            const emptyState = document.getElementById('imap-empty-state');
            
            if (accounts.length === 0) {
                console.log('[UserSettings] No IMAP accounts found');
                list.innerHTML = '';
                emptyState.style.display = 'block';
            } else {
                emptyState.style.display = 'none';
                list.innerHTML = accounts.map(account => `
                    <li class="c-settings-list__item" data-id="${account.id}">
                        <div class="c-settings-list__item-info">
                            <h3 class="c-settings-list__item-title">${escapeHtml(account.label)}</h3>
                            <p class="c-settings-list__item-description">${escapeHtml(account.email)} - ${escapeHtml(account.imap_host)}:${account.imap_port}</p>
                        </div>
                        <div class="c-settings-list__item-actions">
                            <button class="c-button c-button--success c-button--sm btn-test" data-id="${account.id}">Test</button>
                            <button class="c-button c-button--secondary c-button--sm btn-edit" data-id="${account.id}">Edit</button>
                            <button class="c-button c-button--danger c-button--sm btn-delete" data-id="${account.id}">Delete</button>
                        </div>
                    </li>
                `).join('');
                
                // Attach event listeners
                attachImapAccountListeners();
            }
        } else {
            console.error('[UserSettings] Failed to load IMAP accounts:', result.error);
            showAlert('imap-alert', result.error, 'error');
        }
    } catch (error) {
        console.error('[UserSettings] Error loading IMAP accounts:', error);
        showAlert('imap-alert', 'Failed to load accounts: ' + error.message, 'error');
    }
}

// Auto-discover IMAP settings
async function autodiscoverImap() {
    const email = prompt('Enter your email address for auto-discovery:');
    if (!email) {
        console.log('[UserSettings] IMAP auto-discovery cancelled');
        return;
    }
    
    console.log('[UserSettings] Auto-discovering IMAP settings...', { email });
    
    const autodiscoverBtn = document.getElementById('imap-autodiscover-button');
    autodiscoverBtn.disabled = true;
    autodiscoverBtn.textContent = 'Discovering...';
    
    try {
        const response = await fetch(`/api/admin/imap/autodiscover`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email })
        });
        
        const result = await response.json();
        
        if (result.success && result.config) {
            console.log('[UserSettings] IMAP auto-discovery successful:', result.config);
            
            // Fill form with discovered settings
            document.getElementById('imap-email').value = email;
            document.getElementById('imap-host').value = result.config.host;
            document.getElementById('imap-port').value = result.config.port;
            document.getElementById('imap-ssl').checked = result.config.ssl;
            document.getElementById('imap-username').value = email; // Default username to email
            
            // Auto-fill label
            const domain = email.split('@')[1];
            document.getElementById('imap-label').value = `${domain} Account`;
            
            showAlert('imap-modal-alert', `Settings detected: ${result.config.host}:${result.config.port}. Please enter your password.`, 'success');
        } else {
            console.error('[UserSettings] IMAP auto-discovery failed:', result.error);
            showAlert('imap-modal-alert', result.error || 'Auto-discovery failed', 'error');
        }
    } catch (error) {
        console.error('[UserSettings] IMAP auto-discovery error:', error);
        showAlert('imap-modal-alert', 'Auto-discovery failed: ' + error.message, 'error');
    } finally {
        autodiscoverBtn.disabled = false;
        autodiscoverBtn.textContent = 'Auto-discover';
    }
}

// Add autodiscover button event listener
document.addEventListener('DOMContentLoaded', () => {
    const autodiscoverBtn = document.getElementById('imap-autodiscover-button');
    if (autodiscoverBtn) {
        autodiscoverBtn.addEventListener('click', autodiscoverImap);
    }
});

function attachImapAccountListeners() {
    // Test connection buttons
    document.querySelectorAll('.btn-test').forEach(btn => {
        btn.addEventListener('click', async () => {
            const accountId = btn.dataset.id;
            console.log('[UserSettings] Testing IMAP connection for account:', accountId);
            btn.disabled = true;
            btn.textContent = 'Testing...';
            
            try {
                const response = await fetch(`${API_BASE}/imap-accounts/${accountId}/test-connection`, {
                    method: 'POST'
                });
                const result = await response.json();
                
                if (result.success) {
                    console.log('[UserSettings] IMAP connection test successful for account:', accountId);
                    showAlert('imap-alert', 'Connection successful!', 'success');
                } else {
                    console.error('[UserSettings] IMAP connection test failed for account:', accountId, 'Error:', result.error);
                    showAlert('imap-alert', 'Connection failed: ' + result.error, 'error');
                }
            } catch (error) {
                console.error('[UserSettings] IMAP connection test error for account:', accountId, error);
                showAlert('imap-alert', 'Test failed: ' + error.message, 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Test';
            }
        });
    });
    
    // Edit buttons
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', async () => {
            const accountId = btn.dataset.id;
            console.log('[UserSettings] Edit IMAP account clicked:', accountId);
            await editImapAccount(accountId);
        });
    });
    
    // Delete buttons
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', async () => {
            const accountId = btn.dataset.id;
            if (confirm('Are you sure you want to delete this IMAP account?')) {
                console.log('[UserSettings] Delete IMAP account confirmed:', accountId);
                await deleteImapAccount(accountId);
            } else {
                console.log('[UserSettings] Delete IMAP account cancelled:', accountId);
            }
        });
    });
}

// Add Account Button
document.getElementById('add-imap-btn').addEventListener('click', () => {
    console.log('[UserSettings] Add IMAP account button clicked');
    currentImapAccountId = null;
    document.getElementById('imap-modal-title').textContent = 'Add IMAP Account';
    document.getElementById('imap-form').reset();
    document.getElementById('imap-id').value = '';
    document.getElementById('imap-ssl').checked = true;
    document.getElementById('imap-port').value = '993';
    document.getElementById('imap-modal').classList.add('is-open');
});

// Modal Close Buttons
document.getElementById('imap-modal-close').addEventListener('click', () => {
    document.getElementById('imap-modal').classList.remove('is-open');
});

document.getElementById('imap-cancel-btn').addEventListener('click', () => {
    document.getElementById('imap-modal').classList.remove('is-open');
});

// Test Connection in Modal
document.getElementById('imap-test-btn').addEventListener('click', async () => {
    const formData = getImapFormData();
    const testBtn = document.getElementById('imap-test-btn');
    
    console.log('[UserSettings] Testing IMAP connection in modal:', { host: formData.imap_host, port: formData.imap_port });
    
    testBtn.disabled = true;
    testBtn.textContent = 'Testing...';
    
    try {
        // If editing existing account, use test endpoint
        if (currentImapAccountId) {
            const response = await fetch(`${API_BASE}/imap-accounts/${currentImapAccountId}/test-connection`, {
                method: 'POST'
            });
            const result = await response.json();
            
            if (result.success) {
                console.log('[UserSettings] IMAP connection test successful in modal');
                showAlert('imap-modal-alert', 'Connection successful!', 'success');
            } else {
                console.error('[UserSettings] IMAP connection test failed in modal:', result.error);
                showAlert('imap-modal-alert', 'Connection failed: ' + result.error, 'error');
            }
        } else {
            console.warn('[UserSettings] Cannot test connection for unsaved account');
            showAlert('imap-modal-alert', 'Please save the account first to test connection.', 'error');
        }
    } catch (error) {
        console.error('[UserSettings] IMAP connection test error in modal:', error);
        showAlert('imap-modal-alert', 'Test failed: ' + error.message, 'error');
    } finally {
        testBtn.disabled = false;
        testBtn.textContent = 'Test Connection';
    }
});

// Save IMAP Account
document.getElementById('imap-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = getImapFormData();
    const isEdit = currentImapAccountId !== null;
    
    console.log('[UserSettings]', isEdit ? 'Updating' : 'Creating', 'IMAP account:', { label: formData.label, email: formData.email, host: formData.imap_host });
    
    try {
        const url = isEdit 
            ? `${API_BASE}/imap-accounts/${currentImapAccountId}`
            : `${API_BASE}/imap-accounts`;
        
        const method = isEdit ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('[UserSettings] IMAP account', isEdit ? 'updated' : 'created', 'successfully:', result.data?.id);
            showAlert('imap-alert', `Account ${isEdit ? 'updated' : 'created'} successfully!`, 'success');
            document.getElementById('imap-modal').classList.remove('is-open');
            loadImapAccounts();
        } else {
            console.error('[UserSettings] Failed to', isEdit ? 'update' : 'create', 'IMAP account:', result.error);
            showAlert('imap-modal-alert', result.error, 'error');
        }
    } catch (error) {
        console.error('[UserSettings] Error saving IMAP account:', error);
        showAlert('imap-modal-alert', 'Failed to save account: ' + error.message, 'error');
    }
});

async function editImapAccount(accountId) {
    console.log('[UserSettings] Loading IMAP account for editing:', accountId);
    try {
        const response = await fetch(`${API_BASE}/imap-accounts/${accountId}`);
        const result = await response.json();
        
        if (result.success) {
            const account = result.data;
            console.log('[UserSettings] IMAP account loaded for editing:', { id: account.id, label: account.label, email: account.email });
            currentImapAccountId = accountId;
            
            document.getElementById('imap-modal-title').textContent = 'Edit IMAP Account';
            document.getElementById('imap-id').value = account.id;
            document.getElementById('imap-label').value = account.label;
            document.getElementById('imap-email').value = account.email;
            document.getElementById('imap-host').value = account.imap_host;
            document.getElementById('imap-port').value = account.imap_port;
            document.getElementById('imap-username').value = account.imap_username;
            document.getElementById('imap-password').value = ''; // Don't populate password
            document.getElementById('imap-ssl').checked = account.imap_ssl;
            
            document.getElementById('imap-modal').classList.add('is-open');
        } else {
            console.error('[UserSettings] Failed to load IMAP account for editing:', result.error);
            showAlert('imap-alert', result.error, 'error');
        }
    } catch (error) {
        console.error('[UserSettings] Error loading IMAP account for editing:', error);
        showAlert('imap-alert', 'Failed to load account: ' + error.message, 'error');
    }
}

async function deleteImapAccount(accountId) {
    console.log('[UserSettings] Deleting IMAP account:', accountId);
    try {
        const response = await fetch(`${API_BASE}/imap-accounts/${accountId}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('[UserSettings] IMAP account deleted successfully:', accountId);
            showAlert('imap-alert', 'Account deleted successfully!', 'success');
            loadImapAccounts();
        } else {
            console.error('[UserSettings] Failed to delete IMAP account:', result.error);
            showAlert('imap-alert', result.error, 'error');
        }
    } catch (error) {
        console.error('[UserSettings] Error deleting IMAP account:', error);
        showAlert('imap-alert', 'Failed to delete account: ' + error.message, 'error');
    }
}

function getImapFormData() {
    return {
        label: document.getElementById('imap-label').value,
        email: document.getElementById('imap-email').value,
        imap_host: document.getElementById('imap-host').value,
        imap_port: parseInt(document.getElementById('imap-port').value),
        imap_username: document.getElementById('imap-username').value,
        imap_password: document.getElementById('imap-password').value,
        imap_ssl: document.getElementById('imap-ssl').checked
    };
}

// ============================================================================
// EMAIL SIGNATURES MANAGEMENT
// ============================================================================

let currentSignatureId = null;
let smtpConfigured = false;

// Load signatures
async function loadSignatures() {
    console.log('[UserSettings] Loading signatures...');
    try {
        // Check SMTP status first
        const smtpResponse = await fetch(`${API_BASE}/signatures/smtp-status`);
        const smtpResult = await smtpResponse.json();
        smtpConfigured = smtpResult.smtp_configured || false;
        console.log('[UserSettings] SMTP configured:', smtpConfigured);
        
        // Show/hide SMTP warning
        const smtpWarning = document.getElementById('smtp-warning');
        const addSignatureBtn = document.getElementById('add-signature-btn');
        
        if (!smtpConfigured) {
            smtpWarning.style.display = 'block';
            addSignatureBtn.disabled = true;
            addSignatureBtn.style.opacity = '0.5';
            addSignatureBtn.style.cursor = 'not-allowed';
            addSignatureBtn.title = 'Configure SMTP to create personal signatures';
        } else {
            smtpWarning.style.display = 'none';
            addSignatureBtn.disabled = false;
            addSignatureBtn.style.opacity = '1';
            addSignatureBtn.style.cursor = 'pointer';
            addSignatureBtn.title = '';
        }
        
        // Load signatures
        const response = await fetch(`${API_BASE}/signatures`);
        const result = await response.json();
        
        if (result.success) {
            console.log('[UserSettings] Signatures loaded:', { count: result.data.length });
            renderSignatures(result.data);
        } else {
            console.error('[UserSettings] Failed to load signatures:', result.error);
            showAlert('signature-alert', result.error, 'error');
        }
    } catch (error) {
        console.error('[UserSettings] Error loading signatures:', error);
        showAlert('signature-alert', 'Failed to load signatures: ' + error.message, 'error');
    }
}

function renderSignatures(signatures) {
    const list = document.getElementById('signatures-list');
    const emptyState = document.getElementById('signatures-empty-state');
    
    if (signatures.length === 0) {
        list.style.display = 'none';
        emptyState.style.display = 'flex';
        return;
    }
    
    list.style.display = 'block';
    emptyState.style.display = 'none';
    
    list.innerHTML = signatures.map(signature => {
        const isPersonal = signature.type === 'personal';
        const isDisabled = isPersonal && !smtpConfigured;
        const opacityStyle = isDisabled ? 'opacity: 0.5;' : '';
        
        return `
        <li class="imap-account-item" style="${opacityStyle}">
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <div style="flex: 1;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                        <strong>${escapeHtml(signature.name)}</strong>
                        ${signature.is_default ? '<span style="background: #4CAF50; color: white; padding: 0.125rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">Default</span>' : ''}
                        ${signature.type === 'global' ? '<span style="background: #2196F3; color: white; padding: 0.125rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">Global</span>' : ''}
                        ${isDisabled ? '<span style="background: #999; color: white; padding: 0.125rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">SMTP Required</span>' : ''}
                    </div>
                    <div style="color: #666; font-size: 0.875rem; max-height: 3rem; overflow: hidden; white-space: pre-wrap;">${escapeHtml(signature.content.substring(0, 100))}${signature.content.length > 100 ? '...' : ''}</div>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    ${isPersonal && !isDisabled && !signature.is_default ? `<button class="c-button c-button--success" style="padding: 0.375rem 0.75rem; font-size: 0.875rem;" onclick="setDefaultSignature(${signature.id})">Set Default</button>` : ''}
                    ${isPersonal && !isDisabled ? `<button class="c-button c-button--primary" style="padding: 0.375rem 0.75rem; font-size: 0.875rem;" onclick="editSignature(${signature.id})">Edit</button>` : ''}
                    ${isPersonal && !isDisabled ? `<button class="c-button c-button--danger" style="padding: 0.375rem 0.75rem; font-size: 0.875rem;" onclick="deleteSignature(${signature.id})">Delete</button>` : ''}
                    ${signature.type === 'global' ? '<span style="color: #999; font-size: 0.875rem; font-style: italic;">Read-only (Admin managed)</span>' : ''}
                    ${isDisabled ? '<span style="color: #999; font-size: 0.875rem; font-style: italic;">Configure SMTP to use</span>' : ''}
                </div>
            </div>
        </li>
        `;
    }).join('');
}

// Modal controls
document.getElementById('add-signature-btn').addEventListener('click', () => {
    console.log('[UserSettings] Opening add signature modal...');
    currentSignatureId = null;
    document.getElementById('signature-modal-title').textContent = 'Add Email Signature';
    document.getElementById('signature-form').reset();
    document.getElementById('signature-id').value = '';
    document.getElementById('signature-modal').classList.add('is-open');
});

document.getElementById('signature-modal-close').addEventListener('click', closeSignatureModal);
document.getElementById('signature-cancel-btn').addEventListener('click', closeSignatureModal);

document.getElementById('signature-modal').addEventListener('click', (e) => {
    if (e.target.id === 'signature-modal') {
        closeSignatureModal();
    }
});

function closeSignatureModal() {
    console.log('[UserSettings] Closing signature modal');
    document.getElementById('signature-modal').classList.remove('is-open');
    document.getElementById('signature-modal-alert').classList.remove('is-visible');
}

// Form submit
document.getElementById('signature-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    console.log('[UserSettings] Submitting signature form...');
    
    const formData = getSignatureFormData();
    
    try {
        const signatureId = document.getElementById('signature-id').value;
        let response;
        
        if (signatureId) {
            // Update
            console.log('[UserSettings] Updating signature:', signatureId);
            response = await fetch(`${API_BASE}/signatures/${signatureId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
        } else {
            // Create
            console.log('[UserSettings] Creating new signature');
            response = await fetch(`${API_BASE}/signatures`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
        }
        
        const result = await response.json();
        
        if (result.success) {
            console.log('[UserSettings] Signature saved successfully');
            showAlert('signature-alert', signatureId ? 'Signature updated!' : 'Signature created!', 'success');
            closeSignatureModal();
            loadSignatures();
        } else {
            console.error('[UserSettings] Failed to save signature:', result.error);
            showAlert('signature-modal-alert', result.error, 'error');
        }
    } catch (error) {
        console.error('[UserSettings] Error saving signature:', error);
        showAlert('signature-modal-alert', 'Failed to save signature: ' + error.message, 'error');
    }
});

async function editSignature(id) {
    console.log('[UserSettings] Editing signature:', id);
    try {
        const response = await fetch(`${API_BASE}/signatures/${id}`);
        const result = await response.json();
        
        if (result.success) {
            const signature = result.data;
            console.log('[UserSettings] Signature loaded for editing:', { id, name: signature.name });
            
            currentSignatureId = id;
            document.getElementById('signature-modal-title').textContent = 'Edit Email Signature';
            document.getElementById('signature-id').value = signature.id;
            document.getElementById('signature-name').value = signature.name;
            document.getElementById('signature-content').value = signature.content;
            document.getElementById('signature-is-default').checked = signature.is_default;
            document.getElementById('signature-type').value = signature.type;
            
            document.getElementById('signature-modal').classList.add('is-open');
        } else {
            console.error('[UserSettings] Failed to load signature:', result.error);
            showAlert('signature-alert', result.error, 'error');
        }
    } catch (error) {
        console.error('[UserSettings] Error loading signature:', error);
        showAlert('signature-alert', 'Failed to load signature: ' + error.message, 'error');
    }
}

async function deleteSignature(id) {
    console.log('[UserSettings] Deleting signature:', id);
    
    if (!confirm('Are you sure you want to delete this signature?')) {
        console.log('[UserSettings] Delete signature cancelled');
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/signatures/${id}`, {
            method: 'DELETE'
        });
        const result = await response.json();
        
        if (result.success) {
            console.log('[UserSettings] Signature deleted successfully:', id);
            showAlert('signature-alert', 'Signature deleted successfully!', 'success');
            loadSignatures();
        } else {
            console.error('[UserSettings] Failed to delete signature:', result.error);
            showAlert('signature-alert', result.error, 'error');
        }
    } catch (error) {
        console.error('[UserSettings] Error deleting signature:', error);
        showAlert('signature-alert', 'Failed to delete signature: ' + error.message, 'error');
    }
}

async function setDefaultSignature(id) {
    console.log('[UserSettings] Setting default signature:', id);
    try {
        const response = await fetch(`${API_BASE}/signatures/${id}/set-default`, {
            method: 'POST'
        });
        const result = await response.json();
        
        if (result.success) {
            console.log('[UserSettings] Default signature set successfully:', id);
            showAlert('signature-alert', 'Default signature updated!', 'success');
            loadSignatures();
        } else {
            console.error('[UserSettings] Failed to set default signature:', result.error);
            showAlert('signature-alert', result.error, 'error');
        }
    } catch (error) {
        console.error('[UserSettings] Error setting default signature:', error);
        showAlert('signature-alert', 'Failed to set default signature: ' + error.message, 'error');
    }
}

function getSignatureFormData() {
    return {
        name: document.getElementById('signature-name').value,
        content: document.getElementById('signature-content').value,
        is_default: document.getElementById('signature-is-default').checked,
        type: document.getElementById('signature-type').value
    };
}

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

function showAlert(elementId, message, type = 'success') {
    const alert = document.getElementById(elementId);
    alert.className = `c-alert c-alert--${type} is-visible`;
    alert.textContent = message;
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        alert.classList.remove('is-visible');
    }, 5000);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================================================
// INITIALIZATION
// ============================================================================

// Load profile on page load
document.addEventListener('DOMContentLoaded', () => {
    console.log('[UserSettings] Page loaded, initializing...');
    loadProfile();
});

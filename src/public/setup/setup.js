/**
 * CI-Inbox Setup Wizard JavaScript
 * Extracted from index.php for better maintainability
 */

/**
 * Admin Personal IMAP Toggle Handler
 * Shows/hides IMAP password field based on checkbox state
 */
function initAdminImapToggle() {
    const checkbox = document.getElementById('create_admin_personal_imap');
    if (!checkbox) return;
    
    checkbox.addEventListener('change', function() {
        const passwordField = document.getElementById('admin-imap-password-field');
        if (passwordField) {
            passwordField.style.display = this.checked ? 'block' : 'none';
        }
    });
}

/**
 * Test Admin IMAP Connection
 * Tests IMAP credentials and stores discovered config in hidden fields
 */
function initAdminImapTest() {
    const testBtn = document.getElementById('test-admin-imap-btn');
    if (!testBtn) return;
    
    testBtn.addEventListener('click', async function() {
        const email = document.getElementById('admin_email')?.value;
        const loginPassword = document.getElementById('admin_password')?.value;
        const imapPassword = document.getElementById('admin_imap_password')?.value;
        const password = imapPassword || loginPassword;
        
        const btnText = document.getElementById('admin-btn-text');
        const btnSpinner = document.getElementById('admin-btn-spinner');
        const statusDiv = document.getElementById('admin-test-status');
        
        // Validation
        if (!email) {
            alert('Bitte E-Mail-Adresse eingeben');
            return;
        }
        if (!password) {
            alert('Bitte Passwort eingeben');
            return;
        }
        
        // Show loading state
        if (btnText) btnText.style.display = 'none';
        if (btnSpinner) btnSpinner.style.display = 'inline';
        this.disabled = true;
        if (statusDiv) statusDiv.style.display = 'none';
        
        try {
            const formData = new FormData();
            formData.append('email', email);
            formData.append('password', password);
            
            const response = await fetch('/setup/?ajax=test_imap', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Store discovered config in hidden fields
                const hostField = document.getElementById('admin_imap_host');
                const portField = document.getElementById('admin_imap_port');
                const sslField = document.getElementById('admin_imap_ssl');
                
                if (hostField && result.host) hostField.value = result.host;
                if (portField && result.port) portField.value = result.port;
                if (sslField) sslField.value = result.ssl ? '1' : '0';
                
                // Show success message
                if (statusDiv) {
                    statusDiv.style.display = 'block';
                    statusDiv.style.background = '#d1fae5';
                    statusDiv.style.border = '1px solid #86efac';
                    statusDiv.style.color = '#065f46';
                    
                    let message = '✅ IMAP-Verbindung erfolgreich!<br>';
                    message += `<strong>Server:</strong> ${result.host}:${result.port} ${result.ssl ? '(SSL)' : ''}`;
                    
                    if (result.certificate_fix) {
                        message += `<br><em style="color: #1e40af;">ℹ️ Hostname korrigiert: ${result.original_host} → ${result.host}</em>`;
                    }
                    
                    statusDiv.innerHTML = message;
                }
            } else {
                // Show error
                if (statusDiv) {
                    statusDiv.style.display = 'block';
                    statusDiv.style.background = '#fee2e2';
                    statusDiv.style.border = '1px solid #fca5a5';
                    statusDiv.style.color = '#991b1b';
                    statusDiv.innerHTML = '❌ ' + (result.error || 'Verbindung fehlgeschlagen');
                }
            }
        } catch (error) {
            if (statusDiv) {
                statusDiv.style.display = 'block';
                statusDiv.style.background = '#fee2e2';
                statusDiv.style.border = '1px solid #fca5a5';
                statusDiv.style.color = '#991b1b';
                statusDiv.innerHTML = '❌ Fehler: ' + error.message;
            }
        } finally {
            // Reset button state
            if (btnText) btnText.style.display = 'inline';
            if (btnSpinner) btnSpinner.style.display = 'none';
            this.disabled = false;
        }
    });
}

/**
 * Test Shared IMAP Connection
 * Tests IMAP credentials and updates form with discovered values
 */
function initImapTest() {
    const testBtn = document.getElementById('test-imap-btn');
    if (!testBtn) return;
    
    testBtn.addEventListener('click', async function(e) {
        e.preventDefault();
        
        const btn = this;
        const btnText = btn.querySelector('.btn-text');
        const btnSpinner = btn.querySelector('.btn-spinner');
        const resultDiv = document.getElementById('imap-test-result');
        
        const email = document.getElementById('imap_email')?.value;
        const password = document.getElementById('imap_pass_test')?.value;
        
        // Validation
        if (!email || !password) {
            if (resultDiv) {
                resultDiv.style.display = 'block';
                resultDiv.style.background = '#fef2f2';
                resultDiv.style.border = '1px solid #fca5a5';
                resultDiv.style.color = '#991b1b';
                resultDiv.innerHTML = '❌ Bitte E-Mail und Passwort eingeben';
            }
            return;
        }
        
        // Show loading state
        if (btnText) btnText.style.display = 'none';
        if (btnSpinner) btnSpinner.style.display = 'inline';
        btn.disabled = true;
        if (resultDiv) resultDiv.style.display = 'none';
        
        try {
            const formData = new FormData();
            formData.append('email', email);
            formData.append('password', password);
            
            const response = await fetch('/setup/?ajax=test_imap', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Update form with discovered values
                const hostField = document.getElementById('imap_host');
                const portField = document.getElementById('imap_port');
                const sslField = document.getElementById('imap_ssl');
                const userField = document.getElementById('imap_user');
                
                if (hostField && result.host) hostField.value = result.host;
                if (portField && result.port) portField.value = result.port;
                if (sslField && result.ssl !== undefined) sslField.checked = result.ssl;
                if (userField && result.username) userField.value = result.username;
                
                // Also prefill SMTP host
                if (result.host) {
                    const smtpHost = result.host.replace(/^imap\./, 'smtp.');
                    const smtpHostField = document.getElementById('smtp_host');
                    if (smtpHostField) smtpHostField.value = smtpHost;
                }
                
                // Show success message
                if (resultDiv) {
                    resultDiv.style.display = 'block';
                    resultDiv.style.background = '#d1fae5';
                    resultDiv.style.border = '1px solid #86efac';
                    resultDiv.style.color = '#065f46';
                    
                    let message = '✅ IMAP-Verbindung erfolgreich!<br>';
                    message += `<strong>Server:</strong> ${result.host}:${result.port} ${result.ssl ? '(SSL)' : ''}`;
                    
                    if (result.certificate_fix) {
                        message += `<br><em style="color: #1e40af;">ℹ️ Hostname korrigiert: ${result.original_host} → ${result.host}</em>`;
                    }
                    
                    resultDiv.innerHTML = message;
                }
            } else {
                // Show error message
                if (resultDiv) {
                    resultDiv.style.display = 'block';
                    resultDiv.style.background = '#fee2e2';
                    resultDiv.style.border = '1px solid #fca5a5';
                    resultDiv.style.color = '#991b1b';
                    resultDiv.innerHTML = '❌ ' + (result.error || 'Verbindung fehlgeschlagen');
                }
            }
        } catch (error) {
            if (resultDiv) {
                resultDiv.style.display = 'block';
                resultDiv.style.background = '#fee2e2';
                resultDiv.style.border = '1px solid #fca5a5';
                resultDiv.style.color = '#991b1b';
                resultDiv.innerHTML = '❌ Fehler beim Testen: ' + error.message;
            }
        } finally {
            // Reset button state
            if (btnText) btnText.style.display = 'inline';
            if (btnSpinner) btnSpinner.style.display = 'none';
            btn.disabled = false;
        }
    });
}

/**
 * Test SMTP Connection
 * Tests SMTP server connectivity
 */
function initSmtpTest() {
    const testBtn = document.getElementById('test-smtp-btn');
    if (!testBtn) return;
    
    testBtn.addEventListener('click', async function(e) {
        e.preventDefault();
        
        const btn = this;
        const btnText = btn.querySelector('.btn-text');
        const btnSpinner = btn.querySelector('.btn-spinner');
        const resultDiv = document.getElementById('smtp-test-result');
        
        const host = document.getElementById('smtp_host')?.value;
        const port = document.getElementById('smtp_port')?.value;
        const ssl = document.getElementById('smtp_ssl')?.checked;
        
        // Validation
        if (!host) {
            if (resultDiv) {
                resultDiv.style.display = 'block';
                resultDiv.style.background = '#fef2f2';
                resultDiv.style.border = '1px solid #fca5a5';
                resultDiv.style.color = '#991b1b';
                resultDiv.innerHTML = '❌ Bitte SMTP-Server eingeben';
            }
            return;
        }
        
        // Show loading state
        if (btnText) btnText.style.display = 'none';
        if (btnSpinner) btnSpinner.style.display = 'inline';
        btn.disabled = true;
        if (resultDiv) resultDiv.style.display = 'none';
        
        try {
            const formData = new FormData();
            formData.append('host', host);
            formData.append('port', port);
            formData.append('ssl', ssl ? 'true' : 'false');
            
            const response = await fetch('/setup/?ajax=test_smtp', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                if (resultDiv) {
                    resultDiv.style.display = 'block';
                    resultDiv.style.background = '#d1fae5';
                    resultDiv.style.border = '1px solid #86efac';
                    resultDiv.style.color = '#065f46';
                    resultDiv.innerHTML = '✅ SMTP-Verbindung erfolgreich!';
                }
            } else {
                if (resultDiv) {
                    resultDiv.style.display = 'block';
                    resultDiv.style.background = '#fee2e2';
                    resultDiv.style.border = '1px solid #fca5a5';
                    resultDiv.style.color = '#991b1b';
                    resultDiv.innerHTML = '❌ ' + (result.error || 'Verbindung fehlgeschlagen');
                }
            }
        } catch (error) {
            if (resultDiv) {
                resultDiv.style.display = 'block';
                resultDiv.style.background = '#fee2e2';
                resultDiv.style.border = '1px solid #fca5a5';
                resultDiv.style.color = '#991b1b';
                resultDiv.innerHTML = '❌ Fehler beim Testen: ' + error.message;
            }
        } finally {
            // Reset button state
            if (btnText) btnText.style.display = 'inline';
            if (btnSpinner) btnSpinner.style.display = 'none';
            btn.disabled = false;
        }
    });
}

/**
 * Initialize all event handlers when DOM is ready
 */
document.addEventListener('DOMContentLoaded', function() {
    initAdminImapToggle();
    initAdminImapTest();
    initImapTest();
    initSmtpTest();
});

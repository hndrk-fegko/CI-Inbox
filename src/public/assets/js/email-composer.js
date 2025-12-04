/**
 * Email Composer Module
 * 
 * Wiederverwendbarer Email Composer für Reply/Forward/New Email
 * Usage:
 *   showEmailComposer('reply', { threadId: 123, to: 'email@example.com' })
 *   showEmailComposer('forward', { threadId: 123 })
 *   showEmailComposer('new', {})
 */

// API Base URL
const EMAIL_API = '/api';

// Current composer state
let currentComposerMode = null;
let currentComposerData = null;
let composerElement = null;

/**
 * Show email composer modal
 * @param {string} mode - 'reply' | 'forward' | 'new' | 'private-reply'
 * @param {object} data - Context data (threadId, to, subject, etc.)
 */
async function showEmailComposer(mode, data = {}) {
    console.log('[EmailComposer] Opening composer:', mode, data);
    
    currentComposerMode = mode;
    currentComposerData = data;
    
    // Create or get composer element
    if (!composerElement) {
        composerElement = createComposerElement();
        document.body.appendChild(composerElement);
    }
    
    // Update composer for mode
    await updateComposerForMode(mode, data);
    
    // Show modal
    composerElement.classList.add('c-modal--open');
    
    // Focus first input
    const firstInput = composerElement.querySelector('.c-email-composer__input, .c-email-composer__textarea');
    if (firstInput) {
        setTimeout(() => firstInput.focus(), 100);
    }
}

/**
 * Create composer DOM element
 */
function createComposerElement() {
    const div = document.createElement('div');
    div.className = 'c-modal c-modal--composer';
    div.id = 'email-composer';
    
    div.innerHTML = `
        <div class="c-modal__dialog">
            <div class="c-modal__header">
                <div>
                    <h2 class="c-modal__title" id="composer-title">E-Mail verfassen</h2>
                    <div class="c-email-composer__sender" id="composer-sender"></div>
                </div>
                <button type="button" class="c-modal__close" id="composer-close" aria-label="Schließen">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <div class="c-modal__body" id="composer-modal-body">
                <!-- Dynamic content will be inserted here -->
            </div>
            
            <div class="c-modal__footer">
                <button type="button" class="c-button c-button--secondary c-button--sm" id="composer-cancel">
                    Abbrechen
                </button>
                <button type="button" class="c-button c-button--primary c-button--sm" id="composer-send">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <span id="composer-send-text">Senden</span>
                </button>
            </div>
        </div>
    `;
    
    // Attach event listeners
    attachComposerEventListeners(div);
    
    return div;
}

/**
 * Attach event listeners
 */
function attachComposerEventListeners(element) {
    // Close button
    element.querySelector('#composer-close').addEventListener('click', closeEmailComposer);
    element.querySelector('#composer-cancel').addEventListener('click', closeEmailComposer);
    
    // Send button
    element.querySelector('#composer-send').addEventListener('click', sendEmail);
    
    // ESC key to close
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && element.classList.contains('c-modal--open')) {
            closeEmailComposer();
        }
    });
    
    // Click outside to close
    element.addEventListener('click', (e) => {
        if (e.target === element) {
            closeEmailComposer();
        }
    });
}

/**
 * Update composer content based on mode
 */
async function updateComposerForMode(mode, data) {
    const title = composerElement.querySelector('#composer-title');
    const senderInfo = composerElement.querySelector('#composer-sender');
    const body = composerElement.querySelector('#composer-modal-body');
    const sendText = composerElement.querySelector('#composer-send-text');
    
    // Clear previous errors
    const existingError = body.querySelector('.c-email-composer__error');
    if (existingError) existingError.remove();
    
    // Update title, sender info and send button text
    const titles = {
        'reply': 'Antworten',
        'forward': 'Weiterleiten',
        'new': 'Neue E-Mail',
        'private-reply': 'Privat antworten'
    };
    
    title.textContent = titles[mode] || 'E-Mail verfassen';
    sendText.textContent = 'Senden';
    
    // Set sender info
    if (mode === 'private-reply') {
        senderInfo.textContent = 'Von: (wählen Sie Ihren Account)';
    } else {
        senderInfo.textContent = 'Von: ' + (data.fromEmail || 'Shared Inbox');
    }
    
    // Build form based on mode
    if (mode === 'reply' || mode === 'private-reply') {
        body.innerHTML = await buildReplyForm(data, mode === 'private-reply');
    } else if (mode === 'forward') {
        body.innerHTML = await buildForwardForm(data);
    } else if (mode === 'new') {
        body.innerHTML = await buildNewEmailForm(data);
    }
    
    // Load IMAP accounts for private reply
    if (mode === 'private-reply') {
        await loadImapAccounts();
    }
}

/**
 * Build reply form HTML
 */
async function buildReplyForm(data, isPrivate) {
    let html = '';
    
    // Show account selector for private reply
    if (isPrivate) {
        html += `
            <div class="c-email-composer__field">
                <label class="c-email-composer__label c-email-composer__label--required" for="composer-account">
                    Von Account
                </label>
                <select class="c-email-composer__select" id="composer-account" required>
                    <option value="">Lade Accounts...</option>
                </select>
            </div>
        `;
    }
    
    html += `
        <div class="c-email-composer__field">
            <label class="c-email-composer__label" for="composer-to">
                An
            </label>
            <input 
                type="email" 
                class="c-email-composer__input" 
                id="composer-to" 
                value="${escapeHtml(data.to || '')}"
                readonly
            />
        </div>
        
        <div class="c-email-composer__field">
            <label class="c-email-composer__label" for="composer-subject">
                Betreff
            </label>
            <input 
                type="text" 
                class="c-email-composer__input" 
                id="composer-subject" 
                value="${escapeHtml(data.subject ? 'Re: ' + data.subject : '')}"
                readonly
            />
        </div>
        
        <div class="c-email-composer__field">
            <label class="c-email-composer__label c-email-composer__label--required" for="composer-message">
                Nachricht
            </label>
            <textarea 
                class="c-email-composer__textarea" 
                id="composer-message" 
                placeholder="Ihre Nachricht..."
                required
            ></textarea>
            
            <label class="c-email-composer__signature-option">
                <input 
                    type="checkbox" 
                    class="c-email-composer__signature-checkbox" 
                    id="composer-include-signature"
                    checked
                />
                <span>Signatur anhängen</span>
            </label>
        </div>
    `;
    
    return html;
}

/**
 * Build forward form HTML
 */
async function buildForwardForm(data) {
    return `
        <div class="c-email-composer__field">
            <label class="c-email-composer__label c-email-composer__label--required" for="composer-recipients">
                An (durch Komma getrennt)
            </label>
            <input 
                type="text" 
                class="c-email-composer__input" 
                id="composer-recipients" 
                placeholder="email@example.com, another@example.com"
                required
            />
        </div>
        
        <div class="c-email-composer__field">
            <label class="c-email-composer__label" for="composer-note">
                Notiz (optional)
            </label>
            <textarea 
                class="c-email-composer__textarea" 
                id="composer-note" 
                placeholder="Optionale Notiz für die Empfänger..."
                style="min-height: 120px;"
            ></textarea>
        </div>
        
        <div class="c-email-composer__account-info">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="c-email-composer__account-icon">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Der gesamte Thread wird weitergeleitet</span>
        </div>
    `;
}

/**
 * Build new email form HTML
 */
async function buildNewEmailForm(data) {
    return `
        <div class="c-email-composer__field">
            <label class="c-email-composer__label c-email-composer__label--required" for="composer-to">
                An
            </label>
            <input 
                type="email" 
                class="c-email-composer__input" 
                id="composer-to" 
                placeholder="email@example.com"
                value="${escapeHtml(data.to || '')}"
                required
            />
        </div>
        
        <div class="c-email-composer__field">
            <label class="c-email-composer__label c-email-composer__label--required" for="composer-subject">
                Betreff
            </label>
            <input 
                type="text" 
                class="c-email-composer__input" 
                id="composer-subject" 
                placeholder="Betreff der E-Mail"
                required
            />
        </div>
        
        <div class="c-email-composer__field">
            <label class="c-email-composer__label c-email-composer__label--required" for="composer-message">
                Nachricht
            </label>
            <textarea 
                class="c-email-composer__textarea" 
                id="composer-message" 
                placeholder="Ihre Nachricht..."
                required
            ></textarea>
            
            <label class="c-email-composer__signature-option">
                <input 
                    type="checkbox" 
                    class="c-email-composer__signature-checkbox" 
                    id="composer-include-signature"
                    checked
                />
                <span>Signatur anhängen</span>
            </label>
        </div>
    `;
}

/**
 * Load user IMAP accounts for private reply
 */
async function loadImapAccounts() {
    const select = composerElement.querySelector('#composer-account');
    if (!select) return;
    
    try {
        const response = await fetch(`${EMAIL_API}/user/imap-accounts`);
        
        if (!response.ok) {
            throw new Error('Failed to load accounts');
        }
        
        const data = await response.json();
        const accounts = data.accounts || [];
        
        select.innerHTML = '<option value="">Account auswählen...</option>';
        accounts.forEach(account => {
            const option = document.createElement('option');
            option.value = account.id;
            option.textContent = `${account.email} (${account.host})`;
            option.dataset.email = account.email; // Store email for header update
            select.appendChild(option);
        });
        
        // Update sender info when account is selected
        select.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const senderInfo = composerElement.querySelector('#composer-sender');
            
            if (selectedOption.dataset.email) {
                senderInfo.textContent = 'Von: ' + selectedOption.dataset.email;
            } else {
                senderInfo.textContent = 'Von: (wählen Sie Ihren Account)';
            }
        });
        
        console.log('[EmailComposer] Loaded IMAP accounts:', accounts.length);
        
    } catch (error) {
        console.error('[EmailComposer] Failed to load accounts:', error);
        select.innerHTML = '<option value="">Fehler beim Laden</option>';
    }
}

/**
 * Send email via API
 */
async function sendEmail() {
    const sendButton = composerElement.querySelector('#composer-send');
    const composerBody = composerElement.querySelector('#composer-modal-body');
    
    // Remove previous errors
    const existingError = composerBody.querySelector('.c-email-composer__error');
    if (existingError) existingError.remove();
    
    // Validate and gather data
    const emailData = gatherEmailData();
    
    if (!emailData) {
        showComposerError('Bitte füllen Sie alle Pflichtfelder aus.');
        return;
    }
    
    // Show loading state
    sendButton.disabled = true;
    sendButton.classList.add('is-loading');
    
    try {
        let response;
        
        if (currentComposerMode === 'reply' || currentComposerMode === 'private-reply') {
            response = await fetch(`${EMAIL_API}/threads/${currentComposerData.threadId}/reply`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(emailData)
            });
        } else if (currentComposerMode === 'forward') {
            response = await fetch(`${EMAIL_API}/threads/${currentComposerData.threadId}/forward`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(emailData)
            });
        } else if (currentComposerMode === 'new') {
            response = await fetch(`${EMAIL_API}/emails/send`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(emailData)
            });
        }
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Failed to send email');
        }
        
        const result = await response.json();
        
        console.log('[EmailComposer] Email sent successfully:', result);
        
        // Close composer
        closeEmailComposer();
        
        // Reload thread detail if replying
        if (currentComposerMode === 'reply' || currentComposerMode === 'private-reply') {
            if (typeof loadThreadDetail === 'function') {
                loadThreadDetail(currentComposerData.threadId);
            }
        }
        
        // Show success message (could be toast notification)
        console.log('[EmailComposer] ✅ E-Mail erfolgreich gesendet!');
        
    } catch (error) {
        console.error('[EmailComposer] Send failed:', error);
        showComposerError(error.message);
        
        // Reset button
        sendButton.disabled = false;
        sendButton.classList.remove('is-loading');
    }
}

/**
 * Gather email data from form
 */
function gatherEmailData() {
    const mode = currentComposerMode;
    
    // Check if signature should be included
    const includeSignature = composerElement.querySelector('#composer-include-signature')?.checked;
    
    if (mode === 'reply') {
        const body = composerElement.querySelector('#composer-message')?.value.trim();
        if (!body) return null;
        
        return {
            body: includeSignature ? appendSignature(body) : body,
            imap_account_id: 4 // Shared account (default)
        };
    }
    
    if (mode === 'private-reply') {
        const body = composerElement.querySelector('#composer-message')?.value.trim();
        const accountId = composerElement.querySelector('#composer-account')?.value;
        
        if (!body || !accountId) return null;
        
        return {
            body: includeSignature ? appendSignature(body) : body,
            imap_account_id: parseInt(accountId)
        };
    }
    
    if (mode === 'forward') {
        const recipients = composerElement.querySelector('#composer-recipients')?.value.trim();
        const note = composerElement.querySelector('#composer-note')?.value.trim();
        
        if (!recipients) return null;
        
        // Parse comma-separated emails
        const recipientList = recipients.split(',').map(email => email.trim()).filter(Boolean);
        
        return {
            recipients: recipientList,
            note: note || null,
            imap_account_id: 4
        };
    }
    
    if (mode === 'new') {
        const to = composerElement.querySelector('#composer-to')?.value.trim();
        const subject = composerElement.querySelector('#composer-subject')?.value.trim();
        const body = composerElement.querySelector('#composer-message')?.value.trim();
        const signatureId = composerElement.querySelector('#composer-signature')?.value;
        
        if (!to || !subject || !body) return null;
        
        const emailData = {
            to: [to],
            subject: subject,
            body_text: body,
            body_html: body.replace(/\n/g, '<br />'),
            imap_account_id: 4
        };
        
        // Add user_id if available (for default signature)
        if (window.currentUserId) {
            emailData.user_id = window.currentUserId;
        }
        
        // Add signature_id if selected
        if (signatureId && signatureId !== '') {
            emailData.signature_id = parseInt(signatureId);
        }
        
        return emailData;
    }
    
    return null;
}

/**
 * Append user signature to email body
 */
function appendSignature(body) {
    // Get signature from user settings (loaded globally)
    const signature = window.currentUserSettings?.signature || null;
    
    if (!signature) {
        return body;
    }
    
    return body + '\n\n-- \n' + signature;
}

/**
 * Show error message in composer
 */
function showComposerError(message) {
    const composerBody = composerElement.querySelector('#composer-modal-body');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'c-email-composer__error';
    errorDiv.innerHTML = `
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="c-email-composer__error-icon">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>${escapeHtml(message)}</span>
    `;
    
    composerBody.insertBefore(errorDiv, composerBody.firstChild);
}

/**
 * Close email composer
 */
function closeEmailComposer() {
    if (composerElement) {
        composerElement.classList.remove('c-modal--open');
        currentComposerMode = null;
        currentComposerData = null;
    }
    
    console.log('[EmailComposer] Closed');
}

/**
 * Escape HTML
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Export for global access
window.showEmailComposer = showEmailComposer;
window.closeEmailComposer = closeEmailComposer;

console.log('[EmailComposer] Module loaded');

/**
 * Thread Detail Renderer
 * 
 * Renders thread detail view from JSON API data
 */

/**
 * Render thread detail HTML from API response
 */
function renderThreadDetail(data) {
    const { thread, items, labels } = data;
    
    // Update toolbar with thread subject and actions
    updateToolbarDetail(thread, labels);
    
    let html = `<div class="c-thread-detail" data-thread-id="${thread.id}">`;
    
    // Render items (emails and notes mixed chronologically) with Dropzones
    html += '<div class="c-thread-detail__messages">';
    
    let emailIndex = 0;
    
    // Initial dropzone before first item
    html += `<div class="c-note-dropzone" data-position="0" style="display: none;">
        <button class="c-note-dropzone__button">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Notiz hier einfügen
        </button>
    </div>`;
    
    items.forEach((item, index) => {
        if (item.type === 'email') {
            html += renderEmailMessage(item);
            emailIndex++;
            
            // Dropzone after email
            html += `<div class="c-note-dropzone" data-position="${emailIndex}" style="display: none;">
                <button class="c-note-dropzone__button">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Notiz hier einfügen
                </button>
            </div>`;
        } else if (item.type === 'note') {
            html += renderNoteItem(item);
        }
    });
    
    html += '</div>';
    html += '</div>';
    
    return html;
}

/**
 * Update toolbar detail section with thread info and actions
 */
function updateToolbarDetail(thread, labels) {
    const titleEl = document.getElementById('detail-title');
    const actionsEl = document.getElementById('detail-actions');
    
    if (!titleEl || !actionsEl) return;
    
    // Build assigned users display
    const assignedUsers = thread.assigned_users || [];
    let assignedHtml = '';
    if (assignedUsers.length > 0) {
        const avatarsHtml = assignedUsers.map(user => {
            const name = user.name || user.email;
            let initials = name.charAt(0).toUpperCase();
            if (name.includes(' ')) {
                const parts = name.split(' ');
                initials = parts[0].charAt(0).toUpperCase() + parts[parts.length - 1].charAt(0).toUpperCase();
            }
            // Use avatar_color from database (with fallback)
            const colorNum = user.avatar_color || ((user.id % 8) + 1);
            const colorClass = `c-avatar--color-${colorNum}`;
            return `<div class="c-avatar c-avatar--xs ${colorClass}" title="${escapeHtml(name)}">${initials}</div>`;
        }).join('');
        
        assignedHtml = `
            <div class="c-assigned-users" id="toolbar-assigned-users">
                <span class="c-assigned-users__label" style="display: inline-flex; align-items: center; gap: 4px;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="opacity: 0.6;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Zugewiesen:
                </span>
                <div class="c-assigned-users__avatars" onclick="showAssignmentPicker(${thread.id})">
                    ${avatarsHtml}
                </div>
                <button class="c-assigned-users__add" onclick="showAssignmentPicker(${thread.id})" title="Zuweisung ändern">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </button>
            </div>
        `;
    } else {
        assignedHtml = `
            <div class="c-assigned-users c-assigned-users--empty" id="toolbar-assigned-users">
                <span class="c-assigned-users__label" style="display: inline-flex; align-items: center; gap: 4px;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="opacity: 0.6;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Nicht zugewiesen
                </span>
                <button class="c-assigned-users__add" onclick="showAssignmentPicker(${thread.id})" title="Nutzer zuweisen">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </button>
            </div>
        `;
    }
    
    // Build status badge with icon
    const statusConfig = {
        'open': { 
            label: 'Offen', 
            class: 'c-badge--primary',
            icon: '<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-right: 4px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>'
        },
        'assigned': { 
            label: 'In Arbeit', 
            class: 'c-badge--warning',
            icon: '<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-right: 4px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
        },
        'closed': { 
            label: 'Erledigt', 
            class: 'c-badge--success',
            icon: '<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-right: 4px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
        },
        'archived': { 
            label: 'Archiviert', 
            class: 'c-badge--neutral',
            icon: '<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-right: 4px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>'
        }
    };
    
    const status = statusConfig[thread.status] || statusConfig['open'];
    const statusBadgeHtml = `
        <span class="c-badge ${status.class}" style="display: inline-flex; align-items: center; cursor: pointer;" onclick="showStatusPicker(${thread.id})" title="Status ändern">
            ${status.icon}${status.label}
        </span>
    `;
    
    // Update title with status, subject and labels
    let titleHtml = `${statusBadgeHtml} <span style="margin-left: 8px;">${escapeHtml(thread.subject || '(Kein Betreff)')}</span>`;
    if (labels && labels.length > 0) {
        titleHtml += `
            <div class="c-thread-detail__labels" style="display: inline-flex; gap: 6px; margin-left: 12px; align-items: center;">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="opacity: 0.6;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
        `;
        labels.forEach(label => {
            titleHtml += `<span class="c-label-tag" style="--label-color: ${escapeHtml(label.color)}">${escapeHtml(label.name)}</span>`;
        });
        titleHtml += '</div>';
    }
    titleEl.innerHTML = titleHtml;
    
    // Check if user has personal IMAP account configured
    const hasPersonalImap = window.userHasPersonalImap || false;
    const personalImapDisabledClass = hasPersonalImap ? '' : 'c-dropdown__item--disabled';
    const personalImapTitle = hasPersonalImap ? '' : 'title="Persönliche Mailadresse nicht konfiguriert"';
    
    // Update actions - simple approach: main buttons always visible, more actions in dropdown
    actionsEl.innerHTML = `
        ${assignedHtml}
        <div class="c-button-group">
            <button class="c-button c-button--primary c-button--sm" data-action="reply">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                </svg>
                Antworten
            </button>
            <div class="c-dropdown">
                <button class="c-button c-button--primary c-button--sm c-dropdown__trigger" title="Antwort-Optionen">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div class="c-dropdown__menu">
                    <button class="c-dropdown__item" data-action="forward">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                        Weiterleiten
                    </button>
                    <button class="c-dropdown__item ${personalImapDisabledClass}" data-action="reply-private" ${personalImapTitle}>
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                        </svg>
                        Persönlich antworten
                    </button>
                </div>
            </div>
        </div>
        <button class="c-button c-button--secondary c-button--sm" id="toggle-note-mode" data-action="add-note">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Notiz
        </button>
        <div class="c-dropdown">
            <button class="c-button c-button--secondary c-button--sm c-dropdown__trigger">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                </svg>
                Mehr
            </button>
            <div class="c-dropdown__menu">
                <button class="c-dropdown__item" data-action="assign">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Nutzer zuweisen
                </button>
                <button class="c-dropdown__item" data-action="label">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    Label bearbeiten
                </button>
                <div class="c-dropdown__separator"></div>
                <button class="c-dropdown__item" data-action="archive">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                    Archivieren
                </button>
                <button class="c-dropdown__item" data-action="mark-unread">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Als ungelesen markieren
                </button>
                <button class="c-dropdown__item" data-action="move">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    Verschieben
                </button>
                <div class="c-dropdown__separator"></div>
                <button class="c-dropdown__item c-dropdown__item--danger" data-action="delete">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Löschen
                </button>
            </div>
        </div>
    `;
    
    actionsEl.style.display = 'flex';
}

/**
 * Initialize responsive toolbar behavior
 */
function initResponsiveToolbar() {
    const actionsEl = document.getElementById('detail-actions');
    if (!actionsEl) return;
    
    const buttons = Array.from(actionsEl.querySelectorAll('.c-thread-action'));
    const dropdown = actionsEl.querySelector('.c-dropdown');
    const dropdownMenu = dropdown?.querySelector('.c-dropdown__menu');
    const dropdownTrigger = dropdown?.querySelector('.c-dropdown__trigger');
    
    if (!dropdown || !dropdownMenu || !dropdownTrigger) return;
    
    // Store original dropdown items (not from buttons)
    const originalDropdownItems = Array.from(dropdownMenu.children);
    
    const checkAndResize = () => {
        // Get actual available width for buttons
        const containerWidth = actionsEl.offsetWidth;
        const dropdownTriggerWidth = dropdownTrigger.offsetWidth || 40;
        const gap = 8; // Gap between buttons
        const padding = 16; // Safety padding
        
        // Calculate available width (container minus dropdown button)
        const availableWidth = containerWidth - dropdownTriggerWidth - padding;
        
        // Reset: show all buttons first to measure them
        buttons.forEach(btn => {
            btn.style.display = '';
            btn.dataset.hidden = 'false';
        });
        
        // Clear dropdown menu
        dropdownMenu.innerHTML = '';
        
        // Measure buttons and determine which ones fit
        let currentWidth = 0;
        const visibleButtons = [];
        const hiddenButtons = [];
        
        // Sort by priority (lower number = higher priority)
        const sortedButtons = [...buttons].sort((a, b) => {
            return parseInt(a.dataset.priority || '99') - parseInt(b.dataset.priority || '99');
        });
        
        // Determine which buttons fit
        sortedButtons.forEach(btn => {
            const btnWidth = btn.offsetWidth + gap;
            
            if (currentWidth + btnWidth <= availableWidth) {
                // Button fits
                currentWidth += btnWidth;
                visibleButtons.push(btn);
            } else {
                // Button doesn't fit, goes to dropdown
                hiddenButtons.push(btn);
            }
        });
        
        // Hide buttons that don't fit
        hiddenButtons.forEach(btn => {
            btn.style.display = 'none';
            btn.dataset.hidden = 'true';
        });
        
        // Add hidden buttons to dropdown (in priority order)
        hiddenButtons.forEach(btn => {
            const clone = document.createElement('button');
            clone.className = 'c-dropdown__item';
            clone.dataset.action = btn.dataset.action;
            clone.innerHTML = btn.innerHTML;
            dropdownMenu.appendChild(clone);
        });
        
        // Add separator if we have both hidden buttons and original items
        if (hiddenButtons.length > 0 && originalDropdownItems.length > 0) {
            const separator = document.createElement('div');
            separator.className = 'c-dropdown__separator';
            dropdownMenu.appendChild(separator);
        }
        
        // Add original dropdown items back
        originalDropdownItems.forEach(item => {
            dropdownMenu.appendChild(item);
        });
    };
    
    // Run on init with delay to ensure DOM is ready
    setTimeout(checkAndResize, 100);
    
    // Run on window resize with debounce
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(checkAndResize, 150);
    });
    
    // Also observe container size changes
    if (typeof ResizeObserver !== 'undefined') {
        const resizeObserver = new ResizeObserver(() => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(checkAndResize, 150);
        });
        resizeObserver.observe(actionsEl);
    }
}

/**
 * Handle responsive button visibility based on container width
 * @deprecated Use initResponsiveToolbar instead
 */
function handleResponsiveButtons() {
    // Kept for backwards compatibility
    initResponsiveToolbar();
}

/**
 * Render labels
 */
function renderLabels(labels) {
    if (!labels || labels.length === 0) return '';
    
    let html = '<div class="c-thread-detail__labels">';
    labels.forEach(label => {
        html += `<span class="c-label-tag" style="--label-color: ${escapeHtml(label.color)}">${escapeHtml(label.name)}</span>`;
    });
    html += '</div>';
    
    return html;
}

/**
 * Render single email message
 */
function renderEmailMessage(email, index, totalEmails) {
    const initials = (email.from_name || email.from_email).charAt(0).toUpperCase();
    // Collapse all emails except the last one
    const isCollapsed = index < totalEmails - 1;
    const isUnread = !email.is_read;
    
    let html = `
        <article class="c-email-message ${isCollapsed ? 'is-collapsed' : ''} ${isUnread ? 'is-unread' : ''}" data-email-id="${email.id}">
            <div class="c-email-message__header">
                <div class="c-email-message__avatar">${initials}</div>
                
                <div class="c-email-message__info">
                    <div class="c-email-message__sender">
                        <strong>${escapeHtml(email.from_name || email.from_email)}</strong>
                        ${email.from_name ? `<span class="c-email-message__email">&lt;${escapeHtml(email.from_email)}&gt;</span>` : ''}
                        ${isUnread ? '<span class="c-badge c-badge--primary c-badge--sm" style="margin-left: 8px;">Ungelesen</span>' : ''}
                    </div>
                    <div class="c-email-message__meta">
                        <time datetime="${email.sent_at}">${email.sent_at_human}</time>
                        <span class="c-email-message__separator">•</span>
                        <span class="c-email-message__to">an ${escapeHtml(email.to_email)}</span>
                    </div>
                </div>
                
                <button class="c-button c-button--icon c-email-message__toggle" title="${isCollapsed ? 'Details anzeigen' : 'Details verbergen'}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
            </div>
            
            <div class="c-email-message__body">
    `;
    
    // Email body (HTML or plain text)
    if (email.body_html) {
        const trimmedHtml = trimEmailContent(email.body_html);
        html += `
            <div class="c-email-message__content">
                <iframe class="c-email-message__iframe" srcdoc="${escapeHtml(trimmedHtml)}" sandbox="allow-same-origin" title="Email content"></iframe>
            </div>
        `;
        
        // Show full message toggle if content was trimmed
        if (trimmedHtml !== email.body_html) {
            html += `
                <div class="c-email-message__show-more">
                    <button class="c-button c-button--link c-button--sm" data-action="show-full-message">
                        Vollständige Nachricht anzeigen
                    </button>
                    <div class="c-email-message__full-content" style="display: none;">
                        <iframe class="c-email-message__iframe" srcdoc="${escapeHtml(email.body_html)}" sandbox="allow-same-origin" title="Email content"></iframe>
                    </div>
                </div>
            `;
        }
    } else {
        const trimmedText = trimEmailContent(email.body_plain);
        html += `
            <div class="c-email-message__content">
                <div class="c-email-message__text">${escapeHtml(trimmedText).replace(/\n/g, '<br>')}</div>
            </div>
        `;
        
        // Show full message toggle if content was trimmed
        if (trimmedText !== email.body_plain) {
            html += `
                <div class="c-email-message__show-more">
                    <button class="c-button c-button--link c-button--sm" data-action="show-full-message">
                        Vollständige Nachricht anzeigen
                    </button>
                    <div class="c-email-message__full-content" style="display: none;">
                        <div class="c-email-message__text">${escapeHtml(email.body_plain).replace(/\n/g, '<br>')}</div>
                    </div>
                </div>
            `;
        }
    }
    
    html += '</div>';
    
    // Attachments
    if (email.has_attachments && email.attachments && email.attachments.length > 0) {
        html += renderAttachments(email.attachments);
    }
    
    html += '</article>';
    
    return html;
}

/**
 * Render attachments
 */
function renderAttachments(attachments) {
    let html = `
        <div class="c-email-message__attachments">
            <h4 class="c-email-message__attachments-title">Anhänge (${attachments.length})</h4>
            <div class="c-email-message__attachments-list">
    `;
    
    attachments.forEach(attachment => {
        const sizeKB = (attachment.size / 1024).toFixed(1);
        html += `
            <div class="c-attachment">
                <svg class="c-attachment__icon" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                <div class="c-attachment__info">
                    <span class="c-attachment__name">${escapeHtml(attachment.filename)}</span>
                    <span class="c-attachment__size">${sizeKB} KB</span>
                </div>
                <button class="c-button c-button--icon c-attachment__download" title="Herunterladen">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                </button>
            </div>
        `;
    });
    
    html += '</div></div>';
    return html;
}

/**
 * Render internal notes section
 */
/**
 * Render a single note item (inline with emails)
 */
function renderNoteItem(note) {
    // Build time display: show "bearbeitet vor X" if edited, otherwise "vor X"
    let timeDisplay = '';
    if (note.was_edited) {
        const editorName = note.updated_by_name || 'Unbekannt';
        timeDisplay = `<span class="c-note__edited" title="Bearbeitet von ${escapeHtml(editorName)}">bearbeitet</span> <time datetime="${note.updated_at}">${note.updated_at_human || note.created_at_human}</time>`;
    } else {
        timeDisplay = `<time datetime="${note.created_at}">${note.created_at_human}</time>`;
    }
    
    let html = `
        <div class="c-note c-note--inline" data-note-id="${note.id}" data-note-content="${escapeHtml(note.content).replace(/"/g, '&quot;')}">
            <div class="c-note__header">
                <div class="c-note__meta">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                    </svg>
                    <strong>${escapeHtml(note.created_by_name || 'System')}</strong>
                    ${timeDisplay}
                </div>
                <div class="c-note__actions">
                    <button class="c-note__edit" data-action="edit-note" data-note-id="${note.id}" title="Notiz bearbeiten">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    <button class="c-note__delete" data-action="delete-note" data-note-id="${note.id}" title="Notiz löschen">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="c-note__content">${escapeHtml(note.content).replace(/\n/g, '<br>')}</div>
        </div>
    `;
    
    return html;
}

/**
 * Render notes section (old function - kept for backwards compatibility but not used in mixed view)
 */
function renderNotes(threadId, notes) {
    let html = `
        <div class="c-thread-detail__notes">
            <h3 class="c-thread-detail__notes-title">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                </svg>
                Interne Notizen (${notes.length})
            </h3>
            
            <div class="c-thread-detail__notes-list">
    `;
    
    notes.forEach(note => {
        html += `
            <div class="c-note">
                <div class="c-note__header">
                    <strong>${escapeHtml(note.created_by_name || 'System')}</strong>
                    <time datetime="${note.created_at}">${note.created_at_human}</time>
                </div>
                <div class="c-note__content">${escapeHtml(note.content).replace(/\n/g, '<br>')}</div>
            </div>
        `;
    });
    
    html += `
            </div>
            
            <form class="c-note-form" data-thread-id="${threadId}">
                <textarea 
                    class="c-note-form__input" 
                    name="content" 
                    placeholder="Neue interne Notiz hinzufügen..."
                    rows="3"
                ></textarea>
                <button type="submit" class="c-button c-button--primary c-button--sm">
                    Notiz hinzufügen
                </button>
            </form>
        </div>
    `;
    
    return html;
}

/**
 * Initialize dropdown functionality
 * Uses event delegation for dynamic dropdowns
 */
let dropdownInitialized = false;

/**
 * Handle single thread actions
 */
function handleThreadAction(action, element) {
    const threadId = getCurrentThreadId();
    
    console.log('[handleThreadAction] Action:', action, 'ThreadId:', threadId);
    console.log('[handleThreadAction] Detail container:', document.querySelector('.c-thread-detail[data-thread-id]'));
    console.log('[handleThreadAction] Thread list item:', document.querySelector('.c-thread-item.is-active'));
    
    if (!threadId) {
        console.error('No thread ID found for action:', action);
        return;
    }
    
    console.log('Thread action:', action, 'on thread:', threadId);
    
    switch(action) {
        case 'mark-read':
            markThreadAsRead(threadId);
            break;
        case 'mark-unread':
            markThreadAsUnread(threadId);
            break;
        case 'archive':
            archiveThread(threadId);
            break;
        case 'delete':
            confirmDeleteThread(threadId);
            break;
        case 'reply':
            replyToThread(threadId);
            break;
        case 'reply-private':
            privateReplyToThread(threadId);
            break;
        case 'forward':
            forwardThread(threadId);
            break;
        case 'label':
            showLabelPicker(threadId);
            break;
        case 'assign':
            showAssignmentPicker(threadId);
            break;
        case 'move':
            // TODO: Implement move
            alert('Verschieben - noch nicht implementiert');
            break;
        case 'status':
            showStatusPicker(threadId);
            break;
        default:
            console.warn('Unknown action:', action);
    }
}

/**
 * Mark thread as read
 */
async function markThreadAsRead(threadId) {
    try {
        const response = await fetch(`/api/threads/${threadId}/mark-read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Failed to mark thread as read');
        }
        
        const result = await response.json();
        console.log('Thread marked as read:', result);
        
        // Update UI: Mark thread item as read in the list
        const threadItem = document.querySelector(`.c-thread-item[data-thread-id="${threadId}"]`);
        if (threadItem) {
            threadItem.classList.remove('is-unread');
        }
        
        // Show success feedback
        showSuccessMessage('Thread als gelesen markiert');
        
    } catch (error) {
        console.error('Error marking thread as read:', error);
        alert('Fehler beim Markieren als gelesen: ' + error.message);
    }
}

/**
 * Mark thread as unread
 */
async function markThreadAsUnread(threadId) {
    try {
        const response = await fetch(`/api/threads/${threadId}/mark-unread`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Failed to mark thread as unread');
        }
        
        const result = await response.json();
        console.log('Thread marked as unread:', result);
        
        // Update UI: Mark thread item as unread in the list
        const threadItem = document.querySelector(`.c-thread-item[data-thread-id="${threadId}"]`);
        if (threadItem) {
            threadItem.classList.add('is-unread');
        }
        
        // Show success feedback
        showSuccessMessage('Thread als ungelesen markiert');
        
    } catch (error) {
        console.error('Error marking thread as unread:', error);
        alert('Fehler beim Markieren als ungelesen: ' + error.message);
    }
}

/**
 * Archive thread
 */
async function archiveThread(threadId) {
    try {
        const response = await fetch(`/api/threads/${threadId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                status: 'archived'
            })
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Failed to archive thread');
        }
        
        const result = await response.json();
        console.log('Thread archived:', result);
        
        // Remove thread from list (visual)
        const threadItem = document.querySelector(`.c-thread-item[data-thread-id="${threadId}"]`);
        if (threadItem) {
            threadItem.style.transition = 'opacity 0.3s ease-out';
            threadItem.style.opacity = '0';
            setTimeout(() => {
                threadItem.remove();
                
                // If this was the active thread, clear detail view
                if (threadItem.classList.contains('is-active')) {
                    const detailTitle = document.getElementById('detail-title');
                    const detailActions = document.getElementById('detail-actions');
                    const detailContent = document.querySelector('.c-inbox__thread-detail');
                    
                    if (detailTitle) detailTitle.textContent = 'Wähle einen Thread';
                    if (detailActions) detailActions.style.display = 'none';
                    if (detailContent) detailContent.innerHTML = '';
                }
            }, 300);
        }
        
        showSuccessMessage('Thread archiviert');
        
    } catch (error) {
        console.error('Error archiving thread:', error);
        alert('Fehler beim Archivieren: ' + error.message);
    }
}

/**
 * Confirm and delete thread
 */
function confirmDeleteThread(threadId) {
    // Get thread details for confirmation
    const threadItem = document.querySelector(`.c-thread-item[data-thread-id="${threadId}"]`);
    const subject = threadItem ? threadItem.querySelector('.c-thread-item__subject')?.textContent : 'diesem Thread';
    
    showConfirmDialog({
        title: 'Thread löschen?',
        message: `Möchten Sie den Thread wirklich löschen?`,
        details: subject,
        confirmText: 'Löschen',
        cancelText: 'Abbrechen',
        danger: true,
        onConfirm: () => deleteThread(threadId)
    });
}

/**
 * Delete thread (after confirmation)
 */
async function deleteThread(threadId) {
    try {
        const response = await fetch(`/api/threads/${threadId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Failed to delete thread');
        }
        
        const result = await response.json();
        console.log('Thread deleted:', result);
        
        // Remove thread from list (visual)
        const threadItem = document.querySelector(`.c-thread-item[data-thread-id="${threadId}"]`);
        if (threadItem) {
            const wasActive = threadItem.classList.contains('is-active');
            
            threadItem.style.transition = 'opacity 0.3s ease-out';
            threadItem.style.opacity = '0';
            setTimeout(() => {
                threadItem.remove();
                
                // If this was the active thread, select next or clear detail view
                if (wasActive) {
                    const nextThread = document.querySelector('.c-thread-item');
                    if (nextThread) {
                        nextThread.click();
                    } else {
                        // No threads left - clear detail view
                        const detailTitle = document.getElementById('detail-title');
                        const detailActions = document.getElementById('detail-actions');
                        const detailContent = document.querySelector('.c-inbox__thread-detail');
                        
                        if (detailTitle) detailTitle.textContent = 'Wähle einen Thread';
                        if (detailActions) detailActions.style.display = 'none';
                        if (detailContent) detailContent.innerHTML = '';
                    }
                }
            }, 300);
        }
        
        showSuccessMessage('Thread gelöscht');
        
    } catch (error) {
        console.error('Error deleting thread:', error);
        alert('Fehler beim Löschen: ' + error.message);
    }
}

/**
 * Show confirmation dialog
 */
function showConfirmDialog(options) {
    const {
        title = 'Bestätigen',
        message = 'Sind Sie sicher?',
        details = null,
        confirmText = 'OK',
        cancelText = 'Abbrechen',
        danger = false,
        isHtml = false,
        onConfirm = () => {},
        onCancel = () => {}
    } = options;
    
    // Remove existing modal if any
    const existingModal = document.querySelector('.c-modal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Create modal
    const modal = document.createElement('div');
    modal.className = `c-modal ${danger ? 'c-modal--danger' : ''}`;
    modal.innerHTML = `
        <div class="c-modal__dialog">
            <div class="c-modal__header">
                <h3 class="c-modal__title">
                    ${danger ? '<svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>' : ''}
                    ${escapeHtml(title)}
                </h3>
                <button class="c-modal__close" data-action="cancel">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="c-modal__body">
                ${isHtml ? message : `<p class="c-modal__message">${escapeHtml(message)}</p>`}
                ${details ? `<div class="c-modal__details">${escapeHtml(details)}</div>` : ''}
            </div>
            <div class="c-modal__footer">
                <button class="c-button c-button--secondary" data-action="cancel">${escapeHtml(cancelText)}</button>
                <button class="c-button ${danger ? 'c-button--danger' : 'c-button--primary'}" data-action="confirm">${escapeHtml(confirmText)}</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Show modal with animation
    setTimeout(() => {
        modal.classList.add('c-modal--open');
    }, 10);
    
    // Handle confirm
    const confirmBtn = modal.querySelector('[data-action="confirm"]');
    confirmBtn.addEventListener('click', (e) => {
        e.stopPropagation(); // Prevent event bubbling to thread action handlers
        
        // Call onConfirm BEFORE removing modal (so elements are still in DOM)
        onConfirm();
        
        // Then close modal
        modal.classList.remove('c-modal--open');
        setTimeout(() => {
            modal.remove();
        }, 200);
    });
    
    // Handle cancel
    const cancelBtns = modal.querySelectorAll('[data-action="cancel"]');
    cancelBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation(); // Prevent event bubbling to thread action handlers
            modal.classList.remove('c-modal--open');
            setTimeout(() => {
                modal.remove();
                onCancel();
            }, 200);
        });
    });
    
    // Close on backdrop click
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.remove('c-modal--open');
            setTimeout(() => {
                modal.remove();
                onCancel();
            }, 200);
        }
    });
    
    // Close on ESC key
    const handleEsc = (e) => {
        if (e.key === 'Escape') {
            modal.classList.remove('c-modal--open');
            setTimeout(() => {
                modal.remove();
                onCancel();
            }, 200);
            document.removeEventListener('keydown', handleEsc);
        }
    };
    document.addEventListener('keydown', handleEsc);
}

/**
 * Show success message (simple toast notification)
 */
function showSuccessMessage(message) {
    // Simple alert for now - can be replaced with toast component later
    console.log('SUCCESS:', message);
    
    // TODO: Implement proper toast notification component
    // For now, just log to console
}

function initDropdowns() {
    // Prevent multiple initialization
    if (dropdownInitialized) return;
    dropdownInitialized = true;
    
    // Event delegation on document level
    document.addEventListener('click', function(e) {
        const trigger = e.target.closest('.c-dropdown__trigger');
        const dropdown = e.target.closest('.c-dropdown');
        
        // Handle direct action buttons (not in dropdown)
        const actionButton = e.target.closest('[data-action]');
        if (actionButton && !actionButton.classList.contains('c-dropdown__item') && !actionButton.classList.contains('c-dropdown__trigger')) {
            const action = actionButton.dataset.action;
            if (action) {
                e.preventDefault();
                handleThreadAction(action, actionButton);
                return;
            }
        }
        
        // Block disabled dropdown items
        const dropdownItem = e.target.closest('.c-dropdown__item');
        if (dropdownItem && dropdownItem.classList.contains('c-dropdown__item--disabled')) {
            e.preventDefault();
            e.stopPropagation();
            return;
        }
        
        // Click on trigger button
        if (trigger) {
            console.log('[Dropdown] Trigger clicked', trigger);
            e.preventDefault();
            e.stopPropagation();
            
            const parentDropdown = trigger.closest('.c-dropdown');
            const isOpen = parentDropdown.classList.contains('c-dropdown--open');
            
            console.log('[Dropdown] Parent dropdown:', parentDropdown, 'isOpen:', isOpen);
            
            // Close all other dropdowns first
            document.querySelectorAll('.c-dropdown--open').forEach(d => {
                if (d !== parentDropdown) {
                    d.classList.remove('c-dropdown--open');
                }
            });
            
            // Toggle this dropdown
            parentDropdown.classList.toggle('c-dropdown--open');
            console.log('[Dropdown] After toggle, isOpen:', parentDropdown.classList.contains('c-dropdown--open'));
            return;
        }
        
        // Click on dropdown item
        if (e.target.closest('.c-dropdown__item')) {
            const item = e.target.closest('.c-dropdown__item');
            
            // Don't close if disabled
            if (item.disabled) {
                e.preventDefault();
                return;
            }
            
            // Handle action if present
            const action = item.dataset.action;
            if (action) {
                handleThreadAction(action, item);
            }
            
            // Close dropdown after action
            if (dropdown) {
                dropdown.classList.remove('c-dropdown--open');
            }
            return;
        }
        
        // Click outside any dropdown - close all
        if (!dropdown) {
            document.querySelectorAll('.c-dropdown--open').forEach(d => {
                d.classList.remove('c-dropdown--open');
            });
        }
    });
    
    // Close on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.c-dropdown--open').forEach(d => {
                d.classList.remove('c-dropdown--open');
            });
        }
    });
}

/**
 * Initialize email message collapsible functionality
 */
function initEmailCollapse() {
    document.addEventListener('click', function(e) {
        const toggle = e.target.closest('.c-email-message__toggle');
        const header = e.target.closest('.c-email-message__header');
        
        if (toggle || (header && header.closest('.c-email-message').classList.contains('is-collapsed'))) {
            e.preventDefault();
            const emailMessage = (toggle || header).closest('.c-email-message');
            emailMessage.classList.toggle('is-collapsed');
            
            // Update toggle button title
            const toggleBtn = emailMessage.querySelector('.c-email-message__toggle');
            const isCollapsed = emailMessage.classList.contains('is-collapsed');
            toggleBtn.setAttribute('title', isCollapsed ? 'Details anzeigen' : 'Details verbergen');
        }
    });
}

/**
 * Initialize note mode functionality
 */
function initNoteMode() {
    document.addEventListener('click', function(e) {
        // Toggle note mode
        if (e.target.closest('#toggle-note-mode')) {
            e.preventDefault();
            const threadDetail = document.querySelector('.c-thread-detail');
            const isActive = threadDetail.classList.contains('note-mode-active');
            
            threadDetail.classList.toggle('note-mode-active');
            
            const button = e.target.closest('#toggle-note-mode');
            button.textContent = isActive ? 'Notiz' : 'Abbrechen';
            
            // Show/hide dropzones
            document.querySelectorAll('.c-note-dropzone').forEach(zone => {
                zone.style.display = isActive ? 'none' : 'block';
            });
            
            return;
        }
        
        // Click on dropzone to add note
        if (e.target.closest('.c-note-dropzone__button')) {
            e.preventDefault();
            const dropzone = e.target.closest('.c-note-dropzone');
            const position = dropzone.dataset.position;
            
            // Create note form inline
            const form = document.createElement('div');
            form.className = 'c-note-inline-form';
            form.innerHTML = `
                <textarea 
                    class="c-note-form__input" 
                    placeholder="Notiz hinzufügen..."
                    rows="3"
                    autofocus
                ></textarea>
                <div style="display: flex; gap: 8px; margin-top: 8px;">
                    <button class="c-button c-button--primary c-button--sm" data-action="save-note" data-position="${position}">
                        Speichern
                    </button>
                    <button class="c-button c-button--secondary c-button--sm" data-action="cancel-note">
                        Abbrechen
                    </button>
                </div>
            `;
            
            dropzone.replaceWith(form);
            form.querySelector('textarea').focus();
            return;
        }
        
        // Save note
        if (e.target.closest('[data-action="save-note"]')) {
            e.preventDefault();
            const button = e.target.closest('button');
            const form = button.closest('.c-note-inline-form');
            const textarea = form.querySelector('textarea');
            const position = button.dataset.position;
            const content = textarea.value.trim();
            
            console.log('Save note clicked', { content, position });
            
            if (!content) {
                alert('Bitte gib einen Notiztext ein.');
                return;
            }
            
            // Get current thread ID
            const threadId = getCurrentThreadId();
            console.log('Current thread ID:', threadId);
            
            if (!threadId) {
                alert('Thread ID nicht gefunden.');
                return;
            }
            
            // Disable button during save
            button.disabled = true;
            button.textContent = 'Speichert...';
            
            // Save note via API
            saveNote(threadId, content, position)
                .then((result) => {
                    console.log('Note saved successfully:', result);
                    // Reload thread to show new note
                    if (typeof loadThreadDetail === 'function') {
                        loadThreadDetail(threadId);
                    }
                })
                .catch(error => {
                    console.error('Error saving note:', error);
                    alert('Fehler beim Speichern der Notiz: ' + error.message);
                    button.disabled = false;
                    button.textContent = 'Speichern';
                });
            return;
        }
        
        // Cancel note
        if (e.target.closest('[data-action="cancel-note"]')) {
            e.preventDefault();
            const form = e.target.closest('.c-note-inline-form');
            
            // Restore dropzone
            const dropzone = document.createElement('div');
            dropzone.className = 'c-note-dropzone';
            dropzone.style.display = 'block';
            dropzone.innerHTML = `
                <button class="c-note-dropzone__button">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Notiz hier einfügen
                </button>
            `;
            
            form.replaceWith(dropzone);
            return;
        }
        
        // Delete note
        if (e.target.closest('[data-action="delete-note"]')) {
            e.preventDefault();
            const button = e.target.closest('[data-action="delete-note"]');
            const noteId = button.dataset.noteId;
            
            if (!confirm('Möchtest du diese Notiz wirklich löschen?')) {
                return;
            }
            
            const threadId = getCurrentThreadId();
            if (!threadId) {
                alert('Thread ID nicht gefunden.');
                return;
            }
            
            // Delete note via API
            deleteNote(threadId, noteId)
                .then(() => {
                    console.log('Note deleted successfully');
                    // Reload thread to update view
                    if (typeof loadThreadDetail === 'function') {
                        loadThreadDetail(threadId);
                    }
                })
                .catch(error => {
                    console.error('Error deleting note:', error);
                    alert('Fehler beim Löschen der Notiz: ' + error.message);
                });
            return;
        }
        
        // Edit note
        if (e.target.closest('[data-action="edit-note"]')) {
            e.preventDefault();
            const button = e.target.closest('[data-action="edit-note"]');
            const noteId = button.dataset.noteId;
            const noteElement = button.closest('.c-note');
            
            // Get original content from data attribute (preserves line breaks)
            let currentContent = noteElement.dataset.noteContent;
            
            // Decode HTML entities
            const textarea = document.createElement('textarea');
            textarea.innerHTML = currentContent;
            currentContent = textarea.value;
            
            const contentElement = noteElement.querySelector('.c-note__content');
            
            // Replace content with textarea
            const editForm = document.createElement('div');
            editForm.className = 'c-note-form c-note-form--edit';
            editForm.innerHTML = `
                <textarea class="c-note-form__input" rows="3" required>${escapeHtml(currentContent)}</textarea>
                <div style="display: flex; gap: var(--spacing-2);">
                    <button type="button" class="c-button c-button--primary c-button--sm" data-action="save-note-edit">Speichern</button>
                    <button type="button" class="c-button c-button--secondary c-button--sm" data-action="cancel-note-edit">Abbrechen</button>
                </div>
            `;
            
            contentElement.replaceWith(editForm);
            editForm.querySelector('textarea').focus();
            
            return;
        }
        
        // Save note edit
        if (e.target.closest('[data-action="save-note-edit"]')) {
            e.preventDefault();
            const button = e.target.closest('[data-action="save-note-edit"]');
            const editForm = button.closest('.c-note-form--edit');
            const textarea = editForm.querySelector('textarea');
            const newContent = textarea.value;
            
            if (!newContent || !newContent.trim()) {
                alert('Notiz darf nicht leer sein.');
                return;
            }
            
            const noteElement = editForm.closest('.c-note');
            const noteId = noteElement.dataset.noteId;
            const threadId = getCurrentThreadId();
            
            if (!threadId) {
                alert('Thread ID nicht gefunden.');
                return;
            }
            
            // Update note via API
            updateNote(threadId, noteId, newContent)
                .then(() => {
                    console.log('Note updated successfully');
                    // Reload thread to update view
                    if (typeof loadThreadDetail === 'function') {
                        loadThreadDetail(threadId);
                    }
                })
                .catch(error => {
                    console.error('Error updating note:', error);
                    alert('Fehler beim Speichern der Notiz: ' + error.message);
                });
            
            return;
        }
        
        // Cancel note edit
        if (e.target.closest('[data-action="cancel-note-edit"]')) {
            e.preventDefault();
            const threadId = getCurrentThreadId();
            
            if (threadId && typeof loadThreadDetail === 'function') {
                loadThreadDetail(threadId);
            }
            
            return;
        }
        
        // Show full message
        if (e.target.closest('[data-action="show-full-message"]')) {
            e.preventDefault();
            const showMoreDiv = e.target.closest('.c-email-message__show-more');
            const button = e.target.closest('button');
            const contentDiv = showMoreDiv.querySelector('.c-email-message__content');
            const fullContentDiv = showMoreDiv.querySelector('.c-email-message__full-content');
            
            if (fullContentDiv.style.display === 'none') {
                // Show full content
                if (contentDiv) contentDiv.style.display = 'none';
                fullContentDiv.style.display = 'block';
                button.textContent = 'Gekürzte Nachricht anzeigen';
            } else {
                // Show trimmed content
                if (contentDiv) contentDiv.style.display = 'block';
                fullContentDiv.style.display = 'none';
                button.textContent = 'Vollständige Nachricht anzeigen';
            }
        }
    });
}

/**
 * Handle bulk actions on multiple threads
 */
function handleBulkAction(action, threadIds, threadElements) {
    if (!threadIds || threadIds.length === 0) {
        console.warn('No threads selected for bulk action');
        return;
    }
    
    console.log('Bulk action:', action, 'on', threadIds.length, 'threads');
    
    switch(action) {
        case 'mark-read':
            bulkMarkAsRead(threadIds, threadElements);
            break;
        case 'mark-unread':
            bulkMarkAsUnread(threadIds, threadElements);
            break;
        case 'archive':
            bulkArchive(threadIds, threadElements);
            break;
        case 'delete':
            confirmBulkDelete(threadIds, threadElements);
            break;
        case 'label':
            showBulkLabelPicker(threadIds);
            break;
        default:
            console.warn('Unknown bulk action:', action);
    }
}

/**
 * Bulk mark threads as read
 */
async function bulkMarkAsRead(threadIds, threadElements) {
    try {
        const response = await fetch('/api/threads/bulk/status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                thread_ids: threadIds,
                status: 'open',
                is_read: true
            })
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Failed to mark threads as read');
        }
        
        const result = await response.json();
        console.log('Bulk mark as read:', result);
        
        // Update UI
        threadElements.forEach(threadItem => {
            threadItem.classList.remove('is-unread');
            threadItem.classList.remove('is-selected');
        });
        
        showSuccessMessage(`${result.updated || threadIds.length} Threads als gelesen markiert`);
        
    } catch (error) {
        console.error('Error in bulk mark as read:', error);
        alert('Fehler beim Markieren als gelesen: ' + error.message);
    }
}

/**
 * Bulk mark threads as unread
 */
async function bulkMarkAsUnread(threadIds, threadElements) {
    try {
        const response = await fetch('/api/threads/bulk/status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                thread_ids: threadIds,
                status: 'open',
                is_read: false
            })
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Failed to mark threads as unread');
        }
        
        const result = await response.json();
        console.log('Bulk mark as unread:', result);
        
        // Update UI
        threadElements.forEach(threadItem => {
            threadItem.classList.add('is-unread');
            threadItem.classList.remove('is-selected');
        });
        
        showSuccessMessage(`${result.updated || threadIds.length} Threads als ungelesen markiert`);
        
    } catch (error) {
        console.error('Error in bulk mark as unread:', error);
        alert('Fehler beim Markieren als ungelesen: ' + error.message);
    }
}

/**
 * Bulk archive threads
 */
async function bulkArchive(threadIds, threadElements) {
    try {
        const response = await fetch('/api/threads/bulk/status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                thread_ids: threadIds,
                status: 'archived'
            })
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Failed to archive threads');
        }
        
        const result = await response.json();
        console.log('Bulk archive:', result);
        
        // Remove threads from list with animation
        threadElements.forEach((threadItem, index) => {
            setTimeout(() => {
                threadItem.style.transition = 'opacity 0.3s ease-out';
                threadItem.style.opacity = '0';
                setTimeout(() => {
                    threadItem.remove();
                }, 300);
            }, index * 50); // Stagger animations
        });
        
        // Clear detail view if active thread was archived
        const activeThread = threadElements.find(t => t.classList.contains('is-active'));
        if (activeThread) {
            setTimeout(() => {
                const nextThread = document.querySelector('.c-thread-item');
                if (nextThread) {
                    nextThread.click();
                } else {
                    const detailTitle = document.getElementById('detail-title');
                    const detailActions = document.getElementById('detail-actions');
                    const detailContent = document.querySelector('.c-inbox__thread-detail');
                    
                    if (detailTitle) detailTitle.textContent = 'Wähle einen Thread';
                    if (detailActions) detailActions.style.display = 'none';
                    if (detailContent) detailContent.innerHTML = '';
                }
            }, threadElements.length * 50 + 300);
        }
        
        showSuccessMessage(`${result.updated || threadIds.length} Threads archiviert`);
        
    } catch (error) {
        console.error('Error in bulk archive:', error);
        alert('Fehler beim Archivieren: ' + error.message);
    }
}

/**
 * Confirm and bulk delete threads
 */
function confirmBulkDelete(threadIds, threadElements) {
    const count = threadIds.length;
    const message = count === 1
        ? 'Möchten Sie diesen Thread wirklich löschen?'
        : `Möchten Sie ${count} Threads wirklich löschen?`;
    const confirmText = count === 1 ? 'Löschen' : `Alle ${count} löschen`;
    
    showConfirmDialog({
        title: 'Threads löschen?',
        message: message,
        details: 'Diese Aktion kann nicht rückgängig gemacht werden.',
        confirmText: confirmText,
        cancelText: 'Abbrechen',
        danger: true,
        onConfirm: () => bulkDelete(threadIds, threadElements)
    });
}

/**
 * Bulk delete threads (after confirmation)
 */
async function bulkDelete(threadIds, threadElements) {
    try {
        console.log('Bulk delete starting:', threadIds);
        const payload = { thread_ids: threadIds };
        console.log('Payload:', JSON.stringify(payload));
        
        const response = await fetch('/api/threads/bulk/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        
        console.log('Response status:', response.status, response.statusText);
        
        const responseText = await response.text();
        let result = {};
        
        if (responseText) {
            try {
                result = JSON.parse(responseText);
            } catch (e) {
                console.error('Failed to parse response:', responseText);
            }
        }
        
        if (!response.ok) {
            throw new Error(result.error || 'Failed to delete threads');
        }
        console.log('Bulk delete:', result);
        
        // Remove threads from list with animation
        threadElements.forEach((threadItem, index) => {
            setTimeout(() => {
                threadItem.style.transition = 'opacity 0.3s ease-out';
                threadItem.style.opacity = '0';
                setTimeout(() => {
                    threadItem.remove();
                }, 300);
            }, index * 50); // Stagger animations
        });
        
        // Clear detail view if active thread was deleted
        const activeThread = threadElements.find(t => t.classList.contains('is-active'));
        if (activeThread) {
            setTimeout(() => {
                const nextThread = document.querySelector('.c-thread-item');
                if (nextThread) {
                    nextThread.click();
                } else {
                    const detailTitle = document.getElementById('detail-title');
                    const detailActions = document.getElementById('detail-actions');
                    const detailContent = document.querySelector('.c-inbox__thread-detail');
                    
                    if (detailTitle) detailTitle.textContent = 'Wähle einen Thread';
                    if (detailActions) detailActions.style.display = 'none';
                    if (detailContent) detailContent.innerHTML = '';
                }
            }, threadElements.length * 50 + 300);
        }
        
        showSuccessMessage(`${result.deleted || threadIds.length} Threads gelöscht`);
        
    } catch (error) {
        console.error('Error in bulk delete:', error);
        alert('Fehler beim Löschen: ' + error.message);
    }
}

/**
 * Initialize context menu for bulk operations on threads
 */
let contextMenuInitialized = false;

function initContextMenu() {
    if (contextMenuInitialized) return;
    contextMenuInitialized = true;
    
    // Create context menu element
    const contextMenu = document.createElement('div');
    contextMenu.className = 'c-context-menu';
    contextMenu.id = 'thread-context-menu';
    contextMenu.innerHTML = `
        <div class="c-context-menu__header" id="context-menu-header"></div>
        <button class="c-context-menu__item" data-action="label">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
            </svg>
            Label zuweisen
        </button>
        <button class="c-context-menu__item" data-action="assign">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            Nutzer zuweisen
        </button>
        <button class="c-context-menu__item" data-action="status">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Status ändern
        </button>
        <button class="c-context-menu__item" data-action="mark-read">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76"/>
            </svg>
            Als gelesen markieren
        </button>
        <button class="c-context-menu__item" data-action="mark-unread">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            Als ungelesen markieren
        </button>
        <div class="c-context-menu__separator"></div>
        <button class="c-context-menu__item" data-action="archive">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
            </svg>
            Archivieren
        </button>
        <div class="c-context-menu__separator"></div>
        <button class="c-context-menu__item c-context-menu__item--danger" data-action="delete">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Löschen
        </button>
    `;
    document.body.appendChild(contextMenu);
    
    // Right-click on thread items
    document.addEventListener('contextmenu', function(e) {
        const threadItem = e.target.closest('.c-thread-item');
        if (!threadItem) return;
        
        e.preventDefault();
        
        // Get all selected threads
        const selectedThreads = document.querySelectorAll('.c-thread-item.is-selected');
        const count = selectedThreads.length;
        
        // If right-clicked thread is not selected, select only it
        if (!threadItem.classList.contains('is-selected')) {
            document.querySelectorAll('.c-thread-item.is-selected').forEach(item => {
                item.classList.remove('is-selected');
            });
            threadItem.classList.add('is-selected');
        }
        
        // Update header with count
        const header = document.getElementById('context-menu-header');
        const finalCount = document.querySelectorAll('.c-thread-item.is-selected').length;
        header.textContent = finalCount === 1 ? '1 Thread' : `${finalCount} Threads`;
        
        // Show context menu at mouse position
        contextMenu.style.left = e.clientX + 'px';
        contextMenu.style.top = e.clientY + 'px';
        contextMenu.classList.add('c-context-menu--open');
    });
    
    // Close context menu on click outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.c-context-menu')) {
            contextMenu.classList.remove('c-context-menu--open');
        }
    });
    
    // Handle context menu actions
    contextMenu.addEventListener('click', function(e) {
        const item = e.target.closest('.c-context-menu__item');
        if (!item) return;
        
        const action = item.dataset.action;
        const selectedThreads = Array.from(document.querySelectorAll('.c-thread-item.is-selected'));
        const threadIds = selectedThreads.map(t => parseInt(t.dataset.threadId));
        
        console.log('Bulk action:', action, 'on threads:', threadIds);
        
        // Handle single vs bulk
        if (threadIds.length === 1 && action === 'label') {
            showLabelPicker(threadIds[0]);
        } else if (threadIds.length === 1 && action === 'assign') {
            showAssignmentPicker(threadIds[0]);
        } else if (threadIds.length === 1 && action === 'status') {
            showStatusPicker(threadIds[0]);
        } else {
            handleBulkAction(action, threadIds, selectedThreads);
        }
        
        contextMenu.classList.remove('c-context-menu--open');
    });
}

/**
 * Initialize Ctrl+Click multi-select for threads
 */
function initThreadMultiSelect() {
    // Add checkbox support for thread selection
    document.addEventListener('click', function(e) {
        const threadItem = e.target.closest('.c-thread-item');
        if (!threadItem) return;
        
        // Ctrl/Cmd + Click for multi-select
        if (e.ctrlKey || e.metaKey) {
            e.preventDefault();
            e.stopPropagation();
            threadItem.classList.toggle('is-selected');
            return false;
        }
    });
}

/**
 * Auto-mark emails as read after 5 seconds of viewing
 */
let autoReadTimers = new Map();

function initAutoReadEmails() {
    // Observer for unread emails coming into view
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            const emailEl = entry.target;
            const emailId = parseInt(emailEl.dataset.emailId);
            
            if (entry.isIntersecting && emailEl.classList.contains('is-unread')) {
                // Email is visible and unread - start timer
                if (!autoReadTimers.has(emailId)) {
                    const timer = setTimeout(() => {
                        markEmailAsRead(emailId, emailEl);
                    }, 5000); // 5 seconds
                    
                    autoReadTimers.set(emailId, timer);
                    console.log(`Auto-read timer started for email ${emailId}`);
                }
            } else {
                // Email is no longer visible - cancel timer
                if (autoReadTimers.has(emailId)) {
                    clearTimeout(autoReadTimers.get(emailId));
                    autoReadTimers.delete(emailId);
                    console.log(`Auto-read timer cancelled for email ${emailId}`);
                }
            }
        });
    }, {
        threshold: 0.5 // Email must be 50% visible
    });
    
    // Observe all unread email messages
    const observeUnreadEmails = () => {
        document.querySelectorAll('.c-email-message.is-unread').forEach(emailEl => {
            observer.observe(emailEl);
        });
    };
    
    // Initial observation
    observeUnreadEmails();
    
    // Re-observe when thread details are loaded
    const detailPanel = document.querySelector('.c-inbox__thread-detail');
    if (detailPanel) {
        const panelObserver = new MutationObserver(() => {
            observeUnreadEmails();
        });
        
        panelObserver.observe(detailPanel, {
            childList: true,
            subtree: true
        });
    }
}

/**
 * Mark single email as read
 */
async function markEmailAsRead(emailId, emailElement) {
    try {
        const threadId = emailElement.closest('[data-thread-id]')?.dataset.threadId ||
                        document.querySelector('.c-thread-item.is-active')?.dataset.threadId;
        
        if (!threadId) {
            console.error('Could not determine thread ID for email', emailId);
            return;
        }
        
        console.log(`Marking email ${emailId} as read...`);
        
        const response = await fetch(`/api/emails/${emailId}/read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to mark email as read');
        }
        
        // Update UI
        emailElement.classList.remove('is-unread');
        const badge = emailElement.querySelector('.c-badge');
        if (badge) {
            badge.remove();
        }
        
        console.log(`Email ${emailId} marked as read`);
        
        // Check if all emails in thread are now read
        const remainingUnread = document.querySelectorAll('.c-email-message.is-unread').length;
        if (remainingUnread === 0) {
            // Auto-mark thread as read
            await markThreadAsRead(parseInt(threadId));
        }
        
        // Cancel timer
        if (autoReadTimers.has(emailId)) {
            clearTimeout(autoReadTimers.get(emailId));
            autoReadTimers.delete(emailId);
        }
        
    } catch (error) {
        console.error('Error marking email as read:', error);
    }
}

/**
 * Mark thread as read (update thread list item)
 */
async function markThreadAsRead(threadId) {
    try {
        console.log(`Marking thread ${threadId} as read...`);
        
        const response = await fetch(`/api/threads/${threadId}/mark-read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to mark thread as read');
        }
        
        // Update UI - remove unread indicator from thread list
        const threadItem = document.querySelector(`.c-thread-item[data-thread-id="${threadId}"]`);
        if (threadItem) {
            threadItem.classList.remove('is-unread');
            const unreadBadge = threadItem.querySelector('.c-thread-item__unread-badge');
            if (unreadBadge) {
                unreadBadge.remove();
            }
        }
        
        console.log(`Thread ${threadId} marked as read`);
        
    } catch (error) {
        console.error('Error marking thread as read:', error);
    }
}

/**
 * Label Picker - Show label selection dialog
 */
let availableLabels = [];

async function showLabelPicker(threadId) {
    try {
        // Load labels if not cached
        if (availableLabels.length === 0) {
            const response = await fetch('/api/labels');
            if (!response.ok) throw new Error('Failed to load labels');
            const data = await response.json();
            availableLabels = Array.isArray(data) ? data : (data.labels || []);
        }
        
        // Get current thread labels
        const threadResponse = await fetch(`/api/threads/${threadId}`);
        if (!threadResponse.ok) throw new Error('Failed to load thread');
        const threadData = await threadResponse.json();
        const thread = threadData.thread || threadData;
        const currentLabelIds = (thread.labels || []).map(l => l.id);
        
        // Build label picker HTML
        const labelsHtml = availableLabels.map(label => `
            <li class="c-label-picker__item ${currentLabelIds.includes(label.id) ? 'is-selected' : ''}" 
                data-label-id="${label.id}">
                <div class="c-label-picker__checkbox">
                    <svg class="c-label-picker__checkbox-icon" width="12" height="12" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="c-label-picker__color" style="background-color: ${label.color}"></div>
                <div class="c-label-picker__name">${label.name}</div>
            </li>
        `).join('');
        
        const emptyState = availableLabels.length === 0 ? `
            <div class="c-label-picker__empty">
                <svg class="c-label-picker__empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                <p>Keine Labels vorhanden</p>
            </div>
        ` : '';
        
        showConfirmDialog({
            title: 'Labels verwalten',
            message: `
                <div class="c-label-picker">
                    <input type="text" class="c-label-picker__search" placeholder="Labels durchsuchen..." id="label-search">
                    ${emptyState || `<ul class="c-label-picker__list" id="label-list">${labelsHtml}</ul>`}
                </div>
            `,
            isHtml: true,
            confirmText: 'Speichern',
            cancelText: 'Abbrechen',
            onConfirm: async () => {
                const selectedIds = Array.from(document.querySelectorAll('.c-label-picker__item.is-selected'))
                    .map(item => parseInt(item.dataset.labelId));
                
                await updateThreadLabels(threadId, selectedIds);
            }
        });
        
        // Add click handlers for label items
        setTimeout(() => {
            document.querySelectorAll('.c-label-picker__item').forEach(item => {
                item.addEventListener('click', function() {
                    this.classList.toggle('is-selected');
                });
            });
            
            // Search functionality
            const searchInput = document.getElementById('label-search');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const query = this.value.toLowerCase();
                    document.querySelectorAll('.c-label-picker__item').forEach(item => {
                        const name = item.querySelector('.c-label-picker__name').textContent.toLowerCase();
                        item.style.display = name.includes(query) ? 'flex' : 'none';
                    });
                });
            }
        }, 50);
        
    } catch (error) {
        console.error('Error showing label picker:', error);
        alert('Fehler beim Laden der Labels: ' + error.message);
    }
}

/**
 * Update thread labels via API
 */
async function updateThreadLabels(threadId, labelIds) {
    try {
        const response = await fetch(`/api/threads/${threadId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                label_ids: labelIds
            })
        });
        
        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.error || 'Failed to update labels');
        }
        
        // Reload thread details to show new labels
        const activeThreadId = document.querySelector('.c-thread-item.is-active')?.dataset.threadId;
        if (activeThreadId == threadId) {
            loadThreadDetail(threadId);
        }
        
        // Update thread list item
        const threadItem = document.querySelector(`.c-thread-item[data-thread-id="${threadId}"]`);
        if (threadItem) {
            const result = await response.json();
            const labelsHtml = (result.labels || []).map(label => 
                `<span class="c-label-tag" style="--label-color: ${label.color}">${label.name}</span>`
            ).join('');
            
            let labelsContainer = threadItem.querySelector('.c-thread-item__labels');
            if (!labelsContainer) {
                labelsContainer = document.createElement('div');
                labelsContainer.className = 'c-thread-item__labels';
                threadItem.querySelector('.c-thread-item__content').appendChild(labelsContainer);
            }
            labelsContainer.innerHTML = labelsHtml;
        }
        
        console.log('Labels updated successfully');
        
    } catch (error) {
        console.error('Error updating labels:', error);
        alert('Fehler beim Aktualisieren der Labels: ' + error.message);
    }
}

/**
 * Bulk Label Picker - For multiple threads
 */
async function showBulkLabelPicker(threadIds) {
    try {
        // Load labels if not cached
        if (availableLabels.length === 0) {
            const response = await fetch('/api/labels');
            if (!response.ok) throw new Error('Failed to load labels');
            const data = await response.json();
            availableLabels = Array.isArray(data) ? data : (data.labels || []);
        }
        
        // Build label picker HTML (no pre-selection for bulk)
        const labelsHtml = availableLabels.map(label => `
            <li class="c-label-picker__item" data-label-id="${label.id}">
                <div class="c-label-picker__checkbox">
                    <svg class="c-label-picker__checkbox-icon" width="12" height="12" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="c-label-picker__color" style="background-color: ${label.color}"></div>
                <div class="c-label-picker__name">${label.name}</div>
            </li>
        `).join('');
        
        showConfirmDialog({
            title: `Labels für ${threadIds.length} Threads`,
            message: `
                <div class="c-label-picker">
                    <input type="text" class="c-label-picker__search" placeholder="Labels durchsuchen..." id="label-search">
                    <ul class="c-label-picker__list" id="label-list">${labelsHtml}</ul>
                </div>
            `,
            isHtml: true,
            confirmText: 'Hinzufügen',
            cancelText: 'Abbrechen',
            onConfirm: async () => {
                const selectedIds = Array.from(document.querySelectorAll('.c-label-picker__item.is-selected'))
                    .map(item => parseInt(item.dataset.labelId));
                
                if (selectedIds.length === 0) {
                    alert('Bitte wähle mindestens ein Label aus');
                    return;
                }
                
                await bulkAddLabels(threadIds, selectedIds);
            }
        });
        
        // Add click handlers
        setTimeout(() => {
            document.querySelectorAll('.c-label-picker__item').forEach(item => {
                item.addEventListener('click', function() {
                    this.classList.toggle('is-selected');
                });
            });
            
            // Search functionality
            const searchInput = document.getElementById('label-search');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const query = this.value.toLowerCase();
                    document.querySelectorAll('.c-label-picker__item').forEach(item => {
                        const name = item.querySelector('.c-label-picker__name').textContent.toLowerCase();
                        item.style.display = name.includes(query) ? 'flex' : 'none';
                    });
                });
            }
        }, 50);
        
    } catch (error) {
        console.error('Error showing bulk label picker:', error);
        alert('Fehler beim Laden der Labels: ' + error.message);
    }
}

/**
 * ============================================================================
 * ASSIGNMENT PICKER - User assignment to threads
 * ============================================================================
 */
let availableUsers = [];

/**
 * Show assignment picker (assign thread to user)
 */
async function showAssignmentPicker(threadId) {
    try {
        // Load users
        const response = await fetch('/api/users');
        if (!response.ok) throw new Error('Failed to load users');
        const data = await response.json();
        const users = Array.isArray(data) ? data : (data.users || []);
        
        // Get current thread assignments
        const threadResponse = await fetch(`/api/threads/${threadId}`);
        if (!threadResponse.ok) throw new Error('Failed to load thread');
        const threadData = await threadResponse.json();
        const thread = threadData.thread || threadData;
        const currentAssignedIds = (thread.assigned_users || []).map(u => u.id);
        
        console.log('[AssignmentPicker] Thread:', threadId);
        console.log('[AssignmentPicker] Thread data:', thread);
        console.log('[AssignmentPicker] Current assigned IDs:', currentAssignedIds);
        console.log('[AssignmentPicker] Available users:', users);
        
        // Build user picker HTML
        const usersHtml = users.map(user => {
            const name = user.name || user.username || user.email;
            let initials = name.charAt(0).toUpperCase();
            if (name.includes(' ')) {
                const parts = name.split(' ');
                initials = parts[0].charAt(0).toUpperCase() + parts[parts.length - 1].charAt(0).toUpperCase();
            }
            
            // Use avatar_color from database (with fallback)
            const colorNum = user.avatar_color || ((user.id % 8) + 1);
            const colorClass = `c-avatar--color-${colorNum}`;
            
            const isSelected = currentAssignedIds.includes(user.id);
            console.log(`[AssignmentPicker] User ${user.id} (${name}): isSelected=${isSelected}`);
            
            return `
            <li class="c-user-picker__item ${isSelected ? 'is-selected' : ''}" 
                data-user-id="${user.id}">
                <div class="c-user-picker__checkbox">
                    <svg class="c-user-picker__checkbox-icon" width="14" height="14" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="c-avatar c-avatar--sm ${colorClass}">
                    ${initials}
                </div>
                <div class="c-user-picker__info">
                    <div class="c-user-picker__name">${escapeHtml(name)}</div>
                    <div class="c-user-picker__email">${escapeHtml(user.email)}</div>
                </div>
            </li>
        `;
        }).join('');
        
        showConfirmDialog({
            title: 'Thread zuweisen',
            message: `
                <div class="c-user-picker">
                    ${users.length === 0 ? 
                        '<div class="c-user-picker__empty">Keine Benutzer verfügbar</div>' :
                        `<ul class="c-user-picker__list">${usersHtml}</ul>`
                    }
                </div>
            `,
            isHtml: true,
            confirmText: 'Speichern',
            cancelText: 'Abbrechen',
            onConfirm: async () => {
                const selectedItems = document.querySelectorAll('.c-user-picker__item.is-selected');
                console.log('[AssignmentPicker] Selected items on confirm:', selectedItems.length);
                selectedItems.forEach(item => {
                    console.log('[AssignmentPicker] Selected item:', item.dataset.userId, item);
                });
                
                const selectedIds = Array.from(selectedItems)
                    .map(item => parseInt(item.dataset.userId));
                
                console.log('[AssignmentPicker] Submitting user IDs:', selectedIds);
                await updateThreadAssignments(threadId, selectedIds);
            }
        });
        
        // Add click handlers - wait for modal to be in DOM
        setTimeout(() => {
            const userItems = document.querySelectorAll('.c-user-picker__item');
            console.log('[AssignmentPicker] Adding click handlers to', userItems.length, 'items');
            
            userItems.forEach(item => {
                console.log('[AssignmentPicker] Adding handler to item:', item.dataset.userId);
                item.addEventListener('click', function(e) {
                    console.log('[AssignmentPicker] Item clicked:', this.dataset.userId);
                    this.classList.toggle('is-selected');
                    console.log('[AssignmentPicker] Is now selected:', this.classList.contains('is-selected'));
                });
            });
        }, 100); // Increased timeout to ensure modal is rendered
        
    } catch (error) {
        console.error('Error showing assignment picker:', error);
        alert('Fehler beim Laden der Benutzer: ' + error.message);
    }
}

/**
 * Update thread assignments via API
 */
async function updateThreadAssignments(threadId, userIds) {
    console.log('[Assignment API] Starting request to /api/threads/' + threadId + '/assign');
    console.log('[Assignment API] Payload:', { user_ids: userIds });
    
    try {
        const response = await fetch(`/api/threads/${threadId}/assign`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ user_ids: userIds })
        });
        
        console.log('[Assignment API] Response status:', response.status);
        console.log('[Assignment API] Response OK:', response.ok);
        
        if (!response.ok) {
            const error = await response.json();
            console.error('[Assignment API] Error response:', error);
            throw new Error(error.error || 'Failed to update assignments');
        }
        
        const result = await response.json();
        console.log('[Assignment API] Success response:', result);
        const assignedUsers = result.assigned_users || [];
        
        console.log('[Assignment] Successfully updated, refreshing views...');
        
        // Refresh thread detail view (shows updated assigned users in toolbar)
        if (typeof loadThreadDetail === 'function') {
            await loadThreadDetail(threadId);
        }
        
        // Update thread list item (without full reload)
        const threadItem = document.querySelector(`.c-thread-item[data-thread-id="${threadId}"]`);
        if (threadItem) {
            // Build assigned users HTML
            const avatarsHtml = assignedUsers.map(user => {
                const name = user.name || user.email;
                let initials = name.charAt(0).toUpperCase();
                if (name.includes(' ')) {
                    const parts = name.split(' ');
                    initials = parts[0].charAt(0).toUpperCase() + parts[parts.length - 1].charAt(0).toUpperCase();
                }
                // Use avatar_color from database (with fallback)
                const colorNum = user.avatar_color || ((user.id % 8) + 1);
                const colorClass = `c-avatar--color-${colorNum}`;
                return `<div class="c-avatar c-avatar--xs ${colorClass}" title="${name}">${initials}</div>`;
            }).join('');
            
            // Find or create assigned container in thread list item
            let assignedContainer = threadItem.querySelector('.c-thread-item__assigned');
            if (assignedUsers.length > 0) {
                if (!assignedContainer) {
                    // Create container if doesn't exist
                    assignedContainer = document.createElement('div');
                    assignedContainer.className = 'c-thread-item__assigned';
                    threadItem.querySelector('.c-thread-item__meta').appendChild(assignedContainer);
                }
                assignedContainer.innerHTML = avatarsHtml;
            } else if (assignedContainer) {
                // Remove container if no assignments
                assignedContainer.remove();
            }
        }
        
    } catch (error) {
        console.error('[Assignment API] Caught error:', error);
        console.error('[Assignment API] Error stack:', error.stack);
        alert('Fehler beim Zuweisen: ' + error.message);
    }
}

/**
 * Trim email content (remove signatures and quoted text)
 * Simple implementation - can be improved
 */
function trimEmailContent(content) {
    if (!content) return '';
    
    // Common separators
    const separators = [
        '<div class="gmail_quote">',
        '<div class="moz-cite-prefix">',
        '-----Original Message-----',
        '________________________________',
        '<hr id="zwchr">'
    ];
    
    // Find the first occurrence of any separator
    let minIndex = content.length;
    let found = false;
    
    separators.forEach(sep => {
        const index = content.indexOf(sep);
        if (index !== -1 && index < minIndex) {
            minIndex = index;
            found = true;
        }
    });
    
    if (found) {
        return content.substring(0, minIndex);
    }
    
    return content;
}

// Initialize features when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (typeof initContextMenu === 'function') initContextMenu();
    if (typeof initThreadMultiSelect === 'function') initThreadMultiSelect();
    if (typeof initAutoReadEmails === 'function') initAutoReadEmails();
});

// Export functions
window.ThreadDetailRenderer = {
    render: renderThreadDetail,
    autoResizeIframe: autoResizeIframe
};

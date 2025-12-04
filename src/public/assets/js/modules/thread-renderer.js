/**
 * Thread Renderer Module
 * 
 * Generiert HTML für Thread-Detailansicht, E-Mails, Notizen und Anhänge.
 * Reine Rendering-Logik ohne Seiteneffekte.
 * 
 * @module ThreadRenderer
 * @since 2025-11-28 (Refactoring)
 */

const ThreadRenderer = (function() {
    'use strict';
    
    // ============================================================================
    // HELPER FUNCTIONS
    // ============================================================================
    
    /**
     * Escape HTML to prevent XSS
     * @param {string} text - Text to escape
     * @returns {string} Escaped text
     */
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Trim email content (remove signatures and quoted text)
     * @param {string} content - Email content
     * @returns {string} Trimmed content
     */
    function trimEmailContent(content) {
        if (!content) return '';
        
        const separators = [
            '<div class="gmail_quote">',
            '<div class="moz-cite-prefix">',
            '-----Original Message-----',
            '________________________________',
            '<hr id="zwchr">'
        ];
        
        let minIndex = content.length;
        let found = false;
        
        separators.forEach(sep => {
            const index = content.indexOf(sep);
            if (index !== -1 && index < minIndex) {
                minIndex = index;
                found = true;
            }
        });
        
        return found ? content.substring(0, minIndex) : content;
    }
    
    /**
     * Auto-resize iframe to content height
     * @param {HTMLIFrameElement} iframe - Iframe element
     */
    function autoResizeIframe(iframe) {
        if (!iframe || !iframe.contentWindow) return;
        
        try {
            const doc = iframe.contentWindow.document;
            iframe.style.height = doc.body.scrollHeight + 'px';
        } catch (e) {
            console.warn('Could not resize iframe:', e);
        }
    }
    
    // ============================================================================
    // STATUS CONFIGURATION
    // ============================================================================
    
    const STATUS_CONFIG = {
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
    
    // ============================================================================
    // MAIN RENDER FUNCTIONS
    // ============================================================================
    
    /**
     * Render thread detail HTML from API response
     * @param {object} data - API response data
     * @returns {string} HTML string
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
        
        const totalEmails = items.filter(i => i.type === 'email').length;
        let currentEmailIndex = 0;
        
        items.forEach((item, index) => {
            if (item.type === 'email') {
                html += renderEmailMessage(item, currentEmailIndex, totalEmails);
                currentEmailIndex++;
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
     * @param {object} thread - Thread data
     * @param {object[]} labels - Thread labels
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
        const status = STATUS_CONFIG[thread.status] || STATUS_CONFIG['open'];
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
        
        // Update actions
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
    
    // ============================================================================
    // EMAIL RENDERING
    // ============================================================================
    
    /**
     * Render single email message
     * @param {object} email - Email data
     * @param {number} index - Email index
     * @param {number} totalEmails - Total emails in thread
     * @returns {string} HTML string
     */
    function renderEmailMessage(email, index, totalEmails) {
        const initials = (email.from_name || email.from_email).charAt(0).toUpperCase();
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
     * @param {object[]} attachments - Array of attachments
     * @returns {string} HTML string
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
    
    // ============================================================================
    // NOTE RENDERING
    // ============================================================================
    
    /**
     * Render a single note item
     * @param {object} note - Note data
     * @returns {string} HTML string
     */
    function renderNoteItem(note) {
        let timeDisplay = '';
        if (note.was_edited) {
            const editorName = note.updated_by_name || 'Unbekannt';
            timeDisplay = `<span class="c-note__edited" title="Bearbeitet von ${escapeHtml(editorName)}">bearbeitet</span> <time datetime="${note.updated_at}">${note.updated_at_human || note.created_at_human}</time>`;
        } else {
            timeDisplay = `<time datetime="${note.created_at}">${note.created_at_human}</time>`;
        }
        
        return `
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
    }
    
    /**
     * Render labels
     * @param {object[]} labels - Array of labels
     * @returns {string} HTML string
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
    
    // ============================================================================
    // PUBLIC API
    // ============================================================================
    
    return {
        // Main render functions
        render: renderThreadDetail,
        renderThreadDetail,
        updateToolbarDetail,
        
        // Email rendering
        renderEmailMessage,
        renderAttachments,
        
        // Note rendering
        renderNoteItem,
        renderLabels,
        
        // Helpers
        escapeHtml,
        trimEmailContent,
        autoResizeIframe,
        
        // Config
        STATUS_CONFIG
    };
})();

// Make globally available
window.ThreadRenderer = ThreadRenderer;

// Backwards compatibility
window.ThreadDetailRenderer = {
    render: ThreadRenderer.renderThreadDetail,
    autoResizeIframe: ThreadRenderer.autoResizeIframe
};
window.renderThreadDetail = ThreadRenderer.renderThreadDetail;

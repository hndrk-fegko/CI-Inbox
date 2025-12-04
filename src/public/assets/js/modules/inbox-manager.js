/**
 * Inbox Manager Module
 * 
 * Zentrale Steuerung für die Inbox-Ansicht.
 * Event-Handler, Initialisierung, Thread-Aktionen.
 * 
 * @module InboxManager
 * @since 2025-11-28 (Refactoring)
 */

const InboxManager = (function() {
    'use strict';
    
    // ============================================================================
    // STATE
    // ============================================================================
    
    let contextMenuInitialized = false;
    let dropdownInitialized = false;
    let autoReadTimers = new Map();
    
    // ============================================================================
    // HELPER FUNCTIONS
    // ============================================================================
    
    /**
     * Get current thread ID from active thread, selected thread, or detail view
     * @returns {number|null} Thread ID or null
     */
    function getCurrentThreadId() {
        // Priority 1: Detail view with thread ID
        const detailView = document.querySelector('.c-thread-detail[data-thread-id]');
        if (detailView) {
            return parseInt(detailView.dataset.threadId);
        }
        
        // Priority 2: Active thread in list
        const activeThread = document.querySelector('.c-thread-item.is-active');
        if (activeThread) {
            return parseInt(activeThread.dataset.threadId);
        }
        
        // Priority 3: Selected thread (e.g., from context menu right-click)
        const selectedThread = document.querySelector('.c-thread-item.is-selected');
        if (selectedThread) {
            return parseInt(selectedThread.dataset.threadId);
        }
        
        return null;
    }
    
    // ============================================================================
    // THREAD ACTIONS
    // ============================================================================
    
    /**
     * Handle single thread actions with explicit threadId
     * Used by context menu when we already know the thread ID
     * @param {string} action - Action name
     * @param {number} threadId - Thread ID
     * @param {HTMLElement} element - Clicked element
     */
    function handleThreadActionWithId(action, threadId, element) {
        console.log('[InboxManager] ActionWithId:', action, 'ThreadId:', threadId);
        
        if (!threadId) {
            console.error('No thread ID provided for action:', action);
            return;
        }
        
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
                UiComponents.showLabelPicker(threadId);
                break;
            case 'assign':
                UiComponents.showAssignmentPicker(threadId);
                break;
            case 'status':
                UiComponents.showStatusPicker(threadId);
                break;
            case 'move':
                UiComponents.showErrorMessage('Verschieben - noch nicht implementiert');
                break;
            default:
                console.warn('Unknown action:', action);
        }
    }
    
    /**
     * Handle single thread actions
     * @param {string} action - Action name
     * @param {HTMLElement} element - Clicked element
     */
    function handleThreadAction(action, element) {
        const threadId = getCurrentThreadId();
        
        console.log('[InboxManager] Action:', action, 'ThreadId:', threadId);
        
        if (!threadId) {
            console.error('No thread ID found for action:', action);
            return;
        }
        
        // Delegate to the version with explicit ID
        handleThreadActionWithId(action, threadId, element);
    }
    
    /**
     * Mark thread as read
     * @param {number} threadId - Thread ID
     */
    async function markThreadAsRead(threadId) {
        try {
            await ApiClient.markThreadRead(threadId);
            
            const threadItem = document.querySelector(`.c-thread-item[data-thread-id="${threadId}"]`);
            if (threadItem) {
                threadItem.classList.remove('is-unread');
            }
            
            UiComponents.showSuccessMessage('Thread als gelesen markiert');
        } catch (error) {
            console.error('Error marking thread as read:', error);
            UiComponents.showErrorMessage('Fehler: ' + error.message);
        }
    }
    
    /**
     * Mark thread as unread
     * @param {number} threadId - Thread ID
     */
    async function markThreadAsUnread(threadId) {
        try {
            await ApiClient.markThreadUnread(threadId);
            
            const threadItem = document.querySelector(`.c-thread-item[data-thread-id="${threadId}"]`);
            if (threadItem) {
                threadItem.classList.add('is-unread');
            }
            
            UiComponents.showSuccessMessage('Thread als ungelesen markiert');
        } catch (error) {
            console.error('Error marking thread as unread:', error);
            UiComponents.showErrorMessage('Fehler: ' + error.message);
        }
    }
    
    /**
     * Archive thread
     * @param {number} threadId - Thread ID
     */
    async function archiveThread(threadId) {
        try {
            await ApiClient.updateThreadStatus(threadId, 'archived');
            
            const threadItem = document.querySelector(`.c-thread-item[data-thread-id="${threadId}"]`);
            if (threadItem) {
                threadItem.style.transition = 'opacity 0.3s ease-out';
                threadItem.style.opacity = '0';
                setTimeout(() => {
                    threadItem.remove();
                    
                    if (threadItem.classList.contains('is-active')) {
                        clearDetailView();
                    }
                }, 300);
            }
            
            UiComponents.showSuccessMessage('Thread archiviert');
        } catch (error) {
            console.error('Error archiving thread:', error);
            UiComponents.showErrorMessage('Fehler: ' + error.message);
        }
    }
    
    /**
     * Confirm and delete thread
     * @param {number} threadId - Thread ID
     */
    function confirmDeleteThread(threadId) {
        const threadItem = document.querySelector(`.c-thread-item[data-thread-id="${threadId}"]`);
        let subject = 'diesem Thread';
        if (threadItem) {
            const subjectEl = threadItem.querySelector('.c-thread-item__subject');
            if (subjectEl) {
                subject = subjectEl.textContent || subject;
            }
        }
        
        UiComponents.showConfirmDialog({
            title: 'Thread löschen?',
            message: 'Möchten Sie den Thread wirklich löschen?',
            details: subject,
            confirmText: 'Löschen',
            cancelText: 'Abbrechen',
            danger: true,
            onConfirm: () => deleteThread(threadId)
        });
    }
    
    /**
     * Delete thread
     * @param {number} threadId - Thread ID
     */
    async function deleteThread(threadId) {
        try {
            await ApiClient.deleteThread(threadId);
            
            const threadItem = document.querySelector(`.c-thread-item[data-thread-id="${threadId}"]`);
            if (threadItem) {
                const wasActive = threadItem.classList.contains('is-active');
                
                threadItem.style.transition = 'opacity 0.3s ease-out';
                threadItem.style.opacity = '0';
                setTimeout(() => {
                    threadItem.remove();
                    
                    if (wasActive) {
                        const nextThread = document.querySelector('.c-thread-item');
                        if (nextThread) {
                            nextThread.click();
                        } else {
                            clearDetailView();
                        }
                    }
                }, 300);
            }
            
            UiComponents.showSuccessMessage('Thread gelöscht');
        } catch (error) {
            console.error('Error deleting thread:', error);
            UiComponents.showErrorMessage('Fehler: ' + error.message);
        }
    }
    
    /**
     * Clear detail view
     */
    function clearDetailView() {
        const detailTitle = document.getElementById('detail-title');
        const detailActions = document.getElementById('detail-actions');
        const detailContent = document.querySelector('.c-inbox__thread-detail');
        
        if (detailTitle) detailTitle.textContent = 'Wähle einen Thread';
        if (detailActions) detailActions.style.display = 'none';
        if (detailContent) detailContent.innerHTML = '';
    }
    
    // ============================================================================
    // EMAIL ACTIONS
    // ============================================================================
    
    /**
     * Reply to thread
     * @param {number} threadId - Thread ID
     */
    function replyToThread(threadId) {
        if (typeof showEmailComposer === 'function') {
            showEmailComposer('reply', { threadId });
        } else {
            console.error('Email composer not loaded');
            UiComponents.showErrorMessage('E-Mail Composer nicht geladen');
        }
    }
    
    /**
     * Private reply to thread
     * @param {number} threadId - Thread ID
     */
    function privateReplyToThread(threadId) {
        if (typeof showEmailComposer === 'function') {
            showEmailComposer('reply-private', { threadId });
        } else {
            console.error('Email composer not loaded');
            UiComponents.showErrorMessage('E-Mail Composer nicht geladen');
        }
    }
    
    /**
     * Forward thread
     * @param {number} threadId - Thread ID
     */
    function forwardThread(threadId) {
        if (typeof showEmailComposer === 'function') {
            showEmailComposer('forward', { threadId });
        } else {
            console.error('Email composer not loaded');
            UiComponents.showErrorMessage('E-Mail Composer nicht geladen');
        }
    }
    
    // ============================================================================
    // BULK ACTIONS
    // ============================================================================
    
    /**
     * Handle bulk actions on multiple threads
     * @param {string} action - Action name
     * @param {number[]} threadIds - Array of thread IDs
     * @param {HTMLElement[]} threadElements - Array of thread DOM elements
     */
    function handleBulkAction(action, threadIds, threadElements) {
        if (!threadIds || threadIds.length === 0) {
            console.warn('No threads selected for bulk action');
            return;
        }
        
        console.log('[InboxManager] Bulk action:', action, 'on', threadIds.length, 'threads');
        
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
                UiComponents.showBulkLabelPicker(threadIds);
                break;
            case 'status':
                UiComponents.showBulkStatusPicker(threadIds, threadElements);
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
            await ApiClient.bulkUpdateStatus(threadIds, { is_read: true });
            
            threadElements.forEach(threadItem => {
                threadItem.classList.remove('is-unread');
                threadItem.classList.remove('is-selected');
            });
            
            UiComponents.showSuccessMessage(`${threadIds.length} Threads als gelesen markiert`);
        } catch (error) {
            console.error('Error in bulk mark as read:', error);
            UiComponents.showErrorMessage('Fehler: ' + error.message);
        }
    }
    
    /**
     * Bulk mark threads as unread
     */
    async function bulkMarkAsUnread(threadIds, threadElements) {
        try {
            await ApiClient.bulkUpdateStatus(threadIds, { is_read: false });
            
            threadElements.forEach(threadItem => {
                threadItem.classList.add('is-unread');
                threadItem.classList.remove('is-selected');
            });
            
            UiComponents.showSuccessMessage(`${threadIds.length} Threads als ungelesen markiert`);
        } catch (error) {
            console.error('Error in bulk mark as unread:', error);
            UiComponents.showErrorMessage('Fehler: ' + error.message);
        }
    }
    
    /**
     * Bulk archive threads
     */
    async function bulkArchive(threadIds, threadElements) {
        try {
            await ApiClient.bulkUpdateStatus(threadIds, { status: 'archived' });
            
            threadElements.forEach((threadItem, index) => {
                setTimeout(() => {
                    threadItem.style.transition = 'opacity 0.3s ease-out';
                    threadItem.style.opacity = '0';
                    setTimeout(() => threadItem.remove(), 300);
                }, index * 50);
            });
            
            const activeThread = threadElements.find(t => t.classList.contains('is-active'));
            if (activeThread) {
                setTimeout(() => {
                    const nextThread = document.querySelector('.c-thread-item');
                    if (nextThread) {
                        nextThread.click();
                    } else {
                        clearDetailView();
                    }
                }, threadElements.length * 50 + 300);
            }
            
            UiComponents.showSuccessMessage(`${threadIds.length} Threads archiviert`);
        } catch (error) {
            console.error('Error in bulk archive:', error);
            UiComponents.showErrorMessage('Fehler: ' + error.message);
        }
    }
    
    /**
     * Confirm bulk delete
     */
    function confirmBulkDelete(threadIds, threadElements) {
        const count = threadIds.length;
        const message = count === 1 
            ? 'Möchten Sie diesen Thread wirklich löschen?'
            : `Möchten Sie ${count} Threads wirklich löschen?`;
        
        UiComponents.showConfirmDialog({
            title: 'Threads löschen?',
            message: message,
            details: 'Diese Aktion kann nicht rückgängig gemacht werden.',
            confirmText: count === 1 ? 'Löschen' : `Alle ${count} löschen`,
            cancelText: 'Abbrechen',
            danger: true,
            onConfirm: () => bulkDelete(threadIds, threadElements)
        });
    }
    
    /**
     * Bulk delete threads
     */
    async function bulkDelete(threadIds, threadElements) {
        try {
            await ApiClient.bulkDelete(threadIds);
            
            threadElements.forEach((threadItem, index) => {
                setTimeout(() => {
                    threadItem.style.transition = 'opacity 0.3s ease-out';
                    threadItem.style.opacity = '0';
                    setTimeout(() => threadItem.remove(), 300);
                }, index * 50);
            });
            
            const activeThread = threadElements.find(t => t.classList.contains('is-active'));
            if (activeThread) {
                setTimeout(() => {
                    const nextThread = document.querySelector('.c-thread-item');
                    if (nextThread) {
                        nextThread.click();
                    } else {
                        clearDetailView();
                    }
                }, threadElements.length * 50 + 300);
            }
            
            UiComponents.showSuccessMessage(`${threadIds.length} Threads gelöscht`);
        } catch (error) {
            console.error('Error in bulk delete:', error);
            UiComponents.showErrorMessage('Fehler: ' + error.message);
        }
    }
    
    // ============================================================================
    // NOTE ACTIONS
    // ============================================================================
    
    /**
     * Save note to thread
     * @param {number} threadId - Thread ID
     * @param {string} content - Note content
     * @param {number} position - Position
     */
    async function saveNote(threadId, content, position) {
        try {
            const result = await ApiClient.addNote(threadId, content, position);
            console.log('Note saved:', result);
            return result;
        } catch (error) {
            console.error('Error saving note:', error);
            throw error;
        }
    }
    
    /**
     * Update note
     * @param {number} threadId - Thread ID
     * @param {number} noteId - Note ID
     * @param {string} content - New content
     */
    async function updateNote(threadId, noteId, content) {
        try {
            const result = await ApiClient.updateNote(threadId, noteId, content);
            console.log('Note updated:', result);
            return result;
        } catch (error) {
            console.error('Error updating note:', error);
            throw error;
        }
    }
    
    /**
     * Delete note
     * @param {number} threadId - Thread ID
     * @param {number} noteId - Note ID
     */
    async function deleteNote(threadId, noteId) {
        try {
            const result = await ApiClient.deleteNote(threadId, noteId);
            console.log('Note deleted:', result);
            return result;
        } catch (error) {
            console.error('Error deleting note:', error);
            throw error;
        }
    }
    
    // ============================================================================
    // INITIALIZATION FUNCTIONS
    // ============================================================================
    
    /**
     * Initialize context menu for bulk operations
     */
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
            
            console.log('[ContextMenu] Action:', action, 'on threads:', threadIds);
            
            // For single thread: use handleThreadAction with explicit threadId
            // For multiple threads: use handleBulkAction
            if (threadIds.length === 1) {
                handleThreadActionWithId(action, threadIds[0], item);
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
     * Initialize dropdowns
     */
    function initDropdowns() {
        if (dropdownInitialized) return;
        dropdownInitialized = true;
        
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
                e.preventDefault();
                e.stopPropagation();
                
                const parentDropdown = trigger.closest('.c-dropdown');
                
                // Close all other dropdowns first
                document.querySelectorAll('.c-dropdown--open').forEach(d => {
                    if (d !== parentDropdown) {
                        d.classList.remove('c-dropdown--open');
                    }
                });
                
                // Toggle this dropdown
                parentDropdown.classList.toggle('c-dropdown--open');
                return;
            }
            
            // Click on dropdown item
            if (e.target.closest('.c-dropdown__item')) {
                const item = e.target.closest('.c-dropdown__item');
                
                if (item.disabled) {
                    e.preventDefault();
                    return;
                }
                
                const action = item.dataset.action;
                if (action) {
                    handleThreadAction(action, item);
                }
                
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
     * Initialize email message collapse functionality
     */
    function initEmailCollapse() {
        document.addEventListener('click', function(e) {
            const toggle = e.target.closest('.c-email-message__toggle');
            const header = e.target.closest('.c-email-message__header');
            
            if (toggle || (header && header.closest('.c-email-message').classList.contains('is-collapsed'))) {
                e.preventDefault();
                const emailMessage = (toggle || header).closest('.c-email-message');
                emailMessage.classList.toggle('is-collapsed');
                
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
                if (!threadDetail) return;
                
                const isActive = threadDetail.classList.contains('note-mode-active');
                threadDetail.classList.toggle('note-mode-active');
                
                const button = e.target.closest('#toggle-note-mode');
                button.textContent = isActive ? 'Notiz' : 'Abbrechen';
                
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
                
                if (!content) {
                    UiComponents.showErrorMessage('Bitte gib einen Notiztext ein.');
                    return;
                }
                
                const threadId = getCurrentThreadId();
                if (!threadId) {
                    UiComponents.showErrorMessage('Thread ID nicht gefunden.');
                    return;
                }
                
                button.disabled = true;
                button.textContent = 'Speichert...';
                
                saveNote(threadId, content, position)
                    .then(() => {
                        if (typeof loadThreadDetail === 'function') {
                            loadThreadDetail(threadId);
                        }
                    })
                    .catch(error => {
                        UiComponents.showErrorMessage('Fehler beim Speichern: ' + error.message);
                        button.disabled = false;
                        button.textContent = 'Speichern';
                    });
                return;
            }
            
            // Cancel note
            if (e.target.closest('[data-action="cancel-note"]')) {
                e.preventDefault();
                const form = e.target.closest('.c-note-inline-form');
                
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
                    UiComponents.showErrorMessage('Thread ID nicht gefunden.');
                    return;
                }
                
                deleteNote(threadId, noteId)
                    .then(() => {
                        if (typeof loadThreadDetail === 'function') {
                            loadThreadDetail(threadId);
                        }
                    })
                    .catch(error => {
                        UiComponents.showErrorMessage('Fehler: ' + error.message);
                    });
                return;
            }
            
            // Edit note
            if (e.target.closest('[data-action="edit-note"]')) {
                e.preventDefault();
                const button = e.target.closest('[data-action="edit-note"]');
                const noteId = button.dataset.noteId;
                const noteElement = button.closest('.c-note');
                
                // Decode HTML entities from data attribute
                let currentContent = noteElement.dataset.noteContent || '';
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = currentContent;
                currentContent = tempDiv.textContent || tempDiv.innerText || '';
                
                const contentElement = noteElement.querySelector('.c-note__content');
                
                const editForm = document.createElement('div');
                editForm.className = 'c-note-form c-note-form--edit';
                editForm.innerHTML = `
                    <textarea class="c-note-form__input" rows="3" required>${ThreadRenderer.escapeHtml(currentContent)}</textarea>
                    <div style="display: flex; gap: var(--spacing-2);">
                        <button type="button" class="c-button c-button--primary c-button--sm" data-action="save-note-edit" data-note-id="${noteId}">Speichern</button>
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
                    UiComponents.showErrorMessage('Notiz darf nicht leer sein.');
                    return;
                }
                
                const noteElement = editForm.closest('.c-note');
                const noteId = button.dataset.noteId;
                const threadId = getCurrentThreadId();
                
                if (!threadId) {
                    UiComponents.showErrorMessage('Thread ID nicht gefunden.');
                    return;
                }
                
                updateNote(threadId, noteId, newContent)
                    .then(() => {
                        if (typeof loadThreadDetail === 'function') {
                            loadThreadDetail(threadId);
                        }
                    })
                    .catch(error => {
                        UiComponents.showErrorMessage('Fehler: ' + error.message);
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
                    if (contentDiv) contentDiv.style.display = 'none';
                    fullContentDiv.style.display = 'block';
                    button.textContent = 'Gekürzte Nachricht anzeigen';
                } else {
                    if (contentDiv) contentDiv.style.display = 'block';
                    fullContentDiv.style.display = 'none';
                    button.textContent = 'Vollständige Nachricht anzeigen';
                }
            }
        });
    }
    
    /**
     * Initialize auto-read functionality for emails
     */
    function initAutoReadEmails() {
        // Feature detection for IntersectionObserver (supported in FF 55+, Chrome 51+)
        if (!('IntersectionObserver' in window)) {
            console.warn('[InboxManager] IntersectionObserver not supported - auto-read disabled');
            return;
        }
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                const emailEl = entry.target;
                const emailId = parseInt(emailEl.dataset.emailId);
                
                if (entry.isIntersecting && emailEl.classList.contains('is-unread')) {
                    if (!autoReadTimers.has(emailId)) {
                        const timer = setTimeout(() => {
                            markEmailAsRead(emailId, emailEl);
                        }, 5000);
                        
                        autoReadTimers.set(emailId, timer);
                        console.log(`Auto-read timer started for email ${emailId}`);
                    }
                } else {
                    if (autoReadTimers.has(emailId)) {
                        clearTimeout(autoReadTimers.get(emailId));
                        autoReadTimers.delete(emailId);
                        console.log(`Auto-read timer cancelled for email ${emailId}`);
                    }
                }
            });
        }, {
            threshold: 0.5
        });
        
        const observeUnreadEmails = () => {
            document.querySelectorAll('.c-email-message.is-unread').forEach(emailEl => {
                observer.observe(emailEl);
            });
        };
        
        observeUnreadEmails();
        
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
     * @param {number} emailId - Email ID
     * @param {HTMLElement} emailElement - Email DOM element
     */
    async function markEmailAsRead(emailId, emailElement) {
        if (!emailElement) {
            console.error('[InboxManager] markEmailAsRead called without emailElement');
            return;
        }
        
        try {
            // Find thread ID from parent or active thread
            const parentWithId = emailElement.closest('[data-thread-id]');
            const activeThread = document.querySelector('.c-thread-item.is-active');
            const threadId = (parentWithId && parentWithId.dataset.threadId) ||
                            (activeThread && activeThread.dataset.threadId);
            
            if (!threadId) {
                console.error('Could not determine thread ID for email', emailId);
                return;
            }
            
            console.log(`Marking email ${emailId} as read...`);
            
            await ApiClient.markEmailRead(emailId);
            
            emailElement.classList.remove('is-unread');
            const badge = emailElement.querySelector('.c-badge');
            if (badge) {
                badge.remove();
            }
            
            console.log(`Email ${emailId} marked as read`);
            
            const remainingUnread = document.querySelectorAll('.c-email-message.is-unread').length;
            if (remainingUnread === 0) {
                await ApiClient.markThreadRead(parseInt(threadId));
                
                const threadItem = document.querySelector(`.c-thread-item[data-thread-id="${threadId}"]`);
                if (threadItem) {
                    threadItem.classList.remove('is-unread');
                }
            }
            
            if (autoReadTimers.has(emailId)) {
                clearTimeout(autoReadTimers.get(emailId));
                autoReadTimers.delete(emailId);
            }
            
        } catch (error) {
            console.error('Error marking email as read:', error);
        }
    }
    
    /**
     * Initialize all inbox functionality
     */
    function init() {
        console.log('[InboxManager] Initializing...');
        
        initContextMenu();
        initThreadMultiSelect();
        initDropdowns();
        initEmailCollapse();
        initNoteMode();
        initAutoReadEmails();
        
        console.log('[InboxManager] Initialization complete');
    }
    
    // ============================================================================
    // PUBLIC API
    // ============================================================================
    
    return {
        // Initialization
        init,
        initContextMenu,
        initThreadMultiSelect,
        initDropdowns,
        initEmailCollapse,
        initNoteMode,
        initAutoReadEmails,
        
        // Actions
        handleThreadAction,
        handleThreadActionWithId,
        handleBulkAction,
        
        // Thread actions
        markThreadAsRead,
        markThreadAsUnread,
        archiveThread,
        deleteThread,
        
        // Note actions
        saveNote,
        updateNote,
        deleteNote,
        
        // Helpers
        getCurrentThreadId,
        clearDetailView
    };
})();

// Make globally available
window.InboxManager = InboxManager;

// Backwards compatibility
window.getCurrentThreadId = InboxManager.getCurrentThreadId;
window.saveNote = InboxManager.saveNote;
window.updateNote = InboxManager.updateNote;
window.deleteNote = InboxManager.deleteNote;
window.initContextMenu = InboxManager.initContextMenu;
window.initThreadMultiSelect = InboxManager.initThreadMultiSelect;
window.initAutoReadEmails = InboxManager.initAutoReadEmails;

// Auto-initialize on DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    InboxManager.init();
});

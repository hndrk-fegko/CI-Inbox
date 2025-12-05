/**
 * Keyboard Shortcuts Module
 * 
 * Provides comprehensive keyboard navigation for CI-Inbox.
 * Inspired by Gmail/Superhuman keyboard shortcuts.
 * 
 * @module KeyboardShortcuts
 */

const KeyboardShortcuts = (function() {
    'use strict';

    // State
    let enabled = true;
    let helpModalOpen = false;
    let currentThreadIndex = -1;
    let listeners = [];

    // Configuration
    const config = {
        // Enable/disable specific shortcut groups
        navigation: true,
        actions: true,
        compose: true,
        
        // Customizable shortcuts (can be overridden)
        shortcuts: {
            // Navigation
            nextThread: ['j', 'ArrowDown'],
            prevThread: ['k', 'ArrowUp'],
            openThread: ['Enter', 'o'],
            closeThread: ['Escape', 'u'],
            
            // Actions on current thread
            reply: ['r'],
            replyAll: ['a'],
            forward: ['f'],
            archive: ['e'],
            delete: ['#', 'Delete'],
            markRead: ['Shift+I'],
            markUnread: ['Shift+U'],
            star: ['s'],
            label: ['l'],
            assign: ['g'],
            
            // Compose
            compose: ['c'],
            send: ['Ctrl+Enter', 'Cmd+Enter'],
            discard: ['Escape'],
            
            // Global
            search: ['/', 'Ctrl+E', 'Cmd+E'],
            refresh: ['Shift+N'],
            help: ['?', 'Shift+/'],
            selectAll: ['Ctrl+A', 'Cmd+A'],
            
            // Status shortcuts
            statusOpen: ['1'],
            statusAssigned: ['2'],
            statusClosed: ['3'],
        }
    };

    // Shortcut definitions for help modal
    const shortcutGroups = [
        {
            name: 'Navigation',
            icon: '🧭',
            shortcuts: [
                { keys: ['j', '↓'], description: 'Nächster Thread' },
                { keys: ['k', '↑'], description: 'Vorheriger Thread' },
                { keys: ['Enter', 'o'], description: 'Thread öffnen' },
                { keys: ['Esc', 'u'], description: 'Zurück zur Liste' },
                { keys: ['g i'], description: 'Zum Posteingang' },
                { keys: ['g a'], description: 'Zum Archiv' },
            ]
        },
        {
            name: 'Aktionen',
            icon: '⚡',
            shortcuts: [
                { keys: ['r'], description: 'Antworten' },
                { keys: ['a'], description: 'Allen antworten' },
                { keys: ['f'], description: 'Weiterleiten' },
                { keys: ['e'], description: 'Archivieren' },
                { keys: ['#'], description: 'Löschen' },
                { keys: ['l'], description: 'Labels verwalten' },
                { keys: ['g'], description: 'Zuweisen' },
            ]
        },
        {
            name: 'Status',
            icon: '📊',
            shortcuts: [
                { keys: ['1'], description: 'Status: Offen' },
                { keys: ['2'], description: 'Status: In Arbeit' },
                { keys: ['3'], description: 'Status: Erledigt' },
                { keys: ['s'], description: 'Favorisieren' },
            ]
        },
        {
            name: 'Verfassen',
            icon: '✏️',
            shortcuts: [
                { keys: ['c'], description: 'Neue E-Mail' },
                { keys: ['Ctrl+Enter'], description: 'Senden' },
                { keys: ['Esc'], description: 'Verwerfen' },
            ]
        },
        {
            name: 'Global',
            icon: '🌐',
            shortcuts: [
                { keys: ['/', 'Ctrl+E'], description: 'Suche fokussieren' },
                { keys: ['Shift+N'], description: 'Aktualisieren' },
                { keys: ['?'], description: 'Diese Hilfe' },
            ]
        }
    ];

    /**
     * Initialize keyboard shortcuts
     */
    function init(options = {}) {
        // Merge options
        Object.assign(config, options);
        
        // Main keyboard listener
        document.addEventListener('keydown', handleKeyDown);
        
        // Create help modal
        createHelpModal();
        
        console.log('[KeyboardShortcuts] Initialized');
    }

    /**
     * Handle keydown events
     */
    function handleKeyDown(e) {
        if (!enabled) return;
        
        // Ignore when typing in inputs (except for specific shortcuts)
        const target = e.target;
        const isInput = target.tagName === 'INPUT' || 
                       target.tagName === 'TEXTAREA' || 
                       target.isContentEditable;
        
        // Global shortcuts that work even in inputs
        if (e.key === 'Escape') {
            if (helpModalOpen) {
                closeHelpModal();
                e.preventDefault();
                return;
            }
            
            // Close any open modal/composer
            const openModal = document.querySelector('.c-modal.is-open');
            if (openModal) {
                openModal.classList.remove('is-open');
                e.preventDefault();
                return;
            }
            
            // Exit input focus
            if (isInput) {
                target.blur();
                e.preventDefault();
                return;
            }
            
            // Close thread detail
            closeThreadDetail();
            e.preventDefault();
            return;
        }
        
        // Ctrl/Cmd shortcuts work in inputs
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            // Send email if composer is open
            const sendBtn = document.querySelector('.c-composer__send:not(:disabled)');
            if (sendBtn) {
                sendBtn.click();
                e.preventDefault();
                return;
            }
        }
        
        // Search shortcut (Ctrl+E / Cmd+E)
        if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
            focusSearch();
            e.preventDefault();
            return;
        }
        
        // Don't process other shortcuts when in inputs
        if (isInput) return;
        
        // Build key combo string
        const keyCombo = buildKeyCombo(e);
        
        // Process shortcuts
        switch (keyCombo) {
            // Navigation
            case 'j':
            case 'ArrowDown':
                selectNextThread();
                e.preventDefault();
                break;
                
            case 'k':
            case 'ArrowUp':
                selectPrevThread();
                e.preventDefault();
                break;
                
            case 'Enter':
            case 'o':
                openSelectedThread();
                e.preventDefault();
                break;
                
            case 'u':
                closeThreadDetail();
                e.preventDefault();
                break;
            
            // Actions
            case 'r':
                replyToThread();
                e.preventDefault();
                break;
                
            case 'a':
                replyAllToThread();
                e.preventDefault();
                break;
                
            case 'f':
                forwardThread();
                e.preventDefault();
                break;
                
            case 'e':
                archiveThread();
                e.preventDefault();
                break;
                
            case '#':
            case 'Delete':
                deleteThread();
                e.preventDefault();
                break;
                
            case 'l':
                openLabelPicker();
                e.preventDefault();
                break;
                
            case 'g':
                openAssignmentPicker();
                e.preventDefault();
                break;
            
            // Status
            case '1':
                setThreadStatus('open');
                e.preventDefault();
                break;
                
            case '2':
                setThreadStatus('assigned');
                e.preventDefault();
                break;
                
            case '3':
                setThreadStatus('closed');
                e.preventDefault();
                break;
                
            case 's':
                toggleThreadStar();
                e.preventDefault();
                break;
            
            // Compose
            case 'c':
                composeNewEmail();
                e.preventDefault();
                break;
            
            // Global
            case '/':
                focusSearch();
                e.preventDefault();
                break;
                
            case 'Shift+N':
                refreshThreads();
                e.preventDefault();
                break;
                
            case '?':
            case 'Shift+/':
                toggleHelpModal();
                e.preventDefault();
                break;
        }
    }

    /**
     * Build key combo string from event
     */
    function buildKeyCombo(e) {
        let combo = '';
        if (e.ctrlKey) combo += 'Ctrl+';
        if (e.metaKey) combo += 'Cmd+';
        if (e.altKey) combo += 'Alt+';
        if (e.shiftKey && e.key.length > 1) combo += 'Shift+';
        
        // Handle special cases
        if (e.key === '?' || (e.shiftKey && e.key === '/')) {
            return '?';
        }
        
        combo += e.key;
        return combo;
    }

    // ==========================================================================
    // NAVIGATION ACTIONS
    // ==========================================================================

    function selectNextThread() {
        const threads = Array.from(document.querySelectorAll('.c-thread-item:not([style*="display: none"])'));
        if (threads.length === 0) return;
        
        currentThreadIndex = Math.min(currentThreadIndex + 1, threads.length - 1);
        selectThreadByIndex(currentThreadIndex, threads);
    }

    function selectPrevThread() {
        const threads = Array.from(document.querySelectorAll('.c-thread-item:not([style*="display: none"])'));
        if (threads.length === 0) return;
        
        currentThreadIndex = Math.max(currentThreadIndex - 1, 0);
        selectThreadByIndex(currentThreadIndex, threads);
    }

    function selectThreadByIndex(index, threads) {
        // Remove previous selection highlight
        document.querySelectorAll('.c-thread-item.is-keyboard-focused').forEach(el => {
            el.classList.remove('is-keyboard-focused');
        });
        
        if (index >= 0 && index < threads.length) {
            const thread = threads[index];
            thread.classList.add('is-keyboard-focused');
            
            // Scroll into view
            thread.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            
            // Show toast hint
            showToast('Thread ' + (index + 1) + '/' + threads.length, 'info', 1000);
        }
    }

    function openSelectedThread() {
        const focused = document.querySelector('.c-thread-item.is-keyboard-focused');
        if (focused) {
            focused.click();
        } else {
            // Select first thread if none focused
            const first = document.querySelector('.c-thread-item:not([style*="display: none"])');
            if (first) {
                currentThreadIndex = 0;
                first.classList.add('is-keyboard-focused');
                first.click();
            }
        }
    }

    function closeThreadDetail() {
        const detailPanel = document.querySelector('.c-inbox__thread-detail');
        if (detailPanel) {
            detailPanel.innerHTML = `
                <div class="c-inbox__thread-detail-empty">
                    <svg width="80" height="80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <h3>Keine E-Mail ausgewählt</h3>
                    <p>Wähle einen Thread aus der Liste oder drücke <kbd>j</kbd>/<kbd>k</kbd> zum Navigieren.</p>
                </div>
            `;
        }
        
        // Remove active state from threads
        document.querySelectorAll('.c-thread-item.is-active').forEach(el => {
            el.classList.remove('is-active');
        });
    }

    // ==========================================================================
    // THREAD ACTIONS
    // ==========================================================================

    function getActiveThreadId() {
        const active = document.querySelector('.c-thread-item.is-active, .c-thread-item.is-keyboard-focused');
        return active?.dataset.threadId;
    }

    function replyToThread() {
        const threadId = getActiveThreadId();
        if (!threadId) {
            showToast('Kein Thread ausgewählt', 'warning');
            return;
        }
        
        // Trigger reply via global function
        if (typeof showEmailComposer === 'function') {
            showEmailComposer('reply', { threadId });
        }
    }

    function replyAllToThread() {
        const threadId = getActiveThreadId();
        if (!threadId) {
            showToast('Kein Thread ausgewählt', 'warning');
            return;
        }
        
        if (typeof showEmailComposer === 'function') {
            showEmailComposer('replyAll', { threadId });
        }
    }

    function forwardThread() {
        const threadId = getActiveThreadId();
        if (!threadId) {
            showToast('Kein Thread ausgewählt', 'warning');
            return;
        }
        
        if (typeof showEmailComposer === 'function') {
            showEmailComposer('forward', { threadId });
        }
    }

    async function archiveThread() {
        const threadId = getActiveThreadId();
        if (!threadId) {
            showToast('Kein Thread ausgewählt', 'warning');
            return;
        }
        
        try {
            const response = await fetch(`/api/threads/${threadId}/status`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: 'archived' })
            });
            
            if (response.ok) {
                showToast('Thread archiviert', 'success', 5000, {
                    action: 'Rückgängig',
                    onAction: () => unarchiveThread(threadId)
                });
                
                // Move to next thread
                selectNextThread();
                
                // Hide archived thread
                const threadEl = document.querySelector(`[data-thread-id="${threadId}"]`);
                if (threadEl) threadEl.style.display = 'none';
            }
        } catch (error) {
            showToast('Fehler beim Archivieren', 'error');
        }
    }

    async function unarchiveThread(threadId) {
        try {
            await fetch(`/api/threads/${threadId}/status`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: 'open' })
            });
            
            // Show thread again
            const threadEl = document.querySelector(`[data-thread-id="${threadId}"]`);
            if (threadEl) threadEl.style.display = 'flex';
            
            showToast('Archivierung rückgängig gemacht', 'success');
        } catch (error) {
            showToast('Fehler', 'error');
        }
    }

    async function deleteThread() {
        const threadId = getActiveThreadId();
        if (!threadId) {
            showToast('Kein Thread ausgewählt', 'warning');
            return;
        }
        
        // Store thread data for undo
        const threadEl = document.querySelector(`[data-thread-id="${threadId}"]`);
        const threadHtml = threadEl?.outerHTML;
        
        try {
            const response = await fetch(`/api/threads/${threadId}`, {
                method: 'DELETE'
            });
            
            if (response.ok) {
                showToast('Thread gelöscht', 'success', 5000);
                
                // Remove from list
                if (threadEl) threadEl.remove();
                
                // Move to next thread
                selectNextThread();
            }
        } catch (error) {
            showToast('Fehler beim Löschen', 'error');
        }
    }

    async function setThreadStatus(status) {
        const threadId = getActiveThreadId();
        if (!threadId) {
            showToast('Kein Thread ausgewählt', 'warning');
            return;
        }
        
        const statusLabels = {
            'open': 'Offen',
            'assigned': 'In Arbeit',
            'closed': 'Erledigt'
        };
        
        try {
            const response = await fetch(`/api/threads/${threadId}/status`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status })
            });
            
            if (response.ok) {
                showToast(`Status: ${statusLabels[status]}`, 'success');
                
                // Update badge in list
                const threadEl = document.querySelector(`[data-thread-id="${threadId}"]`);
                const badge = threadEl?.querySelector('.c-badge');
                if (badge) {
                    const colors = { open: 'primary', assigned: 'warning', closed: 'success' };
                    badge.className = `c-badge c-badge--${colors[status]}`;
                    badge.textContent = statusLabels[status];
                }
            }
        } catch (error) {
            showToast('Fehler beim Ändern des Status', 'error');
        }
    }

    function toggleThreadStar() {
        const threadId = getActiveThreadId();
        if (!threadId) return;
        
        // Favoriten/Pin-Feature ist noch nicht implementiert
        // Geplant für eine zukünftige Version
        showToast('Favoriten-Feature wird in einem zukünftigen Update verfügbar sein', 'info');
    }

    function openLabelPicker() {
        const threadId = getActiveThreadId();
        if (!threadId) {
            showToast('Kein Thread ausgewählt', 'warning');
            return;
        }
        
        // Trigger label picker via existing function
        if (typeof window.toggleLabelPicker === 'function') {
            window.toggleLabelPicker(threadId);
        } else {
            showToast('Label-Picker nicht verfügbar', 'error');
        }
    }

    function openAssignmentPicker() {
        const threadId = getActiveThreadId();
        if (!threadId) {
            showToast('Kein Thread ausgewählt', 'warning');
            return;
        }
        
        // Trigger assignment picker
        if (typeof window.toggleAssignmentPicker === 'function') {
            window.toggleAssignmentPicker(threadId);
        } else {
            showToast('Zuweisung nicht verfügbar', 'error');
        }
    }

    // ==========================================================================
    // GLOBAL ACTIONS
    // ==========================================================================

    function composeNewEmail() {
        if (typeof showEmailComposer === 'function') {
            showEmailComposer('new', { fromEmail: 'Shared Inbox' });
        }
    }

    function focusSearch() {
        const searchInput = document.getElementById('global-search');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }

    function refreshThreads() {
        if (typeof refreshThreadList === 'function') {
            refreshThreadList();
            showToast('Liste wird aktualisiert...', 'info', 2000);
        } else {
            const refreshBtn = document.getElementById('refresh-threads-btn');
            if (refreshBtn) refreshBtn.click();
        }
    }

    // ==========================================================================
    // HELP MODAL
    // ==========================================================================

    function createHelpModal() {
        // Check if modal already exists
        if (document.getElementById('keyboard-shortcuts-modal')) return;
        
        const modal = document.createElement('div');
        modal.id = 'keyboard-shortcuts-modal';
        modal.className = 'c-shortcuts-modal';
        modal.innerHTML = `
            <div class="c-shortcuts-modal__overlay"></div>
            <div class="c-shortcuts-modal__content">
                <div class="c-shortcuts-modal__header">
                    <h2>⌨️ Tastenkürzel</h2>
                    <button class="c-shortcuts-modal__close" aria-label="Schließen">&times;</button>
                </div>
                <div class="c-shortcuts-modal__body">
                    ${shortcutGroups.map(group => `
                        <div class="c-shortcuts-group">
                            <h3>${group.icon} ${group.name}</h3>
                            <div class="c-shortcuts-list">
                                ${group.shortcuts.map(shortcut => `
                                    <div class="c-shortcut-item">
                                        <span class="c-shortcut-keys">
                                            ${shortcut.keys.map(key => `<kbd>${key}</kbd>`).join(' / ')}
                                        </span>
                                        <span class="c-shortcut-desc">${shortcut.description}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    `).join('')}
                </div>
                <div class="c-shortcuts-modal__footer">
                    Drücke <kbd>?</kbd> um diese Hilfe zu öffnen/schließen
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Event listeners
        modal.querySelector('.c-shortcuts-modal__close').addEventListener('click', closeHelpModal);
        modal.querySelector('.c-shortcuts-modal__overlay').addEventListener('click', closeHelpModal);
    }

    function toggleHelpModal() {
        if (helpModalOpen) {
            closeHelpModal();
        } else {
            openHelpModal();
        }
    }

    function openHelpModal() {
        const modal = document.getElementById('keyboard-shortcuts-modal');
        if (modal) {
            modal.classList.add('is-open');
            helpModalOpen = true;
        }
    }

    function closeHelpModal() {
        const modal = document.getElementById('keyboard-shortcuts-modal');
        if (modal) {
            modal.classList.remove('is-open');
            helpModalOpen = false;
        }
    }

    // ==========================================================================
    // TOAST NOTIFICATIONS
    // ==========================================================================

    /**
     * Show a toast notification
     * @param {string} message - Message to display
     * @param {string} type - Type: 'success', 'error', 'warning', 'info'
     * @param {number} duration - Duration in ms (0 = persistent)
     * @param {object} options - Additional options
     */
    function showToast(message, type = 'info', duration = 3000, options = {}) {
        // Get or create toast container
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'c-toast-container';
            document.body.appendChild(container);
        }
        
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `c-toast c-toast--${type}`;
        
        // Icon based on type
        const icons = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ'
        };
        
        toast.innerHTML = `
            <span class="c-toast__icon">${icons[type] || 'ℹ'}</span>
            <span class="c-toast__message">${message}</span>
            ${options.action ? `<button class="c-toast__action">${options.action}</button>` : ''}
            <button class="c-toast__close" aria-label="Schließen">&times;</button>
        `;
        
        // Event listeners
        toast.querySelector('.c-toast__close').addEventListener('click', () => {
            removeToast(toast);
        });
        
        if (options.action && options.onAction) {
            toast.querySelector('.c-toast__action').addEventListener('click', () => {
                options.onAction();
                removeToast(toast);
            });
        }
        
        // Add to container
        container.appendChild(toast);
        
        // Trigger animation
        requestAnimationFrame(() => {
            toast.classList.add('is-visible');
        });
        
        // Auto-remove after duration
        if (duration > 0) {
            setTimeout(() => removeToast(toast), duration);
        }
        
        return toast;
    }

    function removeToast(toast) {
        toast.classList.remove('is-visible');
        toast.classList.add('is-leaving');
        
        setTimeout(() => {
            toast.remove();
        }, 300);
    }

    // ==========================================================================
    // PUBLIC API
    // ==========================================================================

    return {
        init,
        enable: () => { enabled = true; },
        disable: () => { enabled = false; },
        isEnabled: () => enabled,
        showToast,
        openHelpModal,
        closeHelpModal,
        
        // Expose actions for external use
        actions: {
            selectNext: selectNextThread,
            selectPrev: selectPrevThread,
            openSelected: openSelectedThread,
            close: closeThreadDetail,
            reply: replyToThread,
            archive: archiveThread,
            setStatus: setThreadStatus,
            compose: composeNewEmail,
            search: focusSearch,
            refresh: refreshThreads
        }
    };
})();

// Auto-initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    KeyboardShortcuts.init();
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = KeyboardShortcuts;
}

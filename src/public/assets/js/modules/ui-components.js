/**
 * UI Components Module
 * 
 * Wiederverwendbare UI-Komponenten: Dialoge, Picker, Toasts.
 * Nutzt ApiClient für Datenabfragen.
 * 
 * @module UiComponents
 * @since 2025-11-28 (Refactoring)
 */

const UiComponents = (function() {
    'use strict';

    // ============================================================================
    // CACHED DATA
    // ============================================================================
    
    let availableLabels = [];
    let availableUsers = [];
    
    // ============================================================================
    // CONFIRM DIALOG
    // ============================================================================
    
    /**
     * Show confirmation dialog
     * @param {object} options - Dialog options
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
        
        const escapeHtml = ApiClient.escapeHtml;
        
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
            e.stopPropagation();
            onConfirm();
            modal.classList.remove('c-modal--open');
            setTimeout(() => modal.remove(), 200);
        });
        
        // Handle cancel
        const cancelBtns = modal.querySelectorAll('[data-action="cancel"]');
        cancelBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
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
    
    // ============================================================================
    // SUCCESS MESSAGE (TOAST)
    // ============================================================================
    
    /**
     * Show success message (toast notification)
     * @param {string} message - Message to display
     */
    function showSuccessMessage(message) {
        console.log('[SUCCESS]', message);
        
        // Create toast container if not exists
        let toastContainer = document.querySelector('.c-toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'c-toast-container';
            document.body.appendChild(toastContainer);
        }
        
        const toast = document.createElement('div');
        toast.className = 'c-toast c-toast--success';
        toast.innerHTML = `
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>${ApiClient.escapeHtml(message)}</span>
        `;
        
        toastContainer.appendChild(toast);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            toast.classList.add('c-toast--hiding');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    /**
     * Show error message (toast notification)
     * @param {string} message - Message to display
     */
    function showErrorMessage(message) {
        console.error('[ERROR]', message);
        
        let toastContainer = document.querySelector('.c-toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'c-toast-container';
            document.body.appendChild(toastContainer);
        }
        
        const toast = document.createElement('div');
        toast.className = 'c-toast c-toast--error';
        toast.innerHTML = `
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>${ApiClient.escapeHtml(message)}</span>
        `;
        
        toastContainer.appendChild(toast);
        
        // Auto-remove after 5 seconds (longer for errors)
        setTimeout(() => {
            toast.classList.add('c-toast--hiding');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }
    
    // ============================================================================
    // LABEL PICKER
    // ============================================================================
    
    /**
     * Show label picker dialog for single thread
     * @param {number} threadId - Thread ID
     */
    async function showLabelPicker(threadId) {
        const escapeHtml = ApiClient.escapeHtml;
        
        try {
            // Load labels if not cached
            if (availableLabels.length === 0) {
                availableLabels = await ApiClient.getLabels();
            }
            
            // Get current thread labels
            const threadData = await ApiClient.getThread(threadId);
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
                    <div class="c-label-picker__name">${escapeHtml(label.name)}</div>
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
                    
                    try {
                        await ApiClient.updateThreadLabels(threadId, selectedIds);
                        showSuccessMessage('Labels aktualisiert');
                        
                        // Reload thread details if active
                        if (typeof loadThreadDetail === 'function') {
                            loadThreadDetail(threadId);
                        }
                    } catch (error) {
                        showErrorMessage('Fehler: ' + error.message);
                    }
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
            showErrorMessage('Fehler beim Laden der Labels: ' + error.message);
        }
    }
    
    /**
     * Show bulk label picker for multiple threads
     * @param {number[]} threadIds - Array of thread IDs
     */
    async function showBulkLabelPicker(threadIds) {
        const escapeHtml = ApiClient.escapeHtml;
        
        try {
            if (availableLabels.length === 0) {
                availableLabels = await ApiClient.getLabels();
            }
            
            const labelsHtml = availableLabels.map(label => `
                <li class="c-label-picker__item" data-label-id="${label.id}">
                    <div class="c-label-picker__checkbox">
                        <svg class="c-label-picker__checkbox-icon" width="12" height="12" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="c-label-picker__color" style="background-color: ${label.color}"></div>
                    <div class="c-label-picker__name">${escapeHtml(label.name)}</div>
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
                        showErrorMessage('Bitte wähle mindestens ein Label aus');
                        return;
                    }
                    
                    try {
                        await ApiClient.bulkAddLabels(threadIds, selectedIds);
                        showSuccessMessage(`Labels für ${threadIds.length} Threads aktualisiert`);
                    } catch (error) {
                        showErrorMessage('Fehler: ' + error.message);
                    }
                }
            });
            
            setTimeout(() => {
                document.querySelectorAll('.c-label-picker__item').forEach(item => {
                    item.addEventListener('click', function() {
                        this.classList.toggle('is-selected');
                    });
                });
                
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
            showErrorMessage('Fehler beim Laden der Labels: ' + error.message);
        }
    }
    
    // ============================================================================
    // ASSIGNMENT PICKER
    // ============================================================================
    
    /**
     * Show assignment picker dialog
     * @param {number} threadId - Thread ID
     */
    async function showAssignmentPicker(threadId) {
        const escapeHtml = ApiClient.escapeHtml;
        
        try {
            // Load users
            const users = await ApiClient.getUsers();
            
            // Get current thread assignments
            const threadData = await ApiClient.getThread(threadId);
            const thread = threadData.thread || threadData;
            const currentAssignedIds = (thread.assigned_users || []).map(u => u.id);
            
            // Build user picker HTML
            const usersHtml = users.map(user => {
                const name = user.name || user.username || user.email;
                let initials = name.charAt(0).toUpperCase();
                if (name.includes(' ')) {
                    const parts = name.split(' ');
                    initials = parts[0].charAt(0).toUpperCase() + parts[parts.length - 1].charAt(0).toUpperCase();
                }
                
                const colorNum = user.avatar_color || ((user.id % 8) + 1);
                const colorClass = `c-avatar--color-${colorNum}`;
                const isSelected = currentAssignedIds.includes(user.id);
                
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
                    const selectedIds = Array.from(document.querySelectorAll('.c-user-picker__item.is-selected'))
                        .map(item => parseInt(item.dataset.userId));
                    
                    try {
                        await ApiClient.assignThread(threadId, selectedIds);
                        showSuccessMessage('Zuweisung aktualisiert');
                        
                        if (typeof loadThreadDetail === 'function') {
                            loadThreadDetail(threadId);
                        }
                    } catch (error) {
                        showErrorMessage('Fehler: ' + error.message);
                    }
                }
            });
            
            setTimeout(() => {
                document.querySelectorAll('.c-user-picker__item').forEach(item => {
                    item.addEventListener('click', function() {
                        this.classList.toggle('is-selected');
                    });
                });
            }, 100);
            
        } catch (error) {
            console.error('Error showing assignment picker:', error);
            showErrorMessage('Fehler beim Laden der Benutzer: ' + error.message);
        }
    }
    
    // ============================================================================
    // STATUS PICKER (NEW!)
    // ============================================================================
    
    /**
     * Status configuration
     */
    const STATUS_CONFIG = {
        'open': { 
            label: 'Offen', 
            class: 'c-badge--primary',
            icon: `<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>`,
            description: 'Thread ist offen und wartet auf Bearbeitung'
        },
        'assigned': { 
            label: 'In Arbeit', 
            class: 'c-badge--warning',
            icon: `<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>`,
            description: 'Thread wird aktiv bearbeitet'
        },
        'closed': { 
            label: 'Erledigt', 
            class: 'c-badge--success',
            icon: `<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>`,
            description: 'Thread wurde erfolgreich abgeschlossen'
        },
        'archived': { 
            label: 'Archiviert', 
            class: 'c-badge--neutral',
            icon: `<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
            </svg>`,
            description: 'Thread ist im Archiv und nicht mehr aktiv'
        }
    };
    
    /**
     * Show status picker dialog
     * Click on status = immediate save (no extra button)
     * Shows current status with indicator
     * Handles conflicts (e.g., open with assignments)
     * 
     * @param {number} threadId - Thread ID
     */
    async function showStatusPicker(threadId) {
        try {
            // Get current thread data including assignments
            const threadData = await ApiClient.getThread(threadId);
            const thread = threadData.thread || threadData;
            const currentStatus = thread.status || 'open';
            const assignedUsers = thread.assigned_users || [];
            const hasAssignments = assignedUsers.length > 0;
            
            console.log('[StatusPicker] Thread:', threadId, 'Status:', currentStatus, 'Assigned:', assignedUsers.length);
            
            // Build status picker HTML with current indicator
            const statusHtml = Object.entries(STATUS_CONFIG).map(([status, config]) => {
                const isCurrent = status === currentStatus;
                return `
                    <div class="c-status-picker__item ${isCurrent ? 'is-current' : ''}" 
                         data-status="${status}">
                        <div class="c-status-picker__icon ${config.class}">
                            ${config.icon}
                        </div>
                        <div class="c-status-picker__info">
                            <div class="c-status-picker__label">
                                ${config.label}
                                ${isCurrent ? '<span class="c-status-picker__current-badge">aktuell</span>' : ''}
                            </div>
                            <div class="c-status-picker__description">${config.description}</div>
                        </div>
                        ${isCurrent ? '' : '<div class="c-status-picker__arrow"><svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></div>'}
                    </div>
                `;
            }).join('');
            
            // Create modal (simplified - no confirm button)
            const modal = document.createElement('div');
            modal.className = 'c-modal c-modal--open';
            modal.innerHTML = `
                <div class="c-modal__backdrop"></div>
                <div class="c-modal__container c-modal__container--sm">
                    <div class="c-modal__header">
                        <h3 class="c-modal__title">Status ändern</h3>
                        <button class="c-modal__close" aria-label="Schließen">
                            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="c-modal__body">
                        <div class="c-status-picker c-status-picker--clickable">
                            ${statusHtml}
                        </div>
                    </div>
                    <div class="c-modal__footer c-modal__footer--hint">
                        <span class="c-modal__hint">Klicke auf einen Status, um ihn zu setzen</span>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Close handlers
            const closeModal = () => {
                modal.classList.remove('c-modal--open');
                setTimeout(() => modal.remove(), 200);
            };
            
            modal.querySelector('.c-modal__backdrop').addEventListener('click', closeModal);
            modal.querySelector('.c-modal__close').addEventListener('click', closeModal);
            
            // ESC key to close
            const escHandler = (e) => {
                if (e.key === 'Escape') {
                    closeModal();
                    document.removeEventListener('keydown', escHandler);
                }
            };
            document.addEventListener('keydown', escHandler);
            
            // Status item click handlers - immediate action
            modal.querySelectorAll('.c-status-picker__item').forEach(item => {
                item.addEventListener('click', async function() {
                    const newStatus = this.dataset.status;
                    
                    // Skip if clicking current status
                    if (newStatus === currentStatus) {
                        closeModal();
                        return;
                    }
                    
                    // CONFLICT: Changing to "open" but thread has assignments
                    // User should decide: remove assignments or keep as "assigned"
                    if (newStatus === 'open' && hasAssignments) {
                        closeModal();
                        showOpenWithAssignmentsConfirm(threadId, assignedUsers);
                        return;
                    }
                    
                    // CONFLICT: Changing to "assigned" but no assignments
                    // Can't be "assigned" without users - redirect to assignment picker
                    if (newStatus === 'assigned' && !hasAssignments) {
                        closeModal();
                        showErrorMessage('Status "In Arbeit" erfordert zugewiesene Benutzer. Bitte weise zuerst einen Benutzer zu.');
                        // Optionally open assignment picker
                        if (typeof showAssignmentPicker === 'function') {
                            setTimeout(() => showAssignmentPicker(threadId), 500);
                        }
                        return;
                    }
                    
                    // Normal status change
                    try {
                        await ApiClient.updateThreadStatus(threadId, newStatus);
                        closeModal();
                        showSuccessMessage(`Status auf "${STATUS_CONFIG[newStatus].label}" geändert`);
                        
                        // Update thread list item
                        updateThreadListStatus(threadId, newStatus);
                        
                        // Reload thread detail if active
                        if (typeof loadThreadDetail === 'function') {
                            loadThreadDetail(threadId);
                        }
                    } catch (error) {
                        closeModal();
                        showErrorMessage('Fehler: ' + error.message);
                    }
                });
            });
            
        } catch (error) {
            console.error('Error showing status picker:', error);
            showErrorMessage('Fehler beim Laden des Status: ' + error.message);
        }
    }
    
    /**
     * Show confirmation dialog when setting "open" on thread with assignments
     * @param {number} threadId - Thread ID
     * @param {Array} assignedUsers - Currently assigned users
     */
    function showOpenWithAssignmentsConfirm(threadId, assignedUsers) {
        const userNames = assignedUsers.map(u => u.name || u.email).join(', ');
        
        showConfirmDialog({
            title: 'Zuweisungen entfernen?',
            message: `
                <p>Der Thread ist aktuell zugewiesen an:</p>
                <p><strong>${escapeHtml(userNames)}</strong></p>
                <p>Sollen die Zuweisungen entfernt werden, um den Status auf "Offen" zu setzen?</p>
            `,
            isHtml: true,
            confirmText: 'Ja, Zuweisungen entfernen',
            cancelText: 'Abbrechen',
            danger: true,
            onConfirm: async () => {
                try {
                    // First remove all assignments
                    await ApiClient.assignThread(threadId, []);
                    // Status will automatically change to "open" via Observer
                    showSuccessMessage('Zuweisungen entfernt - Status auf "Offen" geändert');
                    
                    // Update UI - status and remove avatars
                    updateThreadListStatus(threadId, 'open');
                    updateThreadListAssignments(threadId, []);
                    
                    // Reload thread detail
                    if (typeof loadThreadDetail === 'function') {
                        loadThreadDetail(threadId);
                    }
                } catch (error) {
                    showErrorMessage('Fehler: ' + error.message);
                }
            }
        });
    }
    
    /**
     * Update thread list item status badge
     * @param {number} threadId - Thread ID
     * @param {string} newStatus - New status
     */
    function updateThreadListStatus(threadId, newStatus) {
        const threadItem = document.querySelector(`.c-thread-item[data-thread-id="${threadId}"]`);
        if (threadItem) {
            const badge = threadItem.querySelector('.c-thread-item__meta .c-badge');
            if (badge) {
                badge.className = `c-badge ${STATUS_CONFIG[newStatus].class}`;
                badge.textContent = STATUS_CONFIG[newStatus].label;
            }
            
            // Hide from list if archived (unless viewing archive)
            if (newStatus === 'archived') {
                threadItem.style.transition = 'opacity 0.3s ease-out';
                threadItem.style.opacity = '0';
                setTimeout(() => threadItem.remove(), 300);
            }
        }
    }
    
    /**
     * Update thread list item assigned users avatars
     * @param {number} threadId - Thread ID
     * @param {Array} assignedUsers - Array of assigned user objects
     */
    function updateThreadListAssignments(threadId, assignedUsers) {
        const threadItem = document.querySelector(`.c-thread-item[data-thread-id="${threadId}"]`);
        if (!threadItem) return;
        
        const metaContainer = threadItem.querySelector('.c-thread-item__meta');
        if (!metaContainer) return;
        
        // Remove existing assigned users container
        const existingAssigned = threadItem.querySelector('.c-thread-item__assigned');
        if (existingAssigned) {
            existingAssigned.remove();
        }
        
        // If no users assigned, we're done
        if (!assignedUsers || assignedUsers.length === 0) {
            return;
        }
        
        // Build new avatars HTML
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
        
        // Create and append new container
        const assignedContainer = document.createElement('div');
        assignedContainer.className = 'c-thread-item__assigned';
        assignedContainer.innerHTML = avatarsHtml;
        metaContainer.appendChild(assignedContainer);
    }
    
    /**
     * Show bulk status picker for multiple threads
     * @param {number[]} threadIds - Array of thread IDs
     * @param {HTMLElement[]} threadElements - Array of thread DOM elements
     */
    async function showBulkStatusPicker(threadIds, threadElements) {
        // Build status picker HTML (no pre-selection for bulk)
        const statusHtml = Object.entries(STATUS_CONFIG).map(([status, config]) => `
            <div class="c-status-picker__item" data-status="${status}">
                <div class="c-status-picker__icon ${config.class}">
                    ${config.icon}
                </div>
                <div class="c-status-picker__info">
                    <div class="c-status-picker__label">${config.label}</div>
                    <div class="c-status-picker__description">${config.description}</div>
                </div>
                <div class="c-status-picker__arrow">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </div>
        `).join('');
        
        // Create modal
        const modal = document.createElement('div');
        modal.className = 'c-modal c-modal--open';
        modal.innerHTML = `
            <div class="c-modal__backdrop"></div>
            <div class="c-modal__container c-modal__container--sm">
                <div class="c-modal__header">
                    <h3 class="c-modal__title">Status für ${threadIds.length} Threads ändern</h3>
                    <button class="c-modal__close" aria-label="Schließen">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="c-modal__body">
                    <div class="c-status-picker c-status-picker--clickable">
                        ${statusHtml}
                    </div>
                </div>
                <div class="c-modal__footer c-modal__footer--hint">
                    <span class="c-modal__hint">Klicke auf einen Status, um ihn für alle ${threadIds.length} Threads zu setzen</span>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Close handlers
        const closeModal = () => {
            modal.classList.remove('c-modal--open');
            setTimeout(() => modal.remove(), 200);
        };
        
        modal.querySelector('.c-modal__backdrop').addEventListener('click', closeModal);
        modal.querySelector('.c-modal__close').addEventListener('click', closeModal);
        
        // ESC key
        const escHandler = (e) => {
            if (e.key === 'Escape') {
                closeModal();
                document.removeEventListener('keydown', escHandler);
            }
        };
        document.addEventListener('keydown', escHandler);
        
        // Status item click handlers
        modal.querySelectorAll('.c-status-picker__item').forEach(item => {
            item.addEventListener('click', async function() {
                const newStatus = this.dataset.status;
                
                try {
                    await ApiClient.bulkUpdateStatus(threadIds, { status: newStatus });
                    closeModal();
                    showSuccessMessage(`${threadIds.length} Threads auf "${STATUS_CONFIG[newStatus].label}" geändert`);
                    
                    // Update UI
                    threadElements.forEach(threadItem => {
                        const badge = threadItem.querySelector('.c-thread-item__meta .c-badge');
                        if (badge) {
                            badge.className = `c-badge ${STATUS_CONFIG[newStatus].class}`;
                            badge.textContent = STATUS_CONFIG[newStatus].label;
                        }
                        threadItem.classList.remove('is-selected');
                        
                        // Hide if archived
                        if (newStatus === 'archived') {
                            threadItem.style.transition = 'opacity 0.3s ease-out';
                            threadItem.style.opacity = '0';
                            setTimeout(() => threadItem.remove(), 300);
                        }
                    });
                } catch (error) {
                    closeModal();
                    showErrorMessage('Fehler: ' + error.message);
                }
            });
        });
    }
    
    // ============================================================================
    // PUBLIC API
    // ============================================================================
    
    return {
        // Dialogs
        showConfirmDialog,
        showSuccessMessage,
        showErrorMessage,
        
        // Pickers
        showLabelPicker,
        showBulkLabelPicker,
        showAssignmentPicker,
        showStatusPicker,
        showBulkStatusPicker,
        
        // UI Updates
        updateThreadListStatus,
        updateThreadListAssignments,
        
        // Config (for external access)
        STATUS_CONFIG
    };
})();

// Make globally available
window.UiComponents = UiComponents;

// Convenience aliases for backwards compatibility
window.showConfirmDialog = UiComponents.showConfirmDialog;
window.showSuccessMessage = UiComponents.showSuccessMessage;
window.showLabelPicker = UiComponents.showLabelPicker;
window.showBulkLabelPicker = UiComponents.showBulkLabelPicker;
window.showAssignmentPicker = UiComponents.showAssignmentPicker;
window.showStatusPicker = UiComponents.showStatusPicker;

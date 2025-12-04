/**
 * API Client Module
 * 
 * Zentralisierte API-Aufrufe für die Inbox-Anwendung.
 * Alle fetch()-Aufrufe werden hier gebündelt.
 * 
 * @module ApiClient
 * @since 2025-11-28 (Refactoring)
 */

const ApiClient = (function() {
    'use strict';

    // ============================================================================
    // CONFIGURATION
    // ============================================================================
    
    const API_BASE = '/api';
    
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
     * Get current thread ID from active thread or detail view
     * @returns {number|null} Thread ID or null
     */
    function getCurrentThreadId() {
        // Try from detail view first
        const detailView = document.querySelector('.c-thread-detail[data-thread-id]');
        if (detailView) {
            return parseInt(detailView.dataset.threadId);
        }
        
        // Fallback to active thread item
        const activeThread = document.querySelector('.c-thread-item.is-active');
        if (activeThread) {
            return parseInt(activeThread.dataset.threadId);
        }
        
        return null;
    }
    
    /**
     * Generic fetch wrapper with error handling
     * @param {string} url - API endpoint
     * @param {object} options - Fetch options
     * @returns {Promise<object>} Response data
     * @throws {Error} Network or HTTP errors
     */
    async function request(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json'
            }
        };
        
        const mergedOptions = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...options.headers
            }
        };
        
        let response;
        try {
            response = await fetch(url, mergedOptions);
        } catch (networkError) {
            // Network error (offline, DNS failure, CORS, etc.)
            console.error('[ApiClient] Network error:', networkError);
            throw new Error('Netzwerkfehler - Bitte Verbindung prüfen');
        }
        
        if (!response.ok) {
            const error = await response.json().catch(() => ({ error: 'Unbekannter Fehler' }));
            throw new Error(error.error || `HTTP ${response.status}`);
        }
        
        // Handle empty responses (204 No Content)
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            return { success: true };
        }
        
        return response.json();
    }
    
    // ============================================================================
    // THREAD API
    // ============================================================================
    
    /**
     * Get thread details
     * @param {number} threadId - Thread ID
     * @returns {Promise<object>} Thread data
     */
    async function getThread(threadId) {
        return request(`${API_BASE}/threads/${threadId}`);
    }
    
    /**
     * Get thread details with emails and notes
     * @param {number} threadId - Thread ID
     * @returns {Promise<object>} Thread details
     */
    async function getThreadDetails(threadId) {
        return request(`${API_BASE}/threads/${threadId}/details`);
    }
    
    /**
     * Update thread
     * @param {number} threadId - Thread ID
     * @param {object} data - Update data
     * @returns {Promise<object>} Updated thread
     */
    async function updateThread(threadId, data) {
        return request(`${API_BASE}/threads/${threadId}`, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }
    
    /**
     * Delete thread
     * @param {number} threadId - Thread ID
     * @returns {Promise<object>} Result
     */
    async function deleteThread(threadId) {
        return request(`${API_BASE}/threads/${threadId}`, {
            method: 'DELETE'
        });
    }
    
    /**
     * Mark thread as read
     * @param {number} threadId - Thread ID
     * @returns {Promise<object>} Result
     */
    async function markThreadRead(threadId) {
        return request(`${API_BASE}/threads/${threadId}/mark-read`, {
            method: 'POST'
        });
    }
    
    /**
     * Mark thread as unread
     * @param {number} threadId - Thread ID
     * @returns {Promise<object>} Result
     */
    async function markThreadUnread(threadId) {
        return request(`${API_BASE}/threads/${threadId}/mark-unread`, {
            method: 'POST'
        });
    }
    
    /**
     * Update thread status
     * @param {number} threadId - Thread ID
     * @param {string} status - New status (open, assigned, closed, archived)
     * @returns {Promise<object>} Updated thread
     */
    async function updateThreadStatus(threadId, status) {
        return request(`${API_BASE}/threads/${threadId}`, {
            method: 'PUT',
            body: JSON.stringify({ status })
        });
    }
    
    /**
     * Assign users to thread
     * @param {number} threadId - Thread ID
     * @param {number[]} userIds - Array of user IDs
     * @returns {Promise<object>} Result with assigned users
     */
    async function assignThread(threadId, userIds) {
        return request(`${API_BASE}/threads/${threadId}/assign`, {
            method: 'POST',
            body: JSON.stringify({ user_ids: userIds })
        });
    }
    
    /**
     * Update thread labels
     * @param {number} threadId - Thread ID
     * @param {number[]} labelIds - Array of label IDs
     * @returns {Promise<object>} Updated thread
     */
    async function updateThreadLabels(threadId, labelIds) {
        return request(`${API_BASE}/threads/${threadId}`, {
            method: 'PUT',
            body: JSON.stringify({ label_ids: labelIds })
        });
    }
    
    // ============================================================================
    // BULK OPERATIONS
    // ============================================================================
    
    /**
     * Bulk update thread status
     * @param {number[]} threadIds - Array of thread IDs
     * @param {object} data - Status data (status, is_read)
     * @returns {Promise<object>} Result
     */
    async function bulkUpdateStatus(threadIds, data) {
        return request(`${API_BASE}/threads/bulk/status`, {
            method: 'POST',
            body: JSON.stringify({
                thread_ids: threadIds,
                ...data
            })
        });
    }
    
    /**
     * Bulk delete threads
     * @param {number[]} threadIds - Array of thread IDs
     * @returns {Promise<object>} Result
     */
    async function bulkDelete(threadIds) {
        return request(`${API_BASE}/threads/bulk/delete`, {
            method: 'POST',
            body: JSON.stringify({ thread_ids: threadIds })
        });
    }
    
    /**
     * Bulk add labels to threads
     * @param {number[]} threadIds - Array of thread IDs
     * @param {number[]} labelIds - Array of label IDs
     * @returns {Promise<object>} Result
     */
    async function bulkAddLabels(threadIds, labelIds) {
        return request(`${API_BASE}/threads/bulk/labels`, {
            method: 'POST',
            body: JSON.stringify({
                thread_ids: threadIds,
                label_ids: labelIds
            })
        });
    }
    
    // ============================================================================
    // EMAIL API
    // ============================================================================
    
    /**
     * Mark email as read
     * @param {number} emailId - Email ID
     * @returns {Promise<object>} Result
     */
    async function markEmailRead(emailId) {
        return request(`${API_BASE}/emails/${emailId}/read`, {
            method: 'POST'
        });
    }
    
    // ============================================================================
    // NOTES API
    // ============================================================================
    
    /**
     * Add note to thread
     * @param {number} threadId - Thread ID
     * @param {string} content - Note content
     * @param {number} position - Position in thread (optional)
     * @returns {Promise<object>} Created note
     */
    async function addNote(threadId, content, position = null) {
        const body = { content };
        if (position !== null) {
            body.position = position;
        }
        
        return request(`${API_BASE}/threads/${threadId}/notes`, {
            method: 'POST',
            body: JSON.stringify(body)
        });
    }
    
    /**
     * Update note
     * @param {number} threadId - Thread ID
     * @param {number} noteId - Note ID
     * @param {string} content - New content
     * @returns {Promise<object>} Updated note
     */
    async function updateNote(threadId, noteId, content) {
        return request(`${API_BASE}/threads/${threadId}/notes/${noteId}`, {
            method: 'PUT',
            body: JSON.stringify({ content })
        });
    }
    
    /**
     * Delete note
     * @param {number} threadId - Thread ID
     * @param {number} noteId - Note ID
     * @returns {Promise<object>} Result
     */
    async function deleteNote(threadId, noteId) {
        return request(`${API_BASE}/threads/${threadId}/notes/${noteId}`, {
            method: 'DELETE'
        });
    }
    
    // ============================================================================
    // LABELS API
    // ============================================================================
    
    /**
     * Get all labels
     * @returns {Promise<object[]>} Array of labels
     */
    async function getLabels() {
        const data = await request(`${API_BASE}/labels`);
        return Array.isArray(data) ? data : (data.labels || []);
    }
    
    // ============================================================================
    // USERS API
    // ============================================================================
    
    /**
     * Get all users
     * @returns {Promise<object[]>} Array of users
     */
    async function getUsers() {
        const data = await request(`${API_BASE}/users`);
        return Array.isArray(data) ? data : (data.users || []);
    }
    
    // ============================================================================
    // PUBLIC API
    // ============================================================================
    
    return {
        // Helpers
        escapeHtml,
        getCurrentThreadId,
        
        // Threads
        getThread,
        getThreadDetails,
        updateThread,
        deleteThread,
        markThreadRead,
        markThreadUnread,
        updateThreadStatus,
        assignThread,
        updateThreadLabels,
        
        // Bulk
        bulkUpdateStatus,
        bulkDelete,
        bulkAddLabels,
        
        // Emails
        markEmailRead,
        
        // Notes
        addNote,
        updateNote,
        deleteNote,
        
        // Labels
        getLabels,
        
        // Users
        getUsers
    };
})();

// Make globally available
window.ApiClient = ApiClient;

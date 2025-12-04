/**
 * User Management JavaScript
 * 
 * Handles CRUD operations for user management
 */

const API_BASE = '/api/users';

let users = [];
let currentUserId = null;
let userModal = null;
let deleteModal = null;

console.log('[UserManagement] Script loaded');

// ============================================================================
// LOAD USERS
// ============================================================================

async function loadUsers() {
    console.log('[UserManagement] Loading users...');
    const tbody = document.getElementById('users-table-body');
    
    try {
        const response = await fetch(API_BASE);
        const result = await response.json();
        
        if (response.ok && result.users) {
            users = result.users;
            console.log('[UserManagement] Loaded users:', users.length);
            
            if (users.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <svg viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                </svg>
                                <h4>No users yet</h4>
                                <p>Click "Add User" to create your first user</p>
                            </div>
                        </td>
                    </tr>
                `;
            } else {
                tbody.innerHTML = users.map(user => `
                    <tr>
                        <td><strong>${escapeHtml(user.name)}</strong></td>
                        <td>${escapeHtml(user.email)}</td>
                        <td>
                            <span class="badge ${user.role === 'admin' ? 'badge-primary' : 'badge-secondary'}">
                                ${user.role}
                            </span>
                        </td>
                        <td>
                            <span class="badge ${user.is_active ? 'badge-success' : 'badge-danger'}">
                                ${user.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </td>
                        <td>${user.last_login_at ? formatDate(user.last_login_at) : '—'}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-secondary" onclick="editUser(${user.id})">
                                    <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                    </svg>
                                    Edit
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="confirmDelete(${user.id})">
                                    <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            }
        } else {
            throw new Error(result.error || 'Failed to load users');
        }
    } catch (error) {
        console.error('[UserManagement] Error loading users:', error);
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-danger">
                    Failed to load users. Please refresh the page.
                </td>
            </tr>
        `;
        showAlert('Failed to load users: ' + error.message, 'danger');
    }
}

// ============================================================================
// CREATE/EDIT USER
// ============================================================================

function openAddUserModal() {
    console.log('[UserManagement] Opening add user modal');
    currentUserId = null;
    
    document.getElementById('userModalTitle').textContent = 'Add User';
    document.getElementById('user-form').reset();
    document.getElementById('user-id').value = '';
    document.getElementById('user-active').checked = true;
    document.getElementById('password-group').style.display = 'block';
    document.getElementById('user-password').required = true;
    
    userModal.show();
}

function editUser(userId) {
    console.log('[UserManagement] Editing user:', userId);
    currentUserId = userId;
    
    const user = users.find(u => u.id === userId);
    if (!user) {
        showAlert('User not found', 'danger');
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
    
    userModal.show();
}

async function saveUser() {
    console.log('[UserManagement] Saving user...');
    
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
        const url = userId ? `${API_BASE}/${userId}` : API_BASE;
        const method = userId ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (response.ok) {
            console.log('[UserManagement] User saved:', result);
            showAlert(userId ? 'User updated successfully' : 'User created successfully', 'success');
            userModal.hide();
            loadUsers();
        } else {
            throw new Error(result.error || 'Failed to save user');
        }
    } catch (error) {
        console.error('[UserManagement] Error saving user:', error);
        showAlert('Failed to save user: ' + error.message, 'danger');
    } finally {
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save';
    }
}

// ============================================================================
// DELETE USER
// ============================================================================

function confirmDelete(userId) {
    console.log('[UserManagement] Confirming delete for user:', userId);
    currentUserId = userId;
    
    const user = users.find(u => u.id === userId);
    if (!user) {
        showAlert('User not found', 'danger');
        return;
    }
    
    document.getElementById('delete-user-info').innerHTML = `
        <strong>${escapeHtml(user.name)}</strong><br>
        ${escapeHtml(user.email)}
    `;
    
    deleteModal.show();
}

async function deleteUser() {
    console.log('[UserManagement] Deleting user:', currentUserId);
    
    const deleteBtn = document.getElementById('btn-confirm-delete');
    deleteBtn.disabled = true;
    deleteBtn.textContent = 'Deleting...';
    
    try {
        const response = await fetch(`${API_BASE}/${currentUserId}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (response.ok) {
            console.log('[UserManagement] User deleted');
            showAlert('User deleted successfully', 'success');
            deleteModal.hide();
            loadUsers();
        } else {
            throw new Error(result.error || 'Failed to delete user');
        }
    } catch (error) {
        console.error('[UserManagement] Error deleting user:', error);
        showAlert('Failed to delete user: ' + error.message, 'danger');
    } finally {
        deleteBtn.disabled = false;
        deleteBtn.textContent = 'Delete';
    }
}

// ============================================================================
// UTILITIES
// ============================================================================

function showAlert(message, type = 'info') {
    const container = document.getElementById('alert-container');
    const alertId = 'alert-' + Date.now();
    
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

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(isoString) {
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

// ============================================================================
// INITIALIZATION
// ============================================================================

document.addEventListener('DOMContentLoaded', () => {
    console.log('[UserManagement] Initializing...');
    
    // Initialize modals
    userModal = new bootstrap.Modal(document.getElementById('userModal'));
    deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    
    // Load users
    loadUsers();
    
    // Event listeners
    document.getElementById('btn-add-user').addEventListener('click', openAddUserModal);
    document.getElementById('btn-save-user').addEventListener('click', saveUser);
    document.getElementById('btn-confirm-delete').addEventListener('click', deleteUser);
    
    // Form submit
    document.getElementById('user-form').addEventListener('submit', (e) => {
        e.preventDefault();
        saveUser();
    });
});

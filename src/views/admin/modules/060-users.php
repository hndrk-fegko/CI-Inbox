<?php
/**
 * Admin Tab Module: User Management
 * 
 * Provides:
 * - User list with search and filter
 * - Create/Edit/Delete users
 * - Role management (Admin/User)
 * - Status toggle (Active/Inactive)
 * - Inbox assignment overview
 * 
 * Auto-discovered by admin dashboard
 */

return [
    'id' => 'users',
    'title' => 'Users',
    'priority' => 60,
    'icon' => '<path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>',
    
    'card' => function() {
        ?>
        <div class="c-admin-card" data-module="users" style="cursor: pointer;">
            <div class="c-admin-card__header">
                <div class="c-admin-card__icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="c-admin-card__title">User Management</h3>
                </div>
            </div>
            <p class="c-admin-card__description">Manage user accounts, roles, and permissions.</p>
            <div class="c-admin-card__content">
                <div class="c-info-row">
                    <span class="c-info-row__label">Total Users</span>
                    <span class="c-info-row__value" id="total-users-count">—</span>
                </div>
                <div class="c-info-row">
                    <span class="c-info-row__label">Active Users</span>
                    <span class="c-info-row__value" id="active-users-count">—</span>
                </div>
            </div>
        </div>
        <?php
    },
    
    'content' => function() {
        ?>
        <div style="margin-bottom: 2rem;">
            <h3 style="margin: 0 0 0.5rem 0; font-size: 1.25rem; font-weight: 600;">User Management</h3>
            <p style="margin: 0; color: #666; font-size: 0.875rem;">Manage user accounts, roles, and access permissions.</p>
        </div>
        
        <!-- Info Box -->
        <div style="background: #E3F2FD; border-left: 4px solid #2196F3; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="#1976D2" style="flex-shrink: 0; margin-top: 2px;">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <strong style="color: #1565C0;">About User Roles</strong>
                    <p style="margin: 0.5rem 0 0 0; color: #1976D2; font-size: 0.875rem;">
                        <strong>Admin:</strong> Full access to all settings, can manage users and system configuration.<br>
                        <strong>User:</strong> Can access shared inbox, manage assigned threads, but cannot change system settings.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Alert Container -->
        <div id="user-alert" style="margin-bottom: 1rem;"></div>
        
        <!-- Actions Bar -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <input type="text" id="user-search" class="c-input" placeholder="Search by name or email..." style="width: 250px;">
                <select id="user-filter-role" class="c-input" style="width: 150px;">
                    <option value="">All Roles</option>
                    <option value="admin">Admin</option>
                    <option value="user">User</option>
                </select>
                <select id="user-filter-status" class="c-input" style="width: 150px;">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <button id="user-add-btn" class="c-button c-button--primary">
                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                    <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z"/>
                </svg>
                Add User
            </button>
        </div>
        
        <!-- User Table -->
        <div style="background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow: hidden;">
            <div class="table-responsive">
                <table class="table" style="margin: 0;">
                    <thead style="background: #f8f9fa;">
                        <tr>
                            <th style="font-weight: 600; color: #666; text-transform: uppercase; font-size: 0.75rem; padding: 1rem; border: none;">User</th>
                            <th style="font-weight: 600; color: #666; text-transform: uppercase; font-size: 0.75rem; padding: 1rem; border: none;">Role</th>
                            <th style="font-weight: 600; color: #666; text-transform: uppercase; font-size: 0.75rem; padding: 1rem; border: none;">Status</th>
                            <th style="font-weight: 600; color: #666; text-transform: uppercase; font-size: 0.75rem; padding: 1rem; border: none;">Inboxes</th>
                            <th style="font-weight: 600; color: #666; text-transform: uppercase; font-size: 0.75rem; padding: 1rem; border: none;">Last Login</th>
                            <th style="font-weight: 600; color: #666; text-transform: uppercase; font-size: 0.75rem; padding: 1rem; border: none; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="user-table-body">
                        <tr>
                            <td colspan="6" style="padding: 2rem; text-align: center; color: #666;">
                                Loading users...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Add/Edit User Modal -->
        <div class="c-modal" id="user-edit-modal">
            <div class="c-modal__content" style="max-width: 500px;">
                <div class="c-modal__header">
                    <h2 id="user-modal-title">Add User</h2>
                    <button class="c-modal__close" id="user-modal-close">&times;</button>
                </div>
                <div class="c-modal__body">
                    <input type="hidden" id="user-edit-id">
                    
                    <div class="c-input-group" style="margin-bottom: 1rem;">
                        <label for="user-edit-name">Full Name <span style="color: #f44336;">*</span></label>
                        <input type="text" id="user-edit-name" class="c-input" placeholder="John Doe">
                    </div>
                    
                    <div class="c-input-group" style="margin-bottom: 1rem;">
                        <label for="user-edit-email">Email Address <span style="color: #f44336;">*</span></label>
                        <input type="email" id="user-edit-email" class="c-input" placeholder="john@example.com">
                    </div>
                    
                    <div class="c-input-group" style="margin-bottom: 1rem;">
                        <label for="user-edit-password">Password <span id="user-password-required" style="color: #f44336;">*</span></label>
                        <input type="password" id="user-edit-password" class="c-input" placeholder="Enter password">
                        <small id="user-password-hint" style="color: #666;"></small>
                    </div>
                    
                    <div class="c-input-group" style="margin-bottom: 1rem;">
                        <label for="user-edit-role">Role <span style="color: #f44336;">*</span></label>
                        <select id="user-edit-role" class="c-input">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div class="c-input-group">
                        <label>
                            <input type="checkbox" id="user-edit-active" checked>
                            Active (can log in)
                        </label>
                    </div>
                </div>
                <div class="c-modal__footer">
                    <button type="button" class="c-button c-button--secondary" id="user-modal-cancel">Cancel</button>
                    <button type="button" class="c-button c-button--primary" id="user-modal-save">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                            <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z"/>
                        </svg>
                        Save User
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Delete Confirmation Modal -->
        <div class="c-modal" id="user-delete-modal">
            <div class="c-modal__content" style="max-width: 400px;">
                <div class="c-modal__header">
                    <h2>Delete User</h2>
                    <button class="c-modal__close" id="user-delete-close">&times;</button>
                </div>
                <div class="c-modal__body">
                    <p style="color: #666;">Are you sure you want to delete this user?</p>
                    <p id="user-delete-name" style="font-weight: 600;"></p>
                    <p style="color: #f44336; margin-bottom: 0;"><strong>This action cannot be undone. All user data will be permanently deleted.</strong></p>
                </div>
                <div class="c-modal__footer">
                    <button type="button" class="c-button c-button--secondary" id="user-delete-cancel">Cancel</button>
                    <button type="button" class="c-button c-button--danger" id="user-delete-confirm">Delete User</button>
                </div>
            </div>
        </div>
        <?php
    },
    
    'script' => function() {
        ?>
        // Users Module State
        const UsersModule = {
            users: [],
            editingId: null,
            deleteId: null,
            
            init() {
                console.log('[Users] Initializing module...');
                this.loadUsers();
                this.bindEvents();
            },
            
            bindEvents() {
                // Add user button
                const addBtn = document.getElementById('user-add-btn');
                if (addBtn) addBtn.addEventListener('click', () => this.openAddModal());
                
                // Search and filters
                const search = document.getElementById('user-search');
                const roleFilter = document.getElementById('user-filter-role');
                const statusFilter = document.getElementById('user-filter-status');
                
                if (search) search.addEventListener('input', () => this.renderUsers());
                if (roleFilter) roleFilter.addEventListener('change', () => this.renderUsers());
                if (statusFilter) statusFilter.addEventListener('change', () => this.renderUsers());
                
                // Modal controls
                const modalClose = document.getElementById('user-modal-close');
                const modalCancel = document.getElementById('user-modal-cancel');
                const modalSave = document.getElementById('user-modal-save');
                
                if (modalClose) modalClose.addEventListener('click', () => this.closeModal());
                if (modalCancel) modalCancel.addEventListener('click', () => this.closeModal());
                if (modalSave) modalSave.addEventListener('click', () => this.saveUser());
                
                // Delete modal
                const deleteClose = document.getElementById('user-delete-close');
                const deleteCancel = document.getElementById('user-delete-cancel');
                const deleteConfirm = document.getElementById('user-delete-confirm');
                
                if (deleteClose) deleteClose.addEventListener('click', () => this.closeDeleteModal());
                if (deleteCancel) deleteCancel.addEventListener('click', () => this.closeDeleteModal());
                if (deleteConfirm) deleteConfirm.addEventListener('click', () => this.confirmDelete());
            },
            
            async loadUsers() {
                try {
                    const response = await fetch('/api/users');
                    const data = await response.json();
                    
                    if (data.success && data.data) {
                        this.users = data.data;
                        this.renderUsers();
                        this.updateCardCounts();
                    }
                } catch (error) {
                    console.error('[Users] Failed to load:', error);
                    this.showAlert('user-alert', 'Failed to load users', 'error');
                }
            },
            
            updateCardCounts() {
                const total = document.getElementById('total-users-count');
                const active = document.getElementById('active-users-count');
                
                if (total) total.textContent = this.users.length;
                if (active) active.textContent = this.users.filter(u => u.is_active).length;
            },
            
            getFilteredUsers() {
                const search = document.getElementById('user-search')?.value?.toLowerCase() || '';
                const roleFilter = document.getElementById('user-filter-role')?.value || '';
                const statusFilter = document.getElementById('user-filter-status')?.value || '';
                
                return this.users.filter(user => {
                    // Search filter
                    const matchesSearch = !search || 
                        (user.name && user.name.toLowerCase().includes(search)) ||
                        (user.email && user.email.toLowerCase().includes(search));
                    
                    // Role filter
                    const matchesRole = !roleFilter || user.role === roleFilter;
                    
                    // Status filter
                    const matchesStatus = !statusFilter || 
                        (statusFilter === 'active' && user.is_active) ||
                        (statusFilter === 'inactive' && !user.is_active);
                    
                    return matchesSearch && matchesRole && matchesStatus;
                });
            },
            
            renderUsers() {
                const tbody = document.getElementById('user-table-body');
                if (!tbody) return;
                
                const filtered = this.getFilteredUsers();
                
                if (filtered.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" style="padding: 2rem; text-align: center; color: #666;">
                                <svg width="48" height="48" viewBox="0 0 20 20" fill="currentColor" style="opacity: 0.3; margin-bottom: 0.5rem;">
                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"/>
                                </svg>
                                <p style="margin: 0;">No users found</p>
                                <p style="margin: 0.25rem 0 0 0; font-size: 0.875rem;">Try adjusting your search or filters</p>
                            </td>
                        </tr>
                    `;
                    return;
                }
                
                tbody.innerHTML = filtered.map(user => {
                    const initials = (user.name || user.email || '?').charAt(0).toUpperCase();
                    const roleBadge = user.role === 'admin' 
                        ? '<span class="c-badge" style="background: #E3F2FD; color: #1565C0;">Admin</span>'
                        : '<span class="c-badge" style="background: #F5F5F5; color: #666;">User</span>';
                    const statusBadge = user.is_active
                        ? '<span class="c-status-badge c-status-badge--success"><span class="status-dot"></span>Active</span>'
                        : '<span class="c-status-badge c-status-badge--warning"><span class="status-dot"></span>Inactive</span>';
                    
                    return `
                        <tr>
                            <td style="padding: 1rem; border-bottom: 1px solid #eee;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 1rem;">
                                        ${this.escapeHtml(initials)}
                                    </div>
                                    <div>
                                        <div style="font-weight: 500;">${this.escapeHtml(user.name || 'Unnamed')}</div>
                                        <div style="font-size: 0.875rem; color: #666;">${this.escapeHtml(user.email || '')}</div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 1rem; border-bottom: 1px solid #eee;">${roleBadge}</td>
                            <td style="padding: 1rem; border-bottom: 1px solid #eee;">${statusBadge}</td>
                            <td style="padding: 1rem; border-bottom: 1px solid #eee;">
                                <span style="color: #666;" title="${user.inbox_names || 'No inboxes assigned'}">
                                    ${user.inbox_count || 0} inbox${(user.inbox_count || 0) !== 1 ? 'es' : ''}
                                </span>
                            </td>
                            <td style="padding: 1rem; border-bottom: 1px solid #eee; font-size: 0.875rem; color: #666;">
                                ${user.last_login_at || 'Never'}
                            </td>
                            <td style="padding: 1rem; border-bottom: 1px solid #eee; text-align: right;">
                                <button type="button" class="c-button c-button--secondary user-edit-btn" data-id="${user.id}" style="padding: 0.375rem 0.75rem; font-size: 0.875rem; margin-right: 0.25rem;">
                                    <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                    </svg>
                                </button>
                                <button type="button" class="c-button c-button--danger user-delete-btn" data-id="${user.id}" data-name="${this.escapeHtml(user.name || user.email)}" style="padding: 0.375rem 0.75rem; font-size: 0.875rem;">
                                    <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('');
                
                // Bind edit/delete buttons
                document.querySelectorAll('.user-edit-btn').forEach(btn => {
                    btn.addEventListener('click', () => this.openEditModal(btn.dataset.id));
                });
                
                document.querySelectorAll('.user-delete-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        this.deleteId = btn.dataset.id;
                        document.getElementById('user-delete-name').textContent = btn.dataset.name;
                        document.getElementById('user-delete-modal').classList.add('show');
                    });
                });
            },
            
            openAddModal() {
                this.editingId = null;
                document.getElementById('user-edit-id').value = '';
                document.getElementById('user-edit-name').value = '';
                document.getElementById('user-edit-email').value = '';
                document.getElementById('user-edit-password').value = '';
                document.getElementById('user-edit-role').value = 'user';
                document.getElementById('user-edit-active').checked = true;
                document.getElementById('user-modal-title').textContent = 'Add User';
                document.getElementById('user-password-required').style.display = 'inline';
                document.getElementById('user-password-hint').textContent = 'Minimum 8 characters';
                document.getElementById('user-edit-modal').classList.add('show');
            },
            
            openEditModal(id) {
                const user = this.users.find(u => u.id == id);
                if (!user) return;
                
                this.editingId = id;
                document.getElementById('user-edit-id').value = id;
                document.getElementById('user-edit-name').value = user.name || '';
                document.getElementById('user-edit-email').value = user.email || '';
                document.getElementById('user-edit-password').value = '';
                document.getElementById('user-edit-role').value = user.role || 'user';
                document.getElementById('user-edit-active').checked = user.is_active !== false;
                document.getElementById('user-modal-title').textContent = 'Edit User';
                document.getElementById('user-password-required').style.display = 'none';
                document.getElementById('user-password-hint').textContent = 'Leave empty to keep current password';
                document.getElementById('user-edit-modal').classList.add('show');
            },
            
            closeModal() {
                document.getElementById('user-edit-modal').classList.remove('show');
                this.editingId = null;
            },
            
            closeDeleteModal() {
                document.getElementById('user-delete-modal').classList.remove('show');
                this.deleteId = null;
            },
            
            async saveUser() {
                const saveBtn = document.getElementById('user-modal-save');
                const name = document.getElementById('user-edit-name')?.value?.trim();
                const email = document.getElementById('user-edit-email')?.value?.trim();
                const password = document.getElementById('user-edit-password')?.value;
                const role = document.getElementById('user-edit-role')?.value;
                const isActive = document.getElementById('user-edit-active')?.checked;
                
                // Validation
                if (!name) {
                    this.showAlert('user-alert', 'Please enter a name', 'error');
                    return;
                }
                
                if (!email || !email.includes('@')) {
                    this.showAlert('user-alert', 'Please enter a valid email address', 'error');
                    return;
                }
                
                if (!this.editingId && (!password || password.length < 8)) {
                    this.showAlert('user-alert', 'Password must be at least 8 characters', 'error');
                    return;
                }
                
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
                
                try {
                    const data = {
                        name,
                        email,
                        role,
                        is_active: isActive
                    };
                    
                    if (password) {
                        data.password = password;
                    }
                    
                    const url = this.editingId 
                        ? `/api/users/${this.editingId}`
                        : '/api/users';
                    
                    const response = await fetch(url, {
                        method: this.editingId ? 'PUT' : 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.closeModal();
                        this.showAlert('user-alert', `User ${this.editingId ? 'updated' : 'created'} successfully!`, 'success');
                        this.loadUsers();
                    } else {
                        this.showAlert('user-alert', result.error || 'Failed to save user', 'error');
                    }
                } catch (error) {
                    console.error('[Users] Save failed:', error);
                    this.showAlert('user-alert', 'Failed to save user: ' + error.message, 'error');
                } finally {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;"><path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z"/></svg> Save User';
                }
            },
            
            async confirmDelete() {
                if (!this.deleteId) return;
                
                const confirmBtn = document.getElementById('user-delete-confirm');
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Deleting...';
                
                try {
                    const response = await fetch(`/api/users/${this.deleteId}`, {
                        method: 'DELETE'
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.closeDeleteModal();
                        this.showAlert('user-alert', 'User deleted successfully!', 'success');
                        this.loadUsers();
                    } else {
                        this.showAlert('user-alert', result.error || 'Failed to delete user', 'error');
                    }
                } catch (error) {
                    console.error('[Users] Delete failed:', error);
                    this.showAlert('user-alert', 'Failed to delete user: ' + error.message, 'error');
                } finally {
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = 'Delete User';
                }
            },
            
            showAlert(containerId, message, type = 'info') {
                const container = document.getElementById(containerId);
                if (!container) return;
                
                const alertClass = type === 'success' ? 'c-alert--success' : 
                                   type === 'error' ? 'c-alert--error' : 'c-alert--info';
                
                container.innerHTML = `
                    <div class="c-alert ${alertClass} is-visible">
                        ${this.escapeHtml(message)}
                    </div>
                `;
                
                if (type !== 'error') {
                    setTimeout(() => {
                        container.innerHTML = '';
                    }, 5000);
                }
            },
            
            escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        };
        
        // Initialize on DOMContentLoaded or immediately if already loaded
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => UsersModule.init());
        } else {
            UsersModule.init();
        }
        <?php
    }
];

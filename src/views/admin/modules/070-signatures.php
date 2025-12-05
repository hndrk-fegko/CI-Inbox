<?php
/**
 * Admin Tab Module: Email Signatures
 * 
 * Provides:
 * - Global/Shared Inbox Signatures (for team inbox replies)
 * - Personal Signatures (for user's personal IMAP workflow)
 * - Full CRUD for both types (admin can edit user signatures for support)
 * - Signature assignment and defaults
 * 
 * Auto-discovered by admin dashboard
 */

return [
    'id' => 'signatures',
    'title' => 'Signatures',
    'priority' => 70,
    'icon' => '<path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/>',
    
    'card' => function() {
        ?>
        <div class="c-admin-card" data-module="signatures" style="cursor: pointer;">
            <div class="c-admin-card__header">
                <div class="c-admin-card__icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="c-admin-card__title">Email Signatures</h3>
                </div>
            </div>
            <p class="c-admin-card__description">Manage shared inbox and personal email signatures.</p>
            <div class="c-admin-card__content">
                <div class="c-info-row">
                    <span class="c-info-row__label">Shared Inbox</span>
                    <span class="c-info-row__value" id="global-signature-count-card">—</span>
                </div>
                <div class="c-info-row">
                    <span class="c-info-row__label">Personal</span>
                    <span class="c-info-row__value" id="user-signature-count-card">—</span>
                </div>
            </div>
        </div>
        <?php
    },
    
    'content' => function() {
        ?>
        <div style="margin-bottom: 2rem;">
            <h3 style="margin: 0 0 0.5rem 0; font-size: 1.25rem; font-weight: 600;">Email Signatures</h3>
            <p style="margin: 0; color: #666; font-size: 0.875rem;">Manage signatures for shared inbox replies and personal email accounts.</p>
        </div>
        
        <!-- Info Box -->
        <div style="background: #E3F2FD; border-left: 4px solid #2196F3; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="#1976D2" style="flex-shrink: 0; margin-top: 2px;">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <strong style="color: #1565C0;">About Signatures</strong>
                    <p style="margin: 0.5rem 0 0 0; color: #1976D2; font-size: 0.875rem;">
                        <strong>Shared Inbox Signatures:</strong> Used when team members reply from the shared inbox. 
                        Ensures consistent branding across all team responses.<br>
                        <strong>Personal Signatures:</strong> Used when a user takes personal ownership of a thread 
                        and moves it to their personal email. Users manage these, but admin can edit for support.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Alert Container -->
        <div id="signature-alert" style="margin-bottom: 1rem;"></div>
        
        <!-- Signature Tabs -->
        <div style="display: flex; gap: 0; border-bottom: 2px solid #e0e0e0; margin-bottom: 1.5rem;">
            <button type="button" class="sig-tab active" data-tab="shared" style="padding: 0.75rem 1.5rem; background: none; border: none; border-bottom: 2px solid #2196F3; margin-bottom: -2px; cursor: pointer; font-weight: 500; color: #2196F3;">
                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem; vertical-align: middle;">
                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                </svg>
                Shared Inbox Signatures
            </button>
            <button type="button" class="sig-tab" data-tab="personal" style="padding: 0.75rem 1.5rem; background: none; border: none; border-bottom: 2px solid transparent; margin-bottom: -2px; cursor: pointer; font-weight: 500; color: #666;">
                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem; vertical-align: middle;">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                </svg>
                Personal Signatures
            </button>
        </div>
        
        <!-- Shared Inbox Signatures Tab -->
        <div id="sig-tab-shared" class="sig-tab-content">
            <div style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <div>
                        <h4 style="margin: 0 0 0.25rem 0;">Shared Inbox Signatures</h4>
                        <p style="margin: 0; color: #666; font-size: 0.875rem;">
                            These signatures are available to all team members when replying from the shared inbox.
                        </p>
                    </div>
                    <button type="button" id="sig-add-shared-btn" class="c-button c-button--primary">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                        </svg>
                        Add Signature
                    </button>
                </div>
                
                <!-- Default Signature Selection -->
                <div style="background: #f5f5f5; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Default Signature for Shared Inbox</label>
                    <select id="sig-shared-default" class="c-input" style="max-width: 300px;">
                        <option value="">-- No default --</option>
                    </select>
                    <small style="color: #666; display: block; margin-top: 0.25rem;">
                        This signature will be pre-selected when composing replies
                    </small>
                </div>
                
                <div id="sig-shared-list">
                    <div style="padding: 2rem; text-align: center; color: #666;">
                        Loading signatures...
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Personal Signatures Tab -->
        <div id="sig-tab-personal" class="sig-tab-content" style="display: none;">
            <div style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <div>
                        <h4 style="margin: 0 0 0.25rem 0;">Personal Signatures</h4>
                        <p style="margin: 0; color: #666; font-size: 0.875rem;">
                            User signatures for personal email workflow. Admin can edit for support/compliance.
                        </p>
                    </div>
                    <button type="button" id="sig-add-personal-btn" class="c-button c-button--secondary">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                        </svg>
                        Add for User
                    </button>
                </div>
                
                <!-- Warning for personal signatures -->
                <div style="background: #FFF3E0; border-left: 4px solid #FF9800; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem;">
                    <strong style="color: #E65100;">⚠️ Note:</strong>
                    <span style="color: #E65100; font-size: 0.875rem;">
                        Editing a user's personal signature will affect their emails. Consider notifying the user.
                    </span>
                </div>
                
                <!-- Filter by User -->
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Filter by User</label>
                    <select id="sig-personal-filter" class="c-input" style="max-width: 300px;">
                        <option value="">All Users</option>
                    </select>
                </div>
                
                <div id="sig-personal-list">
                    <div style="padding: 2rem; text-align: center; color: #666;">
                        Loading signatures...
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Add/Edit Signature Modal -->
        <div class="c-modal" id="sig-edit-modal">
            <div class="c-modal__content" style="max-width: 600px;">
                <div class="c-modal__header">
                    <h2 id="sig-modal-title">Add Signature</h2>
                    <button class="c-modal__close" id="sig-modal-close">&times;</button>
                </div>
                <div class="c-modal__body">
                    <input type="hidden" id="sig-edit-id">
                    <input type="hidden" id="sig-edit-type">
                    
                    <div class="c-input-group" style="margin-bottom: 1rem;">
                        <label for="sig-edit-name">Signature Name <span style="color: #f44336;">*</span></label>
                        <input type="text" id="sig-edit-name" class="c-input" placeholder="e.g., Standard Reply, Marketing Team">
                    </div>
                    
                    <div class="c-input-group" id="sig-user-select-group" style="margin-bottom: 1rem; display: none;">
                        <label for="sig-edit-user">Assign to User <span style="color: #f44336;">*</span></label>
                        <select id="sig-edit-user" class="c-input">
                            <option value="">-- Select User --</option>
                        </select>
                    </div>
                    
                    <div class="c-input-group" style="margin-bottom: 1rem;">
                        <label for="sig-edit-content">Signature Content <span style="color: #f44336;">*</span></label>
                        <textarea id="sig-edit-content" class="c-input" rows="8" placeholder="Enter your signature here...&#10;&#10;You can use HTML for formatting.&#10;&#10;Available variables:&#10;{{user.name}} - User's full name&#10;{{user.email}} - User's email&#10;{{date}} - Current date"></textarea>
                        <small style="color: #666;">HTML formatting is supported. Use variables for dynamic content.</small>
                    </div>
                    
                    <div class="c-input-group" style="margin-bottom: 1rem;">
                        <label>Preview</label>
                        <div id="sig-preview" style="background: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 8px; padding: 1rem; min-height: 100px; font-family: Arial, sans-serif; font-size: 14px;">
                            <em style="color: #999;">Preview will appear here...</em>
                        </div>
                    </div>
                </div>
                <div class="c-modal__footer">
                    <button type="button" class="c-button c-button--secondary" id="sig-modal-cancel">Cancel</button>
                    <button type="button" class="c-button c-button--primary" id="sig-modal-save">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                            <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z"/>
                        </svg>
                        Save Signature
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Delete Confirmation Modal -->
        <div class="c-modal" id="sig-delete-modal">
            <div class="c-modal__content" style="max-width: 400px;">
                <div class="c-modal__header">
                    <h2>Delete Signature</h2>
                    <button class="c-modal__close" id="sig-delete-close">&times;</button>
                </div>
                <div class="c-modal__body">
                    <p style="color: #666;">Are you sure you want to delete this signature?</p>
                    <p id="sig-delete-name" style="font-weight: 600;"></p>
                    <p style="color: #f44336; margin-bottom: 0;"><strong>This action cannot be undone.</strong></p>
                </div>
                <div class="c-modal__footer">
                    <button type="button" class="c-button c-button--secondary" id="sig-delete-cancel">Cancel</button>
                    <button type="button" class="c-button c-button--danger" id="sig-delete-confirm">Delete</button>
                </div>
            </div>
        </div>
        
        <style>
            .sig-tab:hover {
                color: #2196F3 !important;
            }
            .sig-tab.active {
                color: #2196F3 !important;
                border-bottom-color: #2196F3 !important;
            }
            .sig-item {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                padding: 1rem;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                margin-bottom: 0.75rem;
                background: white;
            }
            .sig-item:hover {
                border-color: #2196F3;
                box-shadow: 0 2px 8px rgba(33, 150, 243, 0.1);
            }
            .sig-item--personal {
                background: #FFFDE7;
                border-color: #FFF9C4;
            }
            .sig-item__info {
                flex: 1;
            }
            .sig-item__name {
                font-weight: 600;
                margin-bottom: 0.25rem;
            }
            .sig-item__meta {
                font-size: 0.875rem;
                color: #666;
            }
            .sig-item__preview {
                font-size: 0.875rem;
                color: #888;
                margin-top: 0.5rem;
                padding: 0.5rem;
                background: #f5f5f5;
                border-radius: 4px;
                max-height: 60px;
                overflow: hidden;
            }
            .sig-item__actions {
                display: flex;
                gap: 0.5rem;
            }
        </style>
        <?php
    },
    
    'script' => function() {
        ?>
        // Signatures Module State
        const SignaturesModule = {
            signatures: [],
            users: [],
            editingId: null,
            deleteId: null,
            currentTab: 'shared',
            
            init() {
                console.log('[Signatures] Initializing module...');
                this.loadSignatures();
                this.loadUsers();
                this.bindEvents();
            },
            
            bindEvents() {
                // Tab switching
                document.querySelectorAll('.sig-tab').forEach(tab => {
                    tab.addEventListener('click', () => this.switchTab(tab.dataset.tab));
                });
                
                // Add buttons
                const addSharedBtn = document.getElementById('sig-add-shared-btn');
                const addPersonalBtn = document.getElementById('sig-add-personal-btn');
                
                if (addSharedBtn) addSharedBtn.addEventListener('click', () => this.openAddModal('shared'));
                if (addPersonalBtn) addPersonalBtn.addEventListener('click', () => this.openAddModal('personal'));
                
                // Modal controls
                const modalClose = document.getElementById('sig-modal-close');
                const modalCancel = document.getElementById('sig-modal-cancel');
                const modalSave = document.getElementById('sig-modal-save');
                
                if (modalClose) modalClose.addEventListener('click', () => this.closeModal());
                if (modalCancel) modalCancel.addEventListener('click', () => this.closeModal());
                if (modalSave) modalSave.addEventListener('click', () => this.saveSignature());
                
                // Delete modal
                const deleteClose = document.getElementById('sig-delete-close');
                const deleteCancel = document.getElementById('sig-delete-cancel');
                const deleteConfirm = document.getElementById('sig-delete-confirm');
                
                if (deleteClose) deleteClose.addEventListener('click', () => this.closeDeleteModal());
                if (deleteCancel) deleteCancel.addEventListener('click', () => this.closeDeleteModal());
                if (deleteConfirm) deleteConfirm.addEventListener('click', () => this.confirmDelete());
                
                // Live preview
                const contentInput = document.getElementById('sig-edit-content');
                if (contentInput) {
                    contentInput.addEventListener('input', () => this.updatePreview());
                }
                
                // Filter
                const filter = document.getElementById('sig-personal-filter');
                if (filter) {
                    filter.addEventListener('change', () => this.renderPersonalSignatures());
                }
                
                // Default signature change
                const defaultSelect = document.getElementById('sig-shared-default');
                if (defaultSelect) {
                    defaultSelect.addEventListener('change', () => this.setDefaultSignature());
                }
            },
            
            switchTab(tab) {
                this.currentTab = tab;
                
                // Update tab buttons
                document.querySelectorAll('.sig-tab').forEach(t => {
                    t.classList.toggle('active', t.dataset.tab === tab);
                    t.style.borderBottomColor = t.dataset.tab === tab ? '#2196F3' : 'transparent';
                    t.style.color = t.dataset.tab === tab ? '#2196F3' : '#666';
                });
                
                // Show/hide content
                document.getElementById('sig-tab-shared').style.display = tab === 'shared' ? 'block' : 'none';
                document.getElementById('sig-tab-personal').style.display = tab === 'personal' ? 'block' : 'none';
            },
            
            async loadSignatures() {
                try {
                    const response = await fetch('/api/admin/signatures');
                    const data = await response.json();
                    
                    if (data.success && data.data) {
                        this.signatures = data.data;
                        this.renderSharedSignatures();
                        this.renderPersonalSignatures();
                        this.updateCardCounts();
                        this.updateDefaultSelect();
                    }
                } catch (error) {
                    console.error('[Signatures] Failed to load:', error);
                    this.showAlert('signature-alert', 'Failed to load signatures', 'error');
                }
            },
            
            async loadUsers() {
                try {
                    const response = await fetch('/api/users');
                    const data = await response.json();
                    
                    if (data.success && data.data) {
                        this.users = data.data;
                        this.updateUserSelects();
                    }
                } catch (error) {
                    console.error('[Signatures] Failed to load users:', error);
                }
            },
            
            updateUserSelects() {
                const userSelect = document.getElementById('sig-edit-user');
                const filterSelect = document.getElementById('sig-personal-filter');
                
                const options = this.users.map(u => 
                    `<option value="${u.id}">${this.escapeHtml(u.name || u.email)}</option>`
                ).join('');
                
                if (userSelect) {
                    userSelect.innerHTML = '<option value="">-- Select User --</option>' + options;
                }
                
                if (filterSelect) {
                    filterSelect.innerHTML = '<option value="">All Users</option>' + options;
                }
            },
            
            updateCardCounts() {
                const sharedCount = this.signatures.filter(s => s.is_global).length;
                const personalCount = this.signatures.filter(s => !s.is_global).length;
                
                const globalEl = document.getElementById('global-signature-count-card');
                const userEl = document.getElementById('user-signature-count-card');
                
                if (globalEl) globalEl.textContent = sharedCount;
                if (userEl) userEl.textContent = personalCount;
            },
            
            updateDefaultSelect() {
                const select = document.getElementById('sig-shared-default');
                if (!select) return;
                
                const shared = this.signatures.filter(s => s.is_global);
                const currentDefault = shared.find(s => s.is_default);
                
                select.innerHTML = '<option value="">-- No default --</option>' +
                    shared.map(s => 
                        `<option value="${s.id}" ${s.is_default ? 'selected' : ''}>${this.escapeHtml(s.name)}</option>`
                    ).join('');
            },
            
            renderSharedSignatures() {
                const container = document.getElementById('sig-shared-list');
                const shared = this.signatures.filter(s => s.is_global);
                
                if (shared.length === 0) {
                    container.innerHTML = `
                        <div style="padding: 2rem; text-align: center; color: #666;">
                            <svg width="48" height="48" viewBox="0 0 20 20" fill="currentColor" style="opacity: 0.3; margin-bottom: 0.5rem;">
                                <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"/>
                            </svg>
                            <p style="margin: 0;">No shared inbox signatures yet</p>
                            <p style="margin: 0.25rem 0 0 0; font-size: 0.875rem;">Click "Add Signature" to create one for the team</p>
                        </div>
                    `;
                    return;
                }
                
                container.innerHTML = shared.map(sig => this.renderSignatureItem(sig, 'shared')).join('');
                this.bindItemEvents();
            },
            
            renderPersonalSignatures() {
                const container = document.getElementById('sig-personal-list');
                const filterUserId = document.getElementById('sig-personal-filter')?.value;
                
                let personal = this.signatures.filter(s => !s.is_global);
                
                if (filterUserId) {
                    personal = personal.filter(s => s.user_id == filterUserId);
                }
                
                if (personal.length === 0) {
                    container.innerHTML = `
                        <div style="padding: 2rem; text-align: center; color: #666;">
                            <svg width="48" height="48" viewBox="0 0 20 20" fill="currentColor" style="opacity: 0.3; margin-bottom: 0.5rem;">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                            </svg>
                            <p style="margin: 0;">No personal signatures found</p>
                            <p style="margin: 0.25rem 0 0 0; font-size: 0.875rem;">Users can create signatures in their profile, or admin can add one</p>
                        </div>
                    `;
                    return;
                }
                
                container.innerHTML = personal.map(sig => this.renderSignatureItem(sig, 'personal')).join('');
                this.bindItemEvents();
            },
            
            renderSignatureItem(sig, type) {
                const userName = sig.user_name || this.users.find(u => u.id === sig.user_id)?.name || 'Unknown User';
                const preview = (sig.content || '').replace(/<[^>]*>/g, '').substring(0, 100);
                
                return `
                    <div class="sig-item ${type === 'personal' ? 'sig-item--personal' : ''}" data-id="${sig.id}">
                        <div class="sig-item__info">
                            <div class="sig-item__name">
                                ${this.escapeHtml(sig.name)}
                                ${sig.is_default ? '<span class="c-badge" style="background: #E3F2FD; color: #1565C0; margin-left: 0.5rem; font-size: 0.75rem;">Default</span>' : ''}
                            </div>
                            <div class="sig-item__meta">
                                ${type === 'personal' ? `<strong>User:</strong> ${this.escapeHtml(userName)} • ` : ''}
                                Created: ${sig.created_at || '—'}
                            </div>
                            <div class="sig-item__preview">${this.escapeHtml(preview)}${preview.length >= 100 ? '...' : ''}</div>
                        </div>
                        <div class="sig-item__actions">
                            <button type="button" class="c-button c-button--secondary sig-edit-btn" data-id="${sig.id}" data-type="${type}" style="padding: 0.375rem 0.75rem; font-size: 0.875rem;">
                                <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                </svg>
                            </button>
                            <button type="button" class="c-button c-button--danger sig-delete-btn" data-id="${sig.id}" data-name="${this.escapeHtml(sig.name)}" style="padding: 0.375rem 0.75rem; font-size: 0.875rem;">
                                <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                `;
            },
            
            bindItemEvents() {
                // Edit buttons
                document.querySelectorAll('.sig-edit-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const id = btn.dataset.id;
                        const type = btn.dataset.type;
                        this.openEditModal(id, type);
                    });
                });
                
                // Delete buttons
                document.querySelectorAll('.sig-delete-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        this.deleteId = btn.dataset.id;
                        document.getElementById('sig-delete-name').textContent = btn.dataset.name;
                        document.getElementById('sig-delete-modal').classList.add('show');
                    });
                });
            },
            
            openAddModal(type) {
                this.editingId = null;
                document.getElementById('sig-edit-id').value = '';
                document.getElementById('sig-edit-type').value = type;
                document.getElementById('sig-edit-name').value = '';
                document.getElementById('sig-edit-content').value = '';
                document.getElementById('sig-edit-user').value = '';
                document.getElementById('sig-modal-title').textContent = type === 'shared' ? 'Add Shared Inbox Signature' : 'Add Personal Signature';
                document.getElementById('sig-user-select-group').style.display = type === 'personal' ? 'block' : 'none';
                this.updatePreview();
                document.getElementById('sig-edit-modal').classList.add('show');
            },
            
            openEditModal(id, type) {
                const sig = this.signatures.find(s => s.id == id);
                if (!sig) return;
                
                this.editingId = id;
                document.getElementById('sig-edit-id').value = id;
                document.getElementById('sig-edit-type').value = type;
                document.getElementById('sig-edit-name').value = sig.name || '';
                document.getElementById('sig-edit-content').value = sig.content || '';
                document.getElementById('sig-edit-user').value = sig.user_id || '';
                document.getElementById('sig-modal-title').textContent = type === 'shared' ? 'Edit Shared Inbox Signature' : 'Edit Personal Signature';
                document.getElementById('sig-user-select-group').style.display = type === 'personal' ? 'block' : 'none';
                this.updatePreview();
                document.getElementById('sig-edit-modal').classList.add('show');
            },
            
            closeModal() {
                document.getElementById('sig-edit-modal').classList.remove('show');
                this.editingId = null;
            },
            
            closeDeleteModal() {
                document.getElementById('sig-delete-modal').classList.remove('show');
                this.deleteId = null;
            },
            
            updatePreview() {
                const content = document.getElementById('sig-edit-content')?.value || '';
                const preview = document.getElementById('sig-preview');
                
                if (preview) {
                    if (content.trim()) {
                        // Replace variables with sample data
                        let previewContent = content
                            .replace(/\{\{user\.name\}\}/g, 'John Doe')
                            .replace(/\{\{user\.email\}\}/g, 'john.doe@example.com')
                            .replace(/\{\{date\}\}/g, new Date().toLocaleDateString());
                        
                        preview.innerHTML = previewContent;
                    } else {
                        preview.innerHTML = '<em style="color: #999;">Preview will appear here...</em>';
                    }
                }
            },
            
            async saveSignature() {
                const saveBtn = document.getElementById('sig-modal-save');
                const name = document.getElementById('sig-edit-name')?.value?.trim();
                const content = document.getElementById('sig-edit-content')?.value?.trim();
                const type = document.getElementById('sig-edit-type')?.value;
                const userId = document.getElementById('sig-edit-user')?.value;
                
                if (!name) {
                    this.showAlert('signature-alert', 'Please enter a signature name', 'error');
                    return;
                }
                
                if (!content) {
                    this.showAlert('signature-alert', 'Please enter signature content', 'error');
                    return;
                }
                
                if (type === 'personal' && !userId && !this.editingId) {
                    this.showAlert('signature-alert', 'Please select a user for personal signature', 'error');
                    return;
                }
                
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
                
                try {
                    const data = {
                        name,
                        content,
                        is_global: type === 'shared',
                        user_id: type === 'personal' ? userId : null
                    };
                    
                    const url = this.editingId 
                        ? `/api/admin/signatures/${this.editingId}`
                        : '/api/admin/signatures';
                    
                    const response = await fetch(url, {
                        method: this.editingId ? 'PUT' : 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.closeModal();
                        this.showAlert('signature-alert', `Signature ${this.editingId ? 'updated' : 'created'} successfully!`, 'success');
                        this.loadSignatures();
                    } else {
                        this.showAlert('signature-alert', result.error || 'Failed to save signature', 'error');
                    }
                } catch (error) {
                    console.error('[Signatures] Save failed:', error);
                    this.showAlert('signature-alert', 'Failed to save signature: ' + error.message, 'error');
                } finally {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;"><path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z"/></svg> Save Signature';
                }
            },
            
            async confirmDelete() {
                if (!this.deleteId) return;
                
                const confirmBtn = document.getElementById('sig-delete-confirm');
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Deleting...';
                
                try {
                    const response = await fetch(`/api/admin/signatures/${this.deleteId}`, {
                        method: 'DELETE'
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.closeDeleteModal();
                        this.showAlert('signature-alert', 'Signature deleted successfully!', 'success');
                        this.loadSignatures();
                    } else {
                        this.showAlert('signature-alert', result.error || 'Failed to delete signature', 'error');
                    }
                } catch (error) {
                    console.error('[Signatures] Delete failed:', error);
                    this.showAlert('signature-alert', 'Failed to delete signature: ' + error.message, 'error');
                } finally {
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = 'Delete';
                }
            },
            
            async setDefaultSignature() {
                const select = document.getElementById('sig-shared-default');
                const sigId = select?.value;
                
                try {
                    const response = await fetch('/api/admin/signatures/default', {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ signature_id: sigId || null })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.showAlert('signature-alert', 'Default signature updated!', 'success');
                        this.loadSignatures();
                    }
                } catch (error) {
                    console.error('[Signatures] Set default failed:', error);
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
            document.addEventListener('DOMContentLoaded', () => SignaturesModule.init());
        } else {
            SignaturesModule.init();
        }
        <?php
    }
];

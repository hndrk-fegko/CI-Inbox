<?php
/**
 * Admin Tab Module: Backup Management
 * 
 * Provides:
 * - Database backup creation
 * - Backup list with download/delete
 * - Auto-cleanup of old backups
 * - Backup statistics
 * 
 * Auto-discovered by admin dashboard
 */

return [
    'id' => 'backup',
    'title' => 'Backup',
    'priority' => 40,
    'icon' => '<path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/>',
    
    'card' => function() {
        ?>
        <div class="c-admin-card" data-module="backup" style="cursor: pointer;">
            <div class="c-admin-card__header">
                <div class="c-admin-card__icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="c-admin-card__title">Backup System</h3>
                </div>
            </div>
            <p class="c-admin-card__description">Manage database backups and configure automated backup schedules.</p>
            <div class="c-admin-card__content">
                <div class="c-info-row">
                    <span class="c-info-row__label">Latest Backup</span>
                    <span class="c-info-row__value" id="latest-backup-date">Never</span>
                </div>
                <div class="c-info-row">
                    <span class="c-info-row__label">Total Backups</span>
                    <span class="c-info-row__value" id="total-backups-count">0</span>
                </div>
            </div>
        </div>
        <?php
    },
    
    'content' => function() {
        ?>
        <div style="margin-bottom: 2rem;">
            <h3 style="margin: 0 0 0.5rem 0; font-size: 1.25rem; font-weight: 600;">Backup Management</h3>
            <p style="margin: 0; color: #666; font-size: 0.875rem;">Create, download, and manage database backups.</p>
        </div>
        
        <!-- Info Box -->
        <div style="background: #E3F2FD; border-left: 4px solid #2196F3; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
            <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="#1976D2" style="flex-shrink: 0; margin-top: 2px;">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <strong style="color: #1565C0;">About Backups</strong>
                    <p style="margin: 0.5rem 0 0 0; color: #1976D2; font-size: 0.875rem;">
                        Backups include your entire database: users, threads, emails, settings, and configuration. 
                        We recommend creating backups before major changes and keeping at least 3 recent backups.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Alert Container -->
        <div id="backup-alert" style="margin-bottom: 1rem;"></div>
        
        <!-- Actions Panel -->
        <div style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h4 style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 10.586V7z" clip-rule="evenodd"/>
                    </svg>
                    Quick Actions
                </h4>
            </div>
            
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <button type="button" id="backup-create-btn" class="c-button c-button--primary">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                    </svg>
                    Create Backup Now
                </button>
                <button type="button" id="backup-cleanup-btn" class="c-button c-button--secondary">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    Cleanup Old Backups
                </button>
            </div>
        </div>
        
        <!-- Backup List -->
        <div style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h4 style="margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z"/>
                        <path fill-rule="evenodd" d="M3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm5 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" clip-rule="evenodd"/>
                    </svg>
                    Available Backups
                </h4>
                <button type="button" id="backup-refresh-btn" class="c-button c-button--secondary" style="font-size: 0.875rem;">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                        <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                    </svg>
                    Refresh
                </button>
            </div>
            
            <div id="backup-list-container">
                <div style="padding: 2rem; text-align: center; color: #666;">
                    Loading backups...
                </div>
            </div>
        </div>
        
        <!-- Cleanup Modal -->
        <div class="c-modal" id="backup-cleanup-modal">
            <div class="c-modal__content" style="max-width: 450px;">
                <div class="c-modal__header">
                    <h2>Cleanup Old Backups</h2>
                    <button class="c-modal__close" id="backup-cleanup-close">&times;</button>
                </div>
                <div class="c-modal__body">
                    <p style="color: #666; margin-bottom: 1.5rem;">
                        Delete backups older than a certain number of days. This cannot be undone.
                    </p>
                    <div class="c-input-group">
                        <label for="backup-retention-days">Keep backups from last</label>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="number" id="backup-retention-days" class="c-input" value="30" min="1" max="365" style="width: 100px;">
                            <span style="color: #666;">days</span>
                        </div>
                    </div>
                    <div style="background: #FFF3E0; border-left: 4px solid #FF9800; padding: 0.75rem; border-radius: 4px; margin-top: 1rem;">
                        <strong style="color: #E65100;">⚠️ Warning:</strong>
                        <p style="margin: 0.25rem 0 0 0; color: #E65100; font-size: 0.875rem;">
                            This will permanently delete older backups. Make sure you have recent backups before proceeding.
                        </p>
                    </div>
                </div>
                <div class="c-modal__footer">
                    <button type="button" class="c-button c-button--secondary" id="backup-cleanup-cancel">Cancel</button>
                    <button type="button" class="c-button c-button--danger" id="backup-cleanup-submit">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        Delete Old Backups
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Delete Confirmation Modal -->
        <div class="c-modal" id="backup-delete-modal">
            <div class="c-modal__content" style="max-width: 400px;">
                <div class="c-modal__header">
                    <h2>Delete Backup</h2>
                    <button class="c-modal__close" id="backup-delete-close">&times;</button>
                </div>
                <div class="c-modal__body">
                    <p style="color: #666;">Are you sure you want to delete this backup?</p>
                    <p id="backup-delete-filename" style="font-weight: 600; word-break: break-all;"></p>
                    <p style="color: #f44336; margin-bottom: 0;"><strong>This action cannot be undone.</strong></p>
                </div>
                <div class="c-modal__footer">
                    <button type="button" class="c-button c-button--secondary" id="backup-delete-cancel">Cancel</button>
                    <button type="button" class="c-button c-button--danger" id="backup-delete-submit">Delete</button>
                </div>
            </div>
        </div>
        <?php
    },
    
    'script' => function() {
        ?>
        // Backup Module State
        const BackupModule = {
            backups: [],
            deleteFilename: null,
            
            init() {
                console.log('[Backup] Initializing module...');
                this.loadBackups();
                this.bindEvents();
            },
            
            bindEvents() {
                // Create backup
                const createBtn = document.getElementById('backup-create-btn');
                if (createBtn) {
                    createBtn.addEventListener('click', () => this.createBackup());
                }
                
                // Refresh list
                const refreshBtn = document.getElementById('backup-refresh-btn');
                if (refreshBtn) {
                    refreshBtn.addEventListener('click', () => this.loadBackups());
                }
                
                // Cleanup modal
                const cleanupBtn = document.getElementById('backup-cleanup-btn');
                const cleanupClose = document.getElementById('backup-cleanup-close');
                const cleanupCancel = document.getElementById('backup-cleanup-cancel');
                const cleanupSubmit = document.getElementById('backup-cleanup-submit');
                
                if (cleanupBtn) cleanupBtn.addEventListener('click', () => this.openCleanupModal());
                if (cleanupClose) cleanupClose.addEventListener('click', () => this.closeCleanupModal());
                if (cleanupCancel) cleanupCancel.addEventListener('click', () => this.closeCleanupModal());
                if (cleanupSubmit) cleanupSubmit.addEventListener('click', () => this.runCleanup());
                
                // Delete modal
                const deleteClose = document.getElementById('backup-delete-close');
                const deleteCancel = document.getElementById('backup-delete-cancel');
                const deleteSubmit = document.getElementById('backup-delete-submit');
                
                if (deleteClose) deleteClose.addEventListener('click', () => this.closeDeleteModal());
                if (deleteCancel) deleteCancel.addEventListener('click', () => this.closeDeleteModal());
                if (deleteSubmit) deleteSubmit.addEventListener('click', () => this.confirmDelete());
            },
            
            async loadBackups() {
                const container = document.getElementById('backup-list-container');
                container.innerHTML = '<div style="padding: 2rem; text-align: center; color: #666;">Loading...</div>';
                
                try {
                    const response = await fetch('/api/admin/backup/list');
                    const data = await response.json();
                    
                    if (data.success) {
                        this.backups = data.data || [];
                        this.renderBackups();
                        this.updateCardStatus();
                    } else {
                        container.innerHTML = `<div style="padding: 2rem; text-align: center; color: #f44336;">${this.escapeHtml(data.error || 'Failed to load backups')}</div>`;
                    }
                } catch (error) {
                    console.error('[Backup] Failed to load:', error);
                    container.innerHTML = '<div style="padding: 2rem; text-align: center; color: #f44336;">Failed to load backups</div>';
                }
            },
            
            renderBackups() {
                const container = document.getElementById('backup-list-container');
                
                if (!this.backups || this.backups.length === 0) {
                    container.innerHTML = `
                        <div style="padding: 2rem; text-align: center; color: #666;">
                            <svg width="48" height="48" viewBox="0 0 20 20" fill="currentColor" style="opacity: 0.3; margin-bottom: 0.5rem;">
                                <path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z"/>
                                <path fill-rule="evenodd" d="M3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm5 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <p style="margin: 0;">No backups found. Click "Create Backup Now" to create your first backup.</p>
                        </div>
                    `;
                    return;
                }
                
                container.innerHTML = `
                    <div class="table-responsive">
                        <table class="table" style="margin: 0;">
                            <thead style="background: #f8f9fa;">
                                <tr>
                                    <th style="font-weight: 600; color: #666; text-transform: uppercase; font-size: 0.75rem; padding: 0.75rem 1rem; border: none;">Filename</th>
                                    <th style="font-weight: 600; color: #666; text-transform: uppercase; font-size: 0.75rem; padding: 0.75rem 1rem; border: none;">Size</th>
                                    <th style="font-weight: 600; color: #666; text-transform: uppercase; font-size: 0.75rem; padding: 0.75rem 1rem; border: none;">Created</th>
                                    <th style="font-weight: 600; color: #666; text-transform: uppercase; font-size: 0.75rem; padding: 0.75rem 1rem; border: none; text-align: right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${this.backups.map(backup => `
                                    <tr>
                                        <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #eee; font-size: 0.875rem;">
                                            <code style="background: #f5f5f5; padding: 0.125rem 0.375rem; border-radius: 4px; font-size: 0.8125rem;">
                                                ${this.escapeHtml(backup.filename)}
                                            </code>
                                        </td>
                                        <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #eee; font-size: 0.875rem;">
                                            ${this.escapeHtml(backup.size_human || '—')}
                                        </td>
                                        <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #eee; font-size: 0.875rem;">
                                            ${this.escapeHtml(backup.created_at_human || backup.created_at || '—')}
                                        </td>
                                        <td style="padding: 0.75rem 1rem; border-bottom: 1px solid #eee; text-align: right;">
                                            <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                                <a href="/api/admin/backup/download/${encodeURIComponent(backup.filename)}" 
                                                   class="c-button c-button--secondary" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">
                                                    Download
                                                </a>
                                                <button type="button" 
                                                        class="c-button c-button--danger" 
                                                        style="font-size: 0.75rem; padding: 0.25rem 0.5rem;"
                                                        onclick="BackupModule.openDeleteModal('${this.escapeHtml(backup.filename)}')">
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            },
            
            updateCardStatus() {
                const latestEl = document.getElementById('latest-backup-date');
                const countEl = document.getElementById('total-backups-count');
                
                if (countEl) {
                    countEl.textContent = this.backups.length;
                }
                
                if (latestEl && this.backups.length > 0) {
                    latestEl.textContent = this.backups[0].created_at_human || 'Unknown';
                } else if (latestEl) {
                    latestEl.textContent = 'Never';
                }
            },
            
            async createBackup() {
                const createBtn = document.getElementById('backup-create-btn');
                createBtn.disabled = true;
                createBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Creating...';
                
                try {
                    const response = await fetch('/api/admin/backup/create', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' }
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showAlert('backup-alert', 'Backup created successfully!', 'success');
                        this.loadBackups();
                    } else {
                        this.showAlert('backup-alert', data.error || 'Failed to create backup', 'error');
                    }
                } catch (error) {
                    console.error('[Backup] Create failed:', error);
                    this.showAlert('backup-alert', 'Failed to create backup: ' + error.message, 'error');
                } finally {
                    createBtn.disabled = false;
                    createBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg> Create Backup Now';
                }
            },
            
            openCleanupModal() {
                document.getElementById('backup-cleanup-modal').classList.add('show');
            },
            
            closeCleanupModal() {
                document.getElementById('backup-cleanup-modal').classList.remove('show');
            },
            
            async runCleanup() {
                const retentionDays = document.getElementById('backup-retention-days').value;
                const submitBtn = document.getElementById('backup-cleanup-submit');
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Deleting...';
                
                try {
                    const response = await fetch('/api/admin/backup/cleanup', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ retention_days: parseInt(retentionDays) })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        const count = data.data?.deleted_count || 0;
                        this.showAlert('backup-alert', `Cleanup complete. ${count} backup(s) deleted.`, 'success');
                        this.closeCleanupModal();
                        this.loadBackups();
                    } else {
                        this.showAlert('backup-alert', data.error || 'Cleanup failed', 'error');
                    }
                } catch (error) {
                    console.error('[Backup] Cleanup failed:', error);
                    this.showAlert('backup-alert', 'Cleanup failed: ' + error.message, 'error');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/></svg> Delete Old Backups';
                }
            },
            
            openDeleteModal(filename) {
                this.deleteFilename = filename;
                document.getElementById('backup-delete-filename').textContent = filename;
                document.getElementById('backup-delete-modal').classList.add('show');
            },
            
            closeDeleteModal() {
                document.getElementById('backup-delete-modal').classList.remove('show');
                this.deleteFilename = null;
            },
            
            async confirmDelete() {
                if (!this.deleteFilename) return;
                
                const submitBtn = document.getElementById('backup-delete-submit');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Deleting...';
                
                try {
                    const response = await fetch(`/api/admin/backup/delete/${encodeURIComponent(this.deleteFilename)}`, {
                        method: 'DELETE'
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.showAlert('backup-alert', 'Backup deleted successfully', 'success');
                        this.closeDeleteModal();
                        this.loadBackups();
                    } else {
                        this.showAlert('backup-alert', data.error || 'Failed to delete backup', 'error');
                    }
                } catch (error) {
                    console.error('[Backup] Delete failed:', error);
                    this.showAlert('backup-alert', 'Failed to delete backup: ' + error.message, 'error');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Delete';
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
            document.addEventListener('DOMContentLoaded', () => BackupModule.init());
        } else {
            BackupModule.init();
        }
        <?php
    }
];

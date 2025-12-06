<?php
/**
 * Admin Tab Module: Backup Management
 * 
 * Provides:
 * - Database backup creation (local/external/both)
 * - External storage configuration (FTP/WebDAV)
 * - Backup list with download/delete and location indicators
 * - Auto-backup scheduling with retention settings
 * - Keep monthly backups option
 * - Bulk cleanup of old backups
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
            <p class="c-admin-card__description">Manage database backups, external storage, and automated schedules.</p>
            <div class="c-admin-card__content">
                <div class="c-info-row">
                    <span class="c-info-row__label">Latest Backup</span>
                    <span class="c-info-row__value" id="latest-backup-date">Never</span>
                </div>
                <div class="c-info-row">
                    <span class="c-info-row__label">Total Backups</span>
                    <span class="c-info-row__value" id="total-backups-count">0</span>
                </div>
                <div class="c-info-row">
                    <span class="c-info-row__label">External Storage</span>
                    <span id="backup-external-status" class="c-status-badge c-status-badge--warning">
                        <span class="status-dot"></span>
                        Not Configured
                    </span>
                </div>
            </div>
        </div>
        <?php
    },
    
    'content' => function() {
        ?>
        <div style="margin-bottom: 2rem;">
            <h3 style="margin: 0 0 0.5rem 0; font-size: 1.25rem; font-weight: 600;">Backup Management</h3>
            <p style="margin: 0; color: #666; font-size: 0.875rem;">Create, download, and manage database backups with local and external storage options.</p>
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
                        Configure external storage (FTP/WebDAV) for off-site backup copies.
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Alert Container -->
        <div id="backup-alert" style="margin-bottom: 1rem;"></div>
        
        <!-- Create Backup Panel -->
        <div style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 1.5rem;">
            <h4 style="margin: 0 0 1rem 0; display: flex; align-items: center; gap: 0.5rem;">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/>
                </svg>
                Create Backup
            </h4>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                <div class="c-input-group">
                    <label for="backup-type">Backup Type</label>
                    <select id="backup-type" class="c-input">
                        <option value="full">Full Backup (Database + Files)</option>
                        <option value="database">Database Only</option>
                        <option value="files">Files Only</option>
                    </select>
                </div>
                <div class="c-input-group">
                    <label for="backup-location">Storage Location</label>
                    <select id="backup-location" class="c-input">
                        <option value="local">Local Only</option>
                        <option value="external" id="backup-location-external" disabled>External Only</option>
                        <option value="both" id="backup-location-both" disabled>Both (Local + External)</option>
                    </select>
                    <small style="color: #666;">Configure external storage below to enable</small>
                </div>
            </div>
            
            <div class="c-input-group" style="margin-bottom: 1rem;">
                <label for="backup-description">Description (optional)</label>
                <input type="text" id="backup-description" class="c-input" placeholder="e.g., Before migration, Weekly backup...">
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
        <div style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 1.5rem;">
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
            
            <!-- Location Legend -->
            <div style="margin-bottom: 1rem; padding: 0.75rem; background: #f5f5f5; border-radius: 8px; font-size: 0.875rem;">
                <strong>Location Icons:</strong>
                <span style="margin-left: 1rem;">💾 Local</span>
                <span style="margin-left: 1rem;">☁️ External</span>
                <span style="margin-left: 1rem;">📌 Monthly (Protected)</span>
            </div>
            
            <div id="backup-list-container">
                <div style="padding: 2rem; text-align: center; color: #666;">
                    Loading backups...
                </div>
            </div>
        </div>
        
        <!-- Auto-Backup Schedule -->
        <div style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 1.5rem;">
            <h4 style="margin: 0 0 1rem 0; display: flex; align-items: center; gap: 0.5rem;">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                </svg>
                Auto-Backup Schedule
            </h4>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                <div class="c-input-group">
                    <label>
                        <input type="checkbox" id="backup-auto-enabled">
                        Enable Automatic Backups
                    </label>
                </div>
            </div>
            
            <div id="backup-schedule-options" style="display: none;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                    <div class="c-input-group">
                        <label for="backup-schedule-frequency">Frequency</label>
                        <select id="backup-schedule-frequency" class="c-input">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <div class="c-input-group">
                        <label for="backup-schedule-time">Time (24h)</label>
                        <input type="time" id="backup-schedule-time" class="c-input" value="03:00">
                    </div>
                    <div class="c-input-group">
                        <label for="backup-schedule-retention">Retention (days)</label>
                        <input type="number" id="backup-schedule-retention" class="c-input" value="30" min="1" max="365">
                    </div>
                    <div class="c-input-group">
                        <label for="backup-schedule-location">Location</label>
                        <select id="backup-schedule-location" class="c-input">
                            <option value="local">Local Only</option>
                            <option value="external" disabled>External Only</option>
                            <option value="both" disabled>Both</option>
                        </select>
                    </div>
                </div>
                
                <!-- Keep Monthly Option -->
                <div style="background: #E8F5E9; border-left: 4px solid #4CAF50; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                    <label style="display: flex; align-items: flex-start; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" id="backup-keep-monthly" style="margin-top: 3px;">
                        <div>
                            <strong style="color: #2E7D32;">Keep Monthly Backups Forever</strong>
                            <p style="margin: 0.25rem 0 0 0; color: #388E3C; font-size: 0.875rem;">
                                Automatically preserve the last backup of each month. These will be excluded from cleanup 
                                and must be deleted manually. Only applies to external storage.
                            </p>
                        </div>
                    </label>
                </div>
                
                <button type="button" id="backup-save-schedule-btn" class="c-button c-button--primary">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                        <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z"/>
                    </svg>
                    Save Schedule
                </button>
            </div>
        </div>
        
        <!-- External Storage Configuration -->
        <div style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 1.5rem;">
            <h4 style="margin: 0 0 1rem 0; display: flex; align-items: center; gap: 0.5rem;">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M5.5 16a3.5 3.5 0 01-.369-6.98 4 4 0 117.753-1.977A4.5 4.5 0 1113.5 16h-8z"/>
                </svg>
                External Storage Configuration
            </h4>
            
            <div class="c-input-group" style="margin-bottom: 1rem;">
                <label for="backup-storage-type">Storage Type</label>
                <select id="backup-storage-type" class="c-input" style="max-width: 300px;">
                    <option value="">-- Select Storage Type --</option>
                    <option value="ftp">FTP / SFTP</option>
                    <option value="webdav">WebDAV (Nextcloud, etc.)</option>
                </select>
            </div>
            
            <!-- FTP Configuration -->
            <div id="backup-ftp-config" style="display: none;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                    <div class="c-input-group">
                        <label for="backup-ftp-host">FTP Host <span style="color: #f44336;">*</span></label>
                        <input type="text" id="backup-ftp-host" class="c-input" placeholder="ftp.example.com">
                    </div>
                    <div class="c-input-group">
                        <label for="backup-ftp-port">Port</label>
                        <input type="number" id="backup-ftp-port" class="c-input" value="21" placeholder="21">
                    </div>
                    <div class="c-input-group">
                        <label for="backup-ftp-username">Username <span style="color: #f44336;">*</span></label>
                        <input type="text" id="backup-ftp-username" class="c-input" placeholder="username">
                    </div>
                    <div class="c-input-group">
                        <label for="backup-ftp-password">Password <span style="color: #f44336;">*</span></label>
                        <input type="password" id="backup-ftp-password" class="c-input" placeholder="Leave empty to keep current">
                    </div>
                </div>
                <div class="c-input-group" style="margin-bottom: 1rem;">
                    <label for="backup-ftp-path">Remote Path</label>
                    <input type="text" id="backup-ftp-path" class="c-input" placeholder="/backups/ci-inbox/" style="max-width: 400px;">
                    <small style="color: #666;">Directory on FTP server where backups will be stored</small>
                </div>
                <div class="c-input-group" style="margin-bottom: 1rem;">
                    <label>
                        <input type="checkbox" id="backup-ftp-ssl">
                        Use FTPS (FTP over SSL)
                    </label>
                </div>
            </div>
            
            <!-- WebDAV Configuration -->
            <div id="backup-webdav-config" style="display: none;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                    <div class="c-input-group">
                        <label for="backup-webdav-url">WebDAV URL <span style="color: #f44336;">*</span></label>
                        <input type="url" id="backup-webdav-url" class="c-input" placeholder="https://cloud.example.com/remote.php/dav/files/user/">
                    </div>
                    <div class="c-input-group">
                        <label for="backup-webdav-username">Username <span style="color: #f44336;">*</span></label>
                        <input type="text" id="backup-webdav-username" class="c-input" placeholder="username">
                    </div>
                    <div class="c-input-group">
                        <label for="backup-webdav-password">Password <span style="color: #f44336;">*</span></label>
                        <input type="password" id="backup-webdav-password" class="c-input" placeholder="Leave empty to keep current">
                    </div>
                </div>
                <div class="c-input-group" style="margin-bottom: 1rem;">
                    <label for="backup-webdav-path">Remote Path</label>
                    <input type="text" id="backup-webdav-path" class="c-input" placeholder="/Backups/CI-Inbox/" style="max-width: 400px;">
                    <small style="color: #666;">Folder path on WebDAV server</small>
                </div>
            </div>
            
            <div id="backup-storage-actions" style="display: none; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee;">
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <button type="button" id="backup-test-storage-btn" class="c-button c-button--secondary">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Test Connection
                    </button>
                    <button type="button" id="backup-save-storage-btn" class="c-button c-button--primary">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                            <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z"/>
                        </svg>
                        Save Storage Configuration
                    </button>
                    <button type="button" id="backup-remove-storage-btn" class="c-button c-button--danger">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        Remove Configuration
                    </button>
                </div>
            </div>
            
            <!-- Storage Test Result -->
            <div id="backup-storage-test-result" style="display: none; margin-top: 1rem;"></div>
        </div>
        
        <!-- Storage Usage -->
        <div style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h4 style="margin: 0 0 1rem 0; display: flex; align-items: center; gap: 0.5rem;">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"/>
                </svg>
                Storage Usage
            </h4>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div style="background: #f5f5f5; padding: 1rem; border-radius: 8px;">
                    <div style="font-size: 0.875rem; color: #666; margin-bottom: 0.25rem;">Local Storage</div>
                    <div id="backup-local-usage" style="font-size: 1.25rem; font-weight: 600;">—</div>
                </div>
                <div style="background: #f5f5f5; padding: 1rem; border-radius: 8px;">
                    <div style="font-size: 0.875rem; color: #666; margin-bottom: 0.25rem;">External Storage</div>
                    <div id="backup-external-usage" style="font-size: 1.25rem; font-weight: 600;">Not configured</div>
                </div>
                <div style="background: #f5f5f5; padding: 1rem; border-radius: 8px;">
                    <div style="font-size: 0.875rem; color: #666; margin-bottom: 0.25rem;">Monthly Backups</div>
                    <div id="backup-monthly-count" style="font-size: 1.25rem; font-weight: 600;">0</div>
                </div>
                <div style="background: #f5f5f5; padding: 1rem; border-radius: 8px;">
                    <div style="font-size: 0.875rem; color: #666; margin-bottom: 0.25rem;">Oldest Backup</div>
                    <div id="backup-oldest" style="font-size: 1.25rem; font-weight: 600;">—</div>
                </div>
            </div>
            
            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #eee;">
                <button type="button" id="backup-cleanup-monthly-btn" class="c-button c-button--secondary">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    Cleanup Monthly Backups (&gt;18 months)
                </button>
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
            schedule: null,
            
            init() {
                console.log('[Backup] Initializing module...');
                this.loadBackups();
                this.loadSchedule();
                this.loadStorageUsage();
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
                
                // Schedule events
                const autoEnabledCheckbox = document.getElementById('backup-auto-enabled');
                const scheduleOptions = document.getElementById('backup-schedule-options');
                const saveScheduleBtn = document.getElementById('backup-save-schedule-btn');
                
                if (autoEnabledCheckbox && scheduleOptions) {
                    autoEnabledCheckbox.addEventListener('change', (e) => {
                        scheduleOptions.style.display = e.target.checked ? 'block' : 'none';
                    });
                }
                
                if (saveScheduleBtn) {
                    saveScheduleBtn.addEventListener('click', () => this.saveSchedule());
                }
                
                // Storage type toggle
                const storageTypeSelect = document.getElementById('backup-storage-type');
                if (storageTypeSelect) {
                    storageTypeSelect.addEventListener('change', (e) => this.toggleStorageConfig(e.target.value));
                }
            },
            
            async loadSchedule() {
                try {
                    const response = await fetch('/api/admin/backup/schedule');
                    const data = await response.json();
                    
                    if (data.success && data.data) {
                        this.schedule = data.data;
                        this.updateScheduleUI();
                    }
                } catch (error) {
                    console.error('[Backup] Failed to load schedule:', error);
                }
            },
            
            updateScheduleUI() {
                if (!this.schedule) return;
                
                const autoEnabled = document.getElementById('backup-auto-enabled');
                const scheduleOptions = document.getElementById('backup-schedule-options');
                const frequencySelect = document.getElementById('backup-schedule-frequency');
                const timeInput = document.getElementById('backup-schedule-time');
                const retentionInput = document.getElementById('backup-schedule-retention');
                const keepMonthly = document.getElementById('backup-keep-monthly');
                
                if (autoEnabled) {
                    autoEnabled.checked = this.schedule.enabled;
                }
                if (scheduleOptions) {
                    scheduleOptions.style.display = this.schedule.enabled ? 'block' : 'none';
                }
                if (frequencySelect) {
                    frequencySelect.value = this.schedule.frequency || 'daily';
                }
                if (timeInput) {
                    timeInput.value = this.schedule.time || '03:00';
                }
                if (retentionInput) {
                    retentionInput.value = this.schedule.retention_days || 30;
                }
                if (keepMonthly) {
                    keepMonthly.checked = this.schedule.keep_monthly || false;
                }
            },
            
            async saveSchedule() {
                const saveBtn = document.getElementById('backup-save-schedule-btn');
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
                
                try {
                    const scheduleData = {
                        enabled: document.getElementById('backup-auto-enabled')?.checked || false,
                        frequency: document.getElementById('backup-schedule-frequency')?.value || 'daily',
                        time: document.getElementById('backup-schedule-time')?.value || '03:00',
                        retention_days: parseInt(document.getElementById('backup-schedule-retention')?.value) || 30,
                        location: document.getElementById('backup-schedule-location')?.value || 'local',
                        keep_monthly: document.getElementById('backup-keep-monthly')?.checked || false
                    };
                    
                    const response = await fetch('/api/admin/backup/schedule', {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(scheduleData)
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        this.schedule = data.data;
                        this.showAlert('backup-alert', 'Schedule saved successfully!', 'success');
                    } else {
                        this.showAlert('backup-alert', data.error || 'Failed to save schedule', 'error');
                    }
                } catch (error) {
                    console.error('[Backup] Save schedule failed:', error);
                    this.showAlert('backup-alert', 'Failed to save schedule', 'error');
                } finally {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;"><path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z"/></svg> Save Schedule';
                }
            },
            
            async loadStorageUsage() {
                try {
                    const response = await fetch('/api/admin/backup/usage');
                    const data = await response.json();
                    
                    if (data.success && data.data) {
                        this.updateStorageUsageUI(data.data);
                    }
                } catch (error) {
                    console.error('[Backup] Failed to load storage usage:', error);
                }
            },
            
            updateStorageUsageUI(usage) {
                const localUsage = document.getElementById('backup-local-usage');
                const externalUsage = document.getElementById('backup-external-usage');
                const monthlyCount = document.getElementById('backup-monthly-count');
                const oldestBackup = document.getElementById('backup-oldest');
                
                if (localUsage) {
                    localUsage.textContent = `${usage.local?.count || 0} files (${usage.local?.size_mb || 0} MB)`;
                }
                if (externalUsage) {
                    externalUsage.textContent = usage.external?.configured 
                        ? `${usage.external?.count || 0} files` 
                        : 'Not configured';
                }
                if (monthlyCount) {
                    monthlyCount.textContent = usage.monthly_count || 0;
                }
                if (oldestBackup) {
                    oldestBackup.textContent = usage.oldest_backup || '—';
                }
                
                // Update card status
                const externalStatus = document.getElementById('backup-external-status');
                if (externalStatus) {
                    if (usage.external?.configured) {
                        externalStatus.className = 'c-status-badge c-status-badge--success';
                        externalStatus.innerHTML = '<span class="status-dot"></span>Configured';
                    } else {
                        externalStatus.className = 'c-status-badge c-status-badge--warning';
                        externalStatus.innerHTML = '<span class="status-dot"></span>Not Configured';
                    }
                }
            },
            
            toggleStorageConfig(type) {
                const ftpConfig = document.getElementById('backup-ftp-config');
                const webdavConfig = document.getElementById('backup-webdav-config');
                const storageActions = document.getElementById('backup-storage-actions');
                
                if (ftpConfig) ftpConfig.style.display = type === 'ftp' ? 'block' : 'none';
                if (webdavConfig) webdavConfig.style.display = type === 'webdav' ? 'block' : 'none';
                if (storageActions) storageActions.style.display = type ? 'block' : 'none';
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

<?php
/**
 * Admin Tab Module: Backup Management
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
        <div class="c-tabs__content" id="backup-tab">
            <div style="margin-bottom: 2rem;">
                <h3 style="margin: 0 0 0.5rem 0; font-size: 1.25rem; font-weight: 600;">Backup Management</h3>
                <p style="margin: 0; color: #666; font-size: 0.875rem;">Create, download, and manage database backups.</p>
            </div>
            
            <div class="c-alert c-alert--info is-visible">
                <strong>Coming Soon:</strong> Full backup management interface with schedule configuration.
            </div>
            
            <div style="background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                <h4 style="margin-top: 0;">Planned Features</h4>
                <ul>
                    <li>One-click backup creation</li>
                    <li>Backup list with download/delete actions</li>
                    <li>Restore from backup functionality</li>
                    <li>Automated schedule configuration</li>
                    <li>Remote sync (WebDAV, FTP, S3)</li>
                    <li>Retention policy management</li>
                </ul>
                
                <div style="margin-top: 1.5rem;">
                    <a href="/backup-management.php" class="c-button c-button--primary">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.5rem;">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 10.586V7z" clip-rule="evenodd"/>
                        </svg>
                        Go to Backup Management
                    </a>
                </div>
            </div>
        </div>
        <?php
    },
    
    'script' => function() {
        ?>
        async function loadBackupStatus() {
            try {
                const response = await fetch('/api/admin/backup/list');
                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.data) {
                        const count = document.getElementById('total-backups-count');
                        const latest = document.getElementById('latest-backup-date');
                        
                        count.textContent = data.data.length;
                        
                        if (data.data.length > 0) {
                            latest.textContent = data.data[0].created_at_human || 'Unknown';
                        }
                    }
                }
            } catch (error) {
                console.error('[Backup] Failed to load status:', error);
            }
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', loadBackupStatus);
        } else {
            loadBackupStatus();
        }
        <?php
    }
];

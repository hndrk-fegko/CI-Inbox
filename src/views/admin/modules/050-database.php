<?php
/**
 * Admin Tab Module: Database Management
 * 
 * Auto-discovered by admin dashboard
 */

return [
    'id' => 'database',
    'title' => 'Database',
    'priority' => 50,
    'icon' => '<path d="M12 3C7.58 3 4 4.79 4 7s3.58 4 8 4 8-1.79 8-4-3.58-4-8-4zM4 9v3c0 2.21 3.58 4 8 4s8-1.79 8-4V9c0 2.21-3.58 4-8 4s-8-1.79-8-4zm0 5v3c0 2.21 3.58 4 8 4s8-1.79 8-4v-3c0 2.21-3.58 4-8 4s-8-1.79-8-4z"/>',
    
    'card' => function() {
        ?>
        <div class="c-admin-card" data-module="database" style="cursor: pointer;">
            <div class="c-admin-card__header">
                <div class="c-admin-card__icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 3C7.58 3 4 4.79 4 7s3.58 4 8 4 8-1.79 8-4-3.58-4-8-4zM4 9v3c0 2.21 3.58 4 8 4s8-1.79 8-4V9c0 2.21-3.58 4-8 4s-8-1.79-8-4zm0 5v3c0 2.21 3.58 4 8 4s8-1.79 8-4v-3c0 2.21-3.58 4-8 4s-8-1.79-8-4z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="c-admin-card__title">Database</h3>
                </div>
            </div>
            <p class="c-admin-card__description">Database connection status, migrations, and maintenance tools.</p>
            <div class="c-admin-card__content">
                <div class="c-info-row">
                    <span class="c-info-row__label">Connection</span>
                    <span class="c-status-badge c-status-badge--success">
                        <span class="status-dot"></span>
                        Connected
                    </span>
                </div>
                <div class="c-info-row">
                    <span class="c-info-row__label">Migration Version</span>
                    <span class="c-info-row__value" id="migration-version">—</span>
                </div>
            </div>
        </div>
        <?php
    },
    
    'content' => function() {
        ?>
        <div class="c-tabs__content" id="database-tab">
            <div style="margin-bottom: 2rem;">
                <h3 style="margin: 0 0 0.5rem 0; font-size: 1.25rem; font-weight: 600;">Database Management</h3>
                <p style="margin: 0; color: #666; font-size: 0.875rem;">Database information, migrations, and maintenance tools.</p>
            </div>
            
            <div class="c-alert c-alert--info is-visible">
                <strong>Coming Soon:</strong> Database management tools are currently in development.
            </div>
            
            <div style="background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                <h4 style="margin-top: 0;">Planned Features</h4>
                <ul>
                    <li>Database connection information and version</li>
                    <li>Migration status and runner</li>
                    <li>Table statistics and sizes</li>
                    <li>Optimize and repair tables</li>
                    <li>Data integrity checks</li>
                    <li>Orphaned data cleanup</li>
                </ul>
            </div>
        </div>
        <?php
    },
    
    'script' => function() {
        ?>
        async function loadDatabaseInfo() {
            try {
                // Placeholder - will implement API endpoint later
                const migrationVersion = document.getElementById('migration-version');
                if (migrationVersion) {
                    migrationVersion.textContent = '017'; // Current version
                }
            } catch (error) {
                console.error('[Database] Failed to load info:', error);
            }
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', loadDatabaseInfo);
        } else {
            loadDatabaseInfo();
        }
        <?php
    }
];

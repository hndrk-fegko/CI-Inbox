<?php
/**
 * Admin Tab Module: User Management
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
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="margin: 0; font-size: 1.25rem; font-weight: 600;">User Management</h3>
                    <button id="btn-add-user" class="c-button c-button--primary">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.5rem;">
                            <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z"/>
                        </svg>
                        Add User
                    </button>
                </div>
                
                <!-- Alert Container -->
                <div id="user-alert-container" style="margin-bottom: 1rem;"></div>
                
                <!-- User Table -->
                <div style="background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow: hidden;">
                    <div class="table-responsive">
                        <table class="table" id="users-table" style="margin: 0;">
                            <thead style="background: #f8f9fa;">
                                <tr>
                                    <th style="font-weight: 600; color: #666; text-transform: uppercase; font-size: 0.75rem; padding: 1rem 1.5rem; border: none;">Name</th>
                                    <th style="font-weight: 600; color: #666; text-transform: uppercase; font-size: 0.75rem; padding: 1rem 1.5rem; border: none;">Email</th>
                                    <th style="font-weight: 600; color: #666; text-transform: uppercase; font-size: 0.75rem; padding: 1rem 1.5rem; border: none;">Role</th>
                                    <th style="font-weight: 600; color: #666; text-transform: uppercase; font-size: 0.75rem; padding: 1rem 1.5rem; border: none;">Status</th>
                                    <th style="font-weight: 600; color: #666; text-transform: uppercase; font-size: 0.75rem; padding: 1rem 1.5rem; border: none;">Last Login</th>
                                    <th style="font-weight: 600; color: #666; text-transform: uppercase; font-size: 0.75rem; padding: 1rem 1.5rem; border: none;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="users-table-body">
                                <tr>
                                    <td colspan="6" style="padding: 2rem; text-align: center;">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php
    },
    
    'script' => function() {
        ?>
        async function loadUserStats() {
            try {
                const response = await fetch('/api/users');
                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.data) {
                        const total = document.getElementById('total-users-count');
                        const active = document.getElementById('active-users-count');
                        
                        total.textContent = data.data.length;
                        active.textContent = data.data.filter(u => u.is_active).length;
                    }
                }
            } catch (error) {
                console.error('[Users] Failed to load stats:', error);
            }
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', loadUserStats);
        } else {
            loadUserStats();
        }
        <?php
    }
];

<?php
/**
 * Admin Tab Module: Email Signatures
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
            <p class="c-admin-card__description">Manage global email signatures and monitor user signatures.</p>
            <div class="c-admin-card__content">
                <div class="c-info-row">
                    <span class="c-info-row__label">Global Signatures</span>
                    <span class="c-info-row__value" id="global-signature-count-card">—</span>
                </div>
                <div class="c-info-row">
                    <span class="c-info-row__label">User Signatures</span>
                    <span class="c-info-row__value" id="user-signature-count-card">—</span>
                </div>
            </div>
        </div>
        <?php
    },
    
    'content' => function() {
        ?>
            <style>
                .admin-content {
                    display: none;
                }
                .admin-content.active {
                    display: block;
                }
                .admin-tab {
                    transition: all 0.2s;
                }
                .admin-tab:hover {
                    color: #2196F3;
                    border-bottom-color: #BBDEFB !important;
                }
                .admin-tab.active {
                    color: #2196F3;
                    border-bottom-color: #2196F3 !important;
                }
                .signature-tabs {
                    display: flex;
                    gap: 0.5rem;
                    border-bottom: 2px solid #e0e0e0;
                    margin-bottom: 2rem;
                }
                .signature-tab {
                    padding: 0.75rem 1.5rem;
                    background: none;
                    border: none;
                    border-bottom: 2px solid transparent;
                    cursor: pointer;
                    font-size: 0.9375rem;
                    font-weight: 500;
                    color: #666;
                    transition: all 0.2s;
                    margin-bottom: -2px;
                }
                .signature-tab:hover {
                    color: #2196F3;
                }
                .signature-tab.active {
                    color: #2196F3;
                    border-bottom-color: #2196F3;
                }
                .signature-content {
                    display: none;
                }
                .signature-content.active {
                    display: block;
                }
                .signature-section {
                    background: white;
                    border-radius: 12px;
                    padding: 2rem;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
                }
                .user-signature-item {
                    background: #f5f5f5 !important;
                }
                .signature-divider {
                    margin: 2rem 0;
                    padding-top: 2rem;
                    border-top: 2px solid #e0e0e0;
                }
            </style>
            
            <!-- Email Signatures Content (Single List) -->
            <div class="signature-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <div>
                        <h2 style="font-size: 1.25rem; font-weight: 600; color: #333; margin: 0 0 0.5rem 0;">Email Signatures Management</h2>
                        <p style="color: #666; font-size: 0.875rem; margin: 0;">Manage global signatures (editable) and monitor user signatures (read-only).</p>
                    </div>
                    <button class="c-button c-button--primary" id="add-global-signature-btn">
                        <span>+ Add Global Signature</span>
                    </button>
                </div>
                
                <div id="signature-alert" class="alert"></div>
                
                <!-- Global Signatures Section -->
                <div style="margin-bottom: 1rem;">
                    <h3 style="font-size: 1rem; font-weight: 600; color: #333; margin: 0 0 0.75rem 0;">Global Signatures</h3>
                </div>
                
                <ul class="imap-accounts-list" id="global-signatures-list">
                    <!-- Global signatures will be loaded here -->
                </ul>
                
                <div class="empty-state" id="global-signatures-empty-state" style="display: none;">
                    <svg fill="currentColor" viewBox="0 0 20 20">
                        <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/>
                    </svg>
                    <p>No global signatures configured yet.</p>
                    <p>Click "Add Global Signature" to create one.</p>
                </div>
                
                <!-- User Signatures Section -->
                <div class="signature-divider">
                    <h3 style="font-size: 1rem; font-weight: 600; color: #333; margin: 0 0 0.5rem 0;">User Signatures (Read-Only)</h3>
                    <p style="color: #666; font-size: 0.875rem; margin: 0 0 0.75rem 0;">Personal signatures created by users. These are read-only for administrators.</p>
                </div>
                
                <ul class="imap-accounts-list" id="user-signatures-list">
                    <!-- User signatures will be loaded here -->
                </ul>
                
                <div class="empty-state" id="user-signatures-empty-state" style="display: none;">
                    <svg fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <p>No user signatures found.</p>
                    <p>Users can create personal signatures from their profile settings.</p>
                </div>
            </div>
        <?php
    },
    
    'script' => function() {
        ?>
        async function loadSignatureStats() {
            try {
                const response = await fetch('/api/admin/signatures');
                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.data) {
                        const globalCount = document.getElementById('global-signature-count-card');
                        const userCount = document.getElementById('user-signature-count-card');
                        
                        const global = data.data.filter(s => s.is_global).length;
                        const user = data.data.filter(s => !s.is_global).length;
                        
                        globalCount.textContent = global;
                        userCount.textContent = user;
                    }
                }
            } catch (error) {
                console.error('[Signatures] Failed to load stats:', error);
            }
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', loadSignatureStats);
        } else {
            loadSignatureStats();
        }
        <?php
    }
];

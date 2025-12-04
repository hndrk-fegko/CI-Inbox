<?php
/**
 * User Settings Page
 * 
 * Features:
 * - Profile & Avatar Management
 * - Password Change
 * - Personal IMAP Accounts
 * - Email Signatures (Future)
 * - Notification Preferences (Future)
 */

session_start();

// Check authentication
if (!isset($_SESSION['user_email'])) {
    header('Location: /login.php');
    exit;
}

// Get user info from session
$userEmail = $_SESSION['user_email'];
$userId = $_SESSION['user_id'] ?? 1; // Fallback to 1 for now

// Get user's theme preference and calculate initials
$themeMode = 'auto'; // Default
$userName = '';
$userInitials = strtoupper(substr($userEmail, 0, 2)); // Fallback

try {
    require_once __DIR__ . '/../../vendor/autoload.php';
    require_once __DIR__ . '/../config/version.php';
    require_once __DIR__ . '/../bootstrap/database.php';
    $config = new \CiInbox\Modules\Config\ConfigService(__DIR__ . '/../../');
    initDatabase($config);
    $user = \CiInbox\App\Models\User::find($userId);
    if ($user) {
        if (isset($user->theme_mode)) {
            $themeMode = $user->theme_mode;
        }
        $userName = $user->name ?? $user->email;
        
        // Calculate proper initials from name
        if (!empty($user->name) && strpos($user->name, ' ') !== false) {
            $parts = explode(' ', trim($user->name));
            $userInitials = strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
        } elseif (!empty($user->name)) {
            $userInitials = strtoupper(substr($user->name, 0, 2));
        }
    }
} catch (Exception $e) {
    // Fallback to auto if DB error
}

?>
<!DOCTYPE html>
<html lang="de" data-user-theme="<?= htmlspecialchars($themeMode) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - C-IMAP</title>
    
    <!-- Main CSS with all components -->
    <link rel="stylesheet" href="/assets/css/main.css<?= asset_version() ?>">
    
    <!-- Theme Module Assets -->
    <script src="/assets/js/theme-switcher.js<?= asset_version() ?>"></script>
</head>
<body>
    <!-- Header -->
    <header class="c-header">
        <div class="c-header__left">
            <a href="/inbox.php" class="c-header__logo-link">
                <svg class="c-header__logo" width="32" height="32" viewBox="0 0 48 48" fill="none">
                    <path d="M12 18L24 26L36 18M12 18V30C12 30.5304 12.2107 31.0391 12.5858 31.4142C12.9609 31.7893 13.4696 32 14 32H34C34.5304 32 35.0391 31.7893 35.4142 31.4142C35.7893 31.0391 36 30.5304 36 30V18M12 18C12 17.4696 12.2107 16.9609 12.5858 16.5858C12.9609 16.2107 13.4696 16 14 16H34C34.5304 16 35.0391 16.2107 35.4142 16.5858C35.7893 16.9609 36 17.4696 36 18Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h1 class="c-header__title">C-IMAP</h1>
            </a>
            <h2 class="c-header__page-title">Settings</h2>
        </div>
        
        <div class="c-header__right">
            <div style="position: relative;">
                <button style="display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.75rem; background: white; border: 1px solid #ddd; border-radius: 8px; cursor: pointer; transition: all 0.2s;" id="user-dropdown-trigger" aria-expanded="false">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #2196F3, #1976D2); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 600;">
                        <?= strtoupper(substr($userEmail, 0, 2)) ?>
                    </div>
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="color: #666; transition: transform 0.2s;">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            
            <div style="position: absolute; top: calc(100% + 0.5rem); right: 0; min-width: 280px; background: white; border: 1px solid #ddd; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); display: none; z-index: 1000;" id="user-dropdown-menu">
                <div style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #2196F3, #1976D2); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.875rem; font-weight: 600;">
                        <?= strtoupper(substr($userEmail, 0, 2)) ?>
                    </div>
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-size: 0.875rem; font-weight: 600; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">User</div>
                        <div style="font-size: 0.75rem; color: #666; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($userEmail) ?></div>
                    </div>
                </div>
                
                <div style="height: 1px; background: #e0e0e0; margin: 0.25rem 0;"></div>
                
                <a href="/inbox.php" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; font-size: 0.875rem; color: #555; text-decoration: none; transition: background 0.2s;" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='white'">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="color: #666;">
                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                    </svg>
                    <span>Inbox</span>
                </a>
                
                <a href="/settings.php" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; font-size: 0.875rem; color: #2196F3; font-weight: 500; text-decoration: none; background: #E3F2FD; transition: background 0.2s;" onmouseover="this.style.background='#BBDEFB'" onmouseout="this.style.background='#E3F2FD'">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="color: #2196F3;">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                    </svg>
                    <span>Profile</span>
                </a>
                
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <a href="/admin-settings.php" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; font-size: 0.875rem; color: #555; text-decoration: none; transition: background 0.2s;" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='white'">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="color: #666;">
                        <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                    </svg>
                    <span>Settings</span>
                </a>
                <?php endif; ?>
                
                <div style="height: 1px; background: #e0e0e0; margin: 0.25rem 0;"></div>
                
                <form method="POST" action="/logout.php" style="margin: 0;">
                    <button type="submit" style="display: flex; align-items: center; gap: 0.75rem; width: 100%; padding: 0.75rem 1rem; font-size: 0.875rem; color: #f44336; text-decoration: none; background: none; border: none; cursor: pointer; text-align: left; transition: background 0.2s;" onmouseover="this.style.background='#FFEBEE'" onmouseout="this.style.background='white'">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="color: #f44336;">
                            <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 001 1h12a1 1 0 001-1V4a1 1 0 00-1-1H3zm11 4.414l-4.293 4.293a1 1 0 01-1.414 0L4 7.414 5.414 6l3.293 3.293L13.586 6 15 7.414z" clip-rule="evenodd"/>
                        </svg>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </header>
    
    <div class="c-settings-container">
        <!-- Tabs -->
        <div class="c-tabs">
            <button class="c-tabs__tab is-active" data-tab="profile">Profile</button>
            <button class="c-tabs__tab" data-tab="imap">Personal IMAP Accounts</button>
            <button class="c-tabs__tab" data-tab="signatures">Email Signatures</button>
            <button class="c-tabs__tab" data-tab="security">Security</button>
        </div>
        
        <!-- Profile Tab -->
        <div class="c-tabs__content is-active" id="profile-tab">
            <div class="c-settings-section">
                <div class="c-settings-section__header">
                    <h2 class="c-settings-section__title">Profile Information</h2>
                </div>
                
                <div id="profile-alert" class="c-alert" style="display: none;"></div>
                
                <form id="profile-form">
                    <div class="c-input-group">
                        <label class="c-input-group__label" for="profile-name">Name</label>
                        <input class="c-input" type="text" id="profile-name" name="name" required>
                    </div>
                    
                    <div class="c-input-group">
                        <label class="c-input-group__label" for="profile-email">Email</label>
                        <input class="c-input" type="email" id="profile-email" name="email" required>
                    </div>
                    
                    <div class="c-input-group">
                        <label class="c-input-group__label" for="profile-timezone">Timezone</label>
                        <select class="c-select" id="profile-timezone" name="timezone">
                            <option value="UTC">UTC</option>
                            <option value="Europe/Berlin">Europe/Berlin</option>
                            <option value="Europe/London">Europe/London</option>
                            <option value="America/New_York">America/New_York</option>
                            <option value="America/Los_Angeles">America/Los_Angeles</option>
                        </select>
                    </div>
                    
                    <div class="c-input-group">
                        <label class="c-input-group__label" for="profile-language">Language</label>
                        <select class="c-select" id="profile-language" name="language">
                            <option value="de">Deutsch</option>
                            <option value="en">English</option>
                        </select>
                    </div>
                    
                    <div class="c-input-group">
                        <label class="c-input-group__label" for="profile-theme">Theme</label>
                        <select class="c-select" id="profile-theme" name="theme_mode">
                            <option value="auto">Auto (System Preference)</option>
                            <option value="light">Always Light</option>
                            <option value="dark">Always Dark</option>
                        </select>
                        <small class="c-input-group__help" style="color: #666;">
                            Auto: Follows your system's dark mode setting
                        </small>
                    </div>
                    
                    <div class="c-input-group">
                        <label class="c-input-group__label">Avatar Color</label>
                        <div class="c-avatar-picker" style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                            <?php for ($i = 1; $i <= 8; $i++): ?>
                                <label class="c-avatar-picker__option" style="cursor: pointer; position: relative;" title="Color <?= $i ?>">
                                    <input type="radio" name="avatar_color" value="<?= $i ?>" 
                                           id="color-<?= $i ?>" class="c-avatar-picker__radio">
                                    <div class="c-avatar c-avatar--lg c-avatar--color-<?= $i ?> c-avatar-picker__preview">
                                        <?= htmlspecialchars($userInitials) ?>
                                    </div>
                                </label>
                            <?php endfor; ?>
                        </div>
                        <small class="c-input-group__help" style="color: #666;">
                            Choose your avatar color for consistent identification across the app
                        </small>
                    </div>
                    
                    <style>
                        /* Avatar Picker Styles */
                        .c-avatar-picker__option {
                            position: relative;
                            display: inline-block;
                        }
                        
                        .c-avatar-picker__radio {
                            position: absolute;
                            opacity: 0;
                            width: 100%;
                            height: 100%;
                            top: 0;
                            left: 0;
                            cursor: pointer;
                            z-index: 1;
                        }
                        
                        /* Important: Ensure color classes work by not overriding background */
                        .c-avatar-picker__preview {
                            transition: all 0.2s;
                            border: 3px solid transparent !important;
                            cursor: pointer;
                            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                            position: relative;
                            z-index: 0;
                            outline: none !important;
                            /* DO NOT set background here - let .c-avatar--color-X handle it */
                        }
                        
                        .c-avatar-picker__preview:hover {
                            transform: scale(1.05);
                            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
                        }
                        
                        .c-avatar-picker__radio:checked + .c-avatar-picker__preview {
                            border: 4px solid var(--color-primary-500) !important;
                            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2), 0 4px 12px rgba(59, 130, 246, 0.4) !important;
                            transform: scale(1.08);
                        }
                    </style>
                    
                    <button type="submit" class="c-button c-button--primary">Save Changes</button>
                </form>
            </div>
        </div>
        
        <!-- Personal IMAP Tab -->
        <div class="c-tabs__content" id="imap-tab">
            <div class="c-settings-section">
                <div class="c-settings-section__header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h2 class="c-settings-section__title" style="margin: 0;">Personal IMAP Accounts</h2>
                    <button class="c-button c-button--primary" id="add-imap-btn">
                        <span>+ Add Account</span>
                    </button>
                </div>
                
                <div id="imap-alert" class="c-alert" style="display: none;"></div>
                
                <ul class="c-settings-list" id="imap-accounts-list">
                    <!-- Accounts will be loaded here -->
                </ul>
                
                <div class="c-empty-state" id="imap-empty-state" style="display: none;">
                    <svg class="c-empty-state__icon" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                    </svg>
                    <p class="c-empty-state__title">No personal IMAP accounts configured yet.</p>
                    <p class="c-empty-state__description">Click "Add Account" to get started.</p>
                </div>
            </div>
        </div>
        
        <!-- Email Signatures Tab -->
        <div class="c-tabs__content" id="signatures-tab">
            <div class="c-settings-section">
                <div class="c-settings-section__header" style="display: flex; justify-content: space-between; align-items: center;">
                    <h2 class="c-settings-section__title" style="margin: 0;">Email Signatures</h2>
                    <button class="c-button c-button--primary" id="add-signature-btn">
                        <span>+ Add Signature</span>
                    </button>
                </div>
                
                <div id="signature-alert" class="c-alert" style="display: none;"></div>
                
                <div id="smtp-warning" class="c-alert c-alert--warning" style="display: none;">
                    <strong>Note:</strong> SMTP is not configured. Only global signatures are available. Please configure SMTP to create personal signatures.
                </div>
                
                <ul class="c-settings-list" id="signatures-list">
                    <!-- Signatures will be loaded here -->
                </ul>
                
                <div class="c-empty-state" id="signatures-empty-state" style="display: none;">
                    <svg class="c-empty-state__icon" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <p class="c-empty-state__title">No email signatures configured yet.</p>
                    <p class="c-empty-state__description">Click "Add Signature" to get started.</p>
                </div>
            </div>
        </div>
        
        <!-- Security Tab -->
        <div class="c-tabs__content" id="security-tab">
            <div class="c-settings-section">
                <div class="c-settings-section__header">
                    <h2 class="c-settings-section__title">Change Password</h2>
                </div>
                
                <div id="password-alert" class="c-alert" style="display: none;"></div>
                
                <form id="password-form">
                    <div class="c-input-group">
                        <label class="c-input-group__label" for="current-password">Current Password</label>
                        <input class="c-input" type="password" id="current-password" name="current_password" required>
                    </div>
                    
                    <div class="c-input-group">
                        <label class="c-input-group__label" for="new-password">New Password</label>
                        <input class="c-input" type="password" id="new-password" name="new_password" required minlength="8">
                        <small class="c-input-group__help" style="color: #666;">Minimum 8 characters</small>
                    </div>
                    
                    <div class="c-input-group">
                        <label class="c-input-group__label" for="confirm-password">Confirm New Password</label>
                        <input class="c-input" type="password" id="confirm-password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="c-button c-button--primary">Change Password</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit IMAP Account Modal -->
    <div class="c-modal c-modal--large" id="imap-modal">
        <div class="c-modal__dialog">
            <div class="c-modal__content">
                <div class="c-modal__header">
                    <h2 class="c-modal__title" id="imap-modal-title">Add IMAP Account</h2>
                    <button type="button" class="c-modal__close" id="imap-modal-close">&times;</button>
                    <div id="imap-modal-alert" class="c-alert" style="display: none; margin-top: 1rem;"></div>
                </div>
                <div class="c-modal__body">
                    <!-- Auto-discover hint -->
            <div style="background: #e3f2fd; padding: 0.875rem; border-radius: 8px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="#1976d2">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div style="flex: 1; font-size: 0.875rem; color: #1976d2;">
                    <strong>Quick Setup:</strong> Let us detect your IMAP settings automatically from your email address.
                </div>
                <button type="button" id="imap-autodiscover-button" class="c-button c-button--primary" style="font-size: 0.875rem; padding: 0.375rem 0.875rem;">
                    Auto-discover
                </button>
            </div>
            
            <form id="imap-form">
                <input type="hidden" id="imap-id" name="id">
                
                <div class="c-input-group">
                    <label class="c-input-group__label" for="imap-label">Account Label</label>
                    <input class="c-input" type="text" id="imap-label" name="label" placeholder="My Gmail Account" required>
                </div>
                
                <div class="c-input-group">
                    <label class="c-input-group__label" for="imap-email">Email Address</label>
                    <input class="c-input" type="email" id="imap-email" name="email" placeholder="user@example.com" required>
                </div>
                
                <div class="c-input-group">
                    <label class="c-input-group__label" for="imap-host">IMAP Server</label>
                    <input class="c-input" type="text" id="imap-host" name="imap_host" placeholder="imap.gmail.com" required>
                </div>
                
                <div class="c-input-group">
                    <label class="c-input-group__label" for="imap-port">Port</label>
                    <input class="c-input" type="number" id="imap-port" name="imap_port" value="993" required>
                </div>
                
                <div class="c-input-group">
                    <label class="c-input-group__label" for="imap-username">Username</label>
                    <input class="c-input" type="text" id="imap-username" name="imap_username" placeholder="user@example.com" required>
                </div>
                
                <div class="c-input-group">
                    <label class="c-input-group__label" for="imap-password">Password</label>
                    <input class="c-input" type="password" id="imap-password" name="imap_password" placeholder="••••••••" required>
                </div>
                
                <div class="c-input-group">
                    <label class="c-input-group__label">
                        <input type="checkbox" id="imap-ssl" name="imap_ssl" checked>
                        Use SSL/TLS
                    </label>
                </div>
                
                <div class="c-modal__footer">
                    <button type="button" class="c-button c-button--secondary" id="imap-cancel-btn">Cancel</button>
                    <button type="button" class="c-button c-button--success" id="imap-test-btn">Test Connection</button>
                    <button type="submit" class="c-button c-button--primary">Save Account</button>
                </div>
            </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Signature Modal -->
    <div class="c-modal c-modal--large" id="signature-modal">
        <div class="c-modal__dialog">
            <div class="c-modal__content">
                <div class="c-modal__header">
                    <h2 class="c-modal__title" id="signature-modal-title">Add Email Signature</h2>
                    <button type="button" class="c-modal__close" id="signature-modal-close">&times;</button>
                    <div id="signature-modal-alert" class="c-alert" style="display: none; margin-top: 1rem;"></div>
                </div>
                <div class="c-modal__body">
            
            <form id="signature-form">
                <input type="hidden" id="signature-id" name="id">
                <input type="hidden" id="signature-type" name="type" value="personal">
                
                <div class="c-input-group">
                    <label class="c-input-group__label" for="signature-name">Signature Name</label>
                    <input class="c-input" type="text" id="signature-name" name="name" placeholder="Work Signature" required>
                </div>
                
                <div class="c-input-group">
                    <label class="c-input-group__label" for="signature-content">Signature Content</label>
                    <textarea class="c-textarea" id="signature-content" name="content" rows="10" placeholder="Best regards,&#10;John Doe&#10;john.doe@example.com" required style="font-family: monospace;"></textarea>
                    <small class="c-input-group__help" style="color: #666;">You can use HTML tags for formatting</small>
                </div>
                
                <div class="c-input-group">
                    <label class="c-input-group__label">
                        <input type="checkbox" id="signature-is-default" name="is_default">
                        Set as default signature
                    </label>
                </div>
                
                <div class="c-modal__footer">
                    <button type="button" class="c-button c-button--secondary" id="signature-cancel-btn">Cancel</button>
                    <button type="submit" class="c-button c-button--primary">Save Signature</button>
                </div>
            </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script>
        // Dropdown Toggle
        document.addEventListener('DOMContentLoaded', () => {
            const trigger = document.getElementById('user-dropdown-trigger');
            const menu = document.getElementById('user-dropdown-menu');
            
            if (trigger && menu) {
                trigger.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isOpen = menu.style.display === 'block';
                    menu.style.display = isOpen ? 'none' : 'block';
                    trigger.setAttribute('aria-expanded', !isOpen);
                    trigger.querySelector('svg:last-child').style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
                });
                
                document.addEventListener('click', (e) => {
                    if (!trigger.contains(e.target) && !menu.contains(e.target)) {
                        menu.style.display = 'none';
                        trigger.setAttribute('aria-expanded', 'false');
                        trigger.querySelector('svg:last-child').style.transform = 'rotate(0deg)';
                    }
                });
                
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && menu.style.display === 'block') {
                        menu.style.display = 'none';
                        trigger.setAttribute('aria-expanded', 'false');
                        trigger.querySelector('svg:last-child').style.transform = 'rotate(0deg)';
                        trigger.focus();
                    }
                });
            }
        });
    </script>
    <script src="/assets/js/user-settings.js"></script>
</body>
</html>

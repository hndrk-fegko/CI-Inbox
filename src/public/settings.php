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
if (!isset($_SESSION['user_email']) || !isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// Get user info from session
$userEmail = $_SESSION['user_email'];
$userId = $_SESSION['user_id'];

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
    <!-- Header using reusable partial -->
    <?php 
    $pageTitle = 'Settings';
    $activePage = 'settings';
    include __DIR__ . '/../views/partials/header.php';
    ?>
    
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
                        <small class="c-input-group__help">
                            Auto: Follows your system's dark mode setting
                        </small>
                    </div>
                    
                    <div class="c-input-group">
                        <label class="c-input-group__label">Avatar Color</label>
                        <div class="c-avatar-picker">
                            <?php for ($i = 1; $i <= 8; $i++): ?>
                                <label class="c-avatar-picker__option" title="Color <?= $i ?>">
                                    <input type="radio" name="avatar_color" value="<?= $i ?>" 
                                           id="color-<?= $i ?>" class="c-avatar-picker__radio">
                                    <div class="c-avatar c-avatar--lg c-avatar--color-<?= $i ?> c-avatar-picker__preview">
                                        <?= htmlspecialchars($userInitials) ?>
                                    </div>
                                </label>
                            <?php endfor; ?>
                        </div>
                        <small class="c-input-group__help">
                            Choose your avatar color for consistent identification across the app
                        </small>
                    </div>
                    
                    <!-- Avatar picker styles now in _settings.css -->
                    
                    <button type="submit" class="c-button c-button--primary">Save Changes</button>
                </form>
            </div>
        </div>
        
        <!-- Personal IMAP Tab -->
        <div class="c-tabs__content" id="imap-tab">
            <div class="c-settings-section">
                <div class="c-settings-section__header c-settings-section__header--with-actions">
                    <h2 class="c-settings-section__title">Personal IMAP Accounts</h2>
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
                <div class="c-settings-section__header c-settings-section__header--with-actions">
                    <h2 class="c-settings-section__title">Email Signatures</h2>
                    <button class="c-button c-button--primary" id="add-signature-btn">
                        <span>+ Add Signature</span>
                    </button>
                </div>
                
                <div id="signature-alert" class="c-alert is-hidden"></div>
                
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
                    <div id="imap-modal-alert" class="c-alert is-hidden" style="margin-top: 1rem;"></div>
                </div>
                <div class="c-modal__body">
                    <!-- Auto-discover hint -->
            <div class="c-autodiscover-hint">
                <svg class="c-autodiscover-hint__icon" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div class="c-autodiscover-hint__text">
                    <strong>Quick Setup:</strong> Let us detect your IMAP settings automatically from your email address.
                </div>
                <button type="button" id="imap-autodiscover-button" class="c-button c-button--primary c-autodiscover-hint__button">
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
    
    <!-- JavaScript - Dropdown handled by user-dropdown partial -->
    <script src="/assets/js/user-settings.js"></script>
</body>
</html>

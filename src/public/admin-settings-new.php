<?php
/**
 * Admin Settings with Sidebar Navigation
 * 
 * Modular admin interface with auto-discovery of configuration modules.
 * Each module in src/views/admin/modules/ provides:
 * - Dashboard card (overview)
 * - Full configuration page (sidebar navigation)
 * - JavaScript functionality
 * 
 * Architecture:
 * - Auto-discovery via glob() pattern
 * - Filename-based priority (010-, 020-, etc.)
 * - Self-contained modules (no central registration)
 */

declare(strict_types=1);

session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /login.php');
    exit;
}

$userEmail = $_SESSION['user_email'] ?? 'Unknown';

// Get user's theme preference
$themeMode = 'auto'; // Default
try {
    require_once __DIR__ . '/../../vendor/autoload.php';
    require_once __DIR__ . '/../bootstrap/database.php';
    $config = new \CiInbox\Modules\Config\ConfigService(__DIR__ . '/../../');
    $logger = new \CiInbox\Modules\Logger\LoggerService(__DIR__ . '/../../logs/');
    initDatabase($config);
    $user = \CiInbox\App\Models\User::find($_SESSION['user_id']);
    if ($user && isset($user->theme_mode)) {
        $themeMode = $user->theme_mode;
    }
} catch (Exception $e) {
    // Fallback to auto if error
    error_log("Admin Settings Error: " . $e->getMessage());
}

// Auto-discover modules from src/views/admin/modules/
$modulesPath = dirname(__DIR__) . '/views/admin/modules/';
$moduleFiles = glob($modulesPath . '*.php');
sort($moduleFiles); // Filename-based ordering

$modules = [];
foreach ($moduleFiles as $file) {
    if (basename($file) === 'README.md') continue;
    
    $module = include $file;
    if (is_array($module) && isset($module['id'], $module['title'])) {
        $modules[] = $module;
    }
}

// Sort by priority
usort($modules, fn($a, $b) => ($a['priority'] ?? 100) <=> ($b['priority'] ?? 100));

$logger->debug('Admin settings loaded', [
    'modules_count' => count($modules),
    'modules' => array_column($modules, 'id')
]);
?>
<!DOCTYPE html>
<html lang="de" data-user-theme="<?= htmlspecialchars($themeMode) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - C-IMAP</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    
    <!-- Theme Module -->
    <script src="/assets/js/theme-switcher.js"></script>
    
    <style>
        body {
            background: #f5f5f5;
        }
        
        /* User Dropdown - Custom for admin page */
        #user-dropdown-trigger {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        #user-dropdown-trigger:hover {
            border-color: #2196F3;
            box-shadow: 0 2px 4px rgba(33, 150, 243, 0.1);
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2196F3, #1976D2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .chevron-icon {
            color: #666;
            transition: transform 0.2s;
        }
        
        #user-dropdown-trigger[aria-expanded="true"] .chevron-icon {
            transform: rotate(180deg);
        }
        
        #user-dropdown-menu {
            position: absolute;
            top: calc(100% + 0.5rem);
            right: 0;
            min-width: 280px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            display: none;
            z-index: 1000;
        }
        
        .dropdown-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
        }
        
        .dropdown-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2196F3, #1976D2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .dropdown-info {
            flex: 1;
            min-width: 0;
        }
        
        .dropdown-name {
            font-size: 0.875rem;
            font-weight: 600;
            color: #333;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .dropdown-email {
            font-size: 0.75rem;
            color: #666;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .dropdown-divider {
            height: 1px;
            background: #e0e0e0;
            margin: 0.25rem 0;
        }
        
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            color: #555;
            text-decoration: none;
            transition: background 0.2s;
        }
        
        .dropdown-item:hover {
            background: #f5f5f5;
        }
        
        .dropdown-item--active {
            color: #2196F3;
            font-weight: 500;
            background: #E3F2FD;
        }
        
        .dropdown-item--active:hover {
            background: #BBDEFB;
        }
        
        .dropdown-item svg {
            color: #666;
            flex-shrink: 0;
        }
        
        .dropdown-item--active svg {
            color: #2196F3;
        }
        
        .dropdown-item--danger {
            color: #f44336;
        }
        
        .dropdown-item--danger:hover {
            background: #FFEBEE;
        }
        
        .dropdown-item--danger svg {
            color: #f44336;
        }
        
        .logout-form {
            margin: 0;
        }
        
        .logout-form button {
            width: 100%;
            background: none;
            border: none;
            cursor: pointer;
            text-align: left;
        }
        
        /* Main Container */
        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem 2rem;
            margin-top: 1rem;
        }
        
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
        }
        
        /* Sidebar Navigation */
        .c-sidebar__link {
            cursor: pointer;
        }
        
        /* Tab Content */
        .tab-content {
            display: none;
        }
        
        .tab-content.is-active {
            display: block;
        }
    </style>
</head>
<body>
    <header class="c-header">
        <div class="c-header__left">
            <a href="/inbox.php" class="c-header__logo-link">
                <svg class="c-header__logo" width="32" height="32" viewBox="0 0 48 48" fill="none">
                    <path d="M12 18L24 26L36 18M12 18V30C12 30.5304 12.2107 31.0391 12.5858 31.4142C12.9609 31.7893 13.4696 32 14 32H34C34.5304 32 35.0391 31.7893 35.4142 31.4142C35.7893 31.0391 36 30.5304 36 30V18M12 18C12 17.4696 12.2107 16.9609 12.5858 16.5858C12.9609 16.2107 13.4696 16 14 16H34C34.5304 16 35.0391 16.2107 35.4142 16.5858C35.7893 16.9609 36 17.4696 36 18Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h1 class="c-header__title">C-IMAP</h1>
            </a>
            <h2 class="c-header__page-title">System Settings</h2>
        </div>
        
        <div class="c-header__right">
            <div style="position: relative;">
                <button id="user-dropdown-trigger" aria-expanded="false">
                    <div class="user-avatar">
                        <?= strtoupper(substr($userEmail, 0, 2)) ?>
                    </div>
                    <svg class="chevron-icon" width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            
            <div id="user-dropdown-menu">
                <div class="dropdown-header">
                    <div class="dropdown-avatar">
                        <?= strtoupper(substr($userEmail, 0, 2)) ?>
                    </div>
                    <div class="dropdown-info">
                        <div class="dropdown-name">Administrator</div>
                        <div class="dropdown-email"><?= htmlspecialchars($userEmail) ?></div>
                    </div>
                </div>
                
                <div class="dropdown-divider"></div>
                
                <a href="/inbox.php" class="dropdown-item">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                    </svg>
                    <span>Inbox</span>
                </a>
                
                <a href="/settings.php" class="dropdown-item">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                    </svg>
                    <span>Profile</span>
                </a>
                
                <a href="/admin-settings.php" class="dropdown-item dropdown-item--active">
                    <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                    </svg>
                    <span>Settings</span>
                </a>
                
                <div class="dropdown-divider"></div>
                
                <form method="POST" action="/logout.php" class="logout-form">
                    <button type="submit" class="dropdown-item dropdown-item--danger">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"/>
                        </svg>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </header>
    
    <div class="admin-container">
        
        <!-- Navigation Tabs as Sidebar -->
        <div class="c-tabs">
            <button class="c-tabs__tab is-active" data-tab="overview">Overview</button>
            <?php foreach ($modules as $module): ?>
                <button class="c-tabs__tab" data-tab="<?= htmlspecialchars($module['id']) ?>">
                    <?= htmlspecialchars($module['title']) ?>
                </button>
            <?php endforeach; ?>
        </div>
        
        <!-- Overview Tab (Dashboard) -->
        <div class="c-tabs__content is-active" id="overview-tab">
            <div class="c-admin-grid"><?php foreach ($modules as $module): ?>
                    <?php if (isset($module['card']) && is_callable($module['card'])): ?>
                        <?= call_user_func($module['card']) ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Module Content Tabs -->
        <?php foreach ($modules as $module): ?>
            <div class="c-tabs__content" id="<?= htmlspecialchars($module['id']) ?>-tab">
                <?php if (isset($module['content']) && is_callable($module['content'])): ?>
                    <?= call_user_func($module['content']) ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        
    </div>
    <!-- End admin-container -->

    <script>
        console.log('[Admin] Script loading...');
        
        document.addEventListener('DOMContentLoaded', function() {
            console.log('[Admin] DOM ready, initializing...');
            
            // User Dropdown Toggle
            const userDropdownTrigger = document.getElementById('user-dropdown-trigger');
            const userDropdownMenu = document.getElementById('user-dropdown-menu');
            
            console.log('[Admin] Dropdown elements:', {trigger: !!userDropdownTrigger, menu: !!userDropdownMenu});
            
            if (userDropdownTrigger && userDropdownMenu) {
                userDropdownTrigger.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isExpanded = userDropdownTrigger.getAttribute('aria-expanded') === 'true';
                    userDropdownTrigger.setAttribute('aria-expanded', !isExpanded);
                    if (isExpanded) {
                        userDropdownMenu.style.display = 'none';
                    } else {
                        userDropdownMenu.style.display = 'block';
                    }
                });
                
                document.addEventListener('click', () => {
                    userDropdownTrigger.setAttribute('aria-expanded', 'false');
                    userDropdownMenu.style.display = 'none';
                });
                
                userDropdownMenu.addEventListener('click', (e) => {
                    e.stopPropagation();
                });
            }
            
            // Tab Switching (horizontal tabs at top)
            window.switchToTab = function(tabId) {
                console.log('[Admin] Switching to tab:', tabId);
            
            // Hide all tab contents
            document.querySelectorAll('.c-tabs__content').forEach(content => {
                content.classList.remove('is-active');
            });
            
            // Show selected tab content
            const selectedContent = document.getElementById(`${tabId}-tab`);
            if (selectedContent) {
                selectedContent.classList.add('is-active');
            }
            
            // Update tab button active state
            document.querySelectorAll('.c-tabs__tab').forEach(tab => {
                tab.classList.remove('is-active');
            });
            
            const activeTab = document.querySelector(`.c-tabs__tab[data-tab="${tabId}"]`);
            if (activeTab) {
                activeTab.classList.add('is-active');
            }
            
            // Update page title
            const titles = {
                'overview': 'System Settings - C-IMAP'<?php if (!empty($modules)): ?>,
                <?php foreach ($modules as $i => $module): ?>
                '<?= $module['id'] ?>': '<?= addslashes($module['title']) ?> - C-IMAP'<?= ($i < count($modules) - 1) ? ',' : '' ?>
                <?php endforeach; ?>
                <?php endif; ?>
            };
            
            if (titles[tabId]) {
                document.title = titles[tabId];
            }
        }
        
        // Attach click handlers to tab buttons
        const tabs = document.querySelectorAll('.c-tabs__tab');
        console.log('[Admin] Found tabs:', tabs.length);
        
        tabs.forEach(tab => {
            const tabId = tab.getAttribute('data-tab');
            console.log('[Admin] Registering tab:', tabId);
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                console.log('[Admin] Tab clicked:', tabId);
                if (tabId) {
                    switchToTab(tabId);
                }
            });
        });
        
        // Handle card click-to-navigate
        const cards = document.querySelectorAll('.c-admin-card');
        console.log('[Admin] Found cards:', cards.length);
        
        cards.forEach(card => {
            const moduleId = card.getAttribute('data-module');
            console.log('[Admin] Registering card:', moduleId);
            card.addEventListener('click', (e) => {
                // Don't trigger if clicking a button or link inside the card
                if (e.target.tagName === 'BUTTON' || e.target.tagName === 'A' || e.target.closest('button') || e.target.closest('a')) {
                    console.log('[Admin] Card click ignored - button/link clicked');
                    return;
                }
                
                console.log('[Admin] Card clicked:', moduleId);
                if (moduleId) {
                    switchToTab(moduleId);
                }
            });
        });
        
        // Module-specific JavaScript
        <?php foreach ($modules as $module): ?>
            <?php if (isset($module['script']) && is_callable($module['script'])): ?>
                // <?= $module['title'] ?> Module
                <?= call_user_func($module['script']) ?>
                
            <?php endif; ?>
        <?php endforeach; ?>
        
        console.log('[Admin] Loaded <?= count($modules) ?> modules:', <?= json_encode(array_column($modules, 'id')) ?>);
        
        }); // End DOMContentLoaded
    </script>
    
    <!-- User Modal (Create/Edit) -->
    <div class="c-modal c-modal--large" id="userModal">
        <div class="c-modal__dialog">
            <div class="c-modal__content">
                <div class="c-modal__header">
                    <h2 class="c-modal__title" id="userModalTitle">Add User</h2>
                    <button type="button" class="c-modal__close" id="userModal-close">&times;</button>
                </div>
                <div class="c-modal__body">
                    <form id="user-form">
                        <input type="hidden" id="user-id" name="user_id">
                        
                        <table class="c-form-table">
                            <tr>
                                <td class="c-form-table__label">Name *</td>
                                <td><input type="text" id="user-name" name="name" placeholder="John Doe" required></td>
                            </tr>
                            <tr>
                                <td class="c-form-table__label">Email *</td>
                                <td><input type="email" id="user-email" name="email" placeholder="user@example.com" required></td>
                            </tr>
                            <tr id="password-group">
                                <td class="c-form-table__label">Password *</td>
                                <td>
                                    <input type="password" id="user-password" name="password" minlength="8" placeholder="Min. 8 characters">
                                    <small class="c-form-hint">Minimum 8 characters required</small>
                                </td>
                            </tr>
                            <tr>
                                <td class="c-form-table__label">Role *</td>
                                <td>
                                    <select id="user-role" name="role" required>
                                        <option value="user">User (Normal Access)</option>
                                        <option value="admin">Admin (Full Access)</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td class="c-form-table__label">Status</td>
                                <td>
                                    <label class="c-checkbox-label">
                                        <input type="checkbox" id="user-active" name="is_active" checked>
                                        Active Account
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
                <div class="c-modal__footer">
                    <button type="button" class="c-button c-button--secondary" id="userModal-cancel">Cancel</button>
                    <button type="button" class="c-button c-button--primary" id="btn-save-user">Save User</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="c-modal" id="deleteUserModal">
        <div class="c-modal__dialog">
            <div class="c-modal__content">
                <div class="c-modal__header">
                    <h2 class="c-modal__title">Delete User</h2>
                    <button type="button" class="c-modal__close" id="deleteUserModal-close">&times;</button>
                </div>
                <div class="c-modal__body">
                    <p>Are you sure you want to delete this user?</p>
                    <p style="color: #dc3545;"><strong>This action cannot be undone.</strong></p>
                    <p id="delete-user-info" style="margin-bottom: 0;"></p>
                </div>
                <div class="c-modal__footer">
                    <button type="button" class="c-button c-button--secondary" id="deleteUserModal-cancel">Cancel</button>
                    <button type="button" class="c-button c-button--danger" id="btn-confirm-delete">Delete</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Global Signature Modal -->
    <div class="c-modal" id="signature-modal">
        <div class="c-modal__content">
            <div class="c-modal__header">
                <h2 id="signature-modal-title">Add Global Signature</h2>
                <button class="c-modal__close" id="signature-modal-close">&times;</button>
            </div>
            
            <div id="signature-modal-alert" class="alert"></div>
            
            <form id="signature-form">
                <input type="hidden" id="signature-id" name="id">
                
                <div class="c-input-group">
                    <label for="signature-name">Signature Name</label>
                    <input type="text" id="signature-name" name="name" placeholder="Company Signature" required>
                </div>
                
                <div class="c-input-group">
                    <label for="signature-content">Signature Content</label>
                    <textarea id="signature-content" name="content" rows="10" placeholder="Best regards,&#10;Company Name&#10;contact@company.com" required style="font-family: monospace;"></textarea>
                    <small style="color: #666;">You can use HTML tags for formatting</small>
                </div>
                
                <div class="c-input-group">
                    <label>
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
    
    <!-- Help FAB Button & Modal -->
    <!-- FAB button uses global .c-fab class from _fab.css -->
    <button type="button" id="help-fab" class="c-fab" title="Help">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
            <path d="M11 18h2v-2h-2v2zm1-16C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-14c-2.21 0-4 1.79-4 4h2c0-1.1.9-2 2-2s2 .9 2 2c0 2-3 1.75-3 5h2c0-2.25 3-2.5 3-5 0-2.21-1.79-4-4-4z"/>
        </svg>
    </button>
    
    <div class="c-modal" id="help-modal">
        <div class="c-modal__content" style="max-width: 700px; max-height: 80vh;">
            <div class="c-modal__header">
                <h2 id="help-modal-title">Help</h2>
                <button class="c-modal__close" id="help-modal-close">&times;</button>
            </div>
            <div class="c-modal__body" style="overflow-y: auto; max-height: calc(80vh - 120px);">
                <div id="help-content">
                    <!-- Help content loaded dynamically based on active tab -->
                </div>
            </div>
        </div>
    </div>
    
    <style>
        /* Help section styles - FAB button now uses global .c-fab from _fab.css */
        .help-section {
            margin-bottom: 1.5rem;
        }
        .help-section__title {
            font-size: 1rem;
            font-weight: 600;
            color: #333;
            margin: 0 0 0.5rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #2196F3;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .help-section__title:hover {
            color: #2196F3;
        }
        .help-section__title::after {
            content: '▼';
            font-size: 0.75rem;
            transition: transform 0.2s;
        }
        .help-section.collapsed .help-section__title::after {
            transform: rotate(-90deg);
        }
        .help-section__content {
            padding: 0.75rem 0;
            color: #555;
            font-size: 0.9375rem;
            line-height: 1.6;
        }
        .help-section.collapsed .help-section__content {
            display: none;
        }
        .help-section ul {
            margin: 0.5rem 0;
            padding-left: 1.5rem;
        }
        .help-section li {
            margin-bottom: 0.25rem;
        }
        .help-tip {
            background: #E3F2FD;
            border-left: 4px solid #2196F3;
            padding: 0.75rem;
            margin: 0.75rem 0;
            border-radius: 4px;
        }
        .help-warning {
            background: #FFF3E0;
            border-left: 4px solid #FF9800;
            padding: 0.75rem;
            margin: 0.75rem 0;
            border-radius: 4px;
        }
    </style>
    
    <script>
        // Help System
        const HelpSystem = {
            content: {
                overview: {
                    title: 'Dashboard Overview - Help',
                    sections: [
                        {
                            title: 'About the Dashboard',
                            content: `The dashboard provides a quick overview of all system components. Each card shows the current status and key metrics for that module.
                            <div class="help-tip"><strong>Tip:</strong> Click on any card to navigate directly to its configuration page.</div>`
                        },
                        {
                            title: 'Status Indicators',
                            content: `<ul>
                                <li><strong>🟢 Green/Success:</strong> Component is configured and working correctly</li>
                                <li><strong>🟡 Yellow/Warning:</strong> Component needs attention or is partially configured</li>
                                <li><strong>🔴 Red/Error:</strong> Component has errors or is not configured</li>
                            </ul>`
                        }
                    ]
                },
                imap: {
                    title: 'IMAP Configuration - Help',
                    sections: [
                        {
                            title: 'Overview',
                            content: 'Configure the IMAP server connection for receiving emails. This is required for the shared inbox functionality to work.'
                        },
                        {
                            title: 'Auto-Discover',
                            content: `Enter your email address and click "Auto-Discover" to automatically detect server settings.
                            <div class="help-tip"><strong>Tip:</strong> Auto-discover works with most major email providers (Gmail, Outlook, Yahoo, etc.)</div>`
                        },
                        {
                            title: 'Manual Configuration',
                            content: `<ul>
                                <li><strong>Host:</strong> Your IMAP server address (e.g., imap.gmail.com)</li>
                                <li><strong>Port:</strong> Usually 993 for SSL or 143 for TLS/None</li>
                                <li><strong>Encryption:</strong> SSL (recommended), TLS, or None</li>
                                <li><strong>Username:</strong> Usually your full email address</li>
                                <li><strong>Password:</strong> Your email password or app-specific password</li>
                            </ul>`
                        },
                        {
                            title: 'Troubleshooting',
                            content: `<div class="help-warning"><strong>Connection Failed?</strong>
                            <ul>
                                <li>Check if your email provider requires app-specific passwords (Gmail, Microsoft)</li>
                                <li>Verify that IMAP is enabled in your email settings</li>
                                <li>Try different encryption methods (SSL vs TLS)</li>
                            </ul></div>`
                        }
                    ]
                },
                smtp: {
                    title: 'SMTP Configuration - Help',
                    sections: [
                        {
                            title: 'Overview',
                            content: 'Configure the SMTP server for sending emails. This is required to reply to emails from the shared inbox.'
                        },
                        {
                            title: 'Sender Identity',
                            content: `<ul>
                                <li><strong>From Name:</strong> Display name for outgoing emails (e.g., "Support Team")</li>
                                <li><strong>From Email:</strong> The email address shown as sender</li>
                            </ul>
                            <div class="help-tip"><strong>Tip:</strong> Use a recognizable sender name for better email deliverability.</div>`
                        },
                        {
                            title: 'Test Email',
                            content: 'Always test your configuration by sending a test email before relying on the system for production use.'
                        }
                    ]
                },
                cron: {
                    title: 'Webcron / Polling - Help',
                    sections: [
                        {
                            title: 'Overview',
                            content: 'The webcron service periodically checks for new emails in your IMAP inbox. It runs automatically based on external cron triggers.'
                        },
                        {
                            title: 'Health Status',
                            content: `<ul>
                                <li><strong>Healthy (>55/hour):</strong> Cron is running correctly</li>
                                <li><strong>Degraded (30-55/hour):</strong> Some executions may be delayed</li>
                                <li><strong>Delayed (<30/hour):</strong> Significant delays in polling</li>
                                <li><strong>Stale (<1/hour):</strong> Cron is not running</li>
                            </ul>`
                        },
                        {
                            title: 'Webhook Configuration',
                            content: `<div class="help-warning"><strong>Security:</strong> Keep your webhook token secret! Anyone with the token can trigger the cron job.</div>
                            Use the webhook URL in your external cron service (e.g., cron-job.org, EasyCron) to trigger polling.`
                        }
                    ]
                },
                backup: {
                    title: 'Backup Management - Help',
                    sections: [
                        {
                            title: 'Overview',
                            content: 'Create and manage database backups to protect your data. Backups include all users, threads, emails, and configuration.'
                        },
                        {
                            title: 'Storage Locations',
                            content: `<ul>
                                <li><strong>💾 Local:</strong> Stored on the server in the data/backups folder</li>
                                <li><strong>☁️ External:</strong> Uploaded to FTP/WebDAV storage</li>
                                <li><strong>📌 Monthly:</strong> Protected from automatic cleanup</li>
                            </ul>`
                        },
                        {
                            title: 'External Storage',
                            content: `Configure FTP or WebDAV (Nextcloud) for off-site backups.
                            <div class="help-tip"><strong>Recommended:</strong> Store backups externally for disaster recovery.</div>`
                        },
                        {
                            title: 'Keep Monthly Backups',
                            content: 'Enable this option to automatically preserve the last backup of each month. These backups are excluded from cleanup and must be deleted manually.'
                        }
                    ]
                },
                database: {
                    title: 'Database Management - Help',
                    sections: [
                        {
                            title: 'Overview',
                            content: 'Monitor database health and perform maintenance operations.'
                        },
                        {
                            title: 'Maintenance Operations',
                            content: `<ul>
                                <li><strong>Optimize:</strong> Reclaims unused space and defragments tables</li>
                                <li><strong>Analyze:</strong> Updates table statistics for better query performance</li>
                            </ul>
                            <div class="help-tip"><strong>Tip:</strong> Run optimization monthly or after deleting large amounts of data.</div>`
                        }
                    ]
                },
                users: {
                    title: 'User Management - Help',
                    sections: [
                        {
                            title: 'Overview',
                            content: 'Manage user accounts, roles, and access permissions.'
                        },
                        {
                            title: 'User Roles',
                            content: `<ul>
                                <li><strong>Admin:</strong> Full access to all settings and system configuration</li>
                                <li><strong>User:</strong> Access to shared inbox and assigned threads only</li>
                            </ul>`
                        },
                        {
                            title: 'Creating Users',
                            content: `<div class="help-tip"><strong>Password Requirements:</strong> Minimum 8 characters. Use strong passwords!</div>`
                        }
                    ]
                },
                oauth: {
                    title: 'OAuth2 / SSO - Help',
                    sections: [
                        {
                            title: 'Overview',
                            content: 'Enable Single Sign-On (SSO) to allow users to log in with their existing accounts from Google, Microsoft, or other OAuth2 providers.'
                        },
                        {
                            title: 'Setting Up a Provider',
                            content: `<ol>
                                <li>Register your application with the provider (e.g., Google Cloud Console)</li>
                                <li>Copy the Callback URL from this page to the provider</li>
                                <li>Enter the Client ID and Client Secret from the provider</li>
                                <li>Enable the provider toggle</li>
                                <li>Save the configuration</li>
                            </ol>`
                        },
                        {
                            title: 'Auto-Registration',
                            content: 'When enabled, new users who sign in via OAuth will automatically get an account created. Set the default role for these users.'
                        }
                    ]
                },
                signatures: {
                    title: 'Email Signatures - Help',
                    sections: [
                        {
                            title: 'Overview',
                            content: 'Manage email signatures for team responses and personal emails.'
                        },
                        {
                            title: 'Signature Types',
                            content: `<ul>
                                <li><strong>Shared Inbox:</strong> Used when replying from the team inbox. Ensures consistent branding.</li>
                                <li><strong>Personal:</strong> Used when users take personal ownership of threads. Admin can edit for support.</li>
                            </ul>`
                        },
                        {
                            title: 'Variables',
                            content: `Use these placeholders in your signatures:
                            <ul>
                                <li><code>{{user.name}}</code> - User's full name</li>
                                <li><code>{{user.email}}</code> - User's email address</li>
                                <li><code>{{date}}</code> - Current date</li>
                            </ul>`
                        }
                    ]
                },
                logger: {
                    title: 'System Logs - Help',
                    sections: [
                        {
                            title: 'Overview',
                            content: 'View and manage system logs for debugging and monitoring.'
                        },
                        {
                            title: 'Log Levels',
                            content: `<ul>
                                <li><strong>DEBUG:</strong> Detailed information for debugging</li>
                                <li><strong>INFO:</strong> General operational messages</li>
                                <li><strong>WARNING:</strong> Potential issues that should be reviewed</li>
                                <li><strong>ERROR:</strong> Errors that need immediate attention</li>
                            </ul>
                            <div class="help-warning"><strong>Note:</strong> DEBUG level generates a lot of data. Use INFO or higher in production.</div>`
                        },
                        {
                            title: 'Filtering Logs',
                            content: 'Use the filters to narrow down logs by level, module, or time range. Click on a log entry to see full details.'
                        }
                    ]
                }
            },
            
            currentTab: 'overview',
            
            init() {
                const fab = document.getElementById('help-fab');
                const modal = document.getElementById('help-modal');
                const closeBtn = document.getElementById('help-modal-close');
                
                if (fab) {
                    fab.addEventListener('click', () => this.openHelp());
                }
                
                if (closeBtn) {
                    closeBtn.addEventListener('click', () => this.closeHelp());
                }
                
                if (modal) {
                    modal.addEventListener('click', (e) => {
                        if (e.target === modal) this.closeHelp();
                    });
                }
                
                // Update current tab when switching
                const originalSwitchToTab = window.switchToTab;
                window.switchToTab = (tabId) => {
                    this.currentTab = tabId;
                    originalSwitchToTab(tabId);
                };
            },
            
            openHelp() {
                const content = this.content[this.currentTab] || this.content.overview;
                const container = document.getElementById('help-content');
                const title = document.getElementById('help-modal-title');
                
                if (title) title.textContent = content.title;
                
                if (container) {
                    container.innerHTML = content.sections.map(section => `
                        <div class="help-section">
                            <div class="help-section__title" onclick="this.parentElement.classList.toggle('collapsed')">
                                ${section.title}
                            </div>
                            <div class="help-section__content">${section.content}</div>
                        </div>
                    `).join('');
                }
                
                document.getElementById('help-modal').classList.add('show');
            },
            
            closeHelp() {
                document.getElementById('help-modal').classList.remove('show');
            }
        };
        
        // Initialize help system when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => HelpSystem.init());
        } else {
            HelpSystem.init();
        }
    </script>
    
    <!-- Admin Settings JavaScript (User/Signature Management) -->
    <script src="/assets/js/admin-settings.js"></script>
</body>
</html>

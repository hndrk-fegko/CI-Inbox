<?php
/**
 * Admin Settings - Modular Dashboard
 * 
 * Auto-discovers modules from src/views/admin/modules/
 * Each module provides: card, content, scripts
 */

session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /login.php');
    exit;
}

$userEmail = $_SESSION['user_email'] ?? 'Unknown';

// Get user's theme preference
$themeMode = 'auto';
try {
    require_once __DIR__ . '/../../vendor/autoload.php';
    require_once __DIR__ . '/../bootstrap/database.php';
    $config = new \CiInbox\Modules\Config\ConfigService(__DIR__ . '/../../');
    initDatabase($config);
    $user = \CiInbox\App\Models\User::find($_SESSION['user_id']);
    if ($user && isset($user->theme_mode)) {
        $themeMode = $user->theme_mode;
    }
} catch (Exception $e) {
    // Fallback
}

// Auto-discover admin modules
$modulesPath = __DIR__ . '/../views/admin/modules/';
$modules = [];

if (is_dir($modulesPath)) {
    $files = glob($modulesPath . '*.php');
    sort($files); // Filename-based ordering (010-, 020-, etc.)
    
    foreach ($files as $file) {
        try {
            $module = include $file;
            if (is_array($module) && isset($module['id'], $module['title'])) {
                $modules[] = $module;
            }
        } catch (Exception $e) {
            error_log("Failed to load admin module: $file - " . $e->getMessage());
        }
    }
    
    // Sort by priority (lower = first)
    usort($modules, function($a, $b) {
        $pa = $a['priority'] ?? 999;
        $pb = $b['priority'] ?? 999;
        return $pa - $pb;
    });
}
?>
<!DOCTYPE html>
<html lang="de" data-user-theme="<?= htmlspecialchars($themeMode) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - C-IMAP</title>
    
    <!-- CSS in ITCSS order -->
    <link rel="stylesheet" href="/assets/css/1-settings/_variables.css">
    <link rel="stylesheet" href="/assets/css/3-generic/_reset.css">
    <link rel="stylesheet" href="/assets/css/4-elements/_typography.css">
    <link rel="stylesheet" href="/assets/css/4-elements/_forms.css">
    <link rel="stylesheet" href="/assets/css/5-objects/_layout.css">
    <link rel="stylesheet" href="/assets/css/6-components/_header.css">
    <link rel="stylesheet" href="/assets/css/6-components/_sidebar.css">
    <link rel="stylesheet" href="/assets/css/6-components/_button.css">
    <link rel="stylesheet" href="/assets/css/6-components/_dropdown.css">
    <link rel="stylesheet" href="/assets/css/6-components/_badge.css">
    <link rel="stylesheet" href="/assets/css/6-components/_admin.css">
    <link rel="stylesheet" href="/assets/css/7-utilities/_utilities.css">
    
    <!-- Theme Module -->
    <script src="/assets/js/theme-switcher.js"></script>
    
    <style>
        body {
            background: #f5f5f5;
        }
        
        /* App Layout with Sidebar */
        .l-admin-app {
            display: grid;
            grid-template-areas:
                "header header"
                "sidebar main";
            grid-template-columns: 240px 1fr;
            grid-template-rows: 64px 1fr;
            min-height: 100vh;
        }
        
        .l-admin-app__header {
            grid-area: header;
        }
        
        .l-admin-app__sidebar {
            grid-area: sidebar;
        }
        
        .l-admin-app__main {
            grid-area: main;
            overflow-y: auto;
        }
        
        /* User Dropdown */
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
                            <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 001 1h12a1 1 0 001-1V4a1 1 0 00-1-1H3zm11 4.414l-4.293 4.293a1 1 0 01-1.414 0L4 7.414 5.414 6l3.293 3.293L13.586 6 15 7.414z" clip-rule="evenodd"/>
                        </svg>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </header>
    
    <div class="admin-container">
        <!-- Navigation Tabs -->
        <div class="c-tabs">
            <button class="c-tabs__tab is-active" data-tab="overview">Overview</button>
            <?php foreach ($modules as $module): ?>
                <button class="c-tabs__tab" data-tab="<?= htmlspecialchars($module['id']) ?>">
                    <?= htmlspecialchars($module['title']) ?>
                </button>
            <?php endforeach; ?>
        </div>
        
        <!-- Overview Tab (Dashboard with all cards) -->
        <div class="c-tabs__content is-active" id="overview-tab">
            <div class="c-alert c-alert--info is-visible">
                <strong>System Configuration:</strong> Click on any card below to configure the respective component. Cards link to their detailed settings tabs.
            </div>
        
            <div class="c-admin-grid">
                <?php foreach ($modules as $module): ?>
                    <?php if (isset($module['card']) && is_callable($module['card'])): ?>
                        <?php call_user_func($module['card']); ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Module Tab Contents -->
        <?php foreach ($modules as $module): ?>
            <?php if (isset($module['content']) && is_callable($module['content'])): ?>
                <?php call_user_func($module['content']); ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    
    <!-- Tab Switching JavaScript -->
    <script>
        // Tab switching logic
        document.querySelectorAll('.c-tabs__tab').forEach(button => {
            button.addEventListener('click', function() {
                const tabName = this.getAttribute('data-tab');
                switchToTab(tabName);
            });
        });
        
        function switchToTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.c-tabs__content').forEach(tab => {
                tab.classList.remove('is-active');
            });
            
            // Remove active state from all buttons
            document.querySelectorAll('.c-tabs__tab').forEach(btn => {
                btn.classList.remove('is-active');
            });
            
            // Show target tab
            const targetTab = document.getElementById(`${tabName}-tab`);
            if (targetTab) {
                targetTab.classList.add('is-active');
            }
            
            // Activate target button
            const targetButton = document.querySelector(`[data-tab="${tabName}"]`);
            if (targetButton) {
                targetButton.classList.add('is-active');
            }
            
            // Trigger tab-specific load function if exists
            const loadFunctionName = `load${tabName.charAt(0).toUpperCase() + tabName.slice(1)}Tab`;
            if (typeof window[loadFunctionName] === 'function') {
                window[loadFunctionName]();
            }
        }
        
        // User dropdown toggle
        document.getElementById('user-dropdown-trigger')?.addEventListener('click', function() {
            const menu = document.getElementById('user-dropdown-menu');
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', !isExpanded);
            menu.style.display = isExpanded ? 'none' : 'block';
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const trigger = document.getElementById('user-dropdown-trigger');
            const menu = document.getElementById('user-dropdown-menu');
            if (trigger && menu && !trigger.contains(event.target) && !menu.contains(event.target)) {
                trigger.setAttribute('aria-expanded', 'false');
                menu.style.display = 'none';
            }
        });
    </script>
    
    <!-- Module Scripts -->
    <?php foreach ($modules as $module): ?>
        <?php if (isset($module['script']) && is_callable($module['script'])): ?>
            <?php call_user_func($module['script']); ?>
        <?php endif; ?>
    <?php endforeach; ?>
</body>
</html>

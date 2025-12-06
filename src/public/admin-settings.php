<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /login.php');
    exit;
}

$userEmail = $_SESSION['user_email'] ?? 'Unknown';

// Get user's theme preference for theme module
$themeMode = 'auto'; // Default
try {
    require_once __DIR__ . '/../../vendor/autoload.php';
    require_once __DIR__ . '/../config/version.php';
    require_once __DIR__ . '/../bootstrap/database.php';
    $config = new \CiInbox\Modules\Config\ConfigService(__DIR__ . '/../../');
    initDatabase($config);
    $user = \CiInbox\App\Models\User::find($_SESSION['user_id']);
    if ($user && isset($user->theme_mode)) {
        $themeMode = $user->theme_mode;
    }
} catch (Exception $e) {
    // Fallback to auto if error
}
?>
<!DOCTYPE html>
<html lang="de" data-user-theme="<?= htmlspecialchars($themeMode) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - CI-Inbox</title>
    <link rel="stylesheet" href="/assets/css/main.css<?= asset_version() ?>">
    
    <!-- Theme Module -->
    <script src="/assets/js/theme-switcher.js<?= asset_version() ?>"></script>
    
    <style>
        
        /* Admin-specific styles (unique to this page) */
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
    </style>
</head>
<body>
    <header class="c-header">
        <div class="c-header__left">
            <a href="/inbox.php" class="c-header__logo-link">
                <svg class="c-header__logo" width="32" height="32" viewBox="0 0 48 48" fill="none">
                    <path d="M12 18L24 26L36 18M12 18V30C12 30.5304 12.2107 31.0391 12.5858 31.4142C12.9609 31.7893 13.4696 32 14 32H34C34.5304 32 35.0391 31.7893 35.4142 31.4142C35.7893 31.0391 36 30.5304 36 30V18M12 18C12 17.4696 12.2107 16.9609 12.5858 16.5858C12.9609 16.2107 13.4696 16 14 16H34C34.5304 16 35.0391 16.2107 35.4142 16.5858C35.7893 16.9609 36 17.4696 36 18Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h1 class="c-header__title">CI-Inbox</h1>
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
            <button class="c-tabs__tab" data-tab="users">Users</button>
            <button class="c-tabs__tab" data-tab="signatures">Email Signatures</button>
        </div>
        
        <!-- Overview Tab -->
        <div class="c-tabs__content is-active" id="overview-tab">
            <div class="c-alert c-alert--info is-visible">
                <strong>Coming Soon:</strong> Advanced system configuration features are currently in development. Basic monitoring is available below.
            </div>
        
        <div class="c-admin-grid">
            <!-- IMAP Configuration Card -->
            <div class="c-admin-card">
                <div class="c-admin-card__header">
                    <div class="c-admin-card__icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="c-admin-card__title">Global IMAP</h3>
                    </div>
                </div>
                <p class="c-admin-card__description">Configure default IMAP settings and autodiscover service for all users.</p>
                <div class="c-admin-card__content">
                    <div id="imap-alert"></div>
                    <div id="imap-configured-info" style="display: none; background: #E8F5E9; border: 1px solid #4CAF50; border-radius: 8px; padding: 0.75rem; margin-bottom: 1rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="#4CAF50">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <strong style="color: #2E7D32;">IMAP Configured</strong>
                        </div>
                        <div style="color: #2E7D32; font-size: 0.875rem;">
                            <div>Host: <strong id="imap-configured-host">—</strong></div>
                            <div>User: <strong id="imap-configured-user">—</strong></div>
                        </div>
                    </div>
                    <div class="c-info-row">
                        <span class="c-info-row__label">Status</span>
                        <span id="imap-status-badge" class="c-status-badge c-status-badge--warning">
                            <span class="status-dot"></span>
                            Not Configured
                        </span>
                    </div>
                    <div class="c-info-row">
                        <span class="c-info-row__label">Autodiscover</span>
                        <span class="c-info-row__value">Available</span>
                    </div>
                    <button id="imap-config-button" class="c-button c-button--secondary" style="width: 100%;" disabled>
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                        </svg>
                        Configure
                    </button>
                </div>
            </div>
            
            <!-- SMTP Configuration Card -->
            <div class="c-admin-card">
                <div class="c-admin-card__header">
                    <div class="c-admin-card__icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20 8l-8 5-8-5V6l8 5 8-5m0-2H4c-1.11 0-2 .89-2 2v12a2 2 0 002 2h16a2 2 0 002-2V6a2 2 0 00-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="c-admin-card__title">Global SMTP</h3>
                    </div>
                </div>
                <p class="c-admin-card__description">Configure default SMTP settings for outgoing emails and replies.</p>
                <div class="c-admin-card__content">
                    <div id="smtp-alert"></div>
                    <div id="smtp-configured-info" style="display: none; background: #E8F5E9; border: 1px solid #4CAF50; border-radius: 8px; padding: 0.75rem; margin-bottom: 1rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="#4CAF50">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <strong style="color: #2E7D32;">SMTP Configured</strong>
                        </div>
                        <div style="color: #2E7D32; font-size: 0.875rem;">
                            <div>Host: <strong id="smtp-configured-host">—</strong></div>
                            <div>From: <strong id="smtp-configured-from">—</strong></div>
                        </div>
                    </div>
                    <div class="c-info-row">
                        <span class="c-info-row__label">Status</span>
                        <span id="smtp-status-badge" class="c-status-badge c-status-badge--warning">
                            <span class="status-dot"></span>
                            Not Configured
                        </span>
                    </div>
                    <div class="c-info-row">
                        <span class="c-info-row__label">Autodiscover</span>
                        <span class="c-info-row__value">Available</span>
                    </div>
                    <button id="smtp-config-button" class="c-button c-button--secondary" style="width: 100%;" disabled>
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                        </svg>
                        Configure
                    </button>
                </div>
            </div>
            
            <!-- Cron Monitor Card -->
            <div class="c-admin-card">
                <div class="c-admin-card__header">
                    <div class="c-admin-card__icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="c-admin-card__title">Cron Monitor</h3>
                    </div>
                </div>
                <p class="c-admin-card__description">Monitor webhook polling service health and execution status.</p>
                <div class="c-admin-card__content">
                    <div id="cron-alert"></div>
                    <div class="c-info-row">
                        <span class="c-info-row__label">Service Status</span>
                        <span id="cron-status-badge" class="c-status-badge c-status-badge--warning">
                            <span class="status-dot"></span>
                            Loading...
                        </span>
                    </div>
                    <div class="c-info-row">
                        <span class="c-info-row__label">Last Execution</span>
                        <span id="cron-last-execution" class="c-info-row__value">—</span>
                    </div>
                    <div class="c-info-row">
                        <span class="c-info-row__label">Executions (Last Hour)</span>
                        <span id="cron-executions-count" class="c-info-row__value">0</span>
                    </div>
                    <button id="cron-view-history-button" class="c-button c-button--secondary" style="width: 100%;" disabled>
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                        </svg>
                        View History
                    </button>
                </div>
            </div>
            
            <!-- Backup Configuration Card -->
            <div class="c-admin-card">
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
                <p class="c-admin-card__description">Configure automated backups to WebDAV/Nextcloud or FTP servers.</p>
                <div class="c-admin-card__content">
                    <div class="c-info-row">
                        <span class="c-info-row__label">Backup Target</span>
                        <span class="c-info-row__value">Not Configured</span>
                    </div>
                    <div class="c-info-row">
                        <span class="c-info-row__label">Last Backup</span>
                        <span class="c-info-row__value">Never</span>
                    </div>
                    <button class="c-button c-button--secondary" style="width: 100%;" disabled>
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                        </svg>
                        Configure (Coming Soon)
                    </button>
                </div>
            </div>
            
            <!-- Database Info Card -->
            <div class="c-admin-card">
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
                <p class="c-admin-card__description">Database connection status and migration information.</p>
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
                        <span class="c-info-row__value">014</span>
                    </div>
                    <button class="c-button c-button--secondary" style="width: 100%;" disabled>
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        View Details (Coming Soon)
                    </button>
                </div>
            </div>
            
            <!-- Email Signatures Card -->
            <div class="c-admin-card">
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
        </div>
        </div>
        
        <!-- Users Tab -->
        <div class="c-tabs__content" id="users-tab">
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
        </div>
        
        <!-- Email Signatures Tab -->
        <div class="c-tabs__content" id="signatures-tab">
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
        </div>
    </div>
    </div> <!-- Close admin-container -->
    
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
    
    <script>
        // Tab switching
        document.querySelectorAll('.c-tabs__tab').forEach(tab => {
            tab.addEventListener('click', () => {
                const tabId = tab.dataset.tab;
                
                // Remove is-active class from all tabs
                document.querySelectorAll('.c-tabs__tab').forEach(t => t.classList.remove('is-active'));
                document.querySelectorAll('.c-tabs__content').forEach(c => c.classList.remove('is-active'));
                
                // Add is-active class to clicked tab
                tab.classList.add('is-active');
                document.getElementById(`${tabId}-tab`).classList.add('is-active');
                
                // Load data for signatures tab
                if (tabId === 'signatures') {
                    loadAllSignatures();
                } else if (tabId === 'overview') {
                    loadSignatureCounts();
                }
            });
        });
    </script>
    <script>
        const API_BASE_ADMIN = '/api/admin';
        let currentSignatureId = null;
        
        // ============================================================================
        // SIGNATURE COUNT FOR CARD
        // ============================================================================
        
        async function loadSignatureCounts() {
            try {
                const response = await fetch(`${API_BASE_ADMIN}/signatures`);
                const result = await response.json();
                
                if (result.success) {
                    const globalCount = result.data.filter(s => s.type === 'global').length;
                    const userCount = result.data.filter(s => s.type === 'personal').length;
                    
                    document.getElementById('global-signature-count-card').textContent = globalCount;
                    document.getElementById('user-signature-count-card').textContent = userCount;
                }
            } catch (error) {
                console.error('[AdminSettings] Failed to load signature counts:', error);
            }
        }
        
        // ============================================================================
        // SIGNATURE MANAGEMENT - COMBINED LIST
        // ============================================================================
        
        async function loadAllSignatures() {
            console.log('[AdminSettings] Loading all signatures...');
            try {
                const response = await fetch(`${API_BASE_ADMIN}/signatures`);
                const result = await response.json();
                
                if (result.success) {
                    const globalSigs = result.data.filter(s => s.type === 'global');
                    const userSigs = result.data.filter(s => s.type === 'personal');
                    
                    console.log('[AdminSettings] Signatures loaded:', { 
                        global: globalSigs.length, 
                        personal: userSigs.length 
                    });
                    
                    renderGlobalSignatures(globalSigs);
                    renderUserSignatures(userSigs);
                } else {
                    console.error('[AdminSettings] Failed to load signatures:', result.error);
                    showAlert('signature-alert', result.error, 'error');
                }
            } catch (error) {
                console.error('[AdminSettings] Error loading signatures:', error);
                showAlert('signature-alert', 'Failed to load signatures: ' + error.message, 'error');
            }
        }
        
        function renderGlobalSignatures(signatures) {
            const list = document.getElementById('global-signatures-list');
            const emptyState = document.getElementById('global-signatures-empty-state');
            
            if (signatures.length === 0) {
                list.style.display = 'none';
                emptyState.style.display = 'flex';
                return;
            }
            
            list.style.display = 'block';
            emptyState.style.display = 'none';
            
            list.innerHTML = signatures.map(signature => `
                <li class="imap-account-item">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                                <strong>${escapeHtml(signature.name)}</strong>
                                ${signature.is_default ? '<span style="background: #4CAF50; color: white; padding: 0.125rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">Default</span>' : ''}
                                <span style="background: #2196F3; color: white; padding: 0.125rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">Global</span>
                            </div>
                            <div style="color: #666; font-size: 0.875rem; max-height: 3rem; overflow: hidden; white-space: pre-wrap;">${escapeHtml(signature.content.substring(0, 100))}${signature.content.length > 100 ? '...' : ''}</div>
                        </div>
                        <div style="display: flex; gap: 0.5rem;">
                            ${!signature.is_default ? `<button class="c-button c-button--success" style="padding: 0.375rem 0.75rem; font-size: 0.875rem;" onclick="setDefaultGlobalSignature(${signature.id})">Set Default</button>` : ''}
                            <button class="c-button c-button--primary" style="padding: 0.375rem 0.75rem; font-size: 0.875rem;" onclick="editGlobalSignature(${signature.id})">Edit</button>
                            <button class="c-button c-button--danger" style="padding: 0.375rem 0.75rem; font-size: 0.875rem;" onclick="deleteGlobalSignature(${signature.id})">Delete</button>
                        </div>
                    </div>
                </li>
            `).join('');
        }
        
        // Modal controls
        document.getElementById('add-global-signature-btn').addEventListener('click', () => {
            console.log('[AdminSettings] Opening add global signature modal...');
            currentSignatureId = null;
            document.getElementById('signature-modal-title').textContent = 'Add Global Signature';
            document.getElementById('signature-form').reset();
            document.getElementById('signature-id').value = '';
            document.getElementById('signature-modal').classList.add('show');
        });
        
        document.getElementById('signature-modal-close').addEventListener('click', closeSignatureModal);
        document.getElementById('signature-cancel-btn').addEventListener('click', closeSignatureModal);
        
        document.getElementById('signature-modal').addEventListener('click', (e) => {
            if (e.target.id === 'signature-modal') {
                closeSignatureModal();
            }
        });
        
        function closeSignatureModal() {
            console.log('[AdminSettings] Closing signature modal');
            document.getElementById('signature-modal').classList.remove('show');
            document.getElementById('signature-modal-alert').classList.remove('show');
        }
        
        // Form submit
        document.getElementById('signature-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            console.log('[AdminSettings] Submitting signature form...');
            
            const formData = {
                name: document.getElementById('signature-name').value,
                content: document.getElementById('signature-content').value,
                is_default: document.getElementById('signature-is-default').checked,
                type: 'global'
            };
            
            try {
                const signatureId = document.getElementById('signature-id').value;
                let response;
                
                if (signatureId) {
                    console.log('[AdminSettings] Updating global signature:', signatureId);
                    response = await fetch(`${API_BASE_ADMIN}/signatures/${signatureId}`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(formData)
                    });
                } else {
                    console.log('[AdminSettings] Creating new global signature');
                    response = await fetch(`${API_BASE_ADMIN}/signatures`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(formData)
                    });
                }
                
                const result = await response.json();
                
                if (result.success) {
                    console.log('[AdminSettings] Global signature saved successfully');
                    showAlert('signature-alert', signatureId ? 'Signature updated!' : 'Signature created!', 'success');
                    closeSignatureModal();
                    loadAllSignatures();
                    loadSignatureCounts();
                } else {
                    console.error('[AdminSettings] Failed to save signature:', result.error);
                    showAlert('signature-modal-alert', result.error, 'error');
                }
            } catch (error) {
                console.error('[AdminSettings] Error saving signature:', error);
                showAlert('signature-modal-alert', 'Failed to save signature: ' + error.message, 'error');
            }
        });
        
        async function editGlobalSignature(id) {
            console.log('[AdminSettings] Editing global signature:', id);
            try {
                const response = await fetch(`${API_BASE_ADMIN}/signatures/${id}`);
                const result = await response.json();
                
                if (result.success) {
                    const signature = result.data;
                    console.log('[AdminSettings] Signature loaded for editing:', { id, name: signature.name });
                    
                    currentSignatureId = id;
                    document.getElementById('signature-modal-title').textContent = 'Edit Global Signature';
                    document.getElementById('signature-id').value = signature.id;
                    document.getElementById('signature-name').value = signature.name;
                    document.getElementById('signature-content').value = signature.content;
                    document.getElementById('signature-is-default').checked = signature.is_default;
                    
                    document.getElementById('signature-modal').classList.add('show');
                } else {
                    console.error('[AdminSettings] Failed to load signature:', result.error);
                    showAlert('signature-alert', result.error, 'error');
                }
            } catch (error) {
                console.error('[AdminSettings] Error loading signature:', error);
                showAlert('signature-alert', 'Failed to load signature: ' + error.message, 'error');
            }
        }
        
        async function deleteGlobalSignature(id) {
            console.log('[AdminSettings] Deleting global signature:', id);
            
            if (!confirm('Are you sure you want to delete this global signature? This will affect all users.')) {
                console.log('[AdminSettings] Delete signature cancelled');
                return;
            }
            
            try {
                const response = await fetch(`${API_BASE_ADMIN}/signatures/${id}`, {
                    method: 'DELETE'
                });
                const result = await response.json();
                
                if (result.success) {
                    console.log('[AdminSettings] Global signature deleted successfully:', id);
                    showAlert('signature-alert', 'Signature deleted successfully!', 'success');
                    loadAllSignatures();
                    loadSignatureCounts();
                } else {
                    console.error('[AdminSettings] Failed to delete signature:', result.error);
                    showAlert('signature-alert', result.error, 'error');
                }
            } catch (error) {
                console.error('[AdminSettings] Error deleting signature:', error);
                showAlert('signature-alert', 'Failed to delete signature: ' + error.message, 'error');
            }
        }
        
        async function setDefaultGlobalSignature(id) {
            console.log('[AdminSettings] Setting default global signature:', id);
            try {
                const response = await fetch(`${API_BASE_ADMIN}/signatures/${id}/set-default`, {
                    method: 'POST'
                });
                const result = await response.json();
                
                if (result.success) {
                    console.log('[AdminSettings] Default global signature set successfully:', id);
                    showAlert('signature-alert', 'Default signature updated!', 'success');
                    loadAllSignatures();
                } else {
                    console.error('[AdminSettings] Failed to set default signature:', result.error);
                    showAlert('signature-alert', result.error, 'error');
                }
            } catch (error) {
                console.error('[AdminSettings] Error setting default signature:', error);
                showAlert('signature-alert', 'Failed to set default signature: ' + error.message, 'error');
            }
        }
        
        // ============================================================================
        // USER SIGNATURES MONITORING (READ-ONLY)
        // ============================================================================
        
        function renderUserSignatures(signatures) {
            const list = document.getElementById('user-signatures-list');
            const emptyState = document.getElementById('user-signatures-empty-state');
            
            if (signatures.length === 0) {
                list.style.display = 'none';
                emptyState.style.display = 'flex';
                return;
            }
            
            list.style.display = 'block';
            emptyState.style.display = 'none';
            
            list.innerHTML = signatures.map(signature => `
                <li class="imap-account-item user-signature-item">
                    <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                                <strong>${escapeHtml(signature.name)}</strong>
                                ${signature.is_default ? '<span style="background: #4CAF50; color: white; padding: 0.125rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">Default</span>' : ''}
                                <span style="background: #9C27B0; color: white; padding: 0.125rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">Personal</span>
                                <span style="color: #999; font-size: 0.75rem;">User ID: ${signature.user_id || 'N/A'}</span>
                            </div>
                            <div style="color: #666; font-size: 0.875rem; max-height: 3rem; overflow: hidden; white-space: pre-wrap;">${escapeHtml(signature.content.substring(0, 100))}${signature.content.length > 100 ? '...' : ''}</div>
                        </div>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <span style="color: #999; font-size: 0.875rem; font-style: italic;">Read-only</span>
                        </div>
                    </div>
                </li>
            `).join('');
        }
        
        // ============================================================================
        // UTILITY FUNCTIONS
        // ============================================================================
        
        function showAlert(elementId, message, type = 'success') {
            const alert = document.getElementById(elementId);
            alert.className = `alert alert-${type} show`;
            alert.textContent = message;
            
            setTimeout(() => {
                alert.classList.remove('show');
            }, 5000);
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Load signature counts
        async function loadSignatureCounts() {
            try {
                const response = await fetch('/api/admin/signatures');
                const result = await response.json();
                
                if (result.success) {
                    const globalCount = result.data.filter(s => s.type === 'global').length;
                    const userCount = result.data.filter(s => s.type === 'personal').length;
                    
                    document.getElementById('global-signature-count-card').textContent = globalCount;
                    document.getElementById('user-signature-count-card').textContent = userCount;
                }
            } catch (error) {
                console.error('Failed to load signature counts:', error);
            }
        }
        
        // Dropdown Toggle
        document.addEventListener('DOMContentLoaded', () => {
            loadSignatureCounts();
            
            const trigger = document.getElementById('user-dropdown-trigger');
            const menu = document.getElementById('user-dropdown-menu');
            
            if (trigger && menu) {
                trigger.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isOpen = menu.style.display === 'block';
                    menu.style.display = isOpen ? 'none' : 'block';
                    trigger.setAttribute('aria-expanded', !isOpen);
                });
                
                document.addEventListener('click', (e) => {
                    if (!trigger.contains(e.target) && !menu.contains(e.target)) {
                        menu.style.display = 'none';
                        trigger.setAttribute('aria-expanded', 'false');
                    }
                });
                
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && menu.style.display === 'block') {
                        menu.style.display = 'none';
                        trigger.setAttribute('aria-expanded', 'false');
                        trigger.focus();
                    }
                });
            }
        });
    </script>
    
    <!-- IMAP Configuration Modal -->
    <div class="c-modal c-modal--large" id="imapConfigModal">
        <div class="c-modal__dialog">
            <div class="c-modal__content">
                <div class="c-modal__header">
                    <h2 class="c-modal__title">Global IMAP Configuration</h2>
                    <button type="button" class="c-modal__close" id="imapConfigModal-close">&times;</button>
                </div>
                <div class="c-modal__body">
                    <div id="imap-modal-alert" class="alert"></div>
                    
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
                    
                    <table class="c-form-table">
                        <tr>
                            <td class="c-form-table__label">IMAP Host *</td>
                            <td>
                                <input type="text" id="imap-host" placeholder="imap.example.com" required>
                                <small class="c-form-hint">IMAP server hostname or IP address</small>
                            </td>
                        </tr>
                        <tr>
                            <td class="c-form-table__label">Port *</td>
                            <td>
                                <input type="number" id="imap-port" value="993" placeholder="993" required style="width: 120px;">
                                <small class="c-form-hint">Typically 993 (SSL) or 143 (unencrypted)</small>
                            </td>
                        </tr>
                        <tr>
                            <td class="c-form-table__label">SSL/TLS</td>
                            <td>
                                <label class="c-checkbox-label">
                                    <input type="checkbox" id="imap-ssl" checked>
                                    Use SSL/TLS encryption (recommended)
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td class="c-form-table__label">Username *</td>
                            <td>
                                <input type="text" id="imap-username" placeholder="username or email@example.com" required>
                            </td>
                        </tr>
                        <tr>
                            <td class="c-form-table__label">Password</td>
                            <td>
                                <input type="password" id="imap-password" placeholder="Leave empty to keep current password">
                                <small class="c-form-hint">Password is encrypted before storage</small>
                            </td>
                        </tr>
                        <tr>
                            <td class="c-form-table__label">Inbox Folder</td>
                            <td>
                                <input type="text" id="imap-inbox-folder" value="INBOX" placeholder="INBOX">
                                <small class="c-form-hint">Main inbox folder name (usually INBOX)</small>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="c-modal__footer">
                    <button type="button" class="c-button c-button--secondary" id="imapConfigModal-cancel">Cancel</button>
                    <button type="button" id="imap-test-button" class="c-button c-button--secondary">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Test Connection
                    </button>
                    <button type="button" id="imap-save-button" class="c-button c-button--primary">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                            <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z"/>
                        </svg>
                        Save Configuration
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- SMTP Configuration Modal -->
    <div class="c-modal c-modal--large" id="smtpConfigModal">
        <div class="c-modal__dialog">
            <div class="c-modal__content">
                <div class="c-modal__header">
                    <h2 class="c-modal__title">Global SMTP Configuration</h2>
                    <button type="button" class="c-modal__close" id="smtpConfigModal-close">&times;</button>
                </div>
                <div class="c-modal__body">
                    <div id="smtp-modal-alert" class="alert"></div>
                    
                    <!-- Auto-discover hint -->
                    <div style="background: #e3f2fd; padding: 0.875rem; border-radius: 8px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="#1976d2">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <div style="flex: 1; font-size: 0.875rem; color: #1976d2;">
                            <strong>Quick Setup:</strong> Let us detect your SMTP settings automatically from your email address.
                        </div>
                        <button type="button" id="smtp-autodiscover-button" class="c-button c-button--primary" style="font-size: 0.875rem; padding: 0.375rem 0.875rem;">
                            Auto-discover
                        </button>
                    </div>
                    
                    <table class="c-form-table">
                        <tr>
                            <td class="c-form-table__label">SMTP Host *</td>
                            <td>
                                <input type="text" id="smtp-host" placeholder="smtp.example.com" required>
                                <small class="c-form-hint">SMTP server hostname or IP address</small>
                            </td>
                        </tr>
                        <tr>
                            <td class="c-form-table__label">Port *</td>
                            <td>
                                <input type="number" id="smtp-port" value="465" placeholder="465" required style="width: 120px;">
                                <small class="c-form-hint">Typically 465 (SSL) or 587 (STARTTLS)</small>
                            </td>
                        </tr>
                        <tr>
                            <td class="c-form-table__label">SSL/TLS</td>
                            <td>
                                <label class="c-checkbox-label">
                                    <input type="checkbox" id="smtp-ssl" checked>
                                    Use SSL/TLS encryption (recommended)
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td class="c-form-table__label">Authentication</td>
                            <td>
                                <label class="c-checkbox-label">
                                    <input type="checkbox" id="smtp-auth" checked>
                                    Require authentication
                                </label>
                                <small class="c-form-hint">Most modern SMTP servers require authentication</small>
                            </td>
                        </tr>
                        <tr id="smtp-auth-row-username">
                            <td class="c-form-table__label">Username *</td>
                            <td>
                                <input type="text" id="smtp-username" placeholder="username or email@example.com">
                            </td>
                        </tr>
                        <tr id="smtp-auth-row-password">
                            <td class="c-form-table__label">Password</td>
                            <td>
                                <input type="password" id="smtp-password" placeholder="Leave empty to keep current password">
                                <small class="c-form-hint">Password is encrypted before storage</small>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="padding-top: 1.5rem; border-top: 1px solid var(--color-border);">
                                <h4 style="margin: 0 0 1rem; font-size: 1rem; font-weight: 600;">Default Sender Information</h4>
                            </td>
                        </tr>
                        <tr>
                            <td class="c-form-table__label">From Name *</td>
                            <td>
                                <input type="text" id="smtp-from-name" placeholder="CI-Inbox Team" required>
                                <small class="c-form-hint">Default sender name for outgoing emails</small>
                            </td>
                        </tr>
                        <tr>
                            <td class="c-form-table__label">From Email *</td>
                            <td>
                                <input type="email" id="smtp-from-email" placeholder="noreply@example.com" required>
                                <small class="c-form-hint">Default sender email address</small>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="c-modal__footer">
                    <button type="button" class="c-button c-button--secondary" id="smtpConfigModal-cancel">Cancel</button>
                    <button type="button" id="smtp-test-button" class="c-button c-button--secondary">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Test Connection
                    </button>
                    <button type="button" id="smtp-save-button" class="c-button c-button--primary">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" style="margin-right: 0.25rem;">
                            <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v7a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z"/>
                        </svg>
                        Save Configuration
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Cron History Modal -->
    <div class="c-modal" id="cronHistoryModal">
        <div class="c-modal__dialog">
            <div class="c-modal__content">
                <div class="c-modal__header">
                    <h2 class="c-modal__title">Cron Execution History</h2>
                    <button type="button" class="c-modal__close" id="cronHistoryModal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="cron-history-alert"></div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Accounts</th>
                                    <th>New Emails</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="cron-history-table-body">
                                <tr>
                                    <td colspan="5" class="text-center">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="c-modal__footer">
                    <button type="button" class="c-button c-button--secondary" id="cronHistoryModal-cancel">Close</button>
                </div>
            </div>
        </div>
    </div>
    
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
    
    <!-- Admin Settings JS -->
    <script src="/assets/js/admin-settings.js"></script>
</body>
</html>





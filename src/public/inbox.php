<?php
/**
 * Dashboard - Main Inbox View
 */

session_start();

// Check authentication
if (!isset($_SESSION['user_email'])) {
    header('Location: /login.php');
    exit;
}

// Load dependencies
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/version.php';

use CiInbox\Core\Container;
use CiInbox\App\Models\Thread;
use CiInbox\App\Models\User;
use CiInbox\App\Models\ImapAccount;

// Initialize database connection via Container
$container = Container::getInstance();
$container->get('database'); // This bootstraps Eloquent

// Get current user settings
$currentUser = User::where('email', $_SESSION['user_email'])->first();
$userSettings = [
    'signature' => $currentUser->signature ?? null,
    'auto_archive_hours' => $currentUser->auto_archive_hours ?? 72
];

// Check if user has personal IMAP account configured
$hasPersonalImap = ImapAccount::where('user_id', $currentUser->id ?? null)
    ->where('is_active', 1)
    ->exists();

// Get user's theme preference for theme module
$themeMode = $currentUser->theme_mode ?? 'auto';

// Get threads (latest 50) with all needed relations
$threads = Thread::with(['emails', 'labels', 'assignedUsers'])
    ->orderBy('last_message_at', 'desc')
    ->limit(50)
    ->get();

// Calculate senders for each thread
foreach ($threads as $thread) {
    $senders = $thread->emails->map(function($email) {
        $name = $email->from_name ?: $email->from_email;
        $initials = strtoupper(substr($name, 0, 1));
        if (str_contains($name, ' ')) {
            $parts = explode(' ', $name);
            $initials = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
        }
        return [
            'email' => $email->from_email,
            'name' => $name,
            'initials' => $initials
        ];
    })->unique('email')->values();
    
    $thread->senders = $senders;
}

?>
<!DOCTYPE html>
<html lang="de" data-user-theme="<?= htmlspecialchars($themeMode) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inbox - C-IMAP</title>
    <!-- CSS in ITCSS order - cache busting via asset_version() from config/version.php -->
    <link rel="stylesheet" href="/assets/css/1-settings/_variables.css<?= asset_version() ?>">
    <link rel="stylesheet" href="/assets/css/3-generic/_reset.css<?= asset_version() ?>">
    <link rel="stylesheet" href="/assets/css/4-elements/_typography.css<?= asset_version() ?>">
    <link rel="stylesheet" href="/assets/css/4-elements/_forms.css<?= asset_version() ?>">
    <link rel="stylesheet" href="/assets/css/5-objects/_layout.css<?= asset_version() ?>">
    <link rel="stylesheet" href="/assets/css/6-components/_auth.css<?= asset_version() ?>">
    <link rel="stylesheet" href="/assets/css/6-components/_avatar.css<?= asset_version() ?>">
    <link rel="stylesheet" href="/assets/css/6-components/_header.css<?= asset_version() ?>">
    <link rel="stylesheet" href="/assets/css/6-components/_sidebar.css<?= asset_version() ?>">
    <link rel="stylesheet" href="/assets/css/6-components/_button.css<?= asset_version() ?>">
    <link rel="stylesheet" href="/assets/css/6-components/_dropdown.css<?= asset_version() ?>">
    <link rel="stylesheet" href="/assets/css/6-components/_context-menu.css<?= asset_version() ?>">
    <link rel="stylesheet" href="/assets/css/6-components/_modal.css<?= asset_version() ?>">
    <link rel="stylesheet" href="/assets/css/6-components/_badge.css<?= asset_version() ?>">
    <link rel="stylesheet" href="/assets/css/6-components/_input.css<?= asset_version() ?>">
    <link rel="stylesheet" href="/assets/css/6-components/_label-tag.css<?= asset_version() ?>">
    <link rel="stylesheet" href="/assets/css/6-components/_label-picker.css<?= asset_version() ?>">
    <link rel="stylesheet" href="/assets/css/6-components/_label-filter.css<?= asset_version() ?>">
    <link rel="stylesheet" href="/assets/css/6-components/_status-filter.css<?= asset_version() ?>">
    <link rel="stylesheet" href="/assets/css/6-components/_status-picker.css<?= asset_version() ?>">
    <link rel="stylesheet" href="/assets/css/6-components/_user-picker.css<?= asset_version() ?>">
    <link rel="stylesheet" href="/assets/css/6-components/_assigned-users.css<?= asset_version() ?>">
    <link rel="stylesheet" href="/assets/css/6-components/_assignment-picker.css<?= asset_version() ?>">
    <link rel="stylesheet" href="/assets/css/6-components/_thread-list.css<?= asset_version() ?>">
    <link rel="stylesheet" href="/assets/css/6-components/_thread-detail.css<?= asset_version() ?>">
    <link rel="stylesheet" href="/assets/css/6-components/_email-unread.css<?= asset_version() ?>">
    <link rel="stylesheet" href="/assets/css/6-components/_status-picker.css<?= asset_version() ?>">
    <link rel="stylesheet" href="/assets/css/6-components/_email-composer.css<?= asset_version() ?>">
    <link rel="stylesheet" href="/assets/css/6-components/_inbox-view.css<?= asset_version() ?>">
    <link rel="stylesheet" href="/assets/css/7-utilities/_utilities.css<?= asset_version() ?>">
    
    <!-- Theme Module -->
    <script src="/modules/theme/assets/theme-switcher.js<?= asset_version() ?>"></script>
    
    <!-- User session data for JavaScript -->
    <script>
        window.currentUserId = <?= json_encode($_SESSION['user_id'] ?? null) ?>;
        window.currentUserEmail = <?= json_encode($_SESSION['user_email'] ?? null) ?>;
        window.userHasPersonalImap = <?= json_encode($hasPersonalImap ?? false) ?>;
    </script>
    
    <!-- Modular JavaScript Architecture (refactored 2025-11-28) -->
    <!-- Load order: ApiClient → UiComponents → ThreadRenderer → InboxManager -->
    <script src="/assets/js/modules/api-client.js<?= asset_version() ?>"></script>
    <script src="/assets/js/modules/ui-components.js<?= asset_version() ?>"></script>
    <script src="/assets/js/modules/thread-renderer.js<?= asset_version() ?>"></script>
    <script src="/assets/js/modules/inbox-manager.js<?= asset_version() ?>"></script>
    
    <!-- Legacy support - will be removed after testing -->
    <!-- <script src="/assets/js/thread-detail-renderer.js<?= asset_version() ?>"></script> -->
    
    <script src="/assets/js/email-composer.js<?= asset_version() ?>"></script>
</head>
<body class="l-app">
    <!-- Header -->
    <header class="c-header l-app__header">
        <div class="c-header__left">
            <a href="/inbox.php" class="c-header__logo-link">
                <svg class="c-header__logo" width="32" height="32" viewBox="0 0 48 48" fill="none">
                    <path d="M12 18L24 26L36 18M12 18V30C12 30.5304 12.2107 31.0391 12.5858 31.4142C12.9609 31.7893 13.4696 32 14 32H34C34.5304 32 35.0391 31.7893 35.4142 31.4142C35.7893 31.0391 36 30.5304 36 30V18M12 18C12 17.4696 12.2107 16.9609 12.5858 16.5858C12.9609 16.2107 13.4696 16 14 16H34C34.5304 16 35.0391 16.2107 35.4142 16.5858C35.7893 16.9609 36 17.4696 36 18Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h1 class="c-header__title">C-IMAP</h1>
            </a>
        </div>
        
        <div class="c-header__center">
            <input type="search" class="c-header__search" id="global-search" placeholder="Suche in E-Mails...">
        </div>
        
        <div class="c-header__right">
            <!-- New Email Button -->
            <button class="c-button c-button--primary c-button--sm" id="new-email-btn" title="Neue E-Mail">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Neue E-Mail</span>
            </button>
            
            <!-- User Dropdown -->
            <div class="c-user-dropdown">
                <button class="c-user-dropdown__trigger" id="user-dropdown-trigger" aria-expanded="false">
                    <div class="c-avatar c-avatar--sm">
                        <span class="c-avatar__initials"><?= strtoupper(substr($_SESSION['user_email'], 0, 2)) ?></span>
                    </div>
                    <svg class="c-user-dropdown__chevron" width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
                
                <div class="c-user-dropdown__menu" id="user-dropdown-menu">
                    <!-- User Section -->
                    <div class="c-user-dropdown__header">
                        <div class="c-avatar c-avatar--md">
                            <span class="c-avatar__initials"><?= strtoupper(substr($_SESSION['user_email'], 0, 2)) ?></span>
                        </div>
                        <div class="c-user-dropdown__info">
                            <div class="c-user-dropdown__name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></div>
                            <div class="c-user-dropdown__email-small"><?= htmlspecialchars($_SESSION['user_email']) ?></div>
                        </div>
                    </div>
                    
                    <div class="c-user-dropdown__divider"></div>
                    
                    <!-- Menu Items -->
                    
                    <div class="c-user-dropdown__divider"></div>
                    
                    <a href="/inbox.php" class="c-user-dropdown__item c-user-dropdown__item--active">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                        </svg>
                        <span>Inbox</span>
                    </a>
                    
                    <a href="/settings.php" class="c-user-dropdown__item">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                        </svg>
                        <span>Profile</span>
                    </a>
                    
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <a href="/admin-settings.php" class="c-user-dropdown__item">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                        </svg>
                        <span>Settings</span>
                    </a>
                    <?php endif; ?>                    <div class="c-user-dropdown__divider"></div>
                    
                    <form method="POST" action="/logout.php" style="margin: 0;">
                        <button type="submit" class="c-user-dropdown__item c-user-dropdown__item--danger">
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 001 1h12a1 1 0 001-1V4a1 1 0 00-1-1H3zm11 4.414l-4.293 4.293a1 1 0 01-1.414 0L4 7.414 5.414 6l3.293 3.293L13.586 6 15 7.414z" clip-rule="evenodd"/>
                            </svg>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <!-- Sidebar -->
    <aside class="c-sidebar l-app__sidebar">
            <button class="c-button c-button--primary c-button--block" id="sidebar-new-email-btn">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Neue E-Mail
            </button>
            
            <nav class="c-sidebar__nav">
                <!-- Ordner Section -->
                <a href="#" class="c-sidebar__link is-active" id="filter-inbox">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <span>Posteingang</span>
                    <span class="c-badge"><?= count($threads) ?></span>
                </a>
                
                <a href="#" class="c-sidebar__link" id="show-archived-filter">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                    <span>Archiv</span>
                </a>
                
                <!-- Divider -->
                <div class="c-sidebar__divider"></div>
                
                <!-- Filter Section -->
                <a href="#" class="c-sidebar__link" id="status-filter-toggle">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span>Status</span>
                    <svg class="c-sidebar__dropdown-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-left: auto;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </a>
                
                <div class="c-status-filter" id="status-filter-dropdown" style="display: none;">
                    <div class="c-status-filter__item" data-status="open">
                        <span class="c-badge c-badge--primary">Offen</span>
                    </div>
                    <div class="c-status-filter__item" data-status="assigned">
                        <span class="c-badge c-badge--warning">In Arbeit</span>
                    </div>
                    <div class="c-status-filter__item" data-status="closed">
                        <span class="c-badge c-badge--success">Erledigt</span>
                    </div>
                </div>
                
                <a href="#" class="c-sidebar__link" id="labels-filter-toggle">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    <span>Labels</span>
                    <svg class="c-sidebar__dropdown-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-left: auto;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </a>
                
                <div class="c-label-filter" id="label-filter-dropdown" style="display: none;">
                    <div class="c-label-filter__loading">Lade Labels...</div>
                </div>
                
                <a href="#" class="c-sidebar__link" id="users-filter-toggle">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span>Zugewiesen</span>
                    <svg class="c-sidebar__dropdown-icon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-left: auto;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </a>
                
                <div class="c-label-filter" id="user-filter-dropdown" style="display: none;">
                    <div class="c-label-filter__loading">Lade Benutzer...</div>
                </div>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="l-app__main">
            <div class="c-inbox">
                <!-- Toolbar: Split into List and Detail sections -->
                <div class="c-inbox__toolbar-list">
                    <h2 class="c-inbox__title">Posteingang</h2>
                    <div class="c-inbox__actions">
                        <button class="c-button c-button--secondary c-button--sm c-button--icon" id="toggle-sort" title="Sortierung umkehren">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="sort-icon-desc">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"/>
                            </svg>
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="sort-icon-asc" style="display: none;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/>
                            </svg>
                        </button>
                        <button class="c-button c-button--secondary c-button--sm c-button--icon" id="toggle-multiselect" title="Mehrfachauswahl">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                        </button>
                        <button class="c-button c-button--secondary c-button--sm c-button--icon" id="refresh-threads-btn" title="Aktualisieren">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="c-inbox__toolbar-detail">
                    <h2 class="c-inbox__title" id="detail-title">Wähle einen Thread</h2>
                    <div class="c-inbox__actions" id="detail-actions" style="display: none;">
                        <!-- Will be populated when thread is selected -->
                    </div>
                </div>

                <!-- Thread List (Left Panel) -->
                <div class="c-inbox__thread-list">
                    <div class="c-thread-list">
                    <?php if (empty($threads)): ?>
                        <div class="c-empty-state">
                            <svg width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                            </svg>
                            <h3>Keine E-Mails</h3>
                            <p>Dein Posteingang ist leer. 🎉</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($threads as $thread): ?>
                            <div class="c-thread-item <?= $thread->is_read ? '' : 'is-unread' ?>" data-thread-id="<?= $thread->id ?>">
                                <!-- Sender Avatars Stack -->
                                <div class="c-thread-item__avatars">
                                    <?php 
                                    $maxAvatars = 3;
                                    $senderCount = count($thread->senders);
                                    $displaySenders = array_slice($thread->senders->toArray(), 0, $maxAvatars);
                                    $colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];
                                    ?>
                                    <?php foreach ($displaySenders as $index => $sender): ?>
                                        <div class="c-avatar c-avatar--sm" 
                                             style="--avatar-color: <?= $colors[$index % count($colors)] ?>; z-index: <?= $maxAvatars - $index ?>;"
                                             title="<?= htmlspecialchars($sender['name']) ?>">
                                            <?= htmlspecialchars($sender['initials']) ?>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if ($senderCount > $maxAvatars): ?>
                                        <div class="c-avatar c-avatar--sm c-avatar--more" title="+<?= $senderCount - $maxAvatars ?> weitere">
                                            +<?= $senderCount - $maxAvatars ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="c-thread-item__content">
                                    <div class="c-thread-item__header">
                                        <span class="c-thread-item__sender"><?= htmlspecialchars($thread->sender_name ?: $thread->sender_email) ?></span>
                                        <span class="c-thread-item__time"><?= $thread->last_message_at?->diffForHumans() ?? 'N/A' ?></span>
                                    </div>
                                    
                                    <div class="c-thread-item__subject">
                                        <?php if ($thread->message_count > 1): ?>
                                            <span class="c-badge c-badge--primary"><?= $thread->message_count ?></span>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($thread->subject ?: '(Kein Betreff)') ?>
                                    </div>
                                    
                                    <div class="c-thread-item__preview">
                                        <?= htmlspecialchars(mb_substr(strip_tags($thread->preview ?? ''), 0, 100)) ?>...
                                    </div>
                                    
                                    <!-- Meta: Status, Labels, Assigned Users -->
                                    <div class="c-thread-item__meta">
                                        <!-- Status Badge -->
                                        <?php 
                                        $statusColors = [
                                            'open' => 'primary',
                                            'assigned' => 'warning',
                                            'pending' => 'warning',
                                            'closed' => 'success',
                                            'archived' => 'neutral'
                                        ];
                                        $statusLabels = [
                                            'open' => 'Offen',
                                            'assigned' => 'In Arbeit',
                                            'pending' => 'Ausstehend',
                                            'closed' => 'Erledigt',
                                            'archived' => 'Archiviert'
                                        ];
                                        $statusColor = $statusColors[$thread->status] ?? 'neutral';
                                        $statusLabel = $statusLabels[$thread->status] ?? ucfirst($thread->status);
                                        ?>
                                        <span class="c-badge c-badge--<?= $statusColor ?>"><?= $statusLabel ?></span>
                                        
                                        <!-- Labels -->
                                        <?php if ($thread->labels && count($thread->labels) > 0): ?>
                                            <?php foreach ($thread->labels as $label): ?>
                                                <span class="c-label-tag" style="--label-color: <?= htmlspecialchars($label->color) ?>">
                                                    <?= htmlspecialchars($label->name) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        
                                        <!-- Assigned Users -->
                                        <?php if ($thread->assignedUsers && count($thread->assignedUsers) > 0): ?>
                                            <div class="c-thread-item__assigned">
                                                <?php foreach ($thread->assignedUsers as $user): ?>
                                                    <?php
                                                    $name = $user->name ?? $user->email;
                                                    $initials = strtoupper(substr($name, 0, 1));
                                                    if (str_contains($name, ' ')) {
                                                        $parts = explode(' ', $name);
                                                        $initials = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
                                                    }
                                                    // Use avatar_color from database (with fallback)
                                                    $colorNum = $user->avatar_color ?? (($user->id % 8) + 1);
                                                    $colorClass = "c-avatar--color-{$colorNum}";
                                                    ?>
                                                    <div class="c-avatar c-avatar--xs <?= $colorClass ?>" title="<?= htmlspecialchars($name) ?>">
                                                        <?= $initials ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </div>
                </div>

                <!-- Thread Detail (Right Panel) -->
                <div class="c-inbox__thread-detail">
                    <div class="c-inbox__thread-detail-empty">
                        <svg width="80" height="80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <h3>Keine E-Mail ausgewählt</h3>
                        <p>Wähle einen Thread aus der Liste, um die E-Mails anzuzeigen.</p>
                    </div>
                </div>
                </div>
            </div>
        </main>
</body>
<script>
// Initialize global user settings for JavaScript
window.currentUserSettings = <?= json_encode($userSettings) ?>;

// Current user data (for note authorship, etc.)
window.currentUser = {
    id: <?= $currentUser->id ?>,
    name: <?= json_encode($currentUser->name ?? $currentUser->username ?? $currentUser->email) ?>,
    email: <?= json_encode($currentUser->email) ?>
};

// Multi-select mode
let multiSelectMode = false;
const selectedThreads = new Set();

// Thread sort order (default: desc = newest first)
let sortOrder = 'desc';

// Toggle thread sort order
document.getElementById('toggle-sort')?.addEventListener('click', function() {
    sortOrder = sortOrder === 'desc' ? 'asc' : 'desc';
    
    // Toggle icon visibility
    const descIcon = this.querySelector('.sort-icon-desc');
    const ascIcon = this.querySelector('.sort-icon-asc');
    
    if (sortOrder === 'desc') {
        descIcon.style.display = 'block';
        ascIcon.style.display = 'none';
    } else {
        descIcon.style.display = 'none';
        ascIcon.style.display = 'block';
    }
    
    // Reverse thread list
    const threadList = document.querySelector('.c-thread-list');
    const threads = Array.from(threadList.querySelectorAll('.c-thread-item'));
    threads.reverse().forEach(thread => threadList.appendChild(thread));
});

document.getElementById('toggle-multiselect')?.addEventListener('click', (e) => {
    multiSelectMode = !multiSelectMode;
    e.currentTarget.classList.toggle('is-active', multiSelectMode);
    
    if (!multiSelectMode) {
        // Clear selections
        selectedThreads.clear();
        document.querySelectorAll('.c-thread-item.is-selected').forEach(item => {
            item.classList.remove('is-selected');
        });
    }
});

// Thread item click
document.querySelectorAll('.c-thread-item').forEach(item => {
    item.addEventListener('click', (e) => {
        // Ctrl/Cmd + Click is handled by initThreadMultiSelect() in thread-detail-renderer.js
        if (e.ctrlKey || e.metaKey) {
            return; // Let the other handler manage it
        }
        
        const threadId = item.dataset.threadId;
        
        if (multiSelectMode) {
            // Toggle selection
            if (selectedThreads.has(threadId)) {
                selectedThreads.delete(threadId);
                item.classList.remove('is-selected');
            } else {
                selectedThreads.add(threadId);
                item.classList.add('is-selected');
            }
        } else {
            // Single select - remove previous selection and add is-active class
            document.querySelectorAll('.c-thread-item.is-selected, .c-thread-item.is-active').forEach(i => {
                i.classList.remove('is-selected', 'is-active');
            });
            item.classList.add('is-selected', 'is-active');
            
            // Load thread details
            loadThreadDetail(threadId);
        }
    });
});

// Load thread detail via AJAX
async function loadThreadDetail(threadId) {
    const detailPanel = document.querySelector('.c-inbox__thread-detail');
    const detailTitle = document.getElementById('detail-title');
    const detailActions = document.getElementById('detail-actions');
    
    // Show loading state
    detailPanel.innerHTML = `
        <div class="c-inbox__thread-detail-empty">
            <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10" stroke-width="2"/>
            </svg>
            <p>Lade Thread...</p>
        </div>
    `;
    
    if (detailTitle) detailTitle.textContent = 'Lädt...';
    if (detailActions) detailActions.style.display = 'none';
    
    try {
        const response = await fetch('/api/threads/' + threadId + '/details');
        
        if (!response.ok) {
            throw new Error('Failed to load thread');
        }
        
        const data = await response.json();
        
        // Render HTML using renderer module (updates toolbar automatically)
        const html = renderThreadDetail(data);
        detailPanel.innerHTML = html;
        
        // Auto-resize iframes
        detailPanel.querySelectorAll('.c-email-message__iframe').forEach(iframe => {
            iframe.onload = function() {
                iframe.style.height = iframe.contentWindow.document.body.scrollHeight + 'px';
            };
        });
        
        // Attach note form handler
        const noteForm = detailPanel.querySelector('.c-note-form');
        if (noteForm) {
            noteForm.addEventListener('submit', handleNoteSubmit);
        }
        
    } catch (error) {
        console.error('Error loading thread:', error);
        detailPanel.innerHTML = `
            <div class="c-inbox__thread-detail-empty">
                <svg width="80" height="80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3>Fehler beim Laden</h3>
                <p>Der Thread konnte nicht geladen werden.</p>
            </div>
        `;
        if (detailTitle) detailTitle.textContent = 'Fehler';
        if (detailActions) detailActions.style.display = 'none';
    }
}

// Handle note form submission
async function handleNoteSubmit(e) {
    e.preventDefault();
    
    const form = e.target;
    const threadId = form.dataset.threadId;
    const textarea = form.querySelector('textarea');
    const content = textarea.value.trim();
    
    if (!content) return;
    
    try {
        const response = await fetch('/api/threads/' + threadId + '/notes', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ content })
        });
        
        if (!response.ok) {
            throw new Error('Failed to add note');
        }
        
        // Reload thread details
        textarea.value = '';
        loadThreadDetail(threadId);
        
    } catch (error) {
        console.error('Error adding note:', error);
        alert('Fehler beim Hinzufügen der Notiz');
    }
}

// ============================================================================
// USER DROPDOWN
// ============================================================================

document.addEventListener('DOMContentLoaded', () => {
    const trigger = document.getElementById('user-dropdown-trigger');
    const menu = document.getElementById('user-dropdown-menu');
    
    if (!trigger || !menu) return;
    
    // Toggle dropdown
    trigger.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = menu.classList.contains('is-open');
        
        if (isOpen) {
            closeDropdown();
        } else {
            openDropdown();
        }
    });
    
    // Close on click outside
    document.addEventListener('click', (e) => {
        if (!trigger.contains(e.target) && !menu.contains(e.target)) {
            closeDropdown();
        }
    });
    
    // Close on ESC key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && menu.classList.contains('is-open')) {
            closeDropdown();
            trigger.focus();
        }
    });
    
    function openDropdown() {
        menu.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');
    }
    
    function closeDropdown() {
        menu.classList.remove('is-open');
        trigger.setAttribute('aria-expanded', 'false');
    }
});

// ============================================================================
// AUTO-REFRESH POLLING (15 seconds interval)
// ============================================================================

let pollingInterval = null;
let lastRefreshTime = Date.now();
let isRefreshing = false;

async function refreshThreadList() {
    if (isRefreshing) return; // Prevent concurrent refreshes
    
    isRefreshing = true;
    const refreshBtn = document.getElementById('refresh-threads-btn');
    
    try {
        // Visual feedback: rotate icon
        if (refreshBtn) {
            refreshBtn.classList.add('is-loading');
        }
        
        // Fetch updated thread list
        const response = await fetch('/api/threads?limit=50&sort=desc');
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success && result.data) {
            // Remember currently selected thread
            const activeThreadId = document.querySelector('.c-thread-item.is-active')?.dataset.threadId;
            
            // Update thread list container
            const threadListContainer = document.querySelector('.c-thread-list');
            if (threadListContainer) {
                threadListContainer.innerHTML = ''; // Clear existing
                
                result.data.forEach(thread => {
                    const threadElement = createThreadElement(thread);
                    threadListContainer.appendChild(threadElement);
                    
                    // Restore active state if thread still exists
                    if (activeThreadId && thread.id == activeThreadId) {
                        threadElement.classList.add('is-active');
                    }
                });
                
                // Re-attach click handlers
                attachThreadClickHandlers();
                
                // Re-apply current filters
                applyFilters();
                
                console.log('[Polling] Thread list refreshed:', result.data.length, 'threads');
            }
            
            lastRefreshTime = Date.now();
        }
    } catch (error) {
        console.error('[Polling] Refresh failed:', error);
        // Silent fail - don't interrupt user workflow
    } finally {
        isRefreshing = false;
        
        // Remove loading state
        if (refreshBtn) {
            refreshBtn.classList.remove('is-loading');
        }
    }
}

// Start polling on page load
function startPolling() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
    
    pollingInterval = setInterval(() => {
        refreshThreadList();
    }, 15000); // 15 seconds
    
    console.log('[Polling] Auto-refresh started (15s interval)');
}

// Stop polling (e.g., when user is idle or tab is hidden)
function stopPolling() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
        console.log('[Polling] Auto-refresh stopped');
    }
}

// Pause polling when tab is hidden (battery saving)
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        stopPolling();
    } else {
        startPolling();
        refreshThreadList(); // Immediate refresh when tab becomes visible
    }
});

// Start polling immediately
startPolling();

// Refresh Threads Button (manual trigger)
document.getElementById('refresh-threads-btn')?.addEventListener('click', function() {
    refreshThreadList();
});

// New Email Button
document.getElementById('new-email-btn')?.addEventListener('click', function() {
    if (typeof showEmailComposer === 'function') {
        showEmailComposer('new', {
            fromEmail: 'Shared Inbox'
        });
    } else {
        console.error('[Inbox] Email composer not loaded');
    }
});

// Sidebar "Neue E-Mail" Button
document.getElementById('sidebar-new-email-btn')?.addEventListener('click', function() {
    if (typeof showEmailComposer === 'function') {
        showEmailComposer('new', {
            fromEmail: 'Shared Inbox'
        });
    } else {
        console.error('[Inbox] Email composer not loaded');
    }
});

// ============================================================================
// SEARCH FUNCTIONALITY
// ============================================================================

let searchTimeout;
const searchInput = document.querySelector('.c-header__search');

if (searchInput) {
    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.trim();
        
        // Clear previous timeout
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        // Debounce search (300ms)
        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 300);
    });
    
    // Clear search on ESC key
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            this.value = '';
            performSearch('');
        }
    });
}

// Global keyboard shortcut: Ctrl+E to focus search
document.addEventListener('keydown', function(e) {
    // Ctrl+E or Cmd+E (Mac)
    if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
        e.preventDefault();
        const searchInput = document.getElementById('global-search');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }
});

async function performSearch(query) {
    const threadList = document.querySelector('.c-thread-list');
    
    if (!threadList) return;
    
    // Show loading state
    threadList.style.opacity = '0.5';
    
    try {
        // Build API URL with search parameter (empty query returns all with relations)
        let url = '/api/threads?limit=100';
        if (query && query.trim() !== '') {
            url += '&search=' + encodeURIComponent(query);
        }
        
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error('Search failed');
        }
        
        const data = await response.json();
        
        // Clear current list
        threadList.innerHTML = '';
        
        if (data.threads && data.threads.length > 0) {
            // Render threads (simplified - you might want to use a template)
            data.threads.forEach(thread => {
                const threadItem = createThreadItem(thread);
                threadList.appendChild(threadItem);
            });
            
            // Re-attach click handlers
            attachThreadClickHandlers();
        } else {
            // No results
            threadList.innerHTML = `
                <div class="c-thread-list__empty">
                    <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <p>${query ? 'Keine Ergebnisse gefunden' : 'Keine Threads vorhanden'}</p>
                </div>
            `;
        }
        
        console.log('[Search] Results:', data.threads.length, 'threads');
        
    } catch (error) {
        console.error('[Search] Failed:', error);
        threadList.innerHTML = `
            <div class="c-thread-list__empty">
                <p>Fehler beim Suchen. Bitte versuchen Sie es erneut.</p>
            </div>
        `;
    } finally {
        threadList.style.opacity = '1';
    }
}

// Helper: Create thread list item HTML
function createThreadItem(thread) {
    const div = document.createElement('div');
    div.className = 'c-thread-item';
    if (!thread.is_read) div.classList.add('is-unread');
    div.dataset.threadId = thread.id;
    
    // Get status badge
    const statusColors = {
        'open': 'primary',
        'assigned': 'warning',
        'pending': 'warning',
        'closed': 'success',
        'archived': 'neutral'
    };
    const statusLabels = {
        'open': 'Offen',
        'assigned': 'In Arbeit',
        'pending': 'Ausstehend',
        'closed': 'Erledigt',
        'archived': 'Archiviert'
    };
    
    // Build labels HTML
    let labelsHtml = '';
    if (thread.labels && thread.labels.length > 0) {
        labelsHtml = thread.labels.map(label => 
            `<span class="c-label-tag" style="--label-color: ${escapeHtml(label.color)}">${escapeHtml(label.name)}</span>`
        ).join('');
    }
    
    // Build assigned users HTML
    let assignedHtml = '';
    if (thread.assigned_users && thread.assigned_users.length > 0) {
        const avatars = thread.assigned_users.map(user => {
            const name = user.name || user.email;
            let initials = name.charAt(0).toUpperCase();
            if (name.includes(' ')) {
                const parts = name.split(' ');
                initials = parts[0].charAt(0).toUpperCase() + parts[parts.length - 1].charAt(0).toUpperCase();
            }
            // Use avatar_color from database (with fallback)
            const colorNum = user.avatar_color || ((user.id % 8) + 1);
            const colorClass = `c-avatar--color-${colorNum}`;
            return `<div class="c-avatar c-avatar--xs ${colorClass}" title="${escapeHtml(name)}">${initials}</div>`;
        }).join('');
        assignedHtml = `<div class="c-thread-item__assigned">${avatars}</div>`;
    }
    
    div.innerHTML = `
        <div class="c-thread-item__content">
            <div class="c-thread-item__header">
                <span class="c-thread-item__sender">${escapeHtml(thread.sender_name || thread.sender_email)}</span>
                <span class="c-thread-item__time">${formatTimestamp(thread.last_message_at)}</span>
            </div>
            <div class="c-thread-item__subject">
                ${thread.message_count > 1 ? `<span class="c-badge c-badge--primary">${thread.message_count}</span>` : ''}
                ${escapeHtml(thread.subject || '(Kein Betreff)')}
            </div>
            <div class="c-thread-item__meta">
                <span class="c-badge c-badge--${statusColors[thread.status] || 'neutral'}">${statusLabels[thread.status] || thread.status}</span>
                ${labelsHtml}
                ${assignedHtml}
            </div>
        </div>
    `;
    
    return div;
}

// Helper: Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Helper: Format timestamp
function formatTimestamp(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;
    
    // Less than 24 hours: show time
    if (diff < 86400000) {
        return date.toLocaleTimeString('de-DE', { hour: '2-digit', minute: '2-digit' });
    }
    
    // Same year: show date without year
    if (date.getFullYear() === now.getFullYear()) {
        return date.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit' });
    }
    
    // Different year: show full date
    return date.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: '2-digit' });
}

// Helper: Re-attach click handlers after search
function attachThreadClickHandlers() {
    document.querySelectorAll('.c-thread-item').forEach(item => {
        item.addEventListener('click', (e) => {
            if (e.ctrlKey || e.metaKey) return;
            
            const threadId = item.dataset.threadId;
            
            if (multiSelectMode) {
                if (selectedThreads.has(threadId)) {
                    selectedThreads.delete(threadId);
                    item.classList.remove('is-selected');
                } else {
                    selectedThreads.add(threadId);
                    item.classList.add('is-selected');
                }
            } else {
                document.querySelectorAll('.c-thread-item.is-selected, .c-thread-item.is-active').forEach(i => {
                    i.classList.remove('is-selected', 'is-active');
                });
                item.classList.add('is-selected', 'is-active');
                loadThreadDetail(threadId);
            }
        });
    });
}

// Status Filter Toggle & Logic
let activeStatusFilters = new Set();

document.getElementById('status-filter-toggle')?.addEventListener('click', function(e) {
    e.preventDefault();
    const dropdown = document.getElementById('status-filter-dropdown');
    const isExpanded = this.classList.contains('is-expanded');
    
    if (isExpanded) {
        dropdown.style.display = 'none';
        this.classList.remove('is-expanded');
    } else {
        dropdown.style.display = 'block';
        this.classList.add('is-expanded');
        
        // Add click handlers if not already added
        if (!dropdown.dataset.initialized) {
            dropdown.querySelectorAll('.c-status-filter__item').forEach(item => {
                item.addEventListener('click', function() {
                    toggleStatusFilter(this.dataset.status);
                });
            });
            dropdown.dataset.initialized = 'true';
        }
    }
});

// Posteingang - Zeigt alle nicht-archivierten Threads (Standard)
document.getElementById('filter-inbox')?.addEventListener('click', function(e) {
    e.preventDefault();
    
    // Clear all filters (shows all non-archived)
    activeStatusFilters.clear();
    activeLabelFilters.clear();
    activeUserFilters.clear();
    
    // Update UI
    updateStatusFilterUI();
    
    // Apply filters (will hide archived by default)
    applyFilters();
    
    // Visual feedback: Mark link as active
    document.querySelectorAll('.c-sidebar__link').forEach(link => {
        link.classList.remove('is-active');
    });
    this.classList.add('is-active');
});

// Archiv anzeigen - Nur archivierte Threads
document.getElementById('show-archived-filter')?.addEventListener('click', function(e) {
    e.preventDefault();
    
    // Clear all other filters
    activeStatusFilters.clear();
    activeLabelFilters.clear();
    activeUserFilters.clear();
    
    // Activate only "archived" status
    activeStatusFilters.add('archived');
    
    // Update UI
    updateStatusFilterUI();
    
    // Apply filters
    applyFilters();
    
    // Visual feedback: Mark link as active
    document.querySelectorAll('.c-sidebar__link').forEach(link => {
        link.classList.remove('is-active');
    });
    this.classList.add('is-active');
});

function toggleStatusFilter(status) {
    if (activeStatusFilters.has(status)) {
        activeStatusFilters.delete(status);
    } else {
        activeStatusFilters.add(status);
    }
    
    updateStatusFilterUI();
    applyFilters();
}

function updateStatusFilterUI() {
    const items = document.querySelectorAll('.c-status-filter__item');
    
    if (activeStatusFilters.size === 0) {
        items.forEach(item => {
            item.classList.remove('is-active', 'is-inactive');
        });
    } else {
        items.forEach(item => {
            const status = item.dataset.status;
            if (activeStatusFilters.has(status)) {
                item.classList.add('is-active');
                item.classList.remove('is-inactive');
            } else {
                item.classList.remove('is-active');
                item.classList.add('is-inactive');
            }
        });
    }
}

// Label Filter Toggle & Logic
let activeLabelFilters = new Set();
let allLabels = [];

document.getElementById('labels-filter-toggle')?.addEventListener('click', async function(e) {
    e.preventDefault();
    const dropdown = document.getElementById('label-filter-dropdown');
    const isExpanded = this.classList.contains('is-expanded');
    
    if (isExpanded) {
        dropdown.style.display = 'none';
        this.classList.remove('is-expanded');
    } else {
        dropdown.style.display = 'block';
        this.classList.add('is-expanded');
        
        // Load labels on first open
        if (allLabels.length === 0) {
            await loadLabelFilters();
        }
    }
});

// User Filter Toggle & Logic
let activeUserFilters = new Set();
let allUsers = [];

document.getElementById('users-filter-toggle')?.addEventListener('click', async function(e) {
    e.preventDefault();
    const dropdown = document.getElementById('user-filter-dropdown');
    const isExpanded = this.classList.contains('is-expanded');
    
    if (isExpanded) {
        dropdown.style.display = 'none';
        this.classList.remove('is-expanded');
    } else {
        dropdown.style.display = 'block';
        this.classList.add('is-expanded');
        
        // Load users on first open
        if (allUsers.length === 0) {
            await loadUserFilters();
        }
    }
});

async function loadLabelFilters() {
    const dropdown = document.getElementById('label-filter-dropdown');
    
    try {
        const response = await fetch('/api/labels');
        if (!response.ok) throw new Error('Failed to load labels');
        const data = await response.json();
        allLabels = Array.isArray(data) ? data : (data.labels || []);
        
        if (allLabels.length === 0) {
            dropdown.innerHTML = '<div class="c-label-filter__loading">Keine Labels vorhanden</div>';
            return;
        }
        
        // Render label filter items
        const itemsHtml = allLabels.map(label => `
            <div class="c-label-filter__item" data-label-id="${label.id}">
                <div class="c-label-filter__color" style="background-color: ${label.color}"></div>
                <div class="c-label-filter__name">${label.name}</div>
            </div>
        `).join('');
        
        dropdown.innerHTML = itemsHtml;
        
        // Add click handlers
        dropdown.querySelectorAll('.c-label-filter__item').forEach(item => {
            item.addEventListener('click', function() {
                toggleLabelFilter(parseInt(this.dataset.labelId));
            });
        });
        
    } catch (error) {
        console.error('Error loading label filters:', error);
        dropdown.innerHTML = '<div class="c-label-filter__loading">Fehler beim Laden</div>';
    }
}

function toggleLabelFilter(labelId) {
    if (activeLabelFilters.has(labelId)) {
        activeLabelFilters.delete(labelId);
    } else {
        activeLabelFilters.add(labelId);
    }
    
    updateFilterUI();
    applyFilters();
}

async function loadUserFilters() {
    const dropdown = document.getElementById('user-filter-dropdown');
    
    try {
        const response = await fetch('/api/users');
        if (!response.ok) throw new Error('Failed to load users');
        const data = await response.json();
        allUsers = Array.isArray(data) ? data : (data.users || []);
        
        if (allUsers.length === 0) {
            dropdown.innerHTML = '<div class="c-label-filter__loading">Keine Benutzer vorhanden</div>';
            return;
        }
        
        // Render user filter items with avatars
        let itemsHtml = allUsers.map(user => {
            const name = user.name || user.username || user.email;
            let initials = name.charAt(0).toUpperCase();
            if (name.includes(' ')) {
                const parts = name.split(' ');
                initials = parts[0].charAt(0).toUpperCase() + parts[parts.length - 1].charAt(0).toUpperCase();
            }
            // Use avatar_color from database (with fallback)
            const colorNum = user.avatar_color || ((user.id % 8) + 1);
            const colorClass = `c-avatar--color-${colorNum}`;
            return `
                <div class="c-label-filter__item" data-user-id="${user.id}">
                    <div class="c-avatar c-avatar--xs ${colorClass}">${initials}</div>
                    <div class="c-label-filter__name">${name}</div>
                </div>
            `;
        }).join('');
        
        dropdown.innerHTML = itemsHtml;
        
        // Add click handlers
        dropdown.querySelectorAll('.c-label-filter__item').forEach(item => {
            item.addEventListener('click', function() {
                const userId = this.dataset.userId;
                toggleUserFilter(userId === 'unassigned' ? 'unassigned' : parseInt(userId));
            });
        });
        
    } catch (error) {
        console.error('Error loading user filters:', error);
        dropdown.innerHTML = '<div class="c-label-filter__loading">Fehler beim Laden</div>';
    }
}

function toggleUserFilter(userId) {
    if (activeUserFilters.has(userId)) {
        activeUserFilters.delete(userId);
    } else {
        activeUserFilters.add(userId);
    }
    
    updateFilterUI();
    applyFilters();
}

function updateFilterUI() {
    // Update label filter items
    const labelItems = document.querySelectorAll('#label-filter-dropdown .c-label-filter__item');
    
    if (activeLabelFilters.size === 0) {
        labelItems.forEach(item => {
            item.classList.remove('is-active', 'is-inactive');
        });
    } else {
        labelItems.forEach(item => {
            const labelId = parseInt(item.dataset.labelId);
            if (activeLabelFilters.has(labelId)) {
                item.classList.add('is-active');
                item.classList.remove('is-inactive');
            } else {
                item.classList.remove('is-active');
                item.classList.add('is-inactive');
            }
        });
    }
    
    // Update user filter items
    const userItems = document.querySelectorAll('#user-filter-dropdown .c-label-filter__item');
    
    if (activeUserFilters.size === 0) {
        userItems.forEach(item => {
            item.classList.remove('is-active', 'is-inactive');
        });
    } else {
        userItems.forEach(item => {
            const userId = item.dataset.userId === 'unassigned' ? 'unassigned' : parseInt(item.dataset.userId);
            if (activeUserFilters.has(userId)) {
                item.classList.add('is-active');
                item.classList.remove('is-inactive');
            } else {
                item.classList.remove('is-active');
                item.classList.add('is-inactive');
            }
        });
    }
}

function applyFilters() {
    const threadItems = document.querySelectorAll('.c-thread-item');
    
    // Special case: If no filters active, hide archived threads by default
    const shouldHideArchived = activeStatusFilters.size === 0 || 
                               (activeStatusFilters.size > 0 && !activeStatusFilters.has('archived'));
    
    // Combined filter: Status AND Labels AND Users
    threadItems.forEach(item => {
        let visible = true;
        
        const statusBadge = item.querySelector('.c-badge');
        const threadStatus = getThreadStatus(statusBadge);
        
        // Always hide archived unless explicitly filtered
        if (shouldHideArchived && threadStatus === 'archived') {
            item.style.display = 'none';
            return;
        }
        
        // Status filter (if active)
        if (activeStatusFilters.size > 0) {
            visible = visible && activeStatusFilters.has(threadStatus);
        }
        
        // Label filter (if active)
        if (activeLabelFilters.size > 0) {
            const threadLabels = Array.from(item.querySelectorAll('.c-label-tag'))
                .map(tag => tag.textContent.trim());
            
            const hasActiveLabel = allLabels.some(label => 
                activeLabelFilters.has(label.id) && threadLabels.includes(label.name)
            );
            
            visible = visible && hasActiveLabel;
        }
        
        // User filter (if active)
        if (activeUserFilters.size > 0) {
            const assignedAvatars = item.querySelectorAll('.c-thread-item__assigned .c-avatar');
            
            // Show threads assigned to selected users
            const assignedUserNames = Array.from(assignedAvatars)
                .map(avatar => avatar.getAttribute('title'));
            
            const hasActiveUser = allUsers.some(user => {
                const userName = user.name || user.username || user.email;
                return activeUserFilters.has(user.id) && assignedUserNames.includes(userName);
            });
            
            visible = visible && hasActiveUser;
        }
        
        item.style.display = visible ? 'flex' : 'none';
    });
}

function getThreadStatus(badgeElement) {
    if (!badgeElement) return 'open';
    
    const text = badgeElement.textContent.trim();
    
    if (text === 'Offen') return 'open';
    if (text === 'In Arbeit') return 'assigned';
    if (text === 'Erledigt') return 'closed';
    if (text === 'Ausstehend') return 'pending';
    if (text === 'Archiviert') return 'archived';
    
    return 'open';
}
</script>
</html>

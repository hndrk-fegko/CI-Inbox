<?php
/**
 * Application Header Partial
 * 
 * Reusable header component with logo, page title, and user dropdown.
 * Supports customization via optional variables.
 * 
 * Usage:
 * <?php 
 * $pageTitle = 'Settings';
 * $activePage = 'settings';
 * $showSearch = false; // Optional: hide search bar
 * $headerActions = '<button>Custom Action</button>'; // Optional: right-side actions
 * include __DIR__ . '/../views/partials/header.php';
 * ?>
 * 
 * Required:
 * - $_SESSION['user_email'] - For user dropdown
 * 
 * Optional variables:
 * - $pageTitle - Page title shown next to logo (default: none)
 * - $activePage - Current page for dropdown active state
 * - $showSearch - Whether to show search bar (default: false)
 * - $headerActions - Additional HTML for right side before user dropdown
 * - $dropdownItems - Custom items to add to user dropdown
 */

declare(strict_types=1);

// Default values
$pageTitle = $pageTitle ?? '';
$activePage = $activePage ?? '';
$showSearch = $showSearch ?? false;
$headerActions = $headerActions ?? '';
?>

<header class="c-header l-app__header">
    <div class="c-header__left">
        <a href="/inbox.php" class="c-header__logo-link">
            <svg class="c-header__logo" width="32" height="32" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                <path d="M12 18L24 26L36 18M12 18V30C12 30.5304 12.2107 31.0391 12.5858 31.4142C12.9609 31.7893 13.4696 32 14 32H34C34.5304 32 35.0391 31.7893 35.4142 31.4142C35.7893 31.0391 36 30.5304 36 30V18M12 18C12 17.4696 12.2107 16.9609 12.5858 16.5858C12.9609 16.2107 13.4696 16 14 16H34C34.5304 16 35.0391 16.2107 35.4142 16.5858C35.7893 16.9609 36 17.4696 36 18Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <h1 class="c-header__title">CI-Inbox</h1>
        </a>
        <?php if (!empty($pageTitle)): ?>
            <h2 class="c-header__page-title"><?= htmlspecialchars($pageTitle) ?></h2>
        <?php endif; ?>
    </div>
    
    <?php if ($showSearch): ?>
    <div class="c-header__center">
        <input type="search" class="c-header__search" id="global-search" placeholder="Suche in E-Mails..." aria-label="Suche">
    </div>
    <?php endif; ?>
    
    <div class="c-header__right">
        <?php if (!empty($headerActions)): ?>
            <?= $headerActions ?>
        <?php endif; ?>
        
        <?php include __DIR__ . '/user-dropdown.php'; ?>
    </div>
</header>

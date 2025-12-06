<?php
/**
 * User Dropdown Partial
 * 
 * Reusable user dropdown component for header navigation.
 * Supports conditional menu items based on user role and enabled features.
 * 
 * Usage:
 * <?php 
 * $activePage = 'inbox'; // Current page for highlighting
 * include __DIR__ . '/../views/partials/user-dropdown.php';
 * ?>
 * 
 * Required variables:
 * - $_SESSION['user_email'] - User's email address
 * - $_SESSION['user_name'] - User's display name (optional)
 * - $_SESSION['user_role'] - User's role (user/admin)
 * 
 * Optional variables:
 * - $activePage - Current page identifier for active state
 * - $dropdownItems - Custom menu items to add (array of arrays with href, icon, label)
 */

declare(strict_types=1);

// Get user info from session
$userEmail = $_SESSION['user_email'] ?? '';
$userName = $_SESSION['user_name'] ?? 'User';
$userRole = $_SESSION['user_role'] ?? 'user';
$userInitials = strtoupper(substr($userEmail, 0, 2));

// Calculate proper initials from name
if (!empty($userName) && strpos($userName, ' ') !== false) {
    $parts = explode(' ', trim($userName));
    $userInitials = strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
} elseif (!empty($userName)) {
    $userInitials = strtoupper(substr($userName, 0, 2));
}

// Default active page
$activePage = $activePage ?? '';

// Build menu items
$menuItems = [];

// Core navigation items
$menuItems[] = [
    'href' => '/inbox.php',
    'id' => 'inbox',
    'icon' => '<path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>',
    'label' => 'Inbox'
];

$menuItems[] = [
    'href' => '/settings.php',
    'id' => 'settings',
    'icon' => '<path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>',
    'label' => 'Profile'
];

// Admin-only items
if ($userRole === 'admin') {
    $menuItems[] = [
        'href' => '/admin-settings.php',
        'id' => 'admin-settings',
        'icon' => '<path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>',
        'label' => 'System Settings'
    ];
}

// Allow custom items to be added (for future module extensions)
if (isset($dropdownItems) && is_array($dropdownItems)) {
    $menuItems = array_merge($menuItems, $dropdownItems);
}
?>

<div class="c-user-dropdown">
    <button class="c-user-dropdown__trigger" id="user-dropdown-trigger" aria-expanded="false" aria-haspopup="true">
        <div class="c-avatar c-avatar--sm">
            <span class="c-avatar__initials"><?= htmlspecialchars($userInitials) ?></span>
        </div>
        <svg class="c-user-dropdown__chevron" width="16" height="16" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
        </svg>
    </button>
    
    <div class="c-user-dropdown__menu" id="user-dropdown-menu" role="menu">
        <!-- User Info Section -->
        <div class="c-user-dropdown__header">
            <div class="c-avatar c-avatar--md">
                <span class="c-avatar__initials"><?= htmlspecialchars($userInitials) ?></span>
            </div>
            <div class="c-user-dropdown__info">
                <div class="c-user-dropdown__name"><?= htmlspecialchars($userName) ?></div>
                <div class="c-user-dropdown__email"><?= htmlspecialchars($userEmail) ?></div>
            </div>
        </div>
        
        <div class="c-user-dropdown__divider"></div>
        
        <!-- Navigation Items -->
        <?php foreach ($menuItems as $item): ?>
            <?php 
            $isActive = ($activePage === $item['id']);
            $activeClass = $isActive ? ' c-user-dropdown__item--active' : '';
            ?>
            <a href="<?= htmlspecialchars($item['href']) ?>" 
               class="c-user-dropdown__item<?= $activeClass ?>"
               role="menuitem">
                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <?= $item['icon'] ?>
                </svg>
                <span><?= htmlspecialchars($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
        
        <div class="c-user-dropdown__divider"></div>
        
        <!-- Logout -->
        <form method="POST" action="/logout.php" class="c-user-dropdown__logout-form">
            <button type="submit" class="c-user-dropdown__item c-user-dropdown__item--danger" role="menuitem">
                <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 001 1h12a1 1 0 001-1V4a1 1 0 00-1-1H3zm11 4.414l-4.293 4.293a1 1 0 01-1.414 0L4 7.414 5.414 6l3.293 3.293L13.586 6 15 7.414z" clip-rule="evenodd"/>
                </svg>
                <span>Logout</span>
            </button>
        </form>
    </div>
</div>

<script>
// User Dropdown Toggle (if not already initialized)
if (typeof window.UserDropdown === 'undefined') {
    window.UserDropdown = {
        init: function() {
            const trigger = document.getElementById('user-dropdown-trigger');
            const menu = document.getElementById('user-dropdown-menu');
            
            if (!trigger || !menu) return;
            
            // Toggle on click
            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                const isOpen = trigger.getAttribute('aria-expanded') === 'true';
                this.toggle(!isOpen);
            });
            
            // Close on outside click
            document.addEventListener('click', (e) => {
                if (!trigger.contains(e.target) && !menu.contains(e.target)) {
                    this.toggle(false);
                }
            });
            
            // Close on Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && trigger.getAttribute('aria-expanded') === 'true') {
                    this.toggle(false);
                    trigger.focus();
                }
            });
        },
        
        toggle: function(open) {
            const trigger = document.getElementById('user-dropdown-trigger');
            const menu = document.getElementById('user-dropdown-menu');
            
            trigger.setAttribute('aria-expanded', open.toString());
            menu.classList.toggle('is-open', open);
        }
    };
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => window.UserDropdown.init());
    } else {
        window.UserDropdown.init();
    }
}
</script>

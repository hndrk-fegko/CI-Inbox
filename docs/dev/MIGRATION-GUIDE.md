# Admin Settings Migration Guide

**Version:** 1.0  
**Date:** December 2025  
**Status:** Production Ready

---

## Overview

This guide documents the migration from the monolithic `admin-settings.php` to the new modular `admin-settings-new.php` system.

---

## Quick Start

### For Users

1. Navigate to `/admin-settings-new.php` instead of `/admin-settings.php`
2. The interface is largely the same, with additional modules available
3. Click any card on the Overview tab to access that module's settings

### For Developers

```php
// Old module registration (centralized)
// Not needed anymore!

// New module registration (auto-discovery)
// Just create a file in src/views/admin/modules/

// Example: src/views/admin/modules/090-custom.php
return [
    'id' => 'custom',
    'title' => 'Custom Module',
    'priority' => 90,
    'icon' => '<path d="..."/>',
    'card' => function() { /* dashboard card HTML */ },
    'content' => function() { /* full page content HTML */ },
    'script' => function() { /* JavaScript code */ }
];
```

---

## Architecture

### Auto-Discovery System

The new admin panel uses auto-discovery to load modules:

```php
// src/public/admin-settings-new.php (line 47-59)
$modulesPath = dirname(__DIR__) . '/views/admin/modules/';
$moduleFiles = glob($modulesPath . '*.php');
sort($moduleFiles);

foreach ($moduleFiles as $file) {
    $module = include $file;
    if (is_array($module) && isset($module['id'], $module['title'])) {
        $modules[] = $module;
    }
}

// Sort by priority
usort($modules, fn($a, $b) => ($a['priority'] ?? 100) <=> ($b['priority'] ?? 100));
```

### Module Contract

Each module must return an array with these keys:

| Key | Type | Required | Description |
|-----|------|----------|-------------|
| `id` | string | ✅ | Unique identifier (used for tab ID) |
| `title` | string | ✅ | Display title in navigation |
| `priority` | int | ❌ | Sort order (lower = earlier, default: 100) |
| `icon` | string | ❌ | SVG path for icon |
| `card` | callable | ❌ | Returns dashboard card HTML |
| `content` | callable | ❌ | Returns full tab content HTML |
| `script` | callable | ❌ | Returns JavaScript code |

### File Naming Convention

Files are sorted alphabetically, so use numeric prefixes:
- `010-imap.php` - Priority 10
- `020-smtp.php` - Priority 20
- `030-cron.php` - Priority 30
- etc.

The numeric prefix helps organize files visually AND sets initial sort order.
The `priority` key in the module array allows fine-tuning if needed.

---

## Module Structure

### Minimal Module

```php
<?php
return [
    'id' => 'mymodule',
    'title' => 'My Module',
    'priority' => 100,
    
    'card' => function() {
        ?>
        <div class="c-admin-card" data-module="mymodule">
            <h3>My Module</h3>
            <p>Click to configure</p>
        </div>
        <?php
    },
    
    'content' => function() {
        ?>
        <h2>My Module Configuration</h2>
        <p>Content goes here...</p>
        <?php
    }
];
```

### Full Module Template

```php
<?php
/**
 * Admin Tab Module: My Module
 * 
 * Description of what this module does.
 */

return [
    'id' => 'mymodule',
    'title' => 'My Module',
    'priority' => 100,
    'icon' => '<path d="M..."/>',
    
    'card' => function() {
        ?>
        <div class="c-admin-card" data-module="mymodule" style="cursor: pointer;">
            <div class="c-admin-card__header">
                <div class="c-admin-card__icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M..."/>
                    </svg>
                </div>
                <h3 class="c-admin-card__title">My Module</h3>
            </div>
            <p class="c-admin-card__description">Short description</p>
            <div class="c-admin-card__content">
                <div class="c-info-row">
                    <span class="c-info-row__label">Status</span>
                    <span id="mymodule-status" class="c-status-badge c-status-badge--success">
                        <span class="status-dot"></span>Active
                    </span>
                </div>
            </div>
        </div>
        <?php
    },
    
    'content' => function() {
        ?>
        <!-- Header -->
        <div style="margin-bottom: 2rem;">
            <h3 style="margin: 0 0 0.5rem 0;">My Module</h3>
            <p style="color: #666;">Description of this module.</p>
        </div>
        
        <!-- Info Box -->
        <div style="background: #E3F2FD; border-left: 4px solid #2196F3; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem;">
            <strong style="color: #1565C0;">About This Module</strong>
            <p style="margin: 0.5rem 0 0 0; color: #1976D2;">
                Helpful information for the user.
            </p>
        </div>
        
        <!-- Alert Container -->
        <div id="mymodule-alert" style="margin-bottom: 1rem;"></div>
        
        <!-- Main Content -->
        <div style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
            <h4>Configuration</h4>
            <form id="mymodule-form">
                <!-- Form fields -->
                <button type="submit" class="c-button c-button--primary">Save</button>
            </form>
        </div>
        <?php
    },
    
    'script' => function() {
        ?>
        const MyModule = {
            init() {
                console.log('[MyModule] Initializing...');
                this.loadConfig();
                this.bindEvents();
            },
            
            bindEvents() {
                const form = document.getElementById('mymodule-form');
                if (form) {
                    form.addEventListener('submit', (e) => {
                        e.preventDefault();
                        this.saveConfig();
                    });
                }
            },
            
            async loadConfig() {
                try {
                    const response = await fetch('/api/admin/mymodule/config');
                    const data = await response.json();
                    if (data.success) {
                        this.populateForm(data.data);
                    }
                } catch (error) {
                    console.error('[MyModule] Failed to load:', error);
                }
            },
            
            async saveConfig() {
                // Implementation
            },
            
            showAlert(message, type = 'info') {
                const container = document.getElementById('mymodule-alert');
                const alertClass = type === 'success' ? 'c-alert--success' : 
                                   type === 'error' ? 'c-alert--error' : 'c-alert--info';
                container.innerHTML = `<div class="c-alert ${alertClass} is-visible">${message}</div>`;
                if (type !== 'error') {
                    setTimeout(() => container.innerHTML = '', 5000);
                }
            }
        };
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => MyModule.init());
        } else {
            MyModule.init();
        }
        <?php
    }
];
```

---

## CSS Classes Reference

### Buttons
- `.c-button` - Base button
- `.c-button--primary` - Blue primary action
- `.c-button--secondary` - Gray secondary action
- `.c-button--danger` - Red destructive action

### Inputs
- `.c-input` - Form input/select
- `.c-input-group` - Label + input wrapper

### Cards
- `.c-admin-card` - Dashboard card container
- `.c-admin-card__header` - Card header section
- `.c-admin-card__title` - Card title
- `.c-admin-card__description` - Card description
- `.c-admin-card__content` - Card content area

### Status
- `.c-status-badge` - Status indicator
- `.c-status-badge--success` - Green
- `.c-status-badge--warning` - Yellow
- `.c-status-badge--error` - Red
- `.status-dot` - Animated dot inside badge

### Alerts
- `.c-alert` - Alert container
- `.c-alert--success` - Green success
- `.c-alert--error` - Red error
- `.c-alert--info` - Blue info
- `.is-visible` - Show the alert

### Modals
- `.c-modal` - Modal backdrop
- `.c-modal.show` - Show modal
- `.c-modal__content` - Modal box
- `.c-modal__header` - Modal header
- `.c-modal__body` - Modal body
- `.c-modal__footer` - Modal footer with buttons
- `.c-modal__close` - Close button (×)

### Layout
- `.c-info-row` - Key-value row
- `.c-info-row__label` - Row label
- `.c-info-row__value` - Row value
- `.table-responsive` - Scrollable table wrapper

---

## Deployment Checklist

### Pre-deployment

- [ ] Test all 8 modules in development
- [ ] Verify API endpoints are working
- [ ] Check for JavaScript console errors
- [ ] Test on mobile viewport
- [ ] Verify admin-only access restriction

### Deployment

1. Deploy updated `src/views/admin/modules/` files
2. Deploy updated `src/public/admin-settings-new.php`
3. Update navigation links if needed

### Post-deployment

- [ ] Verify modules load correctly
- [ ] Test one action from each module
- [ ] Check logs for errors
- [ ] Confirm user feedback

---

## Troubleshooting

### Module Not Appearing

1. Check file is in `src/views/admin/modules/`
2. Verify PHP syntax: `php -l modules/xxx.php`
3. Ensure array has `id` and `title` keys
4. Check browser console for errors

### JavaScript Not Running

1. Module must have `'script' => function()` key
2. Script runs inside DOMContentLoaded
3. Check browser console for errors
4. Ensure element IDs match between HTML and JS

### Card Click Not Working

1. Card must have `data-module="moduleid"` attribute
2. Module ID must match tab content ID: `{id}-tab`
3. Check for JavaScript errors blocking event

---

## Migration from Old System

### What Changed

| Old | New |
|-----|-----|
| Single file with tabs | Multiple module files |
| Hardcoded tab structure | Auto-discovery |
| Mixed JS/PHP | Module-scoped JS objects |
| Limited extensibility | Drop-in modules |

### What Stayed the Same

- User/Signature management functionality
- API endpoint structure
- CSS design system
- Authentication requirements

### Recommended Steps

1. Test `admin-settings-new.php` thoroughly
2. Update header navigation links
3. Keep `admin-settings.php` as fallback initially
4. Remove old file after confirmation period

---

## Support

For issues or questions:
1. Check the browser console for errors
2. Check PHP error logs
3. Verify API responses in Network tab
4. Review module structure against examples above

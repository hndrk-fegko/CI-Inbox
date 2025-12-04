# Admin Settings - Modular System

## Overview

The admin settings system uses an **auto-discovery pattern** where each feature is a self-contained module. Modules are automatically loaded and rendered without modifying the main `admin-settings.php` file.

## Architecture Benefits

✅ **Zero-Touch Main File**: Add new features without editing `admin-settings.php`  
✅ **Self-Contained Modules**: Each module includes UI, logic, and API calls  
✅ **Automatic Ordering**: Filename-based priority (010-, 020-, etc.)  
✅ **Consistent Structure**: All modules follow the same contract  
✅ **Easy Extension**: Drop a new file in `modules/` and it appears automatically  

## Module Structure

### File Location
```
src/views/admin/modules/
├── 010-imap.php          # Priority 10 (first)
├── 020-smtp.php          # Priority 20
├── 030-cron.php          # Priority 30
├── 040-backup.php        # Priority 40
├── 050-database.php      # Priority 50
├── 060-users.php         # Priority 60
└── 070-signatures.php    # Priority 70 (last)
```

### Module Contract

Each module file returns an array with these keys:

```php
<?php
return [
    // Required fields
    'id' => 'unique-identifier',        // Used for tab switching
    'title' => 'Module Title',          // Displayed in tab button
    'priority' => 10,                   // Lower = earlier in list
    
    // Optional icon (SVG path for 24x24 viewBox)
    'icon' => '<path d="..."/>',
    
    // Callable functions
    'card' => function() {
        // Dashboard card HTML (clickable, links to tab)
        ?>
        <div class="c-admin-card" onclick="switchToTab('module-id')">
            <!-- Card content -->
        </div>
        <?php
    },
    
    'content' => function() {
        // Full tab content HTML
        ?>
        <div class="c-tabs__content" id="module-id-tab">
            <!-- Tab content -->
        </div>
        <?php
    },
    
    'script' => function() {
        // JavaScript for this module (wrapped in <script> tags)
        ?>
        <script>
        // Module-specific JS
        async function loadModuleData() {
            // API calls, event handlers, etc.
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', loadModuleData);
        } else {
            loadModuleData();
        }
        </script>
        <?php
    }
];
```

## Auto-Discovery Process

### 1. Scan Directory
```php
$modulesPath = __DIR__ . '/../views/admin/modules/';
$files = glob($modulesPath . '*.php');
sort($files); // Filename-based ordering
```

### 2. Load Modules
```php
foreach ($files as $file) {
    $module = include $file; // Executes return statement
    if (is_array($module) && isset($module['id'], $module['title'])) {
        $modules[] = $module;
    }
}
```

### 3. Sort by Priority
```php
usort($modules, function($a, $b) {
    return ($a['priority'] ?? 999) - ($b['priority'] ?? 999);
});
```

### 4. Render
```php
// Tabs
foreach ($modules as $module) {
    echo '<button class="c-tabs__tab" data-tab="' . $module['id'] . '">';
    echo $module['title'];
    echo '</button>';
}

// Cards (Overview)
foreach ($modules as $module) {
    if (isset($module['card'])) {
        call_user_func($module['card']);
    }
}

// Tab Contents
foreach ($modules as $module) {
    if (isset($module['content'])) {
        call_user_func($module['content']);
    }
}

// Scripts
foreach ($modules as $module) {
    if (isset($module['script'])) {
        call_user_func($module['script']);
    }
}
```

## Adding a New Module

### Step 1: Create Module File

Create `src/views/admin/modules/080-new-feature.php`:

```php
<?php
return [
    'id' => 'new-feature',
    'title' => 'New Feature',
    'priority' => 80,
    'icon' => '<path d="..."/>',
    
    'card' => function() {
        ?>
        <div class="c-admin-card" onclick="switchToTab('new-feature')" style="cursor: pointer;">
            <div class="c-admin-card__header">
                <div class="c-admin-card__icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="..."/>
                    </svg>
                </div>
                <div>
                    <h3 class="c-admin-card__title">New Feature</h3>
                </div>
            </div>
            <p class="c-admin-card__description">Description of the feature.</p>
            <div class="c-admin-card__content">
                <div class="c-info-row">
                    <span class="c-info-row__label">Status</span>
                    <span class="c-info-row__value" id="new-feature-status">Active</span>
                </div>
            </div>
        </div>
        <?php
    },
    
    'content' => function() {
        ?>
        <div class="c-tabs__content" id="new-feature-tab">
            <h3>New Feature Configuration</h3>
            <p>Content goes here...</p>
        </div>
        <?php
    },
    
    'script' => function() {
        ?>
        <script>
        async function loadNewFeatureData() {
            const response = await fetch('/api/admin/new-feature');
            // Handle response...
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', loadNewFeatureData);
        } else {
            loadNewFeatureData();
        }
        </script>
        <?php
    }
];
```

### Step 2: That's It!

The module automatically appears in:
- ✅ Tab navigation bar
- ✅ Overview dashboard (as a card)
- ✅ Tab content area
- ✅ Scripts are loaded

**No changes needed to `admin-settings.php`!**

## Module Examples

### Simple Card (No Tab Content Yet)

```php
<?php
return [
    'id' => 'simple',
    'title' => 'Simple Module',
    'priority' => 90,
    
    'card' => function() {
        ?>
        <div class="c-admin-card">
            <h3>Simple Module</h3>
            <p>Coming soon!</p>
        </div>
        <?php
    }
];
```

### Card with API Integration

```php
<?php
return [
    'id' => 'api-module',
    'title' => 'API Module',
    'priority' => 100,
    
    'card' => function() {
        ?>
        <div class="c-admin-card" onclick="switchToTab('api-module')" style="cursor: pointer;">
            <h3>API Module</h3>
            <div id="api-status">Loading...</div>
        </div>
        <?php
    },
    
    'script' => function() {
        ?>
        <script>
        async function loadApiModuleStatus() {
            try {
                const response = await fetch('/api/admin/api-module/status');
                const data = await response.json();
                document.getElementById('api-status').textContent = data.status;
            } catch (error) {
                console.error('[APIModule] Failed:', error);
            }
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', loadApiModuleStatus);
        } else {
            loadApiModuleStatus();
        }
        </script>
        <?php
    }
];
```

## CSS Classes Available

### Cards (Dashboard)
- `.c-admin-card` - Card container
- `.c-admin-card__header` - Card header with icon
- `.c-admin-card__icon` - Icon wrapper (24x24 SVG)
- `.c-admin-card__title` - Card title
- `.c-admin-card__description` - Card description text
- `.c-admin-card__content` - Card main content area

### Status Elements
- `.c-info-row` - Key-value info row
- `.c-info-row__label` - Label text
- `.c-info-row__value` - Value text
- `.c-status-badge` - Status indicator
- `.c-status-badge--success` - Green status
- `.c-status-badge--warning` - Yellow status
- `.c-status-badge--error` - Red status

### Tabs
- `.c-tabs__content` - Tab content wrapper
- `.c-tabs__content.is-active` - Active tab (visible)

### Alerts
- `.c-alert` - Alert container
- `.c-alert--info` - Info alert (blue)
- `.c-alert--warning` - Warning alert (yellow)
- `.c-alert--error` - Error alert (red)
- `.c-alert--success` - Success alert (green)

### Buttons
- `.c-button` - Base button
- `.c-button--primary` - Primary action (blue)
- `.c-button--secondary` - Secondary action (gray)
- `.c-button--danger` - Destructive action (red)

## API Integration Pattern

### Consistent API Calls

All modules follow this pattern for loading data:

```javascript
async function loadModuleData() {
    try {
        const response = await fetch('/api/admin/module-name/endpoint');
        if (response.ok) {
            const data = await response.json();
            if (data.success && data.data) {
                // Update UI with data
                document.getElementById('element-id').textContent = data.data.value;
            }
        }
    } catch (error) {
        console.error('[ModuleName] Failed to load:', error);
    }
}
```

### Auto-Refresh Pattern

For real-time monitoring:

```javascript
async function loadLiveData() {
    // Load data
    await loadModuleData();
}

// Initial load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadLiveData);
} else {
    loadLiveData();
}

// Auto-refresh every 30 seconds
setInterval(loadLiveData, 30000);
```

## Tab Switching

### Programmatic Tab Switch

From anywhere in the code:

```javascript
switchToTab('module-id');
```

### Card-to-Tab Navigation

Cards are clickable and switch to their tab:

```html
<div class="c-admin-card" onclick="switchToTab('module-id')" style="cursor: pointer;">
    <!-- Card content -->
</div>
```

### Tab-Specific Load Function

Optional: Define a tab-specific load function that triggers when tab becomes active:

```javascript
function loadModuleIdTab() {
    console.log('Module tab activated!');
    // Load data specific to this tab view
}

// Will be called automatically by switchToTab('module-id')
```

## Priority Numbering Convention

Use these ranges for different module types:

- **010-099**: System Configuration (IMAP, SMTP, etc.)
- **100-199**: Monitoring & Maintenance (Cron, Database, Backups)
- **200-299**: User & Content Management (Users, Signatures, etc.)
- **300-399**: Integration & Extensions (Webhooks, OAuth, etc.)
- **400-499**: Advanced Features (Reports, Analytics, etc.)

Example:
- `010-imap.php` - IMAP Configuration
- `020-smtp.php` - SMTP Configuration
- `110-cron.php` - Cron Monitoring
- `120-backup.php` - Backup Management
- `210-users.php` - User Management

## Error Handling

### Module Load Failures

If a module file has errors, it's skipped and logged:

```php
try {
    $module = include $file;
    if (is_array($module) && isset($module['id'], $module['title'])) {
        $modules[] = $module;
    }
} catch (Exception $e) {
    error_log("Failed to load admin module: $file - " . $e->getMessage());
}
```

### Graceful Degradation

- Invalid modules are ignored
- Missing `card`, `content`, or `script` callbacks are skipped
- System continues to work with remaining modules

## Testing Modules

### PHP Syntax Check

```bash
php -l src/views/admin/modules/010-imap.php
```

### Check All Modules

```powershell
Get-ChildItem src\views\admin\modules\*.php | ForEach-Object { php -l $_.FullName }
```

### Test Auto-Discovery

```php
// In admin-settings.php, add before rendering:
echo "<pre>";
print_r(array_map(function($m) {
    return ['id' => $m['id'], 'title' => $m['title'], 'priority' => $m['priority']];
}, $modules));
echo "</pre>";
exit;
```

## Future Extensions

### Planned Features

- **Module Permissions**: Per-module access control
- **Module Enable/Disable**: Toggle modules on/off
- **Module Configuration**: Store module settings in database
- **Module Dependencies**: Declare dependencies between modules
- **Module Hooks**: Register hooks for cross-module communication

### Example: Module with Permissions

```php
<?php
return [
    'id' => 'advanced-feature',
    'title' => 'Advanced Feature',
    'priority' => 200,
    'permissions' => ['admin', 'super-admin'], // Who can see this
    
    'card' => function() {
        // Only rendered if user has permission
    }
];
```

## Migration from Old System

### Old Structure (Monolithic)
```
admin-settings.php (1433 lines)
├── All tabs hard-coded
├── All cards hard-coded
├── All scripts inline
└── Hard to maintain
```

### New Structure (Modular)
```
admin-settings.php (200 lines) - Auto-discovery engine
└── modules/
    ├── 010-imap.php (150 lines)
    ├── 020-smtp.php (150 lines)
    ├── 030-cron.php (140 lines)
    ├── 040-backup.php (130 lines)
    ├── 050-database.php (120 lines)
    ├── 060-users.php (110 lines)
    └── 070-signatures.php (110 lines)
```

**Benefits:**
- ✅ 1433 lines → 200 + (7 × ~130) = ~1100 lines (cleaner)
- ✅ Each module is self-contained and testable
- ✅ Adding features doesn't touch main file
- ✅ Easy to disable modules (just delete/rename file)
- ✅ Clear separation of concerns

## Best Practices

1. **One Feature Per Module**: Don't combine unrelated features
2. **Consistent Naming**: Use descriptive IDs and titles
3. **Priority Gaps**: Leave room (010, 020, 030) for inserting modules later
4. **Error Handling**: Always use try-catch in API calls
5. **Loading States**: Show "Loading..." before data arrives
6. **Responsive Design**: Cards should work on mobile
7. **Accessibility**: Use semantic HTML and ARIA labels
8. **Security**: Sanitize all user inputs, use prepared statements

## Troubleshooting

### Module Not Appearing

1. Check filename is `*.php` in `src/views/admin/modules/`
2. Verify module returns an array with `id` and `title`
3. Check PHP syntax: `php -l module-file.php`
4. Check error logs: `logs/app-*.log`

### Card/Tab Not Rendering

1. Verify `card` and `content` are callable functions
2. Check for PHP errors inside callbacks
3. Ensure HTML is properly closed
4. Check browser console for JavaScript errors

### API Calls Failing

1. Verify endpoint exists in `src/routes/api.php`
2. Check network tab in browser DevTools
3. Verify API returns `{success: true, data: ...}` format
4. Check CORS if calling from different domain

---

**Last Updated:** 2025-11-20  
**Status:** ✅ Production Ready

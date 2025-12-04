# Theme Module

Provides dark mode support for CI-Inbox with per-user preferences.

## Features

- **Three modes**: Always Light, Always Dark, Auto (system preference)
- **User preferences**: Stored in database per user
- **CSS Variables**: Overrides design tokens for dark mode
- **System detection**: Uses `prefers-color-scheme` media query
- **Smooth transitions**: CSS transitions between themes

## Installation

Module is auto-discovered via `module.json`. No manual registration needed.

### Database Migration

Run the migration to add `theme_mode` column to users table:

```bash
php database/migrate.php
```

This adds the migration: `016_add_theme_mode_to_users.php`

## Usage

### In Settings (Frontend)

Users can change their theme preference in Settings > Profile:

- **Auto**: Follow system preference (light/dark based on OS)
- **Light**: Always use light theme
- **Dark**: Always use dark theme

### API Endpoint

```http
POST /api/user/theme
Content-Type: application/json

{
  "theme_mode": "auto|light|dark"
}
```

Response:
```json
{
  "success": true,
  "data": {
    "theme_mode": "dark"
  }
}
```

### Service Usage (PHP)

```php
use CiInbox\Modules\Theme\ThemeService;

$themeService = $container->get(ThemeService::class);

// Get user's theme preference
$themeMode = $themeService->getUserTheme($userId);  // 'auto', 'light', 'dark'

// Update user's theme
$themeService->setUserTheme($userId, 'dark');
```

### JavaScript

Theme switcher is automatically loaded on all pages. Theme is applied immediately based on user preference.

```javascript
// Theme is automatically applied from user settings
// Manual override (stored in localStorage):
document.documentElement.setAttribute('data-theme', 'dark');
```

## Technical Details

### CSS Variables Override

Dark mode overrides variables in `_variables.css`:

```css
[data-theme="dark"] {
  --color-surface: #1f2937;
  --color-background: #111827;
  --color-neutral-900: #f9fafb;  /* Inverted for text */
  /* ... */
}
```

### Database Schema

```sql
ALTER TABLE users ADD COLUMN theme_mode ENUM('auto', 'light', 'dark') DEFAULT 'auto';
```

### Files

- `module.json` - Module manifest
- `src/ThemeService.php` - Core business logic
- `src/ThemeServiceInterface.php` - Service contract
- `assets/theme-dark.css` - Dark mode CSS variables
- `assets/theme-switcher.js` - Client-side theme management
- `config/theme.php` - Module configuration

## Hook Integration

Registers at `onAppInit` (priority 5) to:
- Register API routes for theme management
- Inject theme assets into pages

## Dependencies

- `logger` - Logging theme changes
- `config` - Module configuration

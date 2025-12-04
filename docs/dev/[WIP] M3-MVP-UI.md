# [WIP] M3: MVP UI - Modern Email Client Interface

**Milestone:** M3 - MVP User Interface  
**Geschätzte Dauer:** 3-5 Tage (wahrscheinlich ~12-15 Stunden)  
**Status:** ✅ COMPLETED (Phase 1-4) / 🔄 Polish (Phase 5)  
**Start:** 18. November 2025
**Last Update:** 28. November 2025

---

## Ziel

Minimales funktionsfähiges Frontend für grundlegende Email-Management-Operationen. Modernes, responsives Design mit klarer CSS-Architektur für einfache Wartung und Anpassung.

**Features:** F3.1 - F3.6 (User Auth, Inbox, Thread View, Composer, Actions, Labels)

---

## UI/UX Design Prinzipien

### Design-System

**Inspiriert von:** Gmail, Front, Help Scout (moderne Email-Clients)

**Core-Prinzipien:**
1. **Mobile-First:** Responsive Design, funktioniert auf allen Devices
2. **Performance:** Minimal JavaScript, progressive enhancement
3. **Accessibility:** ARIA labels, keyboard navigation, screen reader support
4. **Consistency:** Einheitliche Komponenten, wiederverwendbare Patterns
5. **Clarity:** Clear visual hierarchy, intuitive actions

### Layout-Struktur

```
┌─────────────────────────────────────────────────────┐
│ .app-header                                         │
│   .app-header__logo   .app-header__nav   .app-user │
├─────────────┬───────────────────────────────────────┤
│             │                                       │
│ .sidebar    │  .main-content                        │
│             │                                       │
│ .nav-menu   │  .inbox-view / .thread-view          │
│             │  / .composer-view                     │
│             │                                       │
│             │                                       │
│             │                                       │
└─────────────┴───────────────────────────────────────┘
```

---

## CSS-Architektur: BEM + ITCSS Hybrid

### Namenskonventionen

**BEM (Block Element Modifier):**
```css
/* Block */
.thread-list { }

/* Element (child of block) */
.thread-list__item { }
.thread-list__subject { }
.thread-list__sender { }

/* Modifier (variation of block/element) */
.thread-list__item--unread { }
.thread-list__item--selected { }
.thread-list__subject--truncated { }
```

**Prefix-System für Klarheit:**
```css
/* Layout */
.l-container { }
.l-sidebar { }
.l-main { }

/* Components */
.c-button { }
.c-input { }
.c-badge { }

/* Utilities */
.u-text-center { }
.u-margin-top { }
.u-hidden { }

/* State (avoid inline styles) */
.is-active { }
.is-loading { }
.is-disabled { }
.has-error { }
```

### CSS-Datei-Struktur

```
src/public/css/
├── 1-settings/
│   ├── _variables.css          # CSS Custom Properties (colors, spacing, etc.)
│   └── _breakpoints.css        # Media query breakpoints
├── 2-tools/
│   └── _mixins.css             # Reusable CSS patterns (optional)
├── 3-generic/
│   ├── _reset.css              # Normalize/reset
│   └── _box-sizing.css         # Universal box-sizing
├── 4-elements/
│   ├── _typography.css         # Base text styles (h1-h6, p, etc.)
│   ├── _forms.css              # Base form element styles
│   └── _links.css              # Base link styles
├── 5-objects/
│   ├── _layout.css             # Grid, flexbox utilities
│   └── _containers.css         # Width containers
├── 6-components/
│   ├── _header.css             # .app-header
│   ├── _sidebar.css            # .sidebar, .nav-menu
│   ├── _thread-list.css        # .thread-list
│   ├── _thread-item.css        # .thread-item
│   ├── _thread-view.css        # .thread-view
│   ├── _email-message.css      # .email-message
│   ├── _composer.css           # .composer
│   ├── _button.css             # .c-button
│   ├── _input.css              # .c-input
│   ├── _badge.css              # .c-badge
│   ├── _label-tag.css          # .label-tag
│   └── _dropdown.css           # .c-dropdown
├── 7-utilities/
│   ├── _spacing.css            # Margin/padding utilities
│   ├── _text.css               # Text utilities
│   └── _display.css            # Display utilities
└── main.css                    # Imports all above
```

### CSS Custom Properties (Design Tokens)

```css
/* _variables.css */
:root {
  /* Colors - Primary */
  --color-primary-50: #eff6ff;
  --color-primary-100: #dbeafe;
  --color-primary-500: #3b82f6;
  --color-primary-600: #2563eb;
  --color-primary-700: #1d4ed8;
  
  /* Colors - Neutral */
  --color-neutral-50: #f9fafb;
  --color-neutral-100: #f3f4f6;
  --color-neutral-200: #e5e7eb;
  --color-neutral-300: #d1d5db;
  --color-neutral-500: #6b7280;
  --color-neutral-700: #374151;
  --color-neutral-900: #111827;
  
  /* Colors - Semantic */
  --color-success: #10b981;
  --color-warning: #f59e0b;
  --color-danger: #ef4444;
  --color-info: #3b82f6;
  
  /* Typography */
  --font-family-base: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  --font-family-mono: "SF Mono", Monaco, "Cascadia Code", "Roboto Mono", Consolas, monospace;
  
  --font-size-xs: 0.75rem;    /* 12px */
  --font-size-sm: 0.875rem;   /* 14px */
  --font-size-base: 1rem;     /* 16px */
  --font-size-lg: 1.125rem;   /* 18px */
  --font-size-xl: 1.25rem;    /* 20px */
  --font-size-2xl: 1.5rem;    /* 24px */
  
  --font-weight-normal: 400;
  --font-weight-medium: 500;
  --font-weight-semibold: 600;
  --font-weight-bold: 700;
  
  --line-height-tight: 1.25;
  --line-height-normal: 1.5;
  --line-height-relaxed: 1.75;
  
  /* Spacing (8px base) */
  --spacing-1: 0.25rem;  /* 4px */
  --spacing-2: 0.5rem;   /* 8px */
  --spacing-3: 0.75rem;  /* 12px */
  --spacing-4: 1rem;     /* 16px */
  --spacing-5: 1.25rem;  /* 20px */
  --spacing-6: 1.5rem;   /* 24px */
  --spacing-8: 2rem;     /* 32px */
  --spacing-10: 2.5rem;  /* 40px */
  --spacing-12: 3rem;    /* 48px */
  
  /* Borders */
  --border-width: 1px;
  --border-color: var(--color-neutral-200);
  --border-radius-sm: 0.25rem;  /* 4px */
  --border-radius-md: 0.375rem; /* 6px */
  --border-radius-lg: 0.5rem;   /* 8px */
  --border-radius-full: 9999px;
  
  /* Shadows */
  --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  
  /* Layout */
  --header-height: 64px;
  --sidebar-width: 240px;
  --sidebar-collapsed-width: 64px;
  
  /* Z-index */
  --z-index-dropdown: 1000;
  --z-index-modal: 2000;
  --z-index-toast: 3000;
  
  /* Transitions */
  --transition-fast: 150ms ease-in-out;
  --transition-normal: 250ms ease-in-out;
  --transition-slow: 350ms ease-in-out;
}
```

---

## Komponenten-Bibliothek

### 1. Thread List Item

```html
<div class="thread-item thread-item--unread">
  <div class="thread-item__checkbox">
    <input type="checkbox" class="c-checkbox" id="thread-123">
  </div>
  
  <div class="thread-item__content">
    <div class="thread-item__header">
      <span class="thread-item__sender">John Doe</span>
      <span class="thread-item__time">2m ago</span>
    </div>
    
    <div class="thread-item__subject">
      <span class="c-badge c-badge--primary">2</span>
      Re: Project Update Q4
    </div>
    
    <div class="thread-item__preview">
      Thanks for the update. I reviewed the document and have a few questions...
    </div>
    
    <div class="thread-item__meta">
      <span class="label-tag label-tag--blue">Support</span>
      <span class="label-tag label-tag--green">Important</span>
      <span class="thread-item__attachment-icon" title="Has attachments">
        <svg>...</svg>
      </span>
    </div>
  </div>
</div>
```

**CSS:**
```css
/* _thread-item.css */
.thread-item {
  display: flex;
  gap: var(--spacing-3);
  padding: var(--spacing-4);
  border-bottom: var(--border-width) solid var(--border-color);
  cursor: pointer;
  transition: background-color var(--transition-fast);
}

.thread-item:hover {
  background-color: var(--color-neutral-50);
}

.thread-item--unread {
  background-color: var(--color-primary-50);
  font-weight: var(--font-weight-medium);
}

.thread-item--selected {
  background-color: var(--color-primary-100);
  border-left: 3px solid var(--color-primary-600);
}

.thread-item__checkbox {
  flex-shrink: 0;
  display: flex;
  align-items: center;
}

.thread-item__content {
  flex: 1;
  min-width: 0; /* Allow truncation */
}

.thread-item__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-1);
}

.thread-item__sender {
  font-weight: var(--font-weight-semibold);
  color: var(--color-neutral-900);
}

.thread-item__time {
  font-size: var(--font-size-sm);
  color: var(--color-neutral-500);
}

.thread-item__subject {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  margin-bottom: var(--spacing-1);
  font-size: var(--font-size-base);
  color: var(--color-neutral-900);
}

.thread-item__preview {
  font-size: var(--font-size-sm);
  color: var(--color-neutral-500);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.thread-item__meta {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  margin-top: var(--spacing-2);
}
```

### 2. Button Component

```html
<!-- Primary Button -->
<button class="c-button c-button--primary">
  Send Email
</button>

<!-- Secondary Button -->
<button class="c-button c-button--secondary">
  Cancel
</button>

<!-- Icon Button -->
<button class="c-button c-button--icon" aria-label="Delete">
  <svg>...</svg>
</button>

<!-- Button with Icon -->
<button class="c-button c-button--primary">
  <svg class="c-button__icon">...</svg>
  <span>Reply</span>
</button>

<!-- Loading State -->
<button class="c-button c-button--primary is-loading" disabled>
  <span class="c-button__spinner"></span>
  Sending...
</button>
```

**CSS:**
```css
/* _button.css */
.c-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--spacing-2);
  padding: var(--spacing-2) var(--spacing-4);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  line-height: var(--line-height-tight);
  border: var(--border-width) solid transparent;
  border-radius: var(--border-radius-md);
  cursor: pointer;
  transition: all var(--transition-fast);
  white-space: nowrap;
}

.c-button:hover {
  opacity: 0.9;
}

.c-button:active {
  transform: translateY(1px);
}

.c-button:disabled,
.c-button.is-disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

/* Primary */
.c-button--primary {
  background-color: var(--color-primary-600);
  color: white;
}

.c-button--primary:hover {
  background-color: var(--color-primary-700);
}

/* Secondary */
.c-button--secondary {
  background-color: white;
  color: var(--color-neutral-700);
  border-color: var(--border-color);
}

.c-button--secondary:hover {
  background-color: var(--color-neutral-50);
}

/* Danger */
.c-button--danger {
  background-color: var(--color-danger);
  color: white;
}

/* Icon Only */
.c-button--icon {
  padding: var(--spacing-2);
  aspect-ratio: 1;
}

.c-button__icon {
  width: 1em;
  height: 1em;
}

/* Loading State */
.c-button.is-loading {
  position: relative;
  color: transparent;
}

.c-button__spinner {
  position: absolute;
  width: 1em;
  height: 1em;
  border: 2px solid currentColor;
  border-right-color: transparent;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
```

### 3. Badge Component

```html
<span class="c-badge c-badge--primary">2</span>
<span class="c-badge c-badge--success">New</span>
<span class="c-badge c-badge--warning">Pending</span>
```

**CSS:**
```css
/* _badge.css */
.c-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.25rem;
  padding: 0 var(--spacing-1);
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-semibold);
  line-height: var(--line-height-tight);
  border-radius: var(--border-radius-full);
}

.c-badge--primary {
  background-color: var(--color-primary-100);
  color: var(--color-primary-700);
}

.c-badge--success {
  background-color: #dcfce7;
  color: #166534;
}

.c-badge--warning {
  background-color: #fef3c7;
  color: #92400e;
}
```

### 4. Label Tag Component

```html
<span class="label-tag label-tag--blue">Support</span>
<span class="label-tag label-tag--green">Important</span>
<span class="label-tag label-tag--red">Urgent</span>
```

**CSS:**
```css
/* _label-tag.css */
.label-tag {
  display: inline-flex;
  align-items: center;
  gap: var(--spacing-1);
  padding: var(--spacing-1) var(--spacing-2);
  font-size: var(--font-size-xs);
  font-weight: var(--font-weight-medium);
  border-radius: var(--border-radius-sm);
}

.label-tag--blue {
  background-color: #dbeafe;
  color: #1e40af;
}

.label-tag--green {
  background-color: #dcfce7;
  color: #166534;
}

.label-tag--red {
  background-color: #fee2e2;
  color: #991b1b;
}

.label-tag--yellow {
  background-color: #fef3c7;
  color: #92400e;
}

.label-tag--purple {
  background-color: #f3e8ff;
  color: #6b21a8;
}
```

### 5. Input Component

```html
<div class="c-input-group">
  <label class="c-input-group__label" for="subject">Subject</label>
  <input 
    type="text" 
    id="subject" 
    class="c-input" 
    placeholder="Enter subject..."
  >
  <span class="c-input-group__hint">Required field</span>
</div>

<!-- With Error -->
<div class="c-input-group has-error">
  <label class="c-input-group__label" for="email">Email</label>
  <input 
    type="email" 
    id="email" 
    class="c-input" 
    aria-invalid="true"
  >
  <span class="c-input-group__error">Invalid email address</span>
</div>
```

**CSS:**
```css
/* _input.css */
.c-input-group {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-1);
}

.c-input-group__label {
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  color: var(--color-neutral-700);
}

.c-input {
  padding: var(--spacing-2) var(--spacing-3);
  font-size: var(--font-size-base);
  line-height: var(--line-height-normal);
  color: var(--color-neutral-900);
  background-color: white;
  border: var(--border-width) solid var(--border-color);
  border-radius: var(--border-radius-md);
  transition: border-color var(--transition-fast);
}

.c-input:focus {
  outline: none;
  border-color: var(--color-primary-500);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.c-input-group.has-error .c-input {
  border-color: var(--color-danger);
}

.c-input-group__hint {
  font-size: var(--font-size-sm);
  color: var(--color-neutral-500);
}

.c-input-group__error {
  font-size: var(--font-size-sm);
  color: var(--color-danger);
}
```

---

## Views-Struktur

### 1. Login View

```html
<!-- src/views/auth/login.php -->
<div class="auth-view">
  <div class="auth-view__container">
    <div class="auth-view__header">
      <img src="/assets/logo.svg" alt="CI-Inbox" class="auth-view__logo">
      <h1 class="auth-view__title">Sign in to CI-Inbox</h1>
    </div>
    
    <form class="auth-view__form" action="/login" method="POST">
      <div class="c-input-group">
        <label class="c-input-group__label" for="email">Email</label>
        <input type="email" id="email" name="email" class="c-input" required>
      </div>
      
      <div class="c-input-group">
        <label class="c-input-group__label" for="password">Password</label>
        <input type="password" id="password" name="password" class="c-input" required>
      </div>
      
      <button type="submit" class="c-button c-button--primary c-button--full-width">
        Sign In
      </button>
    </form>
  </div>
</div>
```

### 2. Inbox View (Main Layout)

```html
<!-- src/views/inbox/index.php -->
<div class="app-layout">
  <!-- Header -->
  <header class="app-header">
    <div class="app-header__left">
      <button class="c-button c-button--icon" id="toggle-sidebar">
        <svg><!-- Menu icon --></svg>
      </button>
      <h1 class="app-header__logo">CI-Inbox</h1>
    </div>
    
    <div class="app-header__center">
      <div class="search-bar">
        <input type="search" class="search-bar__input" placeholder="Search emails...">
      </div>
    </div>
    
    <div class="app-header__right">
      <button class="c-button c-button--primary" id="compose-btn">
        <svg class="c-button__icon"><!-- Plus icon --></svg>
        Compose
      </button>
      
      <div class="app-user">
        <img src="/assets/avatar.png" alt="User" class="app-user__avatar">
        <span class="app-user__name">John Doe</span>
      </div>
    </div>
  </header>
  
  <!-- Sidebar -->
  <aside class="sidebar">
    <nav class="nav-menu">
      <a href="/inbox" class="nav-menu__item is-active">
        <svg class="nav-menu__icon"><!-- Inbox icon --></svg>
        <span class="nav-menu__label">Inbox</span>
        <span class="c-badge c-badge--primary">5</span>
      </a>
      
      <a href="/sent" class="nav-menu__item">
        <svg class="nav-menu__icon"><!-- Send icon --></svg>
        <span class="nav-menu__label">Sent</span>
      </a>
      
      <a href="/drafts" class="nav-menu__item">
        <svg class="nav-menu__icon"><!-- Draft icon --></svg>
        <span class="nav-menu__label">Drafts</span>
      </a>
      
      <div class="nav-menu__divider"></div>
      
      <div class="nav-menu__section">
        <h3 class="nav-menu__section-title">Labels</h3>
        <a href="/label/support" class="nav-menu__item">
          <span class="nav-menu__dot" style="background-color: #3b82f6;"></span>
          <span class="nav-menu__label">Support</span>
          <span class="c-badge">2</span>
        </a>
      </div>
    </nav>
  </aside>
  
  <!-- Main Content -->
  <main class="main-content">
    <div class="inbox-view">
      <!-- Toolbar -->
      <div class="inbox-toolbar">
        <div class="inbox-toolbar__left">
          <input type="checkbox" class="c-checkbox" id="select-all">
          <button class="c-button c-button--icon" title="Archive">
            <svg><!-- Archive icon --></svg>
          </button>
          <button class="c-button c-button--icon" title="Delete">
            <svg><!-- Delete icon --></svg>
          </button>
        </div>
        
        <div class="inbox-toolbar__right">
          <select class="c-select">
            <option>All</option>
            <option>Unread</option>
            <option>Read</option>
          </select>
        </div>
      </div>
      
      <!-- Thread List -->
      <div class="thread-list">
        <!-- Thread items rendered here -->
      </div>
    </div>
  </main>
</div>
```

### 3. Thread Detail View

```html
<!-- src/views/thread/show.php -->
<div class="thread-view">
  <!-- Thread Header -->
  <div class="thread-view__header">
    <button class="c-button c-button--icon" onclick="history.back()">
      <svg><!-- Back arrow --></svg>
    </button>
    
    <div class="thread-view__subject">
      <h1>Re: Project Update Q4</h1>
      <div class="thread-view__meta">
        <span class="label-tag label-tag--blue">Support</span>
        <span class="label-tag label-tag--green">Important</span>
      </div>
    </div>
    
    <div class="thread-view__actions">
      <button class="c-button c-button--secondary">
        <svg class="c-button__icon"><!-- Reply icon --></svg>
        Reply
      </button>
      <button class="c-button c-button--icon" title="More actions">
        <svg><!-- More icon --></svg>
      </button>
    </div>
  </div>
  
  <!-- Email Messages -->
  <div class="thread-view__messages">
    <article class="email-message">
      <div class="email-message__header">
        <img src="/assets/avatar.png" alt="Sender" class="email-message__avatar">
        
        <div class="email-message__info">
          <strong class="email-message__sender">John Doe</strong>
          <span class="email-message__email">john@example.com</span>
          <time class="email-message__time">2 hours ago</time>
        </div>
        
        <button class="c-button c-button--icon email-message__toggle">
          <svg><!-- Expand icon --></svg>
        </button>
      </div>
      
      <div class="email-message__body">
        <p>Thanks for the update. I reviewed the document...</p>
      </div>
      
      <div class="email-message__attachments">
        <div class="attachment-item">
          <svg class="attachment-item__icon"><!-- File icon --></svg>
          <span class="attachment-item__name">report-q4.pdf</span>
          <span class="attachment-item__size">2.3 MB</span>
          <button class="c-button c-button--icon attachment-item__download">
            <svg><!-- Download icon --></svg>
          </button>
        </div>
      </div>
    </article>
  </div>
  
  <!-- Internal Notes -->
  <div class="thread-view__notes">
    <h3 class="thread-view__notes-title">Internal Notes</h3>
    <div class="note-item">
      <div class="note-item__header">
        <strong>Jane Smith</strong>
        <time>Yesterday</time>
      </div>
      <p class="note-item__content">Customer seems satisfied with the response.</p>
    </div>
  </div>
</div>
```

### 4. Email Composer

```html
<!-- src/views/composer/index.php -->
<div class="composer">
  <div class="composer__header">
    <h2 class="composer__title">New Message</h2>
    <button class="c-button c-button--icon composer__close">
      <svg><!-- X icon --></svg>
    </button>
  </div>
  
  <form class="composer__form">
    <div class="composer__field">
      <label class="composer__field-label">To</label>
      <input type="email" class="composer__field-input" placeholder="recipient@example.com">
    </div>
    
    <div class="composer__field composer__field--collapsible">
      <label class="composer__field-label">Cc</label>
      <input type="email" class="composer__field-input">
    </div>
    
    <div class="composer__field">
      <label class="composer__field-label">Subject</label>
      <input type="text" class="composer__field-input" placeholder="Email subject">
    </div>
    
    <div class="composer__editor">
      <textarea class="composer__textarea" placeholder="Write your message..."></textarea>
    </div>
    
    <div class="composer__footer">
      <div class="composer__footer-left">
        <button type="submit" class="c-button c-button--primary">Send</button>
        <button type="button" class="c-button c-button--icon" title="Attach file">
          <svg><!-- Paperclip icon --></svg>
        </button>
      </div>
      
      <button type="button" class="c-button c-button--secondary">Discard</button>
    </div>
  </form>
</div>
```

---

## Responsive Design

### Breakpoints

```css
/* _breakpoints.css */
:root {
  --breakpoint-sm: 640px;   /* Mobile landscape */
  --breakpoint-md: 768px;   /* Tablet portrait */
  --breakpoint-lg: 1024px;  /* Tablet landscape / Small desktop */
  --breakpoint-xl: 1280px;  /* Desktop */
  --breakpoint-2xl: 1536px; /* Large desktop */
}
```

### Mobile Anpassungen

```css
/* Sidebar collapse on mobile */
@media (max-width: 768px) {
  .sidebar {
    position: fixed;
    left: 0;
    top: var(--header-height);
    transform: translateX(-100%);
    transition: transform var(--transition-normal);
    z-index: var(--z-index-dropdown);
  }
  
  .sidebar.is-open {
    transform: translateX(0);
  }
  
  /* Stack thread item content */
  .thread-item {
    flex-direction: column;
  }
  
  /* Hide preview on mobile */
  .thread-item__preview {
    display: none;
  }
}
```

---

## JavaScript-Interaktionen (Progressive Enhancement)

### Minimal JavaScript für Funktionalität

```javascript
// src/public/js/app.js

// Sidebar toggle
document.getElementById('toggle-sidebar')?.addEventListener('click', () => {
  document.querySelector('.sidebar')?.classList.toggle('is-open');
});

// Thread selection
document.querySelectorAll('.thread-item').forEach(item => {
  item.addEventListener('click', (e) => {
    if (!e.target.matches('input[type="checkbox"]')) {
      item.classList.add('thread-item--selected');
      // Load thread details via AJAX or navigate
    }
  });
});

// Compose modal
document.getElementById('compose-btn')?.addEventListener('click', () => {
  // Show composer (modal or new page)
});
```

---

## Implementierungs-Phasen

### Phase 1: Foundation (~2-3h) ✅
- [x] CSS-Architektur aufsetzen (ITCSS-Struktur)
- [x] Design Tokens definieren (_variables.css)
- [x] Base styles (reset, typography, forms)
- [x] Layout-System (header, sidebar, main)

### Phase 2: Core Components (~3-4h) ✅
- [x] Button component
- [x] Input/Form components
- [x] Badge component
- [x] Label tag component
- [x] Thread list item component

### Phase 3: Views (~4-5h) ✅
- [x] Login view
- [x] Inbox view (thread list)
- [x] Thread detail view
- [x] Email composer

### Phase 4: Interactions (~2-3h) ✅
- [x] Sidebar toggle
- [x] Thread selection
- [x] Composer modal/view
- [x] Form validation
- [x] Loading states (Basic implementation)

### Phase 5: Polish (~2h) 🔄
- [ ] Responsive refinements
- [ ] Accessibility improvements
- [ ] Animation/transition polish
- [ ] Cross-browser testing

---

## Accessibility Checklist

- [ ] Semantic HTML (header, nav, main, article)
- [ ] ARIA labels für icon-only buttons
- [ ] Keyboard navigation (Tab, Enter, Escape)
- [ ] Focus states sichtbar
- [ ] Color contrast mindestens WCAG AA
- [ ] Screen reader friendly (aria-live für Updates)
- [ ] Skip navigation link
- [ ] Alt text für alle Images

---

## Browser-Support

**Ziel:** Moderne Browser (letzten 2 Versionen)
- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Mobile Safari iOS 14+
- Chrome Android

**Keine Unterstützung:** IE11

---

## Design-Anpassungen: Dokumentation

### Wie du CSS-Änderungen vornehmen kannst:

**Beispiel 1: Thread-Item-Farbe ändern**
```
Datei: src/public/css/6-components/_thread-item.css
Selektor: .thread-item--unread
Eigenschaft: background-color
```

**Beispiel 2: Button-Größe anpassen**
```
Datei: src/public/css/6-components/_button.css
Selektor: .c-button
Eigenschaft: padding
```

**Beispiel 3: Primary Color ändern**
```
Datei: src/public/css/1-settings/_variables.css
Variable: --color-primary-600
Wert: #3b82f6 → neue Farbe
```

**Du gibst mir einfach:**
- Datei + Selektor/Variable
- Gewünschte Änderung
- Oder: HTML + CSS-Snippet direkt

→ Ich weiß sofort, was adressiert werden soll!

---

## Deliverables

- [x] CSS-Architektur (ITCSS + BEM, ~20 Files)
- [x] Design System (_variables.css mit Tokens)
- [x] Core Components (Button, Input, Badge, Label-Tag)
- [x] Thread List Component
- [x] Thread Detail View
- [x] Email Composer
- [x] Login View
- [x] Responsive Layout (Mobile-First)
- [x] JavaScript Interactions (Progressive Enhancement)
- [ ] Accessibility-compliant (Partial)

---

## Success Criteria

- ✅ Klare CSS-Namenskonventionen (BEM + Prefix)
- ✅ Design Tokens für einfache Anpassungen
- ✅ Komponenten-basierte Struktur
- ✅ Responsive auf Mobile/Tablet/Desktop
- ✅ Accessibility-compliant (WCAG AA)
- ✅ Performance (keine unnötigen JS-Frameworks)
- ✅ Cross-browser kompatibel
- ✅ Wartbar und dokumentiert

---

## Nächste Schritte

1. **Review:** Design-Entscheidungen diskutieren
2. **Start:** Phase 1 - Foundation (CSS-Architektur)
3. **Iterate:** Komponente für Komponente implementieren
4. **Test:** Responsive + Accessibility prüfen

# Admin Settings - Modulare Architektur (Vision & Roadmap)

**Status:** In Entwicklung (M3+)  
**Ziel-Datei:** `src/public/admin-settings-new.php` → wird zu `admin-settings.php` in M4  
**Current:** `admin-settings.php` (alte Version) wird durch `-new.php` abgelöst

---

## 🎯 Vision

Ein **modulares Admin-Interface** nach dem **Plugin-Pattern**, wo jedes Feature:
1. ✅ **Self-contained** ist (UI + Logic + API)
2. ✅ **Auto-discovered** wird (keine manuelle Registrierung)
3. ✅ **Filename-basierte Priorität** hat (010-, 020-, etc.)
4. ✅ **Zwei Präsentationen** liefert:
   - **Dashboard Card** (Overview mit Klick → Tab)
   - **Full Tab** (Detaillierte Konfiguration)

---

## 🏗️ Architektur-Prinzipien

### 1. **Zero-Touch Main File**
- Neue Features = neues File in `src/views/admin/modules/`
- **Kein Edit** in `admin-settings-new.php` nötig
- Auto-Discovery via `glob()` Pattern

### 2. **Module Contract**
Jedes Modul returned ein Array:
```php
return [
    'id' => 'unique-id',           // Tab-ID
    'title' => 'Module Title',      // Tab-Button-Text
    'priority' => 10,               // Sortierung (lower = earlier)
    'icon' => '<svg>...</svg>',     // Optional: SVG path
    
    'card' => function() {
        // Dashboard Card HTML (clickable)
    },
    
    'content' => function() {
        // Full Tab Content
    },
    
    'script' => function() {
        // JavaScript initialization
    }
];
```

### 3. **Filename-basierte Priorität**
```
010-imap.php       → Priority 10 (first)
020-smtp.php       → Priority 20
030-cron.php       → Priority 30
...
080-logger.php     → Priority 80 (new!)
```

---

## 📦 Bestehende Module (Stand: M3)

| File | Modul | Status | Beschreibung |
|------|-------|--------|--------------|
| `010-imap.php` | IMAP | 🟡 Placeholder | Global IMAP Config |
| `020-smtp.php` | SMTP | 🟡 Placeholder | SMTP Config |
| `030-cron.php` | Cron | ✅ Implementiert | Webcron Polling |
| `040-backup.php` | Backup | ✅ Implementiert | Backup Management |
| `050-database.php` | Database | 🟡 Placeholder | DB Tools |
| `060-users.php` | Users | ✅ Implementiert | User Management |
| `070-signatures.php` | Signatures | ✅ Implementiert | Email Signatures |
| **`080-logger.php`** | **Logger** | 🆕 **Template** | **Log Level + Viewer** |

---

## 🆕 Logger-Modul (NEU)

### Features (geplant für M4)

#### 1. **Log Level Configuration**
- Dropdown: DEBUG → INFO → WARNING → ERROR → CRITICAL
- PSR-3 Standard
- Persistierung in System Settings (DB)
- **Performance-Warnung** bei DEBUG-Level

#### 2. **Real-Time Log Viewer** (HomeAssistant-Style)
```
┌─────────────────────────────────────────────────┐
│  Live Log Stream              [Pause] [Clear]   │
├─────────────────────────────────────────────────┤
│  Filter: [All Levels] [All Modules] [Search]    │
├─────────────────────────────────────────────────┤
│  [16:45:23] INFO  [ThreadService] Thread...     │
│  [16:45:24] DEBUG [ImapClient] Connected...     │
│  [16:45:25] ERROR [EmailParser] Failed...       │
│  [16:45:26] INFO  [WebcronManager] Poll...      │
│  ...                                             │
│  500 entries | Auto-refresh every 5s            │
│  ☑ Auto-scroll to bottom                        │
└─────────────────────────────────────────────────┘
```

**Design:**
- Dunkles Theme (`background: #1e1e1e`) wie IDE-Console
- Farbcodierte Log Levels:
  - 🟢 INFO: `#4CAF50`
  - 🟡 WARNING: `#FF9800`
  - 🔴 ERROR: `#f44336`
  - 🟣 CRITICAL: `#9C27B0`
  - ⚪ DEBUG: `#888`

**Funktionen:**
- ⏸️ Pause/Resume
- 🗑️ Clear Display
- 🔍 Filter by Level + Module + Search
- ♻️ Auto-Refresh (5s Interval)
- 📜 Auto-Scroll (optional)

#### 3. **Log File Management**
- **Metrics:**
  - Total Size (MB)
  - File Count
  - Oldest Entry (Date)
- **Actions:**
  - 📥 Download Logs (ZIP)
  - 📦 Archive Old Logs
  - 🗑️ Clear All Logs

---

## 🔄 Migration Plan: `-old` → `-new` → Production

### Phase 1: M3 (Current)
- ✅ `admin-settings.php` = alte Tab-Version (funktioniert)
- ✅ `admin-settings-new.php` = neue modulare Version (Entwicklung)
- ✅ `admin-settings-old-tabs.php.bak` = Backup

### Phase 2: M4 (Logger + Final)
1. ✅ Logger-Modul finalisieren (`080-logger.php`)
2. ✅ API-Endpoints für Logger erstellen:
   - `GET /api/admin/logger/level` - Current log level
   - `PUT /api/admin/logger/level` - Set log level
   - `GET /api/admin/logger/stream` - Live log stream (SSE?)
   - `GET /api/admin/logger/stats` - Log file statistics
   - `POST /api/admin/logger/download` - Download logs
   - `POST /api/admin/logger/clear` - Clear logs
3. ✅ Alle Module in `-new` finalisieren
4. ✅ Testing: Feature-Parity zwischen `-old` und `-new`

### Phase 3: M5 (Deployment)
```bash
# Rename
mv admin-settings.php admin-settings-legacy.php.bak
mv admin-settings-new.php admin-settings.php

# Update Links
# Alle Verweise auf admin-settings.php zeigen jetzt auf neue Version
```

---

## 🎨 Design-System (konsistent über alle Module)

### Dashboard Cards (`.c-admin-card`)
```html
<div class="c-admin-card" data-module="logger">
    <div class="c-admin-card__header">
        <div class="c-admin-card__icon">
            <svg>...</svg>
        </div>
        <h3 class="c-admin-card__title">Module Title</h3>
    </div>
    <p class="c-admin-card__description">Short description</p>
    <div class="c-admin-card__content">
        <div class="c-info-row">
            <span class="c-info-row__label">Key</span>
            <span class="c-info-row__value">Value</span>
        </div>
    </div>
</div>
```

### Sidebar Navigation (`.c-sidebar`)
- Automatisch generiert aus Modulen
- Priority-basierte Sortierung
- Active State Styling

### Tab Content (`.c-tabs__content`)
- Konsistentes Header-Format
- White Cards mit Border-Radius 12px
- Box-Shadow: `0 2px 8px rgba(0,0,0,0.08)`

---

## 🚀 Erweiterung: Neues Modul hinzufügen

### Schritt 1: Datei erstellen
```bash
touch src/views/admin/modules/090-mein-modul.php
```

### Schritt 2: Module Contract implementieren
```php
<?php
return [
    'id' => 'mein-modul',
    'title' => 'Mein Modul',
    'priority' => 90,
    'icon' => '<path d="..."/>',
    
    'card' => function() {
        // Dashboard Card
    },
    
    'content' => function() {
        // Tab Content
    },
    
    'script' => function() {
        // JavaScript
    }
];
```

### Schritt 3: Reload
- ✅ **Fertig!** Modul erscheint automatisch im Admin Interface
- Keine Änderung an `admin-settings-new.php` nötig

---

## 🔮 Zukunfts-Module (Roadmap)

| Modul | Priority | M-Phase | Features |
|-------|----------|---------|----------|
| **Theme** | 090 | M4 | Light/Dark/Auto, Custom Colors |
| **Security** | 100 | M5 | 2FA, Session Management, IP Whitelist |
| **Integrations** | 110 | M5 | Webhook Config, External APIs |
| **Performance** | 120 | M5 | Cache Config, Optimization |
| **Updates** | 130 | M6 | Auto-Update Check, Changelog |

---

## 📝 API-Konventionen für Module

### Endpoint-Pattern
```
GET    /api/admin/{modul-id}/settings     # Get config
PUT    /api/admin/{modul-id}/settings     # Update config
GET    /api/admin/{modul-id}/status       # Health/Status
POST   /api/admin/{modul-id}/action       # Trigger action
```

### Response-Format
```json
{
  "success": true,
  "data": { ... },
  "message": "Optional user message"
}
```

---

## 🎯 Erfolgs-Kriterien

### Technisch
- ✅ Alle Module laden automatisch
- ✅ Dashboard zeigt alle Cards
- ✅ Sidebar Navigation funktioniert
- ✅ Tab-Switching ohne Page-Reload
- ✅ JavaScript per Modul isoliert

### User Experience
- ✅ Konsistentes Design über alle Module
- ✅ Klare Hierarchie (Dashboard → Detail)
- ✅ Responsive Layout
- ✅ Performance (< 500ms Load)

### Developer Experience
- ✅ Neues Modul in < 10min erstellt
- ✅ Keine Konflikte zwischen Modulen
- ✅ Klare Dokumentation
- ✅ Template verfügbar (`080-logger.php`)

---

## 🔗 Verwandte Dateien

- **Main File:** `src/public/admin-settings-new.php`
- **Module Dir:** `src/views/admin/modules/`
- **Styles:** `src/public/assets/css/main.css`
- **Theme:** `src/public/assets/js/theme-switcher.js`
- **Docs:** `src/views/admin/modules/README.md`

---

**Letzte Aktualisierung:** 5. Dezember 2025  
**Nächster Schritt:** Logger-API-Endpoints implementieren (M4)

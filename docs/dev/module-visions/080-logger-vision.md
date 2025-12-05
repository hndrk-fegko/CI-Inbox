# Logger Module Vision

## Zweck
Konfiguration von Log-Levels und Echtzeit-Anzeige von System-Logs.

## Zielgruppe
- **Admins:** Debugging, Troubleshooting, Performance-Monitoring

## Dashboard Card (Overview)
- **Metrics:**
  - Current Level (Badge: INFO, DEBUG, etc.)
  - Log Size (Display)
  - Latest Entry (Recent/No recent errors)
- **Quick Actions:**
  - Card-Click navigiert zum Detail-Tab

## Full Tab (Detailed Config)

### Section 1: Info Box
- Blaue Box mit Erklärung der Logging-Funktion
- Hinweis auf Performance-Impact bei DEBUG

### Section 2: Log Level Configuration
- Current Level Display
- Level Dropdown (DEBUG → CRITICAL)
- Warning box about DEBUG performance
- Save Configuration Button

### Section 3: Live Log Viewer (HomeAssistant-Style)
- Dark theme console (monospace font)
- Color-coded log levels:
  - DEBUG: Gray (#888)
  - INFO: Green (#4CAF50)
  - WARNING: Orange (#FF9800)
  - ERROR: Red (#f44336)
  - CRITICAL: Purple (#9C27B0)
- Filter Controls:
  - Filter by Level (Dropdown)
  - Search Input
- Actions:
  - Refresh Button
  - Clear Display Button
- Entry count display
- Auto-scroll toggle

### Section 4: Log File Management
- Statistics Cards:
  - Total Size
  - Log Files Count
  - Oldest Entry
- Actions:
  - Download Logs Button
  - Clear All Logs Button (with confirmation modal)

### Section 5: Clear Logs Modal
- Warning about irreversibility
- Confirm / Cancel Buttons

## API Endpoints

| Method | Endpoint | Purpose | Status |
|--------|----------|---------|--------|
| GET | `/api/system/errors` | Get recent errors/logs | ✅ Exists |
| PUT | `/api/admin/logger/level` | Set log level | 🆕 Simulated |
| POST | `/api/admin/logger/clear` | Clear log files | 🆕 Simulated |

## JavaScript Behavior

### Log Level Configuration
- Save button → API call (simulated)
- Update badge on success
- Success feedback

### Live Log Viewer
- Fetch logs from API
- Parse and colorize entries
- Apply filters (level, search)
- Auto-scroll to bottom (toggle)

### Filter Logic
- Level filter: Show only selected level or higher
- Search filter: Case-insensitive message search
- Real-time filtering as user types

### Log File Management
- Download: Create text file from current entries
- Clear: Modal confirmation → Clear display

## Error Handling

### API Errors
- **Load Failed:** Show error in viewer
- **Save Failed:** Show error alert
- **Clear Failed:** Show error alert

### User Feedback
- ✅ Success: Green alert, 5s auto-dismiss
- ❌ Error: Red alert, stays until dismissed

## Implementation Status
- ✅ Dashboard Card: Complete
- ✅ Log Level Config: Complete (UI, simulated API)
- ✅ Live Log Viewer: Complete
- ✅ Filter by Level: Complete
- ✅ Search Filter: Complete
- ✅ Download Logs: Complete
- ✅ Clear Logs Modal: Complete
- ⚠️ Real Log Level API: Simulated
- ⚠️ Real Clear Logs API: Simulated

## Styling
- Dark console theme (#1e1e1e background)
- Monospace font (Consolas, Monaco)
- Color-coded log levels
- 400px viewer height with scroll

## Future Enhancements
- [ ] Real log level persistence API
- [ ] Real log file clearing API
- [ ] SSE/WebSocket for live streaming
- [ ] Log rotation settings
- [ ] Per-module log levels
- [ ] Log archiving
- [ ] Statistics charts

## Success Metrics
- ✅ Log viewer loads entries
- ✅ Filters work correctly
- ✅ Download creates file
- ✅ Clear modal works
- ✅ Mobile responsive
- ✅ No console errors

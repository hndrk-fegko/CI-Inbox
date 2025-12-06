# IMAP Module Vision

## Zweck
Zentrale Verwaltung aller IMAP-Account-Konfigurationen für das System.

## Zielgruppe
- **Admins:** Konfigurieren globale IMAP-Defaults
- **Users:** Sehen ihre persönlichen IMAP-Accounts (read-only in Admin-View)

## Dashboard Card (Overview)
- **Status Badge:** 
  - 🟢 "Configured" (wenn mind. 1 Account aktiv)
  - 🟡 "Not Configured" (wenn keine Accounts)
- **Metrics:**
  - Anzahl aktiver Accounts
  - Letzte erfolgreiche Verbindung (Timestamp)
- **Quick Actions:**
  - Card-Click navigiert zum Detail-Tab

## Full Tab (Detailed Config)

### Section 1: Info Box
- Blaue Box mit Erklärung der IMAP-Funktion
- Hinweis auf Autodiscover-Feature

### Section 2: Server Configuration
- Host (Input) - IMAP server hostname
- Port (Input, default: 993)
- Encryption (Dropdown: SSL, TLS, None)
- Username (Input)
- Password (Input, masked)
- Inbox Folder (Input, default: INBOX)
- Validate SSL Certificate (Checkbox)
- **Actions:**
  - Auto-discover Button (Modal)
  - Test Connection Button
  - Save Configuration Button

### Section 3: Connection Test Results
- Collapsible panel
- Shows success/failure status
- Lists available folders on success
- Shows error details on failure

## API Endpoints

| Method | Endpoint | Purpose | Status |
|--------|----------|---------|--------|
| GET | `/api/admin/settings/imap` | Get IMAP configuration | ✅ Exists |
| PUT | `/api/admin/settings/imap` | Update IMAP configuration | ✅ Exists |
| POST | `/api/admin/settings/imap/test` | Test IMAP connection | ✅ Exists |
| POST | `/api/admin/settings/imap/autodiscover` | Auto-discover settings | ✅ Exists |

## JavaScript Behavior

### Auto-Load Configuration
- On tab activation, fetch current config from API
- Populate form fields with existing values
- Update card status badge

### Connection Test
- Click "Test Connection" → Show loading spinner
- API call → Display result (success with folders / error with details)
- Color-coded feedback (green success / red error)

### Autodiscover
- Modal with email input
- API call to detect IMAP settings
- Auto-populate form on success

### Form Validation
- Email: RFC 5322 validation
- Host: Required
- Port: 1-65535 range
- Password: Min length warning

## Error Handling

### Connection Errors
- **Timeout:** "Connection timed out. Check host and firewall."
- **Auth Failed:** "Authentication failed. Check username/password."
- **SSL Error:** "SSL certificate validation failed."
- **Host Not Found:** "Host not reachable. Check DNS and network."

### User Feedback
- ✅ Success: Green alert, 5s auto-dismiss
- ❌ Error: Red alert, stays until dismissed

## Implementation Status
- ✅ Dashboard Card: Complete
- ✅ Tab Content: Complete
- ✅ JavaScript: Complete
- ✅ API Integration: Complete
- ✅ Error Handling: Complete

## Success Metrics
- ✅ Configuration loads from API
- ✅ Form saves to API
- ✅ Test connection works
- ✅ Autodiscover works
- ✅ Mobile responsive
- ✅ No console errors

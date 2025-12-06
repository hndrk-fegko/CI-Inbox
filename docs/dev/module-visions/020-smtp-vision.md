# SMTP Module Vision

## Zweck
Zentrale Verwaltung der SMTP-Konfiguration für ausgehende E-Mails.

## Zielgruppe
- **Admins:** Konfigurieren globale SMTP-Settings
- **System:** Verwendet für Benachrichtigungen, Passwort-Reset, etc.

## Dashboard Card (Overview)
- **Status Badge:** 
  - 🟢 "Configured" (wenn Host und Credentials gesetzt)
  - 🟡 "Not Configured" (wenn nicht vollständig)
- **Metrics:**
  - Host konfiguriert: Ja/Nein
  - From-Email gesetzt: Ja/Nein
- **Quick Actions:**
  - Card-Click navigiert zum Detail-Tab

## Full Tab (Detailed Config)

### Section 1: Info Box
- Blaue Box mit Erklärung der SMTP-Funktion
- Hinweis auf Test-Email-Feature

### Section 2: Server Configuration
- SMTP Host (Input)
- Port (Input, default: 465)
- Encryption (Dropdown: SSL, STARTTLS, None)
- Requires Authentication (Checkbox)
- Username (Input)
- Password (Input, masked)
- **Actions:**
  - Auto-discover Button (Modal)

### Section 3: Sender Identity
- From Name (Input)
- From Email (Input)

### Section 4: Actions
- Test Connection / Send Test Email Button (Modal)
- Save Configuration Button

### Section 5: Test Results
- Collapsible panel
- Shows success/failure status
- Error details on failure

## API Endpoints

| Method | Endpoint | Purpose | Status |
|--------|----------|---------|--------|
| GET | `/api/admin/settings/smtp` | Get SMTP configuration | ✅ Exists |
| PUT | `/api/admin/settings/smtp` | Update SMTP configuration | ✅ Exists |
| POST | `/api/admin/settings/smtp/test` | Send test email | ✅ Exists |
| POST | `/api/admin/settings/smtp/autodiscover` | Auto-discover settings | ✅ Exists |

## JavaScript Behavior

### Auto-Load Configuration
- On tab activation, fetch current config from API
- Populate form fields with existing values
- Update card status badge

### Test Email
- Modal with recipient email input
- API call to send test email
- Display result (success / error with details)

### Port Auto-Update
- When encryption changes, auto-suggest port
- SSL → 465, STARTTLS → 587

### Form Validation
- Email: RFC 5322 validation
- Host: Required
- Port: 1-65535 range

## Error Handling

### Connection Errors
- **Timeout:** "Connection timed out. Check host and firewall."
- **Auth Failed:** "Authentication failed. Check username/password."
- **SSL Error:** "SSL certificate validation failed."
- **Send Failed:** "Failed to send email. Check SMTP configuration."

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
- ✅ Test email sends successfully
- ✅ Autodiscover works
- ✅ Mobile responsive
- ✅ No console errors

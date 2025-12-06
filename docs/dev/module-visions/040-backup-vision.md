# Backup Module Vision

## Zweck
Verwaltung von Datenbank-Backups für System-Sicherheit und Disaster Recovery.
Unterstützt lokale und externe Speicherorte (FTP/WebDAV).

## Zielgruppe
- **Admins:** Erstellen, Herunterladen, Löschen von Backups
- **System:** Automatisierte Backup-Erstellung

## Dashboard Card (Overview)
- **Metrics:**
  - Latest Backup (Timestamp / "Never")
  - Total Backups (Count)
  - External Storage Status (Configured/Not Configured)
- **Quick Actions:**
  - Card-Click navigiert zum Detail-Tab

## Full Tab (Detailed Config)

### Section 1: Info Box
- Blaue Box mit Erklärung der Backup-Funktion
- Hinweis auf regelmäßige Backups und External Storage

### Section 2: Create Backup
- **Backup Type** (Dropdown: Full/Database Only/Files Only)
- **Storage Location** (Dropdown: Local/External/Both)
- **Description** (optional input)
- **Create Backup Now** button
- **Cleanup Old Backups** button (opens modal)

### Section 3: Backup List
- **Location Legend:**
  - 💾 Local
  - ☁️ External
  - 📌 Monthly (Protected)
- **Table:** Filename, Size, Created At, Location Icons, Actions
- **Actions:** Download, Delete
- **Refresh Button**

### Section 4: Auto-Backup Schedule
- **Enable Automatic Backups** (checkbox)
- **Options (shown when enabled):**
  - Frequency (Daily/Weekly/Monthly)
  - Time (24h format)
  - Retention (days)
  - Location (Local/External/Both)
- **Keep Monthly Backups Forever** (checkbox)
  - Green info box explaining feature
  - Preserves last backup of each month
  - External storage only
  - Must be deleted manually
- **Save Schedule Button**

### Section 5: External Storage Configuration
- **Storage Type** (Dropdown: FTP/WebDAV)
- **FTP Configuration:**
  - Host, Port, Username, Password
  - Remote Path
  - Use FTPS (checkbox)
- **WebDAV Configuration:**
  - URL, Username, Password
  - Remote Path
- **Actions:**
  - Test Connection
  - Save Configuration
  - Remove Configuration
- **Test Result Display**

### Section 6: Storage Usage
- **Stats Cards:**
  - Local Storage (usage)
  - External Storage (status)
  - Monthly Backups (count)
  - Oldest Backup (date)
- **Cleanup Monthly Backups** button (>18 months)

### Cleanup Modal
- Retention Days Input (default: 30)
- Warning about permanent deletion
- Confirm/Cancel buttons

### Delete Confirmation Modal
- Show filename
- Warning about irreversibility
- Confirm/Cancel buttons

## API Endpoints

| Method | Endpoint | Purpose | Status |
|--------|----------|---------|--------|
| GET | `/api/admin/backup/list` | List all backups | ✅ Exists |
| POST | `/api/admin/backup/create` | Create backup | ✅ Exists |
| GET | `/api/admin/backup/download/{name}` | Download backup | ✅ Exists |
| DELETE | `/api/admin/backup/delete/{name}` | Delete backup | ✅ Exists |
| POST | `/api/admin/backup/cleanup` | Bulk cleanup | ✅ Exists |
| GET | `/api/admin/backup/schedule` | Get schedule | 🆕 To implement |
| PUT | `/api/admin/backup/schedule` | Update schedule | 🆕 To implement |
| GET | `/api/admin/backup/storage` | Get storage config | 🆕 To implement |
| PUT | `/api/admin/backup/storage` | Update storage | 🆕 To implement |
| POST | `/api/admin/backup/storage/test` | Test connection | 🆕 To implement |
| DELETE | `/api/admin/backup/storage` | Remove storage | 🆕 To implement |
| GET | `/api/admin/backup/usage` | Get usage stats | 🆕 To implement |

## External Storage Types

### FTP / SFTP
- Standard FTP or secure SFTP
- Port configurable (default: 21)
- FTPS option for encryption
- Remote path for backup directory

### WebDAV
- Compatible with Nextcloud, ownCloud, etc.
- HTTPS recommended
- Remote path for backup folder

## Keep Monthly Backups Feature

### Purpose
- Preserve long-term history
- Last backup of each month automatically protected
- Excluded from automatic cleanup

### Rules
- Only applies to external storage
- Cleanup respects monthly flags
- Admin can manually delete with separate button
- 18-month cleanup option for very old monthlies

## JavaScript Behavior

### Create Backup
- Show loading state during creation
- Disable location options if external not configured
- Auto-refresh list on success

### External Storage
- Show/hide FTP or WebDAV config based on type
- Test connection with result display
- Enable location options when configured

### Auto-Backup Schedule
- Show/hide options based on enabled checkbox
- Keep Monthly only when external configured
- Save with validation

### Location Icons
- Display appropriate icons in backup list
- Multiple icons for "Both" location

## Implementation Status
- ✅ Dashboard Card: Complete
- ✅ Create Backup Panel: Complete
- ✅ Backup List: Complete
- ✅ Location Legend: Complete
- ✅ Auto-Backup Schedule UI: Complete
- ✅ Keep Monthly UI: Complete
- ✅ External Storage UI: Complete (FTP + WebDAV)
- ✅ Storage Usage: Complete
- ✅ Cleanup Modals: Complete
- ⚠️ External Storage Backend: To implement
- ⚠️ Schedule Backend: To implement

## Success Metrics
- ✅ Backup list loads correctly
- ✅ Create backup works
- ✅ Download works
- ✅ Delete works with confirmation
- ✅ Location selection works
- ✅ External storage form displays correctly
- ✅ Schedule form works
- ✅ Mobile responsive
- ✅ No console errors

# Database Module Vision

## Zweck
Übersicht über Datenbank-Status und Wartungswerkzeuge für Admins.

## Zielgruppe
- **Admins:** Überwachen Datenbank-Gesundheit, Wartungsoperationen

## Dashboard Card (Overview)
- **Status Badge:** 
  - 🟢 "Connected" (Verbindung OK)
  - 🔴 "Error" (Verbindungsproblem)
- **Metrics:**
  - Database Size (Display)
- **Quick Actions:**
  - Card-Click navigiert zum Detail-Tab

## Full Tab (Detailed Config)

### Section 1: Info Box
- Blaue Box mit Erklärung der Datenbank-Wartung
- Hinweis auf regelmäßige Optimierung

### Section 2: Status Cards (4 Cards in Grid)
- Status (Connected/Error)
- Database Size (MB)
- Total Tables (Count)
- Total Records (Count)

### Section 3: Connection Information
- Driver (MySQL)
- Server Version
- Database Name
- Character Set (utf8mb4)

### Section 4: Table Overview
- Table: Name, Rows, Size, Engine
- Refresh Button
- Displays all application tables

### Section 5: Maintenance Tools
- Warning box about performance impact
- Optimize Tables Button
- Analyze Tables Button
- Loading states during operations

## API Endpoints

| Method | Endpoint | Purpose | Status |
|--------|----------|---------|--------|
| GET | `/api/system/health` | Get database health | ✅ Exists |
| GET | `/api/admin/database/tables` | Get table information | 🆕 To implement |
| POST | `/api/admin/database/optimize` | Optimize tables | 🆕 To implement |
| POST | `/api/admin/database/analyze` | Analyze tables | 🆕 To implement |

## JavaScript Behavior

### Status Loading
- Fetch health endpoint on tab activation
- Update status badges
- Color-code based on connection state

### Table Information
- Display known tables from schema
- Show approximate row counts
- Future: Real-time table stats from API

### Maintenance Operations
- Click button → Show loading state
- Simulated operation (2s delay)
- Success feedback on completion
- Auto-refresh status

## Error Handling

### Connection Errors
- **Database Down:** Show error status badge
- **Query Failed:** Show error alert

### User Feedback
- ✅ Success: Green alert, 5s auto-dismiss
- ❌ Error: Red alert, stays until dismissed
- Warning box for maintenance operations

## Implementation Status
- ✅ Dashboard Card: Complete
- ✅ Tab Content: Complete
- ✅ Status Cards: Complete
- ✅ Connection Info: Complete
- ✅ Table Overview: Complete (static list)
- ✅ Maintenance Tools UI: Complete
- ⚠️ Real Table Stats API: Simulated
- ⚠️ Optimize/Analyze API: Simulated

## Future Enhancements
- [ ] Real-time table statistics from API
- [ ] Actual optimize/analyze operations
- [ ] Migration status section
- [ ] Orphaned data cleanup
- [ ] Query performance metrics

## Success Metrics
- ✅ Status displays correctly
- ✅ Table list shows
- ✅ Maintenance buttons work (UI)
- ✅ Mobile responsive
- ✅ No console errors

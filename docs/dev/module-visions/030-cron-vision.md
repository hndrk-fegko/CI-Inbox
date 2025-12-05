# Cron Module Vision

## Zweck
Überwachung und Verwaltung des Webcron-Polling-Services für E-Mail-Abruf.

## Zielgruppe
- **Admins:** Überwachen Service-Status, Troubleshooting, Webhook-Konfiguration
- **System:** Automatisierter E-Mail-Polling

## Dashboard Card (Overview)
- **Status Badge (based on executions/hour for minutely cron):** 
  - 🟢 "Healthy" (>55 executions in last hour)
  - 🟡 "Degraded" (30-55 executions in last hour)
  - 🟡 "Delayed" (<30 executions in last hour)
  - 🔴 "Stale" (<1 execution in last hour)
  - 🔴 "Never Run" (no executions)
- **Metrics:**
  - Last Execution (Timestamp / "X min ago")
  - Emails Today (Count)
- **Quick Actions:**
  - Card-Click navigiert zum Detail-Tab

## Health Thresholds (Minutely Cron)

| Status | Threshold | Meaning |
|--------|-----------|---------|
| 🟢 Healthy | >55/hour | Cron running normally |
| 🟡 Degraded | 30-55/hour | Some missed executions |
| 🟡 Delayed | <30/hour | Significant missed executions |
| 🔴 Stale | <1/hour | Cron not running |

## Full Tab (Detailed Config)

### Section 1: Info Box
- Blaue Box mit Erklärung der Webcron-Funktion
- Health threshold explanation for minutely cron

### Section 2: Status Cards (4 Cards in Grid)
- Service Status (Healthy/Degraded/Delayed/Stale)
- Last Poll (X min ago)
- Executions/Hour (X/60)
- Emails Today (Count)

### Section 3: Execution History
- Table: Timestamp, Accounts Polled, Emails Fetched, Duration, Status
- Pagination (10 per page)
- Refresh Button
- Color-coded status badges

### Section 4: Performance Statistics
- Avg Duration (ms)
- Total Polls (7 days)
- Total Emails (7 days)

### Section 5: Webhook Configuration 🆕
- **Webhook URL Display**
  - Read-only input with full URL
  - Copy button
  - Help text for cron services
- **Secret Token Display**
  - Read-only input with current token
  - Copy button
- **Regenerate Token**
  - Warning box about invalidation
  - Regenerate button
  - Confirmation modal

## API Endpoints

| Method | Endpoint | Purpose | Status |
|--------|----------|---------|--------|
| GET | `/api/system/cron-status` | Get cron service status | ✅ Exists |
| GET | `/api/admin/cron/history` | Get execution history | ✅ Exists |
| GET | `/api/admin/cron/statistics` | Get performance stats | ✅ Exists |
| GET | `/api/admin/cron/webhook` | Get webhook config | 🆕 To implement |
| POST | `/api/admin/cron/webhook/regenerate` | Regenerate token | 🆕 To implement |

## JavaScript Behavior

### Status Calculation
- Primary: Use `executions_last_hour` if available
- Fallback: Use `minutes_ago` for time-based status

### Auto-Refresh
- Poll status every 30 seconds
- Update status badges dynamically
- No page reload needed

### History Loading
- Paginated API calls
- Previous/Next navigation
- Show loading state during fetch

### Webhook Configuration
- Load webhook URL and token on init
- Copy to clipboard functionality
- Regenerate with confirmation modal

### Statistics
- Load on tab activation
- Display in formatted cards

## Error Handling

### Status Errors
- **API Failure:** Show error message in status card
- **Timeout:** Indicate service may be down

### Webhook Errors
- **Load Failed:** Show "Failed to load" placeholder
- **Regenerate Failed:** Show error alert

### User Feedback
- Status changes shown via badge color
- Refresh button for manual update
- Copy confirmation alerts
- Regenerate success/error alerts

## Implementation Status
- ✅ Dashboard Card: Complete
- ✅ Tab Content: Complete
- ✅ Status Cards: Complete
- ✅ Execution History Table: Complete
- ✅ Pagination: Complete
- ✅ Auto-Refresh (30s): Complete
- ✅ Statistics: Complete
- ✅ API Integration: Complete
- ✅ Health Thresholds: Updated (>55 Healthy, <30 Delayed, <1 Stale)
- ✅ Webhook Configuration UI: Complete
- ⚠️ Webhook API Endpoints: To implement

## Success Metrics
- ✅ Status loads correctly
- ✅ Auto-refresh works
- ✅ History pagination works
- ✅ Statistics display correctly
- ✅ Health thresholds work correctly
- ✅ Webhook URL displays correctly
- ✅ Copy to clipboard works
- ✅ Regenerate modal works
- ✅ Mobile responsive
- ✅ No console errors

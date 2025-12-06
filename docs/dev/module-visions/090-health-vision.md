# Health Module Vision (090-health)

## Purpose
Provide automated system health monitoring, diagnostics, and self-healing capabilities for CI-Inbox administrators.

## Core Features

### 1. Status Dashboard
- **Status Grid**: Visual overview of all system components
  - Database connectivity
  - IMAP/SMTP server status
  - Disk space availability
  - Cron job health
  - Email queue status
  - Session health

### 2. Automated Test Schedule
- **Cron-Based Execution**: Run health checks on configurable intervals
  - Every 5/15/30 minutes
  - Hourly, 6-hourly, or daily
- **Test Selection**: Enable/disable specific tests
- **Schedule Configuration**: Stored in `data/health-schedule.json`

### 3. Health Tests
| Test | What It Checks | Thresholds |
|------|----------------|------------|
| database | MySQL connectivity, query time | Connection fail = critical |
| imap | Socket connection to IMAP server | No config = warning, no connect = critical |
| smtp | Socket connection to SMTP server | No config = warning, no connect = critical |
| disk | Free disk space percentage | <5% = critical, <15% = warning |
| cron | Last webcron execution time | >60min = critical, >30min = warning |
| queue | Stuck email queue items | >10 stuck = critical, any stuck = warning |
| sessions | Session storage writability | Not writable = critical |

### 4. Self-Healing Actions
Automated fixes for common issues:

| Action | What It Does |
|--------|--------------|
| disk | Cleans log files older than 7 days, backups older than 30 days |
| queue | Resets failed queue items to pending, deletes items older than 7 days |
| sessions | Clears session files older than 24 hours |

### 5. Reporting
- **Test Reports**: Aggregated results by time period
- **Export**: Download complete health report as JSON
- **Healing Log**: History of all self-healing actions

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/health/summary` | Overall health status |
| GET | `/api/admin/health/status` | Detailed component status |
| GET | `/api/admin/health/schedule` | Get schedule config |
| PUT | `/api/admin/health/schedule` | Update schedule |
| POST | `/api/admin/health/test/{name}` | Run specific test |
| GET | `/api/admin/health/reports` | Get test reports |
| POST | `/api/admin/health/heal/{type}` | Execute self-healing |
| GET | `/api/admin/health/healing-log` | Get healing history |
| DELETE | `/api/admin/health/healing-log` | Clear healing log |
| GET | `/api/admin/health/export` | Export full report |

## Data Storage

```
data/
├── health-schedule.json    # Schedule configuration
├── health-reports.json     # Test results (last 500)
└── healing-log.json        # Self-healing history (last 100)
```

## Integration Points

### Webcron
- Health checks can be triggered by webcron
- Uses same cron infrastructure as email polling

### Notifications (Future)
- Email admin on critical issues
- Integration with webhooks for alerting

### Logger Module
- All health actions logged via LoggerService
- Errors appear in Logger module view

## UI Components

### Status Cards
```html
<div class="c-health-card c-health-card--healthy">
    <div class="c-health-card__icon">🗄️</div>
    <div class="c-health-card__content">
        <div class="c-health-card__title">Database</div>
        <div class="c-health-card__subtext">healthy</div>
    </div>
</div>
```

### Test Items
```html
<div class="c-health-test">
    <div class="c-health-test__header">
        <label class="c-health-test__label">
            <input type="checkbox" data-test="database">
            <span class="c-health-test__name">Database Connection</span>
        </label>
        <span class="c-health-test__status c-health-test__status--pass">✓ Passed</span>
    </div>
    <p class="c-health-test__description">Tests database connectivity.</p>
    <div class="c-health-test__actions">
        <button class="c-button c-button--sm">Run Test</button>
    </div>
</div>
```

## Security Considerations

1. **Admin Only**: All health endpoints require admin role
2. **Rate Limiting**: Self-healing actions limited to prevent abuse
3. **Logging**: All actions logged for audit trail
4. **No Secrets**: Health report export doesn't include passwords

## Future Enhancements

- [ ] Email notifications on critical status
- [ ] Webhook integration for external monitoring
- [ ] Historical trend charts
- [ ] Custom health check plugins
- [ ] Recovery playbooks for common issues

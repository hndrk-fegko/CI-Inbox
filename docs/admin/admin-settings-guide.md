# Admin Settings - User Guide

**Version:** 1.0  
**Last Updated:** December 2025

---

## Introduction

The Admin Settings panel provides a centralized interface for managing all system configurations. This guide covers all available modules and how to use them effectively.

---

## Accessing Admin Settings

1. Log in with an admin account
2. Click on your user avatar (top right)
3. Select "System Settings" from the dropdown
4. Or navigate directly to `/admin-settings.php`

---

## Dashboard Overview

The dashboard displays cards for each system component, showing current status and key metrics.

### Understanding Status Indicators

| Icon | Status | Meaning |
|------|--------|---------|
| 🟢 | Success/Healthy | Working correctly |
| 🟡 | Warning/Degraded | Needs attention |
| 🔴 | Error/Critical | Requires immediate action |

### Quick Navigation

- Click any card to jump directly to that module's settings
- Use the sidebar tabs to navigate between modules
- Click the **?** button (bottom right) for help

---

## Module Guide

### 📧 IMAP Configuration

Configure the incoming email server for receiving emails into the shared inbox.

**Key Settings:**
- **Host:** IMAP server address (e.g., `imap.gmail.com`)
- **Port:** Usually 993 (SSL) or 143 (TLS)
- **Encryption:** SSL recommended
- **Username/Password:** Your email credentials

**Features:**
- **Auto-Discover:** Enter an email address to automatically detect settings
- **Test Connection:** Verify configuration before saving
- **Folder Selection:** Choose which folder to monitor

**Tips:**
- Gmail/Google Workspace requires app-specific passwords
- Microsoft 365 may require OAuth2 (see OAuth module)

---

### 📤 SMTP Configuration

Configure the outgoing email server for sending replies from the shared inbox.

**Key Settings:**
- **Host:** SMTP server address (e.g., `smtp.gmail.com`)
- **Port:** Usually 587 (TLS) or 465 (SSL)
- **Sender Name:** Display name for outgoing emails
- **Sender Email:** Reply-to address

**Features:**
- **Auto-Discover:** Detect settings from email address
- **Test Email:** Send a test message to verify configuration

**Tips:**
- Use a recognizable sender name for better deliverability
- Test configuration after any changes

---

### ⏰ Webcron / Polling

Monitor the email polling service that checks for new messages.

**Status Indicators:**
- **Healthy (>55/hour):** Cron running correctly
- **Degraded (30-55/hour):** Some delays
- **Delayed (<30/hour):** Significant delays
- **Stale (<1/hour):** Cron not running

**Features:**
- **Execution History:** View recent polling runs with statistics
- **Webhook URL:** URL for external cron services
- **Token Management:** Regenerate security token if compromised

**Tips:**
- Use a reliable external cron service (cron-job.org, EasyCron)
- Monitor the health status regularly

---

### 💾 Backup Management

Create and manage database backups to protect your data.

**Backup Types:**
- **Full:** Database + uploaded files
- **Database Only:** Just the database
- **Files Only:** Just uploaded files

**Storage Locations:**
- 💾 **Local:** Stored on the server
- ☁️ **External:** FTP or WebDAV (Nextcloud)
- 📌 **Monthly:** Protected from automatic cleanup

**Features:**
- **Create Backup:** Manual backup creation
- **Auto-Backup:** Schedule automatic backups
- **Keep Monthly:** Preserve one backup per month
- **Cleanup:** Remove old backups

**External Storage Setup:**

*FTP:*
1. Enter FTP host, port, username, password
2. Set remote path for backups
3. Enable FTPS for encrypted connection
4. Test connection before saving

*WebDAV (Nextcloud):*
1. Enter WebDAV URL (usually ends with `/remote.php/dav/files/username/`)
2. Enter username and password
3. Set folder path
4. Test connection before saving

---

### 🗄️ Database Management

Monitor database health and perform maintenance.

**Information Displayed:**
- Connection status
- Database size
- Table count and sizes
- Migration status

**Maintenance Tools:**
- **Optimize:** Reclaim unused space
- **Analyze:** Update table statistics
- **Check for Orphans:** Find orphaned data

**Tips:**
- Run optimization monthly or after bulk deletions
- Monitor database size for growth trends

---

### 👥 User Management

Manage user accounts and access permissions.

**User Roles:**
- **Admin:** Full access to all settings
- **User:** Access to shared inbox only

**Features:**
- **Create User:** Add new team members
- **Edit User:** Modify name, email, role, status
- **Delete User:** Remove accounts (with confirmation)
- **Search/Filter:** Find users by name, email, role, status

**Tips:**
- Use strong passwords (minimum 8 characters)
- Deactivate rather than delete users to preserve history
- Assign Admin role sparingly

---

### 🔐 OAuth2 / SSO

Enable Single Sign-On with external identity providers.

**Supported Providers:**
- Google
- Microsoft / Azure AD
- GitHub
- Custom OIDC

**Setup Process:**
1. Register your application with the provider
2. Copy the Callback URL from CI-Inbox
3. Enter Client ID and Client Secret
4. Enable the provider
5. Save configuration

**Settings:**
- **Auto-Register:** Create accounts for new OAuth users
- **Default Role:** Role assigned to new OAuth users

**Tips:**
- Test with a non-admin account first
- Keep Client Secrets secure
- Use auto-register cautiously

---

### ✍️ Email Signatures

Manage signatures for team and personal emails.

**Signature Types:**

*Shared Inbox Signatures:*
- Used when replying from the team inbox
- Ensures consistent branding
- Admin-managed

*Personal Signatures:*
- Used for personal email workflow
- User-owned but admin can edit
- Yellow highlight in list

**Features:**
- **Create/Edit:** Full HTML support
- **Preview:** Live preview while editing
- **Variables:** Use `{{user.name}}`, `{{user.email}}`, `{{date}}`
- **Default:** Set default signature for team

---

### 📋 System Logs

View and manage system logs for debugging.

**Log Levels:**
- **DEBUG:** Detailed information (high volume)
- **INFO:** General operational messages
- **WARNING:** Potential issues
- **ERROR:** Errors requiring attention

**Features:**
- **Live Viewer:** Real-time log display
- **Filters:** By level, module, time range
- **Download:** Export logs for analysis
- **Clear:** Remove old log files

**Tips:**
- Use INFO level in production
- Check logs after configuration changes
- Download logs before clearing

---

## Help System

Click the blue **?** button in the bottom right corner of any page to access context-sensitive help.

The help panel includes:
- Overview of current module
- Step-by-step instructions
- Tips and best practices
- Troubleshooting guidance

**Sections are collapsible** - click the section title to expand or collapse.

---

## Keyboard Shortcuts

| Shortcut | Action |
|----------|--------|
| `Esc` | Close modal/help panel |

---

## Troubleshooting

### Common Issues

**"Connection Failed" when testing IMAP/SMTP:**
- Verify credentials are correct
- Check if provider requires app-specific passwords
- Try different encryption (SSL vs TLS)
- Ensure firewall allows outbound connections

**Cron showing as "Stale":**
- Verify external cron service is configured
- Check webhook URL is accessible
- Regenerate token if needed
- Check error logs for details

**Users can't log in:**
- Verify user is marked as "Active"
- Check password meets requirements
- Try password reset
- Check for OAuth configuration issues

**Backups failing:**
- Check disk space availability
- Verify external storage credentials
- Check file permissions
- Review error logs

---

## Getting Help

If you encounter issues not covered in this guide:

1. Check the **?** help button for context-specific guidance
2. Review the system logs for error messages
3. Consult the developer documentation
4. Contact your system administrator

---

## Changelog

### Version 1.0 (December 2025)
- Initial user guide
- All 9 modules documented
- Help system integration

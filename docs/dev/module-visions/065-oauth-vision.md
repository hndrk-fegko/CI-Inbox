# OAuth2 Module Vision

## Zweck
Konfiguration von OAuth2/SSO-Providern für Single Sign-On Authentifizierung.

## Zielgruppe
- **Admins:** Konfigurieren OAuth2-Provider (Google, Microsoft, GitHub, Custom OIDC)
- **Users:** Nutzen SSO für einfachen Login

## Decision: Separate Module (not in Users)

### Reasoning
1. OAuth2 involves multiple providers with complex settings
2. Each provider has client ID, secret, scopes, redirect URIs
3. Token management and session handling are specialized
4. Following existing pattern (IMAP/SMTP are separate despite both being email)
5. Priority 65 places it logically after Users (60) and before Signatures (70)

## Dashboard Card (Overview)
- **Status Badge:**
  - 🟢 "Active" (enabled + providers configured)
  - 🟡 "Disabled" (providers exist but OAuth disabled)
  - 🟡 "Not Configured" (no providers)
- **Metrics:**
  - Active Providers (Count)
  - OAuth Users (Count)
- **Quick Actions:**
  - Card-Click navigiert zum Detail-Tab

## Full Tab (Detailed Config)

### Section 1: Info Box
- Blue box explaining OAuth2/SSO concept
- Note about provider registration requirements

### Section 2: Global Settings
- **Enable OAuth2** (checkbox)
- **Callback URL** (read-only with copy button)
- **Auto-register new users** (checkbox)
- **Default role for OAuth users** (dropdown: User/Admin)
- **Save Global Settings** button

### Section 3: OAuth Providers

#### Google Provider
- Enable toggle
- Client ID input
- Client Secret input
- Link to Google Cloud Console

#### Microsoft Provider
- Enable toggle
- Client ID input
- Client Secret input
- Tenant ID input (common or specific)
- Link to Azure Portal

#### GitHub Provider
- Enable toggle
- Client ID input
- Client Secret input
- Link to GitHub Developer Settings

#### Custom OIDC Provider
- Enable toggle
- Provider Name input
- Discovery URL input
- Client ID input
- Client Secret input
- Scopes input

### Section 4: OAuth Users & Sessions
- Table: User, Provider, Linked Date, Last Login
- Refresh button

## API Endpoints

| Method | Endpoint | Purpose | Status |
|--------|----------|---------|--------|
| GET | `/api/admin/oauth/config` | Get OAuth configuration | 🆕 To implement |
| PUT | `/api/admin/oauth/config` | Update global settings | 🆕 To implement |
| PUT | `/api/admin/oauth/providers` | Update provider config | 🆕 To implement |
| GET | `/api/admin/oauth/users` | List OAuth-linked users | 🆕 To implement |

## JavaScript Behavior

### Provider Toggles
- Show/hide provider config when toggle changes
- Independent enable/disable per provider

### Callback URL
- Auto-generate based on current domain
- Copy to clipboard functionality

### Form Submission
- Separate save for global settings and providers
- Validation of required fields
- Success/error feedback

### Sessions List
- Load OAuth users on init
- Refresh button to reload
- Show provider badge per user

## Implementation Status
- ✅ Dashboard Card: Complete
- ✅ Global Settings Section: Complete
- ✅ Provider Configuration UI: Complete
  - ✅ Google
  - ✅ Microsoft
  - ✅ GitHub
  - ✅ Custom OIDC
- ✅ Toggle Switches: Complete
- ✅ Sessions Table: Complete
- ⚠️ Backend APIs: To implement
- ⚠️ Actual OAuth Flow: To implement

## Security Considerations

### Client Secrets
- Never expose in API responses (except masked)
- Store encrypted in database
- "Leave empty to keep current" pattern for updates

### Provider Validation
- Validate client ID format per provider
- Test connection before saving (optional)

### Auto-Registration
- Admin decides if new OAuth users auto-register
- Default role prevents privilege escalation

## Success Metrics
- ✅ Provider configurations display correctly
- ✅ Toggle switches work
- ✅ Callback URL copies correctly
- ✅ Form saves work (when API implemented)
- ✅ Mobile responsive
- ✅ No console errors

## Future Enhancements
- [ ] Provider connection testing
- [ ] User linking/unlinking
- [ ] Session management (revoke)
- [ ] Multi-provider per user
- [ ] SAML support

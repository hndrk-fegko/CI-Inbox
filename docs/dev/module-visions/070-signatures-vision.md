# Signatures Module Vision

## Zweck
Verwaltung von E-Mail-Signaturen für das Shared Inbox und persönliche E-Mail-Workflows.

## Zielgruppe
- **Admins:** Full CRUD für alle Signaturen (Shared + Personal)
- **Users:** Können persönliche Signaturen erstellen (in User-Settings)

## Signature Types

### 1. Shared Inbox Signatures (Global)
- Used when team members reply from the **shared inbox**
- Ensures consistent branding across all team responses
- Created and managed by admin
- All team members can select from available signatures when replying

### 2. Personal Signatures (User-owned)
- Used when user takes **personal ownership** of a thread (Workflow C)
- User moves thread to personal IMAP and responds from there
- Users create these, but **admin can edit** for support/compliance
- Clear visual distinction (yellow background)

## Dashboard Card (Overview)
- **Metrics:**
  - Shared Inbox Signatures (Count)
  - Personal Signatures (Count)
- **Quick Actions:**
  - Card-Click navigiert zum Detail-Tab

## Full Tab (Detailed Config)

### Tab Navigation
- **Shared Inbox Signatures** tab
- **Personal Signatures** tab

### Shared Inbox Signatures Tab
- **Default Signature Selection** (dropdown)
- **Add Signature Button**
- **Signature List:**
  - Name
  - Default badge
  - Created date
  - Preview (truncated)
  - Actions: Edit, Delete

### Personal Signatures Tab
- **Warning Box:** Editing affects user's emails
- **Filter by User** (dropdown)
- **Add for User Button**
- **Signature List:**
  - Name
  - User name (highlighted)
  - Created date
  - Preview (truncated)
  - Actions: Edit, Delete

### Add/Edit Modal
- Signature Name (required)
- User Selection (for personal only)
- Content (textarea with HTML support)
- Live Preview panel
- Variable placeholders: `{{user.name}}`, `{{user.email}}`, `{{date}}`

### Delete Confirmation Modal
- Show signature name
- Warning about irreversibility

## API Endpoints

| Method | Endpoint | Purpose | Status |
|--------|----------|---------|--------|
| GET | `/api/admin/signatures` | List all signatures | ✅ Exists |
| POST | `/api/admin/signatures` | Create signature | ✅ Exists |
| PUT | `/api/admin/signatures/{id}` | Update signature | ✅ Exists |
| DELETE | `/api/admin/signatures/{id}` | Delete signature | ✅ Exists |
| PUT | `/api/admin/signatures/default` | Set default | 🆕 To implement |

## JavaScript Behavior

### Tab Switching
- Update active tab styling
- Show/hide tab content
- Preserve state between switches

### Signature CRUD
- Add/Edit via modal
- Live preview updates as user types
- Validation before save
- Success/error feedback

### Default Selection
- Dropdown to set default shared signature
- Auto-save on change
- Visual badge on default signature

### User Filter
- Filter personal signatures by user
- Update list dynamically

## Implementation Status
- ✅ Dashboard Card: Complete
- ✅ Tab Navigation: Complete
- ✅ Shared Inbox Signatures: Complete
- ✅ Personal Signatures: Complete
- ✅ Add/Edit Modal: Complete
- ✅ Delete Modal: Complete
- ✅ Live Preview: Complete
- ✅ Variable Support: Complete
- ✅ API Integration: Complete

## Design Decisions

### Why Admin Can Edit Personal Signatures?
1. Small team (3-7 people) - admin needs to help users
2. Compliance requirements may require admin oversight
3. Support scenarios where user needs help
4. Clear warning shown before editing

### Why Two Types?
1. Shared Inbox = Team consistency, corporate branding
2. Personal = User autonomy when they own the thread
3. Different workflows require different signatures

## Success Metrics
- ✅ Both signature types display correctly
- ✅ CRUD operations work for both types
- ✅ Default signature selection works
- ✅ Live preview works
- ✅ Variable replacement works in preview
- ✅ User filter works
- ✅ Mobile responsive
- ✅ No console errors

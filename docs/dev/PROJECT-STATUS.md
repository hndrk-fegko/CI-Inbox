# CI-Inbox: Project Status & Documentation Index

**Last Updated:** 18. November 2025  
**Current Milestone:** M3 - MVP UI (Planning)

---

## 📊 Project Status

### Completed Milestones

✅ **M0 Foundation** (4 hours)
- Logger, Config, Encryption, Database, Core Infrastructure

✅ **M1 IMAP Core** (~11 hours)
- IMAP Client (18 methods, Keywords support)
- Email Parser (HTML/Plain/Attachments)
- Thread Manager (Message-ID, References, Subject matching)
- Label Manager (System + Custom labels)
- Webcron-Polling-Dienst (API Key + IP Whitelist auth)
- Setup Auto-Discovery Wizard

✅ **M2 Thread API** (~9.5 hours)
- Thread Management API (10 endpoints)
- Email Send API (SMTP integration, 3 endpoints)
- Webhook Integration (7 endpoints, HMAC security)

**Total:** ~4,200 lines production code + ~2,800 lines tests + comprehensive documentation

### Current Focus

🎯 **M3 MVP UI** (In Progress by other agents)
- UI Agent: Building frontend components
- Settings Agent: Implementing user settings

---

## 📚 Documentation Structure

### Project-Level Documentation (`docs/dev/`)

| Document | Purpose | Status |
|----------|---------|--------|
| **vision.md** | Project goals, workflows, use cases | ✅ Complete |
| **inventar.md** | Feature list (MUST/SHOULD/COULD) | ✅ Updated |
| **roadmap.md** | M0-M5 timeline, sprint details | ✅ Complete |
| **architecture.md** | System architecture, tech stack | ✅ Complete |
| **codebase.md** | Dev environment, conventions | ✅ Complete |
| **workflow.md** | Development process, 5 phases | ✅ Complete |
| **M1-Preparation.md** | M1 preparation guide | ✅ Updated |
| **Setup-Autodiscover.md** | Setup wizard documentation | ✅ New |
| **Mercury-Setup.md** | Mercury configuration guide | ✅ Updated |
| **[COMPLETED] Sprint Docs** | M0 (5 sprints), M1 (5 sprints), M2 (3 sprints) | ✅ 13 docs |

### Module-Level Documentation

#### Logger Module (`src/modules/logger/`)
- ✅ `README.md` - PSR-3 logging, usage examples

#### Config Module (`src/modules/config/`)
- ✅ `README.md` - Configuration management

#### Encryption Module (`src/modules/encryption/`)
- ✅ `README.md` - AES-256-CBC encryption

#### IMAP Module (`src/modules/imap/`)
- ✅ `README.md` - Full API reference (430 lines)
- ✅ `QUICKSTART.md` - 5-minute setup guide (NEW)
- ✅ `tests/README.md` - All test scripts
- ✅ `tests/_archive/README.md` - Archived scripts

#### Webcron Module (`src/modules/webcron/`)
- ✅ `README.md` - Webcron orchestration, API reference (500+ lines)
- ✅ `tests/webcron-poll-test.php` - Test suite

---

## 🗂️ File Organization

### Source Code

```
src/
├── core/                        # Core infrastructure
│   ├── Application.php
│   ├── Container.php
│   ├── HookManager.php
│   └── ModuleLoader.php
├── modules/                     # Standalone modules
│   ├── logger/                  # PSR-3 logging ✅
│   ├── config/                  # Configuration ✅
│   ├── encryption/              # AES-256-CBC ✅
│   ├── imap/                    # IMAP client ✅
│   ├── smtp/                    # SMTP client (PHPMailer) ✅
│   ├── label/                   # Label management ✅
│   ├── webcron/                 # Email polling orchestration ✅
│   └── auth/                    # Authentication (planned)
├── app/                         # Application layer
│   ├── Models/                  # Eloquent models (10+) ✅
│   ├── Controllers/             # ThreadController, EmailController, WebhookController ✅
│   ├── Services/                # ThreadService, EmailSendService, WebhookService ✅
│   └── Repositories/            # Data Access Layer ✅
├── config/                      # Configuration files
├── routes/                      # HTTP routes
├── public/                      # Public web root
└── bootstrap/                   # Bootstrap files
```

### Documentation

```
docs/
├── dev/                         # Developer documentation
│   ├── vision.md
│   ├── inventar.md
│   ├── roadmap.md
│   ├── architecture.md
│   ├── codebase.md
│   ├── workflow.md
│   ├── M1-Preparation.md
│   ├── Setup-Autodiscover.md    # ⭐ NEW
│   ├── Mercury-Setup.md
│   └── [COMPLETED] M0-Sprint-*.md
├── admin/                       # Admin documentation (TODO)
└── user/                        # User documentation (TODO)
```

### Database

```
database/
├── migrations/                  # 7 migration files
│   ├── 001_create_users_table.php
│   ├── 002_create_imap_accounts_table.php
│   ├── 003_create_threads_table.php
│   ├── 004_create_emails_table.php
│   ├── 005_create_labels_table.php
│   ├── 006_create_thread_assignments_table.php
│   └── 007_create_thread_labels_table.php
├── migrate.php                  # Migration runner
└── test.php                     # CRUD tests
```

---

## 🚀 Quick Access Guide

### For New Developers

1. **Start here:** `README.md` (project root)
2. **Understand vision:** `docs/dev/vision.md`
3. **Setup environment:** `docs/dev/codebase.md`
4. **Module quick start:** `src/modules/imap/QUICKSTART.md`

### For Contributors

1. **Feature planning:** `docs/dev/inventar.md`
2. **Sprint details:** `docs/dev/roadmap.md`
3. **Architecture:** `docs/dev/architecture.md`
4. **Workflow:** `docs/dev/workflow.md`

### For Testing

1. **IMAP testing:** `src/modules/imap/tests/README.md`
2. **Webcron testing:** `tests/manual/webcron-poll-test.php`
3. **Thread API testing:** `tests/manual/thread-api-test.php`
4. **Webhook testing:** `tests/manual/webhook-test.php`
5. **Mercury setup:** `docs/dev/Mercury-Setup.md`
6. **Setup wizard:** `docs/dev/Setup-Autodiscover.md`

### Module Documentation Quick Links

| Module | Documentation | Status |
|--------|---------------|--------|
| **Logger** | `src/modules/logger/README.md` | ✅ Complete |
| **Config** | `src/modules/config/README.md` | ✅ Complete |
| **Encryption** | `src/modules/encryption/README.md` | ✅ Complete |
| **IMAP** | `src/modules/imap/README.md` | ✅ Complete |
| **SMTP** | `src/modules/smtp/README.md` | ✅ Complete |
| **Label** | `src/modules/label/README.md` | ✅ Complete |
| **Webcron** | `src/modules/webcron/README.md` | ✅ Complete |

---

## 🏆 Achievements

- ✅ **13 Sprint Documents** completed (M0: 5, M1: 5, M2: 3)
- ✅ **7 Standalone Modules** with full documentation
- ✅ **27 API Endpoints** implemented and tested
- ✅ **~7,000 lines of code** (4,200 production + 2,800 tests)
- ✅ **Layer Abstraction** strictly enforced (basics.txt compliance)
- ✅ **Production-tested** (Mercury + webhoster.ag)
- ✅ **Graceful Degradation** proven (IMAP Keywords optional)
- ✅ **Security-first** (Encryption, HMAC, API Key Auth)

### For Deployment

1. **Installation:** `docs/dev/codebase.md` (Section 2.2)
2. **Setup wizard:** `src/modules/imap/tests/setup-autodiscover.php`
3. **Configuration:** `.env.example`

---

## 🎯 Next Steps

### Immediate (M1 Sprint 1.2)

**E-Mail Parser Implementation**
- Body sanitization (HTML Purifier)
- Attachment extraction
- Header parsing (Message-ID, In-Reply-To, References)
- Thread detection preparation

**Estimated:** 2 days

**See:** `docs/dev/roadmap.md` → M1 Sprint 1.2

### Upcoming (M1 Sprint 1.3-1.4)

- Threading Engine (2 days)
- Webcron Service (2 days)

**Total M1:** ~9 days (3 sprints remaining)

---

## 📝 Documentation Best Practices

### Module Documentation

**Every module should have:**
- ✅ `README.md` - Full API reference
- ✅ `module.json` - Module manifest
- ⚠️ `QUICKSTART.md` - Optional, for complex modules
- ⚠️ `tests/README.md` - If tests exist

### Project Documentation

**Update when:**
- ✅ New feature completed → Update `inventar.md`
- ✅ Sprint completed → Update `roadmap.md`
- ✅ Architecture changes → Update `architecture.md`
- ✅ New workflow → Update `workflow.md`

### WIP Documents

**During sprint:**
- Create `[WIP] M1-Sprint-1.X-Feature.md`
- Track progress, decisions, problems

**After sprint:**
- Rename to `[COMPLETED] M1-Sprint-1.X-Feature.md`
- Update relevant permanent docs
- Archive if needed

---

## 🗃️ Archive Policy

### What to Archive

- ❌ **Don't delete** deprecated code
- ✅ **Archive** in `_archive/` subdirectory
- ✅ **Document** why archived (README.md in archive)

### Archive Locations

- Test scripts: `src/modules/imap/tests/_archive/`
- Documentation: `docs/dev/_archive/` (if needed)
- Code: `src/_archive/` (if needed)

**Example:** `src/modules/imap/tests/_archive/`
- Old test scripts moved here
- README.md explains replacements
- Available for reference

---

## ✅ Quality Checklist

### Before Committing

- [ ] Code follows `basics.txt` guidelines
- [ ] Module README updated
- [ ] Project docs updated (`inventar.md`, `roadmap.md`)
- [ ] Tests written and passing
- [ ] Deprecated code archived (not deleted)
- [ ] WIP documents finalized

### Sprint Completion

- [ ] All deliverables implemented
- [ ] Documentation complete
- [ ] Tests passing
- [ ] WIP document → COMPLETED
- [ ] Update `M1-Preparation.md` or equivalent
- [ ] Update `roadmap.md` status

---

## 📞 Support & Resources

**Documentation:**
- Project root: `README.md`
- Developer docs: `docs/dev/`
- Module docs: `src/modules/*/README.md`

**Testing:**
- Test scripts: `src/modules/imap/tests/`
- Mercury setup: `docs/dev/Mercury-Setup.md`

**Guidelines:**
- Coding standards: `basics.txt`
- Workflow: `docs/dev/workflow.md`

---

**Last Review:** 17. November 2025  
**Next Review:** After M1 Sprint 1.2 completion

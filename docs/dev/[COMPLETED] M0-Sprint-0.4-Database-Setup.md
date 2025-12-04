# [COMPLETED] M0 Sprint 0.4: Database-Setup

**Status:** ✅ ABGESCHLOSSEN | **Sprint:** 0.4 | **Start:** 17.11.2025 | **Ende:** 17.11.2025 | **Dauer:** ~35 Min

---

## Ziel

Datenbank-Schema implementieren: 7 Tabellen mit Migrations, Eloquent-Setup, Seeding.

---

## Tasks

| # | Task | Status | Notizen |
|---|------|--------|---------|
| 1 | Eloquent Setup & Config | ✅ | bootstrap/database.php |
| 2 | Migration: users | ✅ | id, email, password_hash |
| 3 | Migration: imap_accounts | ✅ | user_id, password_encrypted |
| 4 | Migration: threads | ✅ | subject, participants |
| 5 | Migration: emails | ✅ | thread_id, message_id, body |
| 6 | Migration: labels | ✅ | name, color |
| 7 | Migration: thread_assignments | ✅ | thread_id, user_id |
| 8 | Migration: thread_labels | ✅ | thread_id, label_id |
| 9 | Base Model erstellen | ✅ | BaseModel.php |
| 10 | Eloquent Models | ✅ | User, ImapAccount, Thread, Email, Label |
| 11 | Manual Test | ✅ | 10 Tests passed |

**Test Command:**
```bash
php database/migrate.php   # Run migrations
php database/test.php      # Test CRUD operations
```

---

## Schema-Referenz

Siehe `architecture.md` Section 6 für vollständige Tabellen-Definitionen.

**Dependencies:**
- Config-Modul ✅
- Encryption-Modul ✅ (für imap_accounts.password_encrypted)

---

## Lessons Learned

✅ **Was gut lief:**
- Eloquent Capsule standalone funktioniert perfekt
- 7 Tabellen in < 10 Minuten erstellt
- Relationships (belongsTo, hasMany, belongsToMany) out-of-the-box
- JSON-Casting für Arrays funktioniert

📝 **Erkenntnisse:**
- Pivot-Tabellen: `withPivot()` für custom columns, keine timestamps
- DateTime statt `now()` für Standalone-Tests
- Foreign Keys: Eloquent erstellt Constraints automatisch
- Fulltext-Index für E-Mail-Suche vorbereitet

⚠️ **Bugfixes:**
- Pivot timestamps entfernt (assigned_at, applied_at als custom columns)
- Test cleanup am Anfang (unique constraint errors vermeiden)

---

## Next

Nach Abschluss → M0 Sprint 0.5: Core-Infrastruktur (Application.php, Container, HookManager)

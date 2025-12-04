# Archive: Veraltete Dokumentation

Dieser Ordner enthält **archivierte Dokumente**, die durch neuere ersetzt wurden.

---

## Abgeschlossene Sprint-Dokumentationen

Die folgenden Sprint-Dokumente wurden archiviert, da ihre Implementierungen abgeschlossen sind und die relevanten Informationen in die Hauptdokumentation integriert wurden:

### M0 Foundation (Completed)
- `[COMPLETED] M0-Sprint-0.1-Logger-Modul.md`
- `[COMPLETED] M0-Sprint-0.2-Config-Modul.md`
- `[COMPLETED] M0-Sprint-0.3-Encryption-Service.md`
- `[COMPLETED] M0-Sprint-0.4-Database-Setup.md`
- `[COMPLETED] M0-Sprint-0.5-Core-Infrastruktur.md`

### M1 IMAP Core (Completed)
- `[COMPLETED] M1-Sprint-1.1-IMAP-Client-Modul.md`
- `[COMPLETED] M1-Sprint-1.2-Email-Parser.md`
- `[COMPLETED] M1-Sprint-1.3-Thread-Manager.md`
- `[COMPLETED] M1-Sprint-1.4-Label-Manager.md`
- `[COMPLETED] M1-Sprint-1.5-Webcron-Polling-Dienst.md`

### M2 Thread & Email API (Completed)
- `[COMPLETED] M2-Sprint-2.1-Thread-Management-API.md`

### M3 Features (Completed)
- `[COMPLETED] M3-Admin-Features.md`
- `[COMPLETED] Batch-3.3-Cron-Monitor.md`

### Bugfix Sessions
- `BUGFIX-2025-11-18.md` - API 500 Fehler, Ctrl+Click Multi-Select, Cache-Busting

---

## workflow-v1.0-2025-11-17.md

**Erstellt:** 17. November 2025  
**Archiviert:** 17. November 2025 (gleicher Tag)  
**Grund:** Ersetzt durch `roadmap.md` (KI-optimierte Sprint-Struktur)

**Warum archiviert:**
- workflow.md beschrieb 5-Phasen-Workflow (strategisch)
- roadmap.md ist fokussierter auf Sprints & Meilensteine (operativ)
- Informationen wurden migriert:
  - **Testing-Strategie** → `codebase.md` § 10.2
  - **Health-Check Details** → `roadmap.md` M5 Sprint 5.3
  - **Performance-Metriken** → `roadmap.md` M5 Sprint 5.1
  - **Code-Review Checklist** → `codebase.md` § 10.1 (war schon da)

**Wertvoll für:**
- Historische Referenz (initiale Projekt-Planung)
- Verständnis der Evolution (5-Phasen → Sprint-basiert)

---

## Allgemeine Archivierungs-Richtlinien

**Wann archivieren?**
- Dokument ist veraltet (durch neueres ersetzt)
- Wichtige Infos wurden migriert
- Nicht mehr Teil des aktiven Workflows

**Dateinamen-Konvention:**
```
<original-name>-v<version>-<YYYY-MM-DD>.md
```

**Beispiel:**
- `workflow.md` → `workflow-v1.0-2025-11-17.md`
- `architecture.md` → `architecture-v2.0-2025-12-01.md`

**Niemals löschen:**
- Archiv = permanente Historie
- Git enthält Änderungen, aber Archiv = schneller Zugriff
- Lessons Learned können später nützlich sein

---

**Letzte Aktualisierung:** 4. Dezember 2025

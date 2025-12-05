# Archive: Veraltete Dokumentation

Dieser Ordner enthält **archivierte Dokumente**, die durch neuere ersetzt wurden.

---

## Ordnerstruktur

```
archive/
├── bugs/                    # Archivierte Bugfix-Dokumentation
│   └── BUGFIX-2025-11-18.md
├── workflow-v1.0-2025-11-17.md
└── README.md                # Diese Datei
```

---

## bugs/BUGFIX-2025-11-18.md

**Erstellt:** 18. November 2025  
**Archiviert:** 4. Dezember 2025  
**Grund:** Bugfix abgeschlossen, Dokumentation für historische Referenz

**Wertvoll für:**
- Nachvollziehbarkeit von Bugfixes
- Lessons Learned bei ähnlichen Problemen

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

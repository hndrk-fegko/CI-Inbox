# [COMPLETED] M0 Sprint 0.5: Core-Infrastruktur

**Status:** ✅ ABGESCHLOSSEN | **Sprint:** 0.5 | **Start:** 17.11.2025 | **Ende:** 17.11.2025 | **Dauer:** ~40 Min

---

## Ziel

Application-Kern implementieren: Application.php, Container (DI), HookManager, ModuleLoader, Routes.

---

## Tasks

| # | Task | Status | Notizen |
|---|------|--------|---------|
| 1 | Container.php | ✅ | PHP-DI Wrapper, Service Registration |
| 2 | HookManager.php | ✅ | Event-System für Module |
| 3 | ModuleLoader.php | ✅ | Lädt modules/*.json, registriert Services |
| 4 | Application.php | ✅ | Bootstrap, Run, ErrorHandler |
| 5 | Routes definieren | ✅ | routes/api.php, routes/web.php |
| 6 | HealthCheck Endpoint | ✅ | GET /api/system/health |
| 7 | index.php Update | ✅ | Nutzt Application.php |
| 8 | Manual Test | ✅ | App läuft, Health OK, Homepage rendered |

**Test Commands:**
```bash
# Homepage
curl http://ci-inbox.local/

# Health Check
curl http://ci-inbox.local/api/system/health

# API Info
curl http://ci-inbox.local/api
```

**All Tests:** ✅ PASSED

---

## Dependencies

- Logger ✅
- Config ✅
- Encryption ✅
- Database ✅

---

## Lessons Learned

✅ **Was gut lief:**
- PHP-DI Container nahtlose Integration
- Hook-System einfach und erweiterbar
- ModuleLoader automatisch discovery von modules/
- Slim App perfekt mit DI Container
- Routes-Trennung (api.php, web.php) übersichtlich

📝 **Erkenntnisse:**
- Container Definitions müssen zu Constructor-Signaturen passen
- LoggerService braucht string $logPath, nicht ConfigService
- Slim ErrorMiddleware bereits eingebaut
- Health-Endpoint super nützlich für Monitoring

⚠️ **Bugfixes:**
- Container: LoggerService Constructor-Parameter fix
- Config: log.path nicht definiert → Fallback zu default path

**Dateien erstellt (8):**
- src/core/Container.php
- src/core/HookManager.php  
- src/core/ModuleLoader.php
- src/core/Application.php
- src/routes/api.php
- src/routes/web.php
- src/config/container.php
- src/public/index.php (updated)

---

## Next

**🎉 M0 FOUNDATION COMPLETE! 🎉**

**Implementiert in 4-5 Stunden:**
- Sprint 0.1: Logger (~60 min)
- Sprint 0.2: Config (~50 min)
- Sprint 0.3: Encryption (~45 min)
- Sprint 0.4: Database (~35 min)
- Sprint 0.5: Core (~40 min)

**Total:** ~230 Min (3h 50min) statt geschätzter 2 Wochen!

→ Weiter mit **M1: IMAP Core** (Woche 3-4)

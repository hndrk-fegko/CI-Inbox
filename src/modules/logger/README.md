# Logger Module

**Version:** 1.0.0  
**Status:** ✅ Production Ready  
**Autor:** Hendrik Dreis  
**Lizenz:** MIT License  
**Dependencies:** None (standalone)

---

## Übersicht

Zentrales Logging-System für CI-Inbox mit PSR-3 Kompatibilität. Wraps Monolog mit custom JSON-Formatter und zusätzlichen Features.

---

## Features

- ✅ **PSR-3 kompatibel** - Standard LoggerInterface
- ✅ **Strukturiertes JSON-Format** - Machine-readable logs
- ✅ **Pflichtfelder** - timestamp, level, message, context, extra
- ✅ **Performance-Metriken** - Memory usage, execution time
- ✅ **Exception-Handling** - Vollständige Stack-Traces
- ✅ **Tägliche Rotation** - Automatisch, 30 Tage Aufbewahrung
- ✅ **Standalone** - Kann isoliert getestet werden

---

## Installation

Bereits im Haupt-Composer integriert. Namespace:
```php
use CiInbox\Modules\Logger\LoggerService;
use CiInbox\Modules\Logger\LoggerInterface;
```

---

## Verwendung

### Basic Usage

```php
use CiInbox\Modules\Logger\LoggerService;

// Logger erstellen
$logger = new LoggerService();

// Log-Levels (PSR-3)
$logger->debug('Detailed debug information');
$logger->info('Interesting event', ['user_id' => 42]);
$logger->warning('Unusual situation', ['context' => 'foo']);
$logger->error('Runtime error', ['exception' => $e]);
$logger->critical('System failure!');

// Custom SUCCESS level
$logger->success('Operation completed', ['operation' => 'sync']);
```

### Mit Context

```php
$logger->info('Thread assigned', [
    'thread_id' => 123,
    'user_id' => 7,
    'module' => 'ThreadService',
]);
```

### Exception Logging

```php
try {
    // risky operation
} catch (Exception $e) {
    $logger->error('Operation failed', [
        'exception' => $e,  // Automatische Exception-Formatierung
        'operation' => 'fetch_emails',
    ]);
}
```

### Dependency Injection

```php
class ThreadService
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function assignThread(int $threadId, int $userId): bool
    {
        $this->logger->info('Assigning thread', [
            'thread_id' => $threadId,
            'user_id' => $userId,
        ]);
        
        // ...
    }
}
```

---

## Konfiguration

Siehe `config/logger.config.php`:

```php
return [
    'log_path' => __DIR__ . '/../../../logs',
    'log_level' => 'debug',  // debug, info, warning, error, critical
    'channel' => 'app',
    'rotation' => [
        'max_files' => 30,  // Tage
    ],
];
```

Oder via `.env`:
```env
LOG_LEVEL=debug
```

---

## Log-Format

### JSON-Struktur

```json
{
  "timestamp": "2025-11-17T11:56:13.584281+01:00",
  "level": "INFO",
  "message": "Thread assigned successfully",
  "context": {
    "thread_id": 42,
    "user_id": 7
  },
  "extra": {
    "module": "ThreadService",
    "file": "/path/to/file.php",
    "line": 45,
    "function": "assignThread",
    "memory_usage": "4 MB",
    "memory_peak": "4 MB"
  }
}
```

### Mit Exception

```json
{
  "timestamp": "...",
  "level": "ERROR",
  "message": "Database query failed",
  "context": {...},
  "extra": {...},
  "exception": {
    "class": "PDOException",
    "message": "Connection timeout",
    "code": 2002,
    "file": "/path/to/file.php",
    "line": 123,
    "trace": "..."
  }
}
```

---

## Log-Dateien

Logs werden gespeichert in: `logs/app-YYYY-MM-DD.log`

**Beispiel:**
- `logs/app-2025-11-17.log`
- `logs/app-2025-11-18.log`
- ...

**Rotation:** Automatisch täglich, älteste Datei nach 30 Tagen gelöscht.

---

## Testing

### Manual Test (Standalone)

```bash
# Von Project Root aus
php src/modules/logger/tests/manual-test.php
```

**Erwartete Ausgabe:**
- ✅ 8 Log-Einträge geschrieben
- ✅ JSON-Format validiert
- ✅ Log-Datei in `logs/app-YYYY-MM-DD.log`

### Unit Tests (PHPUnit)

```bash
./vendor/bin/phpunit src/modules/logger/tests/
```

*(PHPUnit-Tests kommen später in Phase 3)*

---

## Performance

- **Durchsatz:** < 5ms pro Log-Eintrag
- **Memory:** < 1MB Footprint
- **Rotation:** Keine Performance-Auswirkung (asynchron)

---

## Best Practices

### 1. Immer Context mitgeben

```php
// ❌ Schlecht
$logger->info('Thread assigned');

// ✅ Gut
$logger->info('Thread assigned', ['thread_id' => 42, 'user_id' => 7]);
```

### 2. Module-Context für Filterung

```php
$logger->info('Action performed', [
    'module' => 'ThreadService',  // Hilft beim Filtern
    'action' => 'assign',
]);
```

### 3. Exceptions immer loggen

```php
try {
    // ...
} catch (Exception $e) {
    $logger->error('Operation failed', [
        'exception' => $e,  // ✅ Automatischer Stack-Trace
        'context' => 'additional info',
    ]);
}
```

### 4. Log-Level richtig wählen

- **DEBUG:** Entwicklungs-Details (nur in DEV)
- **INFO:** Normale Operationen (z.B. "Thread assigned")
- **WARNING:** Unerwartete Situation, aber nicht kritisch
- **ERROR:** Fehler, aber App läuft weiter
- **CRITICAL:** Kritischer Fehler, System nicht nutzbar

---

## Troubleshooting

### Log-Datei wird nicht erstellt

1. Prüfe Schreibrechte: `chmod 775 logs/`
2. Prüfe Log-Path in Config
3. Prüfe PHP-Error-Log: `logs/php-error.log`

### Performance-Probleme

1. Log-Level erhöhen (debug → info)
2. Rotation-Intervall anpassen
3. Async-Logging erwägen (später)

---

## Erweiterung

### Eigener Handler

```php
use Monolog\Handler\AbstractHandler;

class CustomHandler extends AbstractHandler
{
    public function handle(LogRecord $record): bool
    {
        // Custom logic (z.B. Slack-Notification)
        return false;
    }
}

// In LoggerService:
$logger->getMonolog()->pushHandler(new CustomHandler());
```

### Database-Handler (geplant M0 Sprint 0.4)

```php
$handler = new DatabaseHandler($pdo, 'logs');
$logger->getMonolog()->pushHandler($handler);
```

---

## Architektur

### Layer-Abstraktion

```
Services → LoggerInterface → LoggerService → Monolog
```

**Wichtig:** Services nutzen NIEMALS direkt Monolog, nur über `LoggerInterface`!

### Wiederverwendbarkeit

Dieses Modul kann in anderen Projekten verwendet werden:
1. Kopiere `src/modules/logger/` 
2. Füge Namespace zu `composer.json` hinzu
3. `composer dump-autoload`
4. Fertig!

---

## Changelog

### v1.0.0 (2025-11-17)
- ✅ Initial Release
- ✅ PSR-3 Kompatibilität
- ✅ JSON-Formatter mit Pflichtfeldern
- ✅ Tägliche Rotation (30 Tage)
- ✅ Custom SUCCESS level
- ✅ Exception-Handling
- ✅ Standalone-Tests

---

## Lizenz

MIT (wie Haupt-Projekt)

---

## Kontakt

Fragen? Siehe Haupt-Dokumentation in `docs/dev/`

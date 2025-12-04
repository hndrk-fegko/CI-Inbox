# Config Module

**Version:** 1.0.0  
**Status:** ✅ Production Ready  
**Dependencies:** Logger Module (optional)

---

## Übersicht

Zentrales Konfigurations-Management für CI-Inbox. Lädt ENV-Variablen und PHP-Config-Dateien mit type-safe Zugriff.

---

## Features

- ✅ **ENV-Loader** - .env Dateien via vlucas/phpdotenv
- ✅ **PHP-Configs** - Strukturierte Config-Dateien in src/config/
- ✅ **Type-Safe** - getString(), getInt(), getBool(), getArray()
- ✅ **Dot-Notation** - Nested access: `database.connections.mysql.host`
- ✅ **Default-Values** - Fallback-Werte wenn Key fehlt
- ✅ **Validation** - Exceptions bei fehlenden required Keys
- ✅ **Cached** - Config nur einmal geladen (Performance)
- ✅ **Standalone** - Kann isoliert getestet werden

---

## Installation

Bereits im Haupt-Composer integriert. Namespace:
```php
use CiInbox\Modules\Config\ConfigService;
use CiInbox\Modules\Config\ConfigInterface;
```

---

## Verwendung

### Basic Usage

```php
use CiInbox\Modules\Config\ConfigService;

// Config-Service erstellen
$config = new ConfigService();

// Werte abrufen
$appName = $config->get('app.name'); // 'CI-Inbox'
$dbHost = $config->get('database.connections.mysql.host'); // '127.0.0.1'
```

### Type-Safe Getters

```php
// String
$name = $config->getString('app.name'); // 'CI-Inbox'

// Integer
$port = $config->getInt('database.connections.mysql.port'); // 3306

// Boolean
$debug = $config->getBool('app.debug'); // true

// Array
$dbConfig = $config->getArray('database.connections'); // [...]
```

### Default Values

```php
// Mit Default-Value (wenn Key nicht existiert)
$timeout = $config->getInt('app.timeout', 30); // 30

// Oder explizit prüfen
if ($config->has('app.timeout')) {
    $timeout = $config->getInt('app.timeout');
}
```

### Nested Access (Dot-Notation)

```php
// Statt:
$host = $config->get('database')['connections']['mysql']['host'];

// Einfacher:
$host = $config->get('database.connections.mysql.host');
```

### Exception Handling

```php
use CiInbox\Modules\Config\Exceptions\ConfigException;

try {
    $apiKey = $config->getString('api.key'); // Required!
} catch (ConfigException $e) {
    echo "Config error: " . $e->getMessage();
    // "Required configuration key 'api.key' is missing"
}
```

### Dependency Injection

```php
class DatabaseService
{
    public function __construct(
        private ConfigInterface $config
    ) {}

    public function connect(): void
    {
        $host = $this->config->getString('database.connections.mysql.host');
        $port = $this->config->getInt('database.connections.mysql.port');
        // ...
    }
}
```

---

## Konfiguration

### .env Datei (Projekt-Root)

```env
# Application
APP_NAME="CI-Inbox"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://ci-inbox.local

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ci_inbox
DB_USERNAME=root
DB_PASSWORD=secret
```

### PHP Config-Dateien (src/config/)

**src/config/app.php:**
```php
<?php
return [
    'name' => $_ENV['APP_NAME'] ?? 'CI-Inbox',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    'timezone' => 'Europe/Berlin',
];
```

**src/config/database.php:**
```php
<?php
return [
    'connection' => $_ENV['DB_CONNECTION'] ?? 'mysql',
    'connections' => [
        'mysql' => [
            'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port' => (int) ($_ENV['DB_PORT'] ?? 3306),
            'database' => $_ENV['DB_DATABASE'] ?? 'ci_inbox',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4',
        ],
    ],
];
```

---

## Testing

### Manual Test (Standalone)

```bash
# Von Project Root aus
php src/modules/config/tests/manual-test.php
```

**Erwartete Ausgabe:**
- ✅ 9 Tests passed
- ✅ Type-safe getters funktionieren
- ✅ Dot-notation funktioniert
- ✅ Exceptions bei fehlenden Keys

---

## API Reference

### ConfigInterface

| Method | Beschreibung | Exception |
|--------|--------------|-----------|
| `get(string $key, mixed $default = null)` | Beliebigen Wert holen | - |
| `getString(string $key, ?string $default = null)` | String holen | ConfigException |
| `getInt(string $key, ?int $default = null)` | Integer holen | ConfigException |
| `getBool(string $key, ?bool $default = null)` | Boolean holen | ConfigException |
| `getArray(string $key, ?array $default = null)` | Array holen | ConfigException |
| `has(string $key)` | Prüfen ob Key existiert | - |
| `all()` | Gesamte Config als Array | - |
| `reload()` | Config neu laden | - |

---

## Best Practices

### 1. Type-Safe Getters verwenden

```php
// ❌ Schlecht (keine Type-Safety)
$port = $config->get('database.port');

// ✅ Gut (Type-Safe, Exception bei Fehler)
$port = $config->getInt('database.port');
```

### 2. Default-Values für optionale Configs

```php
// ✅ Optionale Config mit sinnvollem Default
$timeout = $config->getInt('app.timeout', 30);
$maxRetries = $config->getInt('app.max_retries', 3);
```

### 3. Required Configs explizit prüfen

```php
// ✅ Required Config ohne Default (Exception wenn fehlt)
try {
    $apiKey = $config->getString('api.key');
} catch (ConfigException $e) {
    die("API Key is required! Set API_KEY in .env");
}
```

### 4. Config in Konstruktoren injizieren

```php
// ✅ Dependency Injection
class ImapService
{
    public function __construct(
        private ConfigInterface $config
    ) {}
}
```

---

## Performance

- **Caching:** Config wird nur einmal geladen (Singleton-Pattern)
- **Memory:** < 100KB für typische Config
- **Startup:** < 5ms zum Laden aller Configs

---

## Troubleshooting

### "Required configuration key 'xxx' is missing"

**Ursache:** Key existiert nicht in Config

**Lösung:**
1. Prüfe .env Datei
2. Prüfe PHP-Config in src/config/
3. Nutze has() zum Prüfen: `if ($config->has('key'))`
4. Oder nutze Default-Value: `$config->get('key', 'default')`

### "Configuration key 'xxx' must be of type int, got string"

**Ursache:** Typ-Mismatch (z.B. String statt Integer)

**Lösung:**
1. Prüfe Config-Datei: `(int)` Cast bei Zahlen aus ENV
2. Oder nutze `get()` statt `getInt()` und caste manuell

### .env wird nicht geladen

**Ursache:** Datei nicht gefunden oder Syntax-Fehler

**Lösung:**
1. Prüfe ob .env im Projekt-Root existiert
2. Prüfe Syntax (keine Leerzeilen vor Schlüssel)
3. ENV-Variablen können auch system-weit gesetzt sein

---

## Erweiterung

### Eigene Config-Datei hinzufügen

```php
// src/config/mail.php
<?php
return [
    'driver' => $_ENV['MAIL_DRIVER'] ?? 'smtp',
    'host' => $_ENV['MAIL_HOST'] ?? 'localhost',
    'port' => (int) ($_ENV['MAIL_PORT'] ?? 587),
];
```

**Zugriff:**
```php
$mailHost = $config->getString('mail.host');
```

### Config-Quellen erweitern

```php
class CustomConfigService extends ConfigService
{
    protected function loadPhpConfigs(): void
    {
        parent::loadPhpConfigs();
        
        // Zusätzliche Quelle (z.B. YAML, JSON)
        $this->loadYamlConfigs();
    }
}
```

---

## Architektur

### Layer-Abstraktion

```
Services → ConfigInterface → ConfigService → [.env + PHP Files]
```

**Wichtig:** Services nutzen NIEMALS direkt $_ENV oder require, nur über ConfigInterface!

---

## Changelog

### v1.0.0 (2025-11-17)
- ✅ Initial Release
- ✅ ENV-Loader (phpdotenv)
- ✅ PHP-Config-Loader
- ✅ Type-Safe Getters
- ✅ Dot-Notation Support
- ✅ Exception Handling
- ✅ Standalone-Tests

---

## Lizenz

MIT (wie Haupt-Projekt)

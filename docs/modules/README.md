# Module Documentation Index

Dieses Verzeichnis enthält die **technische Dokumentation** für alle C-IMAP Module.

## Struktur

```
docs/modules/
├── webcron/          ← Email-Polling via Webcron/Webhook
├── imap/             ← IMAP Client & Sync
├── smtp/             ← Email-Versand (SMTP)
├── encryption/       ← Verschlüsselung (Passwörter, etc.)
└── logger/           ← Logging-System
```

---

## Module Overview

### 1. Webcron Module

**Pfad:** `src/modules/webcron/`  
**Dokumentation:** [webcron/README.md](webcron/README.md)

**Zweck:** Externes Triggern von Email-Polling via HTTP

**Features:**
- ✅ Interner Webcron (`/webcron/poll`)
- ✅ Externer Webhook (`/webhooks/poll-emails`)
- ✅ Job Locking (verhindert parallele Ausführung)
- ✅ Flexible Authentication (Token, Bearer, Query)
- ✅ Integration mit cron-job.org, EasyCron, etc.

**Key Classes:**
- `WebcronManager` - Orchestriert Polling-Jobs
- `WebcronManagerInterface` - Interface
- `WebcronConfig` - Konfiguration
- `WebcronException` - Custom Exception

---

### 2. IMAP Module

**Pfad:** `src/modules/imap/`  
**Dokumentation:** [imap/README.md](imap/README.md) ✅

**Zweck:** IMAP-Client für Email-Abruf und Ordner-Management

**Features:**
- ✅ IMAP Connection Handling
- ✅ Email Fetching (INBOX, Ordner)
- ✅ Email Parsing (Subject, Body, Attachments)
- ✅ Thread Detection (Message-ID, References)
- ✅ Folder Operations (List, Create, Delete)

**Key Classes:**
- `ImapClient` - IMAP Connection (625 lines)
- `EmailParser` - Email Parsing Orchestrator
- `ThreadManager` - Thread-Logik
- `HeaderParser`, `BodyParser`, `AttachmentParser` - Parser-Komponenten

---

### 3. SMTP Module

**Pfad:** `src/modules/smtp/`  
**Dokumentation:** [smtp/README.md](smtp/README.md) ✅

**Zweck:** Email-Versand via SMTP (PHPMailer)

**Features:**
- ✅ SMTP Connection (TLS/SSL)
- ✅ Email-Versand (Plain Text, HTML)
- ✅ Attachments
- ✅ CC/BCC Support
- ✅ Reply-To Headers

**Key Classes:**
- `PHPMailerSmtpClient` - SMTP Client (171 lines)
- `SmtpConfig` - SMTP-Konfiguration DTO
- `EmailMessage` - Email-Message DTO

---

### 4. Encryption Module

**Pfad:** `src/modules/encryption/`  
**Dokumentation:** [encryption/README.md](encryption/README.md) ✅

**Zweck:** Verschlüsselung von sensitiven Daten (IMAP/SMTP Passwörter)

**Features:**
- ✅ AES-256-CBC Encryption
- ✅ Secure Key Storage (Environment Variable)
- ✅ Encrypt/Decrypt Interface
- ✅ IV Generation

**Key Classes:**
- `EncryptionService` - Verschlüsselung (255 lines)
- `EncryptionInterface` - Interface

**Verwendung:**
```php
// Passwort verschlüsseln
$encrypted = $encryption->encrypt($password);

// Passwort entschlüsseln
$password = $encryption->decrypt($encrypted);
```

---

### 5. Logger Module

**Pfad:** `src/modules/logger/`  
**Dokumentation:** [logger/README.md](logger/README.md) ✅

**Zweck:** Strukturiertes Logging (Monolog-basiert)

**Features:**
- ✅ PSR-3 kompatibel
- ✅ Multiple Log Levels (DEBUG, INFO, ERROR, etc.)
- ✅ File Handler (Rotation)
- ✅ Context Support (Arrays, Objekte)
- ✅ Channel-Support

**Key Classes:**
- `LoggerService` - Logger-Implementierung (183 lines)
- `JsonFormatter` - JSON per Line Format
- `RotatingFileHandler` - Daily log rotation

**Log Levels:**
```
DEBUG    ← Development Details
INFO     ← Normal Operations
SUCCESS  ← Erfolgreiche Operationen (custom level)
WARNING  ← Warnungen (z.B. Auth-Failures)
ERROR    ← Fehler (z.B. IMAP-Connection failed)
CRITICAL ← Kritische Fehler (z.B. DB unavailable)
```

---

## Modul-Abhängigkeiten

```
┌─────────────┐
│   Logger    │  ← Wird von allen Modulen verwendet
└──────┬──────┘
       │
┌──────▼──────────────────────────────┐
│  Encryption, IMAP, SMTP, Webcron    │
└─────────────────────────────────────┘
```

**Regel:** Alle Module haben `LoggerInterface` als Dependency!

---

## Dokumentations-Standards

### README.md pro Modul

Jedes Modul sollte folgende Struktur haben:

```markdown
# [Module Name]

## Übersicht
- Was macht das Modul?
- Wofür wird es verwendet?

## Features
- Liste der Hauptfunktionen

## Architektur
- Klassen-Struktur
- Dependencies
- Flow-Diagramme

## Configuration
- Environment Variables
- Config-Dateien

## Usage Examples
- Code-Beispiele
- Typische Use Cases

## Testing
- Test-Anleitungen
- Test Coverage

## Troubleshooting
- Häufige Probleme
- Lösungen

## API Reference
- Public Methods
- Parameters
- Return Values
```

---

## Unterschied: Core Docs vs. Module Docs

### Core Docs (`docs/dev/`)
- ✅ **API-Referenz** (REST Endpoints)
- ✅ **Architektur-Übersicht** (Layer-Structure)
- ✅ **Feature-Dokumentation** (Personal IMAP Accounts API)
- ✅ **Roadmap & Sprint-Planung**
- ✅ **Codebase-Organisation**

### Module Docs (`docs/modules/`)
- ✅ **Modul-Implementierung** (technische Details)
- ✅ **Interne APIs** (Klassen, Methoden)
- ✅ **Konfiguration** (Environment Variables)
- ✅ **Testing** (Unit Tests, Integration Tests)
- ✅ **Troubleshooting** (spezifische Probleme)

**Faustregel:**
- User/API-Consumer → `docs/dev/`
- Developer/Contributor → `docs/modules/`

---

## TODO: Fehlende Dokumentation

### Prio 1 (Wichtig)
- [ ] `imap/README.md` - IMAP-Client Doku
- [ ] `encryption/README.md` - Encryption Details
- [ ] `logger/README.md` - Logging Standards

### Prio 2 (Nice to have)
- [ ] `smtp/README.md` - SMTP-Client Doku
- [ ] `webcron/CONFIGURATION.md` - Detaillierte Config-Optionen
- [ ] `imap/SYNC-PROCESS.md` - Sync-Algorithmus Details

---

## Related Documentation

- **Core API Docs:** `../dev/api.md`
- **Architecture:** `../dev/architecture.md`
- **Codebase Overview:** `../dev/codebase.md`

---

**Last Updated:** 18. November 2025

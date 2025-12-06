# CI-Inbox: Collaborative IMAP Inbox Management

**Leichtgewichtige IMAP-Inbox-Verwaltung für kleine autonome Teams (3-7 Personen).**

CI-Inbox verwandelt gemeinsam genutzte IMAP-Postfächer in kollaborative Aufgaben-Warteschlangen mit klarer Zuständigkeit, internen Notizen und vollständiger Nachvollziehbarkeit – ohne die Komplexität eines Ticketsystems.

[![Status](https://img.shields.io/badge/Status-M3%20In%20Progress-yellow)](https://github.com/hndrk-fegko/CI-Inbox)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-blue)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](CONTRIBUTING.md)

---

## 🎯 Projektziel

CI-Inbox löst das Problem chaotischer Shared-Inbox-Verwaltung für kleine Teams ohne Budget oder Bedarf für komplexe Ticketing-Systeme. Entwickelt für autonome Teams mit flexiblen Arbeitszeiten (z.B. Vereine, Kirchengemeinden, kleine NGOs, Startups).

**Kernfunktionen:**
- ✅ **Thread-basierte E-Mail-Gruppierung** (M1) - Automatische Konversations-Erkennung
- ✅ **Label-System** (M1) - Flexible Organisation mit System- und Custom-Labels
- ✅ **IMAP Keywords** (M1 Bonus) - Performance-Optimierung via Server-Keywords
- ✅ **Automatisches E-Mail-Polling** (M1.5) - Webcron-basiertes Polling
- ✅ **Thread Management API** (M2.1) - 10 REST-Endpoints für CRUD-Operationen
- ✅ **Email Send API** (M2.2) - SMTP-Integration für Senden/Antworten/Weiterleiten
- ✅ **Webhook Integration** (M2.3) - Integration mit externen Systemen
- ⏳ **Interne Notizen** (M3) - Kontext-Weitergabe im Team
- ⏳ **Persönliche IMAP-Übernahme** (M4) - Für sensible Themen
- ⏳ **100% Nachvollziehbarkeit** (M4) - Vollständiges Activity-Log

---

## 🎉 Milestones M0, M1 & M2 COMPLETED!

**Stand:** 18. November 2025

### ✅ M0 Foundation (4h)
- Logger, Config, Encryption, Database, Core Infrastructure

### ✅ M1 IMAP Core (~11h)
- IMAP-Client mit 18 Methoden (inkl. Keywords)
- E-Mail-Parser (HTML, Plain Text, Attachments)
- Thread-Manager (Message-ID, References, Subject-Matching)
- Label-Manager (System + Custom Labels)
- Webcron-Polling-Dienst (API Key + IP Whitelist auth)
- Production Setup-Wizard mit Certificate Auto-Discovery
- Graceful Degradation (funktioniert mit/ohne Keyword-Support)

### ✅ M2 Thread & Email API (~9.5h)
- Thread Management API (10 Endpoints: CRUD + Advanced Operations)
- Email Send API (3 Endpoints: Send, Reply, Forward)
- Webhook Integration (7 Endpoints: Register, Manage, History)
- SMTP Integration (PHPMailer)
- HMAC Security für Webhooks

**Testing:**
- ✅ Mercury IMAP (localhost)
- ✅ Production IMAP (webhoster.ag)
- ✅ ~7,000 lines of code (4,200 production + 2,800 tests)
- ✅ 27 API Endpoints tested

**Next:** M3 - MVP UI (In Progress)

---

## 📚 Dokumentation

**🆕 Neu bei CI-Inbox?** Starten Sie mit dem [Getting Started Guide](docs/GETTING-STARTED.md)!

Die vollständige Entwicklerdokumentation finden Sie in `docs/dev/`:

| Dokument | Beschreibung |
|----------|--------------|
| [`GETTING-STARTED.md`](docs/GETTING-STARTED.md) | **Start here!** Schnellstart-Anleitung (5 Min.) |
| [`vision.md`](docs/dev/vision.md) | Projektziele, Workflows (A/B/C), Anwendungsfälle |
| [`inventar.md`](docs/dev/inventar.md) | Feature-Liste mit Prioritäten (MUST/SHOULD/COULD) |
| [`roadmap.md`](docs/dev/roadmap.md) | Entwicklungs-Timeline (M0-M5, 16 Wochen) |
| [`architecture.md`](docs/dev/architecture.md) | Technische Architektur, Datenmodell, Sicherheit |
| [`codebase.md`](docs/dev/codebase.md) | Entwicklungsumgebung, Code-Konventionen, Testing |
| [`PROJECT-STATUS.md`](docs/dev/PROJECT-STATUS.md) | Aktueller Projektstatus |

---

## 🚀 Quick Start

### Voraussetzungen
- PHP 8.1+
- Composer 2.5+
- MySQL 8.0+ / MariaDB 10.6+
- Apache 2.4+ / Nginx 1.18+

### Installation

```bash
# 1. Repository klonen
git clone https://github.com/hndrk-fegko/CI-Inbox.git ci-inbox
cd ci-inbox

# 2. Dependencies installieren
composer install

# 3. Environment konfigurieren
cp .env.example .env
# Bearbeiten Sie .env mit Ihren Datenbank-Credentials
# Generieren Sie einen Encryption-Key: php -r "echo bin2hex(random_bytes(32));"

# 4. Datenbank einrichten
mysql -u root -p -e "CREATE DATABASE ci_inbox CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php database/migrate.php

# 5. Development-Server starten
php -S localhost:8080 -t src/public
```

**Oder mit XAMPP/Apache:**
- DocumentRoot auf `src/public/` zeigen lassen
- Virtual Host einrichten (z.B. `http://myproject.local`)
  - vHost-Datei erstellen
  - Domain in Windows `hosts`-Datei eintragen

**📖 Detaillierte Installationsanleitung:** Siehe [`docs/dev/codebase.md`](docs/dev/codebase.md) → Abschnitt 2

---

## 🏗️ Technologie-Stack

**Backend:**
- **PHP 8.1+** mit modernen Features (Property Promotion, Enums, Union Types)
- **Slim Framework 4** - Leichtgewichtiges HTTP-Framework
- **Eloquent ORM** (Standalone) - Datenbankabstraktion ohne Laravel
- **Monolog** - PSR-3 konformes Logging
- **PHP-DI** - Dependency Injection Container

**Frontend:**
- **Vanilla JavaScript (ES6+)** - Kein Framework-Lock-in
- **Bootstrap 5** - Responsives UI-Framework
- **Quill.js** - Rich-Text-Editor (geplant)

**Datenbank:**
- **MySQL 8.0+** / **MariaDB 10.6+** mit utf8mb4

**Sicherheit:**
- **AES-256-CBC** - Verschlüsselung sensibler Daten
- **HTML Purifier** - XSS-Schutz
- **Prepared Statements** - SQL Injection Prevention

**Deployment:**
- Shared Hosting kompatibel (kein Node.js/Build-Step erforderlich)
- `.htaccess` Support für Apache
- Nginx-Konfiguration verfügbar

**Begründung der Technologie-Wahl:** Siehe [`docs/dev/architecture.md`](docs/dev/architecture.md) → Abschnitt 1.3

---

## 📦 Projektstruktur

```
ci-inbox/
├── src/
│   ├── core/              # Kern-System (Application, Container)
│   ├── modules/           # Wiederverwendbare Module (logger, imap, etc.)
│   ├── app/               # Anwendungs-Code (Controllers, Services, Repositories)
│   ├── public/            # Web-Root (DocumentRoot hier setzen!)
│   ├── views/             # Templates
│   └── config/            # Konfigurationsdateien
├── docs/
│   ├── dev/               # Entwickler-Dokumentation
│   ├── admin/             # Admin/Deployment-Docs
│   └── user/              # User-Guides (später)
├── tests/                 # Test-Suite (Unit, Integration, E2E)
├── data/                  # Runtime-Daten (nicht im Git)
├── logs/                  # Log-Dateien (nicht im Git)
├── scripts/               # CLI-Skripte (Setup, Cron)
├── .env.example           # Environment-Template
└── composer.json          # PHP-Dependencies
```

---

## 🧪 Testing

```bash
# Standalone Modul-Tests
php src/modules/imap/tests/mercury-quick-test.php
php src/modules/imap/tests/setup-autodiscover.php

# Unit Tests (planned M5)
./vendor/bin/phpunit tests/unit/

# Integration Tests (planned M5)
./vendor/bin/phpunit tests/integration/
```

**Testing-Strategie:** Siehe [`docs/dev/codebase.md`](docs/dev/codebase.md) → Abschnitt 10.2

**Detaillierter Entwicklungs-Status:** Siehe [`docs/dev/roadmap.md`](docs/dev/roadmap.md)

---

## 🔒 Sicherheit

CI-Inbox implementiert mehrere Sicherheitsebenen:

- **Verschlüsselung:** AES-256-CBC für sensible Daten (IMAP-Passwörter)
- **XSS-Schutz:** HTML Purifier für E-Mail-Content
- **CSRF-Schutz:** Token-basierte Absicherung (geplant M3)
- **SQL Injection:** Eloquent ORM mit Prepared Statements
- **Session-Security:** Sichere Session-Verwaltung (geplant M3)
- **Webhook-Security:** HMAC-SHA256 Signaturverifizierung

**Sicherheitslücke melden:**  
Bitte senden Sie Sicherheitsprobleme vertraulich an [hendrik.dreis@feg-koblenz.de](mailto:hendrik.dreis@feg-koblenz.de).

Mehr Details: [`docs/dev/architecture.md`](docs/dev/architecture.md) → Abschnitt 5 (Security)

---

## 🤝 Contributing

Contributions sind willkommen! Siehe [CONTRIBUTING.md](CONTRIBUTING.md) für ausführliche Guidelines.

**Kurzanleitung:**
1. Repository forken
2. Feature-Branch erstellen (`git checkout -b feature/mein-feature`)
3. Änderungen committen (`git commit -m 'feat(scope): Beschreibung'`)
4. Branch pushen (`git push origin feature/mein-feature`)
5. Pull Request erstellen

**Code-Standards:**  
- PSR-12 Coding Style
- Strict Types in allen PHP-Dateien
- Vollständiges Logging aller Operationen
- Layer-Abstraktion (Service → Repository Pattern)
- Klare, strukturierte Dateien mit verständlichen Änderungen
- **KI-Einsatz erlaubt** - Projekt wurde mit KI-Unterstützung entwickelt

Weitere Details: [`docs/dev/codebase.md`](docs/dev/codebase.md) → Abschnitt 4

---

## 📝 Lizenz

MIT License - Siehe [LICENSE](LICENSE) für Details.

Copyright (c) 2025 Hendrik Dreis

---

## 👥 Team & Entwicklung

**Hauptentwickler:**  
Hendrik Dreis ([hendrik.dreis@feg-koblenz.de](mailto:hendrik.dreis@feg-koblenz.de))

**KI-Unterstützung:**  
GitHub Copilot

---

## 📦 Open Source Dependencies

CI-Inbox nutzt folgende Open-Source-Bibliotheken:

| Bibliothek | Lizenz | Verwendung |
|------------|--------|------------|
| Slim Framework | MIT | HTTP Routing & Middleware |
| Eloquent ORM | MIT | Datenbank-Abstraktionsschicht |
| Monolog | MIT | Logging (PSR-3) |
| PHP-DI | MIT | Dependency Injection Container |
| PHPMailer | LGPL 2.1+ | E-Mail-Versand (SMTP) |
| HTML Purifier | LGPL 2.1+ | XSS-Schutz |
| phpdotenv | BSD-3-Clause | Umgebungsvariablen |
| Sabre/DAV | BSD-3-Clause | WebDAV/CardDAV (geplant) |
| Bootstrap 5 | MIT | Frontend-Framework (via CDN) |

Alle Lizenzen sind MIT-kompatibel.

---

## 📧 Kontakt & Support

**Fragen oder Probleme?**
- 📖 Konsultieren Sie die [Dokumentation](docs/dev/)
- 🐛 Öffnen Sie ein [Issue](https://github.com/hndrk-fegko/CI-Inbox/issues)
- 💬 Kontakt: [hendrik.dreis@feg-koblenz.de](mailto:hendrik.dreis@feg-koblenz.de)

---

**Hinweis:** Dieses Projekt folgt den Prinzipien aus `basics.txt` (Layer-Abstraktion, Logging-First, Modulare Architektur).

# CI-Inbox: Collaborative IMAP Inbox Management

**Leichtgewichtige IMAP-Inbox-Verwaltung für kleine autonome Teams (3-7 Personen).**

[![Status](https://img.shields.io/badge/Status-M2%20Complete-brightgreen)](https://github.com/your-repo)
[![PHP](https://img.shields.io/badge/PHP-8.1%2B-blue)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)

---

## 🎯 Projektziel

CI-Inbox löst das Problem chaotischer Shared-Inbox-Verwaltung für kleine Teams ohne Budget/Bedarf für komplexe Ticketing-Systeme. Entwickelt für autonome Teams mit flexiblen Arbeitszeiten (z.B. Vereine, Kirchengemeinden, kleine NGOs).

**Kernfunktionen:**
- 📧 Thread-basierte E-Mail-Gruppierung (✅ M1)
- 🏷️ Label-System für Organisation (✅ M1)
- 🔍 IMAP Keywords für Performance (✅ M1 Bonus)
- 🔄 Automatisches E-Mail-Polling (✅ M1.5 - Webcron)
- 🎯 Thread Management API (✅ M2.1 - 10 Endpoints)
- 📧 Email Send API (✅ M2.2 - SMTP Integration)
- 🔗 Webhook Integration (✅ M2.3 - External Systems)
- 📝 Interne Notizen für Kontext-Weitergabe (⏳ M3)
- 🔄 Persönliche IMAP-Übernahme für sensible Themen (⏳ M4)
- 📊 100% Nachvollziehbarkeit (Activity-Log) (⏳ M4)

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

Alle Entwickler-Dokumentation findest du in `docs/dev/`:

| Dokument | Beschreibung |
|----------|--------------|
| [`vision.md`](docs/dev/vision.md) | Projektziele, Workflows (A/B/C), Use Cases |
| [`inventar.md`](docs/dev/inventar.md) | Feature-Liste mit Prioritäten (MUST/SHOULD/COULD) |
| [`roadmap.md`](docs/dev/roadmap.md) | Entwicklungs-Timeline (M0-M5, 16 Wochen) |
| [`architecture.md`](docs/dev/architecture.md) | Technische Architektur, Datenmodell, Security |
| [`codebase.md`](docs/dev/codebase.md) | Entwicklungsumgebung, Code-Konventionen, Testing |
| [`workflow.md`](docs/dev/workflow.md) | 5-Phasen-Entwicklungsprozess |

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
git clone <repository-url> ci-inbox
cd ci-inbox

# 2. Dependencies installieren
composer install

# 3. Environment konfigurieren
cp .env.example .env
# Bearbeite .env: DB-Credentials, Encryption-Key setzen

# 4. Datenbank einrichten
mysql -u root -p -e "CREATE DATABASE ci_inbox CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php scripts/setup-database.php

# 5. Development-Server starten
php -S localhost:8080 -t src/public
```

**Oder mit XAMPP:**
- vHost auf `src/public` zeigen lassen
- URL: `http://ci-inbox.local`

**Detaillierte Anleitung:** Siehe [`docs/dev/codebase.md`](docs/dev/codebase.md) → Abschnitt 2

---

## 🏗️ Technologie-Stack

- **Backend:** PHP 8.1+, Slim Framework 4, Eloquent ORM (standalone)
- **Frontend:** Vanilla JS (ES6+), Bootstrap 5, Quill.js
- **Datenbank:** MySQL 8.0 / MariaDB 10.6
- **Security:** AES-256-CBC, HTML Purifier, CSRF-Tokens
- **Logging:** Monolog (PSR-3)
- **Deployment:** Shared Hosting kompatibel

**Begründung:** Siehe [`docs/dev/architecture.md`](docs/dev/architecture.md) → Abschnitt 1.3

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

## 🤝 Contributing

Aktuell ist das Projekt in der Planungs-/Foundation-Phase. Contributions sind willkommen ab M3 (MVP UI).

**Workflow:**
1. Fork das Repository
2. Feature-Branch erstellen (`git checkout -b feature/my-feature`)
3. Committen (`git commit -m 'feat(scope): Add feature'`)
4. Push (`git push origin feature/my-feature`)
5. Pull Request erstellen

**Code-Standards:** PSR-12, siehe [`docs/dev/codebase.md`](docs/dev/codebase.md) → Abschnitt 4

---

## 📝 Lizenz

[MIT License](LICENSE) - Details folgen

---

## 👥 Team

- **Entwickler:** [Dein Name]
- **KI-Unterstützung:** GitHub Copilot

---

## 📧 Kontakt

Fragen? Siehe [`docs/dev/workflow.md`](docs/dev/workflow.md) oder öffne ein Issue.

---

**Hinweis:** Dieses Projekt folgt den Prinzipien aus `basics.txt` (Layer-Abstraktion, Logging-First, Modulare Architektur).

# Developer Documentation Index

Willkommen zur C-IMAP Entwickler-Dokumentation! 📚

## 📋 Table of Contents

### 🎯 Getting Started
- [Vision & Roadmap](vision.md) - Projekt-Vision und Ziele
- [Architecture](architecture.md) - System-Architektur
- [Codebase Overview](codebase.md) - Code-Organisation
- [Project Status](PROJECT-STATUS.md) - Aktueller Stand

### 🚀 API Documentation
- **[REST API Reference](api.md)** - Vollständige API-Dokumentation
  - Thread Management API
  - Email API (Send, Reply, Forward)
  - Personal IMAP Accounts API
  - User Management API
  - Label Management API
  - Bulk Operations API
  - Webhook API

### 📦 Features
- [Personal IMAP Accounts](PERSONAL-IMAP-ACCOUNTS.md) - User's persönliche Email-Accounts
- [Inventar](inventar.md) - Feature-Übersicht

### 🔧 Module Documentation
- **[Module Index](../modules/README.md)** - Alle Module
  - [Webcron Module](../modules/webcron/README.md) - Email-Polling
  - IMAP Module *(TODO)*
  - SMTP Module *(TODO)*
  - Encryption Module *(TODO)*
  - Logger Module *(TODO)*

### 📝 Sprint Documentation
- [M0-Sprint-0.1: Logger](M0-Sprint-0.1-Logger-Modul.md) ✅
- [M0-Sprint-0.2: Config](M0-Sprint-0.2-Config-Modul.md) ✅
- [M0-Sprint-0.3: Encryption](M0-Sprint-0.3-Encryption-Service.md) ✅
- [M0-Sprint-0.4: Database](M0-Sprint-0.4-Database-Setup.md) ✅
- [M0-Sprint-0.5: Core Infrastructure](M0-Sprint-0.5-Core-Infrastruktur.md) ✅
- [M1-Sprint-1.1: IMAP Client](M1-Sprint-1.1-IMAP-Client-Modul.md) ✅
- [M1-Sprint-1.2: Email Parser](M1-Sprint-1.2-Email-Parser.md) ✅
- [M1-Sprint-1.3: Thread Manager](M1-Sprint-1.3-Thread-Manager.md) ✅
- [M1-Sprint-1.4: Label Manager](M1-Sprint-1.4-Label-Manager.md) ✅
- [M1-Sprint-1.5: Webcron Polling](M1-Sprint-1.5-Webcron-Polling-Dienst.md) ✅
- [M2-Sprint-2.1: Thread API](M2-Sprint-2.1-Thread-Management-API.md) ✅
- [M2-Sprint-2.2: Email Send API](M2-Sprint-2.2-Email-Send-API.md) ✅
- [M2-Sprint-2.3: Webhook Integration](M2-Sprint-2.3-Webhook-Integration.md) ✅
- [M3: MVP UI](M3-MVP-UI.md) 🚧

### 🛠️ Setup Guides
- [Mercury Setup](Mercury-Setup.md) - PHP Cronjob Manager
- [Autodiscover Setup](Setup-Autodiscover.md) - Email-Client Konfiguration
- [M1 Preparation](M1-Preparation.md) - Meilenstein 1 Vorbereitung

### 🗂️ Archive
- [Archive](archive/) - Alte Dokumentation

---

## 📖 Documentation Structure

### `/docs/dev/` - Core Documentation
**Zielgruppe:** API-Consumer, Feature-User, Projekt-Manager

**Inhalte:**
- ✅ REST API Referenz
- ✅ Feature-Dokumentation (z.B. Personal IMAP Accounts)
- ✅ Architektur-Übersicht
- ✅ Roadmap & Sprint-Planung
- ✅ Setup-Anleitungen

**Beispiel:** "Wie benutze ich die Personal IMAP Account API?"

---

### `/docs/modules/` - Module Documentation
**Zielgruppe:** Entwickler, Contributors

**Inhalte:**
- ✅ Modul-Implementierung (technische Details)
- ✅ Interne APIs (Klassen, Methoden)
- ✅ Konfiguration (Environment Variables)
- ✅ Testing (Unit Tests, Integration Tests)
- ✅ Troubleshooting

**Beispiel:** "Wie funktioniert der WebcronManager intern?"

---

## 🔗 Quick Links

| Thema | Dokument |
|-------|----------|
| **API nutzen** | [api.md](api.md) |
| **Modul entwickeln** | [../modules/README.md](../modules/README.md) |
| **Architecture verstehen** | [architecture.md](architecture.md) |
| **Personal IMAP Accounts** | [PERSONAL-IMAP-ACCOUNTS.md](PERSONAL-IMAP-ACCOUNTS.md) |
| **Webcron/Webhook** | [../modules/webcron/README.md](../modules/webcron/README.md) |
| **Sprint-Übersicht** | [roadmap.md](roadmap.md) |

---

## 📊 Project Status

**Version:** 0.1.0 (MVP Phase)  
**Last Updated:** 18. November 2025

### ✅ Completed Milestones
- ✅ M0: Core Infrastructure (Logger, Config, Encryption, Database)
- ✅ M1: IMAP Core (Client, Parser, Thread Manager, Webcron)
- ✅ M2: REST API (Thread API, Email API, Webhook API, User API, Label API, Bulk Ops)

### 🚧 In Progress
- 🚧 M3: MVP UI (Thread-Liste, Detail-View, Email-Send)

### 📋 Next Steps
- [ ] API Authentication (JWT)
- [ ] Personal IMAP Folder-Liste API
- [ ] Personal IMAP Transfer API (Workflow C)
- [ ] Advanced Search & Filtering

---

## 🤝 Contributing

**Workflow:**
1. Lies relevante Doku (`docs/dev/` oder `docs/modules/`)
2. Check `PROJECT-STATUS.md` für offene Tasks
3. Erstelle Feature-Branch
4. Schreibe Tests
5. Update Dokumentation
6. Pull Request

**Dokumentations-Standards:**
- Markdown-Format
- Code-Beispiele mit Syntax-Highlighting
- Screenshots/Diagramme wo sinnvoll
- TODO-Marker für fehlende Doku

---

## 📞 Support

Bei Fragen oder Problemen:
1. Check [api.md](api.md) für API-Fragen
2. Check [../modules/README.md](../modules/README.md) für Modul-Fragen
3. Check Sprint-Docs für Feature-Details
4. Erstelle Issue im Repository

---

**Happy Coding! 🚀**

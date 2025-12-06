# Feature-Inventar: Collaborative IMAP Inbox (CI-Inbox)

**Letzte Aktualisierung:** 6. Dezember 2025  
**Autor:** Hendrik Dreis ([hendrik.dreis@feg-koblenz.de](mailto:hendrik.dreis@feg-koblenz.de))  
**Lizenz:** MIT License  
**Basis:** `vision.md` - Workflows A, B, C

Dies ist eine vollständige, priorisierte Auflistung aller Features für die CI-Inbox unter Berücksichtigung der Zielgruppe (3-7 Nutzer, kleine Teams) und Technologie (PHP/JavaScript, Webcron, Shared Hosting).

---

## Prioritäts-Legende

- **MUST** = Minimum Viable Product (MVP) - Ohne geht nichts
- **SHOULD** = Version 1.0 - Wichtig für vollständige Funktionalität
- **COULD** = Version 2.0+ - Nice-to-have, spätere Erweiterung
- **WON'T** = Explizit ausgeschlossen (Out of Scope, siehe `vision.md`)

---

## I. Kernfunktionalität & Business-Logik

| ID | Feature | Beschreibung | Priorität | Abhängigkeiten | Workflows |
|----|---------|--------------|-----------|----------------|-----------|
| **1.1** | **Thread-Management** | Automatische Gruppierung eingehender E-Mails zu bestehenden Threads (Message-ID, In-Reply-To, Betreff-Matching). Speicherung der Thread-Historie und -Struktur. | **MUST** | 2.3, 2.5 | A, B, C |
| **1.2** | **Zuweisungslogik** | Funktion zur Zuweisung eines Threads an bestimmten User (oder sich selbst). Status-Änderung von "Neu" → "Assigned/In Progress". Mehrfachzuweisung möglich (keine Race Conditions). | **MUST** | 1.1, 1.4 | A, B |
| **1.3** | **Internes Notizsystem** | Möglichkeit, interne, nicht-öffentliche Notizen zu jedem Thread hinzuzufügen. Notizhistorie mit Zeitstempel und Verfasser anzeigen. | **MUST** | 1.1 | B |
| **1.4** | **Status-Management** | Definition und Verwaltung von Thread-Status: `new`, `assigned`, `in_progress`, `done`, `transferred`, `archived`. UI-Elemente zur Status-Änderung. | **MUST** | - | A, B, C |
| **1.5** | **Archivierung** | Automatische/Manuelle Archivierung von erledigten Threads. Optional: IMAP-Verschiebung in Archiv-Ordner (Fallback-System). | **SHOULD** | 1.4, 2.7 | A, B |

---

## II. IMAP & Datenhandling

| ID | Feature | Beschreibung | Priorität | Abhängigkeiten | Workflows |
|----|---------|--------------|-----------|----------------|-----------|
| **2.1** | **Primäre IMAP-Verbindung** | Verbindung zur gemeinsamen Inbox (info@) zum Lesen und Verschieben von Mails. Konfiguration: Host, Port, SSL/TLS, Login. | **MUST** | - | A, B, C |
| **2.2** | **Sekundäre IMAP-Verbindung** | Verbindung zu persönlichen IMAP/SMTP-Konten der Nutzer (für Workflow C: Transfer). Gesicherte Speicherung der Zugangsdaten (verschlüsselt). | **SHOULD** | 5.1 | C |
| **2.3** | **Webcron-Polling-Dienst** | PHP-Skript zum zeitgesteuerten Abrufen neuer E-Mails aus Haupt-Inbox via externem Webcron (z.B. cronjob.de). Interval: 5-15 Minuten. Webhook-Authentifizierung (Secret Token). Vermeidung doppelter Verarbeitung (IMAP UID Tracking). | **MUST** | 2.1, 2.5 | A, B, C |
| **2.4** | **IMAP-Ordner-Synchronisation** | Laden der Ordnerstruktur der Haupt-Inbox. Optional: Admins können Ordner für Status-Fallback anlegen (z.B. "In Bearbeitung"). | **COULD** | 2.1 | - |
| **2.5** | **E-Mail-Parsen** | Robustes Parsen von E-Mails: Text- und HTML-Body, Betreff, Absender, Empfänger, Anhänge, Message-ID, In-Reply-To. Anzeige in UI mit XSS-Schutz (HTML Purifier). | **MUST** | - | A, B, C |
| **2.6** | **E-Mail-Senden (über info@)** | Senden von Antworten über gemeinsames SMTP-Konto (info@). Korrekte Thread-Referenzierung (In-Reply-To, References Header). | **MUST** | 2.1 | A |
| **2.7** | **IMAP-Status-Fallback** | Optional: Physisches Verschieben der E-Mail innerhalb der Haupt-Inbox basierend auf Status (z.B. → Ordner "In Bearbeitung"). Redundantes Status-Tracking auf IMAP-Server. | **COULD** | 2.1, 1.4 | - |
| **2.8** | **Anhang-Handling** | Speichern von E-Mail-Anhängen (im Dateisystem oder DB). Bereitstellung zum Download. Sicherheitsprüfung (MIME-Type, Größe, Virus-Scan optional). | **SHOULD** | 2.5 | A, B, C |
| **2.9** | **E-Mail-Transfer zu persönlichem IMAP** | Verschieben der Original-Mail ins persönliche IMAP-Postfach des Users (Workflow C2). Sent-Mail-Handling (Kopie im persönlichen Sent-Ordner). | **SHOULD** | 2.2, 5.1 | C |

---

## III. Benutzer- & Rechteverwaltung

| ID | Feature | Beschreibung | Priorität | Abhängigkeiten | Workflows |
|----|---------|--------------|-----------|----------------|-----------|
| **3.1** | **Authentifizierung** | Login-Funktion für Nutzer. Passwort-Hashing (bcrypt/Argon2), Session-Management (HttpOnly, Secure Cookies). Logout-Funktion. | **MUST** | - | A, B, C |
| **3.2** | **User-Rollen** | Zwei Rollen: `user` (Standard) und `admin` (erweiterte Rechte). Admins können System-Config bearbeiten (3.4). | **SHOULD** | 3.1 | - |
| **3.3** | **IMAP-Konto-Registrierung** | Nutzer können ihre persönlichen IMAP/SMTP-Zugangsdaten hinterlegen (für Workflow C). Test-Funktion für Verbindung. Verschlüsselte Speicherung. | **SHOULD** | 3.1, 5.1 | C |
| **3.4** | **Admin-Postfach-Konfiguration** | Admin-Dashboard zur Konfiguration der Haupt-Inbox (info@): Host, Port, Login, SSL/TLS. Webcron-Secret-Token-Verwaltung. | **MUST** | 3.2 | A, B, C |
| **3.5** | **Zwei-Faktor-Auth (2FA)** | Optionale 2FA für Admin-Konten (TOTP). Integration einer 2FA-Bibliothek. | **COULD** | 3.1, 3.2 | - |

---

## IV. Benutzeroberfläche (UI) & Interaktion

| ID | Feature | Beschreibung | Priorität | Abhängigkeiten | Workflows |
|----|---------|--------------|-----------|----------------|-----------|
| **4.1** | **Posteingangs-Übersicht** | Hauptansicht mit allen offenen Threads. Anzeige: Betreff, Absender, Status, Zugewiesener User, Letzte Aktivität. Filter: Status, Zuweisung. Sortierung: Datum, Status. | **MUST** | 1.1, 1.4 | A, B, C |
| **4.2** | **Thread-Detailansicht** | Vollständige Ansicht eines Threads: Alle E-Mails chronologisch, interne Notizen, Anhänge. Trennung zwischen öffentlichen Mails und internen Notizen. | **MUST** | 1.1, 1.3, 2.5 | A, B, C |
| **4.3** | **Aktions-Panel** | UI-Elemente für Kernaktionen: "Mir zuweisen", "Anderem User zuweisen", "Status ändern", "Notiz hinzufügen", "Antworten", "Transferieren" (Workflow C). Responsive Design. | **MUST** | 1.2, 1.3, 1.4 | A, B, C |
| **4.4** | **UI-Polling & Benachrichtigungen** | JavaScript-basiertes Polling (alle 15 Sekunden) zur Anzeige neuer Threads und Status-Änderungen. Visuelle Benachrichtigung bei neuen unzugewiesenen Mails. | **MUST** | 2.3 | A, B, C |
| **4.5** | **Antwort-Formular** | Formular zum Verfassen von Antworten. Rich-Text-Editor (Quill.js) mit Zitier-Funktion. Auswahl: "Von info@ antworten" (A) oder "Von meinem Account" (C). | **MUST** | 2.6, (2.9) | A, (C) |
| **4.6** | **Antwort-Vorlagen** | Optional: Speichern und Verwenden von Standard-Antwort-Vorlagen. | **COULD** | 4.5 | - |
| **4.7** | **Mobile-Optimierung** | Responsive Design für Smartphone-Nutzung (autonome Teams arbeiten flexibel). | **SHOULD** | 4.1, 4.2, 4.3 | A, B, C |

---

## V. System & Sicherheit

| ID | Feature | Beschreibung | Priorität | Abhängigkeiten | Workflows |
|----|---------|--------------|-----------|----------------|-----------|
| **5.1** | **Datenverschlüsselung** | Verschlüsselung aller IMAP/SMTP-Passwörter in der Datenbank (AES-256-CBC). Encryption-Key in `.env` (außerhalb Git). Key-Rotation-Strategie dokumentieren. | **MUST** | - | A, B, C |
| **5.2** | **Webcron-Einrichtung & Authentifizierung** | Dokumentation für Setup mit cronjob.de / cron-job.org. Webhook-Authentifizierung via Secret Token (verhindert unbefugte Aufrufe). Fehlerprotokollierung. | **MUST** | 2.3 | A, B, C |
| **5.3** | **Error-Handling** | Robustes Fehler-Handling: IMAP-Timeouts, fehlerhafte Logins, Datenbank-Fehler. Benutzerfreundliche Fehlermeldungen in UI. Logging aller Fehler (siehe Logger-Modul). | **MUST** | Logger-Modul | A, B, C |
| **5.4** | **Code-Sicherheit** | Prävention von: XSS (HTML Purifier für E-Mails), CSRF (Token-basiert), SQL-Injection (Eloquent ORM), Session-Hijacking (Secure Cookies). Sanitization aller Nutzereingaben. | **MUST** | - | A, B, C |
| **5.5** | **Umgebungskonfiguration** | Management via `.env`-Datei: Datenbank-Zugriff, Encryption-Key, Webcron-Secret, Debug-Mode. `.env` nicht im Git, `.env.example` als Template. | **MUST** | - | A, B, C |
| **5.6** | **Rate-Limiting** | Schutz vor Brute-Force: Max 5 Login-Versuche pro 15 Minuten. Optional: API-Rate-Limiting (falls REST-API später). | **SHOULD** | 3.1 | - |
| **5.7** | **Backup-Strategie** | Dokumentation für Datenbank-Backups und Encryption-Key-Backup. Empfehlung: Tägliches Backup via Hoster-Tools. | **SHOULD** | - | - |

---

## VI. Logging & Monitoring (aus basics.txt)

| ID | Feature | Beschreibung | Priorität | Abhängigkeiten | Workflows |
|----|---------|--------------|-----------|----------------|-----------|
| **6.1** | **Zentrales Logging-System** | Logger-Modul (Monolog-basiert) mit Log-Leveln: DEBUG, INFO, WARNING, ERROR, EXCEPTION, SUCCESS, FAILURE, ANNOUNCEMENT. Pflichtfelder: timestamp, level, module, file, line, function, message, context. Handler: File, Database. | **MUST** | - | A, B, C |
| **6.2** | **Activity-Log (Audit-Trail)** | Protokollierung aller kritischen Aktionen: Thread-Zuweisung, Status-Änderung, Notiz hinzugefügt, E-Mail gesendet, Transfer zu persönlichem Account. Für Nachvollziehbarkeit (Erfolgskriterium aus vision.md). | **MUST** | 6.1, 1.x | A, B, C |
| **6.3** | **Webcron-Monitoring** | Überwachung des Webcron-Dienstes: Letzte erfolgreiche Ausführung, Fehler-Log. Warnung bei fehlgeschlagenen Polls (> 30 Min). | **SHOULD** | 2.3, 6.1 | A, B, C |

---

## VII. Dokumentation & Deployment

| ID | Feature | Beschreibung | Priorität | Abhängigkeiten | Workflows |
|----|---------|--------------|-----------|----------------|-----------|
| **7.1** | **Setup-Wizard** | Geführte Erstinstallation mit Auto-Discovery von SMTP/IMAP-Einstellungen. Interaktive Konfiguration, Test-Mail-Versand, Folder-Scanning. | **MUST** | 2.1, 2.3, 5.5 | - |
| **7.2** | **Auto-Discovery SMTP/IMAP** | Automatische Erkennung von Mail-Server-Einstellungen aus Email-Domain. Test verschiedener Port/SSL-Kombinationen. Intelligente Fallbacks. | **MUST** | 7.1 | - |
| **7.3** | **Folder-Scanner** | Scannt alle IMAP-Ordner nach Test-Mail (Filter-Kompatibilität). Automatische Erkennung des Standard-INBOX-Ordners. | **SHOULD** | 7.1, 2.1 | - |
| **7.4** | **Installation-Guide** | Schritt-für-Schritt-Anleitung für Deployment auf Shared-Hosting, VPS, Docker. System-Requirements, Troubleshooting. | **MUST** | - | - |
| **7.5** | **Administrator-Handbuch** | Wartung, Updates, Backup-Strategien, User-Management, Monitoring. | **SHOULD** | - | - |
| **7.6** | **User-Dokumentation** | Bedienungsanleitung für End-User: Workflows, Features, FAQ. | **SHOULD** | - | - |
| **7.7** | **API-Dokumentation** | Vollständige API-Referenz (falls REST-API implementiert). OpenAPI/Swagger. | **COULD** | - | - |

---

## Implementierungs-Status (17. November 2025)

### ✅ Completed Features

| Feature-ID | Status | Datei/Modul | Bemerkungen |
|------------|--------|-------------|-------------|
| **6.1** | ✅ DONE | `src/modules/logger/` | PSR-3, JSON, Rotation, 8 Log-Level |
| **Config** | ✅ DONE | `src/modules/config/` | ENV + PHP, Type-Safe, Dot-notation |
| **5.1** | ✅ DONE | `src/modules/encryption/` | AES-256-CBC, Random IV, Base64 |
| **Database** | ✅ DONE | `database/migrations/` | 7 Tabellen, Eloquent Models |
| **Core** | ✅ DONE | `src/core/` | DI Container, Hook Manager, ModuleLoader |
| **2.1** | ✅ DONE | `src/modules/imap/` | ImapClient, 14 Operationen, Interface-First |
| **7.1** | ✅ DONE | `tests/setup-autodiscover.php` | Setup-Wizard mit Auto-Discovery |
| **7.2** | ✅ DONE | `tests/setup-autodiscover.php` | 8 SMTP-Configs, Auto-Detection |
| **7.3** | ✅ DONE | `tests/setup-autodiscover.php` | Folder-Scanning, Filter-kompatibel |

### 🔄 In Progress

| Feature-ID | Status | Sprint | Geschätzt |
|------------|--------|--------|-----------|
| **2.5** | 🔴 TODO | M1 Sprint 1.2 | 2 Tage |
| **1.1** | 🔴 TODO | M1 Sprint 1.3 | 2 Tage |
| **2.3** | 🔴 TODO | M1 Sprint 1.4 | 2 Tage |

---

## VIII. Ausgeschlossene Features (WON'T)
| **7.1** | **Entwickler-Dokumentation** | `docs/dev/`: vision.md ✅, workflow.md ✅, inventar.md ✅, roadmap.md, architecture.md, codebase.md, api.md, changelog.md | **MUST** | - | - |
| **7.2** | **Admin-Dokumentation** | `docs/admin/deployment.md`: Installation auf Shared Hosting, Webcron-Setup, .env-Konfiguration, Datenbank-Setup, Troubleshooting. | **MUST** | - | - |
| **7.3** | **User-Dokumentation** | `docs/user/user-guide.md`: Bedienungsanleitung mit Screenshots, Use Cases, FAQ. | **SHOULD** | - | - |
| **7.4** | **Setup-Skripte** | `scripts/setup-database.php`: Datenbank-Initialisierung, Migrations. `scripts/test-imap.php`: IMAP-Verbindung testen. | **SHOULD** | - | - |

---

## Zusammenfassung: Feature-Verteilung

### **MVP (MUST) - 22 Features**
Minimale funktionsfähige Version für Workflow A & B (+ Grundlage für C):
- Kern: 1.1, 1.2, 1.3, 1.4
- IMAP: 2.1, 2.3, 2.5, 2.6
- User: 3.1, 3.4
- UI: 4.1, 4.2, 4.3, 4.4, 4.5
- Sicherheit: 5.1, 5.2, 5.3, 5.4, 5.5
- Logging: 6.1, 6.2
- Docs: 7.1, 7.2

**Geschätzte Entwicklungszeit:** 8 Wochen

---

### **Version 1.0 (SHOULD) - +10 Features**
Vollständige Funktionalität inkl. Workflow C:
- Kern: 1.5
- IMAP: 2.2, 2.8, 2.9
- User: 3.2, 3.3
- UI: 4.7
- Sicherheit: 5.6, 5.7
- Monitoring: 6.3
- Docs: 7.3, 7.4

**Zusätzliche Zeit:** +4 Wochen  
**Gesamt MVP → v1.0:** 12 Wochen

---

### **Version 2.0+ (COULD) - 4 Features**
Erweiterungen & Optimierungen:
- IMAP: 2.4, 2.7
- User: 3.5
- UI: 4.6

**Timeline:** Post-1.0 (nach Bedarf)

---

### **Out of Scope (WON'T)**
Siehe `vision.md` → "Was ist die CI-Inbox NICHT?":
- Vollwertiges Ticketsystem
- CRM-Funktionen
- Projektmanagement
- Echtzeit-Chat/Video
- KI-Features
- Multi-Team-Support (vorerst)

---

## Nächste Schritte

1. ✅ Feature-Inventar priorisiert
2. 🔴 Roadmap erstellen (`roadmap.md`) - Feature-IDs zu Milestones mappen
3. 🔴 Architecture-Design (`architecture.md`) - Datenmodell mit Status-Werten aus 1.4
4. 🔴 MVP-Features in kleinere Tasks unterteilen (WIP-Dokumente)

---

**Ende des Feature-Inventars**

*Dieses Dokument wird bei Architektur-Entscheidungen und während der Implementierung aktualisiert.*

# Vision Statement: Collaborative IMAP Inbox (CI-Inbox)

**Letzte Aktualisierung:** 17. November 2025

---

## Problem: Warum brauchen wir die CI-Inbox?

### Die Ausgangssituation
Wir sind ein kleines, autonomes Team mit flexiblen Arbeitszeitmodellen. Jedes Teammitglied arbeitet weitestgehend selbstständig an eigenen Aufgaben. Die primäre Kommunikation läuft über persönliche IMAP-Postfächer.

**Aber:** Wir betreuen gemeinsam einen zentralen Posteingang (z.B. `info@`).

### Das Problem mit klassischen Mail-Clients
Jeder könnte sich die `info@` in sein E-Mail-Programm einbinden – **aber dann**:
- ❌ **Unklarheit:** Wer beantwortet welche E-Mail?
- ❌ **Doppelarbeit:** Mehrere Personen könnten dieselbe Mail bearbeiten
- ❌ **Keine Historie:** Was wurde bereits beantwortet? Von wem?
- ❌ **Keine Notizen:** Kann ich Infos für Kollegen hinterlassen?
- ❌ **Fehlende Transparenz:** Status unklar (offen/in Bearbeitung/erledigt)
- ❌ **Koordinationsaufwand:** Erfordert Teammeetings für organisatorische Dinge, die digital lösbar wären

### Was fehlt wirklich?
Eine einfache, transparente Möglichkeit zur **Zuweisung**, **Notizen** und **Statusverfolgung** – ohne die Komplexität und Kosten eines vollwertigen Ticketsystems.

---

## Lösung: Die CI-Inbox

Die CI-Inbox transformiert statische, geteilte IMAP-Postfächer in eine **kollaborative Aufgabenwarteschlange** mit klaren Verantwortlichkeiten.

---

## Vision Statement

**Die CI-Inbox ermöglicht kleinen, autonomen Teams, gemeinsame E-Mail-Posteingänge effizient, transparent und nachverfolgbar zu verwalten – ohne die Komplexität eines Ticketsystems.**

Durch **Zuweisung**, **interne Notizen** und **IMAP-Unabhängigkeit** (nahtlose Rückkehr zum persönlichen Postfach) stellen wir sicher, dass:
- ✅ Keine E-Mail übersehen wird
- ✅ Jeder jederzeit weiß, wer wofür verantwortlich ist
- ✅ Teammitglieder flexibel und autonom arbeiten können
- ✅ Die Kontrolle über sensible Kommunikation beim zuständigen Teammitglied bleibt

**Keep It Simple, Stupid (KISS):** Wir bauen genau die Features, die kleine Teams brauchen – nicht mehr.

---

## Zielgruppe

### Wer nutzt die CI-Inbox?

**Primäre Zielgruppe:**
- Kleine Teams (3-7 Personen)
- Autonomes, flexibles Arbeiten (verschiedene Arbeitszeitmodelle)
- Gemeinsame Kontaktadresse(n) wie `info@`, `support@`, `kontakt@`

**Technischer Kontext:**
- Webhosting mit PHP/MySQL verfügbar
- IMAP-Postfächer vorhanden
- Kein Budget für professionelle Ticketsysteme
- **Kein Bedarf** für den Funktionsumfang eines professionellen Ticketsystems

**Typische Anwendungsfälle:**
- Kleine Vereine
- Kirchengemeinden
- Soziale Einrichtungen
- Kleine Beratungsstellen
- Startups/Kleinunternehmen

### Was die Zielgruppe NICHT braucht:
- ❌ SLA-Tracking & Eskalationsmanagement
- ❌ Komplexe Workflow-Automatisierung
- ❌ Customer-Relationship-Management (CRM)
- ❌ Umfangreiche Reporting-Dashboards
- ❌ Multi-Team/Multi-Mandanten-Fähigkeit (vorerst)
- ❌ Admin-Übersichten zur Teamauslastung (nice-to-have, aber nicht MVP)

---

## Kern-Workflows: Wie funktioniert die CI-Inbox?

Die CI-Inbox unterstützt drei Haupt-Workflows, die flexibel kombiniert werden können:

---

### Workflow A: Schnelle Team-Antwort über gemeinsame Adresse
**Szenario:** Standardanfragen, die direkt über die `info@` beantwortet werden können.

**Ablauf:**
1. E-Mail kommt auf `info@` an
2. Teammitglied sieht die Mail in der CI-Inbox
3. Teammitglied **weist sich die Mail selbst zu**
4. Teammitglied beantwortet **direkt über die CI-Inbox** (Absender bleibt `info@`)
5. Status wird automatisch auf **"Erledigt"** gesetzt
6. Thread wird archiviert

**Vorteil:**
- Schnell & unkompliziert
- Team-Identität bleibt gewahrt (`info@` als Absender)
- Für andere Teammitglieder sichtbar: "Erledigt von Person X"

---

### Workflow B: Zuweisung mit interner Rücksprache
**Szenario:** Mail erfordert Expertise einer bestimmten Person oder interne Abstimmung.

**Ablauf:**
1. E-Mail kommt auf `info@` an
2. Teammitglied A sieht die Mail
3. Teammitglied A **weist die Mail an Teammitglied B zu**
4. Teammitglied A fügt **interne Notiz** hinzu (z.B. "Bitte bis Freitag antworten, betrifft Projekt X")
5. Status wird auf **"Offen/Zugewiesen"** gesetzt
6. Teammitglied B sieht die Zuweisung, liest die Notiz und bearbeitet die Mail

**Vorteil:**
- Klare Verantwortlichkeit
- Kontext wird weitergegeben (Notizen)
- Keine Rückfragen via separater E-Mail nötig

---

### Workflow C: Persönliche Übernahme (IMAP-Transfer)
**Szenario:** Sensible Themen (z.B. Seelsorge), langfristige 1:1-Kommunikation, persönliche Verantwortung.

**Ziel:** Die Konversation soll **vollständig aus der gemeinsamen Inbox** raus und ins **persönliche IMAP-Postfach** des Verantwortlichen.

**Zwei Varianten:**

#### Variante C1: Einfache Weiterleitung (manuell)
1. Teammitglied leitet die Mail an **eigene IMAP-Adresse** weiter
2. Beantwortet von dort mit persönlichem Absender
3. Markiert Thread in CI-Inbox als "Persönlich übernommen" → archiviert

#### Variante C2: Intelligenter Transfer (automatisiert)
1. Teammitglied wählt in CI-Inbox: **"Auf meinen Account übertragen"**
2. System verschiebt die **Original-Mail** ins persönliche IMAP-Postfach
3. Teammitglied antwortet aus eigenem Mail-Client (mit persönlichem Absender)
4. **Gesendete Antwort** wird automatisch im persönlichen SENT-Ordner abgelegt
5. Thread verschwindet aus CI-Inbox (oder Status: "Extern übernommen")

**Vorteil:**
- Volle Kontrolle & Datenschutz für sensible Themen
- Nahtloser Übergang zu persönlicher Kommunikation
- Kein "Medienbruch" (alles bleibt in IMAP-Struktur erhalten)

---

## Workflow-Kombinationen & Flexibilität

Die Workflows können flexibel kombiniert werden:

**Beispiel 1:** Workflow B → A
- Mail wird zunächst zugewiesen (B) mit Notiz
- Zugewiesene Person antwortet dann direkt über `info@` (A)

**Beispiel 2:** Workflow A → C
- Erste Antwort über `info@` (A)
- Weitere Korrespondenz wird persönlich übernommen (C)

**Beispiel 3:** Workflow C → Rückkehr zur CI-Inbox
- Mail wird persönlich übernommen (C)
- Bei Rückfragen kann Thread wieder in CI-Inbox erscheinen (z.B. durch BCC an `info@`)

---

## Erfolgskriterien

### Wie messen wir, ob die CI-Inbox funktioniert?

**Primäre Erfolgskriterien (MVP):**
1. ✅ **Keine doppelte Bearbeitung mehr**
   - Metrik: Anzahl der Fälle, in denen zwei Personen dieselbe Mail beantwortet haben → 0

2. ✅ **100% Nachvollziehbarkeit**
   - Jedes Teammitglied kann jederzeit sehen:
     - Wer hat Mail XY beantwortet?
     - Wann wurde sie beantwortet?
     - Welche internen Notizen gibt es?

3. ✅ **Reduzierung von Koordinations-Meetings**
   - Vorher: Wöchentliches Meeting zur "Mail-Verteilung"
   - Nachher: Kein Meeting mehr nötig → Zeit gespart

4. ✅ **Keine verlorenen E-Mails**
   - Jede Mail hat einen Status (Neu/Offen/Erledigt)
   - Keine Mail bleibt "hängen"

**Sekundäre Erfolgskriterien (Post-MVP):**
5. ⏱️ **Reaktionszeit verbessert**
   - Durchschnittliche Zeit bis zur ersten Antwort < 24h (Baseline messen!)

6. 😊 **Team-Zufriedenheit**
   - Qualitatives Feedback: "Ist die Arbeit damit einfacher geworden?"
   - Subjektive Bewertung: 4/5 Sterne oder besser

---

## Use Cases im Detail

### Use Case 1: Neue Anfrage auf info@ kommt an
**Akteure:** Gesamtes Team  
**Ziel:** Mail wird gesehen und zugewiesen

**Ablauf:**
1. System pollt IMAP (alle 5 Minuten)
2. Neue Mail wird erkannt und als Thread in CI-Inbox angezeigt
3. Status: **"Neu/Unzugewiesen"**
4. Alle Teammitglieder sehen die Mail in ihrer Übersicht
5. Erstes Teammitglied, das sich zuständig fühlt, weist sich die Mail zu

**Alternative:**
- Teammitglied weist Mail direkt einer anderen Person zu (Workflow B)

---

### Use Case 2: Notiz für Kollegen hinterlassen
**Akteure:** Teammitglied A, Teammitglied B  
**Ziel:** Kontext weitergeben ohne externe Mail

**Ablauf:**
1. Teammitglied A öffnet Thread
2. Klickt auf "Interne Notiz hinzufügen"
3. Schreibt: "Betrifft Projekt X, siehe Anhang. Bitte bis Freitag antworten."
4. Notiz wird gespeichert mit Timestamp & Verfasser
5. Teammitglied B sieht die Notiz bei Öffnung des Threads

**Ergebnis:**
- Keine separate E-Mail nötig
- Kontext bleibt beim Thread
- Historie ist nachvollziehbar

---

### Use Case 3: Seelsorgerliche Anfrage persönlich übernehmen
**Akteure:** Seelsorger/in im Team  
**Ziel:** Sensible Kommunikation komplett aus gemeinsamer Inbox entfernen

**Ablauf:**
1. Seelsorger/in sieht Anfrage in CI-Inbox
2. Erkennt: "Das ist sensibel und fällt in meine Verantwortung"
3. Wählt: **"Auf meinen Account übertragen"**
4. System:
   - Verschiebt Original-Mail in persönliches IMAP-Postfach
   - Markiert Thread in CI-Inbox als "Extern übernommen"
   - Optional: Thread wird aus CI-Inbox entfernt
5. Seelsorger/in antwortet aus eigenem Mail-Client
6. Alle weiteren Mails gehen direkt an persönliche Adresse

**Datenschutz gewährleistet:**
- Kein anderes Teammitglied hat Zugriff auf Inhalt
- Mail ist physisch aus gemeinsamer Inbox verschwunden

---

### Use Case 4: Urlaubsvertretung
**Akteure:** Teammitglied A (im Urlaub), Teammitglied B (Vertretung)  
**Ziel:** Offene Threads werden von Vertretung übernommen

**Ablauf:**
1. Teammitglied A hat 3 offene Threads (Status: "Offen/Zugewiesen")
2. Vor dem Urlaub: A weist alle Threads an B zu
3. A fügt Notiz hinzu: "Bin im Urlaub bis 30.11., bitte übernehmen"
4. B sieht die Threads in eigener Übersicht und übernimmt

**Alternative (Post-MVP):**
- Admin-Funktion: "Alle Threads von User A auf User B übertragen"

---

## Abgrenzung: Was ist die CI-Inbox NICHT?

Um den Scope klar zu halten (KISS-Prinzip):

**Die CI-Inbox ist NICHT:**
- ❌ Ein vollwertiges Ticketsystem (kein Jira/Zendesk-Ersatz)
- ❌ Ein CRM-System (keine Kundenverwaltung, Verkaufspipeline)
- ❌ Eine Projektmanagement-Software
- ❌ Ein Dokumenten-Management-System
- ❌ Eine komplette Mail-Server-Lösung

**Die CI-Inbox ist:**
- ✅ Eine schlanke Kollaborations-Ebene **über** bestehenden IMAP-Postfächern
- ✅ Ein Tool zur **Zuweisung** und **Statusverfolgung** von E-Mails
- ✅ Eine Brücke zwischen "gemeinsamer Inbox" und "persönlichem Postfach"

---

## Langzeit-Vision (Post-1.0)

### Version 2.0: Erweiterte Team-Features
- Multi-Team-Support (mehrere gemeinsame Posteingänge)
- Einfache Statistiken (z.B. "Anzahl beantworteter Mails pro Person/Woche")
- Vorlagen für Standard-Antworten

### Version 3.0: Leichte Integrationen
- REST-API für externe Tools
- Webhook-System (z.B. Benachrichtigung in Slack/Mattermost)
- E-Mail-Benachrichtigungen bei Zuweisung

### Niemals (Out of Scope):
- KI-gestützte Antwortvorschläge
- Chat-Integration (Echtzeit-Messaging)
- Video-Calls oder Voice-Integration
- Umfangreiche Workflow-Automatisierung

---

**Ende der Vision*
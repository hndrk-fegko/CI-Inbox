# Mercury Mail Server - Setup Guide für CI-Inbox Testing

**Datum:** 17. November 2025  
**Zweck:** Lokaler IMAP/SMTP Server für IMAP-Client Tests

---

## 1. Mercury Konfiguration

### Schritt 1: Mercury Admin Tool öffnen

1. XAMPP Control Panel → Mercury → "Admin"
2. Oder direkt: `C:\xampp\MercuryMail\MERCURY.EXE`

---

### Schritt 2: Test-User anlegen

**In Mercury Admin:**

1. **Configuration** → **Manage local users...**
2. **Add** Button klicken
3. User anlegen:
   - **Username:** `testuser`
   - **Personal name:** `Test User`
   - **Password:** `testpass123`
4. **OK** klicken

---

### Schritt 3: IMAP/POP3 Server prüfen

**In Mercury Admin:**

1. **Configuration** → **Protocol modules**
2. Prüfe dass folgende Module **aktiv** sind (Checkboxen angehakt):
   - ✅ **MercuryS SMTP Server** (Port 25)
   - ✅ **MercuryP POP3 Server** (Port 110)
   - ✅ **MercuryI IMAP4rev1 Server** (Port 143)
3. Falls nicht aktiv → Checkboxen anhaken und **OK**

---

### Schritt 4: Firewall (optional, falls Probleme)

Falls Verbindung fehlschlägt:

1. Windows Firewall → Erweiterte Einstellungen
2. Eingehende Regeln → Neue Regel
3. Port → TCP → Ports: 143,110,25
4. Verbindung zulassen → OK

**Oder einfacher:** Windows Firewall temporär deaktivieren für Test

---

## 2. Test-E-Mails erstellen

### Methode 1: Via SMTP (wenn Mercury läuft)

```bash
telnet localhost 25
HELO localhost
MAIL FROM: admin@localhost
RCPT TO: testuser@localhost
DATA
Subject: Test Email 1
From: admin@localhost
To: testuser@localhost

This is a test email for IMAP testing.
.
QUIT
```

### Methode 2: Manuell (einfacher!)

1. Gehe zu: `C:\xampp\MercuryMail\MAIL\testuser\`
2. Erstelle dort eine Datei: `test1.eml`
3. Inhalt:

```
From: admin@localhost
To: testuser@localhost
Subject: Test Email 1
Date: Sun, 17 Nov 2025 12:00:00 +0100

This is a test email for IMAP client testing.

Best regards,
Admin
```

4. Speichern
5. Wiederhole für `test2.eml`, `test3.eml` etc.

---

## 3. IMAP Test mit CI-Inbox Client

**Test-Script ausführen:**

```bash
cd C:\Users\Dienstlaptop-HD\Documents\Privat-Nextcloud\Private_Dateien\Tools_und_Systeme\CI-Inbox

php src/modules/imap/tests/manual-test.php
```

**Credentials eingeben:**
- Host: `localhost`
- Port: `143`
- Username: `testuser`
- Password: `testpass123`
- SSL: `n` (kein SSL für localhost)

**Erwartete Ausgabe:**
```
✅ Connected to localhost:143
✅ Folders: INBOX (3 msgs)
✅ Fetched 3 messages
✅ Message 1: "Test Email 1"
```

---

## 4. Verbindungsdetails

**IMAP:**
- Host: `localhost`
- Port: `143` (non-SSL)
- Username: `testuser`
- Password: `testpass123`
- Encryption: `none` oder `tls` (nicht `ssl`)

**SMTP (für M1 Sprint 1.4):**
- Host: `localhost`
- Port: `25`
- Auth: optional

**POP3 (Alternative):**
- Host: `localhost`
- Port: `110`

---

## 5. Automatischer Test (EMPFOHLEN!)

### Mercury Quick Test

**Neues automatisches Testskript mit SMTP + IMAP Roundtrip:**

```bash
cd C:\Users\Dienstlaptop-HD\Documents\Privat-Nextcloud\Private_Dateien\Tools_und_Systeme\CI-Inbox

C:\xampp\php\php.exe src/modules/imap/tests/mercury-quick-test.php
```

**Was wird getestet:**
1. ✅ SMTP: Email senden
2. ✅ IMAP: Verbindung & Login
3. ✅ IMAP: Folder-Zugriff (automatische INBOX-Erkennung)
4. ✅ IMAP: Email empfangen & lesen
5. ✅ IMAP: Operationen (mark as read, delete)

**Erwartete Ausgabe bei Erfolg:**
```
=== ✅ ALL TESTS PASSED ===

🎉 Mercury Configuration is CORRECT! 🎉

✅ SMTP sending works
✅ IMAP connection works
✅ Folder access works ('INBOX')
✅ Email delivery works
✅ Message reading works
✅ Operations (mark, delete) work

Your Mercury server is ready for CI-Inbox!

ℹ️  Config saved to mercury-config.json
```

**Gespeicherte Konfiguration:** `src/modules/imap/tests/mercury-config.json`

Beispiel-Inhalt:
```json
{
    "smtp": {
        "host": "localhost",
        "port": 25,
        "ssl": false,
        "auth": false
    },
    "imap": {
        "host": "localhost",
        "port": 143,
        "ssl": false,
        "inbox_folder": "INBOX"
    },
    "test_user": {
        "username": "testuser",
        "password": "testpass123",
        "email": "testuser@localhost"
    },
    "test_result": "success",
    "test_timestamp": "2025-11-17 18:17:58"
}
```

**Verwendung für Installer:**
Diese Config kann direkt für den Setup-Wizard verwendet werden!

---

## 6. Troubleshooting

### Problem: "Connection refused"

**Lösung:**
1. Mercury läuft? (XAMPP Control Panel → grüner Button)
2. Port 143 frei? Test: `netstat -an | findstr :143`
3. Firewall blockiert? → Temporär deaktivieren

### Problem: "Authentication failed"

**Lösung:**
1. User existiert? (Mercury Admin → Manage local users)
2. Password korrekt? (Case-sensitive!)
3. Username ohne @domain (nur `testuser`, nicht `testuser@localhost`)

### Problem: "No messages found"

**Lösung:**
1. Test-E-Mails erstellt? (siehe Schritt 2 oder verwende Quick Test!)
2. E-Mails im richtigen Ordner? (`C:\xampp\MercuryMail\MAIL\testuser\`)
3. Mercury neu starten (Stop → Start in XAMPP)

### Problem: "Array to string conversion" (bekannt, harmlos)

**Symptom:** Warnung bei `getFrom()` - gibt Array statt String zurück

**Lösung:** Bereits gefixt in mercury-quick-test.php:
```php
$from = $message->getFrom();
$fromStr = is_array($from) ? implode(', ', $from) : $from;
```

### Problem: "Can't connect to Laptop-Hendrik-ZenFlip" (IMAP Shutdown Notice)

**Symptom:** Notice beim Skript-Ende, aber Tests erfolgreich

**Erklärung:** IMAP Extension versucht Hostname-Lookup beim Shutdown  
**Lösung:** Ignorieren (harmlos) oder `@imap_close()` verwenden

---

## 7. Erkenntnisse für CI-Inbox Entwicklung

### Mercury-Besonderheiten:

1. **Message-ID:**
   - Mercury speichert Message-ID Header
   - `getMessageId()` gibt aber nur numerische UID zurück!
   - **Workaround:** Suche nach `Subject` oder `getRawHeaders()` parsen

2. **SMTP Authentication:**
   - Mercury localhost braucht KEINE Auth (Port 25)
   - AUTH LOGIN optional (funktioniert aber)

3. **Folder Names:**
   - Mercury nutzt `INBOX` (uppercase)
   - Case-sensitive!

4. **Email Delivery:**
   - Sehr schnell (<1s vom SMTP-Send bis IMAP-Fetch)
   - Perfekt für Tests

### Empfehlung für Produktiv-System:

Wenn CI-Inbox mit Mercury eingesetzt wird:
- **Threading:** Nutze `In-Reply-To` + `References` Header
- **Message-ID Matching:** Parsen der Raw Headers nötig
- **SMTP:** Authentifizierung optional (localhost-Betrieb)

---

## 8. Nächste Schritte

---

## 8. Nächste Schritte

Wenn Mercury Quick Test erfolgreich (✅ ALL TESTS PASSED):

1. ✅ **M1 Sprint 1.1** VALIDIERT mit echtem IMAP-Server
2. 🔄 **M1 Sprint 1.2** starten - E-Mail-Parser implementieren
3. 🔄 **M1 Sprint 1.3** - Threading-Engine testen mit mehreren E-Mails
4. 🔄 **M1 Sprint 1.4** - SMTP-Versand via Mercury

---

**Status:** ✅ **GETESTET & FUNKTIONIERT** (17. November 2025)

**Test-Ergebnis:**
- Mercury läuft auf XAMPP
- Alle Tests bestanden
- Konfiguration gespeichert in `mercury-config.json`
- Bereit für M1 Sprint 1.2!


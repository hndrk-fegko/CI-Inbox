# Personal IMAP Accounts API

**Version:** 0.1.0  
**Status:** ✅ Implementiert  
**Datum:** 18. November 2025

## Übersicht

Die Personal IMAP Accounts API ermöglicht es Benutzern, ihre **persönlichen E-Mail-Accounts** (Gmail, Outlook, etc.) im System zu hinterlegen. Diese Accounts dienen als **Quell-Accounts** für **Workflow C (Transfer)**.

### Use Case: Workflow C - Transfer

```
┌─────────────────────────────────────┐
│  User's Personal Email Account     │
│  (Gmail, Outlook, etc.)             │
└──────────────┬──────────────────────┘
               │
               │ User wählt Email aus
               │ Klickt "Transfer to Inbox"
               ↓
┌─────────────────────────────────────┐
│  Shared Inbox (info@company.com)    │
│  → Thread wird erstellt             │
│  → Email wird zugewiesen            │
└─────────────────────────────────────┘
```

**Wichtig:** Diese Accounts werden **NICHT automatisch gepollt**! Sie sind reine **Quell-Accounts** für manuelles Transfer durch den User.

---

## Naming Convention

Klare Trennung zwischen persönlichen und Shared Inbox Accounts:

| Endpoint | Zweck | Beschreibung |
|----------|-------|--------------|
| `/api/user/imap-accounts` | **Persönliche Accounts** | User's Gmail, Outlook, etc. |
| `/api/imap/accounts/{id}/sync` | **Shared Inbox Sync** | Hauptkonto (info@company.com) |

**Grund:** Vermeidung von Namenskonflikten und klare Trennung der Zuständigkeiten.

---

## API Endpoints

### 1. List Personal Accounts

**Endpoint:** `GET /api/user/imap-accounts`

**Authorization:** User (sieht nur eigene Accounts)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "email": "user@gmail.com",
      "imap_host": "imap.gmail.com",
      "imap_port": 993,
      "imap_username": "user@gmail.com",
      "imap_encryption": "ssl",
      "is_default": false,
      "is_active": true,
      "last_sync_at": null,
      "created_at": "2025-11-18T12:00:00Z",
      "updated_at": "2025-11-18T12:00:00Z"
    }
  ],
  "count": 1
}
```

---

### 2. Get Single Account

**Endpoint:** `GET /api/user/imap-accounts/{id}`

**Authorization:** User (nur eigene Accounts)

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "email": "user@gmail.com",
    "imap_host": "imap.gmail.com",
    "imap_port": 993,
    "imap_username": "user@gmail.com",
    "imap_encryption": "ssl",
    "is_default": false,
    "is_active": true,
    "last_sync_at": null,
    "created_at": "2025-11-18T12:00:00Z"
  }
}
```

---

### 3. Create Personal Account

**Endpoint:** `POST /api/user/imap-accounts`

**Authorization:** User

**Request:**
```json
{
  "email": "user@gmail.com",
  "password": "app-specific-password",
  "imap_host": "imap.gmail.com",
  "imap_port": 993,
  "imap_username": "user@gmail.com",
  "imap_encryption": "ssl",
  "is_default": false,
  "is_active": true
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "email": "user@gmail.com",
    "imap_host": "imap.gmail.com",
    "imap_port": 993,
    "is_active": true,
    "created_at": "2025-11-18T12:00:00Z"
  }
}
```

**Validation:**
- ✅ Email muss valides Format haben
- ✅ Password ist required
- ✅ Keine Duplikate (pro User)
- ✅ IMAP Host required
- ✅ Port muss numerisch sein

---

### 4. Update Personal Account

**Endpoint:** `PUT /api/user/imap-accounts/{id}`

**Authorization:** User (nur eigene Accounts)

**Request:**
```json
{
  "imap_host": "imap.gmail.com",
  "imap_port": 993,
  "password": "new-app-password",
  "is_active": true
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "email": "user@gmail.com",
    "imap_host": "imap.gmail.com",
    "imap_port": 993,
    "is_active": true,
    "updated_at": "2025-11-18T12:30:00Z"
  }
}
```

---

### 5. Delete Personal Account

**Endpoint:** `DELETE /api/user/imap-accounts/{id}`

**Authorization:** User (nur eigene Accounts)

**Response:**
```json
{
  "success": true,
  "message": "Account deleted successfully"
}
```

---

### 6. Test IMAP Connection

**Endpoint:** `POST /api/user/imap-accounts/{id}/test-connection`

**Authorization:** User (nur eigene Accounts)

**Response (Success):**
```json
{
  "success": true,
  "message": "Connection successful"
}
```

**Response (Failure):**
```json
{
  "success": false,
  "message": "Connection failed: Invalid credentials"
}
```

**Verwendung:**
- ✅ Vor dem Speichern testen, ob Credentials korrekt sind
- ✅ Troubleshooting bei Verbindungsproblemen
- ✅ IMAP-Server Erreichbarkeit prüfen

---

## Architektur

### Layer Structure

```
┌─────────────────────────────────────┐
│   Routes: /api/user/imap-accounts   │  ← HTTP Layer
├─────────────────────────────────────┤
│   PersonalImapAccountController     │  ← Request Handling
├─────────────────────────────────────┤
│   PersonalImapAccountService        │  ← Business Logic
├─────────────────────────────────────┤
│   ImapAccountRepository (shared)    │  ← Data Access
├─────────────────────────────────────┤
│   ImapAccount Model (Eloquent)      │  ← ORM
└─────────────────────────────────────┘
```

### Dateien

| Datei | Zeilen | Zweck |
|-------|--------|-------|
| `src/app/Services/PersonalImapAccountService.php` | 320 | Business Logic |
| `src/app/Controllers/PersonalImapAccountController.php` | 353 | HTTP Controller |
| `src/routes/api.php` | +60 | Route Definitions |
| `src/config/container.php` | +20 | DI Container |
| `tests/manual/personal-imap-account-test.php` | 295 | API Tests |

### Repository Pattern

**Wichtig:** Verwendet den **bestehenden** `ImapAccountRepository`, nicht einen neuen!

```php
// Service verwendet shared Repository
public function __construct(
    private ImapAccountRepository $repository,  // ← Shared!
    private EncryptionInterface $encryption,
    private LoggerInterface $logger
) {}

// User-Scope durch Parameter
$accounts = $this->repository->getAllByUser($userId);
$account = $this->repository->findByEmail($email, $userId);
```

**Vorteil:** Keine Code-Duplikation, klare Trennung durch Parameter.

---

## Security

### 1. Password Encryption

Alle IMAP-Passwörter werden verschlüsselt:

```php
// Encryption
$encryptedPassword = $this->encryption->encrypt($password);

// Storage
$account->imap_password_encrypted = $encryptedPassword;

// Decryption (bei Connection Test)
$password = $this->encryption->decrypt($account->imap_password_encrypted);
```

- **Algorithmus:** AES-256-CBC
- **Service:** `EncryptionService` (src/modules/encryption/)
- **Key:** Environment Variable `ENCRYPTION_KEY`

### 2. Ownership Protection

User kann nur eigene Accounts verwalten:

```php
public function getAccount(int $accountId, int $userId): ?ImapAccount
{
    $account = $this->repository->find($accountId);
    
    // Ownership Check
    if ($account && $account->user_id !== $userId) {
        $this->logger->warning('Account access denied', [
            'account_id' => $accountId,
            'owner_id' => $account->user_id,
            'requested_by' => $userId
        ]);
        return null; // Access denied!
    }
    
    return $account;
}
```

**Result:** 404 Not Found (kein 403, um Account-Existenz nicht zu leaken)

### 3. Input Validation

Alle Eingaben werden validiert:

- ✅ Email: `filter_var($email, FILTER_VALIDATE_EMAIL)`
- ✅ Port: `(int)$port` mit Range-Check
- ✅ Encryption: Enum-Check (`ssl`, `tls`, `none`)
- ✅ Required Fields: Email, Password, Host
- ✅ Duplicate Check: Pro User keine doppelten Emails

---

## Gmail Setup (Beispiel)

### 1. App-Passwort erstellen

```
1. Google Account → Security → 2-Step Verification
2. App passwords → Select "Mail" + "Other (Custom name)"
3. Name: "CI-Inbox"
4. Generate → Kopiere 16-stelliges Passwort
```

### 2. Account im System hinterlegen

```bash
curl -X POST http://localhost:8000/api/user/imap-accounts \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@gmail.com",
    "password": "abcd efgh ijkl mnop",
    "imap_host": "imap.gmail.com",
    "imap_port": 993,
    "imap_username": "user@gmail.com",
    "imap_encryption": "ssl"
  }'
```

### 3. Connection testen

```bash
curl -X POST http://localhost:8000/api/user/imap-accounts/1/test-connection
```

**Erwartung:** `{"success": true, "message": "Connection successful"}`

---

## Common IMAP Settings

| Provider | IMAP Host | Port | Encryption |
|----------|-----------|------|------------|
| Gmail | imap.gmail.com | 993 | SSL |
| Outlook | outlook.office365.com | 993 | SSL |
| Yahoo | imap.mail.yahoo.com | 993 | SSL |
| iCloud | imap.mail.me.com | 993 | SSL |
| GMX | imap.gmx.net | 993 | SSL |

---

## Testing

### Manual Tests

```powershell
# Server starten
php -S localhost:8000 -t src/public

# Tests ausführen (neues Terminal)
php tests/manual/personal-imap-account-test.php
```

**Erwartetes Ergebnis:** 10/10 Tests bestanden ✓

### Test Coverage

| Test | Beschreibung | Erwartung |
|------|--------------|-----------|
| 1 | Create account | 201 Created |
| 2 | List accounts | 200 OK |
| 3 | Get single account | 200 OK |
| 4 | Update account | 200 OK |
| 5 | Test connection | 400 Bad Request (invalid credentials) |
| 6 | Create duplicate | 400 Bad Request (duplicate email) |
| 7 | Invalid email | 400 Bad Request (validation error) |
| 8 | Missing password | 400 Bad Request (required field) |
| 9 | Delete account | 200 OK |
| 10 | Get deleted account | 404 Not Found |

---

## Nächste Schritte

### TODO: Folder-Liste API

User muss Ordner seines Personal Accounts sehen können:

```
GET /api/user/imap-accounts/{id}/folders
→ ["INBOX", "Sent", "Drafts", "Archive", "Work", "Personal"]
```

**Zweck:** User wählt in Settings, wohin übernommene Emails verschoben werden.

### TODO: Transfer API

Email von Personal Account → Shared Inbox übertragen:

```
POST /api/user/imap-accounts/{id}/transfer
Body: {
  "message_uid": "12345",
  "source_folder": "INBOX",
  "move_to_folder": "Archive"  // Optional: Email nach Transfer verschieben
}
```

**Workflow:**
1. Email aus Personal Account abrufen (via IMAP)
2. Email parsen (EmailParser)
3. Thread im Shared Inbox erstellen (ThreadManager)
4. Email dem Thread zuordnen
5. Optional: Email im Personal Account nach "Archive" verschieben

### TODO: Authentication Middleware

Aktuell hardcoded:
```php
$request = $request->withAttribute('user_id', 1); // Temporary!
```

**Geplant:**
- JWT Token Validation
- User Context Injection
- Permission Checks

---

## Related Documentation

- **API Reference:** `docs/dev/api.md` (Zeile 1180+)
- **Architecture:** `docs/dev/architecture.md`
- **Workflow C Details:** `docs/dev/M3-MVP-UI.md`
- **Encryption Module:** `docs/modules/encryption/README.md`

---

**Status:** ✅ API vollständig implementiert  
**Tests:** ✅ 10/10 Tests bestanden  
**Production Ready:** ⏳ Nach Authentication Middleware

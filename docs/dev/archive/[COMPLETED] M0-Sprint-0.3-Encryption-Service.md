# [COMPLETED] M0 Sprint 0.3: Encryption-Service

**Status:** ✅ ABGESCHLOSSEN  
**Milestone:** M0 Foundation  
**Sprint:** 0.3  
**Geschätzte Dauer:** 1 Tag (2 Sprints à 30-60min)  
**Tatsächliche Dauer:** ~45 Min (1 Sprint)  
**Start:** 17.11.2025  
**Ende:** 17.11.2025

---

## 1. Ziel

Implementierung eines sicheren Verschlüsselungs-Service für sensible Daten (IMAP-Passwörter).

**Warum wichtig?**
- IMAP-Passwörter müssen verschlüsselt in DB gespeichert werden
- Zentraler Service für alle Encryption-Needs
- AES-256-CBC Standard-Verschlüsselung

**Erfolg-Kriterien:**
- ✅ AES-256-CBC Verschlüsselung (OpenSSL)
- ✅ Key-Management über Config-Modul
- ✅ encrypt() / decrypt() Methoden
- ✅ Standalone testbar
- ✅ Exception-Handling bei Fehlern

---

## 2. Anforderungen (aus `inventar.md` Feature 5.3)

**Priorität:** MUST (MVP)  
**Workflows:** A, B (IMAP-Konfiguration)  
**Dependencies:** Config-Modul (für Encryption-Key)

**Funktionale Anforderungen:**
- Verschlüsselung: AES-256-CBC
- Methoden: encrypt(string $data): string, decrypt(string $encrypted): string
- Key-Source: ENCRYPTION_KEY aus .env
- IV-Handling: Random IV pro Verschlüsselung
- Base64-Encoding für DB-Storage

**Nicht-funktionale Anforderungen:**
- Performance: < 5ms pro encrypt/decrypt
- Security: OpenSSL PHP Extension verwenden
- Fehlertoleranz: Exceptions bei ungültigem Key oder Cipher

---

## 3. Technisches Design

### 3.1 Architektur (Layer-Abstraktion)

```
┌─────────────────────────────────────┐
│   Business Logic / Services         │
│   (nutzt EncryptionInterface)       │
└──────────────┬──────────────────────┘
               │ depends on
┌──────────────▼──────────────────────┐
│   EncryptionInterface               │
│   - encrypt($data)                  │
│   - decrypt($encrypted)             │
└──────────────┬──────────────────────┘
               │ implements
┌──────────────▼──────────────────────┐
│   EncryptionService                 │
│   - OpenSSL wrapper                 │
│   - Key von ConfigService           │
└──────────────┬──────────────────────┘
               │ uses
┌──────────────▼──────────────────────┐
│   ConfigService                     │
│   - Liefert ENCRYPTION_KEY          │
└─────────────────────────────────────┘
```

---

### 3.2 Verzeichnisstruktur

```
src/modules/encryption/
├── module.json                     # Modul-Manifest
├── src/
│   ├── EncryptionService.php       # Haupt-Service (OpenSSL)
│   ├── EncryptionInterface.php     # Interface (für DI)
│   └── Exceptions/
│       └── EncryptionException.php # Custom Exception
├── tests/
│   └── manual-test.php             # Standalone Test
└── README.md                       # Standalone-Dokumentation
```

---

### 3.3 Verschlüsselungs-Format

**Encrypted String Format:**
```
<IV (16 bytes, base64)>::<Encrypted Data (base64)>
```

**Beispiel:**
```
dGVzdGl2MTIzNDU2Nzg5MA==::aGVsbG8gd29ybGQgZW5jcnlwdGVk
```

**Warum `::`?**
- IV muss für Decrypt gespeichert werden
- Einfaches Parsing mit explode()
- Base64 verhindert Charset-Probleme

---

## 4. Implementierungs-Plan

### Task 1: Modul-Struktur anlegen ⏳ NEXT
**Dauer:** 5 Min  
**Dateien:** Verzeichnisse + `module.json`

**Actions:**
```powershell
New-Item -ItemType Directory -Path "src/modules/encryption/src/Exceptions"
New-Item -ItemType Directory -Path "src/modules/encryption/tests"
```

### Task 2: EncryptionInterface erstellen
**Dauer:** 10 Min  
**Dateien:** `src/EncryptionInterface.php`

**Methods:**
- `encrypt(string $data): string`
- `decrypt(string $encrypted): string`

### Task 3: EncryptionException erstellen
**Dauer:** 5 Min  
**Dateien:** `src/Exceptions/EncryptionException.php`

**Static Factories:**
- `invalidKey()`
- `encryptionFailed()`
- `decryptionFailed()`

### Task 4: EncryptionService implementieren
**Dauer:** 40 Min  
**Dateien:** `src/EncryptionService.php`

**Core Logic:**
```php
public function encrypt(string $data): string
{
    $cipher = 'AES-256-CBC';
    $key = $this->config->getString('encryption.key');
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
    
    $encrypted = openssl_encrypt($data, $cipher, $key, 0, $iv);
    
    return base64_encode($iv) . '::' . $encrypted;
}
```

### Task 5: Standalone-Test erstellen
**Dauer:** 15 Min  
**Dateien:** `tests/manual-test.php`

**Test Cases:**
1. Encrypt simple string
2. Decrypt encrypted string
3. Verify original = decrypted
4. Test with special characters
5. Test with empty string
6. Test exception on invalid key

### Task 6: Composer Autoloader aktualisieren
**Dauer:** 5 Min  

```json
"CiInbox\\Modules\\Encryption\\": "src/modules/encryption/src/"
```

### Task 7: Dokumentation
**Dauer:** 15 Min  
**Dateien:** `README.md`

**Sections:**
- Overview
- Installation
- Usage Examples
- API Reference
- Security Considerations
- Troubleshooting

**Gesamt:** ~95 Min (ca. 2 Sprints)

---

## 5. Testing-Strategie

### 5.1 Manual Tests (Standalone)
```powershell
C:\xampp\php\php.exe src/modules/encryption/tests/manual-test.php
```

**Erwartetes Ergebnis:**
```
=== CI-Inbox Encryption Module - Manual Test ===

1. Creating EncryptionService...
   ✅ EncryptionService created

2. Testing basic encryption...
   Original: 'Hello World'
   Encrypted: 'dGVzdGl2MTIzNDU2Nzg5MA==::aGVsbG8...'
   ✅ Encryption works

3. Testing decryption...
   Decrypted: 'Hello World'
   ✅ Decryption works

4. Testing round-trip...
   ✅ Original matches decrypted

5. Testing special characters...
   Original: 'Ümlaut ñ 中文 🔐'
   ✅ Special characters work

6. Testing IMAP password (real use case)...
   Password: 'my$ecret!Pass123'
   Encrypted length: 72 chars
   ✅ Password encryption works

===========================================
✅ ALL TESTS PASSED
===========================================
```

---

## 6. Security Considerations

### ✅ Best Practices:
1. **AES-256-CBC** - Industry standard
2. **Random IV** - Unique IV pro Verschlüsselung
3. **Key-Source** - .env File (nicht im Code)
4. **Key-Length** - 32 bytes (256 bit) für AES-256

### ⚠️ Wichtig:
1. **ENCRYPTION_KEY niemals committen** - Muss in .gitignore
2. **Key-Rotation** - Sollte periodisch gewechselt werden (später Feature)
3. **Backup** - Key muss sicher gesichert werden (sonst Datenverlust)

### 🔐 .env Entry:
```env
# Generate with: openssl rand -base64 32
ENCRYPTION_KEY=base64:dGVzdGtleTEyMzQ1Njc4OTAxMjM0NTY3ODkwMTIzNA==
```

---

## 7. Fortschritt

| Task | Status | Dateien | Notizen |
|------|--------|---------|---------|
| 1. Struktur | ✅ Done | module.json, Verzeichnisse | - |
| 2. Interface | ✅ Done | EncryptionInterface.php | 4 methods + PHPDoc |
| 3. Exception | ✅ Done | EncryptionException.php | 4 static factories |
| 4. Service | ✅ Done | EncryptionService.php (220 lines) | AES-256-CBC, OpenSSL |
| 5. Manual Test | ✅ Done | manual-test.php | 10 tests, all passed |
| 6. Composer | ✅ Done | composer.json | Autoloader updated |
| 7. Doku | ✅ Done | README.md | Comprehensive (500+ lines) |

**Status:** ✅ **ERFOLGREICH ABGESCHLOSSEN**

**Legende:**
- 🔴 Todo
- 🟡 In Progress
- ✅ Done
- ⏸️ Blocked

---

## 8. Dependencies

**Benötigt:**
- ✅ Config-Modul (für ENCRYPTION_KEY)
- ✅ Logger-Modul (für Error-Logging, optional)
- ✅ PHP OpenSSL Extension (Core Dependency)

**Check OpenSSL:**
```powershell
C:\xampp\php\php.exe -m | Select-String openssl
```

---

## 9. Offene Fragen / Entscheidungen

### ✅ Entschieden:
- AES-256-CBC verwenden (Standard)
- IV vor Encrypted Data speichern
- Base64-Encoding für DB-Storage
- Key aus .env über Config-Modul

### ❓ Offen:
- Key-Rotation: Später als Feature oder nie? → **Entscheidung:** Später (M5 Security)
- Alternative Cipher? → **Entscheidung:** Nein, AES-256-CBC ist Standard

---

## 8. Lessons Learned

### ✅ Was gut lief:
1. **OpenSSL Integration** - PHP OpenSSL Extension funktioniert perfekt
2. **IV-Handling** - Random IVs pro Verschlüsselung, korrekt base64-encoded
3. **Config Integration** - ConfigService lädt $_ENV korrekt (nach Bugfix)
4. **Exception Design** - Static factories für verschiedene Error-Cases
5. **Format-Design** - `iv::encrypted` ist einfach zu parsen und robust

### 📝 Erkenntnisse:
1. **ConfigService $_ENV Fallback** - Config Module musste erweitert werden um $_ENV zu unterstützen
2. **Base64-Encoding** - Wichtig für DB-Storage und JSON-API Transport
3. **Key Format** - `base64:` Prefix hilft bei Debugging
4. **OPENSSL_RAW_DATA Flag** - Wichtig für korrekte Encryption (sonst wird Output doppelt base64-encoded)
5. **IV-Length** - 16 bytes für AES-256-CBC (automatisch ermittelt mit `openssl_cipher_iv_length()`)

### 🔄 Was verbessert werden könnte:
1. **Key Rotation** - Aktuell nicht unterstützt, Feature für M5
2. **Async Encryption** - Für große Datenmengen, aber für Passwörter nicht nötig
3. **Multiple Ciphers** - Hardcoded auf AES-256-CBC, könnte konfigurierbar sein

### ⚠️ Potenzielle Issues:
1. **Key Change** - Alte Daten werden unlesbar → Migration-Script nötig
2. **OpenSSL Errors** - Müssen ordentlich geloggt werden (Logger-Integration später)
3. **Performance** - Bei vielen gleichzeitigen Encryptions könnte Caching helfen

### 🐛 Bugfixes während Implementation:
1. **ConfigService missing $_ENV** - `getNestedValue()` musste $_ENV-Fallback bekommen
   - **Before:** Nur `$this->config` Array geprüft
   - **After:** Zusätzlich `$_ENV` für top-level keys
   - **Lesson:** Config sollte ENV-Variablen direkt unterstützen

---

## 11. Nächste Schritte nach Abschluss

Nach erfolgreichem Abschluss:
1. ✅ Encryption-Modul in `workflow.md` als erledigt markieren
2. ➡️ Weiter mit M0 Sprint 0.4: Database-Setup (nutzt Encryption für Passwörter!)
3. ✅ WIP-Dokument zu `[COMPLETED] ...` umbenannt

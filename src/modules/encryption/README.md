# CI-Inbox Encryption Module

**Version:** 1.0.0  
**Status:** ✅ Production Ready  
**Dependencies:** Config Module, PHP OpenSSL Extension

---

## Overview

The Encryption Module provides secure **AES-256-CBC encryption/decryption** for sensitive data, primarily IMAP passwords. It uses PHP's OpenSSL extension with random initialization vectors (IVs) per encryption to ensure maximum security.

### Key Features

- ✅ **AES-256-CBC encryption** (industry standard)
- ✅ **Random IV per encryption** (unique ciphertext for same plaintext)
- ✅ **Base64-encoded output** (safe for database storage)
- ✅ **Key management** via Config Module (.env)
- ✅ **Exception-based error handling**
- ✅ **Standalone testable**

### Why This Module?

IMAP passwords and other sensitive data must NEVER be stored in plain text. This module provides a centralized, secure encryption solution that:
- Uses industry-standard AES-256-CBC
- Generates unique ciphertexts (attackers can't detect duplicate passwords)
- Integrates with existing Config Module for key management
- Provides type-safe interface with proper exception handling

---

## Installation

### 1. Ensure OpenSSL Extension

```bash
php -m | grep openssl
# Should output: openssl
```

### 2. Generate Encryption Key

```bash
php -r "echo 'base64:' . base64_encode(random_bytes(32)) . PHP_EOL;"
```

Output example:
```
base64:8D+kaauoN5Xj+k/stMNq9Ba7b4582xsjBmfVHVq66qI=
```

### 3. Add to .env

```env
ENCRYPTION_KEY=base64:YOUR_GENERATED_KEY_HERE
```

⚠️ **CRITICAL:** Never commit this key to version control! Keep it in `.env` (which should be in `.gitignore`).

### 4. Update Composer Autoloader

Already configured in `composer.json`:
```json
"CiInbox\\Modules\\Encryption\\": "src/modules/encryption/src/"
```

Run:
```bash
composer dump-autoload
```

---

## Usage

### Basic Usage

```php
use CiInbox\Modules\Config\ConfigService;
use CiInbox\Modules\Encryption\EncryptionService;

// Create services
$config = new ConfigService(__DIR__);
$encryption = new EncryptionService($config);

// Encrypt a password
$password = 'my_secret_password';
$encrypted = $encryption->encrypt($password);
// Output: "+bIruHpVES9tJKAjVIk0Zw==::nc6FH/hBdDc/MtE9OB5SHw=="

// Store $encrypted in database...

// Later: Decrypt
$decrypted = $encryption->decrypt($encrypted);
// Output: "my_secret_password"
```

### IMAP Password Storage (Real Use Case)

```php
// When user adds IMAP account
$imapPassword = $_POST['imap_password'];
$encryptedPassword = $encryption->encrypt($imapPassword);

// Save to database
$account = new ImapAccount();
$account->password_encrypted = $encryptedPassword;
$account->save();

// When connecting to IMAP
$account = ImapAccount::find($id);
$plainPassword = $encryption->decrypt($account->password_encrypted);

// Use $plainPassword to connect to IMAP server
```

### Exception Handling

```php
use CiInbox\Modules\Encryption\Exceptions\EncryptionException;

try {
    $encrypted = $encryption->encrypt($data);
} catch (EncryptionException $e) {
    // Handle error (e.g., log, return error response)
    error_log('Encryption failed: ' . $e->getMessage());
}
```

---

## API Reference

### `EncryptionInterface`

#### `encrypt(string $data): string`

Encrypts a plain text string using AES-256-CBC.

**Parameters:**
- `$data` (string): Plain text to encrypt (cannot be empty)

**Returns:**
- (string): Encrypted data in format `"base64_iv::base64_encrypted"`

**Throws:**
- `EncryptionException` if encryption fails or data is empty

**Example:**
```php
$encrypted = $encryption->encrypt('Hello World');
// "+bIruHpVES9tJKAjVIk0Zw==::nc6FH/hBdDc/MtE9OB5SHw=="
```

---

#### `decrypt(string $encrypted): string`

Decrypts an encrypted string back to plain text.

**Parameters:**
- `$encrypted` (string): Encrypted data (format: `"iv::encrypted"`)

**Returns:**
- (string): Decrypted plain text

**Throws:**
- `EncryptionException` if decryption fails or format is invalid

**Example:**
```php
$decrypted = $encryption->decrypt('+bIruHpVES9tJKAjVIk0Zw==::nc6FH/hBdDc/MtE9OB5SHw==');
// "Hello World"
```

---

#### `getCipher(): string`

Returns the cipher algorithm used.

**Returns:**
- (string): `"AES-256-CBC"`

**Example:**
```php
echo $encryption->getCipher();
// "AES-256-CBC"
```

---

#### `isKeyValid(): bool`

Verifies if the encryption key is properly configured by performing a test encryption/decryption.

**Returns:**
- (bool): `true` if key works correctly, `false` otherwise

**Example:**
```php
if (!$encryption->isKeyValid()) {
    die('Encryption key is invalid!');
}
```

---

## Encrypted Data Format

Encrypted strings follow this format:

```
<base64_encoded_iv>::<base64_encoded_encrypted_data>
```

**Example:**
```
+bIruHpVES9tJKAjVIk0Zw==::nc6FH/hBdDc/MtE9OB5SHw==
```

**Parts:**
1. **IV (Initialization Vector):** 16 bytes, base64-encoded
2. **Separator:** `::`
3. **Encrypted Data:** Variable length, base64-encoded

### Why Store IV with Encrypted Data?

AES-256-CBC requires a unique IV for each encryption. The IV:
- Must be **random** for each encryption
- Must be **stored** with the encrypted data
- Does **NOT** need to be secret (only the key must be secret)
- Ensures the same plaintext produces different ciphertext

---

## Security Considerations

### ✅ Best Practices

1. **Key Management**
   - Generate key with `openssl rand -base64 32` or PHP's `random_bytes(32)`
   - Store in `.env` file (NOT in code)
   - Never commit key to version control
   - Use `base64:` prefix for clarity

2. **Key Length**
   - Must be exactly 32 bytes (256 bits) for AES-256
   - Module validates key length on initialization

3. **IV Generation**
   - Random IV is generated per encryption using `openssl_random_pseudo_bytes()`
   - Ensures unique ciphertext even for duplicate plaintexts

4. **Secure Transmission**
   - Always use HTTPS when transmitting encrypted data
   - Encrypted data is base64-encoded (safe for JSON/DB)

### ⚠️ Important Notes

1. **Key Rotation**
   - Currently not supported (planned for M5 Security milestone)
   - Changing key will make old encrypted data unreadable
   - Backup key securely before rotation

2. **Performance**
   - Encryption: ~1-5ms per operation
   - Acceptable for IMAP passwords (infrequent operation)
   - NOT suitable for encrypting large files (use streaming)

3. **Database Storage**
   - Encrypted strings are ~50-200 chars (depends on plaintext length)
   - Use `VARCHAR(255)` or `TEXT` column type
   - Index is possible but not recommended (no search benefit)

---

## Testing

### Manual Test

Run the standalone test:

```bash
php src/modules/encryption/tests/manual-test.php
```

**Expected Output:**
```
=== CI-Inbox Encryption Module - Manual Test ===

1. Creating ConfigService and EncryptionService...
   ✅ EncryptionService created
   Cipher: AES-256-CBC

2. Testing basic encryption...
   ✅ Encryption works

3. Testing decryption...
   ✅ Decryption works

[... 10 tests total ...]

===========================================
✅ ALL TESTS PASSED
===========================================
```

### Test Coverage

- ✅ Basic encryption/decryption
- ✅ Round-trip verification
- ✅ Special characters (UTF-8, emojis)
- ✅ IMAP password use case
- ✅ Unique IVs per encryption
- ✅ Key validation
- ✅ Exception handling (invalid format, empty string)

---

## Troubleshooting

### Error: "OpenSSL extension not loaded"

**Solution:** Enable OpenSSL in `php.ini`:
```ini
extension=openssl
```

Restart PHP/Apache.

---

### Error: "Invalid encryption key configured: Key must be 32 bytes"

**Solution:** Generate a valid 32-byte key:
```bash
php -r "echo 'base64:' . base64_encode(random_bytes(32)) . PHP_EOL;"
```

Update `.env`:
```env
ENCRYPTION_KEY=base64:YOUR_NEW_KEY_HERE
```

---

### Error: "Required configuration key 'ENCRYPTION_KEY' is missing"

**Solution:** Ensure `ENCRYPTION_KEY` is in `.env` and the file is loaded:
```php
// In your bootstrap/entry point
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
```

---

### Error: "Decryption operation failed"

**Possible causes:**
1. **Wrong encryption key** - Encrypted with different key
2. **Corrupted data** - Data was modified after encryption
3. **Invalid format** - Missing `::` separator

**Solution:** Verify key hasn't changed and data wasn't modified.

---

## Module Information

### Module Manifest (`module.json`)

```json
{
  "name": "encryption",
  "version": "1.0.0",
  "type": "core",
  "dependencies": {
    "modules": ["config"]
  },
  "hooks": {
    "onAppInit": {
      "priority": 20
    }
  }
}
```

### Dependencies

- **Config Module** (required): Provides encryption key from `.env`
- **PHP OpenSSL Extension** (required): Core encryption functions
- **Logger Module** (optional): Not used yet, but recommended for production

### Files

```
src/modules/encryption/
├── module.json                     # Module manifest
├── src/
│   ├── EncryptionInterface.php     # Public interface (DI)
│   ├── EncryptionService.php       # Implementation (220 lines)
│   └── Exceptions/
│       └── EncryptionException.php # Custom exceptions
├── tests/
│   └── manual-test.php             # Standalone test (10 tests)
└── README.md                       # This file
```

---

## Performance Metrics

From manual tests (PHP 8.2.12, XAMPP):

| Operation | Time | Memory |
|-----------|------|--------|
| Encrypt (11 chars) | < 1ms | < 100 KB |
| Decrypt (50 chars) | < 1ms | < 100 KB |
| Key validation | < 5ms | < 200 KB |

**Conclusion:** Suitable for IMAP password encryption (infrequent operation).

---

## Future Enhancements (M5 Security)

Planned features (not implemented yet):

1. **Key Rotation**
   - Command to generate new key
   - Decrypt with old key, re-encrypt with new key
   - Migration script for existing data

2. **Key Derivation**
   - Use PBKDF2/Argon2 for key derivation
   - Store salt alongside encrypted data

3. **Audit Logging**
   - Log all encrypt/decrypt operations
   - Integration with Logger module

4. **Multiple Keys**
   - Support different keys per tenant (multi-tenancy)
   - Key ID prefix in encrypted data

---

## Changelog

### Version 1.0.0 (2025-11-17)

- ✅ Initial implementation
- ✅ AES-256-CBC encryption with OpenSSL
- ✅ Random IV per encryption
- ✅ Base64-encoded output format
- ✅ Config Module integration
- ✅ Exception-based error handling
- ✅ Comprehensive manual tests (10 tests)
- ✅ Full documentation

---

## License

Part of CI-Inbox project. See main project LICENSE.

---

## Support

For issues or questions:
1. Check Troubleshooting section above
2. Review manual test output
3. Check logs in `logs/app-YYYY-MM-DD.log`
4. Review Config Module documentation (for key loading issues)

---

**Module Status:** ✅ **PRODUCTION READY**  
**Last Updated:** 2025-11-17  
**Author:** CI-Inbox Team

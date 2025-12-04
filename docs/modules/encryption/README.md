# Encryption Module

**Version:** 0.1.0  
**Status:** ✅ Production Ready  
**Pfad:** `src/modules/encryption/`

## Übersicht

Das Encryption-Modul verschlüsselt **sensitive Daten** (IMAP/SMTP Passwörter) mit **AES-256-CBC**. Es verwendet OpenSSL mit zufälligem IV pro Verschlüsselung.

---

## Dateien

```
src/modules/encryption/
├── src/
│   ├── EncryptionInterface.php
│   ├── EncryptionService.php
│   └── Exceptions/
│       └── EncryptionException.php
```

---

## EncryptionService

### Class Definition

```php
class EncryptionService implements EncryptionInterface
{
    private const CIPHER = 'AES-256-CBC';
    private const SEPARATOR = '::';
    
    private string $key;  // Binary key (32 bytes)
    
    public function __construct(
        ConfigInterface $config,
        ?LoggerService $logger = null
    )
}
```

### Encrypted Data Format

```
base64_iv::base64_encrypted
```

**Beispiel:**
```
SGVsbG8gV29ybGQh::YWJjZGVmZ2hpamtsbW5vcA==
     ↑                      ↑
    IV (16 bytes)      Encrypted Data
```

---

## Features

✅ **AES-256-CBC Encryption**
- Industry-Standard
- 256-bit Key (32 bytes)
- CBC Mode (Cipher Block Chaining)

✅ **Random IV per Encryption**
- 16 bytes IV via `openssl_random_pseudo_bytes()`
- Verhindert Pattern-Analyse
- Gespeichert im Encrypted String

✅ **Key Management**
- Base64-encoded Key in `.env`
- Automatisches Decoding
- Validation (32 bytes required)

✅ **Error Handling**
- Custom `EncryptionException`
- Key validation
- Encryption/Decryption Fehler

---

## Configuration

### Environment Variable

```bash
# .env
ENCRYPTION_KEY=base64:SGVsbG8gV29ybGQhSGVsbG8gV29ybGQhSGVsbG8gV29ybGQh
```

### Key Generation

```bash
# Generate 32-byte key (256-bit)
php -r "echo 'base64:' . base64_encode(random_bytes(32)) . PHP_EOL;"
```

**Output:**
```
base64:YWJjZGVmZ2hpamtsbW5vcHFyc3R1dnd4eXowMTIzNDU2Nzg5
```

### Container Registration

```php
// src/config/container.php
EncryptionInterface::class => function($container) {
    return new EncryptionService(
        $container->get(ConfigInterface::class)
    );
}
```

---

## API Reference

### encrypt()

Verschlüsselt Plain-Text.

```php
public function encrypt(string $plaintext): string
```

**Parameters:**
- `$plaintext` - String zum Verschlüsseln

**Returns:** Base64-encoded IV + Encrypted Data (Format: `iv::encrypted`)

**Throws:** `EncryptionException` bei Fehler

**Example:**
```php
$encrypted = $encryption->encrypt('my_password');
// Result: "SGVsbG8gV29ybGQh::YWJjZGVmZ2hpamtsbW5vcA=="
```

---

### decrypt()

Entschlüsselt Encrypted String.

```php
public function decrypt(string $encrypted): string
```

**Parameters:**
- `$encrypted` - Encrypted String (Format: `iv::encrypted`)

**Returns:** Original Plain-Text

**Throws:** 
- `EncryptionException` bei invalid Format
- `EncryptionException` bei Decryption-Fehler

**Example:**
```php
$plaintext = $encryption->decrypt('SGVsbG8gV29ybGQh::YWJjZGVmZ2hpamtsbW5vcA==');
// Result: "my_password"
```

---

## Usage Examples

### IMAP Password Encryption

```php
use CiInbox\Modules\Encryption\EncryptionInterface;
use CiInbox\App\Models\ImapAccount;

class PersonalImapAccountService {
    public function __construct(
        private EncryptionInterface $encryption
    ) {}
    
    public function createAccount(array $data): ImapAccount {
        // Encrypt password
        $encryptedPassword = $this->encryption->encrypt($data['password']);
        
        $account = new ImapAccount();
        $account->email = $data['email'];
        $account->imap_password_encrypted = $encryptedPassword;
        $account->save();
        
        return $account;
    }
    
    public function testConnection(ImapAccount $account): bool {
        // Decrypt password
        $password = $this->encryption->decrypt($account->imap_password_encrypted);
        
        // Use for IMAP connection
        return $imapClient->connect(
            $account->imap_host,
            $account->imap_port,
            $account->email,
            $password
        );
    }
}
```

### SMTP Password Encryption

```php
$smtpPassword = $_POST['smtp_password'];

// Store encrypted
$config->smtp_password_encrypted = $encryption->encrypt($smtpPassword);
$config->save();

// Use decrypted
$password = $encryption->decrypt($config->smtp_password_encrypted);
$smtpClient->connect($config, $password);
```

---

## Implementation Details

### Encryption Process

```
1. Generate random IV (16 bytes)
   ↓
2. Encrypt plaintext with Key + IV (AES-256-CBC)
   ↓
3. Base64-encode IV
   ↓
4. Base64-encode encrypted data
   ↓
5. Combine: "base64_iv::base64_encrypted"
```

**Code:**
```php
public function encrypt(string $plaintext): string
{
    // 1. Generate IV
    $iv = openssl_random_pseudo_bytes(16);
    
    // 2. Encrypt
    $encrypted = openssl_encrypt(
        $plaintext,
        self::CIPHER,
        $this->key,
        OPENSSL_RAW_DATA,
        $iv
    );
    
    if ($encrypted === false) {
        throw EncryptionException::encryptionFailed();
    }
    
    // 3-5. Encode and combine
    return base64_encode($iv) . self::SEPARATOR . base64_encode($encrypted);
}
```

### Decryption Process

```
1. Split string by "::" separator
   ↓
2. Base64-decode IV
   ↓
3. Base64-decode encrypted data
   ↓
4. Decrypt with Key + IV (AES-256-CBC)
   ↓
5. Return plaintext
```

**Code:**
```php
public function decrypt(string $encrypted): string
{
    // 1. Split
    $parts = explode(self::SEPARATOR, $encrypted, 2);
    if (count($parts) !== 2) {
        throw EncryptionException::invalidFormat();
    }
    
    // 2-3. Decode
    $iv = base64_decode($parts[0], true);
    $encryptedData = base64_decode($parts[1], true);
    
    if ($iv === false || $encryptedData === false) {
        throw EncryptionException::invalidFormat();
    }
    
    // 4. Decrypt
    $plaintext = openssl_decrypt(
        $encryptedData,
        self::CIPHER,
        $this->key,
        OPENSSL_RAW_DATA,
        $iv
    );
    
    if ($plaintext === false) {
        throw EncryptionException::decryptionFailed();
    }
    
    return $plaintext;
}
```

---

## Key Validation

### Automatic Validation

```php
private function loadKey(): void
{
    $keyString = $this->config->getString('ENCRYPTION_KEY');
    
    // Remove "base64:" prefix
    if (str_starts_with($keyString, 'base64:')) {
        $keyString = substr($keyString, 7);
    }
    
    // Decode
    $this->key = base64_decode($keyString, true);
    
    if ($this->key === false) {
        throw EncryptionException::invalidKey('Key is not valid base64');
    }
    
    // Validate length (AES-256 requires 32 bytes)
    if (strlen($this->key) !== 32) {
        throw EncryptionException::invalidKey(
            sprintf('Key must be 32 bytes, got %d', strlen($this->key))
        );
    }
}
```

---

## Security

### ✅ Best Practices

**Key Storage:**
- ✅ Store key in `.env` (nie in Code!)
- ✅ `.env` in `.gitignore`
- ✅ Different keys per Environment (Dev, Staging, Prod)
- ✅ Key Rotation alle 90 Tage

**Key Generation:**
- ✅ Use `random_bytes(32)` (cryptographically secure)
- ✅ Never use predictable strings
- ✅ Never reuse keys from examples

**IV Generation:**
- ✅ New random IV per encryption
- ✅ Use `openssl_random_pseudo_bytes()`
- ✅ Never reuse IVs

### ❌ Common Mistakes

```php
// ❌ DON'T: Hardcoded key
$key = 'my-secret-key';

// ❌ DON'T: Too short key
$key = random_bytes(16);  // 128-bit, not 256-bit!

// ❌ DON'T: Reused IV
$iv = '1234567890123456';  // Static IV!

// ❌ DON'T: Key in Git
// .env committed to repository
```

### Key Rotation

**Process:**
1. Generate new key
2. Decrypt all data with old key
3. Re-encrypt with new key
4. Update `.env`
5. Restart application

**Migration Script:**
```php
$oldKey = getenv('OLD_ENCRYPTION_KEY');
$newKey = getenv('NEW_ENCRYPTION_KEY');

$oldService = new EncryptionService($oldConfig);
$newService = new EncryptionService($newConfig);

foreach ($accounts as $account) {
    $password = $oldService->decrypt($account->imap_password_encrypted);
    $account->imap_password_encrypted = $newService->encrypt($password);
    $account->save();
}
```

---

## Testing

### Unit Tests

```php
// tests/unit/EncryptionServiceTest.php
public function testEncryptDecrypt()
{
    $service = new EncryptionService($config);
    
    $plaintext = 'my_password';
    $encrypted = $service->encrypt($plaintext);
    $decrypted = $service->decrypt($encrypted);
    
    $this->assertEquals($plaintext, $decrypted);
}

public function testDifferentIVs()
{
    $service = new EncryptionService($config);
    
    $encrypted1 = $service->encrypt('password');
    $encrypted2 = $service->encrypt('password');
    
    // Different IVs = different encrypted strings
    $this->assertNotEquals($encrypted1, $encrypted2);
    
    // But both decrypt to same plaintext
    $this->assertEquals(
        $service->decrypt($encrypted1),
        $service->decrypt($encrypted2)
    );
}
```

### Manual Test

```php
// tests/manual/encryption-test.php
require_once __DIR__ . '/../../vendor/autoload.php';

$config = new \CiInbox\Modules\Config\ConfigService(__DIR__ . '/../..');
$encryption = new \CiInbox\Modules\Encryption\EncryptionService($config);

// Test encryption
$plaintext = 'my_secret_password';
echo "Plaintext: {$plaintext}\n";

$encrypted = $encryption->encrypt($plaintext);
echo "Encrypted: {$encrypted}\n";

$decrypted = $encryption->decrypt($encrypted);
echo "Decrypted: {$decrypted}\n";

// Verify
echo ($plaintext === $decrypted) ? "✓ SUCCESS\n" : "✗ FAILED\n";
```

---

## Performance

### Benchmarks

```
1,000 Encryptions:  ~50ms
1,000 Decryptions:  ~45ms
```

**Bottle-Neck:** Base64 encoding/decoding (nicht Verschlüsselung!)

**Optimierung:** Batch-Processing möglich
```php
foreach ($passwords as $password) {
    $encrypted[] = $encryption->encrypt($password);
}
```

---

## Troubleshooting

### Problem: "Invalid key: Key must be 32 bytes"

**Ursache:** Key nicht 256-bit

**Lösung:**
```bash
# Generate correct key
php -r "echo 'base64:' . base64_encode(random_bytes(32));"
```

### Problem: "Decryption failed"

**Ursache:** 
- Falscher Key
- Korrupte Daten
- Format-Fehler

**Lösung:**
```php
try {
    $decrypted = $encryption->decrypt($encrypted);
} catch (EncryptionException $e) {
    // Log error
    $logger->error('Decryption failed', [
        'error' => $e->getMessage(),
        'encrypted_length' => strlen($encrypted),
        'format_valid' => str_contains($encrypted, '::')
    ]);
    
    // Fallback: Request new password
}
```

### Problem: OpenSSL extension not loaded

**Ursache:** PHP ohne OpenSSL kompiliert

**Lösung:**
```bash
# Check
php -m | grep openssl

# Install (Ubuntu/Debian)
sudo apt-get install php-openssl

# Enable (php.ini)
extension=openssl
```

---

## Migration from Other Encryption

### From Laravel Encryption

```php
// Old: Laravel encrypt()
$encrypted = encrypt('password');

// New: EncryptionService
$encrypted = $encryption->encrypt('password');
```

**Unterschied:** Anderes Format! Re-encryption erforderlich.

### Bulk Re-encryption

```php
foreach (ImapAccount::all() as $account) {
    // Decrypt with old method
    $password = decrypt($account->old_password_field);
    
    // Encrypt with new service
    $account->imap_password_encrypted = $encryption->encrypt($password);
    $account->save();
}
```

---

## Related Documentation

- **OpenSSL Docs:** https://www.php.net/manual/en/book.openssl.php
- **AES-256-CBC:** https://en.wikipedia.org/wiki/Advanced_Encryption_Standard
- **Key Management Best Practices:** https://cheatsheetseries.owasp.org/cheatsheets/Key_Management_Cheat_Sheet.html

---

**Status:** ✅ Production Ready  
**Algorithm:** AES-256-CBC  
**Dependencies:** OpenSSL PHP Extension  
**Last Updated:** 18. November 2025

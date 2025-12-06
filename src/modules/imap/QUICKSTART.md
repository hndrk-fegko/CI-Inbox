# IMAP Module - Quick Start Guide

**For:** New Developers / Contributors  
**Module:** `src/modules/imap/`  
**Date:** 17. November 2025

---

## 🚀 5-Minute Setup

### 1. Prerequisites

```bash
# Check PHP version (need 8.1+)
php --version

# Check IMAP extension
php -m | grep imap
```

**If IMAP missing:**
- **Linux:** `sudo apt-get install php-imap && sudo service apache2 restart`
- **Windows (XAMPP):** Uncomment `extension=imap` in `php.ini`

### 2. Test IMAP Module

**Quick Test with Mercury (XAMPP):**

```bash
cd C:\Users\Dienstlaptop-HD\Documents\Privat-Nextcloud\Private_Dateien\Tools_und_Systeme\CI-Inbox

# Run quick test
C:\xampp\php\php.exe src/modules/imap/tests/mercury-quick-test.php
```

**Expected Output:**
```
✅ ALL TESTS PASSED
🎉 Mercury Configuration is CORRECT!
```

### 3. Use in Your Code

```php
use CiInbox\Modules\Imap\ImapClient;
use CiInbox\Modules\Logger\LoggerService;
use CiInbox\Modules\Config\ConfigService;

// Initialize dependencies
$logger = new LoggerService(__DIR__ . '/logs');
$config = new ConfigService(__DIR__);

// Create IMAP client
$imap = new ImapClient($logger, $config);

// Connect
$imap->connect('imap.example.com', 993, 'user@example.com', 'password', true);

// Get messages
$messages = $imap->getMessages(10);

foreach ($messages as $msg) {
    echo $msg->getSubject() . "\n";
}

// Disconnect
$imap->disconnect();
```

---

## 📚 Documentation

### Module Documentation
- **README.md** - Module overview, API reference
- **tests/README.md** - All test scripts

### Project Documentation
- **docs/dev/Setup-Autodiscover.md** - Setup wizard details
- **docs/dev/Mercury-Setup.md** - Mercury configuration
- **docs/dev/M1-Preparation.md** - M1 milestone info

---

## 🧪 Testing Workflow

### Development Testing (Mercury)

1. **Start Mercury** (XAMPP Control Panel)
2. **Run quick test:**
   ```bash
   php src/modules/imap/tests/mercury-quick-test.php
   ```
3. **Check output:** `mercury-config.json`

### Production Testing (Real Provider)

1. **Run setup wizard:**
   ```bash
   php src/modules/imap/tests/setup-autodiscover.php
   ```
2. **Enter credentials** (Gmail, Outlook, etc.)
3. **Check output:** `.env` + `setup-config.json`

### Manual Testing (Custom Config)

1. **Run round-trip test:**
   ```bash
   php src/modules/imap/tests/smtp-imap-roundtrip-test.php
   ```
2. **Enter custom SMTP/IMAP settings**

---

## 🔧 Common Issues

### "IMAP extension not available"

**Fix:**
```bash
# Linux
sudo apt-get install php8.2-imap
sudo service apache2 restart

# Windows (XAMPP)
# Edit php.ini and uncomment:
extension=imap
# Restart Apache
```

### "Connection refused"

**Check:**
1. Server running? (Mercury: XAMPP Control Panel)
2. Port open? `netstat -an | findstr :143`
3. Firewall blocking?

### "Authentication failed"

**Check:**
1. Credentials correct?
2. Username format (with/without @domain)?
3. App password needed? (Gmail with 2FA)

---

## 🏗️ Module Structure

```
src/modules/imap/
├── src/                         # Source code
│   ├── ImapClient.php           # Main client (473 lines)
│   ├── ImapMessage.php          # Message object (520 lines)
│   ├── *Interface.php           # Interfaces
│   └── Exceptions/              # Custom exceptions
├── config/                      # Configuration
│   └── imap.config.php
├── tests/                       # Test scripts
│   ├── setup-autodiscover.php   # ⭐ Setup wizard
│   ├── mercury-quick-test.php   # Mercury testing
│   ├── smtp-imap-roundtrip-test.php
│   ├── README.md
│   └── _archive/                # Old scripts
├── module.json                  # Module manifest
└── README.md                    # Module documentation
```

---

## 📖 Next Steps

1. ✅ **Read:** `README.md` - Full API documentation
2. ✅ **Read:** `tests/README.md` - Test script overview
3. ✅ **Test:** Run `mercury-quick-test.php`
4. ✅ **Code:** Use ImapClient in your feature
5. ✅ **Document:** Update relevant docs if adding features

---

## 🆘 Getting Help

**Documentation:**
- Module README: `src/modules/imap/README.md`
- Project Docs: `docs/dev/`

**Testing:**
- Test Scripts: `src/modules/imap/tests/`
- Mercury Setup: `docs/dev/Mercury-Setup.md`

**Code Reference:**
- Interfaces: `src/modules/imap/src/*Interface.php`
- Examples: Test scripts in `tests/`

---

**Welcome to CI-Inbox IMAP Module! 🚀**

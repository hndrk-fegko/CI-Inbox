# IMAP Client Module

**Version:** 1.0.0  
**Status:** ✅ M1 Sprint 1.1 Complete  
**Dependencies:** Logger, Config  
**Last Updated:** 17. November 2025

PHP wrapper for `php-imap` extension with clean OOP interface.

---

## 🚀 Quick Start

**New to this module?** → See **[QUICKSTART.md](QUICKSTART.md)** for 5-minute setup guide.

**Testing?** → See **[tests/README.md](tests/README.md)** for all test scripts.

---

## Features

- ✅ **Connection Management** - Connect/disconnect from IMAP servers
- ✅ **Folder Operations** - List, select folders
- ✅ **Message Retrieval** - Fetch messages with lazy loading
- ✅ **Message Operations** - Mark as read/unread, move, delete
- ✅ **Threading Support** - Message-ID, In-Reply-To, References headers
- ✅ **Attachment Handling** - List attachments with metadata
- ✅ **Error Handling** - Custom exceptions with IMAP error details
- ✅ **Logging** - Full operation logging via LoggerService

---

## Requirements

- PHP 8.1+
- php-imap extension
- LoggerService (CI-Inbox)
- ConfigService (CI-Inbox)

### Install IMAP Extension

**Linux (Ubuntu/Debian):**
```bash
sudo apt-get install php-imap
sudo service apache2 restart
```

**macOS:**
```bash
brew install php
# IMAP usually included
```

**Windows (XAMPP):**
Edit `php.ini` and uncomment:
```ini
extension=imap
```

---

## Installation

Module is located at: `src/modules/imap/`

**Configuration:**
Copy IMAP config to your config directory:
```bash
cp src/modules/imap/config/imap.config.php src/config/
```

Or load in ConfigService:
```php
$config->load('imap', __DIR__ . '/../modules/imap/config/imap.config.php');
```

---

## Usage

### Basic Connection

```php
use CiInbox\Modules\Imap\ImapClient;
use CiInbox\Modules\Imap\Exceptions\ImapException;

// Initialize (via DI Container)
$client = $container->get(ImapClient::class);

// Or manual initialization
$client = new ImapClient($logger, $config);

// Connect
try {
    $client->connect(
        host: 'imap.gmail.com',
        port: 993,
        username: 'user@example.com',
        password: 'your_password',
        ssl: true
    );
    
    echo "Connected!\n";
} catch (ImapException $e) {
    echo "Connection failed: " . $e->getMessage();
}
```

### List Folders

```php
$folders = $client->getFolders();

foreach ($folders as $folder) {
    echo "- {$folder}\n";
}

// Output:
// - INBOX
// - Sent
// - Drafts
// - Trash
```

### Select Folder & Get Messages

```php
// Select folder
$client->selectFolder('INBOX');

// Get message count
$count = $client->getMessageCount();
echo "Total messages: {$count}\n";

// Fetch last 10 messages
$messages = $client->getMessages(limit: 10, unreadOnly: false);

foreach ($messages as $message) {
    echo "UID: " . $message->getUid() . "\n";
    echo "Subject: " . $message->getSubject() . "\n";
    echo "From: " . $message->getFrom()['email'] . "\n";
    echo "Date: " . $message->getDate()->format('Y-m-d H:i:s') . "\n";
    echo "---\n";
}
```

### Get Single Message

```php
$message = $client->getMessage('12345'); // UID

// Basic info
echo "Subject: " . $message->getSubject() . "\n";
echo "Message-ID: " . $message->getMessageId() . "\n";

// Sender
$from = $message->getFrom();
echo "From: {$from['name']} <{$from['email']}>\n";

// Recipients
$to = $message->getTo();
foreach ($to as $recipient) {
    echo "To: {$recipient['name']} <{$recipient['email']}>\n";
}

// Threading
echo "In-Reply-To: " . $message->getInReplyTo() . "\n";
$references = $message->getReferences();
echo "References: " . count($references) . "\n";

// Body
echo "\nPlain Text:\n";
echo $message->getBodyText();

echo "\n\nHTML:\n";
echo $message->getBodyHtml();

// Attachments
if ($message->hasAttachments()) {
    $attachments = $message->getAttachments();
    echo "\n\nAttachments:\n";
    foreach ($attachments as $attachment) {
        echo "- {$attachment['filename']} ({$attachment['size']} bytes, {$attachment['mime_type']})\n";
    }
}

// Status
echo "\nUnread: " . ($message->isUnread() ? 'Yes' : 'No') . "\n";
echo "Flagged: " . ($message->isFlagged() ? 'Yes' : 'No') . "\n";
```

### Message Operations

```php
// Mark as read
$client->markAsRead('12345');

// Mark as unread
$client->markAsUnread('12345');

// Move to another folder
$client->moveMessage('12345', 'Archive');

// Delete (move to Trash)
$client->deleteMessage('12345');
```

### Disconnect

```php
$client->disconnect();
```

**Note:** Client automatically disconnects on destruction, but explicit disconnect is recommended.

---

## Threading Support

The module extracts threading headers for email conversation grouping:

```php
$message = $client->getMessage('12345');

// Message-ID (unique identifier)
$messageId = $message->getMessageId();
// Output: "<abc123@mail.gmail.com>"

// In-Reply-To (parent message)
$inReplyTo = $message->getInReplyTo();
// Output: "<xyz789@mail.gmail.com>" or null

// References (thread ancestors)
$references = $message->getReferences();
// Output: ["<msg1@...>", "<msg2@...>", "<msg3@...>"]
```

**Use Case:** Build conversation threads based on these headers (M1 Sprint 1.3).

---

## Error Handling

All IMAP errors throw `ImapException`:

```php
use CiInbox\Modules\Imap\Exceptions\ImapException;

try {
    $client->connect('invalid.host', 993, 'user', 'pass');
} catch (ImapException $e) {
    echo "Error: " . $e->getMessage();
    // Includes IMAP-specific error details
}
```

**Common Exceptions:**
- `connectionFailed()` - Failed to connect
- `notConnected()` - Operation requires connection
- `folderNotFound()` - Folder doesn't exist
- `noFolderSelected()` - No folder selected
- `messageNotFound()` - Message UID invalid
- `extensionNotAvailable()` - php-imap not installed

---

## Configuration

**Config File:** `src/modules/imap/config/imap.config.php`

```php
return [
    'default' => [
        'host' => env('IMAP_HOST', 'imap.gmail.com'),
        'port' => env('IMAP_PORT', 993),
        'encryption' => env('IMAP_ENCRYPTION', 'ssl'),
    ],
    'timeout' => env('IMAP_TIMEOUT', 30),
    'fetch_limit' => env('IMAP_FETCH_LIMIT', 100),
    'folders' => [
        'inbox' => 'INBOX',
        'sent' => 'Sent',
        'trash' => 'Trash',
    ],
];
```

**Environment Variables (.env):**
```env
IMAP_HOST=imap.gmail.com
IMAP_PORT=993
IMAP_ENCRYPTION=ssl
IMAP_TIMEOUT=30
IMAP_FETCH_LIMIT=100
```

---

## Testing

### Manual Test

Interactive test with real IMAP server:

```bash
php src/modules/imap/tests/manual-test.php
```

**Test Coverage:**
1. ✅ IMAP extension check
2. ✅ Connect to IMAP server
3. ✅ Fetch folders
4. ✅ Select INBOX
5. ✅ Get message count
6. ✅ Fetch messages
7. ✅ Get single message
8. ✅ Message operations (read, move)
9. ✅ Disconnect

---

## Gmail-Specific Notes

Gmail uses non-standard folder names:

```php
// Standard IMAP
$client->selectFolder('Sent');

// Gmail
$client->selectFolder('[Gmail]/Sent Mail');
```

**Gmail Folders:**
- `INBOX` - Inbox
- `[Gmail]/Sent Mail` - Sent
- `[Gmail]/Trash` - Trash
- `[Gmail]/Drafts` - Drafts
- `[Gmail]/Spam` - Spam
- `[Gmail]/All Mail` - Archive

**Tip:** Use `gmail_folders` config for Gmail accounts.

---

## Performance

- **Connection:** ~1-2 seconds (depends on server)
- **Fetch 100 messages:** ~2-5 seconds
- **Fetch single message:** ~50-200ms (with lazy loading)
- **Mark as read:** ~50-100ms

**Optimization Tips:**
1. Reuse connection for multiple operations
2. Fetch messages in batches (limit parameter)
3. Use `unreadOnly` flag to reduce fetch size
4. Bodies and attachments are lazy-loaded (only when accessed)

---

## Architecture

**Interface-First Design:**
- `ImapClientInterface` - Main IMAP client contract
- `ImapMessageInterface` - Message object contract

**Implementation:**
- `ImapClient` - Wraps php-imap functions
- `ImapMessage` - Represents single email (lazy loading)
- `ImapException` - IMAP-specific exceptions

**Dependencies:**
- `LoggerService` - Operation logging
- `ConfigService` - Configuration management

---

## Roadmap

- ✅ **M1 Sprint 1.1** - IMAP Client Module
- 🔴 **M1 Sprint 1.2** - E-Mail Parser (body sanitization, attachment extraction)
- 🔴 **M1 Sprint 1.3** - Threading Engine (group messages by conversation)
- 🔴 **M1 Sprint 1.4** - Webcron Service (automated polling)

---

## License

---

## Setup & Testing Tools

### Auto-Discovery Setup Wizard

**File:** `tests/setup-autodiscover.php`

Intelligent setup wizard with automatic SMTP/IMAP configuration detection.

**Features:**
- ✅ Auto-detect IMAP server from email domain
- ✅ Auto-test 8 SMTP configurations (with/without auth)
- ✅ Scan all IMAP folders for test message (filter-compatible)
- ✅ Save configuration to .env + JSON

**Usage:**
```bash
php tests/setup-autodiscover.php
```

**See:** `tests/README.md` for detailed documentation

### Quick Test Scripts

**Mercury Quick Test** (Development):
```bash
php tests/mercury-quick-test.php
```

**Round-Trip Test** (Any Provider):
```bash
php tests/smtp-imap-roundtrip-test.php
```

**See:** `tests/README.md` for all available test scripts

---

## Module Structure

```
src/modules/imap/
├── src/
│   ├── ImapClientInterface.php      # Main interface
│   ├── ImapClient.php               # Implementation
│   ├── ImapMessageInterface.php     # Message interface
│   ├── ImapMessage.php              # Message implementation
│   └── Exceptions/
│       └── ImapException.php        # Custom exceptions
├── config/
│   └── imap.config.php              # Default configuration
├── tests/
│   ├── setup-autodiscover.php       # ⭐ Setup wizard
│   ├── mercury-quick-test.php       # Mercury testing
│   ├── smtp-imap-roundtrip-test.php # Generic testing
│   └── README.md                    # Test documentation
├── module.json                      # Module manifest
└── README.md                        # This file
```

---

## Related Documentation

**Project-Level:**
- `docs/dev/Setup-Autodiscover.md` - Setup wizard documentation
- `docs/dev/Mercury-Setup.md` - Mercury server configuration
- `docs/dev/M1-Preparation.md` - M1 milestone preparation

**Module-Level:**
- `tests/README.md` - All test scripts documentation

---

## License

Part of CI-Inbox project.

**Last Updated:** 17. November 2025

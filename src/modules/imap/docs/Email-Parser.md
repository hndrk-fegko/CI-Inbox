# Email Parser Module

The Email Parser module extracts and sanitizes email content from IMAP messages.

## Features

✅ **Header Parsing**
- Message-ID extraction (Mercury-compatible)
- From/To/Cc/Bcc addresses  
- Date with timezone handling
- Threading headers (In-Reply-To, References)

✅ **Body Parsing**
- Plain text bodies
- HTML bodies with XSS protection
- Multipart message handling
- Charset conversion to UTF-8

✅ **Attachment Extraction**
- Robust detection (filename + disposition)
- Metadata (name, size, MIME type)
- Inline vs. attachment detection
- Handles missing Content-Disposition headers

✅ **Sanitization**
- HTML Purifier integration (XSS protection)
- Text cleanup (control chars, linebreaks)
- Quote stripping, URL extraction

✅ **Threading**
- Message-ID chain extraction
- Thread-ID calculation
- Reply detection

## Architecture

```
Parser/
├── EmailParser.php          # Main orchestrator
├── HeaderParser.php         # Parse headers
├── BodyParser.php           # Parse text/HTML bodies
├── AttachmentParser.php     # Extract attachments
├── ThreadingParser.php      # Extract threading info
├── ParsedEmail.php          # Result data object
├── Attachment.php           # Attachment data object
└── ThreadingInfo.php        # Threading data object

Sanitizer/
├── HtmlSanitizer.php        # XSS protection (HTML Purifier)
└── TextSanitizer.php        # Text cleanup
```

## Usage

### Basic Parsing

```php
use CiInbox\Modules\Imap\Parser\EmailParser;
use CiInbox\Modules\Imap\ImapClient;

$parser = new EmailParser();
$message = $imap->getMessage($uid);
$parsed = $parser->parseMessage($message);

// Access data
echo $parsed->subject;
echo $parsed->from;
echo $parsed->bodyText;
echo $parsed->bodyHtml; // Sanitized!

// Attachments
foreach ($parsed->attachments as $attachment) {
    echo "{$attachment->filename} ({$attachment->getFormattedSize()})\n";
}

// Threading
if ($parsed->threadingInfo->isReply()) {
    echo "This is a reply to: {$parsed->threadingInfo->getParentId()}\n";
}
```

### Array Export

```php
$data = $parsed->toArray();
// Returns:
// [
//   'message_id' => '...',
//   'subject' => '...',
//   'from' => '...',
//   'to' => [...],
//   'cc' => [...],
//   'bcc' => [...],
//   'date' => '2025-11-17 19:24:26',
//   'body_text' => '...',
//   'body_html' => '...', // Sanitized
//   'attachments' => [...],
//   'threading' => [...],
//   'headers' => [...]
// ]
```

### Unsanitized Parsing

```php
// For debugging or raw display (iframe)
$parsed = $parser->parseMessageUnsanitized($message);
```

## Components

### EmailParser

Main parser that orchestrates all components:

```php
interface EmailParserInterface {
    public function parseMessage(ImapMessage $message): ParsedEmail;
    public function parseRawEmail(string $rawEmail): ParsedEmail;
}
```

**Dependencies:**
- HeaderParser
- BodyParser
- AttachmentParser
- ThreadingParser
- HtmlSanitizer
- TextSanitizer

### HeaderParser

Extracts all headers including Mercury-specific workarounds:

```php
$headers = $headerParser->parseHeaders($message);
// Returns: [
//   'message_id' => 'test@example.com',
//   'subject' => 'Test',
//   'from' => 'user@example.com',
//   'to' => ['recipient@example.com'],
//   'cc' => [],
//   'bcc' => [],
//   'date' => DateTime object,
//   'in_reply_to' => 'parent@example.com',
//   'references' => ['oldest@example.com', ...]
// ]
```

**Mercury Compatibility:**
- Message-ID extracted from raw headers (not available via getMessageId())
- Fallback to generated ID if missing

### BodyParser

Extracts text and HTML bodies:

```php
$body = $bodyParser->parseBody($message);
// Returns: ['text' => '...', 'html' => '...']
```

**Features:**
- Uses ImapMessage's built-in methods (already handles encoding/charset)
- Returns null for missing bodies
- UTF-8 guaranteed

### AttachmentParser

Robust attachment extraction with fallback:

```php
$attachments = $attachmentParser->parseAttachments($message);
// Returns: Attachment[]
```

**Detection Strategy:**
1. Try ImapMessage->getAttachments() (checks disposition)
2. Fallback: Parse structure directly via reflection
3. Check for filename parameter (common for missing disposition)

**Handles:**
- Missing Content-Disposition headers
- Inline vs. attachment detection
- MIME-encoded filenames
- Nested multipart structures

### HtmlSanitizer

XSS protection via HTML Purifier:

```php
$clean = $htmlSanitizer->sanitize($dirtyHtml);
```

**Allowed Tags:**
- Structure: `p`, `br`, `span`, `div`, `hr`
- Text: `strong`, `em`, `u`, `b`, `i`, `s`, `strike`, `sub`, `sup`
- Links: `a[href|title|target]` (with nofollow + target="_blank")
- Images: `img[src|alt|width|height]`
- Lists: `ul`, `ol`, `li`
- Code: `blockquote`, `pre`, `code`
- Headings: `h1`-`h6`
- Tables: `table`, `thead`, `tbody`, `tr`, `th`, `td`

**Allowed CSS:**
- Colors, fonts, text-align, margins, paddings, borders

**Features:**
- `sanitizeStrict()` - Minimal tags only
- `stripAll()` - Remove all HTML
- `toPlainText()` - Convert to plain text (preserve formatting)

### TextSanitizer

Plain text cleanup:

```php
$clean = $textSanitizer->sanitize($text);
```

**Features:**
- Remove control characters (except \n, \r, \t)
- Normalize linebreaks to \n
- Normalize whitespace
- Ensure UTF-8 encoding
- `stripQuotedText()` - Remove reply chains
- `extractUrls()` - Find all URLs
- `extractEmails()` - Find all email addresses
- `truncate()` - Smart truncation at word boundaries

## Data Objects

### ParsedEmail

```php
class ParsedEmail {
    public string $messageId;
    public string $subject;
    public string $from;
    public array $to;
    public array $cc;
    public array $bcc;
    public DateTime $date;
    public ?string $bodyText;
    public ?string $bodyHtml;
    public array $attachments; // Attachment[]
    public ThreadingInfo $threadingInfo;
    public array $headers;
    
    public function toArray(): array;
    public function hasTextBody(): bool;
    public function hasHtmlBody(): bool;
    public function hasAttachments(): bool;
    public function getAttachmentCount(): int;
    public function getTotalAttachmentSize(): int;
}
```

### Attachment

```php
class Attachment {
    public string $filename;
    public string $mimeType;
    public int $size;
    public string $encoding;
    public string $content; // Base64 or binary
    public ?string $contentId;
    public bool $isInline;
    
    public function toArray(): array;
    public function isImage(): bool;
    public function isDocument(): bool;
    public function getFormattedSize(): string;
    public function getExtension(): string;
    public function getDecodedContent(): string;
}
```

### ThreadingInfo

```php
class ThreadingInfo {
    public string $messageId;
    public ?string $inReplyTo;
    public array $references; // Oldest first
    
    public function toArray(): array;
    public function getThreadId(): string; // Oldest message in chain
    public function isReply(): bool;
    public function isThreaded(): bool;
    public function getThreadDepth(): int;
    public function getParentId(): ?string;
    public function getAncestors(): array;
}
```

## Testing

### Test Script

```bash
php src/modules/imap/tests/email-parser-test.php
```

**Tests:**
- Plain text emails
- HTML emails
- Multipart emails (text + HTML)
- Emails with attachments
- Reply emails (threading)
- Special characters (UTF-8)

### Send Test Email with Attachments

```bash
php src/modules/imap/tests/send-test-email-with-attachments.php
```

Sends test email with 4 attachments (TXT, SVG, CSV, HTML).

### Test Results

✅ **8 messages parsed successfully**
- Average: 11.13ms per email
- 8 with text body
- 2 with HTML body
- 5 total attachments detected

## Mercury/XAMPP Compatibility

### Message-ID Extraction

Mercury stores Message-ID in headers but `getMessageId()` returns UID:

```php
// Solution: Parse raw headers
$rawHeaders = $message->getRawHeaders();
preg_match('/Message-ID:\s*<([^>]+)>/i', $rawHeaders, $matches);
$messageId = $matches[1];
```

### Attachment Detection

Mercury doesn't always set `Content-Disposition: attachment`:

```php
// Solution: Check filename parameter
$filename = $this->extractFilename($part);
if (!empty($filename)) {
    return true; // Is attachment
}
```

## Performance

**Benchmarks (Mercury/XAMPP):**
- Simple text email: ~1.5ms
- HTML email: ~20ms
- Email with 4 attachments: ~86ms
- Average: ~14ms per email

**Optimization:**
- ImapMessage handles encoding/charset (no re-parsing)
- Reflection used sparingly (only for attachment fallback)
- HTML Purifier caching enabled

## Dependencies

**Composer:**
```json
{
    "require": {
        "ezyang/htmlpurifier": "^4.16"
    }
}
```

**PHP Extensions:**
- `imap` (for IMAP parsing)
- `mbstring` (for charset conversion)

## Future Enhancements

**Planned (M2):**
- Attachment content extraction (binary data)
- Attachment storage (filesystem + DB)
- Inline image handling (CID replacement)
- Raw RFC 822 parsing (parseRawEmail)
- Signature detection/extraction

**Considered:**
- S/MIME support
- PGP support
- Advanced threading (JWZ algorithm)

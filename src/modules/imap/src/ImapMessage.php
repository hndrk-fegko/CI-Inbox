<?php

declare(strict_types=1);

namespace CiInbox\Modules\Imap;

use CiInbox\Modules\Logger\LoggerService;

/**
 * IMAP Message
 * 
 * Represents a single email message with all its properties.
 * Lazily loads message parts on demand.
 */
class ImapMessage implements ImapMessageInterface
{
    /** @var object|null Cached message structure */
    private ?object $structure = null;

    /** @var array|null Cached headers */
    private ?array $headers = null;

    /** @var string|null Cached plain text body */
    private ?string $bodyText = null;

    /** @var string|null Cached HTML body */
    private ?string $bodyHtml = null;

    /** @var array|null Cached attachments */
    private ?array $attachments = null;

    /**
     * @param resource $connection IMAP connection resource
     * @param string $uid Message UID
     * @param int $msgNo Message number (sequence number)
     * @param LoggerService $logger
     */
    public function __construct(
        private $connection,
        private string $uid,
        private int $msgNo,
        private LoggerService $logger
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getUid(): string
    {
        return $this->uid;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageId(): string
    {
        return $this->getHeader('Message-ID') ?? $this->uid;
    }

    /**
     * {@inheritdoc}
     */
    public function getInReplyTo(): ?string
    {
        return $this->getHeader('In-Reply-To');
    }

    /**
     * {@inheritdoc}
     */
    public function getReferences(): array
    {
        $references = $this->getHeader('References');
        if (!$references) {
            return [];
        }

        // Split by whitespace and filter empty
        return array_filter(preg_split('/\s+/', $references));
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject(): string
    {
        $headers = $this->getHeaders();
        return $headers['subject'] ?? '(No Subject)';
    }

    /**
     * {@inheritdoc}
     */
    public function getFrom(): array
    {
        $headers = $this->getHeaders();
        return $this->parseAddress($headers['from'] ?? '');
    }

    /**
     * {@inheritdoc}
     */
    public function getTo(): array
    {
        $headers = $this->getHeaders();
        return $this->parseAddresses($headers['to'] ?? '');
    }

    /**
     * {@inheritdoc}
     */
    public function getCc(): array
    {
        $headers = $this->getHeaders();
        return $this->parseAddresses($headers['cc'] ?? '');
    }

    /**
     * {@inheritdoc}
     */
    public function getBcc(): array
    {
        $headers = $this->getHeaders();
        return $this->parseAddresses($headers['bcc'] ?? '');
    }

    /**
     * {@inheritdoc}
     */
    public function getDate(): \DateTime
    {
        $headers = $this->getHeaders();
        $dateStr = $headers['date'] ?? 'now';

        try {
            return new \DateTime($dateStr);
        } catch (\Exception $e) {
            $this->logger->warning('Failed to parse date', [
                'date' => $dateStr,
                'uid' => $this->uid
            ]);
            return new \DateTime();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBodyText(): string
    {
        if ($this->bodyText === null) {
            $this->loadBodies();
        }

        return $this->bodyText ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getBodyHtml(): string
    {
        if ($this->bodyHtml === null) {
            $this->loadBodies();
        }

        return $this->bodyHtml ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getAttachments(): array
    {
        if ($this->attachments === null) {
            $this->loadAttachments();
        }

        return $this->attachments ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function hasAttachments(): bool
    {
        return count($this->getAttachments()) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getRawHeaders(): string
    {
        return @imap_fetchheader($this->connection, $this->msgNo) ?: '';
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader(string $header): ?string
    {
        $headers = $this->getHeaders();
        $headerLower = strtolower($header);
        return $headers[$headerLower] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function isUnread(): bool
    {
        $headers = $this->getHeaders();
        return !isset($headers['seen']) || $headers['seen'] !== 1;
    }

    /**
     * {@inheritdoc}
     */
    public function isFlagged(): bool
    {
        $headers = $this->getHeaders();
        return isset($headers['flagged']) && $headers['flagged'] === 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize(): int
    {
        $headers = $this->getHeaders();
        return (int)($headers['size'] ?? 0);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return [
            'uid' => $this->getUid(),
            'message_id' => $this->getMessageId(),
            'in_reply_to' => $this->getInReplyTo(),
            'references' => $this->getReferences(),
            'subject' => $this->getSubject(),
            'from' => $this->getFrom(),
            'to' => $this->getTo(),
            'cc' => $this->getCc(),
            'date' => $this->getDate()->format('Y-m-d H:i:s'),
            'body_text' => $this->getBodyText(),
            'body_html' => $this->getBodyHtml(),
            'attachments' => $this->getAttachments(),
            'has_attachments' => $this->hasAttachments(),
            'is_unread' => $this->isUnread(),
            'is_flagged' => $this->isFlagged(),
            'size' => $this->getSize(),
        ];
    }

    /**
     * Get message headers (cached)
     * 
     * @return array
     */
    private function getHeaders(): array
    {
        if ($this->headers === null) {
            $headerInfo = @imap_headerinfo($this->connection, $this->msgNo);
            
            if ($headerInfo === false) {
                $this->headers = [];
                return [];
            }

            $this->headers = [
                'subject' => $this->decodeMimeHeader($headerInfo->subject ?? ''),
                'from' => $headerInfo->fromaddress ?? '',
                'to' => $headerInfo->toaddress ?? '',
                'cc' => $headerInfo->ccaddress ?? '',
                'bcc' => $headerInfo->bccaddress ?? '',
                'date' => $headerInfo->date ?? '',
                'message_id' => $headerInfo->message_id ?? '',
                'size' => $headerInfo->Size ?? 0,
                'seen' => isset($headerInfo->Unseen) ? 0 : 1,
                'flagged' => isset($headerInfo->Flagged) ? 1 : 0,
            ];

            // Parse raw headers for additional fields
            $rawHeaders = $this->getRawHeaders();
            if (preg_match('/^In-Reply-To:\s*(.+)$/mi', $rawHeaders, $matches)) {
                $this->headers['in_reply_to'] = trim($matches[1]);
            }
            if (preg_match('/^References:\s*(.+)$/mi', $rawHeaders, $matches)) {
                $this->headers['references'] = trim($matches[1]);
            }
        }

        return $this->headers;
    }

    /**
     * Load message bodies (text and HTML)
     * 
     * @return void
     */
    private function loadBodies(): void
    {
        $structure = $this->getStructure();

        // Try to get text and HTML parts
        $this->bodyText = $this->getPartByType($structure, 'text/plain');
        $this->bodyHtml = $this->getPartByType($structure, 'text/html');

        // Fallback: if no parts found, get body
        if (!$this->bodyText && !$this->bodyHtml) {
            $body = @imap_body($this->connection, $this->msgNo);
            $this->bodyText = $body ?: '';
        }
    }

    /**
     * Load message attachments
     * 
     * @return void
     */
    private function loadAttachments(): void
    {
        $this->attachments = [];
        $structure = $this->getStructure();

        if (!isset($structure->parts)) {
            return;
        }

        foreach ($structure->parts as $partNum => $part) {
            if ($this->isAttachment($part)) {
                $filename = $this->getPartFilename($part);
                $mimeType = $this->getPartMimeType($part);
                $size = $part->bytes ?? 0;

                $this->attachments[] = [
                    'filename' => $filename,
                    'size' => $size,
                    'mime_type' => $mimeType,
                    'part_number' => $partNum + 1,
                ];
            }
        }
    }

    /**
     * Get message structure
     * 
     * @return object
     */
    private function getStructure(): object
    {
        if ($this->structure === null) {
            $this->structure = @imap_fetchstructure($this->connection, $this->msgNo);
            
            if ($this->structure === false) {
                $this->structure = new \stdClass();
            }
        }

        return $this->structure;
    }

    /**
     * Get message part by MIME type
     * 
     * @param object $structure
     * @param string $mimeType
     * @param string $partNumber
     * @return string|null
     */
    private function getPartByType(object $structure, string $mimeType, string $partNumber = '1'): ?string
    {
        $type = $this->getPartMimeType($structure);

        if ($type === $mimeType) {
            $body = @imap_fetchbody($this->connection, $this->msgNo, $partNumber);
            return $this->decodeBody($body, $structure->encoding ?? 0);
        }

        if (isset($structure->parts)) {
            foreach ($structure->parts as $index => $part) {
                $subPartNumber = $partNumber === '1' ? (string)($index + 1) : "{$partNumber}." . ($index + 1);
                $result = $this->getPartByType($part, $mimeType, $subPartNumber);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        return null;
    }

    /**
     * Get MIME type of part
     * 
     * @param object $part
     * @return string
     */
    private function getPartMimeType(object $part): string
    {
        $primaryType = ['text', 'multipart', 'message', 'application', 'audio', 'image', 'video', 'other'];
        $type = $primaryType[$part->type ?? 0] ?? 'unknown';
        $subtype = strtolower($part->subtype ?? '');
        
        return "{$type}/{$subtype}";
    }

    /**
     * Check if part is attachment
     * 
     * @param object $part
     * @return bool
     */
    private function isAttachment(object $part): bool
    {
        return isset($part->disposition) && strtolower($part->disposition) === 'attachment';
    }

    /**
     * Get filename of attachment part
     * 
     * @param object $part
     * @return string
     */
    private function getPartFilename(object $part): string
    {
        $filename = 'unnamed';

        if (isset($part->dparameters)) {
            foreach ($part->dparameters as $param) {
                if (strtolower($param->attribute) === 'filename') {
                    $filename = $param->value;
                    break;
                }
            }
        }

        if ($filename === 'unnamed' && isset($part->parameters)) {
            foreach ($part->parameters as $param) {
                if (strtolower($param->attribute) === 'name') {
                    $filename = $param->value;
                    break;
                }
            }
        }

        return $this->decodeMimeHeader($filename);
    }

    /**
     * Decode message body based on encoding
     * 
     * @param string $body
     * @param int $encoding
     * @return string
     */
    private function decodeBody(string $body, int $encoding): string
    {
        switch ($encoding) {
            case 1: // 8BIT
                return $body;
            case 2: // BINARY
                return $body;
            case 3: // BASE64
                return base64_decode($body);
            case 4: // QUOTED-PRINTABLE
                return quoted_printable_decode($body);
            default:
                return $body;
        }
    }

    /**
     * Decode MIME header (RFC 2047)
     * 
     * @param string $text
     * @return string
     */
    private function decodeMimeHeader(string $text): string
    {
        return mb_decode_mimeheader($text);
    }

    /**
     * Parse single email address
     * 
     * @param string $address
     * @return array ['email' => '...', 'name' => '...']
     */
    private function parseAddress(string $address): array
    {
        if (preg_match('/<([^>]+)>/', $address, $matches)) {
            $email = $matches[1];
            $name = trim(str_replace("<{$email}>", '', $address), '" ');
        } else {
            $email = trim($address);
            $name = '';
        }

        return [
            'email' => $email,
            'name' => $this->decodeMimeHeader($name),
        ];
    }

    /**
     * Parse multiple email addresses
     * 
     * @param string $addresses
     * @return array Array of ['email' => '...', 'name' => '...']
     */
    private function parseAddresses(string $addresses): array
    {
        if (empty($addresses)) {
            return [];
        }

        $parts = explode(',', $addresses);
        $result = [];

        foreach ($parts as $part) {
            $result[] = $this->parseAddress(trim($part));
        }

        return $result;
    }
}

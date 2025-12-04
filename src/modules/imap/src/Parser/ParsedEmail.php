<?php
namespace CiInbox\Modules\Imap\Parser;

/**
 * Parsed Email Data Object
 * 
 * Represents a fully parsed email message with all extracted information.
 */
class ParsedEmail {
    public function __construct(
        public string $messageId,
        public string $subject,
        public string $from,
        public array $to,
        public array $cc,
        public array $bcc,
        public \DateTime $date,
        public ?string $bodyText,
        public ?string $bodyHtml,
        public array $attachments,
        public ThreadingInfo $threadingInfo,
        public array $headers
    ) {}
    
    /**
     * Convert to array representation
     */
    public function toArray(): array {
        return [
            'message_id' => $this->messageId,
            'subject' => $this->subject,
            'from' => $this->from,
            'to' => $this->to,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'date' => $this->date->format('Y-m-d H:i:s'),
            'body_text' => $this->bodyText,
            'body_html' => $this->bodyHtml,
            'attachments' => array_map(fn($a) => $a->toArray(), $this->attachments),
            'threading' => $this->threadingInfo->toArray(),
            'headers' => $this->headers
        ];
    }
    
    /**
     * Check if email has text body
     */
    public function hasTextBody(): bool {
        return !empty($this->bodyText);
    }
    
    /**
     * Check if email has HTML body
     */
    public function hasHtmlBody(): bool {
        return !empty($this->bodyHtml);
    }
    
    /**
     * Check if email has attachments
     */
    public function hasAttachments(): bool {
        return !empty($this->attachments);
    }
    
    /**
     * Get attachment count
     */
    public function getAttachmentCount(): int {
        return count($this->attachments);
    }
    
    /**
     * Get total attachment size in bytes
     */
    public function getTotalAttachmentSize(): int {
        return array_reduce(
            $this->attachments,
            fn($carry, $attachment) => $carry + $attachment->size,
            0
        );
    }
}

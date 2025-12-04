<?php
namespace CiInbox\Modules\Imap\Parser;

/**
 * Email Attachment Data Object
 * 
 * Represents an email attachment with metadata and content.
 */
class Attachment {
    public function __construct(
        public string $filename,
        public string $mimeType,
        public int $size,
        public string $encoding,
        public string $content,
        public ?string $contentId = null,
        public bool $isInline = false
    ) {}
    
    /**
     * Convert to array representation
     */
    public function toArray(): array {
        return [
            'filename' => $this->filename,
            'mime_type' => $this->mimeType,
            'size' => $this->size,
            'encoding' => $this->encoding,
            'content_id' => $this->contentId,
            'is_inline' => $this->isInline,
            'content_length' => strlen($this->content)
        ];
    }
    
    /**
     * Check if attachment is an image
     */
    public function isImage(): bool {
        return str_starts_with($this->mimeType, 'image/');
    }
    
    /**
     * Check if attachment is a document
     */
    public function isDocument(): bool {
        return in_array($this->mimeType, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);
    }
    
    /**
     * Get human-readable file size
     */
    public function getFormattedSize(): string {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->size;
        $unit = 0;
        
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        
        return round($size, 2) . ' ' . $units[$unit];
    }
    
    /**
     * Get file extension from filename
     */
    public function getExtension(): string {
        return strtolower(pathinfo($this->filename, PATHINFO_EXTENSION));
    }
    
    /**
     * Decode content based on encoding
     */
    public function getDecodedContent(): string {
        return match(strtolower($this->encoding)) {
            'base64' => base64_decode($this->content),
            'quoted-printable' => quoted_printable_decode($this->content),
            default => $this->content
        };
    }
}

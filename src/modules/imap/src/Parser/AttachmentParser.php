<?php
namespace CiInbox\Modules\Imap\Parser;

use CiInbox\Modules\Imap\ImapMessage;
use CiInbox\Modules\Logger\LoggerService;

/**
 * Attachment Parser
 * 
 * Extracts attachments from IMAP messages with robust detection.
 * Handles cases where ImapMessage misses attachments (e.g., missing disposition header).
 */
class AttachmentParser {
    public function __construct(
        private LoggerService $logger
    ) {}
    /**
     * Parse attachments from IMAP message
     * 
     * @return Attachment[]
     */
    public function parseAttachments(ImapMessage $message): array {
        $uid = $message->getUid();
        
        // First try: Use ImapMessage's built-in method
        $rawAttachments = $message->getAttachments();
        
        if (!empty($rawAttachments)) {
            $attachments = $this->convertToAttachmentObjects($rawAttachments);
            $this->logger->debug('Attachments parsed via ImapMessage', [
                'uid' => $uid,
                'count' => count($attachments)
            ]);
            return $attachments;
        }
        
        // Fallback: Parse structure directly (for missing disposition headers)
        $this->logger->debug('Using fallback attachment parser (reflection)', [
            'uid' => $uid
        ]);
        
        $attachments = $this->parseFromStructure($message);
        
        $this->logger->info('Attachments parsed via fallback', [
            'uid' => $uid,
            'count' => count($attachments),
            'files' => array_map(fn($a) => $a->filename, $attachments)
        ]);
        
        return $attachments;
    }
    
    /**
     * Convert raw attachment arrays to Attachment objects
     */
    private function convertToAttachmentObjects(array $rawAttachments): array {
        $attachments = [];
        foreach ($rawAttachments as $raw) {
            $attachments[] = new Attachment(
                filename: $raw['filename'] ?? 'unnamed',
                mimeType: $raw['mime_type'] ?? 'application/octet-stream',
                size: $raw['size'] ?? 0,
                encoding: 'base64',
                content: '',
                contentId: null,
                isInline: false
            );
        }
        return $attachments;
    }
    
    /**
     * Parse attachments directly from structure (fallback method)
     * 
     * Uses reflection to access private getStructure() method
     */
    private function parseFromStructure(ImapMessage $message): array {
        try {
            // Get structure via reflection (since it's private)
            $reflection = new \ReflectionClass($message);
            $method = $reflection->getMethod('getStructure');
            $method->setAccessible(true);
            $structure = $method->invoke($message);
            
            if (!isset($structure->parts)) {
                return [];
            }
            
            $attachments = [];
            $this->extractAttachmentsRecursive($structure, $attachments, '');
            
            return $attachments;
            
        } catch (\Exception $e) {
            // Reflection failed, return empty
            $this->logger->error('Reflection-based attachment parsing failed', [
                'uid' => $message->getUid(),
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Extract attachments recursively from structure
     */
    private function extractAttachmentsRecursive(object $structure, array &$attachments, string $prefix): void {
        if (!isset($structure->parts)) {
            return;
        }
        
        foreach ($structure->parts as $index => $part) {
            $partNumber = $prefix === '' ? ($index + 1) : "{$prefix}." . ($index + 1);
            
            // Recursive for nested multipart
            if (isset($part->parts)) {
                $this->extractAttachmentsRecursive($part, $attachments, $partNumber);
                continue;
            }
            
            // Check if this part is an attachment
            $filename = $this->extractFilename($part);
            $isAttachment = $this->isAttachment($part, $filename);
            
            if (!$isAttachment) {
                continue;
            }
            
            $attachments[] = new Attachment(
                filename: $filename ?: 'unnamed',
                mimeType: $this->getMimeType($part),
                size: $part->bytes ?? 0,
                encoding: $this->getEncodingName($part->encoding ?? 0),
                content: '', // Would need message context to fetch
                contentId: $this->getContentId($part),
                isInline: $this->isInline($part)
            );
        }
    }
    
    /**
     * Check if part is an attachment
     */
    private function isAttachment(object $part, ?string $filename): bool {
        // Explicit attachment disposition
        if (isset($part->disposition) && strtolower($part->disposition) === 'attachment') {
            return true;
        }
        
        // Has filename parameter (common for attachments without disposition)
        if (!empty($filename)) {
            // Exclude inline parts that are part of multipart/alternative or multipart/related
            if (!isset($part->disposition) || strtolower($part->disposition) !== 'inline') {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if part is inline
     */
    private function isInline(object $part): bool {
        return isset($part->disposition) && strtolower($part->disposition) === 'inline';
    }
    
    /**
     * Extract filename from part
     */
    private function extractFilename(object $part): ?string {
        // Check disposition parameters
        if (isset($part->dparameters)) {
            foreach ($part->dparameters as $param) {
                if (strtolower($param->attribute) === 'filename') {
                    return $this->decodeFilename($param->value);
                }
            }
        }
        
        // Check content-type parameters
        if (isset($part->parameters)) {
            foreach ($part->parameters as $param) {
                if (strtolower($param->attribute) === 'name') {
                    return $this->decodeFilename($param->value);
                }
            }
        }
        
        return null;
    }
    
    /**
     * Get Content-ID
     */
    private function getContentId(object $part): ?string {
        if (isset($part->id)) {
            return trim($part->id, '<>');
        }
        return null;
    }
    
    /**
     * Get MIME type
     */
    private function getMimeType(object $part): string {
        $primaryType = $this->getPrimaryType($part->type ?? 0);
        $subType = strtolower($part->subtype ?? 'octet-stream');
        return "{$primaryType}/{$subType}";
    }
    
    /**
     * Get primary MIME type
     */
    private function getPrimaryType(int $type): string {
        return match($type) {
            0 => 'text',
            1 => 'multipart',
            2 => 'message',
            3 => 'application',
            4 => 'audio',
            5 => 'image',
            6 => 'video',
            7 => 'model',
            default => 'other'
        };
    }
    
    /**
     * Get encoding name
     */
    private function getEncodingName(int $encoding): string {
        return match($encoding) {
            1 => '8bit',
            2 => 'binary',
            3 => 'base64',
            4 => 'quoted-printable',
            default => '7bit'
        };
    }
    
    /**
     * Decode MIME-encoded filename
     */
    private function decodeFilename(string $filename): string {
        if (empty($filename)) {
            return '';
        }
        
        $decoded = imap_mime_header_decode($filename);
        $result = '';
        
        foreach ($decoded as $part) {
            $charset = $part->charset ?? 'UTF-8';
            $text = $part->text;
            
            if (strtolower($charset) !== 'utf-8' && strtolower($charset) !== 'default') {
                $text = mb_convert_encoding($text, 'UTF-8', $charset);
            }
            
            $result .= $text;
        }
        
        return $result;
    }
}

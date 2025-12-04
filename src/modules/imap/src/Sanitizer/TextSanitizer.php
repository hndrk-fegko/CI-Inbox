<?php
namespace CiInbox\Modules\Imap\Sanitizer;

use CiInbox\Modules\Logger\LoggerService;

/**
 * Text Sanitizer
 * 
 * Sanitizes plain text content.
 */
class TextSanitizer {
    public function __construct(
        private ?LoggerService $logger = null
    ) {
        $this->logger = $logger ?? new LoggerService();
    }
    /**
     * Sanitize plain text
     */
    public function sanitize(string $text): string {
        if (empty($text)) {
            return '';
        }
        
        // Remove control characters (except newlines, tabs)
        $text = $this->removeControlCharacters($text);
        
        // Normalize line breaks
        $text = $this->normalizeLineBreaks($text);
        
        // Normalize whitespace
        $text = $this->normalizeWhitespace($text);
        
        // Ensure UTF-8 encoding
        $text = $this->ensureUtf8($text);
        
        return $text;
    }
    
    /**
     * Remove control characters
     */
    private function removeControlCharacters(string $text): string {
        // Remove all control characters except \n (10), \r (13), \t (9)
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
    }
    
    /**
     * Normalize line breaks to \n
     */
    private function normalizeLineBreaks(string $text): string {
        // Convert Windows (\r\n) and Mac (\r) to Unix (\n)
        $text = str_replace("\r\n", "\n", $text);
        $text = str_replace("\r", "\n", $text);
        
        // Remove excessive empty lines (max 2 consecutive)
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        
        return $text;
    }
    
    /**
     * Normalize whitespace
     */
    private function normalizeWhitespace(string $text): string {
        // Replace multiple spaces with single space (but preserve line breaks)
        $text = preg_replace('/[^\S\n]+/', ' ', $text);
        
        // Remove trailing whitespace from each line
        $text = preg_replace('/[ \t]+$/m', '', $text);
        
        // Trim overall
        $text = trim($text);
        
        return $text;
    }
    
    /**
     * Ensure UTF-8 encoding
     */
    private function ensureUtf8(string $text): string {
        if (!mb_check_encoding($text, 'UTF-8')) {
            // Try to detect and convert
            $detected = mb_detect_encoding($text, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ASCII'], true);
            if ($detected && $detected !== 'UTF-8') {
                $this->logger->debug('Converting text encoding', [
                    'from' => $detected,
                    'to' => 'UTF-8',
                    'length' => strlen($text)
                ]);
                
                $converted = mb_convert_encoding($text, 'UTF-8', $detected);
                if ($converted !== false) {
                    return $converted;
                }
            }
            
            $this->logger->warning('Invalid UTF-8 detected, cleaning', [
                'detected_encoding' => $detected,
                'length' => strlen($text)
            ]);
            
            // Last resort: remove invalid UTF-8 sequences
            return mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        }
        
        return $text;
    }
    
    /**
     * Strip quoted text (reply chains)
     */
    public function stripQuotedText(string $text): string {
        $lines = explode("\n", $text);
        $cleaned = [];
        
        foreach ($lines as $line) {
            // Skip lines starting with > (quote marker)
            if (preg_match('/^\s*>/', $line)) {
                continue;
            }
            
            // Skip common reply headers
            if (preg_match('/^On .+ wrote:$/i', $line)) {
                break;
            }
            
            if (preg_match('/^Am .+ schrieb .+:$/i', $line)) {
                break;
            }
            
            $cleaned[] = $line;
        }
        
        return implode("\n", $cleaned);
    }
    
    /**
     * Extract URLs from text
     */
    public function extractUrls(string $text): array {
        preg_match_all(
            '#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#',
            $text,
            $matches
        );
        
        return $matches[0] ?? [];
    }
    
    /**
     * Extract email addresses from text
     */
    public function extractEmails(string $text): array {
        preg_match_all(
            '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/',
            $text,
            $matches
        );
        
        return $matches[0] ?? [];
    }
    
    /**
     * Truncate text to specified length
     */
    public function truncate(string $text, int $length = 100, string $suffix = '...'): string {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        
        $truncated = mb_substr($text, 0, $length);
        
        // Try to break at word boundary
        $lastSpace = mb_strrpos($truncated, ' ');
        if ($lastSpace !== false && $lastSpace > $length * 0.8) {
            $truncated = mb_substr($truncated, 0, $lastSpace);
        }
        
        return $truncated . $suffix;
    }
}

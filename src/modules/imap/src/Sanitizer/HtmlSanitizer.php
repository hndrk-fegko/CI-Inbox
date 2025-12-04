<?php
namespace CiInbox\Modules\Imap\Sanitizer;

use HTMLPurifier;
use HTMLPurifier_Config;
use CiInbox\Modules\Logger\LoggerService;

/**
 * HTML Sanitizer
 * 
 * Sanitizes HTML content to prevent XSS attacks.
 * Uses HTML Purifier for robust filtering.
 */
class HtmlSanitizer {
    private HTMLPurifier $purifier;
    private HTMLPurifier_Config $config;
    
    public function __construct(
        private ?LoggerService $logger = null
    ) {
        $this->logger = $logger ?? new LoggerService();
        $this->config = HTMLPurifier_Config::createDefault();
        $this->configure();
        $this->purifier = new HTMLPurifier($this->config);
    }
    
    /**
     * Sanitize HTML content
     */
    public function sanitize(string $html): string {
        if (empty($html)) {
            return '';
        }
        
        $originalLength = strlen($html);
        $sanitized = $this->purifier->purify($html);
        $sanitizedLength = strlen($sanitized);
        
        if ($sanitizedLength < $originalLength) {
            $this->logger->debug('HTML sanitized', [
                'original_length' => $originalLength,
                'sanitized_length' => $sanitizedLength,
                'removed_bytes' => $originalLength - $sanitizedLength,
                'removed_percent' => round((($originalLength - $sanitizedLength) / $originalLength) * 100, 1)
            ]);
        }
        
        return $sanitized;
    }
    
    /**
     * Configure HTML Purifier
     */
    private function configure(): void {
        // Cache directory
        $cacheDir = __DIR__ . '/../../../../../../data/cache/htmlpurifier';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        $this->config->set('Cache.SerializerPath', $cacheDir);
        
        // Allowed HTML tags
        $this->config->set('HTML.Allowed', implode(',', [
            'p', 'br', 'span', 'div',
            'strong', 'em', 'u', 'b', 'i', 's', 'strike', 'sub', 'sup',
            'a[href|title|target]',
            'img[src|alt|width|height|title]',
            'ul', 'ol', 'li',
            'blockquote', 'pre', 'code',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'table[border|cellpadding|cellspacing]',
            'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
            'hr'
        ]));
        
        // Allowed CSS properties
        $this->config->set('CSS.AllowedProperties', [
            'color', 'background-color',
            'font-family', 'font-size', 'font-weight', 'font-style',
            'text-align', 'text-decoration',
            'margin', 'padding',
            'width', 'height',
            'border', 'border-color', 'border-width', 'border-style'
        ]);
        
        // Link handling
        $this->config->set('HTML.TargetBlank', true); // Open links in new tab
        $this->config->set('HTML.Nofollow', true); // Add rel="nofollow"
        $this->config->set('URI.DisableExternalResources', false); // Allow external images
        
        // Convert relative URLs to absolute (if needed)
        $this->config->set('URI.MakeAbsolute', false);
        
        // AutoFormat
        $this->config->set('AutoFormat.RemoveEmpty', true);
        $this->config->set('AutoFormat.RemoveSpansWithoutAttributes', true);
        $this->config->set('AutoFormat.Linkify', true);
        $this->config->set('AutoFormat.AutoParagraph', false);
        
        // Output
        $this->config->set('Core.Encoding', 'UTF-8');
        $this->config->set('HTML.Doctype', 'HTML 4.01 Transitional');
    }
    
    /**
     * Sanitize with strict mode (for untrusted content)
     */
    public function sanitizeStrict(string $html): string {
        $config = HTMLPurifier_Config::createDefault();
        
        // Minimal allowed tags
        $config->set('HTML.Allowed', 'p,br,strong,em,u,a[href],ul,ol,li');
        $config->set('CSS.AllowedProperties', []);
        $config->set('HTML.TargetBlank', true);
        $config->set('HTML.Nofollow', true);
        $config->set('URI.DisableExternalResources', true);
        
        $purifier = new HTMLPurifier($config);
        return $purifier->purify($html);
    }
    
    /**
     * Strip all HTML tags
     */
    public function stripAll(string $html): string {
        return strip_tags($html);
    }
    
    /**
     * Convert HTML to plain text (preserve formatting)
     */
    public function toPlainText(string $html): string {
        // Replace <br> with newlines
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
        
        // Replace </p> with double newlines
        $html = preg_replace('/<\/p>/i', "\n\n", $html);
        
        // Replace list items
        $html = preg_replace('/<li[^>]*>/i', "• ", $html);
        $html = preg_replace('/<\/li>/i', "\n", $html);
        
        // Strip remaining tags
        $text = strip_tags($html);
        
        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Normalize whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n\s+/', "\n", $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        
        return trim($text);
    }
}

<?php
namespace CiInbox\Modules\Imap\Parser;

use CiInbox\Modules\Imap\ImapMessage;
use CiInbox\Modules\Logger\LoggerService;

/**
 * Header Parser
 * 
 * Parses email headers including Message-ID, From, To, Date, etc.
 * Handles Mercury-specific quirks (Message-ID in raw headers).
 */
class HeaderParser {
    public function __construct(
        private LoggerService $logger
    ) {}
    /**
     * Parse all headers from IMAP message
     */
    public function parseHeaders(ImapMessage $message): array {
        return [
            'message_id' => $this->extractMessageId($message),
            'subject' => $this->extractSubject($message),
            'from' => $this->extractFrom($message),
            'to' => $this->extractTo($message),
            'cc' => $this->extractCc($message),
            'bcc' => $this->extractBcc($message),
            'date' => $this->extractDate($message),
            'in_reply_to' => $this->extractInReplyTo($message),
            'references' => $this->extractReferences($message)
        ];
    }
    
    /**
     * Extract Message-ID from headers
     * 
     * Mercury-specific: Message-ID is only in raw headers, not in getMessageId()
     */
    public function extractMessageId(ImapMessage $message): string {
        // Try raw headers first (Mercury compatibility)
        $rawHeaders = $message->getRawHeaders();
        if (preg_match('/Message-ID:\s*<([^>]+)>/i', $rawHeaders, $matches)) {
            return $matches[1];
        }
        
        // Fallback to API method
        $messageId = $message->getMessageId();
        if (!empty($messageId) && $messageId !== $message->getUid()) {
            return $messageId;
        }
        
        // Last resort: generate from UID and date
        $generatedId = sprintf(
            '%d.%s@generated.local',
            $message->getUid(),
            $message->getDate()->format('YmdHis')
        );
        
        $this->logger->warning('Message-ID not found, generated fallback', [
            'uid' => $message->getUid(),
            'generated_id' => $generatedId
        ]);
        
        return $generatedId;
    }
    
    /**
     * Extract Subject
     */
    public function extractSubject(ImapMessage $message): string {
        return $this->decodeHeader($message->getSubject());
    }
    
    /**
     * Extract From address
     */
    public function extractFrom(ImapMessage $message): string {
        $from = $message->getFrom();
        
        if (is_array($from)) {
            return implode(', ', array_map(fn($f) => $this->formatAddress($f), $from));
        }
        
        return $this->formatAddress($from);
    }
    
    /**
     * Extract To addresses
     */
    public function extractTo(ImapMessage $message): array {
        $to = $message->getTo();
        
        if (empty($to)) {
            return [];
        }
        
        if (!is_array($to)) {
            return [$this->formatAddress($to)];
        }
        
        return array_map(fn($t) => $this->formatAddress($t), $to);
    }
    
    /**
     * Extract Cc addresses
     */
    public function extractCc(ImapMessage $message): array {
        $cc = $message->getCc();
        
        if (empty($cc)) {
            return [];
        }
        
        if (!is_array($cc)) {
            return [$this->formatAddress($cc)];
        }
        
        return array_map(fn($c) => $this->formatAddress($c), $cc);
    }
    
    /**
     * Extract Bcc addresses
     */
    public function extractBcc(ImapMessage $message): array {
        $bcc = $message->getBcc();
        
        if (empty($bcc)) {
            return [];
        }
        
        if (!is_array($bcc)) {
            return [$this->formatAddress($bcc)];
        }
        
        return array_map(fn($b) => $this->formatAddress($b), $bcc);
    }
    
    /**
     * Extract Date
     */
    public function extractDate(ImapMessage $message): \DateTime {
        return $message->getDate();
    }
    
    /**
     * Extract In-Reply-To header
     */
    public function extractInReplyTo(ImapMessage $message): ?string {
        $rawHeaders = $message->getRawHeaders();
        
        if (preg_match('/In-Reply-To:\s*<([^>]+)>/i', $rawHeaders, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Extract References header
     */
    public function extractReferences(ImapMessage $message): array {
        $rawHeaders = $message->getRawHeaders();
        
        if (preg_match('/References:\s*(.+?)(?:\r\n(?!\s)|$)/is', $rawHeaders, $matches)) {
            $referencesStr = trim($matches[1]);
            preg_match_all('/<([^>]+)>/', $referencesStr, $refMatches);
            return $refMatches[1] ?? [];
        }
        
        return [];
    }
    
    /**
     * Format email address
     */
    private function formatAddress($address): string {
        if (is_string($address)) {
            return $address;
        }
        
        if (is_object($address)) {
            $name = $address->personal ?? '';
            $email = $address->mailbox . '@' . $address->host;
            
            if (!empty($name)) {
                $name = $this->decodeHeader($name);
                return sprintf('%s <%s>', $name, $email);
            }
            
            return $email;
        }
        
        return '';
    }
    
    /**
     * Decode MIME-encoded header
     */
    private function decodeHeader(string $header): string {
        if (empty($header)) {
            return '';
        }
        
        // Decode MIME-encoded words (=?charset?encoding?text?=)
        $decoded = imap_mime_header_decode($header);
        
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

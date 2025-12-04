<?php
namespace CiInbox\Modules\Imap\Parser;

use CiInbox\Modules\Imap\ImapMessage;
use CiInbox\Modules\Imap\Sanitizer\HtmlSanitizer;
use CiInbox\Modules\Imap\Sanitizer\TextSanitizer;
use CiInbox\Modules\Logger\LoggerService;

/**
 * Email Parser
 * 
 * Main parser that orchestrates all parsing components.
 * Parses IMAP messages into structured ParsedEmail objects.
 */
class EmailParser implements EmailParserInterface {
    private HeaderParser $headerParser;
    private BodyParser $bodyParser;
    private AttachmentParser $attachmentParser;
    private ThreadingParser $threadingParser;
    private HtmlSanitizer $htmlSanitizer;
    private TextSanitizer $textSanitizer;
    private LoggerService $logger;
    
    public function __construct(?LoggerService $logger = null) {
        $this->logger = $logger ?? new LoggerService();
        $this->headerParser = new HeaderParser($this->logger);
        $this->bodyParser = new BodyParser($this->logger);
        $this->attachmentParser = new AttachmentParser($this->logger);
        $this->threadingParser = new ThreadingParser($this->logger);
        $this->htmlSanitizer = new HtmlSanitizer($this->logger);
        $this->textSanitizer = new TextSanitizer($this->logger);
    }
    
    /**
     * Parse an IMAP message
     */
    public function parseMessage(ImapMessage $message): ParsedEmail {
        $startTime = microtime(true);
        $uid = $message->getUid();
        
        try {
            $this->logger->debug('Starting email parse', [
                'uid' => $uid,
                'subject' => substr($message->getSubject(), 0, 50)
            ]);
            
            // Parse headers
            $headers = $this->headerParser->parseHeaders($message);
            
            // Parse body
            $body = $this->bodyParser->parseBody($message);
            $bodyText = $body['text'] ? $this->textSanitizer->sanitize($body['text']) : null;
            $bodyHtml = $body['html'] ? $this->htmlSanitizer->sanitize($body['html']) : null;
            
            // Parse attachments
            $attachments = $this->attachmentParser->parseAttachments($message);
            
            // Parse threading
            $threadingInfo = $this->threadingParser->parseThreadingFromHeaders($headers);
            
            $parsed = new ParsedEmail(
                messageId: $headers['message_id'],
                subject: $headers['subject'],
                from: $headers['from'],
                to: $headers['to'],
                cc: $headers['cc'],
                bcc: $headers['bcc'],
                date: $headers['date'],
                bodyText: $bodyText,
                bodyHtml: $bodyHtml,
                attachments: $attachments,
                threadingInfo: $threadingInfo,
                headers: $headers
            );
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->info('Email parsed successfully', [
                'uid' => $uid,
                'message_id' => $headers['message_id'],
                'duration_ms' => $duration,
                'has_text' => !empty($bodyText),
                'has_html' => !empty($bodyHtml),
                'attachments' => count($attachments),
                'is_reply' => $threadingInfo->isReply()
            ]);
            
            return $parsed;
            
        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->error('Failed to parse email', [
                'uid' => $uid,
                'duration_ms' => $duration,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    /**
     * Parse raw email string (RFC 822)
     */
    public function parseRawEmail(string $rawEmail): ParsedEmail {
        // This would require implementing a full RFC 822 parser
        // For now, throw an exception
        throw new \Exception('Raw email parsing not yet implemented');
    }
    
    /**
     * Parse message without sanitization (for raw display)
     */
    public function parseMessageUnsanitized(ImapMessage $message): ParsedEmail {
        $headers = $this->headerParser->parseHeaders($message);
        $body = $this->bodyParser->parseBody($message);
        $attachments = $this->attachmentParser->parseAttachments($message);
        $threadingInfo = $this->threadingParser->parseThreadingFromHeaders($headers);
        
        return new ParsedEmail(
            messageId: $headers['message_id'],
            subject: $headers['subject'],
            from: $headers['from'],
            to: $headers['to'],
            cc: $headers['cc'],
            bcc: $headers['bcc'],
            date: $headers['date'],
            bodyText: $body['text'],
            bodyHtml: $body['html'],
            attachments: $attachments,
            threadingInfo: $threadingInfo,
            headers: $headers
        );
    }
}

<?php
namespace CiInbox\Modules\Imap\Parser;

use CiInbox\Modules\Imap\ImapMessage;
use CiInbox\Modules\Logger\LoggerService;

/**
 * Threading Parser
 * 
 * Extracts threading information from email headers.
 */
class ThreadingParser {
    private HeaderParser $headerParser;
    
    public function __construct(
        private LoggerService $logger
    ) {
        $this->headerParser = new HeaderParser($logger);
    }
    
    /**
     * Parse threading information from IMAP message
     */
    public function parseThreading(ImapMessage $message): ThreadingInfo {
        $this->logger->debug('Parsing threading information', ['uid' => $message->getUid()]);
        
        $messageId = $this->headerParser->extractMessageId($message);
        $inReplyTo = $this->headerParser->extractInReplyTo($message);
        $references = $this->headerParser->extractReferences($message);
        
        $this->logger->debug('Threading parsed', [
            'message_id' => $messageId,
            'in_reply_to' => $inReplyTo,
            'references_count' => count($references),
            'is_reply' => $inReplyTo !== null
        ]);
        
        return new ThreadingInfo(
            messageId: $messageId,
            inReplyTo: $inReplyTo,
            references: $references
        );
    }
    
    /**
     * Parse threading from parsed headers
     */
    public function parseThreadingFromHeaders(array $headers): ThreadingInfo {
        return new ThreadingInfo(
            messageId: $headers['message_id'] ?? '',
            inReplyTo: $headers['in_reply_to'] ?? null,
            references: $headers['references'] ?? []
        );
    }
}

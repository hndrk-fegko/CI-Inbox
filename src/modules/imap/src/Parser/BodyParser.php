<?php
namespace CiInbox\Modules\Imap\Parser;

use CiInbox\Modules\Imap\ImapMessage;
use CiInbox\Modules\Logger\LoggerService;

/**
 * Body Parser
 * 
 * Parses email body (text and HTML) from IMAP messages.
 * Simplified: ImapMessage already handles encoding/charset conversion.
 */
class BodyParser {
    public function __construct(
        private LoggerService $logger
    ) {}
    /**
     * Parse body from IMAP message
     * 
     * @return array ['text' => ?string, 'html' => ?string]
     */
    public function parseBody(ImapMessage $message): array {
        try {
            $textBody = $message->getBodyText();
            $htmlBody = $message->getBodyHtml();
            
            $this->logger->debug('Body parsed', [
                'uid' => $message->getUid(),
                'has_text' => !empty($textBody),
                'has_html' => !empty($htmlBody),
                'text_length' => strlen($textBody),
                'html_length' => strlen($htmlBody)
            ]);
            
            return [
                'text' => !empty($textBody) ? $textBody : null,
                'html' => !empty($htmlBody) ? $htmlBody : null
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to parse body', [
                'uid' => $message->getUid(),
                'error' => $e->getMessage()
            ]);
            return ['text' => null, 'html' => null];
        }
    }
}

<?php
namespace CiInbox\Modules\Imap\Parser;

use CiInbox\Modules\Imap\ImapMessage;

/**
 * Email Parser Interface
 * 
 * Contract for parsing email messages into structured data.
 */
interface EmailParserInterface {
    /**
     * Parse an IMAP message
     * 
     * @param ImapMessage $message The IMAP message to parse
     * @return ParsedEmail The parsed email data
     * @throws \Exception If parsing fails
     */
    public function parseMessage(ImapMessage $message): ParsedEmail;
    
    /**
     * Parse raw email string (RFC 822)
     * 
     * @param string $rawEmail The raw email content
     * @return ParsedEmail The parsed email data
     * @throws \Exception If parsing fails
     */
    public function parseRawEmail(string $rawEmail): ParsedEmail;
}

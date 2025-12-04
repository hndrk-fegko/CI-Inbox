<?php

namespace CiInbox\Modules\Imap\Manager;

/**
 * Thread Structure Data Transfer Object
 * 
 * Represents a thread with its emails and metadata.
 */
class ThreadStructure
{
    /**
     * @param string $threadId Unique thread identifier
     * @param string $subject Thread subject (normalized)
     * @param array $emails Array of ParsedEmail objects
     * @param array $participants Unique email addresses
     * @param \DateTime $lastMessageAt Timestamp of last message
     */
    public function __construct(
        public string $threadId,
        public string $subject,
        public array $emails,
        public array $participants,
        public \DateTime $lastMessageAt
    ) {}
    
    /**
     * Get message count
     */
    public function getMessageCount(): int
    {
        return count($this->emails);
    }
    
    /**
     * Get first message (thread starter)
     */
    public function getFirstMessage()
    {
        return $this->emails[0] ?? null;
    }
    
    /**
     * Get last message
     */
    public function getLastMessage()
    {
        return $this->emails[count($this->emails) - 1] ?? null;
    }
}

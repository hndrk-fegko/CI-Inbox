<?php
namespace CiInbox\Modules\Imap\Parser;

/**
 * Email Threading Information
 * 
 * Contains information for threading emails into conversations.
 */
class ThreadingInfo {
    public function __construct(
        public string $messageId,
        public ?string $inReplyTo = null,
        public array $references = []
    ) {}
    
    /**
     * Convert to array representation
     */
    public function toArray(): array {
        return [
            'message_id' => $this->messageId,
            'in_reply_to' => $this->inReplyTo,
            'references' => $this->references,
            'thread_id' => $this->getThreadId()
        ];
    }
    
    /**
     * Calculate thread ID from message chain
     * 
     * Thread ID is the oldest message ID in the chain.
     */
    public function getThreadId(): string {
        if (!empty($this->references)) {
            return $this->references[0]; // Oldest reference
        }
        
        if ($this->inReplyTo) {
            return $this->inReplyTo;
        }
        
        return $this->messageId; // Start of new thread
    }
    
    /**
     * Check if this is a reply
     */
    public function isReply(): bool {
        return $this->inReplyTo !== null;
    }
    
    /**
     * Check if this is part of a thread
     */
    public function isThreaded(): bool {
        return !empty($this->references) || $this->inReplyTo !== null;
    }
    
    /**
     * Get depth in thread (0 = root)
     */
    public function getThreadDepth(): int {
        return count($this->references);
    }
    
    /**
     * Get parent message ID
     */
    public function getParentId(): ?string {
        return $this->inReplyTo;
    }
    
    /**
     * Get all ancestor message IDs (oldest first)
     */
    public function getAncestors(): array {
        return $this->references;
    }
}

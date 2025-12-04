<?php

namespace CiInbox\Modules\Imap\Manager;

use CiInbox\Modules\Imap\Parser\ParsedEmail;
use CiInbox\Modules\Logger\LoggerService;

/**
 * Thread Manager
 * 
 * Groups emails into threads based on:
 * 1. In-Reply-To header (highest priority)
 * 2. References header
 * 3. Subject + time window (fallback)
 */
class ThreadManager implements ThreadManagerInterface
{
    /** @var int Time window for subject matching (days) */
    private const TIME_WINDOW_DAYS = 30;
    
    public function __construct(
        private LoggerService $logger
    ) {}
    
    /**
     * {@inheritDoc}
     */
    public function buildThreads(array $emails): array
    {
        $this->logger->debug('Building threads', ['email_count' => count($emails)]);
        
        $threads = [];
        $messageIdToThreadId = []; // Map: message_id => thread_id
        
        // Sort emails by date (oldest first)
        usort($emails, fn($a, $b) => $a->date <=> $b->date);
        
        foreach ($emails as $email) {
            $threadId = $this->findThreadIdForEmail($email, $messageIdToThreadId, $threads);
            
            if ($threadId === null) {
                // Create new thread
                $threadId = $this->generateThreadId();
                $threads[$threadId] = new ThreadStructure(
                    threadId: $threadId,
                    subject: $this->normalizeSubject($email->subject),
                    emails: [$email],
                    participants: $this->extractParticipants($email),
                    lastMessageAt: $email->date
                );
                
                $this->logger->debug('Created new thread', [
                    'thread_id' => $threadId,
                    'subject' => $email->subject,
                    'message_id' => $email->messageId
                ]);
            } else {
                // Add to existing thread
                $threads[$threadId]->emails[] = $email;
                $threads[$threadId]->participants = array_values(array_unique(
                    array_merge($threads[$threadId]->participants, $this->extractParticipants($email))
                ));
                $threads[$threadId]->lastMessageAt = $email->date;
                
                $this->logger->debug('Added to existing thread', [
                    'thread_id' => $threadId,
                    'message_id' => $email->messageId
                ]);
            }
            
            // Register message_id for future lookups
            $messageIdToThreadId[$email->messageId] = $threadId;
        }
        
        $this->logger->info('Threads built', [
            'thread_count' => count($threads),
            'email_count' => count($emails)
        ]);
        
        return array_values($threads);
    }
    
    /**
     * {@inheritDoc}
     */
    public function findThreadForEmail(ParsedEmail $email, array $existingThreads = []): ?string
    {
        // Build message_id map from existing threads
        $messageIdToThreadId = [];
        foreach ($existingThreads as $thread) {
            foreach ($thread->emails as $threadEmail) {
                $messageIdToThreadId[$threadEmail->messageId] = $thread->threadId;
            }
        }
        
        return $this->findThreadIdForEmail($email, $messageIdToThreadId, $existingThreads);
    }
    
    /**
     * Find thread ID for email
     * 
     * @param ParsedEmail $email
     * @param array $messageIdToThreadId Map of message_id => thread_id
     * @param array $threads Existing threads
     * @return ?string Thread ID or null
     */
    private function findThreadIdForEmail(ParsedEmail $email, array $messageIdToThreadId, array $threads): ?string
    {
        // 1. Check In-Reply-To (highest priority)
        if ($email->threadingInfo->inReplyTo) {
            $threadId = $messageIdToThreadId[$email->threadingInfo->inReplyTo] ?? null;
            if ($threadId) {
                $this->logger->debug('Thread found via In-Reply-To', [
                    'thread_id' => $threadId,
                    'in_reply_to' => $email->threadingInfo->inReplyTo
                ]);
                return $threadId;
            }
        }
        
        // 2. Check References
        foreach ($email->threadingInfo->references as $refId) {
            $threadId = $messageIdToThreadId[$refId] ?? null;
            if ($threadId) {
                $this->logger->debug('Thread found via References', [
                    'thread_id' => $threadId,
                    'reference' => $refId
                ]);
                return $threadId;
            }
        }
        
        // 3. Fallback: Subject + Time Window
        $normalizedSubject = $this->normalizeSubject($email->subject);
        $timeWindow = (clone $email->date)->modify('-' . self::TIME_WINDOW_DAYS . ' days');
        
        foreach ($threads as $thread) {
            if ($this->normalizeSubject($thread->subject) === $normalizedSubject) {
                // Check time window
                if ($thread->lastMessageAt >= $timeWindow) {
                    $this->logger->debug('Thread found via Subject matching', [
                        'thread_id' => $thread->threadId,
                        'subject' => $normalizedSubject
                    ]);
                    return $thread->threadId;
                }
            }
        }
        
        // No thread found
        $this->logger->debug('No thread found for email', [
            'message_id' => $email->messageId,
            'subject' => $email->subject
        ]);
        return null;
    }
    
    /**
     * Normalize subject for matching
     * 
     * Removes prefixes like "Re:", "Fwd:", "AW:", etc.
     * 
     * @param string $subject
     * @return string Normalized subject
     */
    private function normalizeSubject(string $subject): string
    {
        // Remove common prefixes (case-insensitive)
        $subject = preg_replace('/^(Re|RE|Fwd|FWD|AW|Aw|WG|Wg):\s*/i', '', $subject);
        
        // Remove multiple spaces
        $subject = preg_replace('/\s+/', ' ', $subject);
        
        // Trim and lowercase for comparison
        $subject = trim(strtolower($subject));
        
        return $subject;
    }
    
    /**
     * Extract unique participants from email
     * 
     * @param ParsedEmail $email
     * @return array Email addresses
     */
    private function extractParticipants(ParsedEmail $email): array
    {
        $participants = [$email->from];
        
        foreach ($email->to as $to) {
            $participants[] = $to;
        }
        
        foreach ($email->cc as $cc) {
            $participants[] = $cc;
        }
        
        return array_values(array_unique($participants));
    }
    
    /**
     * Generate unique thread ID
     * 
     * @return string Thread ID
     */
    private function generateThreadId(): string
    {
        return 'thread-' . uniqid('', true);
    }
}

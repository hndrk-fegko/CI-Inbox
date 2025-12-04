<?php

namespace CiInbox\App\Services;

use CiInbox\App\Models\Thread;
use CiInbox\App\Models\Email;
use CiInbox\App\Repositories\ThreadRepository;
use CiInbox\App\Repositories\EmailRepositoryInterface;
use CiInbox\Modules\Imap\Parser\ParsedEmail;
use CiInbox\Modules\Imap\Manager\ThreadManager;
use CiInbox\Modules\Logger\LoggerService;

/**
 * Thread Service
 * 
 * Business logic for thread management.
 * Processes emails and assigns them to threads.
 */
class ThreadService
{
    public function __construct(
        private ThreadManager $threadManager,
        private ThreadRepository $threadRepository,
        private EmailRepositoryInterface $emailRepository,
        private LoggerService $logger
    ) {}
    
    /**
     * Process new email and assign to thread
     * 
     * @param ParsedEmail $parsedEmail
     * @return Thread Created or updated thread
     */
    public function processEmail(ParsedEmail $parsedEmail): Thread
    {
        $this->logger->info('Processing email for threading', [
            'message_id' => $parsedEmail->messageId,
            'subject' => $parsedEmail->subject
        ]);
        
        // Check if email already exists
        if ($this->emailRepository->existsByMessageId($parsedEmail->messageId)) {
            $this->logger->warning('Email already exists', [
                'message_id' => $parsedEmail->messageId
            ]);
            $email = $this->emailRepository->findByMessageId($parsedEmail->messageId);
            return $this->threadRepository->find($email->thread_id);
        }
        
        // Find existing thread
        $threadId = $this->findExistingThread($parsedEmail);
        
        // Create or update thread
        if ($threadId === null) {
            $thread = $this->createNewThread($parsedEmail);
        } else {
            $thread = $this->threadRepository->find($threadId);
        }
        
        // Save email to database
        $this->saveEmail($parsedEmail, $thread->id);
        
        // Update thread metadata
        $this->updateThreadMetadata($thread->id);
        
        return $thread;
    }
    
    /**
     * Get thread with all emails
     * 
     * @param int $threadId
     * @return Thread
     */
    public function getThread(int $threadId): Thread
    {
        return $this->threadRepository->find($threadId);
    }
    
    /**
     * List threads with filters
     * 
     * @param array $filters
     * @return array
     */
    public function listThreads(array $filters = []): array
    {
        $limit = $filters['limit'] ?? 50;
        $offset = $filters['offset'] ?? 0;
        
        $this->logger->info('ThreadService: Listing threads', [
            'filters' => $filters,
            'limit' => $limit,
            'has_search' => isset($filters['search']),
            'search_value' => $filters['search'] ?? null
        ]);
        
        $threads = $this->threadRepository->getAll($filters, $limit);
        
        $this->logger->info('ThreadService: Threads fetched', [
            'count' => $threads->count()
        ]);
        
        return [
            'threads' => $threads->toArray(),
            'total' => $threads->count()
        ];
    }
    
    /**
     * Update thread metadata (subject, participants, last_message_at)
     * 
     * @param int $threadId
     * @return void
     */
    public function updateThreadMetadata(int $threadId): void
    {
        $thread = $this->threadRepository->find($threadId);
        $emails = $thread->emails;
        
        // Update participants (unique)
        $participants = [];
        foreach ($emails as $email) {
            $participants[] = $email->from_email;
            
            // Add TO recipients
            if ($email->to_addresses) {
                $toAddresses = is_string($email->to_addresses) 
                    ? json_decode($email->to_addresses, true) 
                    : $email->to_addresses;
                if (isset($toAddresses['addresses'])) {
                    foreach ($toAddresses['addresses'] as $to) {
                        if (is_string($to)) {
                            $participants[] = $to;
                        }
                    }
                }
            }
            
            // Add CC recipients
            if ($email->cc_addresses) {
                $ccAddresses = is_string($email->cc_addresses) 
                    ? json_decode($email->cc_addresses, true) 
                    : $email->cc_addresses;
                if (isset($ccAddresses['addresses'])) {
                    foreach ($ccAddresses['addresses'] as $cc) {
                        if (is_string($cc)) {
                            $participants[] = $cc;
                        }
                    }
                }
            }
        }
        
        $participants = array_values(array_unique(array_filter($participants)));
        
        // Update thread
        $this->threadRepository->update($thread, [
            'participants' => $participants,
            'message_count' => $emails->count(),
            'last_message_at' => $emails->max('sent_at')
        ]);
        
        $this->logger->debug('Updated thread metadata', [
            'thread_id' => $threadId,
            'message_count' => $emails->count(),
            'participants' => count($participants)
        ]);
    }
    
    /**
     * Find existing thread for email
     * 
     * @param ParsedEmail $email
     * @return int|null Thread ID
     */
    private function findExistingThread(ParsedEmail $email): ?int
    {
        // 1. Check In-Reply-To
        if ($email->threadingInfo->inReplyTo) {
            $parent = $this->emailRepository->findByMessageId($email->threadingInfo->inReplyTo);
            if ($parent && $parent->thread_id) {
                $this->logger->debug('Thread found via In-Reply-To', [
                    'thread_id' => $parent->thread_id,
                    'in_reply_to' => $email->threadingInfo->inReplyTo
                ]);
                return $parent->thread_id;
            }
        }
        
        // 2. Check References
        foreach ($email->threadingInfo->references as $refId) {
            $ref = $this->emailRepository->findByMessageId($refId);
            if ($ref && $ref->thread_id) {
                $this->logger->debug('Thread found via References', [
                    'thread_id' => $ref->thread_id,
                    'reference' => $refId
                ]);
                return $ref->thread_id;
            }
        }
        
        // 3. Check Subject + Time Window
        $threadId = $this->threadRepository->findBySubjectAndTimeWindow(
            $email->subject,
            $email->date
        );
        
        if ($threadId) {
            $this->logger->debug('Thread found via Subject matching', [
                'thread_id' => $threadId,
                'subject' => $email->subject
            ]);
        }
        
        return $threadId;
    }
    
    /**
     * Create new thread
     * 
     * @param ParsedEmail $email
     * @return Thread
     */
    private function createNewThread(ParsedEmail $email): Thread
    {
        $normalizedSubject = $this->normalizeSubject($email->subject);
        
        $thread = $this->threadRepository->create([
            'subject' => $normalizedSubject,
            'participants' => [$email->from],
            'message_count' => 1,
            'status' => 'open',
            'last_message_at' => $email->date
        ]);
        
        $this->logger->info('Created new thread', [
            'thread_id' => $thread->id,
            'subject' => $thread->subject
        ]);
        
        return $thread;
    }
    
    /**
     * Save email to database
     * 
     * @param ParsedEmail $parsedEmail
     * @param int $threadId
     * @return Email
     */
    private function saveEmail(ParsedEmail $parsedEmail, int $threadId): Email
    {
        // Prepare recipients as JSON with addresses array
        $toAddresses = !empty($parsedEmail->to) ? ['addresses' => $parsedEmail->to] : ['addresses' => []];
        $ccAddresses = !empty($parsedEmail->cc) ? ['addresses' => $parsedEmail->cc] : null;
        
        $email = $this->emailRepository->create([
            'thread_id' => $threadId,
            'imap_account_id' => 1, // TODO: Get from context
            'message_id' => $parsedEmail->messageId,
            'in_reply_to' => $parsedEmail->threadingInfo->inReplyTo,
            'subject' => $parsedEmail->subject,
            'from_email' => $parsedEmail->from,
            'from_name' => $parsedEmail->fromName ?? '',
            'to_addresses' => $toAddresses,
            'cc_addresses' => $ccAddresses,
            'body_plain' => $parsedEmail->bodyText,
            'body_html' => $parsedEmail->bodyHtml,
            'has_attachments' => !empty($parsedEmail->attachments),
            'attachment_metadata' => !empty($parsedEmail->attachments) ? array_map(function($att) {
                return [
                    'filename' => $att->filename,
                    'mime_type' => $att->mimeType,
                    'size' => $att->size
                ];
            }, $parsedEmail->attachments) : null,
            'sent_at' => $parsedEmail->date
        ]);
        
        $this->logger->info('Email saved', [
            'email_id' => $email->id,
            'thread_id' => $threadId,
            'message_id' => $parsedEmail->messageId
        ]);
        
        return $email;
    }
    
    /**
     * Normalize subject
     * 
     * @param string $subject
     * @return string
     */
    private function normalizeSubject(string $subject): string
    {
        $subject = preg_replace('/^(Re|RE|Fwd|FWD|AW|Aw|WG|Wg):\s*/i', '', $subject);
        $subject = preg_replace('/\s+/', ' ', $subject);
        return trim($subject);
    }
}

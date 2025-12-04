<?php
declare(strict_types=1);

namespace CiInbox\App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use CiInbox\App\Repositories\ImapAccountRepository;
use CiInbox\App\Repositories\EmailRepositoryInterface;
use CiInbox\App\Repositories\ThreadRepository;
use CiInbox\App\Repositories\LabelRepository;
use CiInbox\Modules\Imap\ImapClientInterface;
use CiInbox\Modules\Imap\Parser\EmailParserInterface;
use CiInbox\Modules\Imap\Manager\ThreadManagerInterface;
use CiInbox\Modules\Encryption\EncryptionInterface;
use Psr\Log\LoggerInterface;

/**
 * IMAP Controller
 * 
 * API endpoints für IMAP-Account-Verwaltung und E-Mail-Synchronisation
 */
class ImapController
{
    public function __construct(
        private ImapAccountRepository $accountRepository,
        private EmailRepositoryInterface $emailRepository,
        private ThreadRepository $threadRepository,
        private LabelRepository $labelRepository,
        private ImapClientInterface $imapClient,
        private EmailParserInterface $emailParser,
        private ThreadManagerInterface $threadManager,
        private EncryptionInterface $encryptionService,
        private LoggerInterface $logger
    ) {}
    
    /**
     * Synchronize IMAP account - fetch new emails
     * 
     * POST /api/imap/accounts/{id}/sync
     * 
     * @param Request $request
     * @param Response $response
     * @param array $args ['id' => account_id]
     * @return Response
     */
    public function syncAccount(Request $request, Response $response, array $args): Response
    {
        $accountId = (int) $args['id'];
        $startTime = microtime(true);
        
        $this->logger->info('API: IMAP sync requested', [
            'account_id' => $accountId
        ]);
        
        try {
            // 1. Account laden
            $account = $this->accountRepository->find($accountId);
            if (!$account) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => 'Account not found'
                ], 404);
            }
            
            if (!$account->is_active) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => 'Account is inactive'
                ], 400);
            }
            
            // 2. Passwort entschlüsseln
            $password = $this->encryptionService->decrypt($account->imap_password_encrypted);
            
            // 3. IMAP verbinden
            $ssl = in_array($account->imap_encryption, ['ssl', 'tls']);
            $connected = $this->imapClient->connect(
                $account->imap_host,
                $account->imap_port,
                $account->imap_username,
                $password,
                $ssl
            );
            
            if (!$connected) {
                throw new \Exception("Failed to connect to IMAP server");
            }
            
            $this->logger->debug('IMAP connected', [
                'account_id' => $accountId,
                'host' => $account->imap_host
            ]);
            
            // 4. Folder durchlaufen (INBOX, Sent, etc.)
            $folders = ['INBOX']; // TODO: Config oder Account-spezifisch
            $totalNewEmails = 0;
            $totalProcessed = 0;
            $errors = [];
            
            foreach ($folders as $folder) {
                try {
                    $this->imapClient->selectFolder($folder);
                    
                    // Neue E-Mails ermitteln
                    $result = $this->fetchNewEmailsFromFolder($accountId, $folder);
                    
                    $totalNewEmails += $result['new_emails'];
                    $totalProcessed += $result['processed'];
                    $errors = array_merge($errors, $result['errors']);
                    
                } catch (\Exception $e) {
                    $this->logger->error('Failed to sync folder', [
                        'account_id' => $accountId,
                        'folder' => $folder,
                        'error' => $e->getMessage()
                    ]);
                    
                    $errors[] = [
                        'folder' => $folder,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            // 5. Disconnect
            $this->imapClient->disconnect();
            
            // 6. Last-Sync-Timestamp updaten
            $this->accountRepository->updateLastSync($accountId, null);
            
            $duration = microtime(true) - $startTime;
            
            $this->logger->info('API: IMAP sync completed', [
                'account_id' => $accountId,
                'new_emails' => $totalNewEmails,
                'processed' => $totalProcessed,
                'errors' => count($errors),
                'duration' => round($duration, 2)
            ]);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'data' => [
                    'account_id' => $accountId,
                    'email' => $account->email,
                    'new_emails' => $totalNewEmails,
                    'processed' => $totalProcessed,
                    'failed' => count($errors),
                    'errors' => $errors,
                    'duration' => round($duration, 2)
                ]
            ], 200);
            
        } catch (\Exception $e) {
            $duration = microtime(true) - $startTime;
            
            $this->logger->error('API: IMAP sync failed', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Error in DB speichern
            $this->accountRepository->updateLastSync($accountId, $e->getMessage());
            
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => $e->getMessage(),
                'duration' => round($duration, 2)
            ], 500);
        }
    }
    
    /**
     * Fetch new emails from specific folder
     * Uses IMAP keyword "CI-Synced" as performance filter (DB remains SSOT)
     * 
     * @param int $accountId
     * @param string $folder
     * @return array ['new_emails' => int, 'processed' => int, 'errors' => array]
     */
    private function fetchNewEmailsFromFolder(int $accountId, string $folder): array
    {
        $this->logger->debug('Fetching emails from folder', [
            'account_id' => $accountId,
            'folder' => $folder
        ]);
        
        // Try to use keyword filter for performance (fallback to all messages)
        $candidateUids = $this->imapClient->search('UNKEYWORD CI-Synced');
        
        if (empty($candidateUids)) {
            $this->logger->debug('No unsynced messages (all have CI-Synced tag)', [
                'account_id' => $accountId,
                'folder' => $folder
            ]);
            
            // Check if keyword search is supported by getting all messages
            $allMessages = $this->imapClient->getMessages(10, false);
            if (empty($allMessages)) {
                return ['new_emails' => 0, 'processed' => 0, 'errors' => []];
            }
            
            $this->logger->warning('Keyword search returned empty but messages exist - server may not support keywords', [
                'account_id' => $accountId,
                'folder' => $folder
            ]);
            
            // Fallback: Get recent messages without keyword filter
            $messages = $this->imapClient->getMessages(1000, false);
        } else {
            $this->logger->info('Found candidate messages without CI-Synced tag', [
                'account_id' => $accountId,
                'folder' => $folder,
                'candidates' => count($candidateUids)
            ]);
            
            // Fetch only candidate messages
            $messages = [];
            foreach ($candidateUids as $uid) {
                try {
                    $messages[] = $this->imapClient->getMessage($uid);
                } catch (\Exception $e) {
                    $this->logger->warning('Failed to fetch message', [
                        'uid' => $uid,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        // Process each message (DB = SSOT, Tag = marker)
        $processed = 0;
        $skipped = 0;
        $errors = [];
        
        foreach ($messages as $imapMessage) {
            try {
                $messageId = $imapMessage->getMessageId();
                $uid = $imapMessage->getUid();
                
                // DB is SSOT: Check if email already exists
                if ($this->emailRepository->existsByMessageId($messageId)) {
                    $skipped++;
                    
                    // Repair: Set tag if missing (idempotent)
                    $this->imapClient->addKeyword($uid, 'CI-Synced');
                    
                    $this->logger->debug('Email exists - tag repaired', [
                        'message_id' => substr($messageId, 0, 50),
                        'uid' => $uid
                    ]);
                    
                    continue;
                }
                
                // Process new email: Parse → Thread → Store
                $emailId = $this->processEmail($imapMessage, $accountId, $folder);
                
                // Set sync tag AFTER successful DB save (Tag = Backup marker)
                $tagSet = $this->imapClient->addKeyword($uid, 'CI-Synced');
                
                if (!$tagSet) {
                    $this->logger->warning('Email saved but tag not set (server may not support keywords)', [
                        'email_id' => $emailId,
                        'uid' => $uid
                    ]);
                }
                
                $processed++;
                
            } catch (\Exception $e) {
                $this->logger->error('Failed to process email', [
                    'account_id' => $accountId,
                    'folder' => $folder,
                    'uid' => $imapMessage->getUid(),
                    'error' => $e->getMessage()
                ]);
                
                $errors[] = [
                    'uid' => $imapMessage->getUid(),
                    'error' => $e->getMessage()
                ];
                
                // Tag wird NICHT gesetzt → beim nächsten Poll erneut versuchen
            }
        }
        
        $this->logger->info('Folder processing completed', [
            'account_id' => $accountId,
            'folder' => $folder,
            'total_messages' => count($messages),
            'processed' => $processed,
            'skipped' => $skipped,
            'errors' => count($errors)
        ]);
        
        return [
            'new_emails' => $processed,
            'processed' => $processed,
            'errors' => $errors
        ];
    }
    
    /**
     * Process single email: Parse → Thread → Store → Auto-Label
     * 
     * @param \CiInbox\Modules\Imap\ImapMessageInterface $imapMessage
     * @param int $accountId
     * @param string $folder
     * @return int Email ID
     */
    private function processEmail(
        \CiInbox\Modules\Imap\ImapMessageInterface $imapMessage,
        int $accountId,
        string $folder
    ): int {
        // 1. Parse email
        $parsedEmail = $this->emailParser->parseMessage($imapMessage);
        
        // 2. Find or create thread (simple logic)
        $threadId = $this->findOrCreateThread($parsedEmail);
        
        // 3. Store in database
        $emailData = [
            'imap_account_id' => $accountId,
            'thread_id' => $threadId,
            'message_id' => $parsedEmail->messageId,
            'in_reply_to' => $parsedEmail->threadingInfo->inReplyTo ?? null,
            'references' => !empty($parsedEmail->threadingInfo->references)
                ? json_encode($parsedEmail->threadingInfo->references)
                : null,
            'subject' => $parsedEmail->subject,
            'from_email' => $parsedEmail->from['email'] ?? 'unknown@localhost',
            'from_name' => $parsedEmail->from['name'] ?? 'Unknown Sender',
            'to_addresses' => !empty($parsedEmail->to)
                ? json_encode($parsedEmail->to)
                : null,
            'cc_addresses' => !empty($parsedEmail->cc)
                ? json_encode($parsedEmail->cc)
                : null,
            'sent_at' => $parsedEmail->date,
            'body_plain' => $parsedEmail->bodyText,
            'body_html' => $parsedEmail->bodyHtml,
            'has_attachments' => !empty($parsedEmail->attachments),
            'attachment_metadata' => !empty($parsedEmail->attachments)
                ? json_encode($parsedEmail->attachments)
                : null,
            'direction' => 'incoming',
            'is_read' => false
        ];
        
        $email = $this->emailRepository->create($emailData);
        
        $this->logger->debug('Email stored', [
            'email_id' => $email->id,
            'thread_id' => $threadId,
            'subject' => substr($parsedEmail->subject, 0, 50)
        ]);
        
        // 4. Update thread metadata
        $this->updateThreadMetadata($threadId);
        
        // 5. Auto-assign labels based on folder
        $this->autoAssignLabels($threadId, $folder);
        
        return $email->id;
    }
    
    /**
     * Find existing thread or create new one
     * Simple threading logic based on subject + time window
     */
    private function findOrCreateThread(\CiInbox\Modules\Imap\Parser\ParsedEmail $parsedEmail): int
    {
        // Try to find thread by subject within 30 days
        $threadId = $this->threadRepository->findBySubjectAndTimeWindow(
            $parsedEmail->subject,
            $parsedEmail->date,
            30
        );
        
        if ($threadId) {
            $this->logger->debug('Found existing thread', [
                'thread_id' => $threadId,
                'subject' => substr($parsedEmail->subject, 0, 50)
            ]);
            return $threadId;
        }
        
        // Create new thread
        $thread = $this->threadRepository->create([
            'subject' => $parsedEmail->subject,
            'last_message_at' => $parsedEmail->date,
            'email_count' => 0
        ]);
        
        $this->logger->debug('Created new thread', [
            'thread_id' => $thread->id,
            'subject' => substr($parsedEmail->subject, 0, 50)
        ]);
        
        return $thread->id;
    }
    
    /**
     * Update thread metadata (email count, last activity)
     */
    private function updateThreadMetadata(int $threadId): void
    {
        $emailCount = $this->emailRepository->findByThreadId($threadId)->count();
        $lastEmail = $this->emailRepository->findByThreadId($threadId)
            ->sortByDesc('sent_at')
            ->first();
        
        if ($lastEmail) {
            $thread = $this->threadRepository->find($threadId);
            $thread->email_count = $emailCount;
            $thread->last_activity_at = $lastEmail->sent_at;
            $thread->save();
        }
    }
    
    /**
     * Auto-assign labels based on IMAP folder
     */
    private function autoAssignLabels(int $threadId, string $folder): void
    {
        // Map IMAP folder to system label
        $folderLabelMap = [
            'INBOX' => 'Inbox',
            'Sent' => 'Sent',
            'Drafts' => 'Draft',
            'Trash' => 'Trash',
            'Spam' => 'Spam',
        ];
        
        $labelName = $folderLabelMap[$folder] ?? null;
        if (!$labelName) {
            return; // Kein Label für diesen Folder
        }
        
        // Label suchen oder erstellen
        $label = $this->labelRepository->findByName($labelName);
        if (!$label) {
            $this->logger->warning('Label not found for auto-assignment', [
                'label' => $labelName,
                'folder' => $folder
            ]);
            return;
        }
        
        // Thread-Label-Assignment (prevent duplicates)
        $thread = $this->threadRepository->find($threadId);
        if (!$thread->labels()->where('label_id', $label->id)->exists()) {
            $thread->labels()->attach($label->id);
            
            $this->logger->debug('Auto-assigned label', [
                'thread_id' => $threadId,
                'label' => $labelName,
                'folder' => $folder
            ]);
        }
    }
    
    /**
     * Helper: JSON response
     */
    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}

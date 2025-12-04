<?php

declare(strict_types=1);

namespace CiInbox\App\Services;

use CiInbox\App\Models\Email;
use CiInbox\App\Models\Thread;
use CiInbox\App\Repositories\EmailRepositoryInterface;
use CiInbox\App\Repositories\ThreadRepositoryInterface;
use CiInbox\Modules\Smtp\SmtpClientInterface;
use CiInbox\Modules\Smtp\SmtpConfig;
use CiInbox\Modules\Smtp\EmailMessage;
use CiInbox\Modules\Logger\LoggerService;
use Carbon\Carbon;

/**
 * Email Send Service
 * 
 * Business logic for sending emails via SMTP
 */
class EmailSendService
{
    public function __construct(
        private SmtpClientInterface $smtpClient,
        private EmailRepositoryInterface $emailRepository,
        private ThreadRepositoryInterface $threadRepository,
        private LoggerService $logger,
        private SmtpConfig $smtpConfig,
        private ?WebhookService $webhookService = null
    ) {}

    /**
     * Send new email
     * 
     * @param array $data Email data
     * @return Email Sent email record
     */
    public function sendEmail(array $data): Email
    {
        $this->logger->info('Sending new email', [
            'subject' => $data['subject'],
            'to' => $data['to']
        ]);

        // Connect to SMTP
        $this->smtpClient->connect($this->smtpConfig);
        
        // Get signature if provided
        $signature = $this->getSignature($data);
        
        // Prepare body with signature
        $bodyText = $data['body_text'] ?? strip_tags($data['body_html'] ?? '');
        $bodyHtml = $data['body_html'] ?? nl2br($data['body_text'] ?? '');
        
        if ($signature) {
            $bodyText .= "\n\n" . strip_tags($signature);
            $bodyHtml .= "<br /><br />" . nl2br($signature);
        }

        // Create message
        $message = new EmailMessage(
            subject: $data['subject'],
            bodyText: $bodyText,
            bodyHtml: $bodyHtml,
            to: $this->parseRecipients($data['to']),
            cc: $this->parseRecipients($data['cc'] ?? []),
            bcc: $this->parseRecipients($data['bcc'] ?? []),
            attachments: $data['attachments'] ?? []
        );

        // Send via SMTP
        $this->smtpClient->send($message);
        $this->smtpClient->disconnect();

        // Generate Message-ID
        $messageId = $this->generateMessageId();
        
        // Create thread if not provided
        $threadId = $data['thread_id'] ?? null;
        if ($threadId === null) {
            $thread = $this->createNewThreadForEmail($data);
            $threadId = $thread->id;
            $this->logger->info('Created new thread for outgoing email', [
                'thread_id' => $threadId,
                'subject' => $data['subject']
            ]);
        }

        // Save to database
        $email = new Email();
        $email->thread_id = $threadId;
        $email->imap_account_id = $data['imap_account_id'];
        $email->message_id = $messageId;
        $email->subject = $data['subject'];
        $email->from_email = $this->smtpConfig->fromEmail;
        $email->from_name = $this->smtpConfig->fromName;
        $email->to_addresses = $this->parseRecipients($data['to']);
        $email->cc_addresses = !empty($data['cc']) ? $this->parseRecipients($data['cc']) : null;
        $email->body_plain = $data['body_text'] ?? strip_tags($data['body_html'] ?? '');
        $email->body_html = $data['body_html'] ?? nl2br($data['body_text'] ?? '');
        $email->direction = 'outgoing';
        $email->sent_at = Carbon::now();
        
        $this->emailRepository->save($email);

        $this->logger->info('Email sent and saved', [
            'email_id' => $email->id,
            'message_id' => $messageId
        ]);

        // Dispatch webhook event
        if ($this->webhookService) {
            $this->webhookService->dispatch('email.sent', [
                'email_id' => $email->id,
                'thread_id' => $email->thread_id,
                'subject' => $email->subject,
                'to' => $email->to_addresses,
                'from' => $this->smtpConfig->fromEmail,
                'sent_at' => $email->sent_at->toIso8601String()
            ]);
        }

        return $email;
    }

    /**
     * Reply to thread
     * 
     * @param int $threadId Thread ID
     * @param string $body Reply body
     * @param int $imapAccountId IMAP account ID
     * @return Email Sent reply
     */
    public function replyToThread(int $threadId, string $body, int $imapAccountId): Email
    {
        $thread = $this->threadRepository->findById($threadId);

        if (!$thread) {
            throw new \Exception("Thread not found: {$threadId}");
        }

        $this->logger->info('Replying to thread', [
            'thread_id' => $threadId,
            'subject' => $thread->subject
        ]);

        // Get original emails for threading headers
        $originalEmails = $this->emailRepository->findByThreadId($threadId);
        $latestEmail = $originalEmails->sortByDesc('sent_at')->first();

        if (!$latestEmail) {
            throw new \Exception("Thread has no emails: {$threadId}");
        }

        // Extract recipients (reply to sender)
        $to = [['email' => $latestEmail->from_email, 'name' => $latestEmail->from_name]];

        // Build references header
        $references = [];
        if ($latestEmail->in_reply_to) {
            $references[] = $latestEmail->in_reply_to;
        }
        $references[] = $latestEmail->message_id;

        // Connect to SMTP
        $this->smtpClient->connect($this->smtpConfig);

        // Create reply message
        $message = new EmailMessage(
            subject: "Re: " . $thread->subject,
            bodyText: $body,
            bodyHtml: nl2br($body),
            // TODO: EMAIL_SIGNATURE - Add signature support for replies
            // - Same as sendEmail() but for reply context
            // - Consider quote formatting vs signature placement
            to: $to,
            inReplyTo: $latestEmail->message_id,
            references: $references
        );

        // Send via SMTP
        $this->smtpClient->send($message);
        $this->smtpClient->disconnect();

        // Generate Message-ID
        $messageId = $this->generateMessageId();

        // Save to database
        $email = new Email();
        $email->thread_id = $threadId;
        $email->imap_account_id = $imapAccountId;
        $email->message_id = $messageId;
        $email->in_reply_to = $latestEmail->message_id;
        $email->subject = "Re: " . $thread->subject;
        $email->from_email = $this->smtpConfig->fromEmail;
        $email->from_name = $this->smtpConfig->fromName;
        $email->to_addresses = $to;
        $email->body_plain = $body;
        $email->body_html = nl2br($body);
        $email->direction = 'outgoing';
        $email->sent_at = Carbon::now();
        
        $this->emailRepository->save($email);

        $this->logger->info('Reply sent and saved', [
            'email_id' => $email->id,
            'thread_id' => $threadId
        ]);

        return $email;
    }

    /**
     * Forward thread
     * 
     * @param int $threadId Thread ID
     * @param array $recipients Forward recipients
     * @param string|null $note Optional note to prepend
     * @param int $imapAccountId IMAP account ID
     * @return Email Forwarded email
     */
    public function forwardThread(int $threadId, array $recipients, ?string $note, int $imapAccountId): Email
    {
        $thread = $this->threadRepository->findById($threadId);

        if (!$thread) {
            throw new \Exception("Thread not found: {$threadId}");
        }

        $this->logger->info('Forwarding thread', [
            'thread_id' => $threadId,
            'recipients' => count($recipients)
        ]);

        // Get all emails in thread
        $emails = $this->emailRepository->findByThreadId($threadId);

        // Build forwarded body
        $forwardedBody = $note ? $note . "\n\n---\n\n" : '';
        foreach ($emails as $email) {
            $forwardedBody .= "From: {$email->from_name} <{$email->from_email}>\n";
            $forwardedBody .= "Date: " . $email->sent_at->format('Y-m-d H:i') . "\n";
            $forwardedBody .= "Subject: {$email->subject}\n\n";
            $forwardedBody .= $email->body_plain . "\n\n---\n\n";
        }

        // Connect to SMTP
        $this->smtpClient->connect($this->smtpConfig);

        // Create forward message
        $message = new EmailMessage(
            subject: "Fwd: " . $thread->subject,
            bodyText: $forwardedBody,
            bodyHtml: nl2br($forwardedBody),
            to: $this->parseRecipients($recipients)
        );

        // Send via SMTP
        $this->smtpClient->send($message);
        $this->smtpClient->disconnect();

        // Generate Message-ID
        $messageId = $this->generateMessageId();

        // Save to database
        $email = new Email();
        $email->thread_id = $threadId;
        $email->imap_account_id = $imapAccountId;
        $email->message_id = $messageId;
        $email->subject = "Fwd: " . $thread->subject;
        $email->from_email = $this->smtpConfig->fromEmail;
        $email->from_name = $this->smtpConfig->fromName;
        $email->to_addresses = $this->parseRecipients($recipients);
        $email->body_plain = $forwardedBody;
        $email->body_html = nl2br($forwardedBody);
        $email->direction = 'outgoing';
        $email->sent_at = Carbon::now();
        
        $this->emailRepository->save($email);

        $this->logger->info('Forward sent and saved', [
            'email_id' => $email->id,
            'thread_id' => $threadId
        ]);

        return $email;
    }

    /**
     * Parse recipients array
     */
    private function parseRecipients($recipients): array
    {
        if (is_string($recipients)) {
            return [['email' => $recipients, 'name' => '']];
        }

        return array_map(function($recipient) {
            if (is_string($recipient)) {
                return ['email' => $recipient, 'name' => ''];
            }
            return $recipient;
        }, $recipients);
    }

    /**
     * Generate unique Message-ID
     */
    private function generateMessageId(): string
    {
        $domain = parse_url($this->smtpConfig->fromEmail, PHP_URL_HOST) 
            ?? explode('@', $this->smtpConfig->fromEmail)[1] 
            ?? 'localhost';
        
        return '<' . uniqid('msg_', true) . '@' . $domain . '>';
    }
    
    /**
     * Create new thread for outgoing email
     * 
     * @param array $data Email data
     * @return Thread
     */
    private function createNewThreadForEmail(array $data): Thread
    {
        // Normalize subject (remove Re:, Fwd:, etc.)
        $subject = $data['subject'];
        $normalizedSubject = preg_replace('/^(Re|Fwd|Aw):\\s*/i', '', $subject);
        
        // Extract participants from recipients
        $participants = [];
        foreach ($this->parseRecipients($data['to']) as $recipient) {
            $participants[] = $recipient['email'];
        }
        
        $thread = $this->threadRepository->create([
            'subject' => $normalizedSubject,
            'participants' => $participants,
            'message_count' => 1,
            'status' => 'open',
            'last_message_at' => Carbon::now(),
            'sender_email' => $this->smtpConfig->fromEmail,
            'sender_name' => $this->smtpConfig->fromName
        ]);
        
        return $thread;
    }
    
    /**
     * Get signature for email
     * 
     * @param array $data Email data
     * @return string|null Signature content or null
     */
    private function getSignature(array $data): ?string
    {
        // Check if signature_id is provided
        if (isset($data['signature_id'])) {
            try {
                $signature = \CiInbox\App\Models\Signature::find($data['signature_id']);
                if ($signature) {
                    return $signature->content;
                }
            } catch (\Exception $e) {
                $this->logger->warning('Failed to load signature', [
                    'signature_id' => $data['signature_id'],
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Try to load default signature for user
        if (isset($data['user_id'])) {
            try {
                $signature = \CiInbox\App\Models\Signature::where('user_id', $data['user_id'])
                    ->where('is_default', true)
                    ->first();
                if ($signature) {
                    $this->logger->debug('Using default signature', ['signature_id' => $signature->id]);
                    return $signature->content;
                }
            } catch (\Exception $e) {
                $this->logger->warning('Failed to load default signature', [
                    'user_id' => $data['user_id'],
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return null;
    }
}

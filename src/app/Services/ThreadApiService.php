<?php

declare(strict_types=1);

namespace CiInbox\App\Services;

use CiInbox\App\Models\Thread;
use CiInbox\App\Models\Email;
use CiInbox\App\Models\InternalNote;
use CiInbox\App\Repositories\ThreadRepositoryInterface;
use CiInbox\App\Repositories\EmailRepositoryInterface;
use CiInbox\App\Repositories\NoteRepositoryInterface;
use CiInbox\Modules\Logger\LoggerService;
use Illuminate\Database\Capsule\Manager as DB;
use Carbon\Carbon;

/**
 * Extended Thread Service with Advanced Operations
 * 
 * Provides advanced thread management operations including:
 * - Basic CRUD operations
 * - Email assignment to threads
 * - Thread splitting
 * - Thread merging
 * - Email moving between threads
 */
class ThreadApiService
{
    public function __construct(
        private ThreadRepositoryInterface $threadRepository,
        private EmailRepositoryInterface $emailRepository,
        private NoteRepositoryInterface $noteRepository,
        private LoggerService $logger,
        private ?WebhookService $webhookService = null
    ) {}

    /**
     * Create a new thread
     */
    public function createThread(array $data): Thread
    {
        $this->logger->info('Creating new thread', ['subject' => $data['subject'] ?? 'N/A']);

        $thread = new Thread();
        $thread->subject = $data['subject'];
        $thread->status = $data['status'] ?? 'open';
        $thread->participants = $data['participants'] ?? [];
        $thread->preview = $data['preview'] ?? null;
        $thread->last_message_at = Carbon::now();
        $thread->message_count = 0;
        $thread->has_attachments = false;

        $this->threadRepository->save($thread);

        // Create system note
        if (!empty($data['note'])) {
            $this->createSystemNote($thread->id, "Thread created: " . $data['note']);
        }

        $this->logger->info('Thread created', ['thread_id' => $thread->id]);

        // Dispatch webhook event
        if ($this->webhookService) {
            $this->webhookService->dispatch('thread.created', [
                'thread_id' => $thread->id,
                'subject' => $thread->subject,
                'status' => $thread->status,
                'created_at' => $thread->created_at->toIso8601String()
            ]);
        }

        return $thread;
    }

    /**
     * Get thread by ID with related data
     */
    public function getThread(int $id, bool $withEmails = true, bool $withNotes = true): ?Thread
    {
        $thread = $this->threadRepository->findById($id);

        if (!$thread) {
            return null;
        }

        if ($withEmails) {
            $thread->load('emails');
        }

        if ($withNotes) {
            $thread->load('notes');
        }

        return $thread;
    }

    /**
     * List threads with filters
     */
    public function listThreads(array $filters = []): array
    {
        $threads = $this->threadRepository->findAll($filters);

        return [
            'threads' => $threads->toArray(),
            'total' => $threads->count(),
        ];
    }

    /**
     * Update thread
     */
    public function updateThread(int $id, array $data): Thread
    {
        $thread = $this->threadRepository->findById($id);

        if (!$thread) {
            throw new \Exception("Thread not found: {$id}");
        }

        $this->logger->info('Updating thread', ['thread_id' => $id]);

        $changes = [];

        if (isset($data['subject']) && $data['subject'] !== $thread->subject) {
            $changes['subject'] = ['old' => $thread->subject, 'new' => $data['subject']];
            $thread->subject = $data['subject'];
        }

        if (isset($data['status']) && $data['status'] !== $thread->status) {
            $changes['status'] = ['old' => $thread->status, 'new' => $data['status']];
            $thread->status = $data['status'];
            
            // Set closed_at timestamp when status changes to 'closed'
            if ($data['status'] === 'closed' && $thread->status !== 'closed') {
                $thread->closed_at = new \DateTime();
            }
            
            // Clear closed_at when reopening
            if ($data['status'] !== 'closed' && $thread->status === 'closed') {
                $thread->closed_at = null;
            }
        }

        $this->threadRepository->save($thread);

        // Log changes
        if (!empty($changes)) {
            $changeLog = "Thread updated:\n";
            foreach ($changes as $field => $change) {
                $changeLog .= "- {$field}: {$change['old']} → {$change['new']}\n";
            }
            $this->createSystemNote($thread->id, $changeLog);
        }

        $this->logger->info('Thread updated', ['thread_id' => $id, 'changes' => array_keys($changes)]);

        // Dispatch webhook event
        if ($this->webhookService && !empty($changes)) {
            $this->webhookService->dispatch('thread.updated', [
                'thread_id' => $thread->id,
                'subject' => $thread->subject,
                'status' => $thread->status,
                'changes' => $changes,
                'updated_at' => $thread->updated_at->toIso8601String()
            ]);
        }

        return $thread;
    }

    /**
     * Delete thread
     */
    public function deleteThread(int $id): bool
    {
        $thread = $this->threadRepository->findById($id);

        if (!$thread) {
            throw new \Exception("Thread not found: {$id}");
        }

        $this->logger->info('Deleting thread', ['thread_id' => $id]);

        // Store thread data for webhook before deletion
        $threadData = [
            'thread_id' => $thread->id,
            'subject' => $thread->subject,
            'deleted_at' => Carbon::now()->toIso8601String()
        ];

        $result = $this->threadRepository->delete($id);

        if ($result) {
            $this->logger->info('Thread deleted', ['thread_id' => $id]);
            
            // Dispatch webhook event
            if ($this->webhookService) {
                $this->webhookService->dispatch('thread.deleted', $threadData);
            }
        }

        return $result;
    }

    /**
     * Add note to thread
     */
    public function addNote(int $threadId, string $content, ?int $userId = null, ?int $position = null): InternalNote
    {
        $thread = $this->threadRepository->findById($threadId);

        if (!$thread) {
            throw new \Exception("Thread not found: {$threadId}");
        }

        $this->logger->info('Adding note to thread', ['thread_id' => $threadId, 'position' => $position]);

        $note = new InternalNote();
        $note->thread_id = $threadId;
        $note->user_id = $userId;
        $note->content = $content;
        $note->type = 'user';
        
        // Calculate position if provided (for ordering notes between emails)
        if ($position !== null) {
            // Position is the index after which to insert (0 = before first email)
            // Load emails to calculate proper position value
            $emails = $thread->emails()->orderBy('sent_at')->get();
            
            if ($position == 0) {
                // Before first email
                $note->position = 50;
            } elseif ($position >= count($emails)) {
                // After last email
                $note->position = (count($emails) * 100) + 50;
            } else {
                // Between emails: position * 100 + 50
                // E.g., between email 1 (100) and email 2 (200) = 150
                $note->position = ($position * 100) + 50;
            }
        }

        $this->noteRepository->save($note);

        // Update thread activity

        $this->threadRepository->save($thread);

        $this->logger->info('Note added', ['note_id' => $note->id, 'thread_id' => $threadId]);

        // Dispatch webhook event
        if ($this->webhookService) {
            $this->webhookService->dispatch('note.added', [
                'note_id' => $note->id,
                'thread_id' => $threadId,
                'user_id' => $userId,
                'content' => $note->content,
                'created_at' => $note->created_at->toIso8601String()
            ]);
        }

        return $note;
    }

    /**
     * Update note content
     * 
     * @param int $threadId
     * @param int $noteId
     * @param string $content
     * @param int|null $userId User who is updating the note
     * @return InternalNote
     * @throws \Exception
     */
    public function updateNote(int $threadId, int $noteId, string $content, ?int $userId = null): InternalNote
    {
        $note = $this->noteRepository->findById($noteId);

        if (!$note) {
            throw new \Exception("Note not found: {$noteId}");
        }

        if ($note->thread_id !== $threadId) {
            throw new \Exception("Note does not belong to thread {$threadId}");
        }

        $this->logger->info('Updating note', [
            'note_id' => $noteId,
            'thread_id' => $threadId,
            'user_id' => $userId
        ]);

        // Update content and track who edited
        $note->content = $content;
        $note->updated_by_user_id = $userId;
        
        // Note: updated_at is automatically updated by Eloquent
        
        $this->noteRepository->save($note);

        // Update thread activity
        $thread = $this->threadRepository->findById($threadId);
        if ($thread) {
            $this->threadRepository->save($thread);
        }

        $this->logger->info('Note updated', ['note_id' => $note->id, 'thread_id' => $threadId]);

        // Dispatch webhook event
        if ($this->webhookService) {
            $this->webhookService->dispatch('note.updated', [
                'note_id' => $note->id,
                'thread_id' => $threadId,
                'user_id' => $userId,
                'content' => $note->content,
                'updated_at' => $note->updated_at->toIso8601String()
            ]);
        }

        return $note;
    }

    /**
     * Delete note from thread
     * 
     * @param int $threadId
     * @param int $noteId
     * @throws \Exception
     */
    public function deleteNote(int $threadId, int $noteId): void
    {
        $note = $this->noteRepository->findById($noteId);

        if (!$note) {
            throw new \Exception("Note not found: {$noteId}");
        }

        if ($note->thread_id !== $threadId) {
            throw new \Exception("Note does not belong to thread {$threadId}");
        }

        $this->logger->info('Deleting note', ['note_id' => $noteId, 'thread_id' => $threadId]);

        $this->noteRepository->delete($noteId);

        $this->logger->info('Note deleted', ['note_id' => $noteId, 'thread_id' => $threadId]);

        // Dispatch webhook event
        if ($this->webhookService) {
            $this->webhookService->dispatch('note.deleted', [
                'note_id' => $noteId,
                'thread_id' => $threadId
            ]);
        }
    }

    /**
     * Assign email to thread (re-threading)
     * 
     * Moves an email from one thread to another
     */
    public function assignEmailToThread(int $threadId, int $emailId): Thread
    {
        $thread = $this->threadRepository->findById($threadId);
        $email = $this->emailRepository->findById($emailId);

        if (!$thread) {
            throw new \Exception("Thread not found: {$threadId}");
        }

        if (!$email) {
            throw new \Exception("Email not found: {$emailId}");
        }

        DB::beginTransaction();

        try {
            $oldThreadId = $email->thread_id;

            $this->logger->info('Assigning email to thread', [
                'email_id' => $emailId,
                'old_thread_id' => $oldThreadId,
                'new_thread_id' => $threadId
            ]);

            // Update email's thread
            $email->thread_id = $threadId;
            $this->emailRepository->save($email);

            // Update target thread

            $this->threadRepository->save($thread);

            // Create system notes
            $this->createSystemNote($threadId, "Email #{$emailId} assigned from Thread #{$oldThreadId}");
            
            if ($oldThreadId !== $threadId) {
                $this->createSystemNote($oldThreadId, "Email #{$emailId} moved to Thread #{$threadId}");
            }

            DB::commit();

            $this->logger->info('Email assigned to thread', [
                'email_id' => $emailId,
                'thread_id' => $threadId
            ]);

            return $thread->fresh(['emails', 'notes']);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logger->error('Failed to assign email to thread', [
                'error' => $e->getMessage(),
                'email_id' => $emailId,
                'thread_id' => $threadId
            ]);
            throw $e;
        }
    }

    /**
     * Split thread into two threads
     * 
     * Creates a new thread with selected emails
     */
    public function splitThread(int $threadId, array $emailIds, string $newSubject): Thread
    {
        $thread = $this->threadRepository->findById($threadId);

        if (!$thread) {
            throw new \Exception("Thread not found: {$threadId}");
        }

        $emails = $this->emailRepository->findByIds($emailIds);

        if ($emails->isEmpty()) {
            throw new \Exception("No emails found with provided IDs");
        }

        // Validate: Original thread must keep at least 1 email
        $threadEmails = $this->emailRepository->findByThreadId($threadId);
        if ($threadEmails->count() <= count($emailIds)) {
            throw new \Exception("Cannot split: Original thread must retain at least 1 email");
        }

        DB::beginTransaction();

        try {
            $this->logger->info('Splitting thread', [
                'thread_id' => $threadId,
                'email_count' => count($emailIds),
                'new_subject' => $newSubject
            ]);

            // Create new thread
            $newThread = new Thread();
            $newThread->subject = $newSubject;
            $newThread->status = $thread->status;
            $newThread->participants = $thread->participants;
            $newThread->last_message_at = Carbon::now();
            $newThread->message_count = count($emailIds);
            $newThread->has_attachments = $thread->has_attachments;
            $this->threadRepository->save($newThread);

            // Move emails to new thread
            foreach ($emails as $email) {
                $email->thread_id = $newThread->id;
                $this->emailRepository->save($email);
            }

            // Update original thread
            $this->threadRepository->save($thread);

            // Create system notes
            $this->createSystemNote($thread->id, "Thread split: {$emails->count()} email(s) moved to Thread #{$newThread->id}");
            $this->createSystemNote($newThread->id, "Thread created by splitting from Thread #{$threadId}");

            DB::commit();

            $this->logger->info('Thread split successfully', [
                'original_thread_id' => $threadId,
                'new_thread_id' => $newThread->id
            ]);

            return $newThread->fresh(['emails', 'notes']);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logger->error('Failed to split thread', [
                'error' => $e->getMessage(),
                'thread_id' => $threadId
            ]);
            throw $e;
        }
    }

    /**
     * Merge two threads
     * 
     * Moves all emails from source thread to target thread
     */
    public function mergeThreads(int $targetThreadId, int $sourceThreadId): Thread
    {
        $targetThread = $this->threadRepository->findById($targetThreadId);
        $sourceThread = $this->threadRepository->findById($sourceThreadId);

        if (!$targetThread) {
            throw new \Exception("Target thread not found: {$targetThreadId}");
        }

        if (!$sourceThread) {
            throw new \Exception("Source thread not found: {$sourceThreadId}");
        }

        if ($targetThreadId === $sourceThreadId) {
            throw new \Exception("Cannot merge thread with itself");
        }

        DB::beginTransaction();

        try {
            $this->logger->info('Merging threads', [
                'target_thread_id' => $targetThreadId,
                'source_thread_id' => $sourceThreadId
            ]);

            // Get all emails from source thread
            $sourceEmails = $this->emailRepository->findByThreadId($sourceThreadId);

            // Move emails to target thread
            foreach ($sourceEmails as $email) {
                $email->thread_id = $targetThreadId;
                $this->emailRepository->save($email);
            }

            // Update target thread

            $this->threadRepository->save($targetThread);

            // Archive source thread (set status to 'archived')
            $sourceThread->status = 'archived';
            $this->threadRepository->save($sourceThread);

            // Create system notes
            $this->createSystemNote($targetThreadId, "Thread merged: {$sourceEmails->count()} email(s) from Thread #{$sourceThreadId}");
            $this->createSystemNote($sourceThreadId, "Thread merged into Thread #{$targetThreadId} (archived)");

            DB::commit();

            $this->logger->info('Threads merged successfully', [
                'target_thread_id' => $targetThreadId,
                'source_thread_id' => $sourceThreadId,
                'emails_moved' => $sourceEmails->count()
            ]);

            return $targetThread->fresh(['emails', 'notes']);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logger->error('Failed to merge threads', [
                'error' => $e->getMessage(),
                'target_thread_id' => $targetThreadId,
                'source_thread_id' => $sourceThreadId
            ]);
            throw $e;
        }
    }

    /**
     * Move single email to another thread
     * 
     * Similar to assignEmailToThread but with validation that source thread keeps at least 1 email
     */
    public function moveEmailToThread(int $emailId, int $newThreadId): Email
    {
        $email = $this->emailRepository->findById($emailId);
        $newThread = $this->threadRepository->findById($newThreadId);

        if (!$email) {
            throw new \Exception("Email not found: {$emailId}");
        }

        if (!$newThread) {
            throw new \Exception("Thread not found: {$newThreadId}");
        }

        $oldThreadId = $email->thread_id;

        if ($oldThreadId === $newThreadId) {
            throw new \Exception("Email is already in target thread");
        }

        // Validate: Original thread must keep at least 1 email
        $oldThreadEmails = $this->emailRepository->findByThreadId($oldThreadId);
        if ($oldThreadEmails->count() <= 1) {
            throw new \Exception("Cannot move: Original thread must retain at least 1 email");
        }

        DB::beginTransaction();

        try {
            $this->logger->info('Moving email to thread', [
                'email_id' => $emailId,
                'old_thread_id' => $oldThreadId,
                'new_thread_id' => $newThreadId
            ]);

            // Update email
            $email->thread_id = $newThreadId;
            $this->emailRepository->save($email);

            // Update both threads
            $oldThread = $this->threadRepository->findById($oldThreadId);

            $this->threadRepository->save($oldThread);


            $this->threadRepository->save($newThread);

            // Create system notes
            $this->createSystemNote($newThreadId, "Email #{$emailId} moved from Thread #{$oldThreadId}");
            $this->createSystemNote($oldThreadId, "Email #{$emailId} moved to Thread #{$newThreadId}");

            DB::commit();

            $this->logger->info('Email moved to thread', [
                'email_id' => $emailId,
                'new_thread_id' => $newThreadId
            ]);

            return $email->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logger->error('Failed to move email to thread', [
                'error' => $e->getMessage(),
                'email_id' => $emailId,
                'new_thread_id' => $newThreadId
            ]);
            throw $e;
        }
    }

    /**
     * Get thread details with all related data for display
     * 
     * Returns thread with emails (sorted by sent_at), labels, and notes
     * formatted for the detail view.
     */
    public function getThreadDetails(int $id): array
    {
        $thread = $this->threadRepository->findById($id);

        if (!$thread) {
            throw new \Exception("Thread not found: {$id}");
        }

        // Load relationships
        $thread->load(['emails', 'labels', 'notes.user', 'notes.updatedByUser', 'assignedUsers']);

        // Sort emails chronologically
        $emails = $thread->emails->sortBy('sent_at')->values();
        
        // Get unique senders for avatar stack
        $senders = $emails->map(function($email) {
            $name = $email->from_name ?: $email->from_email;
            $initials = strtoupper(substr($name, 0, 1));
            if (str_contains($name, ' ')) {
                $parts = explode(' ', $name);
                $initials = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
            }
            return [
                'email' => $email->from_email,
                'name' => $name,
                'initials' => $initials
            ];
        })->unique('email')->values()->toArray();

        // Format emails data with type and sort_key
        $emailsData = $emails->map(function ($email, $index) {
            return [
                'type' => 'email',
                'id' => $email->id,
                'sort_key' => $email->sent_at->timestamp,
                'position' => ($index + 1) * 100, // 100, 200, 300...
                'from_name' => $email->from_name,
                'from_email' => $email->from_email,
                'to_email' => $email->to_email,
                'to_name' => $email->to_name,
                'subject' => $email->subject,
                'body_html' => $email->body_html,
                'body_plain' => $email->body_plain,
                'sent_at' => $email->sent_at->toIso8601String(),
                'sent_at_human' => $email->sent_at->format('d.m.Y H:i'),
                'has_attachments' => $email->has_attachments,
                'attachments' => $email->attachments ? json_decode($email->attachments, true) : [],
                'is_read' => (bool) $email->is_read,
            ];
        });

        // Format notes with type and calculate position
        $notesData = $thread->notes->map(function ($note) use ($emailsData) {
            // If note has explicit position, use it; otherwise use created_at for sorting
            if ($note->position !== null) {
                // Position is between emails (e.g., 150 = between email 1 and 2)
                $sortKey = $note->position;
            } else {
                // No position set - sort by created_at timestamp
                $sortKey = $note->created_at->timestamp;
            }
            
            return [
                'type' => 'note',
                'id' => $note->id,
                'sort_key' => $sortKey,
                'position' => $note->position,
                'content' => $note->content,
                'created_by_name' => $note->created_by_name ?? 'System',
                'updated_by_name' => $note->updated_by_name,
                'created_at' => $note->created_at->toIso8601String(),
                'created_at_human' => $note->created_at->diffForHumans(),
                'updated_at' => $note->updated_at->toIso8601String(),
                'updated_at_human' => $note->updated_at->diffForHumans(),
                'was_edited' => $note->created_at->ne($note->updated_at),
                'note_type' => $note->type ?? 'user',
            ];
        });

        // Merge emails and notes, sort by sort_key
        $items = $emailsData->concat($notesData)->sortBy('sort_key')->values()->toArray();

        // Format labels
        $labelsData = $thread->labels->map(function ($label) {
            return [
                'id' => $label->id,
                'name' => $label->name,
                'color' => $label->color,
            ];
        })->toArray();

        // Format assigned users
        $assignedUsersData = $thread->assignedUsers->map(function ($user) {
            $name = $user->name ?? $user->email;
            $initials = strtoupper(substr($name, 0, 1));
            if (str_contains($name, ' ')) {
                $parts = explode(' ', $name);
                $initials = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
            }
            return [
                'id' => $user->id,
                'name' => $name,
                'email' => $user->email,
                'avatar_color' => $user->avatar_color ?? (($user->id % 8) + 1),
                'initials' => $initials,
            ];
        })->toArray();

        return [
            'thread' => [
                'id' => $thread->id,
                'subject' => $thread->subject,
                'status' => $thread->status,
                'sender_name' => $thread->sender_name,
                'sender_email' => $thread->sender_email,
                'message_count' => $thread->message_count,
                'has_attachments' => $thread->has_attachments,
                'created_at' => $thread->created_at->toIso8601String(),
                'last_message_at' => $thread->last_message_at?->toIso8601String(),
                'assigned_users' => $assignedUsersData, // Add assigned users to thread object
            ],
            'items' => $items, // Mixed emails and notes, chronologically sorted
            'labels' => $labelsData,
            'assignedUsers' => $assignedUsersData,
            'senders' => $senders,
        ];
    }

    /**
     * Mark all emails in thread as read
     */
    public function markAsRead(int $threadId): void
    {
        $thread = $this->threadRepository->findById($threadId);
        
        if (!$thread) {
            throw new \Exception("Thread not found: {$threadId}");
        }

        // Load all emails in thread
        $thread->load('emails');
        
        // Mark each email as read
        foreach ($thread->emails as $email) {
            if (!$email->is_read) {
                $email->is_read = true;
                $this->emailRepository->save($email);
            }
        }

        $this->logger->info('Thread marked as read', [
            'thread_id' => $threadId,
            'emails_updated' => $thread->emails->count()
        ]);

        // Dispatch webhook event
        if ($this->webhookService) {
            $this->webhookService->dispatch('thread.read', [
                'thread_id' => $threadId,
                'email_count' => $thread->emails->count()
            ]);
        }
    }

    /**
     * Mark all emails in thread as unread
     */
    public function markAsUnread(int $threadId): void
    {
        $thread = $this->threadRepository->findById($threadId);
        
        if (!$thread) {
            throw new \Exception("Thread not found: {$threadId}");
        }

        // Load all emails in thread
        $thread->load('emails');
        
        // Mark each email as unread
        foreach ($thread->emails as $email) {
            if ($email->is_read) {
                $email->is_read = false;
                $this->emailRepository->save($email);
            }
        }

        $this->logger->info('Thread marked as unread', [
            'thread_id' => $threadId,
            'emails_updated' => $thread->emails->count()
        ]);

        // Dispatch webhook event
        if ($this->webhookService) {
            $this->webhookService->dispatch('thread.unread', [
                'thread_id' => $threadId,
                'email_count' => $thread->emails->count()
            ]);
        }
    }

    /**
     * Create system note (helper method)
     */
    private function createSystemNote(int $threadId, string $content): void
    {
        $note = new InternalNote();
        $note->thread_id = $threadId;
        $note->user_id = null;
        $note->content = $content;
        $note->type = 'system';

        $this->noteRepository->save($note);
    }
}

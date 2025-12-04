<?php

namespace CiInbox\App\Services;

use CiInbox\App\Models\Thread;
use Psr\Log\LoggerInterface;

/**
 * Thread Status Service
 * 
 * Manages automatic thread status transitions based on workflow rules:
 * - New thread → open
 * - Thread assigned → assigned
 * - Thread unassigned (not closed) → open
 * - Thread closed → closed
 * - New email in thread → assigned (if assigned) / open (if unassigned)
 */
class ThreadStatusService
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Update thread status when assignment changes
     */
    public function updateStatusOnAssignment(Thread $thread): void
    {
        // Don't change status if thread is closed
        if ($thread->status === 'closed') {
            return;
        }

        $assignedUsers = $thread->assignedUsers()->count();

        if ($assignedUsers > 0) {
            $this->setStatus($thread, 'assigned', 'Thread assigned to user(s)');
        } else {
            $this->setStatus($thread, 'open', 'Thread unassigned');
        }
    }

    /**
     * Update thread status when new email arrives
     */
    public function updateStatusOnNewEmail(Thread $thread): void
    {
        // Special handling for archived threads: Reopen automatically
        if ($thread->status === 'archived') {
            $this->reopenArchivedThread($thread);
            return;
        }

        // Don't reopen closed threads automatically (but archived yes!)
        if ($thread->status === 'closed') {
            $this->logger->info("New email in closed thread #{$thread->id}, keeping status");
            return;
        }

        $assignedUsers = $thread->assignedUsers()->count();

        if ($assignedUsers > 0) {
            $this->setStatus($thread, 'assigned', 'New email received (thread assigned)');
        } else {
            $this->setStatus($thread, 'open', 'New email received (thread unassigned)');
        }
    }

    /**
     * Mark thread as closed
     */
    public function closeThread(Thread $thread): void
    {
        $thread->closed_at = new \DateTime();
        $this->setStatus($thread, 'closed', 'Thread marked as closed');
    }

    /**
     * Reopen closed thread
     */
    public function reopenThread(Thread $thread): void
    {
        $thread->closed_at = null; // Reset closed timestamp
        
        $assignedUsers = $thread->assignedUsers()->count();
        
        if ($assignedUsers > 0) {
            $this->setStatus($thread, 'assigned', 'Thread reopened (assigned)');
        } else {
            $this->setStatus($thread, 'open', 'Thread reopened (unassigned)');
        }
    }

    /**
     * Archive a closed thread (auto-archiving via webcron)
     */
    public function archiveThread(Thread $thread): bool
    {
        if ($thread->status !== 'closed') {
            $this->logger->warning('Cannot archive non-closed thread', [
                'thread_id' => $thread->id,
                'status' => $thread->status
            ]);
            return false;
        }
        
        $this->setStatus($thread, 'archived', 'Auto-archived (closed timeout)');
        return true;
    }

    /**
     * Reopen archived thread when new email arrives
     */
    public function reopenArchivedThread(Thread $thread): void
    {
        if ($thread->status !== 'archived') {
            return;
        }
        
        $thread->closed_at = null; // Reset closed timestamp
        
        // Reset to open or assigned (depending on assigned_users)
        $assignedUsers = $thread->assignedUsers()->count();
        $newStatus = $assignedUsers > 0 ? 'assigned' : 'open';
        
        $this->setStatus($thread, $newStatus, 'Archived thread reopened (new email)');
    }

    /**
     * Set thread status with logging
     */
    private function setStatus(Thread $thread, string $status, string $reason): void
    {
        if ($thread->status === $status) {
            return; // No change needed
        }

        $oldStatus = $thread->status;
        $thread->status = $status;
        $thread->save();

        $this->logger->info("Thread #{$thread->id} status: {$oldStatus} → {$status}", [
            'reason' => $reason,
            'thread_id' => $thread->id,
            'subject' => $thread->subject
        ]);
    }

    /**
     * Get status badge configuration
     */
    public static function getStatusConfig(string $status): array
    {
        $configs = [
            'open' => [
                'label' => 'Offen',
                'color' => 'primary', // Blue
                'icon' => 'inbox'
            ],
            'assigned' => [
                'label' => 'In Arbeit',
                'color' => 'warning', // Orange
                'icon' => 'user'
            ],
            'closed' => [
                'label' => 'Erledigt',
                'color' => 'success', // Green
                'icon' => 'check'
            ],
            'pending' => [ // Legacy fallback
                'label' => 'Ausstehend',
                'color' => 'neutral',
                'icon' => 'clock'
            ],
            'archived' => [
                'label' => 'Archiviert',
                'color' => 'neutral', // Gray
                'icon' => 'archive'
            ]
        ];

        return $configs[$status] ?? [
            'label' => ucfirst($status),
            'color' => 'neutral',
            'icon' => 'circle'
        ];
    }
}

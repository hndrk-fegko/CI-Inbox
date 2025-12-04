<?php

namespace CiInbox\App\Observers;

use CiInbox\App\Models\ThreadAssignment;
use CiInbox\App\Models\Thread;
use CiInbox\App\Services\ThreadStatusService;
use CiInbox\Core\Container;

/**
 * Thread Assignment Observer
 * 
 * Listens to ThreadAssignment events and updates thread status.
 */
class ThreadAssignmentObserver
{
    private ThreadStatusService $statusService;

    public function __construct()
    {
        $this->statusService = Container::getInstance()->get(ThreadStatusService::class);
    }

    /**
     * Handle the ThreadAssignment "created" event (assigned).
     */
    public function created(ThreadAssignment $assignment): void
    {
        $thread = Thread::find($assignment->thread_id);
        
        if ($thread) {
            $this->statusService->updateStatusOnAssignment($thread);
        }
    }

    /**
     * Handle the ThreadAssignment "deleted" event (unassigned).
     */
    public function deleted(ThreadAssignment $assignment): void
    {
        $thread = Thread::find($assignment->thread_id);
        
        if ($thread) {
            $this->statusService->updateStatusOnAssignment($thread);
        }
    }
}

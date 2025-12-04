<?php

namespace CiInbox\App\Observers;

use CiInbox\App\Models\Email;
use CiInbox\App\Models\Thread;
use CiInbox\App\Services\ThreadStatusService;
use CiInbox\Core\Container;

/**
 * Email Observer
 * 
 * Listens to Email model events and triggers status updates.
 */
class EmailObserver
{
    private ThreadStatusService $statusService;

    public function __construct()
    {
        $this->statusService = Container::getInstance()->get(ThreadStatusService::class);
    }

    /**
     * Handle the Email "created" event.
     */
    public function created(Email $email): void
    {
        // Update thread status when new email arrives
        $thread = Thread::find($email->thread_id);
        
        if ($thread) {
            $this->statusService->updateStatusOnNewEmail($thread);
        }
    }
}

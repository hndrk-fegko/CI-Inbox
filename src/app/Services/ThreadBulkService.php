<?php

declare(strict_types=1);

namespace CiInbox\App\Services;

use CiInbox\App\Repositories\ThreadRepositoryInterface;
use CiInbox\App\Models\Thread;
use CiInbox\Modules\Logger\LoggerInterface;

/**
 * Thread Bulk Operations Service
 * 
 * Handles bulk operations on multiple threads simultaneously.
 * 
 * Architecture Layer: Service Layer (Business Logic)
 * Dependencies: ThreadRepository (Data Access)
 */
class ThreadBulkService
{
    public function __construct(
        private ThreadRepositoryInterface $threadRepository,
        private LoggerInterface $logger
    ) {}
    
    /**
     * Update multiple threads at once
     * 
     * @param array $threadIds Array of thread IDs
     * @param array $updates Updates to apply (status, assigned_to, etc.)
     * @return array ['updated' => int, 'failed' => int, 'errors' => array]
     */
    public function bulkUpdate(array $threadIds, array $updates): array
    {
        $this->logger->info('Bulk update requested', [
            'thread_ids' => $threadIds,
            'updates' => $updates,
            'count' => count($threadIds)
        ]);
        
        $updated = 0;
        $failed = 0;
        $errors = [];
        
        foreach ($threadIds as $threadId) {
            try {
                $thread = $this->threadRepository->findById((int)$threadId);
                
                if (!$thread) {
                    $failed++;
                    $errors[] = [
                        'thread_id' => $threadId,
                        'error' => 'Thread not found'
                    ];
                    continue;
                }
                
                // Apply updates
                foreach ($updates as $key => $value) {
                    if (property_exists($thread, $key)) {
                        $thread->$key = $value;
                    }
                }
                
                $thread->save();
                $updated++;
                
                $this->logger->debug('Thread updated in bulk operation', [
                    'thread_id' => $threadId
                ]);
                
            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'thread_id' => $threadId,
                    'error' => $e->getMessage()
                ];
                
                $this->logger->error('Failed to update thread in bulk operation', [
                    'thread_id' => $threadId,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $this->logger->info('Bulk update completed', [
            'updated' => $updated,
            'failed' => $failed,
            'total' => count($threadIds)
        ]);
        
        return [
            'updated' => $updated,
            'failed' => $failed,
            'total' => count($threadIds),
            'errors' => $errors
        ];
    }
    
    /**
     * Delete multiple threads at once
     * 
     * @param array $threadIds Array of thread IDs
     * @return array ['deleted' => int, 'failed' => int, 'errors' => array]
     */
    public function bulkDelete(array $threadIds): array
    {
        $this->logger->info('Bulk delete requested', [
            'thread_ids' => $threadIds,
            'count' => count($threadIds)
        ]);
        
        $deleted = 0;
        $failed = 0;
        $errors = [];
        
        foreach ($threadIds as $threadId) {
            try {
                $thread = $this->threadRepository->findById((int)$threadId);
                
                if (!$thread) {
                    $failed++;
                    $errors[] = [
                        'thread_id' => $threadId,
                        'error' => 'Thread not found'
                    ];
                    continue;
                }
                
                // Delete using ID, not object
                $this->threadRepository->delete((int)$threadId);
                $deleted++;
                
                $this->logger->debug('Thread deleted in bulk operation', [
                    'thread_id' => $threadId
                ]);
                
            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'thread_id' => $threadId,
                    'error' => $e->getMessage()
                ];
                
                $this->logger->error('Failed to delete thread in bulk operation', [
                    'thread_id' => $threadId,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $this->logger->info('Bulk delete completed', [
            'deleted' => $deleted,
            'failed' => $failed,
            'total' => count($threadIds)
        ]);
        
        return [
            'deleted' => $deleted,
            'failed' => $failed,
            'total' => count($threadIds),
            'errors' => $errors
        ];
    }
    
    /**
     * Assign multiple threads to a user
     * 
     * @param array $threadIds Array of thread IDs
     * @param int|null $userId User ID to assign (null = unassign)
     * @return array ['assigned' => int, 'failed' => int, 'errors' => array]
     */
    public function bulkAssign(array $threadIds, ?int $userId): array
    {
        $this->logger->info('Bulk assign requested', [
            'thread_ids' => $threadIds,
            'user_id' => $userId,
            'count' => count($threadIds)
        ]);
        
        return $this->bulkUpdate($threadIds, ['assigned_to' => $userId]);
    }
    
    /**
     * Mark multiple threads with a specific status
     * 
     * @param array $threadIds Array of thread IDs
     * @param string $status Status to set (open, closed, archived)
     * @return array ['updated' => int, 'failed' => int, 'errors' => array]
     */
    public function bulkSetStatus(array $threadIds, string $status): array
    {
        $this->logger->info('Bulk set status requested', [
            'thread_ids' => $threadIds,
            'status' => $status,
            'count' => count($threadIds)
        ]);
        
        // Validate status
        $validStatuses = ['open', 'closed', 'archived'];
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Invalid status: {$status}. Must be one of: " . implode(', ', $validStatuses));
        }
        
        return $this->bulkUpdate($threadIds, ['status' => $status]);
    }
    
    /**
     * Add label to multiple threads
     * 
     * @param array $threadIds Array of thread IDs
     * @param int $labelId Label ID to add
     * @return array ['added' => int, 'failed' => int, 'errors' => array]
     */
    public function bulkAddLabel(array $threadIds, int $labelId): array
    {
        $this->logger->info('Bulk add label requested', [
            'thread_ids' => $threadIds,
            'label_id' => $labelId,
            'count' => count($threadIds)
        ]);
        
        $added = 0;
        $failed = 0;
        $errors = [];
        
        foreach ($threadIds as $threadId) {
            try {
                $thread = $this->threadRepository->findById((int)$threadId);
                
                if (!$thread) {
                    $failed++;
                    $errors[] = [
                        'thread_id' => $threadId,
                        'error' => 'Thread not found'
                    ];
                    continue;
                }
                
                // Check if label already attached
                if (!$thread->labels->contains($labelId)) {
                    $thread->labels()->attach($labelId);
                    $added++;
                    
                    $this->logger->debug('Label added to thread in bulk operation', [
                        'thread_id' => $threadId,
                        'label_id' => $labelId
                    ]);
                }
                
            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'thread_id' => $threadId,
                    'error' => $e->getMessage()
                ];
                
                $this->logger->error('Failed to add label in bulk operation', [
                    'thread_id' => $threadId,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $this->logger->info('Bulk add label completed', [
            'added' => $added,
            'failed' => $failed,
            'total' => count($threadIds)
        ]);
        
        return [
            'added' => $added,
            'failed' => $failed,
            'total' => count($threadIds),
            'errors' => $errors
        ];
    }
    
    /**
     * Remove label from multiple threads
     * 
     * @param array $threadIds Array of thread IDs
     * @param int $labelId Label ID to remove
     * @return array ['removed' => int, 'failed' => int, 'errors' => array]
     */
    public function bulkRemoveLabel(array $threadIds, int $labelId): array
    {
        $this->logger->info('Bulk remove label requested', [
            'thread_ids' => $threadIds,
            'label_id' => $labelId,
            'count' => count($threadIds)
        ]);
        
        $removed = 0;
        $failed = 0;
        $errors = [];
        
        foreach ($threadIds as $threadId) {
            try {
                $thread = $this->threadRepository->findById((int)$threadId);
                
                if (!$thread) {
                    $failed++;
                    $errors[] = [
                        'thread_id' => $threadId,
                        'error' => 'Thread not found'
                    ];
                    continue;
                }
                
                // Check if label attached
                if ($thread->labels->contains($labelId)) {
                    $thread->labels()->detach($labelId);
                    $removed++;
                    
                    $this->logger->debug('Label removed from thread in bulk operation', [
                        'thread_id' => $threadId,
                        'label_id' => $labelId
                    ]);
                }
                
            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'thread_id' => $threadId,
                    'error' => $e->getMessage()
                ];
                
                $this->logger->error('Failed to remove label in bulk operation', [
                    'thread_id' => $threadId,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $this->logger->info('Bulk remove label completed', [
            'removed' => $removed,
            'failed' => $failed,
            'total' => count($threadIds)
        ]);
        
        return [
            'removed' => $removed,
            'failed' => $failed,
            'total' => count($threadIds),
            'errors' => $errors
        ];
    }
}

<?php

namespace CiInbox\App\Repositories;

use CiInbox\App\Models\Thread;
use CiInbox\Modules\Logger\LoggerService;
use Illuminate\Support\Collection;

/**
 * Thread Repository
 * 
 * Database operations for Thread model.
 * Implements ThreadRepositoryInterface for unified API access.
 */
class ThreadRepository implements ThreadRepositoryInterface
{
    public function __construct(
        private LoggerService $logger
    ) {}
    
    /**
     * Find thread by ID
     * 
     * @param int $id
     * @return Thread|null
     */
    public function find(int $id): ?Thread
    {
        try {
            $thread = Thread::with(['emails', 'assignedUsers'])->find($id);
            
            if ($thread) {
                $this->logger->debug('ThreadRepository: Thread found', ['thread_id' => $id]);
            } else {
                $this->logger->debug('ThreadRepository: Thread not found', ['thread_id' => $id]);
            }
            
            return $thread;
        } catch (\Exception $e) {
            $this->logger->error('ThreadRepository: Failed to find thread', [
                'thread_id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Find thread by ID (Interface method)
     * Alias for find()
     */
    public function findById(int $id): ?Thread
    {
        return $this->find($id);
    }
    
    /**
     * Find thread by thread UID
     */
    public function findByThreadUid(string $threadUid): ?Thread
    {
        try {
            $thread = Thread::where('thread_uid', $threadUid)->first();
            
            if ($thread) {
                $this->logger->debug('ThreadRepository: Thread found by UID', ['thread_uid' => $threadUid]);
            }
            
            return $thread;
        } catch (\Exception $e) {
            $this->logger->error('ThreadRepository: Failed to find thread by UID', [
                'thread_uid' => $threadUid,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Find thread by subject and time window
     * 
     * @param string $subject
     * @param \DateTime $date
     * @param int $windowDays
     * @return int|null Thread ID
     */
    public function findBySubjectAndTimeWindow(string $subject, \DateTime $date, int $windowDays = 30): ?int
    {
        try {
            $normalized = $this->normalizeSubject($subject);
            $timeWindow = (clone $date)->modify("-{$windowDays} days");
            
            $thread = Thread::where('subject', 'LIKE', "%{$normalized}%")
                ->where('last_message_at', '>=', $timeWindow)
                ->first();
            
            if ($thread) {
                $this->logger->debug('ThreadRepository: Found thread by subject', [
                    'thread_id' => $thread->id,
                    'subject' => $subject
                ]);
            }
            
            return $thread?->id;
        } catch (\Exception $e) {
            $this->logger->error('ThreadRepository: Failed to find thread by subject', [
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Create new thread
     * 
     * @param array $data
     * @return Thread
     */
    public function create(array $data): Thread
    {
        try {
            $thread = Thread::create($data);
            
            $this->logger->info('ThreadRepository: Thread created', [
                'thread_id' => $thread->id,
                'subject' => $thread->subject
            ]);
            
            return $thread;
        } catch (\Exception $e) {
            $this->logger->error('ThreadRepository: Failed to create thread', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Update thread
     * 
     * @param Thread $thread
     * @param array $data
     * @return Thread
     */
    public function update(Thread $thread, array $data): Thread
    {
        try {
            $thread->update($data);
            
            $this->logger->info('ThreadRepository: Thread updated', [
                'thread_id' => $thread->id,
                'changes' => array_keys($data)
            ]);
            
            return $thread;
        } catch (\Exception $e) {
            $this->logger->error('ThreadRepository: Failed to update thread', [
                'thread_id' => $thread->id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Find all threads (Interface method)
     * 
     * @param array $filters
     * @return Collection
     */
    public function findAll(array $filters = []): Collection
    {
        $limit = $filters['limit'] ?? 100;
        return $this->getAll($filters, $limit);
    }
    
    /**
     * Get all threads
     * 
     * @param array $filters
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll(array $filters = [], int $limit = 100)
    {
        try {
            // Load with relations for full detail view
            $query = Thread::with(['emails', 'labels', 'assignedUsers']);
            
            // Apply filters
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            
            if (isset($filters['assigned_user_id'])) {
                $query->whereHas('assignments', function($q) use ($filters) {
                    $q->where('user_id', $filters['assigned_user_id']);
                });
            }
            
            if (isset($filters['label'])) {
                $query->whereHas('labels', function($q) use ($filters) {
                    $q->where('labels.id', $filters['label']);
                });
            }
            
            // Search in subject, sender_name, sender_email
            if (isset($filters['search']) && !empty($filters['search'])) {
                $search = '%' . $filters['search'] . '%';
                $this->logger->debug('ThreadRepository: Applying search filter', [
                    'search_term' => $filters['search'],
                    'like_pattern' => $search
                ]);
                $query->where(function($q) use ($search) {
                    $q->where('subject', 'LIKE', $search)
                      ->orWhere('sender_name', 'LIKE', $search)
                      ->orWhere('sender_email', 'LIKE', $search);
                });
                
                // Log the SQL query
                $sql = $query->toSql();
                $bindings = $query->getBindings();
                $this->logger->debug('ThreadRepository: SQL Query', [
                    'sql' => $sql,
                    'bindings' => $bindings
                ]);
            }
            
            $threads = $query->orderBy('last_message_at', 'desc')
                ->limit($limit)
                ->get();
            
            $this->logger->debug('ThreadRepository: Fetched threads', [
                'count' => $threads->count(),
                'filters' => $filters,
                'limit' => $limit
            ]);
            
            return $threads;
        } catch (\Exception $e) {
            $this->logger->error('ThreadRepository: Failed to fetch threads', [
                'filters' => $filters,
                'limit' => $limit,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Normalize subject for searching
     * 
     * @param string $subject
     * @return string
     */
    private function normalizeSubject(string $subject): string
    {
        // Remove Re:, Fwd:, etc.
        $subject = preg_replace('/^(Re|RE|Fwd|FWD|AW|Aw|WG|Wg):\s*/i', '', $subject);
        $subject = preg_replace('/\s+/', ' ', $subject);
        $subject = trim($subject);
        
        return $subject;
    }
    
    /**
     * Save thread (Interface method)
     */
    public function save(Thread $thread): bool
    {
        try {
            $result = $thread->save();
            $this->logger->debug('ThreadRepository: Thread saved', ['thread_id' => $thread->id]);
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('ThreadRepository: Failed to save thread', [
                'thread_id' => $thread->id ?? 'new',
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Delete thread (Interface method)
     */
    public function delete(int $id): bool
    {
        try {
            $thread = $this->find($id);
            
            if (!$thread) {
                $this->logger->warning('ThreadRepository: Thread not found for deletion', ['thread_id' => $id]);
                return false;
            }
            
            $result = $thread->delete();
            $this->logger->info('ThreadRepository: Thread deleted', ['thread_id' => $id]);
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('ThreadRepository: Failed to delete thread', [
                'thread_id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Count threads by status (Interface method)
     */
    public function countByStatus(string $status): int
    {
        try {
            $count = Thread::where('status', $status)->count();
            $this->logger->debug('ThreadRepository: Counted threads by status', [
                'status' => $status,
                'count' => $count
            ]);
            return $count;
        } catch (\Exception $e) {
            $this->logger->error('ThreadRepository: Failed to count threads', [
                'status' => $status,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
    
    /**
     * Find closed threads ready for archiving
     * 
     * @param \DateTime $cutoffTime Threads closed before this time will be archived
     * @return array Array of Thread models
     */
    public function findClosedForArchiving(\DateTime $cutoffTime): array
    {
        try {
            $threads = Thread::where('status', 'closed')
                ->where('closed_at', '<=', $cutoffTime->format('Y-m-d H:i:s'))
                ->whereNotNull('closed_at')
                ->get()
                ->all();
            
            $this->logger->debug('ThreadRepository: Found threads for archiving', [
                'count' => count($threads),
                'cutoff_time' => $cutoffTime->format('Y-m-d H:i:s')
            ]);
            
            return $threads;
        } catch (\Exception $e) {
            $this->logger->error('ThreadRepository: Failed to find threads for archiving', [
                'error' => $e->getMessage(),
                'cutoff_time' => $cutoffTime->format('Y-m-d H:i:s')
            ]);
            return [];
        }
    }
}

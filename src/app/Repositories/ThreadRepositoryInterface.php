<?php

declare(strict_types=1);

namespace CiInbox\App\Repositories;

use CiInbox\App\Models\Thread;
use Illuminate\Support\Collection;

/**
 * Thread Repository Interface
 * 
 * Defines operations for thread data access
 */
interface ThreadRepositoryInterface
{
    /**
     * Find thread by ID
     */
    public function findById(int $id): ?Thread;

    /**
     * Find thread by thread UID
     */
    public function findByThreadUid(string $threadUid): ?Thread;

    /**
     * Find all threads with optional filters
     */
    public function findAll(array $filters = []): Collection;

    /**
     * Save thread
     */
    public function save(Thread $thread): bool;

    /**
     * Delete thread
     */
    public function delete(int $id): bool;

    /**
     * Count threads by status
     */
    public function countByStatus(string $status): int;
}

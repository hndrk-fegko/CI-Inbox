<?php

declare(strict_types=1);

namespace CiInbox\App\Repositories;

use CiInbox\App\Models\InternalNote;
use Illuminate\Support\Collection;

/**
 * Note Repository Interface
 * 
 * Defines operations for internal notes data access
 */
interface NoteRepositoryInterface
{
    /**
     * Find note by ID
     */
    public function findById(int $id): ?InternalNote;

    /**
     * Find all notes for a thread
     */
    public function findByThreadId(int $threadId): Collection;

    /**
     * Save note
     */
    public function save(InternalNote $note): bool;

    /**
     * Delete note
     */
    public function delete(int $id): bool;
}

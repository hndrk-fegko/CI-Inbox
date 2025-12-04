<?php

declare(strict_types=1);

namespace CiInbox\App\Repositories;

use CiInbox\App\Models\Email;
use Illuminate\Support\Collection;

/**
 * Email Repository Interface
 * 
 * Defines operations for email data access
 */
interface EmailRepositoryInterface
{
    /**
     * Find email by ID
     */
    public function findById(int $id): ?Email;

    /**
     * Find email by Message-ID
     */
    public function findByMessageId(string $messageId): ?Email;

    /**
     * Find multiple emails by IDs
     */
    public function findByIds(array $ids): Collection;

    /**
     * Find all emails in a thread
     */
    public function findByThreadId(int $threadId): Collection;

    /**
     * Save email (for existing Email objects)
     */
    public function save(Email $email): bool;

    /**
     * Create new email from array data
     */
    public function create(array $data): Email;

    /**
     * Update email with array data
     */
    public function update(Email $email, array $data): Email;

    /**
     * Check if email exists by Message-ID
     */
    public function existsByMessageId(string $messageId): bool;

    /**
     * Get unprocessed emails (without thread_id)
     */
    public function getUnprocessed(int $limit = 100): Collection;
}

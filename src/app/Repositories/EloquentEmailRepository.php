<?php

declare(strict_types=1);

namespace CiInbox\App\Repositories;

use CiInbox\App\Models\Email;
use CiInbox\Modules\Logger\LoggerInterface;
use Illuminate\Support\Collection;

/**
 * Eloquent Email Repository
 * 
 * Implements EmailRepositoryInterface with Eloquent ORM.
 * This is the single, canonical implementation for email data access.
 */
class EloquentEmailRepository implements EmailRepositoryInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function findById(int $id): ?Email
    {
        return Email::find($id);
    }

    public function findByMessageId(string $messageId): ?Email
    {
        return Email::where('message_id', $messageId)->first();
    }

    public function findByIds(array $ids): Collection
    {
        return Email::whereIn('id', $ids)->get();
    }

    public function findByThreadId(int $threadId): Collection
    {
        return Email::where('thread_id', $threadId)
            ->orderBy('sent_at', 'asc')
            ->get();
    }

    public function save(Email $email): bool
    {
        try {
            $result = $email->save();
            
            $this->logger->debug('Email saved', [
                'email_id' => $email->id,
                'message_id' => $email->message_id ?? null,
                'thread_id' => $email->thread_id ?? null
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to save email', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function create(array $data): Email
    {
        try {
            $email = Email::create($data);
            
            $this->logger->info('[SUCCESS] Email created', [
                'email_id' => $email->id,
                'message_id' => $email->message_id,
                'thread_id' => $email->thread_id
            ]);
            
            return $email;
        } catch (\Exception $e) {
            $this->logger->error('Failed to create email', [
                'message_id' => $data['message_id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function update(Email $email, array $data): Email
    {
        try {
            $email->update($data);
            
            $this->logger->debug('Email updated', [
                'email_id' => $email->id
            ]);
            
            return $email;
        } catch (\Exception $e) {
            $this->logger->error('Failed to update email', [
                'email_id' => $email->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function existsByMessageId(string $messageId): bool
    {
        return Email::where('message_id', $messageId)->exists();
    }

    public function getUnprocessed(int $limit = 100): Collection
    {
        return Email::whereNull('thread_id')
            ->orderBy('sent_at', 'asc')
            ->limit($limit)
            ->get();
    }
}

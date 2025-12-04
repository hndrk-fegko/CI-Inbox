<?php

declare(strict_types=1);

namespace CiInbox\App\Repositories;

use CiInbox\App\Models\InternalNote;
use CiInbox\Modules\Logger\LoggerInterface;
use Illuminate\Support\Collection;

/**
 * Eloquent Note Repository
 * 
 * Implements NoteRepositoryInterface with Eloquent ORM
 */
class EloquentNoteRepository implements NoteRepositoryInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function findById(int $id): ?InternalNote
    {
        return InternalNote::find($id);
    }

    public function findByThreadId(int $threadId): Collection
    {
        return InternalNote::where('thread_id', $threadId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function save(InternalNote $note): bool
    {
        try {
            $isNew = !$note->exists;
            $result = $note->save();
            
            $this->logger->debug($isNew ? 'Note created' : 'Note updated', [
                'note_id' => $note->id,
                'thread_id' => $note->thread_id,
                'user_id' => $note->user_id ?? null
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to save note', [
                'thread_id' => $note->thread_id ?? null,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function delete(int $id): bool
    {
        $note = $this->findById($id);
        
        if (!$note) {
            $this->logger->warning('Note delete failed - not found', ['note_id' => $id]);
            return false;
        }

        try {
            $threadId = $note->thread_id;
            $result = $note->delete();
            
            $this->logger->info('[SUCCESS] Note deleted', [
                'note_id' => $id,
                'thread_id' => $threadId
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete note', [
                'note_id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}

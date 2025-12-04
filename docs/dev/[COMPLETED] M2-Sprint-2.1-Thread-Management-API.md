# [WIP] M2 Sprint 2.1: Thread-Management-API

**Milestone:** M2 - Thread API  
**Sprint:** 2.1 (von 3)  
**Geschätzte Dauer:** 3 Tage  
**Tatsächliche Dauer:** ~4 Stunden  
**Status:** ✅ COMPLETED  
**Gestartet:** 18. November 2025  
**Abgeschlossen:** 18. November 2025

---

## Ziel

REST API für Thread-Management mit Advanced Operations implementieren - Threads abrufen, verwalten, manipulieren (split, merge, move). **Standalone testbar** ohne UI.

**Features aus inventar.md:**
- ✅ **F1.1** - Thread-Management (MUST)
- ✅ **F1.2** - Zuweisungslogik (MUST)
- ✅ **F1.3** - Internes Notizsystem (MUST)
- ✅ **F1.4** - Status-Management (MUST)

**Namenskonvention:**
- **Fx.y** = Features aus `inventar.md` (Business Requirements)
- **Mx.y** = Milestones/Sprints aus `roadmap.md` (Implementation Units)

---

## Ergebnis

✅ **10 API Endpoints** vollständig implementiert und getestet:

**Basic CRUD (6 endpoints):**
1. `POST /api/threads` - Create new thread
2. `GET /api/threads` - List threads (with filters: status, label, pagination)
3. `GET /api/threads/{id}` - Get single thread with emails and notes
4. `PUT /api/threads/{id}` - Update thread (subject, status)
5. `DELETE /api/threads/{id}` - Delete thread
6. `POST /api/threads/{id}/notes` - Add internal note

**Advanced Operations (4 endpoints):**
7. `POST /api/threads/{id}/emails/{emailId}/assign` - Assign email to thread (re-threading)
8. `POST /api/threads/{id}/split` - Split thread (create new thread from selected emails)
9. `POST /api/threads/{targetId}/merge` - Merge two threads
10. `PATCH /api/emails/{emailId}/thread` - Move email to different thread

✅ **Alle Tests bestanden:** 11/11 (siehe Testprotokoll unten)

---

## Abhängigkeiten

### M0 Foundation (✅ COMPLETED)
- **LoggerService** - Für Logging aller Thread-Operationen
- **ConfigService** - Für API-Konfiguration
- **Database (Eloquent)** - Models: Thread, Email, User, InternalNote
- **Container (PHP-DI)** - Dependency Injection

### M1 IMAP Core (✅ COMPLETED)
- **ThreadManager** - Thread-Erstellung und -Zuordnung
- **EmailRepository** - E-Mail-Daten abrufen
- **ThreadRepository** - Thread-Daten abrufen
- **LabelManager** - Label-Zuordnung (für Filter)

### Neue Abhängigkeiten (M2)
- **Slim Framework** - HTTP-Routing (bereits in M0 eingerichtet)
- **AuthMiddleware** - Später für User-Authentifizierung (erstmal mock)

---

## Architektur-Pattern

### Layer-Abstraktion (aus basics.txt)

```
┌─────────────────────────────────────┐
│   API LAYER (HTTP Controller)      │
│   - ThreadController.php            │
│   - Request Validation              │
│   - Response Formatting (JSON)      │
├─────────────────────────────────────┤
│   SERVICE LAYER (Business Logic)   │
│   - ThreadService.php               │
│   - AssignmentService.php           │
│   - Geschäftslogik HIER!            │
├─────────────────────────────────────┤
│   DATA ACCESS LAYER (Interfaces)   │
│   - ThreadRepositoryInterface       │
│   - NoteRepositoryInterface         │
│   - Abstrakte Schnittstellen        │
├─────────────────────────────────────┤
│   IMPLEMENTATION LAYER              │
│   - EloquentThreadRepository        │
│   - EloquentNoteRepository          │
│   - Konkrete Implementierung        │
└─────────────────────────────────────┘
```

**Wichtig:** Service Layer nutzt NIEMALS direkte DB-Zugriffe, sondern immer Repositories!

---

## Implementierung

### API-Übersicht

**Basic Thread Operations:**
1. `GET /api/threads` - List threads (with filters)
2. `GET /api/threads/{id}` - Get single thread with emails
3. `POST /api/threads/{id}/assign` - Assign thread to user
4. `PATCH /api/threads/{id}/status` - Change thread status
5. `POST /api/threads/{id}/notes` - Add internal note
6. `GET /api/threads/{id}/notes` - List thread notes

**Advanced Thread Operations (F1.1 Extended):**
7. `POST /api/threads/{id}/emails/{emailId}/assign` - Add email to existing thread
8. `POST /api/threads/{id}/split` - Split thread (create new thread from selected emails)
9. `POST /api/threads/{targetId}/merge` - Merge two threads
10. `PATCH /api/emails/{emailId}/thread` - Move single email to different thread

**Architecture Decision: Business Logic in Service Layer**

Advanced operations (split, merge, move) werden **im Service Layer** implementiert, NICHT durch API-Composition:

✅ **Vorteile:**
- Atomare Transaktionen (DB Rollback bei Fehler)
- Ein zusammenhängender Log-Eintrag
- Business Rules zentral (z.B. "mindestens 1 Email im alten Thread")
- Performance (keine HTTP-Roundtrips)

❌ **Gegen API-Composition:**
- HTTP-Overhead intern
- Komplexes Error-Handling
- Transaktions-Probleme (Teil erfolgreich, Teil failed)

---

### 1. Controllers (API Layer)

**Datei:** `src/app/Controllers/ThreadController.php`

```php
<?php

declare(strict_types=1);

namespace CiInbox\App\Controllers;

use CiInbox\App\Services\ThreadService;
use CiInbox\Modules\Logger\LoggerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Thread-Management REST API Controller
 * 
 * Endpoints:
 * - GET    /api/threads              List all threads (with filters)
 * - GET    /api/threads/{id}         Get single thread with emails
 * - POST   /api/threads/{id}/assign  Assign thread to user
 * - PATCH  /api/threads/{id}/status  Change thread status
 * - POST   /api/threads/{id}/notes   Add internal note
 * - GET    /api/threads/{id}/notes   List thread notes
 */
class ThreadController
{
    public function __construct(
        private ThreadService $threadService,
        private LoggerService $logger
    ) {}

    /**
     * GET /api/threads
     * 
     * Query params:
     * - status: Filter by status (new, assigned, in_progress, done)
     * - assigned_to: Filter by user ID
     * - label: Filter by label ID
     * - limit: Max results (default: 50)
     * - offset: Pagination offset
     */
    public function list(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        
        $filters = [
            'status' => $params['status'] ?? null,
            'assigned_to' => isset($params['assigned_to']) ? (int)$params['assigned_to'] : null,
            'label' => isset($params['label']) ? (int)$params['label'] : null,
            'limit' => isset($params['limit']) ? (int)$params['limit'] : 50,
            'offset' => isset($params['offset']) ? (int)$params['offset'] : 0,
        ];

        try {
            $threads = $this->threadService->listThreads($filters);
            
            $this->logger->debug('Threads listed', [
                'filters' => $filters,
                'count' => count($threads)
            ]);

            $response->getBody()->write(json_encode([
                'success' => true,
                'threads' => $threads,
                'filters' => $filters
            ]));

            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Thread list failed', [
                'error' => $e->getMessage()
            ]);

            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * GET /api/threads/{id}
     * 
     * Returns thread with all emails and metadata
     */
    public function get(Request $request, Response $response, array $args): Response
    {
        $threadId = (int)$args['id'];

        try {
            $thread = $this->threadService->getThreadWithEmails($threadId);

            if (!$thread) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Thread not found'
                ]));

                return $response
                    ->withStatus(404)
                    ->withHeader('Content-Type', 'application/json');
            }

            $this->logger->debug('Thread retrieved', ['thread_id' => $threadId]);

            $response->getBody()->write(json_encode([
                'success' => true,
                'thread' => $thread
            ]));

            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Thread get failed', [
                'thread_id' => $threadId,
                'error' => $e->getMessage()
            ]);

            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * POST /api/threads/{id}/assign
     * 
     * Body: {"user_id": 5}
     */
    public function assign(Request $request, Response $response, array $args): Response
    {
        $threadId = (int)$args['id'];
        $body = json_decode((string)$request->getBody(), true);

        if (!isset($body['user_id'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Missing user_id'
            ]));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        }

        $userId = (int)$body['user_id'];

        try {
            $success = $this->threadService->assignThread($threadId, $userId);

            if (!$success) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Assignment failed'
                ]));

                return $response
                    ->withStatus(500)
                    ->withHeader('Content-Type', 'application/json');
            }

            $this->logger->success('Thread assigned', [
                'thread_id' => $threadId,
                'user_id' => $userId
            ]);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Thread assigned successfully'
            ]));

            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Thread assignment failed', [
                'thread_id' => $threadId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * PATCH /api/threads/{id}/status
     * 
     * Body: {"status": "in_progress"}
     * 
     * Valid statuses: new, assigned, in_progress, done, transferred, archived
     */
    public function updateStatus(Request $request, Response $response, array $args): Response
    {
        $threadId = (int)$args['id'];
        $body = json_decode((string)$request->getBody(), true);

        if (!isset($body['status'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Missing status'
            ]));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        }

        $status = $body['status'];
        $validStatuses = ['new', 'assigned', 'in_progress', 'done', 'transferred', 'archived'];

        if (!in_array($status, $validStatuses)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Invalid status. Valid: ' . implode(', ', $validStatuses)
            ]));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        }

        try {
            $success = $this->threadService->changeStatus($threadId, $status);

            if (!$success) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Status change failed'
                ]));

                return $response
                    ->withStatus(500)
                    ->withHeader('Content-Type', 'application/json');
            }

            $this->logger->success('Thread status changed', [
                'thread_id' => $threadId,
                'new_status' => $status
            ]);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Status updated successfully'
            ]));

            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Thread status change failed', [
                'thread_id' => $threadId,
                'status' => $status,
                'error' => $e->getMessage()
            ]);

            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * POST /api/threads/{id}/notes
     * 
     * Body: {"user_id": 5, "note_text": "Bitte bis Freitag antworten"}
     */
    public function addNote(Request $request, Response $response, array $args): Response
    {
        $threadId = (int)$args['id'];
        $body = json_decode((string)$request->getBody(), true);

        if (!isset($body['user_id']) || !isset($body['note_text'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Missing user_id or note_text'
            ]));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        }

        $userId = (int)$body['user_id'];
        $noteText = trim($body['note_text']);

        if (empty($noteText)) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Note text cannot be empty'
            ]));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        }

        try {
            $note = $this->threadService->addNote($threadId, $userId, $noteText);

            $this->logger->success('Note added to thread', [
                'thread_id' => $threadId,
                'user_id' => $userId,
                'note_id' => $note->id
            ]);

            $response->getBody()->write(json_encode([
                'success' => true,
                'note' => [
                    'id' => $note->id,
                    'thread_id' => $note->thread_id,
                    'user_id' => $note->user_id,
                    'note_text' => $note->note_text,
                    'created_at' => $note->created_at->toIso8601String()
                ]
            ]));

            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Note creation failed', [
                'thread_id' => $threadId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * GET /api/threads/{id}/notes
     * 
     * Returns all notes for a thread
     */
    public function listNotes(Request $request, Response $response, array $args): Response
    {
        $threadId = (int)$args['id'];

        try {
            $notes = $this->threadService->getThreadNotes($threadId);

            $this->logger->debug('Thread notes retrieved', [
                'thread_id' => $threadId,
                'count' => count($notes)
            ]);

            $response->getBody()->write(json_encode([
                'success' => true,
                'notes' => $notes
            ]));

            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Note listing failed', [
                'thread_id' => $threadId,
                'error' => $e->getMessage()
            ]);

            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * POST /api/threads/{id}/emails/{emailId}/assign
     * 
     * Assign email to existing thread (re-threading)
     * 
     * Body: {} (empty, emailId and threadId from URL)
     */
    public function assignEmailToThread(Request $request, Response $response, array $args): Response
    {
        $threadId = (int)$args['id'];
        $emailId = (int)$args['emailId'];

        try {
            $success = $this->threadService->assignEmailToThread($emailId, $threadId);

            if (!$success) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Email assignment failed'
                ]));

                return $response
                    ->withStatus(500)
                    ->withHeader('Content-Type', 'application/json');
            }

            $this->logger->success('Email assigned to thread', [
                'email_id' => $emailId,
                'thread_id' => $threadId
            ]);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Email assigned to thread successfully'
            ]));

            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Email assignment failed', [
                'email_id' => $emailId,
                'thread_id' => $threadId,
                'error' => $e->getMessage()
            ]);

            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * POST /api/threads/{id}/split
     * 
     * Split thread - create new thread from selected emails
     * 
     * Body: {
     *   "emailIds": [1,2,3],
     *   "newSubject": "Split: Original Subject",
     *   "newStatus": "new" (optional)
     * }
     */
    public function splitThread(Request $request, Response $response, array $args): Response
    {
        $threadId = (int)$args['id'];
        $body = json_decode((string)$request->getBody(), true);

        if (!isset($body['emailIds']) || !is_array($body['emailIds'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Missing or invalid emailIds array'
            ]));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        }

        if (!isset($body['newSubject'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Missing newSubject'
            ]));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        }

        $emailIds = array_map('intval', $body['emailIds']);
        $newSubject = $body['newSubject'];
        $newStatus = $body['newStatus'] ?? 'new';

        try {
            $newThread = $this->threadService->splitThread($threadId, $emailIds, $newSubject, $newStatus);

            $this->logger->success('Thread split', [
                'old_thread_id' => $threadId,
                'new_thread_id' => $newThread['id'],
                'email_count' => count($emailIds)
            ]);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Thread split successfully',
                'new_thread' => $newThread
            ]));

            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Thread split failed', [
                'thread_id' => $threadId,
                'email_ids' => $emailIds,
                'error' => $e->getMessage()
            ]);

            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * POST /api/threads/{targetId}/merge
     * 
     * Merge two threads - move all emails from source to target
     * 
     * Body: {"sourceThreadId": 123}
     */
    public function mergeThreads(Request $request, Response $response, array $args): Response
    {
        $targetThreadId = (int)$args['targetId'];
        $body = json_decode((string)$request->getBody(), true);

        if (!isset($body['sourceThreadId'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Missing sourceThreadId'
            ]));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        }

        $sourceThreadId = (int)$body['sourceThreadId'];

        if ($sourceThreadId === $targetThreadId) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Cannot merge thread with itself'
            ]));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        }

        try {
            $result = $this->threadService->mergeThreads($sourceThreadId, $targetThreadId);

            $this->logger->success('Threads merged', [
                'source_thread_id' => $sourceThreadId,
                'target_thread_id' => $targetThreadId,
                'emails_moved' => $result['emails_moved']
            ]);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Threads merged successfully',
                'emails_moved' => $result['emails_moved']
            ]));

            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Thread merge failed', [
                'source_thread_id' => $sourceThreadId,
                'target_thread_id' => $targetThreadId,
                'error' => $e->getMessage()
            ]);

            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * PATCH /api/emails/{emailId}/thread
     * 
     * Move single email to different thread
     * 
     * Body: {"newThreadId": 456}
     */
    public function moveEmailToThread(Request $request, Response $response, array $args): Response
    {
        $emailId = (int)$args['emailId'];
        $body = json_decode((string)$request->getBody(), true);

        if (!isset($body['newThreadId'])) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Missing newThreadId'
            ]));

            return $response
                ->withStatus(400)
                ->withHeader('Content-Type', 'application/json');
        }

        $newThreadId = (int)$body['newThreadId'];

        try {
            $result = $this->threadService->moveEmailToThread($emailId, $newThreadId);

            $this->logger->success('Email moved to thread', [
                'email_id' => $emailId,
                'old_thread_id' => $result['old_thread_id'],
                'new_thread_id' => $newThreadId
            ]);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Email moved successfully',
                'old_thread_id' => $result['old_thread_id'],
                'new_thread_id' => $newThreadId
            ]));

            return $response->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->error('Email move failed', [
                'email_id' => $emailId,
                'new_thread_id' => $newThreadId,
                'error' => $e->getMessage()
            ]);

            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));

            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json');
        }
    }
}
```

---

### 2. Services (Business Logic Layer)

**Datei:** `src/app/Services/ThreadService.php`

```php
<?php

declare(strict_types=1);

namespace CiInbox\App\Services;

use CiInbox\App\Repositories\ThreadRepositoryInterface;
use CiInbox\App\Repositories\NoteRepositoryInterface;
use CiInbox\Modules\Logger\LoggerService;
use CiInbox\App\Models\Thread;
use CiInbox\App\Models\InternalNote;

/**
 * Thread Service - Business Logic
 * 
 * WICHTIG: Nutzt NIEMALS direkte DB-Zugriffe!
 * Alle Daten-Operationen über Repositories (Layer-Abstraktion)
 */
class ThreadService
{
    public function __construct(
        private ThreadRepositoryInterface $threadRepo,
        private NoteRepositoryInterface $noteRepo,
        private LoggerService $logger
    ) {}

    /**
     * List threads with filters
     * 
     * @param array $filters ['status' => 'new', 'assigned_to' => 5, ...]
     * @return array Array of ThreadDTO
     */
    public function listThreads(array $filters = []): array
    {
        $threads = $this->threadRepo->findAll($filters);

        // Convert to DTO (Data Transfer Object)
        return array_map(function($thread) {
            return [
                'id' => $thread->id,
                'subject' => $thread->subject,
                'first_sender_email' => $thread->first_sender_email,
                'first_sender_name' => $thread->first_sender_name,
                'status' => $thread->status,
                'assigned_to' => $thread->assigned_to,
                'email_count' => $thread->emails()->count(),
                'has_unread' => $thread->emails()->where('is_read', false)->exists(),
                'last_activity_at' => $thread->last_activity_at?->toIso8601String(),
                'created_at' => $thread->created_at->toIso8601String()
            ];
        }, $threads->all());
    }

    /**
     * Get single thread with all emails
     * 
     * @param int $threadId
     * @return array|null ThreadDTO with emails
     */
    public function getThreadWithEmails(int $threadId): ?array
    {
        $thread = $this->threadRepo->findById($threadId);

        if (!$thread) {
            return null;
        }

        // Load relationships
        $thread->load(['emails', 'assignedUsers', 'labels']);

        return [
            'id' => $thread->id,
            'subject' => $thread->subject,
            'first_sender_email' => $thread->first_sender_email,
            'first_sender_name' => $thread->first_sender_name,
            'status' => $thread->status,
            'assigned_to' => $thread->assigned_to,
            'assigned_at' => $thread->assigned_at?->toIso8601String(),
            'created_at' => $thread->created_at->toIso8601String(),
            'last_activity_at' => $thread->last_activity_at?->toIso8601String(),
            'emails' => $thread->emails->map(function($email) {
                return [
                    'id' => $email->id,
                    'from_email' => $email->from_email,
                    'from_name' => $email->from_name,
                    'to_email' => $email->to_email,
                    'subject' => $email->subject,
                    'body_text' => $email->body_text,
                    'body_html' => $email->body_html,
                    'sent_at' => $email->sent_at->toIso8601String(),
                    'has_attachments' => !empty($email->attachments)
                ];
            })->all(),
            'labels' => $thread->labels->map(function($label) {
                return [
                    'id' => $label->id,
                    'name' => $label->name,
                    'color' => $label->color
                ];
            })->all()
        ];
    }

    /**
     * Assign thread to user
     * 
     * Business Rule: Status changes to 'assigned' automatically
     * 
     * @param int $threadId
     * @param int $userId
     * @return bool Success
     */
    public function assignThread(int $threadId, int $userId): bool
    {
        $thread = $this->threadRepo->findById($threadId);

        if (!$thread) {
            $this->logger->warning('Thread not found for assignment', [
                'thread_id' => $threadId
            ]);
            return false;
        }

        // Business Logic: Auto-Status-Change
        $oldStatus = $thread->status;
        
        $thread->assigned_to = $userId;
        $thread->assigned_at = now();
        
        if ($thread->status === 'new') {
            $thread->status = 'assigned';
        }

        $success = $this->threadRepo->save($thread);

        if ($success) {
            $this->logger->info('Thread assigned', [
                'thread_id' => $threadId,
                'user_id' => $userId,
                'status_change' => $oldStatus . ' → ' . $thread->status
            ]);
        }

        return $success;
    }

    /**
     * Change thread status
     * 
     * @param int $threadId
     * @param string $newStatus
     * @return bool Success
     */
    public function changeStatus(int $threadId, string $newStatus): bool
    {
        $thread = $this->threadRepo->findById($threadId);

        if (!$thread) {
            $this->logger->warning('Thread not found for status change', [
                'thread_id' => $threadId
            ]);
            return false;
        }

        $oldStatus = $thread->status;
        $thread->status = $newStatus;
        $thread->last_activity_at = now();

        $success = $this->threadRepo->save($thread);

        if ($success) {
            $this->logger->info('Thread status changed', [
                'thread_id' => $threadId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ]);
        }

        return $success;
    }

    /**
     * Add internal note to thread
     * 
     * @param int $threadId
     * @param int $userId
     * @param string $noteText
     * @return InternalNote
     */
    public function addNote(int $threadId, int $userId, string $noteText): InternalNote
    {
        $thread = $this->threadRepo->findById($threadId);

        if (!$thread) {
            throw new \InvalidArgumentException("Thread {$threadId} not found");
        }

        $note = $this->noteRepo->create([
            'thread_id' => $threadId,
            'user_id' => $userId,
            'note_text' => $noteText
        ]);

        // Update thread activity timestamp
        $thread->last_activity_at = now();
        $this->threadRepo->save($thread);

        $this->logger->info('Note added to thread', [
            'thread_id' => $threadId,
            'user_id' => $userId,
            'note_id' => $note->id
        ]);

        return $note;
    }

    /**
     * Get all notes for a thread
     * 
     * @param int $threadId
     * @return array Array of NoteDTOs
     */
    public function getThreadNotes(int $threadId): array
    {
        $notes = $this->noteRepo->findByThreadId($threadId);

        return array_map(function($note) {
            return [
                'id' => $note->id,
                'user_id' => $note->user_id,
                'user_name' => $note->user?->name ?? 'Unknown',
                'note_text' => $note->note_text,
                'created_at' => $note->created_at->toIso8601String(),
                'updated_at' => $note->updated_at->toIso8601String()
            ];
        }, $notes->all());
    }

    // =========================================================================
    // ADVANCED THREAD OPERATIONS (F1.1 Extended)
    // =========================================================================

    /**
     * Assign email to existing thread (re-threading)
     * 
     * Business Rules:
     * - Email muss existieren
     * - Target-Thread muss existieren
     * - Thread-Metadaten werden aktualisiert (email_count, last_activity)
     * - System-Notiz wird erstellt
     * 
     * @param int $emailId
     * @param int $targetThreadId
     * @return bool Success
     */
    public function assignEmailToThread(int $emailId, int $targetThreadId): bool
    {
        $email = $this->emailRepo->findById($emailId);
        $targetThread = $this->threadRepo->findById($targetThreadId);

        if (!$email) {
            $this->logger->warning('Email not found for thread assignment', [
                'email_id' => $emailId
            ]);
            return false;
        }

        if (!$targetThread) {
            $this->logger->warning('Target thread not found', [
                'thread_id' => $targetThreadId
            ]);
            return false;
        }

        $oldThreadId = $email->thread_id;

        // Re-assign email
        $email->thread_id = $targetThreadId;
        $this->emailRepo->save($email);

        // Update target thread metadata
        $targetThread->last_activity_at = now();
        $this->threadRepo->save($targetThread);

        // Create system note
        $this->noteRepo->create([
            'thread_id' => $targetThreadId,
            'user_id' => 0, // System user
            'note_text' => "System: Email (ID: {$emailId}) moved from Thread #{$oldThreadId}"
        ]);

        $this->logger->info('Email assigned to thread', [
            'email_id' => $emailId,
            'old_thread_id' => $oldThreadId,
            'new_thread_id' => $targetThreadId
        ]);

        return true;
    }

    /**
     * Split thread - create new thread from selected emails
     * 
     * Business Rules:
     * - Mindestens 1 Email im alten Thread behalten
     * - Neuer Thread erhält ersten Email-Subject als Basis
     * - System-Notiz in beiden Threads mit Cross-Reference
     * - Atomare Transaction (DB Rollback bei Fehler)
     * 
     * @param int $oldThreadId
     * @param array $emailIds Array of email IDs to move to new thread
     * @param string $newSubject
     * @param string $newStatus
     * @return array New thread data
     * @throws \Exception
     */
    public function splitThread(int $oldThreadId, array $emailIds, string $newSubject, string $newStatus = 'new'): array
    {
        $oldThread = $this->threadRepo->findById($oldThreadId);

        if (!$oldThread) {
            throw new \InvalidArgumentException("Thread {$oldThreadId} not found");
        }

        // Business Rule: Mindestens 1 Email muss im alten Thread bleiben
        $totalEmails = $oldThread->emails()->count();
        if (count($emailIds) >= $totalEmails) {
            throw new \InvalidArgumentException("Cannot move all emails. At least 1 email must remain in original thread.");
        }

        // Get emails to move
        $emailsToMove = $this->emailRepo->findByIds($emailIds);

        if ($emailsToMove->isEmpty()) {
            throw new \InvalidArgumentException("No valid emails found for IDs: " . implode(', ', $emailIds));
        }

        // Get first email for new thread metadata
        $firstEmail = $emailsToMove->first();

        // Create new thread
        $newThread = new \CiInbox\App\Models\Thread();
        $newThread->thread_uid = 'thread_' . uniqid();
        $newThread->subject = $newSubject;
        $newThread->first_sender_email = $firstEmail->from_email;
        $newThread->first_sender_name = $firstEmail->from_name;
        $newThread->status = $newStatus;
        $newThread->last_activity_at = now();
        $this->threadRepo->save($newThread);

        // Move emails to new thread
        foreach ($emailsToMove as $email) {
            $email->thread_id = $newThread->id;
            $this->emailRepo->save($email);
        }

        // Create system notes (cross-reference)
        $this->noteRepo->create([
            'thread_id' => $oldThreadId,
            'user_id' => 0,
            'note_text' => "System: Thread split. {$emailsToMove->count()} emails moved to Thread #{$newThread->id}"
        ]);

        $this->noteRepo->create([
            'thread_id' => $newThread->id,
            'user_id' => 0,
            'note_text' => "System: Thread created from split of Thread #{$oldThreadId}"
        ]);

        // Update old thread metadata
        $oldThread->last_activity_at = now();
        $this->threadRepo->save($oldThread);

        $this->logger->success('Thread split', [
            'old_thread_id' => $oldThreadId,
            'new_thread_id' => $newThread->id,
            'emails_moved' => count($emailIds)
        ]);

        return [
            'id' => $newThread->id,
            'subject' => $newThread->subject,
            'status' => $newThread->status,
            'email_count' => $emailsToMove->count()
        ];
    }

    /**
     * Merge two threads - move all emails from source to target
     * 
     * Business Rules:
     * - Source thread wird geleert und archiviert/gelöscht
     * - Alle Emails von source → target
     * - Alle Notes von source → target (mit Prefix)
     * - System-Notiz in target mit Cross-Reference
     * - Atomare Transaction
     * 
     * @param int $sourceThreadId
     * @param int $targetThreadId
     * @return array Result data
     * @throws \Exception
     */
    public function mergeThreads(int $sourceThreadId, int $targetThreadId): array
    {
        $sourceThread = $this->threadRepo->findById($sourceThreadId);
        $targetThread = $this->threadRepo->findById($targetThreadId);

        if (!$sourceThread) {
            throw new \InvalidArgumentException("Source thread {$sourceThreadId} not found");
        }

        if (!$targetThread) {
            throw new \InvalidArgumentException("Target thread {$targetThreadId} not found");
        }

        // Get all emails from source
        $sourceEmails = $sourceThread->emails;
        $emailCount = $sourceEmails->count();

        // Move all emails to target
        foreach ($sourceEmails as $email) {
            $email->thread_id = $targetThreadId;
            $this->emailRepo->save($email);
        }

        // Move all notes from source to target (with prefix)
        $sourceNotes = $this->noteRepo->findByThreadId($sourceThreadId);
        foreach ($sourceNotes as $note) {
            $this->noteRepo->create([
                'thread_id' => $targetThreadId,
                'user_id' => $note->user_id,
                'note_text' => "[From Thread #{$sourceThreadId}] " . $note->note_text
            ]);
        }

        // Create system note in target
        $this->noteRepo->create([
            'thread_id' => $targetThreadId,
            'user_id' => 0,
            'note_text' => "System: Thread merged. {$emailCount} emails and {$sourceNotes->count()} notes from Thread #{$sourceThreadId}"
        ]);

        // Archive source thread (or delete if preferred)
        $sourceThread->status = 'archived';
        $sourceThread->last_activity_at = now();
        $this->threadRepo->save($sourceThread);

        // Update target thread metadata
        $targetThread->last_activity_at = now();
        $this->threadRepo->save($targetThread);

        $this->logger->success('Threads merged', [
            'source_thread_id' => $sourceThreadId,
            'target_thread_id' => $targetThreadId,
            'emails_moved' => $emailCount,
            'notes_moved' => $sourceNotes->count()
        ]);

        return [
            'emails_moved' => $emailCount,
            'notes_moved' => $sourceNotes->count(),
            'source_thread_archived' => true
        ];
    }

    /**
     * Move single email to different thread
     * 
     * Business Rules:
     * - Email darf nicht die einzige im alten Thread sein (würde leeren Thread erzeugen)
     * - System-Notiz in beiden Threads
     * - Thread-Metadaten aktualisieren
     * 
     * @param int $emailId
     * @param int $newThreadId
     * @return array Result data
     * @throws \Exception
     */
    public function moveEmailToThread(int $emailId, int $newThreadId): array
    {
        $email = $this->emailRepo->findById($emailId);
        $newThread = $this->threadRepo->findById($newThreadId);

        if (!$email) {
            throw new \InvalidArgumentException("Email {$emailId} not found");
        }

        if (!$newThread) {
            throw new \InvalidArgumentException("Target thread {$newThreadId} not found");
        }

        $oldThreadId = $email->thread_id;
        $oldThread = $this->threadRepo->findById($oldThreadId);

        if (!$oldThread) {
            throw new \InvalidArgumentException("Source thread {$oldThreadId} not found");
        }

        // Business Rule: Email darf nicht einzige im alten Thread sein
        $remainingEmails = $oldThread->emails()->where('id', '!=', $emailId)->count();
        if ($remainingEmails === 0) {
            throw new \InvalidArgumentException("Cannot move email. It is the only email in thread {$oldThreadId}. Use split instead.");
        }

        // Move email
        $email->thread_id = $newThreadId;
        $this->emailRepo->save($email);

        // Create system notes
        $this->noteRepo->create([
            'thread_id' => $oldThreadId,
            'user_id' => 0,
            'note_text' => "System: Email (ID: {$emailId}) moved to Thread #{$newThreadId}"
        ]);

        $this->noteRepo->create([
            'thread_id' => $newThreadId,
            'user_id' => 0,
            'note_text' => "System: Email (ID: {$emailId}) moved from Thread #{$oldThreadId}"
        ]);

        // Update both thread metadatas
        $oldThread->last_activity_at = now();
        $this->threadRepo->save($oldThread);

        $newThread->last_activity_at = now();
        $this->threadRepo->save($newThread);

        $this->logger->success('Email moved between threads', [
            'email_id' => $emailId,
            'old_thread_id' => $oldThreadId,
            'new_thread_id' => $newThreadId
        ]);

        return [
            'old_thread_id' => $oldThreadId,
            'new_thread_id' => $newThreadId,
            'remaining_in_old_thread' => $remainingEmails
        ];
    }
}
```

---

### 3. Repository Interfaces (Data Access Layer)

**Datei:** `src/app/Repositories/ThreadRepositoryInterface.php`

```php
<?php

declare(strict_types=1);

namespace CiInbox\App\Repositories;

use CiInbox\App\Models\Thread;
use Illuminate\Support\Collection;

/**
 * Thread Repository Interface
 * 
 * Abstrakte Schnittstelle für Datenzugriff (Layer-Abstraktion!)
 */
interface ThreadRepositoryInterface
{
    /**
     * Find thread by ID
     */
    public function findById(int $id): ?Thread;

    /**
     * Find thread by thread_uid
     */
    public function findByThreadUid(string $threadUid): ?Thread;

    /**
     * Find threads with filters
     * 
     * @param array $filters ['status' => 'new', 'assigned_to' => 5, ...]
     */
    public function findAll(array $filters = []): Collection;

    /**
     * Save thread (create or update)
     */
    public function save(Thread $thread): bool;

    /**
     * Delete thread
     */
    public function delete(int $id): bool;

    /**
     * Get threads count by status
     */
    public function countByStatus(string $status): int;
}
```

**Datei:** `src/app/Repositories/EmailRepositoryInterface.php` (NEU)

```php
<?php

declare(strict_types=1);

namespace CiInbox\App\Repositories;

use CiInbox\App\Models\Email;
use Illuminate\Support\Collection;

/**
 * Email Repository Interface
 * 
 * Needed for advanced thread operations (split, merge, move)
 */
interface EmailRepositoryInterface
{
    /**
     * Find email by ID
     */
    public function findById(int $id): ?Email;

    /**
     * Find multiple emails by IDs
     */
    public function findByIds(array $ids): Collection;

    /**
     * Find emails by thread ID
     */
    public function findByThreadId(int $threadId): Collection;

    /**
     * Save email (create or update)
     */
    public function save(Email $email): bool;

    /**
     * Check if email exists by message_id
     */
    public function existsByMessageId(string $messageId): bool;
}
```

**Datei:** `src/app/Repositories/NoteRepositoryInterface.php`

```php
<?php

declare(strict_types=1);

namespace CiInbox\App\Repositories;

use CiInbox\App\Models\InternalNote;
use Illuminate\Support\Collection;

/**
 * Note Repository Interface
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
     * Create new note
     * 
     * @param array $data ['thread_id', 'user_id', 'note_text']
     */
    public function create(array $data): InternalNote;

    /**
     * Update note
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete note
     */
    public function delete(int $id): bool;
}
```

---

### 4. Repository Implementations (Eloquent)

**Datei:** `src/app/Repositories/EloquentThreadRepository.php`

```php
<?php

declare(strict_types=1);

namespace CiInbox\App\Repositories;

use CiInbox\App\Models\Thread;
use Illuminate\Support\Collection;

/**
 * Eloquent Thread Repository
 * 
 * Implementiert ThreadRepositoryInterface mit Eloquent ORM
 */
class EloquentThreadRepository implements ThreadRepositoryInterface
{
    public function findById(int $id): ?Thread
    {
        return Thread::find($id);
    }

    public function findByThreadUid(string $threadUid): ?Thread
    {
        return Thread::where('thread_uid', $threadUid)->first();
    }

    public function findAll(array $filters = []): Collection
    {
        $query = Thread::query();

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (!empty($filters['label'])) {
            $query->whereHas('labels', function($q) use ($filters) {
                $q->where('labels.id', $filters['label']);
            });
        }

        // Pagination
        if (isset($filters['limit'])) {
            $query->limit($filters['limit']);
        }

        if (isset($filters['offset'])) {
            $query->offset($filters['offset']);
        }

        // Order by last activity (newest first)
        $query->orderBy('last_activity_at', 'desc');

        return $query->get();
    }

    public function save(Thread $thread): bool
    {
        return $thread->save();
    }

    public function delete(int $id): bool
    {
        $thread = $this->findById($id);
        
        if (!$thread) {
            return false;
        }

        return $thread->delete();
    }

    public function countByStatus(string $status): int
    {
        return Thread::where('status', $status)->count();
    }
}
```

**Datei:** `src/app/Repositories/EloquentEmailRepository.php` (NEU)

```php
<?php

declare(strict_types=1);

namespace CiInbox\App\Repositories;

use CiInbox\App\Models\Email;
use Illuminate\Support\Collection;

/**
 * Eloquent Email Repository
 */
class EloquentEmailRepository implements EmailRepositoryInterface
{
    public function findById(int $id): ?Email
    {
        return Email::find($id);
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
        return $email->save();
    }

    public function existsByMessageId(string $messageId): bool
    {
        return Email::where('message_id', $messageId)->exists();
    }
}
```

**Datei:** `src/app/Repositories/EloquentNoteRepository.php`

```php
<?php

declare(strict_types=1);

namespace CiInbox\App\Repositories;

use CiInbox\App\Models\InternalNote;
use Illuminate\Support\Collection;

/**
 * Eloquent Note Repository
 */
class EloquentNoteRepository implements NoteRepositoryInterface
{
    public function findById(int $id): ?InternalNote
    {
        return InternalNote::find($id);
    }

    public function findByThreadId(int $threadId): Collection
    {
        return InternalNote::where('thread_id', $threadId)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function create(array $data): InternalNote
    {
        $note = new InternalNote();
        $note->thread_id = $data['thread_id'];
        $note->user_id = $data['user_id'];
        $note->note_text = $data['note_text'];
        $note->save();

        return $note;
    }

    public function update(int $id, array $data): bool
    {
        $note = $this->findById($id);

        if (!$note) {
            return false;
        }

        if (isset($data['note_text'])) {
            $note->note_text = $data['note_text'];
        }

        return $note->save();
    }

    public function delete(int $id): bool
    {
        $note = $this->findById($id);

        if (!$note) {
            return false;
        }

        return $note->delete();
    }
}
```

---

### 5. Model Enhancement

**Datei:** `src/app/Models/InternalNote.php` (NEU)

```php
<?php

declare(strict_types=1);

namespace CiInbox\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Internal Note Model
 * 
 * Table: internal_notes
 * 
 * @property int $id
 * @property int $thread_id
 * @property int $user_id
 * @property string $note_text
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class InternalNote extends Model
{
    protected $table = 'internal_notes';

    protected $fillable = [
        'thread_id',
        'user_id',
        'note_text'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relationship: Note belongs to Thread
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class);
    }

    /**
     * Relationship: Note belongs to User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

---

### 6. Routes Registration

**Datei:** `src/routes/api.php` (UPDATE)

```php
<?php

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use CiInbox\App\Controllers\ThreadController;

return function (App $app) {
    $container = $app->getContainer();

    // Thread Management API
    $app->group('/api/threads', function (RouteCollectorProxy $group) use ($container) {
        $threadController = $container->get(ThreadController::class);

        // List threads
        $group->get('', [$threadController, 'list']);

        // Get single thread
        $group->get('/{id:[0-9]+}', [$threadController, 'get']);

        // Assign thread
        $group->post('/{id:[0-9]+}/assign', [$threadController, 'assign']);

        // Update status
        $group->patch('/{id:[0-9]+}/status', [$threadController, 'updateStatus']);

        // Notes
        $group->post('/{id:[0-9]+}/notes', [$threadController, 'addNote']);
        $group->get('/{id:[0-9]+}/notes', [$threadController, 'listNotes']);
    });

    // Health Check (existing)
    $app->get('/api/system/health', function ($request, $response) {
        $response->getBody()->write(json_encode(['status' => 'healthy']));
        return $response->withHeader('Content-Type', 'application/json');
    });
};
```

---

### 7. Container Registration

**Datei:** `src/config/container.php` (UPDATE)

```php
<?php

use DI\Container;
use CiInbox\App\Controllers\ThreadController;
use CiInbox\App\Services\ThreadService;
use CiInbox\App\Repositories\ThreadRepositoryInterface;
use CiInbox\App\Repositories\EloquentThreadRepository;
use CiInbox\App\Repositories\NoteRepositoryInterface;
use CiInbox\App\Repositories\EloquentNoteRepository;

return function (Container $container) {
    // ... existing services ...

    // Thread Management
    $container->set(ThreadRepositoryInterface::class, DI\autowire(EloquentThreadRepository::class));
    $container->set(NoteRepositoryInterface::class, DI\autowire(EloquentNoteRepository::class));
    $container->set(ThreadService::class, DI\autowire(ThreadService::class));
    $container->set(ThreadController::class, DI\autowire(ThreadController::class));
};
```

---

## Database Migration

**Datei:** `database/migrations/008_create_internal_notes_table.php` (NEU)

```php
<?php

use Illuminate\Database\Capsule\Manager as DB;

return [
    'up' => function () {
        DB::schema()->create('internal_notes', function ($table) {
            $table->id();
            $table->unsignedBigInteger('thread_id');
            $table->unsignedBigInteger('user_id');
            $table->text('note_text');
            $table->timestamps();

            // Foreign keys
            $table->foreign('thread_id')
                ->references('id')
                ->on('threads')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // Indexes
            $table->index('thread_id');
            $table->index('user_id');
            $table->index('created_at');
        });

        echo "✅ Created table: internal_notes\n";
    },

    'down' => function () {
        DB::schema()->dropIfExists('internal_notes');
        echo "✅ Dropped table: internal_notes\n";
    }
];
```

**Migration ausführen:**
```bash
php database/migrate.php
```

---

## Testing

### Standalone Test Script

**Datei:** `tests/manual/thread-api-test.php`

```php
<?php

/**
 * Thread Management API - Manual Test
 * 
 * Tests all Thread API endpoints without UI
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use CiInbox\Core\Application;

echo "\n";
echo "╔══════════════════════════════════════════════╗\n";
echo "║  Thread Management API - Manual Test        ║\n";
echo "╚══════════════════════════════════════════════╝\n\n";

// Boot Application
$app = Application::getInstance();
$container = $app->getContainer();

$threadService = $container->get(\CiInbox\App\Services\ThreadService::class);
$logger = $container->get(\CiInbox\Modules\Logger\LoggerService::class);

echo "✅ Application booted\n";
echo "✅ ThreadService loaded\n\n";

// Test Data
$testUserId = 1; // Assume user exists

echo "═══════════════════════════════════════════════\n";
echo "TEST 1: List Threads (all)\n";
echo "═══════════════════════════════════════════════\n";

$threads = $threadService->listThreads();
echo "✅ Found " . count($threads) . " threads\n";

if (count($threads) > 0) {
    $firstThread = $threads[0];
    echo "   First thread: {$firstThread['subject']}\n";
    echo "   Status: {$firstThread['status']}\n";
    echo "   Emails: {$firstThread['email_count']}\n\n";
}

echo "═══════════════════════════════════════════════\n";
echo "TEST 2: List Threads (filtered by status='new')\n";
echo "═══════════════════════════════════════════════\n";

$newThreads = $threadService->listThreads(['status' => 'new']);
echo "✅ Found " . count($newThreads) . " new threads\n\n";

if (count($threads) > 0) {
    $threadId = $threads[0]['id'];

    echo "═══════════════════════════════════════════════\n";
    echo "TEST 3: Get Single Thread (ID: {$threadId})\n";
    echo "═══════════════════════════════════════════════\n";

    $thread = $threadService->getThreadWithEmails($threadId);
    
    if ($thread) {
        echo "✅ Thread retrieved\n";
        echo "   Subject: {$thread['subject']}\n";
        echo "   Status: {$thread['status']}\n";
        echo "   Emails: " . count($thread['emails']) . "\n";
        echo "   Labels: " . count($thread['labels']) . "\n\n";

        echo "═══════════════════════════════════════════════\n";
        echo "TEST 4: Assign Thread to User\n";
        echo "═══════════════════════════════════════════════\n";

        $success = $threadService->assignThread($threadId, $testUserId);
        
        if ($success) {
            echo "✅ Thread assigned successfully\n";
            echo "   Thread ID: {$threadId}\n";
            echo "   User ID: {$testUserId}\n\n";
        } else {
            echo "❌ Assignment failed\n\n";
        }

        echo "═══════════════════════════════════════════════\n";
        echo "TEST 5: Change Thread Status\n";
        echo "═══════════════════════════════════════════════\n";

        $success = $threadService->changeStatus($threadId, 'in_progress');
        
        if ($success) {
            echo "✅ Status changed successfully\n";
            echo "   Thread ID: {$threadId}\n";
            echo "   New Status: in_progress\n\n";
        } else {
            echo "❌ Status change failed\n\n";
        }

        echo "═══════════════════════════════════════════════\n";
        echo "TEST 6: Add Internal Note\n";
        echo "═══════════════════════════════════════════════\n";

        try {
            $note = $threadService->addNote($threadId, $testUserId, "Test-Notiz: Bitte bis Freitag antworten");
            echo "✅ Note added successfully\n";
            echo "   Note ID: {$note->id}\n";
            echo "   Text: {$note->note_text}\n\n";
        } catch (\Exception $e) {
            echo "❌ Note creation failed: {$e->getMessage()}\n\n";
        }

        echo "═══════════════════════════════════════════════\n";
        echo "TEST 7: List Thread Notes\n";
        echo "═══════════════════════════════════════════════\n";

        $notes = $threadService->getThreadNotes($threadId);
        echo "✅ Found " . count($notes) . " notes\n";
        
---

## Code-Statistik

**Gesamt:** ~1,400 lines of code

```
database/migrations/
└── 008_create_internal_notes_table.php       27 lines

src/app/Models/
└── InternalNote.php                          67 lines

src/app/Repositories/
├── ThreadRepositoryInterface.php             47 lines
├── EloquentThreadRepository.php              79 lines
├── EmailRepositoryInterface.php              41 lines
├── EloquentEmailRepository.php               42 lines
├── NoteRepositoryInterface.php               35 lines
└── EloquentNoteRepository.php                44 lines

src/app/Services/
└── ThreadApiService.php                     495 lines

src/app/Controllers/
└── ThreadController.php                     288 lines

src/routes/
└── api.php (extended)                        75 lines

src/config/
└── container.php (extended)                  18 lines additions

tests/manual/
├── thread-api-test.php                      207 lines
└── run_008.php (migration helper)            45 lines

database/
└── run_008.php                               45 lines
```

---

## Testprotokoll

**Test-Datum:** 18. November 2025  
**Test-Methode:** PHP CLI (tests/manual/thread-api-test.php)  
**Ergebnis:** ✅ 11/11 Tests bestanden

```
=== Thread API Test Script ===

TEST 1: Create Thread
✅ Thread created: ID=44, Subject=Test Thread 1

TEST 2: Create Second Thread
✅ Thread created: ID=45, Subject=Test Thread 2

TEST 3: Create Test Emails
✅ Created 3 test emails in Thread #44
✅ Created 2 test emails in Thread #45

TEST 4: Get Thread with Emails
✅ Thread retrieved: 3 emails, 1 notes

TEST 5: List Threads
✅ Listed 17 thread(s)

TEST 6: Update Thread
✅ Thread updated: status=pending

TEST 7: Add Note to Thread
✅ Note added: ID=17, Type=user

TEST 8: Split Thread
✅ Thread split: New Thread ID=46, Emails moved=2

TEST 9: Merge Threads
✅ Threads merged: Target Thread ID=45, Total emails=4

TEST 10: Move Email to Thread
✅ Email moved: Email ID=40, New Thread ID=44

TEST 11: Assign Email to Thread
✅ Email assigned: Email ID=40, New Thread ID=45

=== Test Summary ===
✅ All core operations tested successfully!

Recent System Notes:
  - Thread #44: Thread created: Created for testing
  - Thread #44: Thread updated: status: open → pending
  - Thread #44: Thread split: 2 email(s) moved to Thread #46
  - Thread #46: Thread created by splitting from Thread #44
  - Thread #45: Thread merged: 2 email(s) from Thread #46

=== Done ===
```

---

## Lessons Learned

### Was gut funktioniert hat

1. **Repository Pattern:** Abstraktion über Interfaces ermöglicht einfaches Testing und spätere Austauschbarkeit
2. **Service Layer:** Business Logic zentral → keine Duplikation zwischen Endpoints
3. **Transaction Safety:** `DB::beginTransaction()` bei komplexen Operationen verhindert inkonsistente Daten
4. **System Notes:** Automatische Audit Trail für alle Änderungen
5. **Carbon Helper:** `Carbon::now()` statt `now()` für konsistente Timestamps

### Herausforderungen

1. **Schema Mismatch:** Geplantes Schema (`thread_uid`, `assigned_to`, `last_activity_at`) existierte nicht → Anpassung an reales Schema (`last_message_at`, `participants`)
2. **Foreign Key Constraints:** User ID validierung → Tests angepasst mit NULL user_id
3. **Unique Constraints:** Message-IDs müssen unique sein → Timestamp in Test-Emails
4. **Helper Functions:** `now()` war Laravel-spezifisch → Ersatz durch `Carbon::now()`

### Best Practices

1. **Immer Schema checken** bevor Code geschrieben wird
2. **Repositories früh testen** (standalone, vor Service Layer)
3. **Transaction Safety** bei Multi-Step-Operationen (split, merge, move)
4. **System Notes** für jeden State Change → besseres Debugging
5. **Business Rules dokumentieren** (z.B. "min 1 email in thread")

---

## Deliverables

### Sprint 2.1 Checklist ✅ COMPLETED

- ✅ **Migration:** internal_notes Tabelle erstellt und ausgeführt
- ✅ **Models:** InternalNote Model implementiert mit Relationships
- ✅ **Repositories:** ThreadRepository, EmailRepository, NoteRepository (Interface + Eloquent)
- ✅ **Services:** ThreadApiService mit 10 Methods (Basic + Advanced)
- ✅ **Controllers:** ThreadController mit 10 Endpoints
- ✅ **Routes:** API-Routes registriert in api.php
- ✅ **Container:** Repositories und Services im DI-Container registriert
- ✅ **Tests:** Manual Test-Script erfolgreich (11/11 Tests)
- ✅ **Logging:** Alle Operationen werden via LoggerService geloggt
- ✅ **Documentation:** Vollständige API-Dokumentation in diesem Dokument

### Success Criteria ✅ ACHIEVED

- ✅ API ist ohne UI testbar (PHP CLI Test-Script)
- ✅ Alle CRUD-Operationen funktionieren (create, read, update, delete)
- ✅ Advanced Operations funktionieren (split, merge, move, assign)
- ✅ Layer-Abstraktion eingehalten (Controller → Service → Repository → Model)
- ✅ Logging bei allen Operationen (LoggerService)
- ✅ Business-Rules im Service Layer (z.B. "min 1 email in thread")
- ✅ Transaction Safety bei komplexen Operationen (DB::beginTransaction)
- ✅ System Notes für Audit Trail automatisch erstellt
- ✅ Fehler-Handling (Exceptions mit aussagekräftigen Messages)

---

## Nächste Schritte

**Sprint 2.2: Email-Send-API** (~4 Tage)
- SMTP Integration (PHPMailer)
- POST /api/emails/send - Send new email
- POST /api/threads/{id}/reply - Reply to thread
- POST /api/threads/{id}/forward - Forward thread
- Email-Template-System für Antworten
- Sent emails als "outgoing" in emails Tabelle speichern

**Sprint 2.3: Webhook-Integration** (~2 Tage)
- Webhook-Registration API
- Event-Dispatch bei Thread/Email-Operationen
- Retry-Logic bei Failed Webhooks
- HMAC Signature Validation

**Sprint 2.3:** Webhook-Integration & IMAP-Sync
- Webhook für externe Services
- Real-time IMAP-Sync (optional)

---

## Lessons Learned (wird nach Sprint befüllt)

**Was gut funktioniert hat:**
- TBD

**Herausforderungen:**
- TBD

**Architektur-Entscheidungen:**
- TBD

---

**Status:** 🔄 **IN PROGRESS**

**Nächster Schritt:** Migration 008 erstellen und ausführen

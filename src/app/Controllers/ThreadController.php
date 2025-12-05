<?php

declare(strict_types=1);

namespace CiInbox\App\Controllers;

use CiInbox\App\Services\ThreadApiService;
use CiInbox\App\Services\ThreadBulkService;
use CiInbox\Modules\Logger\LoggerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Thread Controller
 * 
 * Handles HTTP requests for thread management
 */
class ThreadController
{
    public function __construct(
        private ThreadApiService $threadService,
        private ThreadBulkService $bulkService,
        private LoggerService $logger
    ) {}

    /**
     * Create new thread
     * POST /api/threads
     */
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();

            // Validation
            if (empty($data['subject'])) {
                return $this->jsonResponse($response, [
                    'error' => 'Subject is required'
                ], 400);
            }

            $thread = $this->threadService->createThread($data);

            return $this->jsonResponse($response, [
                'success' => true,
                'thread' => $thread->toArray()
            ], 201);

        } catch (\Exception $e) {
            $this->logger->error('Failed to create thread', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get thread by ID
     * GET /api/threads/{id}
     */
    public function get(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int)$args['id'];
            
            $withEmails = $request->getQueryParams()['with_emails'] ?? true;
            $withNotes = $request->getQueryParams()['with_notes'] ?? true;

            $thread = $this->threadService->getThread($id, $withEmails, $withNotes);

            if (!$thread) {
                return $this->jsonResponse($response, [
                    'error' => 'Thread not found'
                ], 404);
            }

            return $this->jsonResponse($response, [
                'thread' => $thread->toArray()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to get thread', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List threads
     * GET /api/threads
     */
    public function list(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();
            
            $this->logger->debug('ThreadController: List threads called', [
                'query_params' => $queryParams
            ]);

            $filters = [
                'status' => $queryParams['status'] ?? null,
                'assigned_to' => $queryParams['assigned_to'] ?? null,
                'label' => $queryParams['label'] ?? null,
                'search' => $queryParams['search'] ?? null,
                'limit' => isset($queryParams['limit']) ? (int)$queryParams['limit'] : 50,
                'offset' => isset($queryParams['offset']) ? (int)$queryParams['offset'] : 0,
            ];
            
            $this->logger->debug('ThreadController: Filters', [
                'filters' => $filters
            ]);

            $result = $this->threadService->listThreads($filters);
            
            $this->logger->debug('ThreadController: Result', [
                'total' => $result['total']
            ]);

            return $this->jsonResponse($response, $result);

        } catch (\Exception $e) {
            $this->logger->error('Failed to list threads', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update thread
     * PUT /api/threads/{id}
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int)$args['id'];
            $data = $request->getParsedBody();

            $thread = $this->threadService->updateThread($id, $data);

            return $this->jsonResponse($response, [
                'success' => true,
                'thread' => $thread->toArray()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to update thread', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete thread
     * DELETE /api/threads/{id}
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int)$args['id'];

            $result = $this->threadService->deleteThread($id);

            return $this->jsonResponse($response, [
                'success' => $result
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to delete thread', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add note to thread
     * POST /api/threads/{id}/notes
     */
    public function addNote(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int)$args['id'];
            $data = $request->getParsedBody();
            
            // Debug logging
            $this->logger->info('Add note request received', [
                'thread_id' => $id,
                'raw_body' => (string)$request->getBody(),
                'parsed_body' => $data,
                'content_type' => $request->getHeaderLine('Content-Type')
            ]);

            if (empty($data['content'])) {
                $this->logger->warning('Content is empty', ['data' => $data]);
                return $this->jsonResponse($response, [
                    'error' => 'Content is required'
                ], 400);
            }

            $userId = $data['user_id'] ?? null;
            $position = isset($data['position']) ? (int)$data['position'] : null;
            $note = $this->threadService->addNote($id, $data['content'], $userId, $position);

            return $this->jsonResponse($response, [
                'success' => true,
                'note' => $note->toArray()
            ], 201);

        } catch (\Exception $e) {
            $this->logger->error('Failed to add note', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update note
     * PUT /api/threads/{id}/notes/{noteId}
     */
    public function updateNote(Request $request, Response $response, array $args): Response
    {
        try {
            $threadId = (int)$args['id'];
            $noteId = (int)$args['noteId'];
            $data = $request->getParsedBody();
            
            $this->logger->info('Update note request received', [
                'thread_id' => $threadId,
                'note_id' => $noteId,
                'data' => $data
            ]);

            // Extract content and user_id
            $content = $data['content'] ?? '';
            $userId = $data['user_id'] ?? null;
            
            if (empty($content)) {
                return $this->jsonResponse($response, [
                    'error' => 'Content is required'
                ], 400);
            }

            $note = $this->threadService->updateNote($threadId, $noteId, $content, $userId);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Note updated successfully',
                'data' => $note
            ], 200);

        } catch (\Exception $e) {
            $this->logger->error('Failed to update note', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete note
     * DELETE /api/threads/{id}/notes/{noteId}
     */
    public function deleteNote(Request $request, Response $response, array $args): Response
    {
        try {
            $threadId = (int)$args['id'];
            $noteId = (int)$args['noteId'];
            
            $this->logger->info('Delete note request received', [
                'thread_id' => $threadId,
                'note_id' => $noteId
            ]);

            $this->threadService->deleteNote($threadId, $noteId);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Note deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            $this->logger->error('Failed to delete note', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign email to thread
     * POST /api/threads/{id}/emails/{emailId}/assign
     */
    public function assignEmailToThread(Request $request, Response $response, array $args): Response
    {
        try {
            $threadId = (int)$args['id'];
            $emailId = (int)$args['emailId'];

            $thread = $this->threadService->assignEmailToThread($threadId, $emailId);

            return $this->jsonResponse($response, [
                'success' => true,
                'thread' => $thread->toArray()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to assign email to thread', [
                'error' => $e->getMessage(),
                'thread_id' => $args['id'] ?? null,
                'email_id' => $args['emailId'] ?? null
            ]);
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Split thread
     * POST /api/threads/{id}/split
     */
    public function splitThread(Request $request, Response $response, array $args): Response
    {
        try {
            $threadId = (int)$args['id'];
            $data = $request->getParsedBody();

            // Validation
            if (empty($data['email_ids']) || !is_array($data['email_ids'])) {
                return $this->jsonResponse($response, [
                    'error' => 'email_ids array is required'
                ], 400);
            }

            if (empty($data['new_subject'])) {
                return $this->jsonResponse($response, [
                    'error' => 'new_subject is required'
                ], 400);
            }

            $emailIds = array_map('intval', $data['email_ids']);
            $newThread = $this->threadService->splitThread($threadId, $emailIds, $data['new_subject']);

            return $this->jsonResponse($response, [
                'success' => true,
                'new_thread' => $newThread->toArray()
            ], 201);

        } catch (\Exception $e) {
            $this->logger->error('Failed to split thread', [
                'error' => $e->getMessage(),
                'thread_id' => $args['id'] ?? null
            ]);
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Merge threads
     * POST /api/threads/{targetId}/merge
     */
    public function mergeThreads(Request $request, Response $response, array $args): Response
    {
        try {
            $targetThreadId = (int)$args['targetId'];
            $data = $request->getParsedBody();

            // Validation
            if (empty($data['source_thread_id'])) {
                return $this->jsonResponse($response, [
                    'error' => 'source_thread_id is required'
                ], 400);
            }

            $sourceThreadId = (int)$data['source_thread_id'];
            $thread = $this->threadService->mergeThreads($targetThreadId, $sourceThreadId);

            return $this->jsonResponse($response, [
                'success' => true,
                'thread' => $thread->toArray()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to merge threads', [
                'error' => $e->getMessage(),
                'target_thread_id' => $args['targetId'] ?? null
            ]);
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Move email to thread
     * PATCH /api/emails/{emailId}/thread
     */
    public function moveEmailToThread(Request $request, Response $response, array $args): Response
    {
        try {
            $emailId = (int)$args['emailId'];
            $data = $request->getParsedBody();

            // Validation
            if (empty($data['new_thread_id'])) {
                return $this->jsonResponse($response, [
                    'error' => 'new_thread_id is required'
                ], 400);
            }

            $newThreadId = (int)$data['new_thread_id'];
            $email = $this->threadService->moveEmailToThread($emailId, $newThreadId);

            return $this->jsonResponse($response, [
                'success' => true,
                'email' => $email->toArray()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to move email to thread', [
                'error' => $e->getMessage(),
                'email_id' => $args['emailId'] ?? null
            ]);
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get thread details with all related data
     * GET /api/threads/{id}/details
     */
    public function getDetails(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int)$args['id'];

            $details = $this->threadService->getThreadDetails($id);

            return $this->jsonResponse($response, $details);

        } catch (\Exception $e) {
            $this->logger->error('Failed to get thread details', [
                'error' => $e->getMessage(),
                'thread_id' => $args['id'] ?? null
            ]);
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], $e->getMessage() === 'Thread not found: ' . ($args['id'] ?? '') ? 404 : 500);
        }
    }
    
    /**
     * Bulk update threads
     * POST /api/threads/bulk/update
     */
    public function bulkUpdate(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            
            // Validation
            if (empty($data['thread_ids']) || !is_array($data['thread_ids'])) {
                return $this->jsonResponse($response, [
                    'error' => 'thread_ids array is required'
                ], 400);
            }
            
            if (empty($data['updates']) || !is_array($data['updates'])) {
                return $this->jsonResponse($response, [
                    'error' => 'updates object is required'
                ], 400);
            }
            
            $result = $this->bulkService->bulkUpdate($data['thread_ids'], $data['updates']);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'result' => $result
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Bulk update failed', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Bulk delete threads
     * POST /api/threads/bulk/delete
     */
    public function bulkDelete(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            
            // Validation
            if (empty($data['thread_ids']) || !is_array($data['thread_ids'])) {
                return $this->jsonResponse($response, [
                    'error' => 'thread_ids array is required'
                ], 400);
            }
            
            $result = $this->bulkService->bulkDelete($data['thread_ids']);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'result' => $result
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Bulk delete failed', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Bulk assign threads to user
     * POST /api/threads/bulk/assign
     */
    public function bulkAssign(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            
            // Validation
            if (empty($data['thread_ids']) || !is_array($data['thread_ids'])) {
                return $this->jsonResponse($response, [
                    'error' => 'thread_ids array is required'
                ], 400);
            }
            
            $userId = isset($data['user_id']) ? (int)$data['user_id'] : null;
            
            $result = $this->bulkService->bulkAssign($data['thread_ids'], $userId);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'result' => $result
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Bulk assign failed', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Bulk set status
     * POST /api/threads/bulk/status
     */
    public function bulkSetStatus(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            
            // Validation
            if (empty($data['thread_ids']) || !is_array($data['thread_ids'])) {
                return $this->jsonResponse($response, [
                    'error' => 'thread_ids array is required'
                ], 400);
            }
            
            if (empty($data['status'])) {
                return $this->jsonResponse($response, [
                    'error' => 'status is required'
                ], 400);
            }
            
            $result = $this->bulkService->bulkSetStatus($data['thread_ids'], $data['status']);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'result' => $result
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Bulk set status failed', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Bulk add label
     * POST /api/threads/bulk/labels/add
     */
    public function bulkAddLabel(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            
            // Validation
            if (empty($data['thread_ids']) || !is_array($data['thread_ids'])) {
                return $this->jsonResponse($response, [
                    'error' => 'thread_ids array is required'
                ], 400);
            }
            
            if (empty($data['label_id'])) {
                return $this->jsonResponse($response, [
                    'error' => 'label_id is required'
                ], 400);
            }
            
            $result = $this->bulkService->bulkAddLabel($data['thread_ids'], (int)$data['label_id']);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'result' => $result
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Bulk add label failed', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Bulk remove label
     * POST /api/threads/bulk/labels/remove
     */
    public function bulkRemoveLabel(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();
            
            // Validation
            if (empty($data['thread_ids']) || !is_array($data['thread_ids'])) {
                return $this->jsonResponse($response, [
                    'error' => 'thread_ids array is required'
                ], 400);
            }
            
            if (empty($data['label_id'])) {
                return $this->jsonResponse($response, [
                    'error' => 'label_id is required'
                ], 400);
            }
            
            $result = $this->bulkService->bulkRemoveLabel($data['thread_ids'], (int)$data['label_id']);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'result' => $result
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Bulk remove label failed', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark thread as read
     * POST /api/threads/{id}/mark-read
     */
    public function markAsRead(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int)$args['id'];
            
            $this->threadService->markAsRead($id);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Thread marked as read'
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to mark thread as read', [
                'thread_id' => $id,
                'error' => $e->getMessage()
            ]);
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark thread as unread
     * POST /api/threads/{id}/mark-unread
     */
    public function markAsUnread(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int)$args['id'];
            
            $this->threadService->markAsUnread($id);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Thread marked as unread'
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to mark thread as unread', [
                'thread_id' => $id,
                'error' => $e->getMessage()
            ]);
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign thread to users
     * POST /api/threads/{id}/assign
     */
    public function assignUsers(Request $request, Response $response, array $args): Response
    {
        try {
            $id = (int)$args['id'];
            $data = $request->getParsedBody();
            
            // Validation
            if (!isset($data['user_ids']) || !is_array($data['user_ids'])) {
                return $this->jsonResponse($response, [
                    'error' => 'user_ids array is required'
                ], 400);
            }
            
            // Sanitize user IDs - ensure all are integers to prevent SQL injection
            $userIds = array_map('intval', $data['user_ids']);
            $userIds = array_filter($userIds, function($id) { return $id > 0; });
            
            // Load thread via repository (avoiding direct model access)
            $thread = $this->threadApiService->getThread($id);
            if (!$thread || !isset($thread['thread'])) {
                return $this->jsonResponse($response, [
                    'error' => 'Thread not found'
                ], 404);
            }
            
            // Get the actual thread model for assignment
            $threadModel = \CiInbox\App\Models\Thread::find($id);
            if (!$threadModel) {
                return $this->jsonResponse($response, [
                    'error' => 'Thread not found'
                ], 404);
            }
            
            // Sync assignments (replaces all existing with new list)
            $threadModel->assignedUsers()->sync($userIds);
            
            // Trigger status update via service (injected via constructor in future)
            $statusService = \CiInbox\Core\Container::getInstance()->get(\CiInbox\App\Services\ThreadStatusService::class);
            $statusService->updateStatusOnAssignment($threadModel);
            
            $this->logger->info("Thread #{$id} assigned to users", [
                'thread_id' => $id,
                'user_ids' => $userIds
            ]);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Thread assignments updated',
                'assigned_users' => $threadModel->fresh()->assignedUsers
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to assign thread', [
                'thread_id' => $id,
                'error' => $e->getMessage()
            ]);
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper: JSON response
     */
    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}

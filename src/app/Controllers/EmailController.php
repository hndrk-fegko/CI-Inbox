<?php

declare(strict_types=1);

namespace CiInbox\App\Controllers;

use CiInbox\App\Services\EmailSendService;
use CiInbox\Modules\Logger\LoggerService;
use CiInbox\App\Repositories\EmailRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Email Send API Controller
 */
class EmailController
{
    public function __construct(
        private EmailSendService $emailSendService,
        private LoggerService $logger,
        private EmailRepositoryInterface $emailRepository
    ) {}

    /**
     * Send new email
     * POST /api/emails/send
     */
    public function send(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();

            // Validation
            if (empty($data['subject'])) {
                return $this->jsonResponse($response, [
                    'error' => 'Subject is required'
                ], 400);
            }

            if (empty($data['to'])) {
                return $this->jsonResponse($response, [
                    'error' => 'Recipients required'
                ], 400);
            }

            if (empty($data['body_text']) && empty($data['body_html'])) {
                return $this->jsonResponse($response, [
                    'error' => 'Body is required'
                ], 400);
            }

            if (empty($data['imap_account_id'])) {
                return $this->jsonResponse($response, [
                    'error' => 'IMAP account ID required'
                ], 400);
            }

            $email = $this->emailSendService->sendEmail($data);

            $this->logger->info('Email send service completed', [
                'email_id' => $email->id,
                'thread_id' => $email->thread_id
            ]);

            return $this->jsonResponse($response, [
                'success' => true,
                'email' => $email->toArray()
            ], 201);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to send email', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reply to thread
     * POST /api/threads/{id}/reply
     */
    public function reply(Request $request, Response $response, array $args): Response
    {
        try {
            $threadId = (int)$args['id'];
            $data = $request->getParsedBody();

            // Validation
            if (empty($data['body'])) {
                return $this->jsonResponse($response, [
                    'error' => 'Body is required'
                ], 400);
            }

            $imapAccountId = $data['imap_account_id'] ?? 4;

            $email = $this->emailSendService->replyToThread(
                $threadId,
                $data['body'],
                $imapAccountId
            );

            return $this->jsonResponse($response, [
                'success' => true,
                'email' => $email->toArray()
            ], 201);

        } catch (\Exception $e) {
            $this->logger->error('Failed to reply to thread', [
                'error' => $e->getMessage(),
                'thread_id' => $args['id'] ?? null
            ]);
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Forward thread
     * POST /api/threads/{id}/forward
     */
    public function forward(Request $request, Response $response, array $args): Response
    {
        try {
            $threadId = (int)$args['id'];
            $data = $request->getParsedBody();

            // Validation
            if (empty($data['recipients'])) {
                return $this->jsonResponse($response, [
                    'error' => 'Recipients required'
                ], 400);
            }

            $imapAccountId = $data['imap_account_id'] ?? 4;

            $email = $this->emailSendService->forwardThread(
                $threadId,
                $data['recipients'],
                $data['note'] ?? null,
                $imapAccountId
            );

            return $this->jsonResponse($response, [
                'success' => true,
                'email' => $email->toArray()
            ], 201);

        } catch (\Exception $e) {
            $this->logger->error('Failed to forward thread', [
                'error' => $e->getMessage(),
                'thread_id' => $args['id'] ?? null
            ]);
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark email as read
     * POST /api/emails/{id}/read
     */
    public function markAsRead(Request $request, Response $response, array $args): Response
    {
        try {
            $emailId = (int)$args['id'];
            
            $email = $this->emailRepository->findById($emailId);
            
            if (!$email) {
                return $this->jsonResponse($response, [
                    'error' => 'Email not found'
                ], 404);
            }
            
            // Update is_read flag
            $email->is_read = true;
            $email->save();
            
            $this->logger->info('Email marked as read', ['email_id' => $emailId]);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'email' => $email->toArray()
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to mark email as read', [
                'error' => $e->getMessage(),
                'email_id' => $args['id'] ?? null
            ]);
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark email as unread
     * POST /api/emails/{id}/unread
     */
    public function markAsUnread(Request $request, Response $response, array $args): Response
    {
        try {
            $emailId = (int)$args['id'];
            
            $email = $this->emailRepository->findById($emailId);
            
            if (!$email) {
                return $this->jsonResponse($response, [
                    'error' => 'Email not found'
                ], 404);
            }
            
            // Update is_read flag
            $email->is_read = false;
            $email->save();
            
            $this->logger->info('Email marked as unread', ['email_id' => $emailId]);
            
            return $this->jsonResponse($response, [
                'success' => true,
                'email' => $email->toArray()
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to mark email as unread', [
                'error' => $e->getMessage(),
                'email_id' => $args['id'] ?? null
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

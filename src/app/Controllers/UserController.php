<?php

declare(strict_types=1);

namespace CiInbox\App\Controllers;

use CiInbox\App\Services\UserService;
use CiInbox\Modules\Logger\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * User Controller
 * 
 * Handles HTTP requests for user management (CRUD operations)
 */
class UserController
{
    public function __construct(
        private UserService $userService,
        private LoggerInterface $logger
    ) {}

    /**
     * List all users
     * GET /api/users
     */
    public function index(Request $request, Response $response): Response
    {
        try {
            $queryParams = $request->getQueryParams();

            $filters = [];
            if (isset($queryParams['role'])) {
                $filters['role'] = $queryParams['role'];
            }
            if (isset($queryParams['is_active'])) {
                $filters['is_active'] = filter_var($queryParams['is_active'], FILTER_VALIDATE_BOOLEAN);
            }

            $limit = isset($queryParams['limit']) ? (int)$queryParams['limit'] : 50;
            $offset = isset($queryParams['offset']) ? (int)$queryParams['offset'] : 0;

            $result = $this->userService->getAllUsers($filters, $limit, $offset);

            // Transform users to API format (hide password_hash)
            $users = $result['users']->map(function ($user) {
                return [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                    'last_login_at' => $user->last_login_at?->toIso8601String(),
                    'created_at' => $user->created_at->toIso8601String(),
                    'updated_at' => $user->updated_at->toIso8601String()
                ];
            })->all();

            return $this->jsonResponse($response, [
                'users' => $users,
                'meta' => [
                    'total' => $result['total'],
                    'limit' => $limit,
                    'offset' => $offset
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to list users', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, [
                'error' => 'Failed to fetch users'
            ], 500);
        }
    }

    /**
     * Get single user
     * GET /api/users/{id}
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        try {
            $userId = (int)$args['id'];
            
            $user = $this->userService->getUserById($userId);

            if (!$user) {
                return $this->jsonResponse($response, [
                    'error' => 'User not found'
                ], 404);
            }

            return $this->jsonResponse($response, [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                    'last_login_at' => $user->last_login_at?->toIso8601String(),
                    'created_at' => $user->created_at->toIso8601String(),
                    'updated_at' => $user->updated_at->toIso8601String()
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to get user', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, [
                'error' => 'Failed to fetch user'
            ], 500);
        }
    }

    /**
     * Create new user
     * POST /api/users
     */
    public function create(Request $request, Response $response): Response
    {
        try {
            $data = $request->getParsedBody();

            // Validation
            if (empty($data['email'])) {
                return $this->jsonResponse($response, [
                    'error' => 'Email is required'
                ], 400);
            }

            if (empty($data['password'])) {
                return $this->jsonResponse($response, [
                    'error' => 'Password is required'
                ], 400);
            }

            $user = $this->userService->createUser($data);

            return $this->jsonResponse($response, [
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at->toIso8601String()
                ]
            ], 201);

        } catch (\Exception $e) {
            $this->logger->error('Failed to create user', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update user
     * PUT /api/users/{id}
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        try {
            $userId = (int)$args['id'];
            $data = $request->getParsedBody();

            if (empty($data)) {
                return $this->jsonResponse($response, [
                    'error' => 'No update data provided'
                ], 400);
            }

            $user = $this->userService->updateUser($userId, $data);

            return $this->jsonResponse($response, [
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                    'updated_at' => $user->updated_at->toIso8601String()
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to update user', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], $e->getMessage() === "User not found: {$args['id']}" ? 404 : 400);
        }
    }

    /**
     * Delete user
     * DELETE /api/users/{id}
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        try {
            $userId = (int)$args['id'];

            $this->userService->deleteUser($userId);

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'User deleted successfully'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to delete user', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], str_contains($e->getMessage(), 'not found') ? 404 : 400);
        }
    }

    /**
     * Change password
     * POST /api/users/{id}/password
     */
    public function changePassword(Request $request, Response $response, array $args): Response
    {
        try {
            $userId = (int)$args['id'];
            $data = $request->getParsedBody();

            // Validation
            if (empty($data['current_password'])) {
                return $this->jsonResponse($response, [
                    'error' => 'Current password is required'
                ], 400);
            }

            if (empty($data['new_password'])) {
                return $this->jsonResponse($response, [
                    'error' => 'New password is required'
                ], 400);
            }

            if (empty($data['confirm_password'])) {
                return $this->jsonResponse($response, [
                    'error' => 'Password confirmation is required'
                ], 400);
            }

            if ($data['new_password'] !== $data['confirm_password']) {
                return $this->jsonResponse($response, [
                    'error' => 'Password confirmation does not match'
                ], 400);
            }

            $this->userService->changePassword(
                $userId,
                $data['current_password'],
                $data['new_password']
            );

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Password changed successfully'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to change password', ['error' => $e->getMessage()]);
            return $this->jsonResponse($response, [
                'error' => $e->getMessage()
            ], str_contains($e->getMessage(), 'not found') ? 404 : 400);
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

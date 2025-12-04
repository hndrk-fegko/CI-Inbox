<?php

declare(strict_types=1);

namespace CiInbox\App\Services;

use CiInbox\App\Models\User;
use CiInbox\Modules\Logger\LoggerInterface;
use Illuminate\Support\Collection;

/**
 * User Management Service
 * 
 * Handles user CRUD operations and password management.
 * Architecture Layer: Service Layer (Business Logic)
 */
class UserService
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    /**
     * Get all users with optional filters
     * 
     * @param array $filters ['role' => 'admin', 'is_active' => true]
     * @param int $limit
     * @param int $offset
     * @return array ['users' => Collection, 'total' => int]
     */
    public function getAllUsers(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $this->logger->debug('Fetching users', [
            'filters' => $filters,
            'limit' => $limit,
            'offset' => $offset
        ]);

        $query = User::query();

        // Apply filters
        if (isset($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool)$filters['is_active']);
        }

        // Get total before pagination
        $total = $query->count();

        // Apply pagination
        $users = $query
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->offset($offset)
            ->get();

        $this->logger->info('Users fetched', [
            'count' => $users->count(),
            'total' => $total
        ]);

        return [
            'users' => $users,
            'total' => $total
        ];
    }

    /**
     * Get user by ID
     * 
     * @param int $userId
     * @return User|null
     */
    public function getUserById(int $userId): ?User
    {
        $this->logger->debug('Fetching user by ID', ['user_id' => $userId]);
        
        $user = User::find($userId);
        
        if ($user) {
            $this->logger->debug('User found', ['user_id' => $userId]);
        } else {
            $this->logger->warning('User not found', ['user_id' => $userId]);
        }
        
        return $user;
    }

    /**
     * Get user by email
     * 
     * @param string $email
     * @return User|null
     */
    public function getUserByEmail(string $email): ?User
    {
        $this->logger->debug('Fetching user by email', ['email' => $email]);
        
        return User::where('email', $email)->first();
    }

    /**
     * Create new user
     * 
     * @param array $data
     * @return User
     * @throws \Exception
     */
    public function createUser(array $data): User
    {
        $this->logger->info('Creating user', [
            'email' => $data['email'] ?? 'unknown',
            'role' => $data['role'] ?? 'user'
        ]);

        // Validate required fields
        if (empty($data['email']) || empty($data['password'])) {
            throw new \Exception('Email and password are required');
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Invalid email format');
        }

        // Check if user already exists
        $existingUser = $this->getUserByEmail($data['email']);
        if ($existingUser) {
            throw new \Exception('User with this email already exists');
        }

        // Validate password strength
        if (strlen($data['password']) < 8) {
            throw new \Exception('Password must be at least 8 characters long');
        }

        // Create user
        $user = new User();
        $user->email = $data['email'];
        $user->password_hash = password_hash($data['password'], PASSWORD_BCRYPT);
        $user->name = $data['name'] ?? explode('@', $data['email'])[0];
        $user->role = $data['role'] ?? 'user';
        $user->is_active = $data['is_active'] ?? true;
        $user->save();

        $this->logger->info('User created', [
            'user_id' => $user->id,
            'email' => $user->email
        ]);

        return $user;
    }

    /**
     * Update user
     * 
     * @param int $userId
     * @param array $data
     * @return User
     * @throws \Exception
     */
    public function updateUser(int $userId, array $data): User
    {
        $this->logger->info('Updating user', [
            'user_id' => $userId,
            'fields' => array_keys($data)
        ]);

        $user = $this->getUserById($userId);
        if (!$user) {
            throw new \Exception("User not found: {$userId}");
        }

        // Update fields
        if (isset($data['email'])) {
            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Invalid email format');
            }

            // Check if email is already taken by another user
            $existingUser = User::where('email', $data['email'])
                ->where('id', '!=', $userId)
                ->first();
            
            if ($existingUser) {
                throw new \Exception('Email already taken by another user');
            }

            $user->email = $data['email'];
        }

        if (isset($data['name'])) {
            $user->name = $data['name'];
        }

        if (isset($data['role'])) {
            // Validate role
            if (!in_array($data['role'], ['user', 'admin'])) {
                throw new \Exception('Invalid role. Must be "user" or "admin"');
            }
            $user->role = $data['role'];
        }

        if (isset($data['is_active'])) {
            $user->is_active = (bool)$data['is_active'];
        }

        $user->save();

        $this->logger->info('User updated', [
            'user_id' => $userId
        ]);

        return $user;
    }

    /**
     * Delete user
     * 
     * @param int $userId
     * @return bool
     * @throws \Exception
     */
    public function deleteUser(int $userId): bool
    {
        $this->logger->info('Deleting user', ['user_id' => $userId]);

        $user = $this->getUserById($userId);
        if (!$user) {
            throw new \Exception("User not found: {$userId}");
        }

        // Prevent deleting the last admin
        if ($user->role === 'admin') {
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                throw new \Exception('Cannot delete the last admin user');
            }
        }

        $email = $user->email;
        $user->delete();

        $this->logger->info('User deleted', [
            'user_id' => $userId,
            'email' => $email
        ]);

        return true;
    }

    /**
     * Change user password
     * 
     * @param int $userId
     * @param string $currentPassword
     * @param string $newPassword
     * @return bool
     * @throws \Exception
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $this->logger->info('Changing password', ['user_id' => $userId]);

        $user = $this->getUserById($userId);
        if (!$user) {
            throw new \Exception("User not found: {$userId}");
        }

        // Verify current password
        if (!password_verify($currentPassword, $user->password_hash)) {
            throw new \Exception('Current password is incorrect');
        }

        // Validate new password strength
        if (strlen($newPassword) < 8) {
            throw new \Exception('New password must be at least 8 characters long');
        }

        // Update password
        $user->password_hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $user->save();

        $this->logger->info('Password changed', ['user_id' => $userId]);

        return true;
    }

    /**
     * Authenticate user (for API login)
     * 
     * @param string $email
     * @param string $password
     * @return User|null
     */
    public function authenticate(string $email, string $password): ?User
    {
        $this->logger->debug('Authenticating user', ['email' => $email]);

        $user = $this->getUserByEmail($email);
        
        if (!$user) {
            $this->logger->warning('Authentication failed - user not found', ['email' => $email]);
            return null;
        }

        if (!$user->is_active) {
            $this->logger->warning('Authentication failed - user inactive', ['email' => $email]);
            return null;
        }

        if (!password_verify($password, $user->password_hash)) {
            $this->logger->warning('Authentication failed - invalid password', ['email' => $email]);
            return null;
        }

        // Update last login
        $user->last_login_at = now();
        $user->save();

        $this->logger->info('User authenticated successfully', [
            'user_id' => $user->id,
            'email' => $email
        ]);

        return $user;
    }
}

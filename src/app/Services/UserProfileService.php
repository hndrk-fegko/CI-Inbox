<?php

declare(strict_types=1);

namespace CiInbox\App\Services;

use CiInbox\App\Models\User;
use CiInbox\Modules\Encryption\EncryptionService;
use CiInbox\Modules\Logger\LoggerService;
use Exception;

/**
 * User Profile Service
 * 
 * Handles user profile operations (name, email, avatar, timezone, language)
 */
class UserProfileService
{
    public function __construct(
        private LoggerService $logger,
        private EncryptionService $encryption
    ) {}
    
    /**
     * Get user profile
     */
    public function getProfile(int $userId): ?User
    {
        try {
            return User::find($userId);
        } catch (Exception $e) {
            $this->logger->error('Failed to get user profile', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Update user profile
     */
    public function updateProfile(int $userId, array $data): User
    {
        try {
            $user = User::findOrFail($userId);
            
            // Update allowed fields
            if (isset($data['name'])) {
                $user->name = $data['name'];
            }
            
            if (isset($data['email'])) {
                // Check if email is already taken by another user
                $existing = User::where('email', $data['email'])
                    ->where('id', '!=', $userId)
                    ->first();
                    
                if ($existing) {
                    throw new Exception('Email address already in use');
                }
                
                $user->email = $data['email'];
            }
            
            if (isset($data['timezone'])) {
                $user->timezone = $data['timezone'];
            }
            
            if (isset($data['language'])) {
                $user->language = $data['language'];
            }
            
            $user->save();
            
            $this->logger->info('User profile updated', [
                'user_id' => $userId,
                'updated_fields' => array_keys($data)
            ]);
            
            return $user;
            
        } catch (Exception $e) {
            $this->logger->error('Failed to update user profile', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Upload and save user avatar
     */
    public function uploadAvatar(int $userId, array $file): string
    {
        try {
            $user = User::findOrFail($userId);
            
            // Validate file
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('Invalid file type. Allowed: JPG, PNG, GIF');
            }
            
            // Max 2MB
            if ($file['size'] > 2 * 1024 * 1024) {
                throw new Exception('File too large. Maximum: 2MB');
            }
            
            // Create user avatar directory
            $avatarDir = __DIR__ . '/../../data/uploads/avatars/' . $userId;
            if (!is_dir($avatarDir)) {
                mkdir($avatarDir, 0755, true);
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'avatar_' . time() . '_' . uniqid() . '.' . $extension;
            $avatarPath = $avatarDir . '/' . $filename;
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $avatarPath)) {
                throw new Exception('Failed to save uploaded file');
            }
            
            // Delete old avatar if exists
            if ($user->avatar_path && file_exists($user->avatar_path)) {
                @unlink($user->avatar_path);
            }
            
            // Update user avatar path
            $user->avatar_path = $avatarPath;
            $user->save();
            
            $this->logger->info('Avatar uploaded', [
                'user_id' => $userId,
                'filename' => $filename
            ]);
            
            return $avatarPath;
            
        } catch (Exception $e) {
            $this->logger->error('Failed to upload avatar', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Delete user avatar
     */
    public function deleteAvatar(int $userId): bool
    {
        try {
            $user = User::findOrFail($userId);
            
            if ($user->avatar_path && file_exists($user->avatar_path)) {
                @unlink($user->avatar_path);
            }
            
            $user->avatar_path = null;
            $user->save();
            
            $this->logger->info('Avatar deleted', [
                'user_id' => $userId
            ]);
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Failed to delete avatar', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Change user password
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        try {
            $user = User::findOrFail($userId);
            
            // Verify current password
            if (!password_verify($currentPassword, $user->password)) {
                throw new Exception('Current password is incorrect');
            }
            
            // Hash new password
            $user->password = password_hash($newPassword, PASSWORD_DEFAULT);
            $user->save();
            
            $this->logger->info('Password changed', [
                'user_id' => $userId
            ]);
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->error('Failed to change password', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Get avatar URL for display
     */
    public function getAvatarUrl(int $userId): ?string
    {
        $user = User::find($userId);
        
        if (!$user || !$user->avatar_path) {
            return null;
        }
        
        // Convert absolute path to web-accessible URL
        $relativePath = str_replace(__DIR__ . '/../../data/uploads/', '/uploads/', $user->avatar_path);
        
        return $relativePath;
    }
}

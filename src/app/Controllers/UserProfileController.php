<?php

declare(strict_types=1);

namespace CiInbox\App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use CiInbox\App\Services\UserProfileService;
use CiInbox\Modules\Logger\LoggerService;
use Exception;

/**
 * User Profile Controller
 * 
 * Handles user profile operations via API
 */
class UserProfileController
{
    public function __construct(
        private UserProfileService $profileService,
        private LoggerService $logger
    ) {}
    
    /**
     * GET /api/user/profile
     * Get current user profile
     */
    public function getProfile(Request $request, Response $response): Response
    {
        try {
            // TODO: Get user ID from JWT token
            $userId = $request->getAttribute('user_id') ?? 1; // Temporary hardcoded
            
            $user = $this->profileService->getProfile($userId);
            
            if (!$user) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'User not found'
                ]));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }
            
            // Remove sensitive data
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $this->profileService->getAvatarUrl($userId),
                'avatar_color' => $user->avatar_color,
                'timezone' => $user->timezone,
                'language' => $user->language,
                'theme_mode' => $user->theme_mode ?? 'auto',
                'created_at' => $user->created_at->toIso8601String()
            ];
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $userData
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (Exception $e) {
            $this->logger->error('Get profile failed', [
                'error' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Failed to get profile: ' . $e->getMessage()
            ]));
            
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
    
    /**
     * PUT /api/user/profile
     * Update user profile
     */
    public function updateProfile(Request $request, Response $response): Response
    {
        try {
            // TODO: Get user ID from JWT token
            $userId = $request->getAttribute('user_id') ?? 1; // Temporary hardcoded
            
            $data = $request->getParsedBody();
            
            // Validate input
            $allowedFields = ['name', 'email', 'timezone', 'language', 'avatar_color'];
            $updateData = [];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            // Validate avatar_color range (1-8)
            if (isset($updateData['avatar_color'])) {
                $color = (int)$updateData['avatar_color'];
                if ($color < 1 || $color > 8) {
                    $response->getBody()->write(json_encode([
                        'success' => false,
                        'error' => 'Avatar color must be between 1 and 8'
                    ]));
                    return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
                }
                $updateData['avatar_color'] = $color;
            }
            
            if (empty($updateData)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'No valid fields to update'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
            
            // Validate email format
            if (isset($updateData['email']) && !filter_var($updateData['email'], FILTER_VALIDATE_EMAIL)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Invalid email format'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
            
            $user = $this->profileService->updateProfile($userId, $updateData);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'timezone' => $user->timezone,
                    'language' => $user->language
                ]
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (Exception $e) {
            $this->logger->error('Update profile failed', [
                'error' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Failed to update profile: ' . $e->getMessage()
            ]));
            
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
    
    /**
     * POST /api/user/profile/avatar
     * Upload user avatar
     */
    public function uploadAvatar(Request $request, Response $response): Response
    {
        try {
            // TODO: Get user ID from JWT token
            $userId = $request->getAttribute('user_id') ?? 1; // Temporary hardcoded
            
            $uploadedFiles = $request->getUploadedFiles();
            
            if (!isset($uploadedFiles['avatar'])) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'No file uploaded'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
            
            $file = $uploadedFiles['avatar'];
            
            // Convert PSR-7 UploadedFile to array for service
            $fileArray = [
                'name' => $file->getClientFilename(),
                'type' => $file->getClientMediaType(),
                'size' => $file->getSize(),
                'tmp_name' => $file->getStream()->getMetadata('uri')
            ];
            
            $avatarPath = $this->profileService->uploadAvatar($userId, $fileArray);
            $avatarUrl = $this->profileService->getAvatarUrl($userId);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Avatar uploaded successfully',
                'data' => [
                    'avatar_url' => $avatarUrl
                ]
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (Exception $e) {
            $this->logger->error('Upload avatar failed', [
                'error' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Failed to upload avatar: ' . $e->getMessage()
            ]));
            
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
    
    /**
     * DELETE /api/user/profile/avatar
     * Delete user avatar
     */
    public function deleteAvatar(Request $request, Response $response): Response
    {
        try {
            // TODO: Get user ID from JWT token
            $userId = $request->getAttribute('user_id') ?? 1; // Temporary hardcoded
            
            $this->profileService->deleteAvatar($userId);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Avatar deleted successfully'
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (Exception $e) {
            $this->logger->error('Delete avatar failed', [
                'error' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Failed to delete avatar: ' . $e->getMessage()
            ]));
            
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
    
    /**
     * POST /api/user/profile/change-password
     * Change user password
     */
    public function changePassword(Request $request, Response $response): Response
    {
        try {
            // TODO: Get user ID from JWT token
            $userId = $request->getAttribute('user_id') ?? 1; // Temporary hardcoded
            
            $data = $request->getParsedBody();
            
            // Validate input
            if (!isset($data['current_password']) || !isset($data['new_password'])) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Current password and new password required'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
            
            // Validate new password length
            if (strlen($data['new_password']) < 8) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'New password must be at least 8 characters'
                ]));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
            
            $this->profileService->changePassword(
                $userId,
                $data['current_password'],
                $data['new_password']
            );
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Password changed successfully'
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (Exception $e) {
            $this->logger->error('Change password failed', [
                'error' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]));
            
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    }
}

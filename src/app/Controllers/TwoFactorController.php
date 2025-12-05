<?php

declare(strict_types=1);

namespace CiInbox\App\Controllers;

use CiInbox\App\Services\TwoFactorAuthService;
use CiInbox\App\Models\User;
use CiInbox\Modules\Logger\LoggerService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Two-Factor Authentication Controller
 * 
 * Handles 2FA setup, verification, and management.
 */
class TwoFactorController
{
    private TwoFactorAuthService $twoFactorService;
    private LoggerService $logger;

    public function __construct(
        TwoFactorAuthService $twoFactorService,
        LoggerService $logger
    ) {
        $this->twoFactorService = $twoFactorService;
        $this->logger = $logger;
    }

    /**
     * GET /api/user/2fa/status
     * Check if 2FA is enabled for current user
     */
    public function status(Request $request, Response $response): Response
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Not authenticated'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(401);
            }
            
            $user = User::find($userId);
            
            if (!$user) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'User not found'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => [
                    'enabled' => $this->twoFactorService->isEnabled($user),
                    'verified_at' => $user->totp_verified_at?->toIso8601String(),
                    'backup_codes_remaining' => $this->twoFactorService->getBackupCodeCount($user)
                ]
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('Error checking 2FA status', [
                'error' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Internal server error'
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * POST /api/user/2fa/setup
     * Initialize 2FA setup - generates secret and QR code
     */
    public function setup(Request $request, Response $response): Response
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Not authenticated'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(401);
            }
            
            $user = User::find($userId);
            
            if (!$user) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'User not found'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }
            
            // Check if already enabled
            if ($this->twoFactorService->isEnabled($user)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => '2FA is already enabled. Disable it first to reconfigure.'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
            }
            
            // Generate secret and backup codes
            $setupData = $this->twoFactorService->generateSecret($user);
            
            // Store in session for verification step
            $_SESSION['2fa_setup'] = [
                'secret' => $setupData['secret'],
                'backup_codes_hashed' => $setupData['backup_codes_hashed'],
                'expires' => time() + 600 // 10 minutes
            ];
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => [
                    'qr_uri' => $setupData['qr_uri'],
                    'secret' => $setupData['secret'], // For manual entry
                    'backup_codes' => $setupData['backup_codes'] // Show once!
                ]
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('Error setting up 2FA', [
                'error' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Internal server error'
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * POST /api/user/2fa/enable
     * Enable 2FA after verifying code
     */
    public function enable(Request $request, Response $response): Response
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Not authenticated'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(401);
            }
            
            // Check setup session
            $setupData = $_SESSION['2fa_setup'] ?? null;
            
            if (!$setupData || time() > ($setupData['expires'] ?? 0)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Setup session expired. Please start again.'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
            }
            
            $user = User::find($userId);
            $body = json_decode($request->getBody()->getContents(), true);
            $code = $body['code'] ?? '';
            
            if (empty($code)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Verification code required'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
            }
            
            // Enable 2FA
            $enabled = $this->twoFactorService->enable(
                $user,
                $setupData['secret'],
                $setupData['backup_codes_hashed'],
                $code
            );
            
            if (!$enabled) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Invalid verification code. Please try again.'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
            }
            
            // Clear setup session
            unset($_SESSION['2fa_setup']);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Two-factor authentication enabled successfully'
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('Error enabling 2FA', [
                'error' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Internal server error'
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * POST /api/user/2fa/disable
     * Disable 2FA (requires current code)
     */
    public function disable(Request $request, Response $response): Response
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Not authenticated'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(401);
            }
            
            $user = User::find($userId);
            $body = json_decode($request->getBody()->getContents(), true);
            $code = $body['code'] ?? '';
            
            if (empty($code)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Verification code required'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
            }
            
            $disabled = $this->twoFactorService->disable($user, $code);
            
            if (!$disabled) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Invalid verification code'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
            }
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Two-factor authentication disabled'
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('Error disabling 2FA', [
                'error' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Internal server error'
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * POST /api/user/2fa/verify
     * Verify 2FA code (for login or sensitive operations)
     */
    public function verify(Request $request, Response $response): Response
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Not authenticated'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(401);
            }
            
            $user = User::find($userId);
            $body = json_decode($request->getBody()->getContents(), true);
            $code = $body['code'] ?? '';
            
            if (empty($code)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Verification code required'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
            }
            
            $verified = $this->twoFactorService->verify($user, $code);
            
            if (!$verified) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Invalid verification code'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
            }
            
            // Mark session as 2FA verified
            $_SESSION['2fa_verified'] = true;
            $_SESSION['2fa_verified_at'] = time();
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Verification successful'
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('Error verifying 2FA', [
                'error' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Internal server error'
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    /**
     * POST /api/user/2fa/backup-codes/regenerate
     * Regenerate backup codes (requires current code)
     */
    public function regenerateBackupCodes(Request $request, Response $response): Response
    {
        try {
            $userId = $_SESSION['user_id'] ?? null;
            
            if (!$userId) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Not authenticated'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(401);
            }
            
            $user = User::find($userId);
            $body = json_decode($request->getBody()->getContents(), true);
            $code = $body['code'] ?? '';
            
            // Verify current code first
            if (!$this->twoFactorService->verify($user, $code)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Invalid verification code'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
            }
            
            $newCodes = $this->twoFactorService->regenerateBackupCodes($user);
            
            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => [
                    'backup_codes' => $newCodes
                ]
            ]));
            
            return $response->withHeader('Content-Type', 'application/json');
            
        } catch (\Exception $e) {
            $this->logger->error('Error regenerating backup codes', [
                'error' => $e->getMessage()
            ]);
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Internal server error'
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}

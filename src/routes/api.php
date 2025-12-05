<?php

/**
 * API Routes
 * 
 * RESTful API endpoints.
 * 
 * Security Features:
 * - AuthMiddleware for authenticated routes
 * - AdminMiddleware for admin-only routes
 * - RateLimitMiddleware for abuse prevention
 * - Input sanitization in controllers
 */

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use CiInbox\Core\Container;
use CiInbox\Modules\Logger\LoggerInterface;
use CiInbox\App\Controllers\ImapController;
use CiInbox\App\Controllers\ThreadController;
use CiInbox\App\Controllers\EmailController;
use CiInbox\App\Controllers\WebhookController;
use CiInbox\App\Controllers\LabelController;
use CiInbox\App\Controllers\UserController;
use CiInbox\App\Controllers\PersonalImapAccountController;
use CiInbox\App\Controllers\UserProfileController;
use CiInbox\Modules\Theme\ThemeService;
use CiInbox\App\Middleware\AuthMiddleware;
use CiInbox\App\Middleware\AdminMiddleware;
use CiInbox\App\Middleware\RateLimitMiddleware;

return function (App $app) {
    
    // Get container and middleware instances
    $container = Container::getInstance();
    $authMiddleware = $container->get(AuthMiddleware::class);
    $adminMiddleware = $container->get(AdminMiddleware::class);
    $rateLimitMiddleware = $container->get(RateLimitMiddleware::class);
    
    // Thread Bulk Operations (MUST be before /api/threads to avoid routing conflicts!)
    $app->group('/api/threads/bulk', function ($app) {
        // Bulk update
        $app->post('/update', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(ThreadController::class);
            return $controller->bulkUpdate($request, $response);
        });
        
        // Bulk delete
        $app->post('/delete', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(ThreadController::class);
            return $controller->bulkDelete($request, $response);
        });
        
        // Bulk assign
        $app->post('/assign', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(ThreadController::class);
            return $controller->bulkAssign($request, $response);
        });
        
        // Bulk set status
        $app->post('/status', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(ThreadController::class);
            return $controller->bulkSetStatus($request, $response);
        });
        
        // Bulk add label
        $app->post('/labels/add', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(ThreadController::class);
            return $controller->bulkAddLabel($request, $response);
        });
        
        // Bulk remove label
        $app->post('/labels/remove', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(ThreadController::class);
            return $controller->bulkRemoveLabel($request, $response);
        });
    });
    
    // Thread API Routes
    $app->group('/api/threads', function ($app) {
        // Basic CRUD
        $app->post('', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(ThreadController::class);
            return $controller->create($request, $response);
        });
        
        $app->get('', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(ThreadController::class);
            return $controller->list($request, $response);
        });
        
        $app->get('/{id}', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(ThreadController::class);
            return $controller->get($request, $response, $args);
        });
        
        // Get thread details (for UI)
        $app->get('/{id}/details', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(ThreadController::class);
            return $controller->getDetails($request, $response, $args);
        });
        
        $app->put('/{id}', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(ThreadController::class);
            return $controller->update($request, $response, $args);
        });
        
        $app->delete('/{id}', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(ThreadController::class);
            return $controller->delete($request, $response, $args);
        });
        
        // Notes
        $app->post('/{id}/notes', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(ThreadController::class);
            return $controller->addNote($request, $response, $args);
        });
        
        $app->put('/{id}/notes/{noteId}', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(ThreadController::class);
            return $controller->updateNote($request, $response, $args);
        });
        
        $app->delete('/{id}/notes/{noteId}', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(ThreadController::class);
            return $controller->deleteNote($request, $response, $args);
        });
        
        // Mark as Read/Unread
        $app->post('/{id}/mark-read', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(ThreadController::class);
            return $controller->markAsRead($request, $response, $args);
        });
        
        $app->post('/{id}/mark-unread', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(ThreadController::class);
            return $controller->markAsUnread($request, $response, $args);
        });
        
        // Thread Assignment
        $app->post('/{id}/assign', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(ThreadController::class);
            return $controller->assignUsers($request, $response, $args);
        });
        
        // Advanced operations
        $app->post('/{id}/emails/{emailId}/assign', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(ThreadController::class);
            return $controller->assignEmailToThread($request, $response, $args);
        });
        
        $app->post('/{id}/split', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(ThreadController::class);
            return $controller->splitThread($request, $response, $args);
        });
        
        $app->post('/{targetId}/merge', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(ThreadController::class);
            return $controller->mergeThreads($request, $response, $args);
        });
        
        // Reply and Forward
        $app->post('/{id}/reply', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(EmailController::class);
            return $controller->reply($request, $response, $args);
        });
        
        $app->post('/{id}/forward', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(EmailController::class);
            return $controller->forward($request, $response, $args);
        });
    });
    
    // Email API Routes
    $app->group('/api/emails', function ($app) {
        $app->post('/send', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(EmailController::class);
            return $controller->send($request, $response);
        });
        
        // Mark email as read
        $app->post('/{id}/read', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(EmailController::class);
            return $controller->markAsRead($request, $response, $args);
        });
        
        // Mark email as unread
        $app->post('/{id}/unread', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(EmailController::class);
            return $controller->markAsUnread($request, $response, $args);
        });
        
        $app->patch('/{emailId}/thread', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(ThreadController::class);
            return $controller->moveEmailToThread($request, $response, $args);
        });
    });
    
    // IMAP API Routes
    $app->group('/api/imap', function ($app) {
        // Sync IMAP account
        $app->post('/accounts/{id}/sync', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(ImapController::class);
            return $controller->syncAccount($request, $response, $args);
        });
    });
    
    // Label API Routes
    $app->group('/api/labels', function ($app) {
        // Get statistics (MUST be before /{id} to avoid routing conflict)
        $app->get('/stats', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(LabelController::class);
            return $controller->stats($request, $response);
        });
        
        // CRUD
        $app->post('', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(LabelController::class);
            return $controller->create($request, $response);
        });
        
        $app->get('', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(LabelController::class);
            return $controller->index($request, $response);
        });
        
        $app->get('/{id}', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(LabelController::class);
            return $controller->show($request, $response, $args);
        });
        
        $app->put('/{id}', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(LabelController::class);
            return $controller->update($request, $response, $args);
        });
        
        $app->delete('/{id}', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(LabelController::class);
            return $controller->delete($request, $response, $args);
        });
    });
    
    // User API Routes
    $app->group('/api/users', function ($app) {
        // List users
        $app->get('', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(UserController::class);
            return $controller->index($request, $response);
        });
        
        // Get single user
        $app->get('/{id}', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(UserController::class);
            return $controller->show($request, $response, $args);
        });
        
        // Create user
        $app->post('', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(UserController::class);
            return $controller->create($request, $response);
        });
        
        // Update user
        $app->put('/{id}', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(UserController::class);
            return $controller->update($request, $response, $args);
        });
        
        // Delete user
        $app->delete('/{id}', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(UserController::class);
            return $controller->delete($request, $response, $args);
        });
        
        // Change password
        $app->post('/{id}/password', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(UserController::class);
            return $controller->changePassword($request, $response, $args);
        });
    });
    
    // Personal IMAP Account API Routes (for user's personal email accounts)
    // Protected by AuthMiddleware - user_id is set automatically from session
    $app->group('/api/user/imap-accounts', function ($app) {
        // List all personal IMAP accounts
        $app->get('', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(PersonalImapAccountController::class);
            return $controller->index($request, $response);
        });
        
        // Get single account
        $app->get('/{id}', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(PersonalImapAccountController::class);
            return $controller->show($request, $response, $args);
        });
        
        // Create account
        $app->post('', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(PersonalImapAccountController::class);
            return $controller->create($request, $response);
        });
        
        // Update account
        $app->put('/{id}', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(PersonalImapAccountController::class);
            return $controller->update($request, $response, $args);
        });
        
        // Delete account
        $app->delete('/{id}', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(PersonalImapAccountController::class);
            return $controller->delete($request, $response, $args);
        });
        
        // Test connection
        $app->post('/{id}/test-connection', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(PersonalImapAccountController::class);
            return $controller->testConnection($request, $response, $args);
        });
    })->add($authMiddleware);
    
    // User Profile API Routes (for current user's profile settings)
    // Protected by AuthMiddleware - user_id is set automatically from session
    $app->group('/api/user/profile', function ($app) {
        // Get profile
        $app->get('', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(UserProfileController::class);
            return $controller->getProfile($request, $response);
        });
        
        // Update profile
        $app->put('', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(UserProfileController::class);
            return $controller->updateProfile($request, $response);
        });
        
        // Upload avatar
        $app->post('/avatar', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(UserProfileController::class);
            return $controller->uploadAvatar($request, $response);
        });
        
        // Delete avatar
        $app->delete('/avatar', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(UserProfileController::class);
            return $controller->deleteAvatar($request, $response);
        });
        
        // Change password
        $app->post('/change-password', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(UserProfileController::class);
            return $controller->changePassword($request, $response);
        });
    })->add($authMiddleware);
    
    // User Theme API Routes (Theme Module) - Separate group
    // Protected by AuthMiddleware - user_id is set automatically from session
    $app->group('/api/user', function ($app) use ($container) {
        // Get theme preference
        $app->get('/theme', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $themeService = $container->get(ThemeService::class);
            $logger = $container->get(LoggerInterface::class);
            
            try {
                $userId = $request->getAttribute('user_id');
                $themeMode = $themeService->getUserTheme($userId);
                
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'data' => [
                        'theme_mode' => $themeMode
                    ]
                ]));
                
                return $response->withHeader('Content-Type', 'application/json');
                
            } catch (\Exception $e) {
                $logger->error('Failed to get user theme', [
                    'error' => $e->getMessage()
                ]);
                
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Failed to retrieve theme preference'
                ]));
                
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(500);
            }
        });
        
        // Set theme preference (Theme Module)
        $app->post('/theme', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $themeService = $container->get(ThemeService::class);
            $logger = $container->get(LoggerInterface::class);
            
            try {
                $userId = $request->getAttribute('user_id');
                $data = $request->getParsedBody();
                
                if (!isset($data['theme_mode'])) {
                    $response->getBody()->write(json_encode([
                        'success' => false,
                        'error' => 'theme_mode is required'
                    ]));
                    
                    return $response
                        ->withHeader('Content-Type', 'application/json')
                        ->withStatus(400);
                }
                
                $themeMode = $data['theme_mode'];
                
                if (!$themeService->isValidThemeMode($themeMode)) {
                    $response->getBody()->write(json_encode([
                        'success' => false,
                        'error' => 'Invalid theme_mode. Must be: auto, light, or dark'
                    ]));
                    
                    return $response
                        ->withHeader('Content-Type', 'application/json')
                        ->withStatus(400);
                }
                
                $success = $themeService->setUserTheme($userId, $themeMode);
                
                if ($success) {
                    $response->getBody()->write(json_encode([
                        'success' => true,
                        'data' => [
                            'theme_mode' => $themeMode
                        ]
                    ]));
                    
                    return $response->withHeader('Content-Type', 'application/json');
                } else {
                    throw new \Exception('Failed to save theme preference');
                }
                
            } catch (\Exception $e) {
                $logger->error('Failed to set user theme', [
                    'error' => $e->getMessage()
                ]);
                
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Failed to save theme preference'
                ]));
                
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(500);
            }
        });
    })->add($authMiddleware);
    
    // ========== TWO-FACTOR AUTHENTICATION ROUTES ==========
    
    $app->group('/api/user/2fa', function ($app) {
        // Check 2FA status
        $app->get('/status', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\TwoFactorController::class);
            return $controller->status($request, $response);
        });
        
        // Start 2FA setup
        $app->post('/setup', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\TwoFactorController::class);
            return $controller->setup($request, $response);
        });
        
        // Enable 2FA
        $app->post('/enable', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\TwoFactorController::class);
            return $controller->enable($request, $response);
        });
        
        // Disable 2FA
        $app->post('/disable', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\TwoFactorController::class);
            return $controller->disable($request, $response);
        });
        
        // Verify 2FA code
        $app->post('/verify', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\TwoFactorController::class);
            return $controller->verify($request, $response);
        });
        
        // Regenerate backup codes
        $app->post('/backup-codes/regenerate', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\TwoFactorController::class);
            return $controller->regenerateBackupCodes($request, $response);
        });
    })->add($authMiddleware);
    
    // ========== ONBOARDING ROUTES ==========
    
    $app->group('/api/user/onboarding', function ($app) {
        // Save onboarding progress
        $app->post('/progress', function (Request $request, Response $response) {
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
            
            $body = json_decode($request->getBody()->getContents(), true);
            $tourId = $body['tour_id'] ?? '';
            $completed = $body['completed'] ?? false;
            $currentStep = $body['current_step'] ?? 0;
            
            // Store in database (simple implementation)
            try {
                \Illuminate\Database\Capsule\Manager::table('onboarding_progress')
                    ->updateOrInsert(
                        ['user_id' => $userId, 'tour_id' => $tourId],
                        [
                            'completed' => $completed,
                            'current_step' => $currentStep,
                            'completed_at' => $completed ? \Carbon\Carbon::now() : null,
                            'updated_at' => \Carbon\Carbon::now()
                        ]
                    );
                    
                $response->getBody()->write(json_encode(['success' => true]));
                return $response->withHeader('Content-Type', 'application/json');
                
            } catch (\Exception $e) {
                $response->getBody()->write(json_encode([
                    'success' => true // Silent fail - onboarding is not critical
                ]));
                return $response->withHeader('Content-Type', 'application/json');
            }
        });
        
        // Get onboarding status
        $app->get('/status', function (Request $request, Response $response) {
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
            
            try {
                $progress = \Illuminate\Database\Capsule\Manager::table('onboarding_progress')
                    ->where('user_id', $userId)
                    ->get()
                    ->keyBy('tour_id');
                    
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'data' => $progress
                ]));
                return $response->withHeader('Content-Type', 'application/json');
                
            } catch (\Exception $e) {
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'data' => []
                ]));
                return $response->withHeader('Content-Type', 'application/json');
            }
        });
    })->add($authMiddleware);
    
    // System Health API Routes
    $app->group('/api/system', function ($app) {
        // Basic health check (public)
        $app->get('/health', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $logger = $container->get(LoggerInterface::class);
            
            try {
                // Check database
                $db = $container->get('database');
                $dbStatus = 'OK';
                
                // Check disk space
                $diskFree = disk_free_space(__DIR__ . '/../../');
                $diskTotal = disk_total_space(__DIR__ . '/../../');
                
                // Count IMAP accounts
                $imapCount = \CiInbox\App\Models\ImapAccount::where('is_active', true)->count();
                
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'data' => [
                        'database' => $dbStatus,
                        'disk_space_free_gb' => round($diskFree / 1024 / 1024 / 1024, 2),
                        'disk_space_total_gb' => round($diskTotal / 1024 / 1024 / 1024, 2),
                        'disk_space_used_percent' => round((1 - $diskFree / $diskTotal) * 100, 1),
                        'php_version' => PHP_VERSION,
                        'imap_accounts' => $imapCount,
                        'timestamp' => time()
                    ]
                ]));
                
                return $response->withHeader('Content-Type', 'application/json');
                
            } catch (\Exception $e) {
                $logger->error('Health check failed', ['error' => $e->getMessage()]);
                
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Health check failed'
                ]));
                
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(500);
            }
        });
        
        // Cron status endpoint
        $app->get('/cron-status', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $logger = $container->get(LoggerInterface::class);
            
            try {
                // Get latest cron executions
                $latestExecution = \CiInbox\App\Models\CronExecution::orderBy('started_at', 'desc')->first();
                
                // Calculate statistics
                $now = new \DateTime();
                $yesterday = (new \DateTime())->modify('-24 hours');
                $last24h = \CiInbox\App\Models\CronExecution::where('started_at', '>=', $yesterday)->get();
                $successCount = $last24h->where('status', 'success')->count();
                $totalCount = $last24h->count();
                $successRate = $totalCount > 0 ? round(($successCount / $totalCount) * 100, 1) : 0;
                
                // Count emails processed today
                $today = (new \DateTime())->format('Y-m-d');
                $emailsToday = \CiInbox\App\Models\Email::whereDate('created_at', $today)->count();
                
                $data = [
                    'last_poll_at' => $latestExecution ? $latestExecution->started_at->format('Y-m-d H:i:s') : null,
                    'minutes_ago' => $latestExecution ? $latestExecution->started_at->diffInMinutes($now) : null,
                    'status' => $latestExecution ? $latestExecution->status : 'never_run',
                    'interval' => 15, // Default interval
                    'success_rate' => $successRate,
                    'emails_today' => $emailsToday,
                    'executions_24h' => $totalCount
                ];
                
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'data' => $data
                ]));
                
                return $response->withHeader('Content-Type', 'application/json');
                
            } catch (\Exception $e) {
                $logger->error('Cron status check failed', ['error' => $e->getMessage()]);
                
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Cron status unavailable'
                ]));
                
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(500);
            }
        });
        
        // Error log endpoint (admin only)
        $app->get('/errors', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $logger = $container->get(LoggerInterface::class);
            
            try {
                $limit = $request->getQueryParams()['limit'] ?? 20;
                $logDir = __DIR__ . '/../../logs/';
                $errors = [];
                
                // Read latest log file
                $logFiles = glob($logDir . 'app-*.log');
                if (!empty($logFiles)) {
                    arsort($logFiles); // Latest first
                    $latestLog = file_get_contents($logFiles[0]);
                    $lines = explode("\n", $latestLog);
                    
                    // Parse last N error lines
                    $count = 0;
                    foreach (array_reverse($lines) as $line) {
                        if (empty($line) || $count >= $limit) break;
                        
                        // Look for ERROR or WARNING level
                        if (str_contains($line, '"level":"error"') || str_contains($line, '"level":"warning"')) {
                            $json = json_decode($line, true);
                            if ($json) {
                                $errors[] = [
                                    'time' => $json['timestamp'] ?? '—',
                                    'level' => $json['level'] ?? 'error',
                                    'message' => $json['message'] ?? 'Unknown error',
                                    'context' => $json['context'] ?? []
                                ];
                                $count++;
                            }
                        }
                    }
                }
                
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'data' => [
                        'errors' => $errors,
                        'total' => count($errors)
                    ]
                ]));
                
                return $response->withHeader('Content-Type', 'application/json');
                
            } catch (\Exception $e) {
                $logger->error('Error log fetch failed', ['error' => $e->getMessage()]);
                
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => 'Failed to fetch error log'
                ]));
                
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(500);
            }
        });
    });
    
    // System Settings API Routes (Admin only)
    $app->group('/api/admin/settings', function ($app) {
        // IMAP Configuration
        $app->get('/imap', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\SystemSettingsController::class);
            return $controller->getImapConfig($request, $response);
        });
        
        $app->put('/imap', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\SystemSettingsController::class);
            return $controller->updateImapConfig($request, $response);
        });
        
        $app->post('/imap/test', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\SystemSettingsController::class);
            return $controller->testImapConnection($request, $response);
        });
        
        $app->post('/imap/autodiscover', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\SystemSettingsController::class);
            return $controller->autodiscoverImap($request, $response);
        });
        
        // SMTP Configuration
        $app->get('/smtp', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\SystemSettingsController::class);
            return $controller->getSmtpConfig($request, $response);
        });
        
        $app->put('/smtp', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\SystemSettingsController::class);
            return $controller->updateSmtpConfig($request, $response);
        });
        
        $app->post('/smtp/test', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\SystemSettingsController::class);
            return $controller->testSmtpConnection($request, $response);
        });
        
        $app->post('/smtp/autodiscover', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\SystemSettingsController::class);
            return $controller->autodiscoverSmtp($request, $response);
        });
        
        // Get all settings
        $app->get('', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\SystemSettingsController::class);
            return $controller->getAllSettings($request, $response);
        });
    });
    
    // Cron Monitor API Routes
    $app->group('/api/admin/cron', function ($app) {
        // Get current status
        $app->get('/status', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\CronMonitorController::class);
            return $controller->getStatus($request, $response);
        });
        
        // Get execution history
        $app->get('/history', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\CronMonitorController::class);
            return $controller->getHistory($request, $response);
        });
        
        // Get statistics
        $app->get('/statistics', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\CronMonitorController::class);
            return $controller->getStatistics($request, $response);
        });
    });
    
    // Backup API Routes (Admin only)
    $app->group('/api/admin/backup', function ($app) {
        $container = Container::getInstance();
        $logger = $container->get(LoggerInterface::class);
        
        // Create backup
        $app->post('/create', function (Request $request, Response $response) use ($container, $logger) {
            try {
                $backupService = $container->get(\CiInbox\App\Services\BackupService::class);
                $backup = $backupService->createBackup();
                
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'data' => $backup
                ]));
                
                return $response->withHeader('Content-Type', 'application/json');
                
            } catch (\Exception $e) {
                $logger->error('Backup creation failed', ['error' => $e->getMessage()]);
                
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]));
                
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(500);
            }
        });
        
        // List backups
        $app->get('/list', function (Request $request, Response $response) use ($container, $logger) {
            try {
                $backupService = $container->get(\CiInbox\App\Services\BackupService::class);
                $backups = $backupService->listBackups();
                
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'data' => $backups
                ]));
                
                return $response->withHeader('Content-Type', 'application/json');
                
            } catch (\Exception $e) {
                $logger->error('Backup list failed', ['error' => $e->getMessage()]);
                
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]));
                
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(500);
            }
        });
        
        // Download backup
        $app->get('/download/{filename}', function (Request $request, Response $response, array $args) use ($container, $logger) {
            try {
                $filename = $args['filename'];
                $backupService = $container->get(\CiInbox\App\Services\BackupService::class);
                $path = $backupService->getBackupPath($filename);
                
                if (!$path) {
                    $response->getBody()->write(json_encode([
                        'success' => false,
                        'error' => 'Backup not found'
                    ]));
                    
                    return $response
                        ->withHeader('Content-Type', 'application/json')
                        ->withStatus(404);
                }
                
                // Stream file
                $response = $response
                    ->withHeader('Content-Type', 'application/gzip')
                    ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                    ->withHeader('Content-Length', (string)filesize($path));
                
                $response->getBody()->write(file_get_contents($path));
                
                return $response;
                
            } catch (\Exception $e) {
                $logger->error('Backup download failed', ['error' => $e->getMessage()]);
                
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]));
                
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(500);
            }
        });
        
        // Delete backup
        $app->delete('/delete/{filename}', function (Request $request, Response $response, array $args) use ($container, $logger) {
            try {
                $filename = $args['filename'];
                $backupService = $container->get(\CiInbox\App\Services\BackupService::class);
                $success = $backupService->deleteBackup($filename);
                
                if ($success) {
                    $response->getBody()->write(json_encode([
                        'success' => true
                    ]));
                } else {
                    $response->getBody()->write(json_encode([
                        'success' => false,
                        'error' => 'Backup not found or deletion failed'
                    ]));
                }
                
                return $response->withHeader('Content-Type', 'application/json');
                
            } catch (\Exception $e) {
                $logger->error('Backup deletion failed', ['error' => $e->getMessage()]);
                
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]));
                
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(500);
            }
        });
        
        // Cleanup old backups
        $app->post('/cleanup', function (Request $request, Response $response) use ($container, $logger) {
            try {
                $body = $request->getParsedBody();
                $retentionDays = $body['retention_days'] ?? 30;
                
                $backupService = $container->get(\CiInbox\App\Services\BackupService::class);
                $deleted = $backupService->cleanupOldBackups((int)$retentionDays);
                
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'data' => [
                        'deleted_count' => $deleted
                    ]
                ]));
                
                return $response->withHeader('Content-Type', 'application/json');
                
            } catch (\Exception $e) {
                $logger->error('Backup cleanup failed', ['error' => $e->getMessage()]);
                
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]));
                
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(500);
            }
        });
    });
    
    // Webhook API Routes
    $app->group('/api/webhooks', function ($app) {
        // CRUD
        $app->post('', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(WebhookController::class);
            return $controller->create($request, $response);
        });
        
        $app->get('', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(WebhookController::class);
            return $controller->index($request, $response);
        });
        
        $app->get('/{id}', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(WebhookController::class);
            return $controller->show($request, $response, $args);
        });
        
        $app->put('/{id}', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(WebhookController::class);
            return $controller->update($request, $response, $args);
        });
        
        $app->delete('/{id}', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(WebhookController::class);
            return $controller->delete($request, $response, $args);
        });
        
        // Deliveries
        $app->get('/{id}/deliveries', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(WebhookController::class);
            return $controller->deliveries($request, $response, $args);
        });
        
        // Retry
        $app->post('/deliveries/{id}/retry', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(WebhookController::class);
            return $controller->retry($request, $response, $args);
        });
    });
    
    // ========== SIGNATURE ROUTES ==========
    
    // Personal Signatures (User)
    $app->group('/api/user/signatures', function ($app) {
        $app->get('/smtp-status', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\SignatureController::class);
            return $controller->getSmtpStatus($request, $response);
        });
        
        $app->get('', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\SignatureController::class);
            return $controller->getPersonalSignatures($request, $response);
        });
        
        $app->post('', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\SignatureController::class);
            return $controller->createPersonalSignature($request, $response);
        });
        
        $app->get('/{id}', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\SignatureController::class);
            return $controller->getSignature($request, $response, $args);
        });
        
        $app->put('/{id}', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\SignatureController::class);
            return $controller->updateSignature($request, $response, $args);
        });
        
        $app->delete('/{id}', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\SignatureController::class);
            return $controller->deleteSignature($request, $response, $args);
        });
        
        $app->post('/{id}/set-default', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\SignatureController::class);
            return $controller->setAsDefault($request, $response, $args);
        });
    });
    
    // Global Signatures (Admin)
    $app->group('/api/admin/signatures', function ($app) {
        $app->get('', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\SignatureController::class);
            return $controller->getGlobalSignatures($request, $response);
        });
        
        $app->post('', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\SignatureController::class);
            return $controller->createGlobalSignature($request, $response);
        });
        
        $app->put('/{id}', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\SignatureController::class);
            return $controller->updateGlobalSignature($request, $response, $args);
        });
        
        $app->delete('/{id}', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\SignatureController::class);
            return $controller->deleteGlobalSignature($request, $response, $args);
        });
        
        $app->post('/{id}/set-default', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\SignatureController::class);
            return $controller->setGlobalAsDefault($request, $response, $args);
        });
    });
    
    // ========== OAUTH ROUTES ==========
    
    // Public: List OAuth providers (for login page)
    $app->get('/api/oauth/providers', function (Request $request, Response $response) {
        $container = Container::getInstance();
        $controller = $container->get(\CiInbox\App\Controllers\OAuthController::class);
        return $controller->listProviders($request, $response);
    });
    
    // Public: Initiate OAuth flow
    $app->get('/oauth/authorize/{provider}', function (Request $request, Response $response, array $args) {
        $container = Container::getInstance();
        $controller = $container->get(\CiInbox\App\Controllers\OAuthController::class);
        return $controller->authorize($request, $response, $args);
    });
    
    // Public: OAuth callback
    $app->get('/oauth/callback/{provider}', function (Request $request, Response $response, array $args) {
        $container = Container::getInstance();
        $controller = $container->get(\CiInbox\App\Controllers\OAuthController::class);
        return $controller->callback($request, $response, $args);
    });
    
    // Admin: OAuth provider management
    $app->group('/api/admin/oauth', function ($app) {
        $app->post('/providers', function (Request $request, Response $response) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\OAuthController::class);
            return $controller->createProvider($request, $response);
        });
        
        $app->put('/providers/{id}', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\OAuthController::class);
            return $controller->updateProvider($request, $response, $args);
        });
        
        $app->delete('/providers/{id}', function (Request $request, Response $response, array $args) {
            $container = Container::getInstance();
            $controller = $container->get(\CiInbox\App\Controllers\OAuthController::class);
            return $controller->deleteProvider($request, $response, $args);
        });
    })->add($adminMiddleware)->add($authMiddleware);
    
    // API info endpoint
    $app->get('/api', function (Request $request, Response $response) {
        $apiInfo = [
            'name' => 'CI-Inbox API',
            'version' => '0.1.0',
            'endpoints' => [
                // System
                'GET /api/system/health' => 'System health check',
                'GET /api' => 'API information',
                // Threads
                'POST /api/threads' => 'Create new thread',
                'GET /api/threads' => 'List threads (with filters)',
                'GET /api/threads/{id}' => 'Get thread by ID',
                'GET /api/threads/{id}/details' => 'Get thread details (UI-optimized)',
                'PUT /api/threads/{id}' => 'Update thread',
                'DELETE /api/threads/{id}' => 'Delete thread',
                'POST /api/threads/{id}/notes' => 'Add note to thread',
                'PUT /api/threads/{id}/notes/{noteId}' => 'Update note',
                'DELETE /api/threads/{id}/notes/{noteId}' => 'Delete note',
                'POST /api/threads/{id}/emails/{emailId}/assign' => 'Assign email to thread',
                'POST /api/threads/{id}/split' => 'Split thread',
                'POST /api/threads/{targetId}/merge' => 'Merge threads',
                // Bulk Operations
                'POST /api/threads/bulk/update' => 'Bulk update threads',
                'POST /api/threads/bulk/delete' => 'Bulk delete threads',
                'POST /api/threads/bulk/assign' => 'Bulk assign threads',
                'POST /api/threads/bulk/status' => 'Bulk set status',
                'POST /api/threads/bulk/labels/add' => 'Bulk add label',
                'POST /api/threads/bulk/labels/remove' => 'Bulk remove label',
                // Emails
                'POST /api/emails/send' => 'Send new email',
                'POST /api/threads/{id}/reply' => 'Reply to thread',
                'POST /api/threads/{id}/forward' => 'Forward thread',
                'PATCH /api/emails/{emailId}/thread' => 'Move email to thread',
                // Labels
                'POST /api/labels' => 'Create label',
                'GET /api/labels' => 'List labels',
                'GET /api/labels/{id}' => 'Get label',
                'PUT /api/labels/{id}' => 'Update label',
                'DELETE /api/labels/{id}' => 'Delete label',
                'GET /api/labels/stats' => 'Get label statistics',
                // Users
                'POST /api/users' => 'Create user',
                'GET /api/users' => 'List users',
                'GET /api/users/{id}' => 'Get user',
                'PUT /api/users/{id}' => 'Update user',
                'DELETE /api/users/{id}' => 'Delete user',
                'POST /api/users/{id}/password' => 'Change password',
                // Personal IMAP Accounts (User's personal email accounts)
                'POST /api/user/imap-accounts' => 'Create personal IMAP account',
                'GET /api/user/imap-accounts' => 'List personal IMAP accounts',
                'GET /api/user/imap-accounts/{id}' => 'Get personal IMAP account',
                'PUT /api/user/imap-accounts/{id}' => 'Update personal IMAP account',
                'DELETE /api/user/imap-accounts/{id}' => 'Delete personal IMAP account',
                'POST /api/user/imap-accounts/{id}/test-connection' => 'Test IMAP connection',
                // Signatures (Personal)
                'GET /api/user/signatures' => 'List personal signatures',
                'GET /api/user/signatures/smtp-status' => 'Check SMTP configuration status',
                'POST /api/user/signatures' => 'Create personal signature',
                'GET /api/user/signatures/{id}' => 'Get signature details',
                'PUT /api/user/signatures/{id}' => 'Update signature',
                'DELETE /api/user/signatures/{id}' => 'Delete signature',
                'POST /api/user/signatures/{id}/set-default' => 'Set as default signature',
                // Signatures (Global - Admin)
                'GET /api/admin/signatures' => 'List global signatures',
                'POST /api/admin/signatures' => 'Create global signature',
                'PUT /api/admin/signatures/{id}' => 'Update global signature',
                'DELETE /api/admin/signatures/{id}' => 'Delete global signature',
                'POST /api/admin/signatures/{id}/set-default' => 'Set as default global signature',
                // System Settings (Admin)
                'GET /api/admin/settings/imap' => 'Get IMAP configuration',
                'PUT /api/admin/settings/imap' => 'Update IMAP configuration',
                'POST /api/admin/settings/imap/test' => 'Test IMAP connection',
                'POST /api/admin/settings/imap/autodiscover' => 'Auto-discover IMAP settings from email',
                'GET /api/admin/settings/smtp' => 'Get SMTP configuration',
                'PUT /api/admin/settings/smtp' => 'Update SMTP configuration',
                'POST /api/admin/settings/smtp/test' => 'Test SMTP connection',
                'POST /api/admin/settings/smtp/autodiscover' => 'Auto-discover SMTP settings from email',
                'GET /api/admin/settings' => 'Get all system settings',
                'GET /api/admin/cron/status' => 'Get cron service status',
                'GET /api/admin/cron/history' => 'Get cron execution history',
                'GET /api/admin/cron/statistics' => 'Get cron statistics',
                // Webhooks
                'POST /api/webhooks' => 'Register webhook',
                'GET /api/webhooks' => 'List webhooks',
                'GET /api/webhooks/{id}' => 'Get webhook details',
                'PUT /api/webhooks/{id}' => 'Update webhook',
                'DELETE /api/webhooks/{id}' => 'Delete webhook',
                'GET /api/webhooks/{id}/deliveries' => 'Get delivery history',
                'POST /api/webhooks/deliveries/{id}/retry' => 'Retry failed delivery',
                // Public Webhooks (External Cron Services)
                'POST /webhooks/poll-emails' => 'Trigger email polling (external cron)',
            ],
        ];
        
        $response->getBody()->write(json_encode($apiInfo, JSON_PRETTY_PRINT));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    });
};

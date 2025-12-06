<?php

/**
 * Container Definitions
 * 
 * Defines how services should be created and injected.
 */

use CiInbox\Modules\Config\ConfigService;
use CiInbox\Modules\Config\ConfigInterface;
use CiInbox\Modules\Logger\LoggerService;
use CiInbox\Modules\Logger\LoggerInterface;
use CiInbox\Modules\Encryption\EncryptionService;
use CiInbox\Modules\Encryption\EncryptionInterface;
use CiInbox\Modules\Label\LabelManager;
use CiInbox\Modules\Label\LabelManagerInterface;
use CiInbox\Modules\Webcron\WebcronManager;
use CiInbox\Modules\Webcron\WebcronManagerInterface;
use CiInbox\Modules\Imap\ImapClientInterface;
use CiInbox\Modules\Imap\ImapClient;
use CiInbox\Modules\Imap\Parser\EmailParserInterface;
use CiInbox\Modules\Imap\Parser\EmailParser;
use CiInbox\Modules\Imap\Manager\ThreadManagerInterface;
use CiInbox\Modules\Imap\Manager\ThreadManager;
use CiInbox\App\Controllers\ImapController;
use CiInbox\App\Controllers\ThreadController;
use CiInbox\App\Controllers\LabelController;
use CiInbox\App\Controllers\UserController;
use CiInbox\App\Services\LabelService;
use CiInbox\App\Services\UserService;
use CiInbox\App\Services\PollingService;
use CiInbox\App\Services\EmailProcessingService;
use CiInbox\App\Services\ThreadApiService;
use CiInbox\App\Repositories\LabelRepository;
use CiInbox\App\Repositories\ThreadRepository;
use CiInbox\App\Repositories\ImapAccountRepository;
use CiInbox\App\Repositories\ThreadRepositoryInterface;
use CiInbox\App\Repositories\EmailRepositoryInterface;
use CiInbox\App\Repositories\EloquentEmailRepository;
use CiInbox\App\Repositories\NoteRepositoryInterface;
use CiInbox\App\Repositories\EloquentNoteRepository;
use CiInbox\Modules\Smtp\SmtpClientInterface;
use CiInbox\Modules\Smtp\PHPMailerSmtpClient;
use CiInbox\Modules\Smtp\SmtpConfig;
use CiInbox\App\Services\EmailSendService;
use CiInbox\App\Controllers\EmailController;
use CiInbox\App\Services\WebhookService;
use CiInbox\App\Controllers\WebhookController;
use CiInbox\App\Models\Webhook;
use CiInbox\App\Models\WebhookDelivery;
use CiInbox\App\Controllers\AuthController;
use CiInbox\App\Services\ThreadBulkService;
use CiInbox\App\Services\ThreadStatusService;
use CiInbox\App\Services\PersonalImapAccountService;
use CiInbox\App\Controllers\PersonalImapAccountController;
use CiInbox\App\Services\UserProfileService;
use CiInbox\App\Controllers\UserProfileController;
use CiInbox\App\Services\SystemHealthService;
use CiInbox\App\Controllers\SystemHealthController;
use CiInbox\Modules\Theme\ThemeServiceInterface;
use CiInbox\Modules\Theme\ThemeService;

return [
    // Config Service
    ConfigInterface::class => function() {
        return new ConfigService(__DIR__ . '/../../');
    },

    // Logger Service
    LoggerInterface::class => function($container) {
        $config = $container->get(ConfigInterface::class);
        return new LoggerService(
            $config->getString('log.path', __DIR__ . '/../../logs'),
            $config->getString('log.level', 'debug'),
            $config->getString('log.channel', 'app')
        );
    },

    // Encryption Service
    EncryptionInterface::class => function($container) {
        return new EncryptionService($container->get(ConfigInterface::class));
    },

    // Database Connection (Eloquent already initialized in bootstrap)
    'database' => function($container) {
        require_once __DIR__ . '/../bootstrap/database.php';
        return initDatabase($container->get(ConfigInterface::class));
    },
    
    // Repositories
    LabelRepository::class => function($container) {
        return new LabelRepository(
            $container->get(LoggerService::class)
        );
    },
    
    ThreadRepository::class => function($container) {
        return new ThreadRepository($container->get(LoggerService::class));
    },
    
    // New Repository Interfaces (M2 Sprint 2.1)
    // Unified: ThreadRepository implements ThreadRepositoryInterface
    ThreadRepositoryInterface::class => function($container) {
        return $container->get(ThreadRepository::class);
    },
    
    EmailRepositoryInterface::class => function($container) {
        return new EloquentEmailRepository(
            $container->get(LoggerService::class)
        );
    },
    
    NoteRepositoryInterface::class => function($container) {
        return new EloquentNoteRepository(
            $container->get(LoggerService::class)
        );
    },
    
    // Thread API Service (M2 Sprint 2.1)
    ThreadApiService::class => function($container) {
        return new ThreadApiService(
            $container->get(ThreadRepositoryInterface::class),
            $container->get(EmailRepositoryInterface::class),
            $container->get(NoteRepositoryInterface::class),
            $container->get(LoggerService::class),
            $container->get(WebhookService::class)
        );
    },
    
    // Thread Bulk Operations Service
    ThreadBulkService::class => function($container) {
        return new ThreadBulkService(
            $container->get(ThreadRepositoryInterface::class),
            $container->get(LoggerService::class)
        );
    },
    
    // Thread Status Service (Auto-Status Management)
    ThreadStatusService::class => function($container) {
        return new ThreadStatusService(
            $container->get(LoggerService::class)
        );
    },
    
    // Thread Controller (M2 Sprint 2.1)
    ThreadController::class => function($container) {
        return new ThreadController(
            $container->get(ThreadApiService::class),
            $container->get(ThreadBulkService::class),
            $container->get(LoggerService::class)
        );
    },
    
    // ========== SYSTEM SETTINGS (Required for SMTP Config) ==========
    
    // SystemSetting Repository
    \CiInbox\App\Repositories\SystemSettingRepository::class => function($container) {
        return new \CiInbox\App\Repositories\SystemSettingRepository(
            $container->get(EncryptionService::class),
            $container->get(LoggerService::class)
        );
    },
    
    // SystemSettings Service
    \CiInbox\App\Services\SystemSettingsService::class => function($container) {
        return new \CiInbox\App\Services\SystemSettingsService(
            $container->get(\CiInbox\App\Repositories\SystemSettingRepository::class),
            $container->get(LoggerService::class)
        );
    },
    
    // ========== SMTP MODULE (M2 Sprint 2.2) ==========
    
    // SMTP Config - Load from database via SystemSettingsService
    'smtp.config' => function($container) {
        // Check if SystemSettingsService is available
        if ($container->has(\CiInbox\App\Services\SystemSettingsService::class)) {
            $settingsService = $container->get(\CiInbox\App\Services\SystemSettingsService::class);
            $dbConfig = $settingsService->getSmtpConfig();
            
            // If database config exists and is valid, use it
            if (!empty($dbConfig['host'])) {
                // Transform database format to SmtpConfig format
                // DB stores 'ssl' (bool), SmtpConfig expects 'encryption' (string)
                $encryption = 'none';
                if (!empty($dbConfig['ssl'])) {
                    $encryption = ($dbConfig['port'] == 465) ? 'ssl' : 'tls';
                }
                
                return SmtpConfig::fromArray([
                    'host' => $dbConfig['host'],
                    'port' => $dbConfig['port'],
                    'username' => $dbConfig['username'] ?? '',
                    'password' => $dbConfig['password'] ?? '',
                    'encryption' => $encryption,
                    'from_email' => $dbConfig['from_email'],
                    'from_name' => $dbConfig['from_name']
                ]);
            }
        }
        
        // Fallback to file config if DB not available or empty
        $config = require __DIR__ . '/../modules/smtp/config/smtp.config.php';
        return SmtpConfig::fromArray($config);
    },
    
    // SMTP Client Interface
    SmtpClientInterface::class => function($container) {
        return new PHPMailerSmtpClient(
            $container->get(LoggerService::class)
        );
    },
    
    // Email Send Service (M2 Sprint 2.2)
    EmailSendService::class => function($container) {
        return new EmailSendService(
            $container->get(SmtpClientInterface::class),
            $container->get(EmailRepositoryInterface::class),
            $container->get(ThreadRepositoryInterface::class),
            $container->get(LoggerService::class),
            $container->get('smtp.config'),
            $container->get(WebhookService::class)
        );
    },
    
    // Email Controller (M2 Sprint 2.2)
    EmailController::class => function($container) {
        return new EmailController(
            $container->get(EmailSendService::class),
            $container->get(LoggerService::class),
            $container->get(EmailRepositoryInterface::class)
        );
    },
    
    // ========== IMAP MODULE ==========
    
    // IMAP Client
    ImapClientInterface::class => function($container) {
        return new ImapClient(
            $container->get(LoggerService::class),
            $container->get(ConfigInterface::class)
        );
    },
    
    // Email Parser
    EmailParserInterface::class => function($container) {
        return new EmailParser(
            $container->get(LoggerService::class)
        );
    },
    
    // Thread Manager
    ThreadManagerInterface::class => function($container) {
        return new ThreadManager(
            $container->get(LoggerService::class),
            $container->get(ThreadRepository::class)
        );
    },
    
    // ========== LABEL MODULE ==========
    
    // Label Manager
    LabelManagerInterface::class => function($container) {
        $configService = $container->get(ConfigService::class);
        $labelConfig = require __DIR__ . '/../modules/label/config/label.config.php';
        
        return new LabelManager(
            $container->get(LabelRepository::class),
            $container->get(LoggerService::class),
            $labelConfig
        );
    },
    
    // Label Service
    LabelService::class => function($container) {
        return new LabelService(
            $container->get(LabelManagerInterface::class),
            $container->get(LabelRepository::class),
            $container->get(ThreadRepository::class),
            $container->get(LoggerService::class)
        );
    },
    
    // Label Controller
    LabelController::class => function($container) {
        return new LabelController(
            $container->get(LabelService::class),
            $container->get(LoggerService::class)
        );
    },
    
    // User Service
    UserService::class => function($container) {
        return new UserService(
            $container->get(LoggerInterface::class)
        );
    },
    
    // User Controller
    UserController::class => function($container) {
        return new UserController(
            $container->get(UserService::class),
            $container->get(LoggerService::class)
        );
    },
    
    // ========== WEBCRON MODULE ==========
    
    // Webcron Config
    'webcron.config' => function() {
        return require __DIR__ . '/../modules/webcron/config/webcron.config.php';
    },
    
    // Webcron Manager (refactored to use internal API)
    WebcronManagerInterface::class => function($container) {
        return new WebcronManager(
            $container->get(ImapAccountRepository::class),
            $container->get(LoggerService::class),
            $container->get('webcron.config')
        );
    },
    
    // Webcron Manager concrete class (alias to interface)
    WebcronManager::class => function($container) {
        return $container->get(WebcronManagerInterface::class);
    },
    
    // IMAP Controller
    ImapController::class => function($container) {
        return new ImapController(
            $container->get(ImapAccountRepository::class),
            $container->get(EmailRepositoryInterface::class),
            $container->get(ThreadRepository::class),
            $container->get(LabelRepository::class),
            $container->get(ImapClientInterface::class),
            $container->get(EmailParserInterface::class),
            $container->get(ThreadManagerInterface::class),
            $container->get(EncryptionInterface::class),
            $container->get(LoggerService::class)
        );
    },
    
    // IMAP Account Repository
    ImapAccountRepository::class => function($container) {
        return new ImapAccountRepository(
            $container->get(LoggerService::class)
        );
    },
    
    // Webhook Service
    WebhookService::class => function($container) {
        return new WebhookService(
            $container->get(LoggerService::class)
        );
    },
    
    // Webhook Controller
    WebhookController::class => function($container) {
        return new WebhookController(
            $container->get(WebhookService::class),
            $container->get(LoggerService::class),
            $container->get(WebcronManager::class)
        );
    },
    
    // Auth Controller
    AuthController::class => function($container) {
        return new AuthController(
            $container->get(LoggerService::class)
        );
    },
    
    // Personal IMAP Account Service
    PersonalImapAccountService::class => function($container) {
        return new PersonalImapAccountService(
            $container->get(ImapAccountRepository::class),
            $container->get(EncryptionInterface::class),
            $container->get(LoggerService::class)
        );
    },
    
    // Personal IMAP Account Controller
    PersonalImapAccountController::class => function($container) {
        return new PersonalImapAccountController(
            $container->get(PersonalImapAccountService::class),
            $container->get(LoggerService::class)
        );
    },
    
    // User Profile Service
    UserProfileService::class => function($container) {
        return new UserProfileService(
            $container->get(LoggerService::class),
            $container->get(EncryptionInterface::class)
        );
    },
    
    // User Profile Controller
    UserProfileController::class => function($container) {
        return new UserProfileController(
            $container->get(UserProfileService::class),
            $container->get(LoggerService::class)
        );
    },
    
    // ========== SYSTEM HEALTH MODULE (M5 Sprint 5.3) ==========
    
    // System Health Service
    SystemHealthService::class => function($container) {
        $healthService = new SystemHealthService(
            $container->get(LoggerService::class)
        );
        
        // Register modules for health checks
        $healthService->registerModule($container->get(LoggerService::class));
        $healthService->registerModule($container->get(ConfigService::class));
        $healthService->registerModule($container->get(EncryptionService::class));
        
        return $healthService;
    },
    
    // System Health Controller
    SystemHealthController::class => function($container) {
        return new SystemHealthController(
            $container->get(SystemHealthService::class),
            $container->get(LoggerService::class)
        );
    },
    
    // ========== SIGNATURE MODULE ==========
    
    // Signature Repository
    \CiInbox\App\Repositories\SignatureRepository::class => function($container) {
        return new \CiInbox\App\Repositories\SignatureRepository(
            $container->get(LoggerService::class)
        );
    },
    
    // Signature Service
    \CiInbox\App\Services\SignatureService::class => function($container) {
        return new \CiInbox\App\Services\SignatureService(
            $container->get(\CiInbox\App\Repositories\SignatureRepository::class),
            $container->get(LoggerService::class)
        );
    },
    
    // Signature Controller
    \CiInbox\App\Controllers\SignatureController::class => function($container) {
        return new \CiInbox\App\Controllers\SignatureController(
            $container->get(\CiInbox\App\Services\SignatureService::class)
        );
    },
    
    // ========== SYSTEM SETTINGS MODULE ==========
    
    // Note: SystemSettingRepository and SystemSettingsService are defined earlier
    // (before SMTP config) to avoid circular dependencies
    
    // AutoDiscover Service
    \CiInbox\App\Services\AutoDiscoverService::class => function($container) {
        return new \CiInbox\App\Services\AutoDiscoverService(
            $container->get(LoggerService::class)
        );
    },
    
    // SystemSettings Controller
    \CiInbox\App\Controllers\SystemSettingsController::class => function($container) {
        return new \CiInbox\App\Controllers\SystemSettingsController(
            $container->get(\CiInbox\App\Services\SystemSettingsService::class),
            $container->get(\CiInbox\App\Services\AutoDiscoverService::class),
            $container->get(LoggerService::class)
        );
    },
    
    // CronMonitor Service
    \CiInbox\App\Services\CronMonitorService::class => function($container) {
        return new \CiInbox\App\Services\CronMonitorService(
            $container->get(LoggerService::class)
        );
    },
    
    // CronMonitor Controller
    \CiInbox\App\Controllers\CronMonitorController::class => function($container) {
        return new \CiInbox\App\Controllers\CronMonitorController(
            $container->get(\CiInbox\App\Services\CronMonitorService::class),
            $container->get(LoggerService::class)
        );
    },
    
    // ========== BACKUP SERVICE ==========
    
    // Backup Service
    \CiInbox\App\Services\BackupService::class => function($container) {
        return new \CiInbox\App\Services\BackupService(
            $container->get(LoggerService::class),
            $container->get(ConfigService::class)
        );
    },
    
    // ========== DATABASE ADMIN ==========
    
    // Database Admin Service
    \CiInbox\App\Services\DatabaseAdminService::class => function($container) {
        return new \CiInbox\App\Services\DatabaseAdminService(
            $container->get(LoggerService::class)
        );
    },
    
    // Database Admin Controller
    \CiInbox\App\Controllers\DatabaseAdminController::class => function($container) {
        return new \CiInbox\App\Controllers\DatabaseAdminController(
            $container->get(\CiInbox\App\Services\DatabaseAdminService::class),
            $container->get(LoggerService::class)
        );
    },
    
    // ========== LOGGER ADMIN ==========
    
    // Logger Admin Service
    \CiInbox\App\Services\LoggerAdminService::class => function($container) {
        return new \CiInbox\App\Services\LoggerAdminService(
            $container->get(LoggerService::class),
            $container->get(\CiInbox\App\Repositories\SystemSettingRepository::class)
        );
    },
    
    // Logger Admin Controller
    \CiInbox\App\Controllers\LoggerAdminController::class => function($container) {
        return new \CiInbox\App\Controllers\LoggerAdminController(
            $container->get(\CiInbox\App\Services\LoggerAdminService::class),
            $container->get(LoggerService::class)
        );
    },
    
    // ========== THEME MODULE ==========
    
    // Theme Service
    ThemeServiceInterface::class => function($container) {
        return new ThemeService(
            $container->get(LoggerService::class),
            $container->get(ConfigService::class)
        );
    },
    
    ThemeService::class => function($container) {
        return $container->get(ThemeServiceInterface::class);
    },
    
    // ========== MIDDLEWARE ==========
    
    // Auth Middleware
    \CiInbox\App\Middleware\AuthMiddleware::class => function($container) {
        return new \CiInbox\App\Middleware\AuthMiddleware(
            $container->get(LoggerService::class)
        );
    },
    
    // Admin Middleware
    \CiInbox\App\Middleware\AdminMiddleware::class => function($container) {
        return new \CiInbox\App\Middleware\AdminMiddleware(
            $container->get(LoggerService::class)
        );
    },
    
    // Rate Limit Middleware
    \CiInbox\App\Middleware\RateLimitMiddleware::class => function($container) {
        return new \CiInbox\App\Middleware\RateLimitMiddleware(
            $container->get(LoggerService::class),
            100,  // 100 requests per minute
            60    // 60 second window
        );
    },
    
    // CSRF Middleware
    \CiInbox\App\Middleware\CsrfMiddleware::class => function($container) {
        return new \CiInbox\App\Middleware\CsrfMiddleware(
            $container->get(LoggerService::class)
        );
    },
    
    // Security Headers Middleware
    \CiInbox\App\Middleware\SecurityHeadersMiddleware::class => function($container) {
        return new \CiInbox\App\Middleware\SecurityHeadersMiddleware();
    },
    
    // ========== OAUTH & PASSWORD RESET ==========
    
    // OAuth Service
    \CiInbox\App\Services\OAuthService::class => function($container) {
        return new \CiInbox\App\Services\OAuthService(
            $container->get(LoggerService::class),
            $container->get(EncryptionInterface::class)
        );
    },
    
    // OAuth Controller
    \CiInbox\App\Controllers\OAuthController::class => function($container) {
        return new \CiInbox\App\Controllers\OAuthController(
            $container->get(\CiInbox\App\Services\OAuthService::class),
            $container->get(LoggerService::class)
        );
    },
    
    // Password Reset Service
    \CiInbox\App\Services\PasswordResetService::class => function($container) {
        return new \CiInbox\App\Services\PasswordResetService(
            $container->get(LoggerService::class),
            $container->get(SmtpClientInterface::class),
            $container->get('smtp.config')
        );
    },
    
    // ========== TWO-FACTOR AUTHENTICATION ==========
    
    // 2FA Service
    \CiInbox\App\Services\TwoFactorAuthService::class => function($container) {
        return new \CiInbox\App\Services\TwoFactorAuthService(
            $container->get(LoggerService::class),
            $container->get(EncryptionInterface::class)
        );
    },
    
    // 2FA Controller
    \CiInbox\App\Controllers\TwoFactorController::class => function($container) {
        return new \CiInbox\App\Controllers\TwoFactorController(
            $container->get(\CiInbox\App\Services\TwoFactorAuthService::class),
            $container->get(LoggerService::class)
        );
    },
];

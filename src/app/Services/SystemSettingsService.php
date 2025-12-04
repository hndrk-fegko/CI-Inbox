<?php

namespace CiInbox\App\Services;

use CiInbox\App\Repositories\SystemSettingRepository;
use CiInbox\Modules\Imap\ImapClient;
use CiInbox\Modules\Smtp\PHPMailerSmtpClient;
use CiInbox\Modules\Smtp\SmtpConfig;
use CiInbox\Modules\Logger\LoggerInterface;

/**
 * SystemSettings Service
 * 
 * Business logic for system-wide configuration management.
 */
class SystemSettingsService
{
    public function __construct(
        private SystemSettingRepository $repository,
        private LoggerInterface $logger
    ) {}
    
    /**
     * Get IMAP configuration
     */
    public function getImapConfig(): array
    {
        try {
            $config = $this->repository->getByPrefix('imap.');
            
            $this->logger->info('IMAP config retrieved');
            
            return [
                'host' => $config['host'] ?? '',
                'port' => $config['port'] ?? 993,
                'ssl' => $config['ssl'] ?? true,
                'username' => $config['username'] ?? '',
                'password' => $config['password'] ?? '',
                'inbox_folder' => $config['inbox_folder'] ?? 'INBOX',
                'configured' => !empty($config['host']) && !empty($config['username'])
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get IMAP config', [
                'exception' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Update IMAP configuration
     */
    public function updateImapConfig(array $config): array
    {
        try {
            $settings = [
                'imap.host' => $config['host'] ?? '',
                'imap.port' => $config['port'] ?? 993,
                'imap.ssl' => $config['ssl'] ?? true,
                'imap.username' => $config['username'] ?? '',
                'imap.inbox_folder' => $config['inbox_folder'] ?? 'INBOX',
            ];
            
            // Only update password if provided
            if (!empty($config['password'])) {
                $settings['imap.password'] = $config['password'];
            }
            
            $this->repository->updateMultiple($settings);
            
            $this->logger->info('[SUCCESS] IMAP config updated', [
                'host' => $config['host'],
                'username' => $config['username']
            ]);
            
            return $this->getImapConfig();
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to update IMAP config', [
                'exception' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Test IMAP connection
     */
    public function testImapConnection(array $config = null): array
    {
        try {
            // Use provided config or fetch from database
            if ($config === null) {
                $config = $this->getImapConfig();
            }
            
            $this->logger->info('Testing IMAP connection', [
                'host' => $config['host'],
                'port' => $config['port']
            ]);
            
            $imapClient = new ImapClient(
                $config['host'],
                $config['username'],
                $config['password'],
                $config['port'],
                $config['ssl']
            );
            
            $imapClient->connect();
            $folders = $imapClient->listFolders();
            $imapClient->disconnect();
            
            $this->logger->info('[SUCCESS] IMAP connection successful', [
                'host' => $config['host'],
                'folders_found' => count($folders)
            ]);
            
            return [
                'success' => true,
                'message' => 'Connection successful',
                'folders' => $folders
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('IMAP connection failed', [
                'host' => $config['host'] ?? 'unknown',
                'exception' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get SMTP configuration
     */
    public function getSmtpConfig(): array
    {
        try {
            $config = $this->repository->getByPrefix('smtp.');
            
            $this->logger->info('SMTP config retrieved');
            
            return [
                'host' => $config['host'] ?? '',
                'port' => $config['port'] ?? 587,
                'ssl' => $config['ssl'] ?? true,
                'auth' => $config['auth'] ?? true,
                'username' => $config['username'] ?? '',
                'password' => $config['password'] ?? '',
                'from_name' => $config['from_name'] ?? 'CI-Inbox',
                'from_email' => $config['from_email'] ?? '',
                'configured' => !empty($config['host']) && !empty($config['from_email'])
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get SMTP config', [
                'exception' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Update SMTP configuration
     */
    public function updateSmtpConfig(array $config): array
    {
        try {
            $settings = [
                'smtp.host' => $config['host'] ?? '',
                'smtp.port' => $config['port'] ?? 587,
                'smtp.ssl' => $config['ssl'] ?? true,
                'smtp.auth' => $config['auth'] ?? true,
                'smtp.username' => $config['username'] ?? '',
                'smtp.from_name' => $config['from_name'] ?? 'CI-Inbox',
                'smtp.from_email' => $config['from_email'] ?? '',
            ];
            
            // Only update password if provided
            if (!empty($config['password'])) {
                $settings['smtp.password'] = $config['password'];
            }
            
            $this->repository->updateMultiple($settings);
            
            $this->logger->info('[SUCCESS] SMTP config updated', [
                'host' => $config['host'],
                'from_email' => $config['from_email']
            ]);
            
            return $this->getSmtpConfig();
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to update SMTP config', [
                'exception' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Test SMTP connection
     */
    public function testSmtpConnection(array $config = null, string $testEmail = null): array
    {
        try {
            // Use provided config or fetch from database
            if ($config === null) {
                $config = $this->getSmtpConfig();
            }
            
            $this->logger->info('Testing SMTP connection', [
                'host' => $config['host'],
                'port' => $config['port']
            ]);
            
            // Create SmtpConfig object
            $smtpConfig = new SmtpConfig(
                host: $config['host'],
                port: (int)$config['port'],
                encryption: $config['ssl'] ? 'ssl' : null,
                username: $config['username'],
                password: $config['password'],
                fromName: $config['from_name'],
                fromEmail: $config['from_email']
            );
            
            // Create and connect SMTP client
            $smtpClient = new PHPMailerSmtpClient($this->logger);
            $smtpClient->connect($smtpConfig);
            
            // If test email provided, send test message
            if ($testEmail) {
                $testMessage = new \CiInbox\Modules\Smtp\EmailMessage(
                    subject: 'CI-Inbox SMTP Test',
                    bodyText: 'This is a test email from CI-Inbox. If you receive this, SMTP is configured correctly.',
                    bodyHtml: '<p>This is a test email from CI-Inbox. If you receive this, SMTP is configured correctly.</p>',
                    to: [['email' => $testEmail, 'name' => '']]
                );
                
                $smtpClient->send($testMessage);
                
                $message = 'Test email sent successfully to ' . $testEmail;
            } else {
                // Just test connection without sending
                $message = 'SMTP connection successful';
            }
            
            $smtpClient->disconnect();
            
            $this->logger->info('[SUCCESS] SMTP connection successful', [
                'host' => $config['host'],
                'test_email_sent' => !empty($testEmail)
            ]);
            
            return [
                'success' => true,
                'message' => $message
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('SMTP connection failed', [
                'host' => $config['host'] ?? 'unknown',
                'exception' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get all settings (admin view)
     */
    public function getAllSettings(): array
    {
        try {
            return $this->repository->getAll();
        } catch (\Exception $e) {
            $this->logger->error('Failed to get all settings', [
                'exception' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}

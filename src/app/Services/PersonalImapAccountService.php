<?php

declare(strict_types=1);

namespace CiInbox\App\Services;

use CiInbox\App\Repositories\ImapAccountRepository;
use CiInbox\Modules\Encryption\EncryptionInterface;
use CiInbox\Modules\Logger\LoggerInterface;
use CiInbox\App\Models\ImapAccount;

/**
 * Personal IMAP Account Service
 * 
 * Manages user's personal IMAP accounts (for Workflow C - Transfer)
 * This is SEPARATE from the main shared inbox IMAP connection.
 * 
 * Naming Convention:
 * - "Personal IMAP Account" = User's personal email account (Gmail, Outlook, etc.)
 * - "Main IMAP" = Shared inbox (info@company.com) - handled by ImapController
 */
class PersonalImapAccountService
{
    public function __construct(
        private ImapAccountRepository $repository,
        private EncryptionInterface $encryption,
        private LoggerInterface $logger
    ) {}

    /**
     * Get all personal accounts for a user
     * 
     * @param int $userId
     * @return array
     */
    public function getUserAccounts(int $userId): array
    {
        $this->logger->debug('Fetching personal IMAP accounts', ['user_id' => $userId]);
        
        $accounts = $this->repository->getAllByUser($userId);
        
        $this->logger->info('Personal IMAP accounts fetched', [
            'user_id' => $userId,
            'count' => count($accounts)
        ]);
        
        return $accounts;
    }

    /**
     * Get single account by ID
     * 
     * @param int $accountId
     * @param int $userId User ID for ownership check
     * @return ImapAccount|null
     */
    public function getAccount(int $accountId, int $userId): ?ImapAccount
    {
        $this->logger->debug('Fetching personal IMAP account', [
            'account_id' => $accountId,
            'user_id' => $userId
        ]);
        
        $account = $this->repository->find($accountId);
        
        if ($account && $account->user_id !== $userId) {
            $this->logger->warning('Account access denied - wrong user', [
                'account_id' => $accountId,
                'owner_id' => $account->user_id,
                'requested_by' => $userId
            ]);
            return null;
        }
        
        return $account;
    }

    /**
     * Create new personal IMAP account
     * 
     * @param int $userId
     * @param array $data
     * @return ImapAccount
     * @throws \Exception
     */
    public function createAccount(int $userId, array $data): ImapAccount
    {
        $this->logger->info('Creating personal IMAP account', [
            'user_id' => $userId,
            'email' => $data['email'] ?? 'unknown'
        ]);

        // Validation
        if (empty($data['email']) || empty($data['password'])) {
            throw new \Exception('Email and password are required');
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Invalid email format');
        }

        // Check if account already exists for this user
        $existing = $this->repository->findByEmail($data['email'], $userId);
        if ($existing) {
            throw new \Exception('IMAP account with this email already exists');
        }

        // Encrypt password
        $encryptedPassword = $this->encryption->encrypt($data['password']);

        // Create account
        $account = new ImapAccount();
        $account->user_id = $userId;
        $account->email = $data['email'];
        $account->imap_host = $data['imap_host'];
        $account->imap_port = $data['imap_port'] ?? 993;
        $account->imap_username = $data['imap_username'] ?? $data['email'];
        $account->imap_password_encrypted = $encryptedPassword;
        $account->imap_encryption = $data['imap_encryption'] ?? 'ssl';
        $account->is_default = $data['is_default'] ?? false;
        $account->is_active = $data['is_active'] ?? true;
        $account->save();

        $this->logger->info('Personal IMAP account created', [
            'account_id' => $account->id,
            'user_id' => $userId,
            'email' => $account->email
        ]);

        return $account;
    }

    /**
     * Update personal IMAP account
     * 
     * @param int $accountId
     * @param int $userId User ID for ownership check
     * @param array $data
     * @return ImapAccount
     * @throws \Exception
     */
    public function updateAccount(int $accountId, int $userId, array $data): ImapAccount
    {
        $this->logger->info('Updating personal IMAP account', [
            'account_id' => $accountId,
            'user_id' => $userId
        ]);

        $account = $this->getAccount($accountId, $userId);
        if (!$account) {
            throw new \Exception("Account not found or access denied");
        }

        // Update fields
        if (isset($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Invalid email format');
            }
            $account->email = $data['email'];
        }

        if (isset($data['imap_host'])) {
            $account->imap_host = $data['imap_host'];
        }

        if (isset($data['imap_port'])) {
            $account->imap_port = (int)$data['imap_port'];
        }

        if (isset($data['imap_username'])) {
            $account->imap_username = $data['imap_username'];
        }

        if (isset($data['password'])) {
            $account->imap_password_encrypted = $this->encryption->encrypt($data['password']);
        }

        if (isset($data['imap_encryption'])) {
            $account->imap_encryption = $data['imap_encryption'];
        }

        if (isset($data['is_default'])) {
            $account->is_default = (bool)$data['is_default'];
        }

        if (isset($data['is_active'])) {
            $account->is_active = (bool)$data['is_active'];
        }

        $account->save();

        $this->logger->info('Personal IMAP account updated', [
            'account_id' => $accountId
        ]);

        return $account;
    }

    /**
     * Delete personal IMAP account
     * 
     * @param int $accountId
     * @param int $userId User ID for ownership check
     * @return bool
     * @throws \Exception
     */
    public function deleteAccount(int $accountId, int $userId): bool
    {
        $this->logger->info('Deleting personal IMAP account', [
            'account_id' => $accountId,
            'user_id' => $userId
        ]);

        $account = $this->getAccount($accountId, $userId);
        if (!$account) {
            throw new \Exception("Account not found or access denied");
        }

        $email = $account->email;
        $account->delete();

        $this->logger->info('Personal IMAP account deleted', [
            'account_id' => $accountId,
            'email' => $email
        ]);

        return true;
    }

    /**
     * Test IMAP connection
     * 
     * @param int $accountId
     * @param int $userId
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection(int $accountId, int $userId): array
    {
        $this->logger->info('Testing IMAP connection', [
            'account_id' => $accountId,
            'user_id' => $userId
        ]);

        $account = $this->getAccount($accountId, $userId);
        if (!$account) {
            return [
                'success' => false,
                'message' => 'Account not found or access denied'
            ];
        }

        try {
            // Decrypt password
            $password = $this->encryption->decrypt($account->imap_password_encrypted);

            // Try to connect (simplified - would use ImapClient in real implementation)
            $connection = @imap_open(
                "{{$account->imap_host}:{$account->imap_port}/imap/{$account->imap_encryption}}INBOX",
                $account->imap_username,
                $password
            );

            if ($connection) {
                imap_close($connection);
                
                $this->logger->info('IMAP connection test successful', [
                    'account_id' => $accountId
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Connection successful'
                ];
            } else {
                $error = imap_last_error();
                
                $this->logger->warning('IMAP connection test failed', [
                    'account_id' => $accountId,
                    'error' => $error
                ]);
                
                return [
                    'success' => false,
                    'message' => $error ?: 'Connection failed'
                ];
            }

        } catch (\Exception $e) {
            $this->logger->error('IMAP connection test error', [
                'account_id' => $accountId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}

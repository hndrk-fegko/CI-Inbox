<?php
declare(strict_types=1);

namespace CiInbox\App\Repositories;

use CiInbox\App\Models\ImapAccount;
use CiInbox\Modules\Logger\LoggerInterface;
use Illuminate\Support\Facades\DB;

/**
 * IMAP Account Repository
 * 
 * Datenbank-Operationen für IMAP-Accounts
 */
class ImapAccountRepository
{
    public function __construct(
        private LoggerInterface $logger
    ) {}
    
    /**
     * Account nach ID finden
     */
    public function find(int $id): ?ImapAccount
    {
        return ImapAccount::find($id);
    }
    
    /**
     * Account nach E-Mail-Adresse finden
     */
    public function findByEmail(string $email, int $userId): ?ImapAccount
    {
        return ImapAccount::where('email', $email)
            ->where('user_id', $userId)
            ->first();
    }
    
    /**
     * Alle Accounts eines Users
     */
    public function getAllByUser(int $userId): array
    {
        return ImapAccount::where('user_id', $userId)
            ->orderBy('is_default', 'desc')
            ->orderBy('email', 'asc')
            ->get()
            ->all();
    }
    
    /**
     * Alle aktiven Accounts (für Polling)
     */
    public function getActiveAccounts(): array
    {
        return ImapAccount::where('is_active', true)
            ->orderBy('last_sync_at', 'asc')  // Oldest first
            ->get()
            ->all();
    }
    
    /**
     * Account erstellen
     */
    public function create(array $data): ImapAccount
    {
        try {
            $account = ImapAccount::create($data);
            
            $this->logger->info('[SUCCESS] IMAP account created', [
                'account_id' => $account->id,
                'email' => $data['email'] ?? 'unknown',
                'user_id' => $data['user_id'] ?? null
            ]);
            
            return $account;
        } catch (\Exception $e) {
            $this->logger->error('Failed to create IMAP account', [
                'email' => $data['email'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Account aktualisieren
     */
    public function update(int $id, array $data): bool
    {
        $account = $this->find($id);
        if (!$account) {
            $this->logger->warning('IMAP account update failed - not found', ['account_id' => $id]);
            return false;
        }
        
        try {
            $result = $account->update($data);
            
            $this->logger->info('[SUCCESS] IMAP account updated', [
                'account_id' => $id,
                'fields' => array_keys($data)
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to update IMAP account', [
                'account_id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Account löschen
     */
    public function delete(int $id): bool
    {
        $account = $this->find($id);
        if (!$account) {
            $this->logger->warning('IMAP account delete failed - not found', ['account_id' => $id]);
            return false;
        }
        
        try {
            $email = $account->email;
            $result = $account->delete();
            
            $this->logger->info('[SUCCESS] IMAP account deleted', [
                'account_id' => $id,
                'email' => $email
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete IMAP account', [
                'account_id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Last-Sync-Timestamp aktualisieren
     */
    public function updateLastSync(int $id, ?string $error = null): bool
    {
        $account = $this->find($id);
        if (!$account) {
            return false;
        }
        
        $data = [
            'last_sync_at' => date('Y-m-d H:i:s'),
            'sync_count' => $account->sync_count + 1,
        ];
        
        if ($error !== null) {
            $data['last_error'] = $error;
        } else {
            $data['last_error'] = null;  // Clear error on success
        }
        
        return $account->update($data);
    }
    
    /**
     * Account deaktivieren (z.B. nach wiederholten Fehlern)
     */
    public function deactivate(int $id, string $reason): bool
    {
        $account = $this->find($id);
        if (!$account) {
            $this->logger->warning('IMAP account deactivate failed - not found', ['account_id' => $id]);
            return false;
        }
        
        $result = $account->update([
            'is_active' => false,
            'last_error' => $reason,
        ]);
        
        $this->logger->warning('IMAP account deactivated', [
            'account_id' => $id,
            'email' => $account->email,
            'reason' => $reason
        ]);
        
        return $result;
    }
    
    /**
     * Account aktivieren
     */
    public function activate(int $id): bool
    {
        $account = $this->find($id);
        if (!$account) {
            $this->logger->warning('IMAP account activate failed - not found', ['account_id' => $id]);
            return false;
        }
        
        $result = $account->update([
            'is_active' => true,
            'last_error' => null,
        ]);
        
        $this->logger->info('[SUCCESS] IMAP account activated', [
            'account_id' => $id,
            'email' => $account->email
        ]);
        
        return $result;
    }
    
    /**
     * Standard-Account eines Users setzen
     */
    public function setDefault(int $id, int $userId): bool
    {
        DB::beginTransaction();
        try {
            // Alle anderen Accounts als nicht-default markieren
            ImapAccount::where('user_id', $userId)
                ->update(['is_default' => false]);
            
            // Diesen Account als default setzen
            $account = $this->find($id);
            if (!$account || $account->user_id !== $userId) {
                DB::rollBack();
                $this->logger->warning('Set default IMAP account failed - not found or wrong user', [
                    'account_id' => $id,
                    'user_id' => $userId
                ]);
                return false;
            }
            
            $account->update(['is_default' => true]);
            
            DB::commit();
            
            $this->logger->info('[SUCCESS] Default IMAP account set', [
                'account_id' => $id,
                'user_id' => $userId,
                'email' => $account->email
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logger->error('Failed to set default IMAP account', [
                'account_id' => $id,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Zählt aktive Accounts
     */
    public function countActiveAccounts(): int
    {
        return ImapAccount::where('is_active', true)->count();
    }
    
    /**
     * Holt Accounts mit Fehlern
     */
    public function getAccountsWithErrors(): array
    {
        return ImapAccount::whereNotNull('last_error')
            ->where('is_active', true)
            ->get()
            ->all();
    }
    
    /**
     * Holt Accounts die länger nicht synchronisiert wurden
     */
    public function getStaleAccounts(int $minutesThreshold = 60): array
    {
        $threshold = now()->subMinutes($minutesThreshold);
        
        return ImapAccount::where('is_active', true)
            ->where(function($query) use ($threshold) {
                $query->whereNull('last_sync_at')
                    ->orWhere('last_sync_at', '<', $threshold);
            })
            ->get()
            ->all();
    }
}

<?php

namespace CiInbox\App\Repositories;

use CiInbox\App\Models\Signature;
use Illuminate\Database\Eloquent\Collection;
use CiInbox\Modules\Logger\LoggerService;

class SignatureRepository
{
    public function __construct(
        private LoggerService $logger
    ) {}
    
    /**
     * Get all personal signatures for a user
     */
    public function getPersonalSignatures(int $userId): Collection
    {
        try {
            $signatures = Signature::personal($userId)
                ->orderBy('is_default', 'desc')
                ->orderBy('name')
                ->get();
            
            $this->logger->debug('SignatureRepository: Fetched personal signatures', [
                'user_id' => $userId,
                'count' => $signatures->count()
            ]);
            
            return $signatures;
        } catch (\Exception $e) {
            $this->logger->error('SignatureRepository: Failed to fetch personal signatures', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Get all global signatures (admin)
     */
    public function getGlobalSignatures(): Collection
    {
        try {
            $signatures = Signature::global()
                ->orderBy('is_default', 'desc')
                ->orderBy('name')
                ->get();
            
            $this->logger->debug('SignatureRepository: Fetched global signatures', [
                'count' => $signatures->count()
            ]);
            
            return $signatures;
        } catch (\Exception $e) {
            $this->logger->error('SignatureRepository: Failed to fetch global signatures', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Get signature by ID
     */
    public function find(int $id): ?Signature
    {
        try {
            $signature = Signature::find($id);
            
            if ($signature) {
                $this->logger->debug('SignatureRepository: Signature found', ['id' => $id]);
            } else {
                $this->logger->debug('SignatureRepository: Signature not found', ['id' => $id]);
            }
            
            return $signature;
        } catch (\Exception $e) {
            $this->logger->error('SignatureRepository: Failed to find signature', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Get signature by ID and user (security check)
     */
    public function findByUserOrGlobal(int $id, ?int $userId = null): ?Signature
    {
        try {
            $query = Signature::where('id', $id);
            
            if ($userId) {
                $query->where(function($q) use ($userId) {
                    $q->where('user_id', $userId)
                      ->orWhere('type', 'global');
                });
            } else {
                $query->where('type', 'global');
            }
            
            $signature = $query->first();
            
            if ($signature) {
                $this->logger->debug('SignatureRepository: Signature found with ownership check', [
                    'id' => $id,
                    'user_id' => $userId
                ]);
            } else {
                $this->logger->debug('SignatureRepository: Signature not found or access denied', [
                    'id' => $id,
                    'user_id' => $userId
                ]);
            }
            
            return $signature;
        } catch (\Exception $e) {
            $this->logger->error('SignatureRepository: Failed to find signature with ownership check', [
                'id' => $id,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Get default signature for user or global
     */
    public function getDefaultSignature(?int $userId = null): ?Signature
    {
        try {
            if ($userId) {
                // First try personal default
                $signature = Signature::personal($userId)->default()->first();
                if ($signature) {
                    $this->logger->debug('SignatureRepository: Found personal default signature', [
                        'user_id' => $userId,
                        'signature_id' => $signature->id
                    ]);
                    return $signature;
                }
            }
            
            // Fallback to global default
            $signature = Signature::global()->default()->first();
            if ($signature) {
                $this->logger->debug('SignatureRepository: Found global default signature', [
                    'signature_id' => $signature->id
                ]);
            } else {
                $this->logger->debug('SignatureRepository: No default signature found', [
                    'user_id' => $userId
                ]);
            }
            
            return $signature;
        } catch (\Exception $e) {
            $this->logger->error('SignatureRepository: Failed to get default signature', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Create signature
     */
    public function create(array $data): Signature
    {
        try {
            $signature = Signature::create($data);
            
            $this->logger->info('SignatureRepository: Signature created', [
                'signature_id' => $signature->id,
                'user_id' => $data['user_id'] ?? null,
                'type' => $data['type'],
                'name' => $data['name']
            ]);
            
            return $signature;
        } catch (\Exception $e) {
            $this->logger->error('SignatureRepository: Failed to create signature', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Update signature
     */
    public function update(int $id, array $data): bool
    {
        try {
            $signature = Signature::find($id);
            if (!$signature) {
                $this->logger->warning('SignatureRepository: Signature not found for update', ['id' => $id]);
                return false;
            }
            
            $result = $signature->update($data);
            
            if ($result) {
                $this->logger->info('SignatureRepository: Signature updated', [
                    'signature_id' => $id,
                    'changes' => array_keys($data)
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('SignatureRepository: Failed to update signature', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Delete signature
     */
    public function delete(int $id): bool
    {
        try {
            $signature = Signature::find($id);
            if (!$signature) {
                $this->logger->warning('SignatureRepository: Signature not found for delete', ['id' => $id]);
                return false;
            }
            
            $result = $signature->delete();
            
            if ($result) {
                $this->logger->info('SignatureRepository: Signature deleted', ['signature_id' => $id]);
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('SignatureRepository: Failed to delete signature', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Set signature as default (unset others)
     */
    public function setAsDefault(int $id, ?int $userId = null): bool
    {
        try {
            $signature = Signature::find($id);
            if (!$signature) {
                $this->logger->warning('SignatureRepository: Signature not found for setAsDefault', ['id' => $id]);
                return false;
            }
            
            // Unset other defaults for same type/user
            if ($signature->type === 'global') {
                Signature::global()->update(['is_default' => false]);
            } else {
                Signature::personal($userId)->update(['is_default' => false]);
            }
            
            // Set this one as default
            $signature->is_default = true;
            $result = $signature->save();
            
            if ($result) {
                $this->logger->info('SignatureRepository: Signature set as default', [
                    'signature_id' => $id,
                    'user_id' => $userId,
                    'type' => $signature->type
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('SignatureRepository: Failed to set signature as default', [
                'id' => $id,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Check if SMTP is configured (for personal signatures visibility)
     */
    public function isSmtpConfigured(): bool
    {
        // Check if system_settings table exists and has SMTP config
        try {
            $smtpHost = \Illuminate\Database\Capsule\Manager::table('system_settings')
                ->where('setting_key', 'smtp.host')
                ->value('setting_value');
            
            $configured = !empty($smtpHost);
            
            $this->logger->debug('SignatureRepository: SMTP configuration check', [
                'configured' => $configured
            ]);
            
            return $configured;
        } catch (\Exception $e) {
            $this->logger->debug('SignatureRepository: SMTP configuration check failed (table may not exist)', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}

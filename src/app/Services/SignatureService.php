<?php

namespace App\Services;

use App\Repositories\SignatureRepository;
use CiInbox\App\Models\Signature;
use Psr\Log\LoggerInterface;

class SignatureService
{
    private SignatureRepository $repository;
    private LoggerInterface $logger;
    
    public function __construct(
        SignatureRepository $repository,
        LoggerInterface $logger
    ) {
        $this->repository = $repository;
        $this->logger = $logger;
    }
    
    /**
     * Get all personal signatures for user
     */
    public function getPersonalSignatures(int $userId): array
    {
        try {
            $signatures = $this->repository->getPersonalSignatures($userId);
            
            return [
                'success' => true,
                'data' => $signatures->map(function($sig) {
                    return [
                        'id' => $sig->id,
                        'name' => $sig->name,
                        'content' => $sig->content,
                        'type' => $sig->type,
                        'is_default' => $sig->is_default,
                        'created_at' => $sig->created_at->toIso8601String()
                    ];
                })->toArray()
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to get personal signatures', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to retrieve signatures'
            ];
        }
    }
    
    /**
     * Get all signatures for user (personal + global)
     */
    public function getAllSignaturesForUser(int $userId): array
    {
        try {
            // Get personal signatures
            $personalSignatures = $this->repository->getPersonalSignatures($userId);
            
            // Get global signatures
            $globalSignatures = $this->repository->getGlobalSignatures();
            
            // Merge and format
            $allSignatures = $personalSignatures->merge($globalSignatures)->map(function($sig) {
                return [
                    'id' => $sig->id,
                    'name' => $sig->name,
                    'content' => $sig->content,
                    'type' => $sig->type,
                    'is_default' => $sig->is_default,
                    'created_at' => $sig->created_at->toIso8601String()
                ];
            })->sortBy('type')->values()->toArray();
            
            return [
                'success' => true,
                'data' => $allSignatures
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to get all signatures for user', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to retrieve signatures'
            ];
        }
    }
    
    /**
     * Get all global signatures (admin)
     */
    public function getGlobalSignatures(): array
    {
        try {
            $signatures = $this->repository->getGlobalSignatures();
            
            return [
                'success' => true,
                'data' => $signatures->map(function($sig) {
                    return [
                        'id' => $sig->id,
                        'name' => $sig->name,
                        'content' => $sig->content,
                        'type' => $sig->type,
                        'is_default' => $sig->is_default,
                        'created_at' => $sig->created_at->toIso8601String()
                    ];
                })->toArray()
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to get global signatures', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to retrieve global signatures'
            ];
        }
    }
    
    /**
     * Get all signatures (global + personal) - Admin only
     */
    public function getAllSignatures(): array
    {
        try {
            // Get all signatures from database
            $allSignatures = \App\Models\Signature::orderBy('type')
                ->orderBy('is_default', 'desc')
                ->orderBy('name')
                ->get();
            
            return [
                'success' => true,
                'data' => $allSignatures->map(function($sig) {
                    return [
                        'id' => $sig->id,
                        'user_id' => $sig->user_id,
                        'name' => $sig->name,
                        'content' => $sig->content,
                        'type' => $sig->type,
                        'is_default' => $sig->is_default,
                        'created_at' => $sig->created_at->toIso8601String()
                    ];
                })->toArray()
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to get all signatures', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to retrieve signatures'
            ];
        }
    }
    
    /**
     * Get signature by ID
     */
    public function getSignature(int $id, ?int $userId = null): array
    {
        try {
            $signature = $this->repository->findByUserOrGlobal($id, $userId);
            
            if (!$signature) {
                return [
                    'success' => false,
                    'error' => 'Signature not found'
                ];
            }
            
            return [
                'success' => true,
                'data' => [
                    'id' => $signature->id,
                    'name' => $signature->name,
                    'content' => $signature->content,
                    'is_default' => $signature->is_default,
                    'type' => $signature->type
                ]
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to get signature', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to retrieve signature'
            ];
        }
    }
    
    /**
     * Create personal signature
     */
    public function createPersonalSignature(int $userId, array $data): array
    {
        try {
            // Validate
            if (empty($data['name']) || !isset($data['content'])) {
                return [
                    'success' => false,
                    'error' => 'Name and content are required'
                ];
            }
            
            if (strlen($data['name']) > 255) {
                return [
                    'success' => false,
                    'error' => 'Name is too long (max 255 characters)'
                ];
            }
            
            $signature = $this->repository->create([
                'user_id' => $userId,
                'type' => 'personal',
                'name' => $data['name'],
                'content' => $data['content'],
                'is_default' => $data['is_default'] ?? false
            ]);
            
            // If set as default, unset others
            if ($signature->is_default) {
                $this->repository->setAsDefault($signature->id, $userId);
            }
            
            $this->logger->info('Personal signature created', [
                'user_id' => $userId,
                'signature_id' => $signature->id
            ]);
            
            return [
                'success' => true,
                'signature' => [
                    'id' => $signature->id,
                    'name' => $signature->name,
                    'content' => $signature->content,
                    'is_default' => $signature->is_default
                ]
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to create personal signature', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to create signature'
            ];
        }
    }
    
    /**
     * Create global signature (admin)
     */
    public function createGlobalSignature(array $data): array
    {
        try {
            // Validate
            if (empty($data['name']) || !isset($data['content'])) {
                return [
                    'success' => false,
                    'error' => 'Name and content are required'
                ];
            }
            
            if (strlen($data['name']) > 255) {
                return [
                    'success' => false,
                    'error' => 'Name is too long (max 255 characters)'
                ];
            }
            
            $signature = $this->repository->create([
                'user_id' => null,
                'type' => 'global',
                'name' => $data['name'],
                'content' => $data['content'],
                'is_default' => $data['is_default'] ?? false
            ]);
            
            // If set as default, unset others
            if ($signature->is_default) {
                $this->repository->setAsDefault($signature->id, null);
            }
            
            $this->logger->info('Global signature created', [
                'signature_id' => $signature->id
            ]);
            
            return [
                'success' => true,
                'signature' => [
                    'id' => $signature->id,
                    'name' => $signature->name,
                    'content' => $signature->content,
                    'is_default' => $signature->is_default
                ]
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to create global signature', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to create signature'
            ];
        }
    }
    
    /**
     * Update signature
     */
    public function updateSignature(int $id, array $data, ?int $userId = null): array
    {
        try {
            // Check ownership
            $signature = $this->repository->findByUserOrGlobal($id, $userId);
            if (!$signature) {
                return [
                    'success' => false,
                    'error' => 'Signature not found'
                ];
            }
            
            // Validate
            if (isset($data['name']) && strlen($data['name']) > 255) {
                return [
                    'success' => false,
                    'error' => 'Name is too long (max 255 characters)'
                ];
            }
            
            $updateData = [];
            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }
            if (isset($data['content'])) {
                $updateData['content'] = $data['content'];
            }
            if (isset($data['is_default'])) {
                $updateData['is_default'] = $data['is_default'];
            }
            
            $this->repository->update($id, $updateData);
            
            // If set as default, unset others
            if (isset($data['is_default']) && $data['is_default']) {
                $this->repository->setAsDefault($id, $userId);
            }
            
            $this->logger->info('Signature updated', [
                'signature_id' => $id,
                'user_id' => $userId
            ]);
            
            return ['success' => true];
        } catch (\Exception $e) {
            $this->logger->error('Failed to update signature', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to update signature'
            ];
        }
    }
    
    /**
     * Delete signature
     */
    public function deleteSignature(int $id, ?int $userId = null): array
    {
        try {
            // Check ownership
            $signature = $this->repository->findByUserOrGlobal($id, $userId);
            if (!$signature) {
                return [
                    'success' => false,
                    'error' => 'Signature not found'
                ];
            }
            
            $this->repository->delete($id);
            
            $this->logger->info('Signature deleted', [
                'signature_id' => $id,
                'user_id' => $userId
            ]);
            
            return ['success' => true];
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete signature', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to delete signature'
            ];
        }
    }
    
    /**
     * Set signature as default
     */
    public function setAsDefault(int $id, ?int $userId = null): array
    {
        try {
            // Check ownership
            $signature = $this->repository->findByUserOrGlobal($id, $userId);
            if (!$signature) {
                return [
                    'success' => false,
                    'error' => 'Signature not found'
                ];
            }
            
            $this->repository->setAsDefault($id, $userId);
            
            $this->logger->info('Signature set as default', [
                'signature_id' => $id,
                'user_id' => $userId
            ]);
            
            return ['success' => true];
        } catch (\Exception $e) {
            $this->logger->error('Failed to set default signature', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to set as default'
            ];
        }
    }
    
    /**
     * Check if SMTP is configured
     */
    public function isSmtpConfigured(): bool
    {
        return $this->repository->isSmtpConfigured();
    }
}

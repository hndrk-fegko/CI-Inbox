<?php

namespace CiInbox\Modules\Label;

use CiInbox\Modules\Label\Exceptions\LabelException;
use CiInbox\Modules\Logger\LoggerService;
use CiInbox\App\Repositories\LabelRepository;

/**
 * Class LabelManager
 * 
 * Label-Management für Thread-Kategorisierung
 * 
 * Features:
 * - System-Labels (Inbox, Sent, Trash, etc.)
 * - Custom Labels mit Farben
 * - Thread-Label Zuweisungen
 * - Label-Validierung
 * 
 * @package CiInbox\Modules\Label
 */
class LabelManager implements LabelManagerInterface
{
    private array $config;
    
    public function __construct(
        private LabelRepository $repository,
        private LoggerService $logger,
        array $config = []
    ) {
        $this->config = $config;
    }
    
    /**
     * {@inheritdoc}
     */
    public function createLabel(
        string $name,
        ?string $color = null,
        bool $isSystemLabel = false,
        int $displayOrder = 0
    ): int {
        $this->logger->debug('Creating label', [
            'name' => $name,
            'color' => $color,
            'is_system' => $isSystemLabel
        ]);
        
        // Validierung
        if (!$this->validateName($name)) {
            throw LabelException::invalidName($name);
        }
        
        if ($color !== null && !$this->validateColor($color)) {
            throw LabelException::invalidColor($color);
        }
        
        // Prüfen ob Name bereits existiert
        if ($this->getLabelByName($name) !== null) {
            throw LabelException::alreadyExists($name);
        }
        
        // Standard-Farbe wenn keine angegeben
        if ($color === null) {
            $color = $this->getDefaultColor();
        }
        
        $labelId = $this->repository->create([
            'name' => $name,
            'color' => $color,
            'is_system_label' => $isSystemLabel,
            'display_order' => $displayOrder
        ]);
        
        $this->logger->info('Label created', [
            'label_id' => $labelId,
            'name' => $name
        ]);
        
        return $labelId;
    }
    
    /**
     * {@inheritdoc}
     */
    public function updateLabel(int $labelId, array $data): bool
    {
        $this->logger->debug('Updating label', [
            'label_id' => $labelId,
            'data' => $data
        ]);
        
        $label = $this->getLabel($labelId);
        if ($label === null) {
            throw LabelException::notFound($labelId);
        }
        
        // System-Labels: Nur display_order änderbar
        if ($label->is_system_label) {
            if (isset($data['name']) && $data['name'] !== $label->name) {
                throw LabelException::cannotModifySystemLabel($label->name);
            }
            if (isset($data['is_system_label']) && $data['is_system_label'] !== $label->is_system_label) {
                throw LabelException::cannotModifySystemLabel($label->name);
            }
        }
        
        // Validierung
        if (isset($data['name']) && !$this->validateName($data['name'])) {
            throw LabelException::invalidName($data['name']);
        }
        
        if (isset($data['color']) && !$this->validateColor($data['color'])) {
            throw LabelException::invalidColor($data['color']);
        }
        
        // Name-Duplikat prüfen
        if (isset($data['name']) && $data['name'] !== $label->name) {
            if ($this->getLabelByName($data['name']) !== null) {
                throw LabelException::alreadyExists($data['name']);
            }
        }
        
        $success = $this->repository->update($labelId, $data);
        
        if ($success) {
            $this->logger->info('Label updated', [
                'label_id' => $labelId,
                'updated_fields' => array_keys($data)
            ]);
        }
        
        return $success;
    }
    
    /**
     * {@inheritdoc}
     */
    public function deleteLabel(int $labelId): bool
    {
        $this->logger->debug('Deleting label', ['label_id' => $labelId]);
        
        $label = $this->getLabel($labelId);
        if ($label === null) {
            throw LabelException::notFound($labelId);
        }
        
        // System-Labels können nicht gelöscht werden
        if ($label->is_system_label) {
            throw LabelException::cannotDeleteSystemLabel($label->name);
        }
        
        $success = $this->repository->delete($labelId);
        
        if ($success) {
            $this->logger->info('Label deleted', [
                'label_id' => $labelId,
                'name' => $label->name
            ]);
        }
        
        return $success;
    }
    
    /**
     * {@inheritdoc}
     */
    public function addLabelToThread(int $threadId, int $labelId): bool
    {
        $this->logger->debug('Adding label to thread', [
            'thread_id' => $threadId,
            'label_id' => $labelId
        ]);
        
        // Prüfen ob Label existiert
        if (!$this->labelExists($labelId)) {
            throw LabelException::notFound($labelId);
        }
        
        // Prüfen ob bereits zugewiesen
        if ($this->threadHasLabel($threadId, $labelId)) {
            $this->logger->debug('Label already assigned to thread', [
                'thread_id' => $threadId,
                'label_id' => $labelId
            ]);
            return true; // Idempotent
        }
        
        $success = $this->repository->attachToThread($threadId, $labelId);
        
        if ($success) {
            $label = $this->getLabel($labelId);
            $this->logger->info('Label added to thread', [
                'thread_id' => $threadId,
                'label_id' => $labelId,
                'label_name' => $label->name ?? 'unknown'
            ]);
        }
        
        return $success;
    }
    
    /**
     * {@inheritdoc}
     */
    public function removeLabelFromThread(int $threadId, int $labelId): bool
    {
        $this->logger->debug('Removing label from thread', [
            'thread_id' => $threadId,
            'label_id' => $labelId
        ]);
        
        $success = $this->repository->detachFromThread($threadId, $labelId);
        
        if ($success) {
            $label = $this->getLabel($labelId);
            $this->logger->info('Label removed from thread', [
                'thread_id' => $threadId,
                'label_id' => $labelId,
                'label_name' => $label->name ?? 'unknown'
            ]);
        }
        
        return $success;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getThreadLabels(int $threadId): array
    {
        return $this->repository->getThreadLabels($threadId);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getThreadsByLabel(int $labelId): array
    {
        return $this->repository->getThreadsByLabel($labelId);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getAllLabels(?bool $systemOnly = null): array
    {
        return $this->repository->getAll($systemOnly);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getLabel(int $labelId): ?object
    {
        return $this->repository->find($labelId);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getLabelByName(string $name): ?object
    {
        return $this->repository->findByName($name);
    }
    
    /**
     * {@inheritdoc}
     */
    public function labelExists(int $labelId): bool
    {
        return $this->getLabel($labelId) !== null;
    }
    
    /**
     * {@inheritdoc}
     */
    public function threadHasLabel(int $threadId, int $labelId): bool
    {
        $labels = $this->getThreadLabels($threadId);
        foreach ($labels as $label) {
            if ($label->id === $labelId) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function initializeSystemLabels(): array
    {
        $this->logger->info('Initializing system labels');
        
        $systemLabels = $this->config['system_labels'] ?? [];
        $createdIds = [];
        
        foreach ($systemLabels as $labelData) {
            $name = $labelData['name'];
            
            // Prüfen ob bereits existiert
            if ($this->getLabelByName($name) !== null) {
                $this->logger->debug('System label already exists', ['name' => $name]);
                continue;
            }
            
            $labelId = $this->createLabel(
                name: $name,
                color: $labelData['color'] ?? null,
                isSystemLabel: true,
                displayOrder: $labelData['display_order'] ?? 0
            );
            
            $createdIds[] = $labelId;
        }
        
        $this->logger->info('System labels initialized', [
            'created_count' => count($createdIds),
            'label_ids' => $createdIds
        ]);
        
        return $createdIds;
    }
    
    /**
     * {@inheritdoc}
     */
    public function validateName(string $name): bool
    {
        $minLength = $this->config['validation']['name_min_length'] ?? 2;
        $maxLength = $this->config['validation']['name_max_length'] ?? 50;
        
        $length = mb_strlen($name);
        return $length >= $minLength && $length <= $maxLength;
    }
    
    /**
     * {@inheritdoc}
     */
    public function validateColor(string $color): bool
    {
        $pattern = $this->config['validation']['color_pattern'] ?? '/^#[0-9A-Fa-f]{6}$/';
        return preg_match($pattern, $color) === 1;
    }
    
    /**
     * Holt Standard-Farbe aus Konfiguration
     * 
     * @return string Hex-Farbe
     */
    private function getDefaultColor(): string
    {
        $colors = $this->config['default_custom_colors'] ?? ['#1a73e8'];
        return $colors[array_rand($colors)];
    }
}

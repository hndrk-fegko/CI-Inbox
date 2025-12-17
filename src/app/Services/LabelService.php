<?php

namespace CiInbox\App\Services;

use CiInbox\Modules\Label\LabelManager;
use CiInbox\Modules\Label\Exceptions\LabelException;
use CiInbox\App\Repositories\LabelRepository;
use CiInbox\App\Repositories\ThreadRepository;
use CiInbox\Modules\Logger\LoggerService;

/**
 * Class LabelService
 * 
 * Business Logic für Label-Management
 * 
 * Features:
 * - Label CRUD mit Validierung
 * - Thread-Label Zuweisungen
 * - Label-Statistiken
 * - System-Label Schutz
 * 
 * @package CiInbox\App\Services
 */
class LabelService
{
    public function __construct(
        private LabelManager $labelManager,
        private LabelRepository $labelRepository,
        private ThreadRepository $threadRepository,
        private LoggerService $logger
    ) {}
    
    /**
     * Label erstellen mit Validierung
     * 
     * @param string $name Label-Name
     * @param string|null $color Hex-Farbe (z.B. '#FF5733')
     * @param int $displayOrder Anzeigereihenfolge
     * @return int Label-ID
     * @throws LabelException Bei Validierungs-Fehlern
     */
    public function createLabel(string $name, ?string $color = null, int $displayOrder = 0): int
    {
        $this->logger->info('Creating label', [
            'name' => $name,
            'color' => $color
        ]);
        
        try {
            $labelId = $this->labelManager->createLabel(
                name: $name,
                color: $color,
                isSystemLabel: false,
                displayOrder: $displayOrder
            );
            
            $this->logger->info('Label created successfully', [
                'label_id' => $labelId,
                'name' => $name
            ]);
            
            return $labelId;
        } catch (LabelException $e) {
            $this->logger->error('Failed to create label', [
                'name' => $name,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Label aktualisieren mit Validierung
     * 
     * @param int $labelId Label-ID
     * @param array $data ['name' => '...', 'color' => '...', 'display_order' => 0]
     * @return bool Success
     * @throws LabelException Bei Validierungs-Fehlern
     */
    public function updateLabel(int $labelId, array $data): bool
    {
        $this->logger->info('Updating label', [
            'label_id' => $labelId,
            'fields' => array_keys($data)
        ]);
        
        try {
            $success = $this->labelManager->updateLabel($labelId, $data);
            
            if ($success) {
                $this->logger->info('Label updated successfully', [
                    'label_id' => $labelId
                ]);
            }
            
            return $success;
        } catch (LabelException $e) {
            $this->logger->error('Failed to update label', [
                'label_id' => $labelId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Label löschen (mit Sicherheitsprüfung)
     * 
     * @param int $labelId Label-ID
     * @return bool Success
     * @throws LabelException Wenn System-Label oder nicht gefunden
     */
    public function deleteLabel(int $labelId): bool
    {
        $this->logger->info('Deleting label', ['label_id' => $labelId]);
        
        try {
            $success = $this->labelManager->deleteLabel($labelId);
            
            if ($success) {
                $this->logger->info('Label deleted successfully', [
                    'label_id' => $labelId
                ]);
            }
            
            return $success;
        } catch (LabelException $e) {
            $this->logger->warning('Failed to delete label', [
                'label_id' => $labelId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Label zu Thread hinzufügen
     * 
     * @param int $threadId Thread-ID
     * @param int $labelId Label-ID
     * @return bool Success
     * @throws LabelException Bei ungültigen IDs
     */
    public function tagThread(int $threadId, int $labelId): bool
    {
        $this->logger->debug('Tagging thread with label', [
            'thread_id' => $threadId,
            'label_id' => $labelId
        ]);
        
        // Thread-Existenz prüfen
        $thread = $this->threadRepository->find($threadId);
        if (!$thread) {
            $this->logger->warning('Thread not found', ['thread_id' => $threadId]);
            throw new LabelException("Thread {$threadId} not found.");
        }
        
        try {
            $success = $this->labelManager->addLabelToThread($threadId, $labelId);
            
            if ($success) {
                $label = $this->labelManager->getLabel($labelId);
                $this->logger->info('Thread tagged successfully', [
                    'thread_id' => $threadId,
                    'label_id' => $labelId,
                    'label_name' => $label->name ?? 'unknown'
                ]);
            }
            
            return $success;
        } catch (LabelException $e) {
            $this->logger->error('Failed to tag thread', [
                'thread_id' => $threadId,
                'label_id' => $labelId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Label von Thread entfernen
     * 
     * @param int $threadId Thread-ID
     * @param int $labelId Label-ID
     * @return bool Success
     */
    public function untagThread(int $threadId, int $labelId): bool
    {
        $this->logger->debug('Untagging thread', [
            'thread_id' => $threadId,
            'label_id' => $labelId
        ]);
        
        $success = $this->labelManager->removeLabelFromThread($threadId, $labelId);
        
        if ($success) {
            $this->logger->info('Thread untagged successfully', [
                'thread_id' => $threadId,
                'label_id' => $labelId
            ]);
        }
        
        return $success;
    }
    
    /**
     * Alle Labels eines Threads abrufen
     * 
     * @param int $threadId Thread-ID
     * @return array<object> Labels
     */
    public function getThreadLabels(int $threadId): array
    {
        return $this->labelManager->getThreadLabels($threadId);
    }
    
    /**
     * Thread-Übersicht nach Label filtern
     * 
     * @param int $labelId Label-ID
     * @param array $options Filter-Optionen ['limit' => 50, 'offset' => 0]
     * @return array ['threads' => [...], 'total' => 10]
     */
    public function getThreadsByLabel(int $labelId, array $options = []): array
    {
        $this->logger->debug('Fetching threads by label', [
            'label_id' => $labelId,
            'options' => $options
        ]);
        
        $threadIds = $this->labelManager->getThreadsByLabel($labelId);
        
        if (empty($threadIds)) {
            return ['threads' => [], 'total' => 0];
        }
        
        // Pagination
        $limit = $options['limit'] ?? 50;
        $offset = $options['offset'] ?? 0;
        
        $total = count($threadIds);
        $threadIds = array_slice($threadIds, $offset, $limit);
        
        // Threads abrufen
        $threads = [];
        foreach ($threadIds as $threadId) {
            $thread = $this->threadRepository->find($threadId);
            if ($thread) {
                $threads[] = $thread;
            }
        }
        
        $this->logger->debug('Threads fetched by label', [
            'label_id' => $labelId,
            'returned' => count($threads),
            'total' => $total
        ]);
        
        return [
            'threads' => $threads,
            'total' => $total
        ];
    }
    
    /**
     * Alle Labels abrufen (mit Filter)
     * 
     * @param bool|null $systemOnly Nur System-Labels? null = alle
     * @return array<object> Labels
     */
    public function getAllLabels(?bool $systemOnly = null): array
    {
        return $this->labelManager->getAllLabels($systemOnly);
    }
    
    /**
     * Label nach ID abrufen
     * 
     * @param int $labelId Label-ID
     * @return object|null Label oder null
     */
    public function getLabelById(int $labelId): ?object
    {
        return $this->labelRepository->find($labelId);
    }
    
    /**
     * Label nach Name abrufen
     * 
     * @param string $name Label-Name
     * @return object|null Label oder null
     */
    public function getLabelByName(string $name): ?object
    {
        return $this->labelManager->getLabelByName($name);
    }
    
    /**
     * System-Label-ID nach Name abrufen
     * 
     * @param string $name System-Label Name (z.B. 'Inbox')
     * @return int|null Label-ID oder null
     */
    public function getSystemLabelId(string $name): ?int
    {
        $label = $this->getLabelByName($name);
        
        if ($label && $label->is_system) {
            return $label->id;
        }
        
        return null;
    }
    
    /**
     * Statistik: Anzahl Threads pro Label
     * 
     * @return array<array> ['label_id', 'label_name', 'thread_count', 'color']
     */
    public function getLabelStatistics(): array
    {
        $this->logger->debug('Generating label statistics');
        
        $labels = $this->labelRepository->getAllWithThreadCount();
        
        $statistics = [];
        foreach ($labels as $label) {
            $statistics[] = [
                'label_id' => $label->id,
                'label_name' => $label->name,
                'thread_count' => $label->thread_count ?? 0,
                'color' => $label->color,
                'is_system' => $label->is_system
            ];
        }
        
        // Sortieren nach thread_count DESC
        usort($statistics, fn($a, $b) => $b['thread_count'] <=> $a['thread_count']);
        
        $this->logger->debug('Label statistics generated', [
            'label_count' => count($statistics)
        ]);
        
        return $statistics;
    }
    
    /**
     * System-Labels initialisieren
     * 
     * @return array<int> Created Label IDs
     */
    public function initializeSystemLabels(): array
    {
        $this->logger->info('Initializing system labels');
        
        try {
            $labelIds = $this->labelManager->initializeSystemLabels();
            
            $this->logger->info('System labels initialized', [
                'created_count' => count($labelIds)
            ]);
            
            return $labelIds;
        } catch (\Exception $e) {
            $this->logger->error('Failed to initialize system labels', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Thread-Übersicht mit Labels anreichern
     * 
     * @param array<object> $threads Thread-Objekte
     * @return array<object> Threads mit 'labels' Property
     */
    public function enrichThreadsWithLabels(array $threads): array
    {
        foreach ($threads as &$thread) {
            $thread->labels = $this->getThreadLabels($thread->id);
        }
        
        return $threads;
    }
    
    /**
     * Batch-Operation: Mehrere Labels zu Thread hinzufügen
     * 
     * @param int $threadId Thread-ID
     * @param array<int> $labelIds Label-IDs
     * @return array ['success' => [...], 'failed' => [...]]
     */
    public function tagThreadBatch(int $threadId, array $labelIds): array
    {
        $this->logger->debug('Batch tagging thread', [
            'thread_id' => $threadId,
            'label_count' => count($labelIds)
        ]);
        
        $results = ['success' => [], 'failed' => []];
        
        foreach ($labelIds as $labelId) {
            try {
                if ($this->tagThread($threadId, $labelId)) {
                    $results['success'][] = $labelId;
                } else {
                    $results['failed'][] = $labelId;
                }
            } catch (LabelException $e) {
                $results['failed'][] = $labelId;
            }
        }
        
        $this->logger->info('Batch tagging completed', [
            'thread_id' => $threadId,
            'success_count' => count($results['success']),
            'failed_count' => count($results['failed'])
        ]);
        
        return $results;
    }
}

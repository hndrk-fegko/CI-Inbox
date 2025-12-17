<?php

namespace CiInbox\App\Repositories;

use CiInbox\App\Models\Label;
use CiInbox\Modules\Logger\LoggerInterface;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Class LabelRepository
 * 
 * Repository für Label-Datenbank-Operationen
 * 
 * Features:
 * - CRUD-Operationen für Labels
 * - Pivot-Tabellen-Operations (thread_labels)
 * - Query-Optimierung mit Indexes
 * 
 * @package CiInbox\App\Repositories
 */
class LabelRepository
{
    public function __construct(
        private LoggerInterface $logger
    ) {}
    
    /**
     * Label nach ID finden
     * 
     * @param int $id Label-ID
     * @return object|null Label oder null
     */
    public function find(int $id): ?object
    {
        $label = Label::find($id);
        return $label ? (object)$label->toArray() : null;
    }
    
    /**
     * Label nach Name finden
     * 
     * @param string $name Label-Name
     * @return object|null Label oder null
     */
    public function findByName(string $name): ?object
    {
        $label = Label::where('name', $name)->first();
        return $label ? (object)$label->toArray() : null;
    }
    
    /**
     * Alle Labels abrufen
     * 
     * @param bool|null $systemOnly Nur System-Labels? null = alle
     * @return array<object> Labels sortiert nach display_order
     */
    public function getAll(?bool $systemOnly = null): array
    {
        $query = Label::query();
        
        if ($systemOnly !== null) {
            $query->where('is_system', $systemOnly);
        }
        
        $labels = $query->orderBy('display_order', 'asc')
                       ->orderBy('name', 'asc')
                       ->get();
        
        return $labels->map(fn($label) => (object)$label->toArray())->toArray();
    }
    
    /**
     * System-Labels abrufen
     * 
     * @return array<object> System-Labels
     */
    public function getSystemLabels(): array
    {
        return $this->getAll(systemOnly: true);
    }
    
    /**
     * Custom Labels abrufen
     * 
     * @return array<object> Custom Labels
     */
    public function getCustomLabels(): array
    {
        return $this->getAll(systemOnly: false);
    }
    
    /**
     * Neues Label erstellen
     * 
     * @param array $data ['name', 'color', 'is_system', 'display_order']
     * @return int Label-ID
     */
    public function create(array $data): int
    {
        try {
            $label = Label::create([
                'name' => $data['name'],
                'color' => $data['color'] ?? null,
                'is_system' => $data['is_system'] ?? false,
                'display_order' => $data['display_order'] ?? 0
            ]);
            
            $this->logger->info('[SUCCESS] Label created', [
                'label_id' => $label->id,
                'name' => $data['name'],
                'is_system' => $data['is_system'] ?? false
            ]);
            
            return $label->id;
        } catch (\Exception $e) {
            $this->logger->error('Failed to create label', [
                'name' => $data['name'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Label aktualisieren
     * 
     * @param int $id Label-ID
     * @param array $data Zu aktualisierende Felder
     * @return bool Success
     */
    public function update(int $id, array $data): bool
    {
        $label = Label::find($id);
        if (!$label) {
            $this->logger->warning('Label update failed - not found', ['label_id' => $id]);
            return false;
        }
        
        try {
            $result = $label->update($data);
            
            $this->logger->info('[SUCCESS] Label updated', [
                'label_id' => $id,
                'fields' => array_keys($data)
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to update label', [
                'label_id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Label löschen
     * 
     * @param int $id Label-ID
     * @return bool Success
     */
    public function delete(int $id): bool
    {
        $label = Label::find($id);
        if (!$label) {
            $this->logger->warning('Label delete failed - not found', ['label_id' => $id]);
            return false;
        }
        
        try {
            $labelName = $label->name;
            $result = $label->delete();
            
            $this->logger->info('[SUCCESS] Label deleted', [
                'label_id' => $id,
                'name' => $labelName
            ]);
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete label', [
                'label_id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Label zu Thread zuweisen (Pivot-Tabelle)
     * 
     * @param int $threadId Thread-ID
     * @param int $labelId Label-ID
     * @return bool Success
     */
    public function attachToThread(int $threadId, int $labelId): bool
    {
        try {
            DB::table('thread_labels')->insert([
                'thread_id' => $threadId,
                'label_id' => $labelId
            ]);
            
            $this->logger->debug('Label attached to thread', [
                'thread_id' => $threadId,
                'label_id' => $labelId
            ]);
            
            return true;
        } catch (\Exception $e) {
            // Duplicate entry ignorieren
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                $this->logger->debug('Label already attached to thread (duplicate ignored)', [
                    'thread_id' => $threadId,
                    'label_id' => $labelId
                ]);
                return true;
            }
            
            $this->logger->error('Failed to attach label to thread', [
                'thread_id' => $threadId,
                'label_id' => $labelId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Label von Thread entfernen (Pivot-Tabelle)
     * 
     * @param int $threadId Thread-ID
     * @param int $labelId Label-ID
     * @return bool Success
     */
    public function detachFromThread(int $threadId, int $labelId): bool
    {
        $deleted = DB::table('thread_labels')
            ->where('thread_id', $threadId)
            ->where('label_id', $labelId)
            ->delete();
        
        if ($deleted > 0) {
            $this->logger->debug('Label detached from thread', [
                'thread_id' => $threadId,
                'label_id' => $labelId
            ]);
        }
        
        return $deleted > 0;
    }
    
    /**
     * Alle Labels eines Threads
     * 
     * @param int $threadId Thread-ID
     * @return array<object> Labels
     */
    public function getThreadLabels(int $threadId): array
    {
        $labels = DB::table('labels')
            ->join('thread_labels', 'labels.id', '=', 'thread_labels.label_id')
            ->where('thread_labels.thread_id', $threadId)
            ->orderBy('labels.display_order', 'asc')
            ->orderBy('labels.name', 'asc')
            ->select('labels.*')
            ->get();
        
        return $labels->map(fn($label) => (object)(array)$label)->toArray();
    }
    
    /**
     * Alle Threads mit einem Label
     * 
     * @param int $labelId Label-ID
     * @return array<int> Thread-IDs
     */
    public function getThreadsByLabel(int $labelId): array
    {
        $threadIds = DB::table('thread_labels')
            ->where('label_id', $labelId)
            ->pluck('thread_id')
            ->toArray();
        
        return $threadIds;
    }
    
    /**
     * Anzahl Threads pro Label
     * 
     * @param int $labelId Label-ID
     * @return int Thread-Count
     */
    public function getThreadCountByLabel(int $labelId): int
    {
        return DB::table('thread_labels')
            ->where('label_id', $labelId)
            ->count();
    }
    
    /**
     * Alle Labels mit Thread-Count
     * 
     * @return array<object> Labels mit thread_count Property
     */
    public function getAllWithThreadCount(): array
    {
        $labels = DB::table('labels')
            ->leftJoin('thread_labels', 'labels.id', '=', 'thread_labels.label_id')
            ->select(
                'labels.*',
                DB::raw('COUNT(thread_labels.thread_id) as thread_count')
            )
            ->groupBy('labels.id')
            ->orderBy('labels.display_order', 'asc')
            ->orderBy('labels.name', 'asc')
            ->get();
        
        return $labels->map(fn($label) => (object)(array)$label)->toArray();
    }
    
    /**
     * Entfernt alle Labels von einem Thread
     * 
     * @param int $threadId Thread-ID
     * @return int Anzahl entfernter Labels
     */
    public function detachAllFromThread(int $threadId): int
    {
        return DB::table('thread_labels')
            ->where('thread_id', $threadId)
            ->delete();
    }
    
    /**
     * Prüft ob Thread ein Label hat
     * 
     * @param int $threadId Thread-ID
     * @param int $labelId Label-ID
     * @return bool Hat Label?
     */
    public function threadHasLabel(int $threadId, int $labelId): bool
    {
        return DB::table('thread_labels')
            ->where('thread_id', $threadId)
            ->where('label_id', $labelId)
            ->exists();
    }
}

<?php

namespace CiInbox\Modules\Label;

use CiInbox\Modules\Label\Exceptions\LabelException;

/**
 * Interface LabelManagerInterface
 * 
 * Label-Management für Thread-Kategorisierung
 * Unterstützt System-Labels (Inbox, Sent, etc.) und Custom Labels
 * 
 * @package CiInbox\Modules\Label
 */
interface LabelManagerInterface
{
    /**
     * Erstellt ein neues Label
     * 
     * @param string $name Label-Name
     * @param string|null $color Hex-Farbe (z.B. '#FF5733')
     * @param bool $isSystemLabel System-Label?
     * @param int $displayOrder Anzeigereihenfolge
     * @return int Label-ID
     * @throws LabelException Bei Validierungs-Fehlern
     */
    public function createLabel(
        string $name,
        ?string $color = null,
        bool $isSystemLabel = false,
        int $displayOrder = 0
    ): int;
    
    /**
     * Aktualisiert ein Label
     * 
     * @param int $labelId Label-ID
     * @param array $data ['name' => '...', 'color' => '...', 'display_order' => 0]
     * @return bool Success
     * @throws LabelException Bei Validierungs-Fehlern oder wenn Label nicht existiert
     */
    public function updateLabel(int $labelId, array $data): bool;
    
    /**
     * Löscht ein Label (nur Custom Labels)
     * 
     * @param int $labelId Label-ID
     * @return bool Success
     * @throws LabelException Wenn System-Label oder Label nicht existiert
     */
    public function deleteLabel(int $labelId): bool;
    
    /**
     * Fügt Label zu Thread hinzu
     * 
     * @param int $threadId Thread-ID
     * @param int $labelId Label-ID
     * @return bool Success
     * @throws LabelException Bei DB-Fehlern oder wenn IDs ungültig
     */
    public function addLabelToThread(int $threadId, int $labelId): bool;
    
    /**
     * Entfernt Label von Thread
     * 
     * @param int $threadId Thread-ID
     * @param int $labelId Label-ID
     * @return bool Success
     * @throws LabelException Bei DB-Fehlern
     */
    public function removeLabelFromThread(int $threadId, int $labelId): bool;
    
    /**
     * Holt alle Labels eines Threads
     * 
     * @param int $threadId Thread-ID
     * @return array<object> Labels mit Properties: id, name, color, is_system
     */
    public function getThreadLabels(int $threadId): array;
    
    /**
     * Holt alle Threads mit einem Label
     * 
     * @param int $labelId Label-ID
     * @return array<int> Thread-IDs
     */
    public function getThreadsByLabel(int $labelId): array;
    
    /**
     * Holt alle verfügbaren Labels
     * 
     * @param bool|null $systemOnly Nur System-Labels? null = alle
     * @return array<object> Labels sortiert nach display_order
     */
    public function getAllLabels(?bool $systemOnly = null): array;
    
    /**
     * Holt Label nach ID
     * 
     * @param int $labelId Label-ID
     * @return object|null Label oder null wenn nicht gefunden
     */
    public function getLabel(int $labelId): ?object;
    
    /**
     * Holt Label nach Name
     * 
     * @param string $name Label-Name
     * @return object|null Label oder null wenn nicht gefunden
     */
    public function getLabelByName(string $name): ?object;
    
    /**
     * Prüft ob Label existiert
     * 
     * @param int $labelId Label-ID
     * @return bool Existiert?
     */
    public function labelExists(int $labelId): bool;
    
    /**
     * Prüft ob Thread ein bestimmtes Label hat
     * 
     * @param int $threadId Thread-ID
     * @param int $labelId Label-ID
     * @return bool Hat Label?
     */
    public function threadHasLabel(int $threadId, int $labelId): bool;
    
    /**
     * Initialisiert Standard-Labels (einmalig bei Installation)
     * 
     * @return array<int> Created Label IDs
     */
    public function initializeSystemLabels(): array;
    
    /**
     * Validiert Label-Name
     * 
     * @param string $name Label-Name
     * @return bool Valid?
     */
    public function validateName(string $name): bool;
    
    /**
     * Validiert Hex-Farbe
     * 
     * @param string $color Hex-Farbe (z.B. '#FF5733')
     * @return bool Valid?
     */
    public function validateColor(string $color): bool;
}

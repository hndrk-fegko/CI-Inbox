<?php

namespace CiInbox\Modules\Label\Exceptions;

use Exception;

/**
 * Class LabelException
 * 
 * Spezifische Exception für Label-Manager Fehler
 * 
 * @package CiInbox\Modules\Label\Exceptions
 */
class LabelException extends Exception
{
    /**
     * Validierungs-Fehler
     */
    public static function invalidName(string $name): self
    {
        return new self("Invalid label name: '{$name}'. Must be 2-50 characters.");
    }
    
    public static function invalidColor(string $color): self
    {
        return new self("Invalid label color: '{$color}'. Must be hex format: #RRGGBB");
    }
    
    /**
     * Label nicht gefunden
     */
    public static function notFound(int $labelId): self
    {
        return new self("Label with ID {$labelId} not found.");
    }
    
    public static function notFoundByName(string $name): self
    {
        return new self("Label with name '{$name}' not found.");
    }
    
    /**
     * Label bereits vorhanden
     */
    public static function alreadyExists(string $name): self
    {
        return new self("Label with name '{$name}' already exists.");
    }
    
    /**
     * System-Label Schutz
     */
    public static function cannotDeleteSystemLabel(string $name): self
    {
        return new self("Cannot delete system label: '{$name}'.");
    }
    
    public static function cannotModifySystemLabel(string $name): self
    {
        return new self("Cannot modify system label properties: '{$name}'.");
    }
    
    /**
     * Thread-Label Beziehung
     */
    public static function labelAlreadyAssigned(int $threadId, int $labelId): self
    {
        return new self("Label {$labelId} is already assigned to thread {$threadId}.");
    }
    
    public static function labelNotAssigned(int $threadId, int $labelId): self
    {
        return new self("Label {$labelId} is not assigned to thread {$threadId}.");
    }
    
    /**
     * Datenbank-Fehler
     */
    public static function databaseError(string $operation, string $message): self
    {
        return new self("Database error during {$operation}: {$message}");
    }
}

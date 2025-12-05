<?php

namespace CiInbox\App\Interfaces;

use CiInbox\App\DTOs\ModuleHealthDTO;

/**
 * Interface für Module Health Checks
 * 
 * Jedes Modul im System sollte dieses Interface implementieren,
 * um Health-Status und Self-Tests bereitzustellen.
 */
interface ModuleHealthInterface
{
    /**
     * Gibt den aktuellen Health-Status des Moduls zurück
     * 
     * @return ModuleHealthDTO Health-Status mit Metriken
     */
    public function getHealthStatus(): ModuleHealthDTO;

    /**
     * Führt einen Self-Test des Moduls durch
     * 
     * Dieser Test sollte die Kernfunktionalität des Moduls
     * verifizieren ohne externe Abhängigkeiten zu stark zu belasten.
     * 
     * @return bool true wenn Test erfolgreich, false sonst
     */
    public function runHealthTest(): bool;

    /**
     * Gibt den Namen des Moduls zurück
     * 
     * @return string Modulname (z.B. "logger", "config", "encryption")
     */
    public function getModuleName(): string;
}

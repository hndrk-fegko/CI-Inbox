<?php

namespace CiInbox\Core;

use CiInbox\Modules\Logger\LoggerInterface;

/**
 * Module Loader
 * 
 * Discovers and loads modules from src/modules/.
 * Reads module.json manifests and registers hooks.
 */
class ModuleLoader
{
    private string $modulesPath;
    private HookManager $hookManager;
    private LoggerInterface $logger;
    private array $loadedModules = [];

    public function __construct(
        string $modulesPath,
        HookManager $hookManager,
        LoggerInterface $logger
    ) {
        $this->modulesPath = $modulesPath;
        $this->hookManager = $hookManager;
        $this->logger = $logger;
    }

    /**
     * Load all modules
     */
    public function loadAll(): void
    {
        $moduleDirs = glob($this->modulesPath . '/*', GLOB_ONLYDIR);

        foreach ($moduleDirs as $moduleDir) {
            $this->loadModule($moduleDir);
        }

        $this->logger->info('Modules loaded', [
            'count' => count($this->loadedModules),
            'modules' => array_keys($this->loadedModules),
        ]);
    }

    /**
     * Load a single module
     */
    private function loadModule(string $moduleDir): void
    {
        $manifestFile = $moduleDir . '/module.json';

        if (!file_exists($manifestFile)) {
            return; // Skip if no manifest
        }

        $manifest = json_decode(file_get_contents($manifestFile), true);
        
        if (!$manifest || !isset($manifest['name'])) {
            $this->logger->warning('Invalid module manifest', [
                'file' => $manifestFile,
            ]);
            return;
        }

        $moduleName = $manifest['name'];

        // Register hooks
        if (isset($manifest['hooks']) && is_array($manifest['hooks'])) {
            foreach ($manifest['hooks'] as $hookName => $hookConfig) {
                $priority = $hookConfig['priority'] ?? 10;
                
                // Hook callback points to module's init function
                $this->hookManager->register($hookName, function() use ($moduleName) {
                    $this->logger->debug("Hook executed: {$moduleName}");
                }, $priority);
            }
        }

        $this->loadedModules[$moduleName] = $manifest;
    }

    /**
     * Get all loaded modules
     */
    public function getLoaded(): array
    {
        return $this->loadedModules;
    }

    /**
     * Check if module is loaded
     */
    public function isLoaded(string $moduleName): bool
    {
        return isset($this->loadedModules[$moduleName]);
    }
}

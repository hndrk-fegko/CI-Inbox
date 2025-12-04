<?php

namespace CiInbox\Core;

/**
 * Hook Manager
 * 
 * Event system for module hooks.
 * Modules can register callbacks for lifecycle events.
 */
class HookManager
{
    private array $hooks = [];

    /**
     * Register a hook callback
     * 
     * @param string $hookName Hook name (e.g., 'onAppInit', 'onError')
     * @param callable $callback Function to call
     * @param int $priority Lower = earlier execution (default: 10)
     */
    public function register(string $hookName, callable $callback, int $priority = 10): void
    {
        if (!isset($this->hooks[$hookName])) {
            $this->hooks[$hookName] = [];
        }

        $this->hooks[$hookName][] = [
            'callback' => $callback,
            'priority' => $priority,
        ];

        // Sort by priority
        usort($this->hooks[$hookName], function($a, $b) {
            return $a['priority'] <=> $b['priority'];
        });
    }

    /**
     * Execute all hooks for an event
     * 
     * @param string $hookName Hook name
     * @param mixed ...$args Arguments to pass to callbacks
     * @return array Results from all callbacks
     */
    public function execute(string $hookName, mixed ...$args): array
    {
        if (!isset($this->hooks[$hookName])) {
            return [];
        }

        $results = [];
        foreach ($this->hooks[$hookName] as $hook) {
            $results[] = call_user_func_array($hook['callback'], $args);
        }

        return $results;
    }

    /**
     * Check if hook has any registered callbacks
     */
    public function has(string $hookName): bool
    {
        return isset($this->hooks[$hookName]) && count($this->hooks[$hookName]) > 0;
    }

    /**
     * Get all registered hooks
     */
    public function getAll(): array
    {
        return array_keys($this->hooks);
    }
}

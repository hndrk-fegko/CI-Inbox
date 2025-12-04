<?php

namespace CiInbox\Core;

use DI\Container as DIContainer;
use DI\ContainerBuilder;

/**
 * Dependency Injection Container
 * 
 * Wrapper around PHP-DI for service management.
 */
class Container
{
    private static ?DIContainer $instance = null;

    /**
     * Get container instance (singleton)
     */
    public static function getInstance(): DIContainer
    {
        if (self::$instance === null) {
            $builder = new ContainerBuilder();
            
            // Load definitions from config
            $definitions = require __DIR__ . '/../config/container.php';
            $builder->addDefinitions($definitions);
            
            self::$instance = $builder->build();
        }

        return self::$instance;
    }

    /**
     * Get service from container
     */
    public static function get(string $id): mixed
    {
        return self::getInstance()->get($id);
    }

    /**
     * Check if service exists
     */
    public static function has(string $id): bool
    {
        return self::getInstance()->has($id);
    }

    /**
     * Set service in container
     */
    public static function set(string $id, mixed $value): void
    {
        self::getInstance()->set($id, $value);
    }
}

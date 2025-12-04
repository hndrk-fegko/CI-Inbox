<?php

/**
 * Database Bootstrap
 * 
 * Sets up Eloquent ORM as standalone component.
 * Uses illuminate/database package without full Laravel.
 */

use Illuminate\Database\Capsule\Manager as Capsule;
use CiInbox\Modules\Config\ConfigInterface;
use CiInbox\App\Models\Email;
use CiInbox\App\Models\ThreadAssignment;
use CiInbox\App\Observers\EmailObserver;
use CiInbox\App\Observers\ThreadAssignmentObserver;

/**
 * Initialize database connection with Eloquent
 * 
 * @param ConfigInterface $config
 * @return Capsule
 */
function initDatabase(ConfigInterface $config): Capsule
{
    $capsule = new Capsule;

    // Add connection from config
    $capsule->addConnection([
        'driver' => $config->getString('database.connections.mysql.driver'),
        'host' => $config->getString('database.connections.mysql.host'),
        'port' => $config->getInt('database.connections.mysql.port'),
        'database' => $config->getString('database.connections.mysql.database'),
        'username' => $config->getString('database.connections.mysql.username'),
        'password' => $config->getString('database.connections.mysql.password'),
        'charset' => $config->getString('database.connections.mysql.charset'),
        'collation' => $config->getString('database.connections.mysql.collation'),
        'prefix' => $config->getString('database.connections.mysql.prefix', ''),
    ]);

    // Make this Capsule instance available globally via static methods
    $capsule->setAsGlobal();

    // Setup the Eloquent ORM
    $capsule->bootEloquent();
    
    // Register Model Observers for auto-status management
    Email::observe(EmailObserver::class);
    ThreadAssignment::observe(ThreadAssignmentObserver::class);

    return $capsule;
}

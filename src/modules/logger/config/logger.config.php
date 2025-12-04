<?php
declare(strict_types=1);

/**
 * Logger Module Configuration
 * 
 * This file is loaded by the logger module on initialization.
 */

return [
    // Log file path (relative to project root)
    'log_path' => __DIR__ . '/../../../logs',

    // Minimum log level: debug, info, notice, warning, error, critical, alert, emergency
    'log_level' => $_ENV['LOG_LEVEL'] ?? 'debug',

    // Logger channel name
    'channel' => 'app',

    // Rotation settings
    'rotation' => [
        'max_files' => 30, // Keep logs for 30 days
        'file_permission' => 0664,
    ],

    // Additional handlers (can be extended later)
    'handlers' => [
        'file' => [
            'enabled' => true,
            'path' => 'app.log',
        ],
        // Future: Database handler
        // 'database' => [
        //     'enabled' => false,
        //     'table' => 'logs',
        // ],
    ],

    // Formatter settings
    'formatter' => [
        'type' => 'json', // json or line
        'include_backtrace' => true,
        'include_performance' => true,
    ],
];

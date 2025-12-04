<?php
declare(strict_types=1);

/**
 * Application Configuration
 */

return [
    'name' => $_ENV['APP_NAME'] ?? 'CI-Inbox',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    
    'timezone' => 'Europe/Berlin',
    'locale' => 'de_DE',
];

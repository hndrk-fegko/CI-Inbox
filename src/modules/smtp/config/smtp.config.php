<?php

return [
    'host' => $_ENV['SMTP_HOST'] ?? 'localhost',
    'port' => (int)($_ENV['SMTP_PORT'] ?? 25),
    'username' => $_ENV['SMTP_USERNAME'] ?? '',
    'password' => $_ENV['SMTP_PASSWORD'] ?? '',
    'encryption' => $_ENV['SMTP_ENCRYPTION'] ?? 'none',
    'from_email' => $_ENV['SMTP_FROM_EMAIL'] ?? 'info@feg-koblenz.de',
    'from_name' => $_ENV['SMTP_FROM_NAME'] ?? 'CI-Inbox',
];

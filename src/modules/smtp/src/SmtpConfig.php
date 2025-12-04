<?php

declare(strict_types=1);

namespace CiInbox\Modules\Smtp;

/**
 * SMTP Configuration DTO
 */
class SmtpConfig
{
    public function __construct(
        public readonly string $host,
        public readonly int $port,
        public readonly string $username,
        public readonly string $password,
        public readonly string $encryption, // 'tls', 'ssl', 'none'
        public readonly string $fromEmail,
        public readonly string $fromName
    ) {}

    /**
     * Load from config array
     */
    public static function fromArray(array $config): self
    {
        return new self(
            $config['host'],
            $config['port'],
            $config['username'],
            $config['password'],
            $config['encryption'] ?? 'tls',
            $config['from_email'],
            $config['from_name']
        );
    }
}

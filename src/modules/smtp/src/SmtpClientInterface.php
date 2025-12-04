<?php

declare(strict_types=1);

namespace CiInbox\Modules\Smtp;

/**
 * SMTP Client Interface
 * 
 * Abstraktion über SMTP-Versand (aktuell PHPMailer, später austauschbar)
 */
interface SmtpClientInterface
{
    /**
     * Connect to SMTP server
     * 
     * @param SmtpConfig $config SMTP connection details
     * @return bool Success
     * @throws SmtpException on connection failure
     */
    public function connect(SmtpConfig $config): bool;

    /**
     * Send email via SMTP
     * 
     * @param EmailMessage $message Email to send
     * @return bool Success
     * @throws SmtpException on send failure
     */
    public function send(EmailMessage $message): bool;

    /**
     * Disconnect from SMTP server
     */
    public function disconnect(): void;

    /**
     * Get last error message
     */
    public function getLastError(): ?string;
}

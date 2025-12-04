<?php

declare(strict_types=1);

namespace CiInbox\Modules\Smtp;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use CiInbox\Modules\Logger\LoggerService;

/**
 * PHPMailer SMTP Client Implementation
 */
class PHPMailerSmtpClient implements SmtpClientInterface
{
    private ?PHPMailer $mailer = null;
    private ?string $lastError = null;

    public function __construct(
        private LoggerService $logger
    ) {}

    public function connect(SmtpConfig $config): bool
    {
        $this->logger->info('Connecting to SMTP server', [
            'host' => $config->host,
            'port' => $config->port,
            'encryption' => $config->encryption
        ]);

        try {
            $this->mailer = new PHPMailer(true);
            
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $config->host;
            $this->mailer->Port = $config->port;
            
            // Auth only if username provided
            if (!empty($config->username)) {
                $this->mailer->SMTPAuth = true;
                $this->mailer->Username = $config->username;
                $this->mailer->Password = $config->password;
            } else {
                $this->mailer->SMTPAuth = false;
            }
            
            // Encryption
            if ($config->encryption === 'tls') {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($config->encryption === 'ssl') {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }
            
            // From address
            $this->mailer->setFrom($config->fromEmail, $config->fromName);
            
            // Set charset to UTF-8
            $this->mailer->CharSet = 'UTF-8';
            
            // Enable debug output (only in development)
            $this->mailer->SMTPDebug = 0;
            
            $this->logger->info('SMTP connection established');
            return true;
            
        } catch (PHPMailerException $e) {
            $this->lastError = $e->getMessage();
            $this->logger->error('SMTP connection failed', [
                'error' => $e->getMessage()
            ]);
            throw new SmtpException("SMTP connection failed: " . $e->getMessage(), 0, $e);
        }
    }

    public function send(EmailMessage $message): bool
    {
        if (!$this->mailer) {
            throw new SmtpException('Not connected to SMTP server');
        }

        $this->logger->info('Sending email via SMTP', [
            'subject' => $message->subject,
            'to' => count($message->to) . ' recipient(s)'
        ]);

        try {
            // Clear previous recipients
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->clearCustomHeaders();
            
            // Add recipients
            foreach ($message->to as $recipient) {
                $this->mailer->addAddress(
                    $recipient['email'],
                    $recipient['name'] ?? ''
                );
            }
            
            // Add CC
            foreach ($message->cc as $cc) {
                $this->mailer->addCC($cc['email'], $cc['name'] ?? '');
            }
            
            // Add BCC
            foreach ($message->bcc as $bcc) {
                $this->mailer->addBCC($bcc['email'], $bcc['name'] ?? '');
            }
            
            // Add Reply-To
            foreach ($message->replyTo as $replyTo) {
                $this->mailer->addReplyTo($replyTo['email'], $replyTo['name'] ?? '');
            }
            
            // Subject
            $this->mailer->Subject = $message->subject;
            
            // Body
            $this->mailer->isHTML(true);
            $this->mailer->Body = $message->bodyHtml;
            $this->mailer->AltBody = $message->bodyText;
            
            // Threading headers (for reply/forward)
            if ($message->inReplyTo) {
                $this->mailer->addCustomHeader('In-Reply-To', $message->inReplyTo);
            }
            
            if (!empty($message->references)) {
                $this->mailer->addCustomHeader('References', implode(' ', $message->references));
            }
            
            // Attachments
            foreach ($message->attachments as $attachment) {
                $this->mailer->addAttachment(
                    $attachment['path'],
                    $attachment['name'] ?? ''
                );
            }
            
            // Send
            $result = $this->mailer->send();
            
            if ($result) {
                $this->logger->info('Email sent successfully');
            } else {
                $this->logger->error('Email send failed', [
                    'error' => $this->mailer->ErrorInfo
                ]);
            }
            
            return $result;
            
        } catch (PHPMailerException $e) {
            $this->lastError = $e->getMessage();
            $this->logger->error('Email send failed', [
                'error' => $e->getMessage()
            ]);
            throw new SmtpException("Email send failed: " . $e->getMessage(), 0, $e);
        }
    }

    public function disconnect(): void
    {
        $this->mailer = null;
        $this->logger->info('SMTP connection closed');
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }
}

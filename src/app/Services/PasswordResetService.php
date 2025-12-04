<?php

declare(strict_types=1);

namespace CiInbox\App\Services;

use CiInbox\App\Models\User;
use CiInbox\Modules\Logger\LoggerService;
use CiInbox\Modules\Smtp\SmtpClientInterface;
use CiInbox\Modules\Smtp\SmtpConfig;

/**
 * Password Reset Service
 * 
 * Handles forgot password functionality including
 * token generation and email sending.
 */
class PasswordResetService
{
    private LoggerService $logger;
    private SmtpClientInterface $smtpClient;
    private SmtpConfig $smtpConfig;

    public function __construct(
        LoggerService $logger,
        SmtpClientInterface $smtpClient,
        SmtpConfig $smtpConfig
    ) {
        $this->logger = $logger;
        $this->smtpClient = $smtpClient;
        $this->smtpConfig = $smtpConfig;
    }

    /**
     * Initiate password reset for user
     * 
     * @param string $email User's email address
     * @return bool True if reset email was sent (or would be sent)
     */
    public function initiateReset(string $email): bool
    {
        // Always return true to prevent email enumeration
        $user = User::where('email', $email)
            ->where('is_active', true)
            ->first();

        if (!$user) {
            $this->logger->info('Password reset requested for non-existent email', [
                'email' => $email
            ]);
            return true; // Don't reveal that email doesn't exist
        }

        // OAuth users cannot reset password
        if ($user->isOAuthUser()) {
            $this->logger->info('Password reset requested for OAuth user', [
                'email' => $email,
                'provider' => $user->oauth_provider
            ]);
            return true; // Don't reveal OAuth status
        }

        // Generate reset token
        $token = $user->generatePasswordResetToken();

        // Send reset email
        try {
            $this->sendResetEmail($user, $token);
            $this->logger->info('Password reset email sent', ['email' => $email]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send password reset email', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            // Clear token since email wasn't sent
            $user->clearPasswordResetToken();
            throw new \Exception('E-Mail konnte nicht gesendet werden. Bitte versuchen Sie es später erneut.');
        }

        return true;
    }

    /**
     * Validate reset token
     * 
     * @param string $token Reset token from URL
     * @return User|null User if token is valid
     */
    public function validateToken(string $token): ?User
    {
        return User::findByPasswordResetToken($token);
    }

    /**
     * Reset password with token
     * 
     * @param string $token Reset token
     * @param string $newPassword New password
     * @return bool Success
     */
    public function resetPassword(string $token, string $newPassword): bool
    {
        $user = User::findByPasswordResetToken($token);

        if (!$user) {
            $this->logger->warning('Invalid password reset token used', [
                'token_hash' => hash('sha256', $token)
            ]);
            throw new \Exception('Ungültiger oder abgelaufener Reset-Link.');
        }

        // Validate password strength
        $this->validatePasswordStrength($newPassword);

        // Update password
        $user->password_hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $user->clearPasswordResetToken();

        $this->logger->info('Password reset successful', ['email' => $user->email]);

        return true;
    }

    /**
     * Validate password strength
     */
    private function validatePasswordStrength(string $password): void
    {
        if (strlen($password) < 8) {
            throw new \Exception('Das Passwort muss mindestens 8 Zeichen lang sein.');
        }

        if (!preg_match('/[A-Z]/', $password)) {
            throw new \Exception('Das Passwort muss mindestens einen Großbuchstaben enthalten.');
        }

        if (!preg_match('/[a-z]/', $password)) {
            throw new \Exception('Das Passwort muss mindestens einen Kleinbuchstaben enthalten.');
        }

        if (!preg_match('/[0-9]/', $password)) {
            throw new \Exception('Das Passwort muss mindestens eine Zahl enthalten.');
        }
    }

    /**
     * Send password reset email
     */
    private function sendResetEmail(User $user, string $token): void
    {
        $resetUrl = $this->buildResetUrl($token);
        
        $subject = 'Passwort zurücksetzen - C-IMAP';
        
        $htmlBody = $this->buildResetEmailHtml($user->name ?? $user->email, $resetUrl);
        $textBody = $this->buildResetEmailText($user->name ?? $user->email, $resetUrl);

        // Configure SMTP client
        $this->smtpClient->configure($this->smtpConfig);
        
        // Send email
        $this->smtpClient->send(
            $user->email,
            $subject,
            $htmlBody,
            $textBody
        );
    }

    /**
     * Build reset URL
     * Uses APP_URL from environment or validates HTTP_HOST
     */
    private function buildResetUrl(string $token): string
    {
        // Prefer APP_URL from environment for security
        $appUrl = getenv('APP_URL');
        if ($appUrl) {
            return rtrim($appUrl, '/') . '/reset-password.php?token=' . urlencode($token);
        }
        
        // Fallback with validation
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // Basic validation - remove any malicious characters
        $host = preg_replace('/[^a-zA-Z0-9\.\-:]/', '', $host);
        
        return "{$protocol}://{$host}/reset-password.php?token=" . urlencode($token);
    }

    /**
     * Build HTML email body
     */
    private function buildResetEmailHtml(string $name, string $resetUrl): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .button { 
            display: inline-block; 
            padding: 12px 24px; 
            background-color: #3B82F6; 
            color: white; 
            text-decoration: none; 
            border-radius: 6px;
            margin: 20px 0;
        }
        .footer { margin-top: 30px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Passwort zurücksetzen</h2>
        <p>Hallo {$name},</p>
        <p>Sie haben eine Anfrage zum Zurücksetzen Ihres Passworts gestellt. 
           Klicken Sie auf den folgenden Button, um ein neues Passwort zu erstellen:</p>
        
        <a href="{$resetUrl}" class="button">Passwort zurücksetzen</a>
        
        <p>Oder kopieren Sie diesen Link in Ihren Browser:</p>
        <p style="word-break: break-all; font-size: 12px;">{$resetUrl}</p>
        
        <p><strong>Dieser Link ist 1 Stunde gültig.</strong></p>
        
        <p>Falls Sie diese Anfrage nicht gestellt haben, können Sie diese E-Mail ignorieren. 
           Ihr Passwort bleibt unverändert.</p>
        
        <div class="footer">
            <p>Diese E-Mail wurde automatisch von C-IMAP generiert.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Build plain text email body
     */
    private function buildResetEmailText(string $name, string $resetUrl): string
    {
        return <<<TEXT
Passwort zurücksetzen

Hallo {$name},

Sie haben eine Anfrage zum Zurücksetzen Ihres Passworts gestellt.
Öffnen Sie den folgenden Link, um ein neues Passwort zu erstellen:

{$resetUrl}

Dieser Link ist 1 Stunde gültig.

Falls Sie diese Anfrage nicht gestellt haben, können Sie diese E-Mail ignorieren.
Ihr Passwort bleibt unverändert.

---
Diese E-Mail wurde automatisch von C-IMAP generiert.
TEXT;
    }
}

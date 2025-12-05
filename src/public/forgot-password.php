<?php
/**
 * Forgot Password Page
 * 
 * Allows users to request a password reset email.
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/version.php';

session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: /inbox.php');
    exit;
}

// CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF
    if (!hash_equals($csrfToken, $_POST['csrf_token'] ?? '')) {
        $error = 'Sicherheitstoken ungültig. Bitte versuchen Sie es erneut.';
    } else {
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        
        if (!$email) {
            $error = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
        } else {
            try {
                // Initialize services
                $container = \CiInbox\Core\Container::getInstance();
                $container->get('database');
                
                $passwordResetService = $container->get(\CiInbox\App\Services\PasswordResetService::class);
                $passwordResetService->initiateReset($email);
                
                // Always show success to prevent email enumeration
                $success = 'Falls ein Account mit dieser E-Mail-Adresse existiert, wurde eine E-Mail mit einem Reset-Link gesendet.';
                
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passwort vergessen - C-IMAP</title>
    <link rel="stylesheet" href="/assets/css/main.css<?= asset_version() ?>">
</head>
<body class="l-auth">
    <div class="l-auth__container">
        <div class="c-auth-card">
            <div class="c-auth-card__header">
                <svg class="c-auth-card__logo" width="48" height="48" viewBox="0 0 48 48" fill="none">
                    <rect width="48" height="48" rx="8" fill="currentColor" opacity="0.1"/>
                    <path d="M12 18L24 26L36 18M12 18V30C12 30.5304 12.2107 31.0391 12.5858 31.4142C12.9609 31.7893 13.4696 32 14 32H34C34.5304 32 35.0391 31.7893 35.4142 31.4142C35.7893 31.0391 36 30.5304 36 30V18M12 18C12 17.4696 12.2107 16.9609 12.5858 16.5858C12.9609 16.2107 13.4696 16 14 16H34C34.5304 16 35.0391 16.2107 35.4142 16.5858C35.7893 16.9609 36 17.4696 36 18Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h1 class="c-auth-card__title">Passwort vergessen</h1>
                <p class="c-auth-card__subtitle">Geben Sie Ihre E-Mail-Adresse ein, um einen Reset-Link zu erhalten.</p>
            </div>

            <form class="c-auth-card__form" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                
                <?php if ($error): ?>
                    <div class="c-alert c-alert--danger">
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="c-alert c-alert--success">
                        <span><?= htmlspecialchars($success) ?></span>
                    </div>
                <?php else: ?>
                    <div class="c-input-group">
                        <label for="email" class="c-input-group__label">E-Mail-Adresse</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="c-input" 
                            placeholder="name@beispiel.de"
                            required
                            autofocus
                            autocomplete="email"
                        >
                    </div>

                    <button type="submit" class="c-button c-button--primary c-button--block">
                        Reset-Link senden
                    </button>
                <?php endif; ?>

                <div class="c-auth-card__footer" style="margin-top: 20px; text-align: center;">
                    <a href="/login.php" class="c-link">← Zurück zur Anmeldung</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>

<?php
/**
 * Reset Password Page
 * 
 * Allows users to set a new password using a reset token.
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/version.php';

session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: /inbox.php');
    exit;
}

// Get token from URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: /login.php');
    exit;
}

// CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$error = null;
$success = null;
$tokenValid = false;

// Initialize container and validate token
try {
    $container = \CiInbox\Core\Container::getInstance();
    $container->get('database');
    
    $passwordResetService = $container->get(\CiInbox\App\Services\PasswordResetService::class);
    $user = $passwordResetService->validateToken($token);
    
    if ($user) {
        $tokenValid = true;
    }
} catch (\Exception $e) {
    $error = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    // Verify CSRF
    if (!hash_equals($csrfToken, $_POST['csrf_token'] ?? '')) {
        $error = 'Sicherheitstoken ungültig. Bitte versuchen Sie es erneut.';
    } else {
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        
        if ($password !== $passwordConfirm) {
            $error = 'Die Passwörter stimmen nicht überein.';
        } else {
            try {
                $passwordResetService->resetPassword($token, $password);
                $success = 'Ihr Passwort wurde erfolgreich geändert. Sie können sich jetzt anmelden.';
                $tokenValid = false; // Prevent form from showing again
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
    <title>Neues Passwort setzen - C-IMAP</title>
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
                <h1 class="c-auth-card__title">Neues Passwort</h1>
                <p class="c-auth-card__subtitle">Geben Sie Ihr neues Passwort ein.</p>
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
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="/login.php" class="c-button c-button--primary">Zur Anmeldung</a>
                    </div>
                <?php elseif (!$tokenValid): ?>
                    <div class="c-alert c-alert--danger">
                        <span>Dieser Reset-Link ist ungültig oder abgelaufen.</span>
                    </div>
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="/forgot-password.php" class="c-button c-button--secondary">
                            Neuen Link anfordern
                        </a>
                    </div>
                <?php else: ?>
                    <div class="c-input-group">
                        <label for="password" class="c-input-group__label">Neues Passwort</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="c-input" 
                            placeholder="••••••••"
                            required
                            minlength="8"
                            autofocus
                            autocomplete="new-password"
                        >
                        <small style="color: #6b7280; font-size: 12px; margin-top: 4px; display: block;">
                            Mindestens 8 Zeichen, mit Groß-/Kleinbuchstaben und Zahlen
                        </small>
                    </div>

                    <div class="c-input-group">
                        <label for="password_confirm" class="c-input-group__label">Passwort bestätigen</label>
                        <input 
                            type="password" 
                            id="password_confirm" 
                            name="password_confirm" 
                            class="c-input" 
                            placeholder="••••••••"
                            required
                            minlength="8"
                            autocomplete="new-password"
                        >
                    </div>

                    <button type="submit" class="c-button c-button--primary c-button--block">
                        Passwort ändern
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

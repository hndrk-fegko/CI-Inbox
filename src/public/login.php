<?php
/**
 * Simple Login Page (Static)
 * Test without Slim routing
 */

require_once __DIR__ . '/../../vendor/autoload.php';

session_start();

// Simple authentication
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        // Database connection
        $pdo = new PDO(
            'mysql:host=localhost;dbname=ci_inbox;charset=utf8mb4',
            'root',
            '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Get user from database
        $stmt = $pdo->prepare('SELECT id, email, name, password_hash, role, is_active FROM users WHERE email = ? AND is_active = 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Authentication successful
            session_regenerate_id(true);
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['logged_in_at'] = time();
            
            header('Location: /inbox.php');
            exit;
        } else {
            $error = 'Ungültige E-Mail-Adresse oder Passwort.';
        }
    } catch (Exception $e) {
        $error = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.';
        error_log('Login error: ' . $e->getMessage());
    }
}

// Redirect if already logged in
if (isset($_SESSION['user_email'])) {
    header('Location: /inbox.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - C-IMAP</title>
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body class="l-auth">
    <div class="l-auth__container">
        <div class="c-auth-card">
            <!-- Header -->
            <div class="c-auth-card__header">
                <svg class="c-auth-card__logo" width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="48" height="48" rx="8" fill="currentColor" opacity="0.1"/>
                    <path d="M12 18L24 26L36 18M12 18V30C12 30.5304 12.2107 31.0391 12.5858 31.4142C12.9609 31.7893 13.4696 32 14 32H34C34.5304 32 35.0391 31.7893 35.4142 31.4142C35.7893 31.0391 36 30.5304 36 30V18M12 18C12 17.4696 12.2107 16.9609 12.5858 16.5858C12.9609 16.2107 13.4696 16 14 16H34C34.5304 16 35.0391 16.2107 35.4142 16.5858C35.7893 16.9609 36 17.4696 36 18Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h1 class="c-auth-card__title">C-IMAP</h1>
                <p class="c-auth-card__subtitle">E-Mail Management System</p>
            </div>

            <!-- Login Form -->
            <form class="c-auth-card__form" method="POST" action="/login.php">
                <?php if (isset($error)): ?>
                    <div class="c-alert c-alert--danger" role="alert">
                        <svg class="c-alert__icon" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <!-- Email Input -->
                <div class="c-input-group">
                    <label for="email" class="c-input-group__label">
                        E-Mail-Adresse
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="c-input" 
                        placeholder="name@beispiel.de"
                        required
                        autofocus
                        autocomplete="email"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    >
                </div>

                <!-- Password Input -->
                <div class="c-input-group">
                    <label for="password" class="c-input-group__label">
                        Passwort
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="c-input" 
                        placeholder="••••••••"
                        required
                        autocomplete="current-password"
                    >
                </div>

                <!-- Remember Me -->
                <div class="c-checkbox">
                    <input 
                        type="checkbox" 
                        id="remember" 
                        name="remember" 
                        class="c-checkbox__input"
                    >
                    <label for="remember" class="c-checkbox__label">
                        Angemeldet bleiben
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="c-button c-button--primary c-button--block">
                    Anmelden
                </button>

                <!-- Demo Credentials -->
                <div class="c-auth-card__footer">
                    <p style="font-size: 12px; color: #6b7280; margin: 16px 0 0;">
                        <strong>Demo:</strong> demo@c-imap.local / demo123<br>
                        <strong>Admin:</strong> admin@c-imap.local / admin123
                    </p>
                </div>
            </form>
        </div>

        <!-- System Info Footer -->
        <footer class="l-auth__footer">
            <p class="l-auth__footer-text">
                C-IMAP v1.0.0 | 
                <a href="/status" class="c-link c-link--muted">Status</a> |
                <a href="/api" class="c-link c-link--muted">API</a>
            </p>
        </footer>
    </div>
</body>
</html>

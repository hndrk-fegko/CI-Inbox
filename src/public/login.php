<?php
/**
 * Login Page
 * 
 * Security features:
 * - Session-based authentication
 * - Rate limiting (configurable)
 * - Honeypot field (anti-bot)
 * - CSRF protection
 * - Secure password verification
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config/version.php';

use CiInbox\App\Middleware\CsrfMiddleware;

session_start();

// ============================================================================
// RATE LIMITING
// ============================================================================

$rateLimitFile = sys_get_temp_dir() . '/ci-inbox-login-limits/' . hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
$maxAttempts = 5;           // Max login attempts
$lockoutSeconds = 300;      // 5 minutes lockout
$isLocked = false;

// Check rate limit
if (file_exists($rateLimitFile)) {
    $data = json_decode(file_get_contents($rateLimitFile), true);
    if ($data && isset($data['attempts']) && isset($data['first_attempt'])) {
        // Check if still in lockout period
        if ($data['attempts'] >= $maxAttempts) {
            $lockoutEnd = $data['first_attempt'] + $lockoutSeconds;
            if (time() < $lockoutEnd) {
                $isLocked = true;
                $lockoutRemaining = $lockoutEnd - time();
            } else {
                // Lockout expired, reset
                @unlink($rateLimitFile);
            }
        }
    }
}

// ============================================================================
// CSRF TOKEN
// ============================================================================

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// ============================================================================
// HONEYPOT TRACKING
// ============================================================================

$honeypotTriggered = false;

// ============================================================================
// AUTHENTICATION
// ============================================================================

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isLocked) {
    // Verify CSRF token
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrfToken, $submittedToken)) {
        $error = 'Sicherheitstoken ungültig. Bitte versuchen Sie es erneut.';
    }
    // Check honeypot (should be empty)
    elseif (!empty($_POST['website'] ?? '')) {
        // Silently reject - don't reveal honeypot detection
        $honeypotTriggered = true;
        $error = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.';
        // Log honeypot trigger for security monitoring
        error_log('Honeypot triggered from IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    }
    else {
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
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
                
                // Regenerate CSRF token after login
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                
                // Clear rate limit on successful login
                @unlink($rateLimitFile);
                
                header('Location: /inbox.php');
                exit;
            } else {
                $error = 'Ungültige E-Mail-Adresse oder Passwort.';
                
                // Track failed attempt for rate limiting
                $rateLimitDir = dirname($rateLimitFile);
                if (!is_dir($rateLimitDir)) {
                    mkdir($rateLimitDir, 0755, true);
                }
                
                $data = file_exists($rateLimitFile) 
                    ? json_decode(file_get_contents($rateLimitFile), true) 
                    : ['attempts' => 0, 'first_attempt' => time()];
                
                $data['attempts']++;
                $data['last_attempt'] = time();
                
                file_put_contents($rateLimitFile, json_encode($data));
                
                // Check if now locked out
                if ($data['attempts'] >= $maxAttempts) {
                    $isLocked = true;
                    $lockoutRemaining = $lockoutSeconds;
                }
            }
        } catch (Exception $e) {
            $error = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.';
            error_log('Login error: ' . $e->getMessage());
        }
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
    <title>Login - CI-Inbox</title>
    <link rel="stylesheet" href="/assets/css/main.css<?= asset_version() ?>">
    <!-- Honeypot CSS - hide field from users but keep it accessible to bots -->
    <style>
        .hp-field { opacity: 0; position: absolute; top: 0; left: 0; height: 0; width: 0; z-index: -1; }
    </style>
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
                <h1 class="c-auth-card__title">CI-Inbox</h1>
                <p class="c-auth-card__subtitle">E-Mail Management System</p>
            </div>

            <!-- Login Form -->
            <form class="c-auth-card__form" method="POST" action="/login.php">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                
                <!-- Honeypot Field (hidden from users, visible to bots) -->
                <div class="hp-field" aria-hidden="true">
                    <label for="website">Website (leave empty)</label>
                    <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
                </div>
                
                <?php if ($isLocked): ?>
                    <div class="c-alert c-alert--danger" role="alert">
                        <svg class="c-alert__icon" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <span>Zu viele Anmeldeversuche. Bitte warten Sie <?= ceil($lockoutRemaining / 60) ?> Minute(n).</span>
                    </div>
                <?php elseif (isset($error)): ?>
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
                        <?= $isLocked ? 'disabled' : '' ?>
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
                        <?= $isLocked ? 'disabled' : '' ?>
                    >
                </div>

                <!-- Remember Me & Forgot Password -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <div class="c-checkbox">
                        <input 
                            type="checkbox" 
                            id="remember" 
                            name="remember" 
                            class="c-checkbox__input"
                            <?= $isLocked ? 'disabled' : '' ?>
                        >
                        <label for="remember" class="c-checkbox__label">
                            Angemeldet bleiben
                        </label>
                    </div>
                    <a href="/forgot-password.php" class="c-link" style="font-size: 14px;">
                        Passwort vergessen?
                    </a>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="c-button c-button--primary c-button--block" <?= $isLocked ? 'disabled' : '' ?>>
                    Anmelden
                </button>

                <!-- OAuth Providers (loaded dynamically) -->
                <div id="oauth-providers" style="margin-top: 20px;"></div>

                <!-- Demo Credentials -->
                <div class="c-auth-card__footer">
                    <p style="font-size: 12px; color: #6b7280; margin: 16px 0 0;">
                        <strong>Demo:</strong> demo@ci-inbox.local / demo123<br>
                        <strong>Admin:</strong> admin@ci-inbox.local / admin123
                    </p>
                </div>
            </form>
        </div>

        <!-- System Info Footer -->
        <footer class="l-auth__footer">
            <p class="l-auth__footer-text">
                CI-Inbox v1.0.0 | 
                <a href="/status" class="c-link c-link--muted">Status</a> |
                <a href="/api" class="c-link c-link--muted">API</a>
            </p>
        </footer>
    </div>
    
    <!-- Load OAuth Providers -->
    <script>
    (async function() {
        try {
            const response = await fetch('/api/oauth/providers');
            const data = await response.json();
            
            if (data.success && data.providers && data.providers.length > 0) {
                const container = document.getElementById('oauth-providers');
                
                // Add divider
                container.innerHTML = `
                    <div style="display: flex; align-items: center; margin: 20px 0; color: #9ca3af;">
                        <div style="flex: 1; height: 1px; background: #e5e7eb;"></div>
                        <span style="padding: 0 16px; font-size: 13px;">oder anmelden mit</span>
                        <div style="flex: 1; height: 1px; background: #e5e7eb;"></div>
                    </div>
                `;
                
                // Add provider buttons
                const buttonsHtml = data.providers.map(provider => `
                    <a href="/oauth/authorize/${encodeURIComponent(provider.name)}" 
                       class="c-button c-button--block" 
                       style="background: ${provider.button_color}; color: white; margin-bottom: 8px;">
                        ${provider.display_name}
                    </a>
                `).join('');
                
                container.innerHTML += buttonsHtml;
            }
        } catch (error) {
            console.error('Failed to load OAuth providers:', error);
        }
    })();
    </script>
</body>
</html>

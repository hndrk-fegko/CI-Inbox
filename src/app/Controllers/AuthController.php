<?php

namespace CiInbox\App\Controllers;

use CiInbox\Modules\Logger\LoggerService;

/**
 * AuthController - Authentication & Authorization
 * 
 * Handles user login, logout, and session management.
 */
class AuthController
{
    private LoggerService $logger;
    
    public function __construct(LoggerService $logger)
    {
        $this->logger = $logger;
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Show login form
     */
    public function showLogin(): void
    {
        // Redirect if already logged in
        if ($this->isAuthenticated()) {
            header('Location: /inbox');
            exit;
        }
        
        $this->render('auth/login', [
            'email' => $_SESSION['_old_email'] ?? '',
            'error' => $_SESSION['_error'] ?? null,
            'remember' => $_SESSION['_old_remember'] ?? false
        ]);
        
        // Clear flash data
        unset($_SESSION['_old_email'], $_SESSION['_error'], $_SESSION['_old_remember']);
    }
    
    /**
     * Process login attempt
     */
    public function login(): void
    {
        try {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $remember = isset($_POST['remember']);
            
            // Validation
            if (empty($email) || empty($password)) {
                throw new \Exception('E-Mail und Passwort sind erforderlich.');
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Ungültige E-Mail-Adresse.');
            }
            
            // Get user from database
            $user = \CiInbox\App\Models\User::where('email', $email)
                ->where('is_active', 1)
                ->first();
            
            if (!$user || !password_verify($password, $user->password_hash)) {
                throw new \Exception('Ungültige E-Mail-Adresse oder Passwort.');
            }
            
            // Authentication successful
            $this->createSession($user, $remember);
            
            $this->logger->info('User logged in', ['email' => $email]);
            
            // Redirect to inbox
            header('Location: /inbox');
            exit;
            
        } catch (\Exception $e) {
            $this->logger->warning('Login failed', [
                'email' => $email ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            // Store error and old input
            $_SESSION['_error'] = $e->getMessage();
            $_SESSION['_old_email'] = $email ?? '';
            $_SESSION['_old_remember'] = $remember ?? false;
            
            // Redirect back to login form
            header('Location: /auth/login');
            exit;
        }
    }
    
    /**
     * Logout user
     */
    public function logout(): void
    {
        $email = $_SESSION['user_email'] ?? 'unknown';
        
        // Destroy session
        $_SESSION = [];
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
        
        $this->logger->info('User logged out', ['email' => $email]);
        
        // Redirect to login
        header('Location: /auth/login');
        exit;
    }
    
    /**
     * Check if user is authenticated
     */
    public function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']) && isset($_SESSION['user_email']);
    }
    
    /**
     * Require authentication (middleware)
     */
    public function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            header('Location: /auth/login');
            exit;
        }
    }
    
    /**
     * Get current user email
     */
    public function getCurrentUserEmail(): ?string
    {
        return $_SESSION['user_email'] ?? null;
    }
    
    /**
     * Get current user ID
     */
    public function getCurrentUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Create user session
     */
    private function createSession($user, bool $remember): void
    {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session data
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['user_name'] = $user->name;
        $_SESSION['user_role'] = $user->role;
        $_SESSION['logged_in_at'] = time();
        
        // Set remember me cookie (30 days)
        if ($remember) {
            $cookieLifetime = 30 * 24 * 60 * 60; // 30 days
            ini_set('session.cookie_lifetime', (string)$cookieLifetime);
            session_set_cookie_params($cookieLifetime);
        }
    }
    
    /**
     * Render view
     */
    private function render(string $view, array $data = []): void
    {
        extract($data);
        
        $viewPath = __DIR__ . '/../../views/' . $view . '.php';
        
        if (!file_exists($viewPath)) {
            throw new \Exception("View not found: {$view}");
        }
        
        require $viewPath;
    }
}

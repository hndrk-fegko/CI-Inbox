<?php
/**
 * Setup Wizard - Step 4: Admin Account Configuration
 * 
 * Handles admin account creation with optional personal IMAP
 */

declare(strict_types=1);

/**
 * Handle Step 4 form submission
 * 
 * @param array $post POST data
 * @return void Redirects on success, throws Exception on error
 * @throws Exception On validation errors
 */
function handleStep4Submit(array $post): void
{
    $email = filter_var($post['admin_email'] ?? '', FILTER_VALIDATE_EMAIL);
    $name = trim($post['admin_name'] ?? '');
    $password = $post['admin_password'] ?? '';
    $passwordConfirm = $post['admin_password_confirm'] ?? '';
    
    // Validation
    if (!$email) {
        throw new Exception('Bitte geben Sie eine gültige E-Mail-Adresse ein.');
    }
    if (empty($name)) {
        throw new Exception('Bitte geben Sie einen Namen ein.');
    }
    if (strlen($password) < 8) {
        throw new Exception('Das Passwort muss mindestens 8 Zeichen lang sein.');
    }
    if ($password !== $passwordConfirm) {
        throw new Exception('Die Passwörter stimmen nicht überein.');
    }
    
    // Store admin data
    $adminData = [
        'email' => $email,
        'name' => $name,
        'password' => $password
    ];
    
    // Check if user wants to create personal IMAP account for admin
    if (isset($post['create_admin_personal_imap'])) {
        $imapPassword = $post['admin_imap_password'] ?? '';
        // If no separate IMAP password given, use login password
        $imapPassword = !empty($imapPassword) ? $imapPassword : $password;
        
        $adminData['create_personal_imap'] = true;
        $adminData['imap_password'] = $imapPassword;
        
        // Store discovered IMAP config (from test button)
        if (!empty($post['admin_imap_host'])) {
            $adminData['imap_host'] = $post['admin_imap_host'];
            $adminData['imap_port'] = $post['admin_imap_port'] ?? '993';
            $adminData['imap_ssl'] = isset($post['admin_imap_ssl']) && $post['admin_imap_ssl'] === '1';
        }
    }
    
    updateSessionData('admin', $adminData);
    updateSessionStep(5);
    redirectToStep(5);
}

/**
 * Render Step 4 form
 */
function renderStep4Form(): void
{
    ?>
    <h2 class="section-title">Administrator-Account</h2>
    <p class="section-desc">Erstellen Sie den ersten Administrator-Account.</p>
    
    <form method="POST" id="adminAccountForm">
        <div class="form-group">
            <label>Name</label>
            <input type="text" name="admin_name" id="admin_name" placeholder="Max Mustermann" required>
        </div>
        <div class="form-group">
            <label>E-Mail-Adresse</label>
            <input type="email" name="admin_email" id="admin_email" placeholder="admin@example.com" required>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Passwort</label>
                <input type="password" name="admin_password" id="admin_password" placeholder="Mindestens 8 Zeichen" required>
            </div>
            <div class="form-group">
                <label>Passwort bestätigen</label>
                <input type="password" name="admin_password_confirm" placeholder="Passwort wiederholen" required>
            </div>
        </div>
        
        <!-- Personal IMAP Option -->
        <div style="margin: 30px 0; padding: 20px; background: #f0f9ff; border-radius: 8px; border: 1px solid #bae6fd;">
            <h3 style="margin: 0 0 10px 0; font-size: 16px; color: #0c4a6e;">
                📬 Persönliches IMAP-Postfach für Admin (Optional)
            </h3>
            <p style="margin: 0 0 15px 0; font-size: 14px; color: #0369a1;">
                Sie können dem Admin-Account direkt ein persönliches IMAP-Postfach zuweisen. 
                Dies ermöglicht Workflow C (Sensitive E-Mails in persönlichen Account verschieben).
            </p>
            
            <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 15px; cursor: pointer;">
                <input type="checkbox" name="create_admin_personal_imap" id="create_admin_personal_imap" style="width: auto;">
                <span style="font-weight: 500;">Persönliches IMAP-Postfach für Admin erstellen</span>
            </label>
            
            <div id="admin-imap-password-field" style="display: none;">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>IMAP-Passwort (optional)</label>
                    <input type="password" name="admin_imap_password" id="admin_imap_password" 
                           placeholder="Leer lassen um Login-Passwort zu verwenden">
                    <small style="color: #0369a1; display: block; margin-top: 4px;">
                        💡 Falls Ihr E-Mail-Passwort vom Login-Passwort abweicht
                    </small>
                </div>
                
                <!-- Test Button -->
                <button type="button" id="test-admin-imap-btn" class="btn btn-secondary" style="margin-bottom: 10px;">
                    <span id="admin-btn-text">🔍 IMAP-Verbindung testen</span>
                    <span id="admin-btn-spinner" class="btn-spinner"></span>
                </button>
                
                <!-- Test Result -->
                <div id="admin-test-status" style="display: none; padding: 12px; border-radius: 6px; margin-top: 10px;"></div>
                
                <!-- Hidden fields for discovered config -->
                <input type="hidden" name="admin_imap_host" id="admin_imap_host">
                <input type="hidden" name="admin_imap_port" id="admin_imap_port">
                <input type="hidden" name="admin_imap_ssl" id="admin_imap_ssl">
            </div>
        </div>
        
        <div class="actions">
            <a href="?step=3" class="btn btn-secondary">← Zurück</a>
            <button type="submit" class="btn btn-primary">Weiter →</button>
        </div>
    </form>
    
    <!-- JavaScript moved to setup.js -->
    <?php
}

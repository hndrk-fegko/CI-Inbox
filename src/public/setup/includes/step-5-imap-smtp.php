<?php
/**
 * Setup Wizard - Step 5: IMAP/SMTP Configuration
 * 
 * Handles email server configuration (shared inbox)
 */

declare(strict_types=1);

/**
 * Handle Step 5 form submission
 * 
 * @param array $post POST data
 * @return void Redirects to next step
 */
function handleStep5Submit(array $post): void
{
    // IMAP configuration (optional - shared inbox)
    updateSessionData('imap', [
        'host' => $post['imap_host'] ?? '',
        'port' => $post['imap_port'] ?? '993',
        'user' => $post['imap_user'] ?? '',
        'pass' => $post['imap_pass'] ?? '',
        'ssl' => isset($post['imap_ssl']),
        'user_id' => null,  // K5: NULL = Shared Inbox (no user assigned)
    ]);
    
    // SMTP configuration (optional)
    $imapUser = $post['imap_user'] ?? '';
    updateSessionData('smtp', [
        'host' => $post['smtp_host'] ?? '',
        'port' => $post['smtp_port'] ?? '587',
        'user' => $post['smtp_user'] ?? '',
        'pass' => $post['smtp_pass'] ?? '',
        'ssl' => isset($post['smtp_ssl']),
        // Fallback: Use IMAP user email if SMTP from_email not provided
        'from_email' => $post['smtp_from_email'] ?? $imapUser,
        'from_name' => $post['smtp_from_name'] ?? 'CI-Inbox',
    ]);
    
    updateSessionStep(6);
    redirectToStep(6);
}

/**
 * Render Step 5 form
 */
function renderStep5Form(): void
{
    ?>
    <h2 class="section-title">E-Mail-Konfiguration</h2>
    <p class="section-desc">Konfigurieren Sie IMAP für den E-Mail-Empfang und SMTP für den Versand. Sie können diesen Schritt überspringen und später konfigurieren.</p>
    
    <form method="POST">
        <h3 style="font-size: 16px; margin-bottom: 15px; color: #374151;">IMAP (Empfang)</h3>
        
        <div id="imap-test-result" style="display: none; padding: 15px; border-radius: 8px; margin-bottom: 20px;"></div>
        
        <div class="form-row">
            <div class="form-group">
                <label>E-Mail für Test</label>
                <input type="email" id="imap_email" name="imap_email" placeholder="user@example.com">
            </div>
            <div class="form-group">
                <label>Passwort für Test</label>
                <input type="password" id="imap_pass_test" placeholder="Password">
            </div>
        </div>
        
        <button type="button" id="test-imap-btn" class="btn btn-secondary" style="margin-bottom: 20px;">
            <span class="btn-text">🔍 IMAP-Verbindung testen & Autodiscover</span>
            <span class="btn-spinner"></span>
        </button>
        
        <div class="form-row">
            <div class="form-group">
                <label>IMAP Server</label>
                <input type="text" name="imap_host" id="imap_host" placeholder="imap.example.com">
            </div>
            <div class="form-group">
                <label>Port</label>
                <input type="number" name="imap_port" id="imap_port" value="993">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Benutzername</label>
                <input type="text" name="imap_user" id="imap_user" placeholder="user@example.com">
            </div>
            <div class="form-group">
                <label>Passwort</label>
                <input type="password" name="imap_pass" id="imap_pass">
            </div>
        </div>
        <div class="checkbox-group">
            <input type="checkbox" name="imap_ssl" id="imap_ssl" checked>
            <label for="imap_ssl">SSL verwenden (empfohlen)</label>
        </div>
        
        <hr style="margin: 30px 0; border: none; border-top: 1px solid #e5e7eb;">
        
        <h3 style="font-size: 16px; margin-bottom: 15px; color: #374151;">SMTP (Versand)</h3>
        
        <div id="smtp-test-result" style="display: none; padding: 15px; border-radius: 8px; margin-bottom: 20px;"></div>
        
        <button type="button" id="test-smtp-btn" class="btn btn-secondary" style="margin-bottom: 20px;">
            <span class="btn-text">🔍 SMTP-Verbindung testen</span>
            <span class="btn-spinner"></span>
        </button>
        
        <div class="form-row">
            <div class="form-group">
                <label>SMTP Server</label>
                <input type="text" name="smtp_host" id="smtp_host" placeholder="smtp.example.com">
            </div>
            <div class="form-group">
                <label>Port</label>
                <input type="number" name="smtp_port" id="smtp_port" value="587">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Benutzername</label>
                <input type="text" name="smtp_user" placeholder="user@example.com">
            </div>
            <div class="form-group">
                <label>Passwort</label>
                <input type="password" name="smtp_pass">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Absender E-Mail</label>
                <input type="email" name="smtp_from_email" placeholder="noreply@example.com">
            </div>
            <div class="form-group">
                <label>Absender Name</label>
                <input type="text" name="smtp_from_name" value="CI-Inbox">
            </div>
        </div>
        <div class="checkbox-group">
            <input type="checkbox" name="smtp_ssl" id="smtp_ssl">
            <label for="smtp_ssl">SSL/TLS verwenden (Port 465)</label>
        </div>
        
        <div class="actions">
            <a href="?step=4" class="btn btn-secondary">← Zurück</a>
            <button type="submit" class="btn btn-primary">Weiter →</button>
        </div>
    </form>
    
    <!-- JavaScript moved to setup.js -->
    <?php
}

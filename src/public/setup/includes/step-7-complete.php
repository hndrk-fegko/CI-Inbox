<?php
/**
 * Setup Wizard - Step 7: Installation Complete
 * 
 * Success page with next steps
 */

declare(strict_types=1);

/**
 * Render Step 7 (completion page)
 * No form submission - installation is complete
 * 
 * @param array $sessionData Session data for admin info
 */
function renderStep7(array $sessionData): void
{
    // K4: Clear session after successful installation
    session_destroy();
    
    ?>
    <div style="text-align: center; padding: 40px 20px;">
        <div style="font-size: 72px; margin-bottom: 20px;">🎉</div>
        <h2 class="section-title">Installation erfolgreich!</h2>
        <p class="section-desc" style="font-size: 16px; max-width: 600px; margin: 0 auto 30px;">
            CI-Inbox wurde erfolgreich installiert. Der Setup-Wizard wurde automatisch deaktiviert.<br>
            Sie können sich jetzt mit Ihren Administrator-Zugangsdaten anmelden.
        </p>
        
        <div class="review-section" style="max-width: 500px; margin: 0 auto 30px;">
            <h3>📋 Ihre Zugangsdaten</h3>
            <table class="review-table">
                <tr>
                    <th style="text-align: right;">E-Mail:</th>
                    <td style="text-align: left;"><strong><?= htmlspecialchars($sessionData['admin_email'] ?? 'admin@example.com') ?></strong></td>
                </tr>
                <tr>
                    <th style="text-align: right;">Passwort:</th>
                    <td style="text-align: left;"><em>(Ihr gewähltes Passwort)</em></td>
                </tr>
            </table>
        </div>
        
        <div class="alert alert-success" style="max-width: 600px; margin: 0 auto 30px; background: #d1fae5; border-color: #10b981; color: #065f46;">
            <strong>✓ Folgende Komponenten wurden eingerichtet:</strong>
            <ul style="text-align: left; margin: 15px 0 0 0; padding-left: 20px;">
                <li>Datenbank-Tabellen erstellt</li>
                <li>Administrator-Account angelegt</li>
                <li>Gemeinsame IMAP/SMTP-Inbox konfiguriert</li>
                <?php if (!empty($sessionData['enable_admin_imap'])): ?>
                <li>Persönlicher IMAP-Account für Admin eingerichtet</li>
                <?php endif; ?>
                <li>System-Labels erstellt (Important, Follow-up, In Progress, Done)</li>
                <li>Basis-Einstellungen gespeichert</li>
                <li>.env-Datei und .htaccess geschrieben</li>
            </ul>
        </div>
        
        <div style="margin-top: 40px;">
            <a href="../" class="btn btn-primary" style="font-size: 16px; padding: 12px 30px; text-decoration: none; display: inline-block;">
                → Zur Anmeldung
            </a>
        </div>
        
        <div class="alert alert-warning" style="max-width: 600px; margin: 40px auto 0; font-size: 13px;">
            <strong>🔒 Sicherheitshinweis:</strong> 
            Stellen Sie sicher, dass der <code>/setup</code>-Ordner für die Öffentlichkeit nicht mehr zugänglich ist. 
            Die .htaccess-Datei wurde bereits konfiguriert.
        </div>
        
        <p style="margin-top: 30px; color: #6b7280; font-size: 13px;">
            Dokumentation: <a href="https://github.com/your-repo/ci-inbox" style="color: #3b82f6;">GitHub Repository</a>
        </p>
    </div>
    <?php
}

<?php
/**
 * Setup Wizard - Step 1: Hosting Environment Check
 * 
 * Checks hosting environment compatibility
 */

declare(strict_types=1);

/**
 * Handle Step 1 form submission
 * Just proceeds to next step (no form data)
 */
function handleStep1Submit(): void
{
    updateSessionStep(2);
    redirectToStep(2);
}

/**
 * Render Step 1 form
 * 
 * @param array $hostingChecks Hosting environment check results
 * @param bool $hostingReady Whether environment is ready
 */
function renderStep1Form(array $hostingChecks, bool $hostingReady): void
{
    ?>
    <h2 class="section-title">🌐 Hosting-Umgebung prüfen</h2>
    <p class="section-desc">
        Wir überprüfen, ob Ihre Hosting-Umgebung für CI-Inbox geeignet ist.
        <?php if (!$hostingReady): ?>
            <strong style="color: #ef4444;">⚠️ Kritische Probleme gefunden - Installation kann fehlschlagen!</strong>
        <?php elseif (count(array_filter($hostingChecks, fn($c) => $c['status'] === 'warning')) > 0): ?>
            <strong style="color: #f59e0b;">⚠️ Einige Warnungen - Installation möglich, aber Performance könnte eingeschränkt sein.</strong>
        <?php else: ?>
            <strong style="color: #10b981;">✓ Ihre Umgebung ist für CI-Inbox geeignet!</strong>
        <?php endif; ?>
    </p>
    
    <table class="requirements-table">
        <thead>
            <tr>
                <th>Prüfpunkt</th>
                <th>Aktuell</th>
                <th>Empfohlen</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($hostingChecks as $check): ?>
            <tr>
                <td><strong><?= htmlspecialchars($check['name']) ?></strong></td>
                <td><?= htmlspecialchars($check['value']) ?></td>
                <td><?= htmlspecialchars($check['required']) ?></td>
                <td class="<?= $check['status'] === 'ok' ? 'status-ok' : ($check['status'] === 'warning' ? 'status-warning' : 'status-error') ?>">
                    <?= $check['status'] === 'ok' ? '✓ OK' : ($check['status'] === 'warning' ? '⚠ Warnung' : '✗ Fehler') ?>
                </td>
            </tr>
            <?php if ($check['recommendation']): ?>
            <tr style="background: #fef3c7; border-left: 4px solid #f59e0b;">
                <td colspan="4" style="padding: 12px; font-size: 13px;">
                    <strong>💡 Empfehlung:</strong> <?= htmlspecialchars($check['recommendation']) ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php if (!$hostingReady): ?>
    <div class="alert alert-error" style="margin-top: 20px;">
        <strong>⛔ Installation blockiert</strong><br>
        Bitte beheben Sie die kritischen Fehler oben, bevor Sie fortfahren. 
        Kontaktieren Sie ggf. Ihren Hosting-Anbieter für Hilfe bei der PHP-Konfiguration.
    </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="actions">
            <div></div>
            <button type="submit" class="btn btn-primary" <?= !$hostingReady ? 'disabled' : '' ?>>
                Weiter zu System-Anforderungen →
            </button>
        </div>
    </form>
    <?php
}

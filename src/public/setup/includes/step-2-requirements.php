<?php
/**
 * Setup Wizard - Step 2: Requirements Check
 * 
 * Checks PHP extensions and system requirements
 */

declare(strict_types=1);

/**
 * Handle Step 2 form submission
 * Just proceeds to next step (no form data)
 */
function handleStep2Submit(): void
{
    updateSessionStep(3);
    redirectToStep(3);
}

/**
 * Render Step 2 form
 * 
 * @param array $requirements Requirements check results
 * @param bool $allRequirementsMet Whether all requirements are met
 */
function renderStep2Form(array $requirements, bool $allRequirementsMet): void
{
    ?>
    <h2 class="section-title">Systemanforderungen</h2>
    <p class="section-desc">Überprüfung der erforderlichen PHP-Erweiterungen und Berechtigungen.</p>
    
    <table class="requirements-table">
        <thead>
            <tr>
                <th>Anforderung</th>
                <th>Benötigt</th>
                <th>Aktuell</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($requirements as $req): ?>
            <tr>
                <td><?= htmlspecialchars($req['name']) ?></td>
                <td><?= htmlspecialchars($req['required']) ?></td>
                <td><?= htmlspecialchars($req['current']) ?></td>
                <td class="<?= $req['met'] ? 'status-ok' : 'status-error' ?>">
                    <?= $req['met'] ? '✓ OK' : '✗ Fehlt' ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <form method="POST">
        <div class="actions">
            <a href="?step=1" class="btn btn-secondary">← Zurück</a>
            <button type="submit" class="btn btn-primary" <?= !$allRequirementsMet ? 'disabled' : '' ?>>
                Weiter →
            </button>
        </div>
    </form>
    <?php
}

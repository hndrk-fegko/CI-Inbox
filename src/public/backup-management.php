<?php
/**
 * Backup Management (Admin Only)
 * 
 * Database backup creation and restoration.
 */

session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /login.php');
    exit;
}

$userEmail = $_SESSION['user_email'] ?? 'Unknown';

// Get user's theme preference
$themeMode = 'auto';
try {
    require_once __DIR__ . '/../../vendor/autoload.php';
    require_once __DIR__ . '/../bootstrap/database.php';
    $config = new \CiInbox\Modules\Config\ConfigService(__DIR__ . '/../../');
    initDatabase($config);
    $user = \CiInbox\App\Models\User::find($_SESSION['user_id']);
    if ($user && isset($user->theme_mode)) {
        $themeMode = $user->theme_mode;
    }
} catch (Exception $e) {
    // Fallback
}

// Get existing backups
$backupDir = __DIR__ . '/../../data/backups';
$backups = [];

if (is_dir($backupDir)) {
    $files = glob($backupDir . '/backup-*.sql.gz');
    foreach ($files as $file) {
        $backups[] = [
            'filename' => basename($file),
            'size' => filesize($file),
            'size_mb' => round(filesize($file) / 1024 / 1024, 2),
            'created_at' => filemtime($file),
            'created_at_human' => date('Y-m-d H:i:s', filemtime($file))
        ];
    }
    
    // Sort by date descending
    usort($backups, function($a, $b) {
        return $b['created_at'] - $a['created_at'];
    });
}
?>
<!DOCTYPE html>
<html lang="de" data-user-theme="<?= htmlspecialchars($themeMode) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup Management - C-IMAP</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    
    <!-- Theme Module -->
    <script src="/modules/theme/assets/theme-switcher.js"></script>
    
    <style>
        .backup-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: var(--border-radius-lg);
            overflow: hidden;
        }
        
        .backup-table thead {
            background-color: var(--color-neutral-100);
        }
        
        .backup-table th {
            padding: var(--spacing-3);
            text-align: left;
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-semibold);
            color: var(--color-neutral-700);
            border-bottom: 2px solid var(--border-color);
        }
        
        .backup-table td {
            padding: var(--spacing-3);
            border-bottom: var(--border-width) solid var(--border-color);
            font-size: var(--font-size-sm);
        }
        
        .backup-table tr:last-child td {
            border-bottom: none;
        }
        
        .backup-table tr:hover {
            background-color: var(--color-neutral-50);
        }
        
        .backup-actions {
            display: flex;
            gap: var(--spacing-2);
        }
        
        .empty-state {
            text-align: center;
            padding: var(--spacing-8);
            color: var(--color-neutral-500);
        }
        
        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: var(--spacing-4);
            color: var(--color-neutral-300);
        }
        
        .backup-info-box {
            background-color: var(--color-primary-50);
            border: var(--border-width) solid var(--color-primary-200);
            border-radius: var(--border-radius-md);
            padding: var(--spacing-4);
            margin-bottom: var(--spacing-6);
        }
        
        .backup-info-box h3 {
            font-size: var(--font-size-base);
            font-weight: var(--font-weight-semibold);
            color: var(--color-primary-700);
            margin-bottom: var(--spacing-2);
        }
        
        .backup-info-box ul {
            margin: 0;
            padding-left: var(--spacing-5);
            color: var(--color-primary-900);
            font-size: var(--font-size-sm);
        }
        
        .backup-info-box li {
            margin-bottom: var(--spacing-1);
        }
    </style>
</head>
<body class="l-app">
    <!-- Header -->
    <header class="c-header l-app__header">
        <div class="c-header__left">
            <a href="/inbox.php" class="c-header__logo-link">
                <svg class="c-header__logo" width="32" height="32" viewBox="0 0 48 48" fill="none">
                    <path d="M12 18L24 26L36 18M12 18V30C12 30.5304 12.2107 31.0391 12.5858 31.4142C12.9609 31.7893 13.4696 32 14 32H34C34.5304 32 35.0391 31.7893 35.4142 31.4142C35.7893 31.0391 36 30.5304 36 30V18M12 18C12 17.4696 12.2107 16.9609 12.5858 16.5858C12.9609 16.2107 13.4696 16 14 16H34C34.5304 16 35.0391 16.2107 35.4142 16.5858C35.7893 16.9609 36 17.4696 36 18Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h1 class="c-header__title">C-IMAP - Backup Management</h1>
            </a>
        </div>
        
        <div class="c-header__right">
            <div class="c-user-dropdown">
                <button class="c-user-dropdown__trigger" id="user-dropdown-trigger" aria-expanded="false">
                    <div class="c-avatar c-avatar--sm">
                        <span class="c-avatar__initials"><?= strtoupper(substr($userEmail, 0, 2)) ?></span>
                    </div>
                </button>
                
                <div class="c-user-dropdown__menu" id="user-dropdown-menu">
                    <a href="/inbox.php" class="c-user-dropdown__item">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                        </svg>
                        <span>Inbox</span>
                    </a>
                    <a href="/admin-settings.php" class="c-user-dropdown__item">
                        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                        </svg>
                        <span>Settings</span>
                    </a>
                    <a href="/system-health.php" class="c-user-dropdown__item">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span>System Health</span>
                    </a>
                    <div class="c-user-dropdown__divider"></div>
                    <form method="POST" action="/logout.php" style="margin: 0;">
                        <button type="submit" class="c-user-dropdown__item c-user-dropdown__item--danger">
                            <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 001 1h12a1 1 0 001-1V4a1 1 0 00-1-1H3zm11 4.414l-4.293 4.293a1 1 0 01-1.414 0L4 7.414 5.414 6l3.293 3.293L13.586 6 15 7.414z" clip-rule="evenodd"/>
                            </svg>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="l-app__main" style="padding: var(--spacing-6);">
        <div class="l-container" style="max-width: 1200px; margin: 0 auto;">
            
            <!-- Page Header -->
            <div style="margin-bottom: var(--spacing-6); display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1 style="font-size: var(--font-size-2xl); font-weight: var(--font-weight-bold); margin-bottom: var(--spacing-2);">Backup Management</h1>
                    <p style="color: var(--color-neutral-500);">Datenbank-Backups erstellen und verwalten</p>
                </div>
                
                <button class="c-button c-button--primary" id="create-backup-btn">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Backup erstellen
                </button>
            </div>

            <!-- Info Box -->
            <div class="backup-info-box">
                <h3>💡 Backup-Strategie</h3>
                <ul>
                    <li>Backups werden als komprimierte SQL-Dumps (.sql.gz) gespeichert</li>
                    <li>Empfohlen: Wöchentliches Backup + vor wichtigen Updates</li>
                    <li>Backups enthalten: Threads, E-Mails, Labels, Notizen, User-Daten</li>
                    <li>Nicht enthalten: Anhänge (separat sichern: <code>data/attachments/</code>)</li>
                    <li>Backups automatisch nach 30 Tagen löschen (konfigurierbar)</li>
                </ul>
            </div>

            <!-- Backups Table -->
            <div style="background: white; border: 1px solid var(--border-color); border-radius: var(--border-radius-lg); overflow: hidden;">
                <?php if (empty($backups)): ?>
                <div class="empty-state">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                    <h3 style="margin-bottom: var(--spacing-2);">Keine Backups vorhanden</h3>
                    <p>Erstelle dein erstes Backup, um deine Daten zu sichern.</p>
                </div>
                <?php else: ?>
                <table class="backup-table">
                    <thead>
                        <tr>
                            <th>Dateiname</th>
                            <th>Größe</th>
                            <th>Erstellt am</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody id="backups-table">
                        <?php foreach ($backups as $backup): ?>
                        <tr>
                            <td>
                                <code style="font-family: var(--font-family-mono); font-size: var(--font-size-sm);">
                                    <?= htmlspecialchars($backup['filename']) ?>
                                </code>
                            </td>
                            <td><?= $backup['size_mb'] ?> MB</td>
                            <td><?= htmlspecialchars($backup['created_at_human']) ?></td>
                            <td>
                                <div class="backup-actions">
                                    <button class="c-button c-button--sm" onclick="downloadBackup('<?= htmlspecialchars($backup['filename']) ?>')">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        Download
                                    </button>
                                    <button class="c-button c-button--sm c-button--danger" onclick="deleteBackup('<?= htmlspecialchars($backup['filename']) ?>')">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Löschen
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <script>
        const API_BASE = '/api/admin';
        
        // User dropdown toggle
        document.getElementById('user-dropdown-trigger')?.addEventListener('click', function() {
            const menu = document.getElementById('user-dropdown-menu');
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        });
        
        // Create backup button
        document.getElementById('create-backup-btn')?.addEventListener('click', createBackup);
        
        /**
         * Create new backup
         */
        async function createBackup() {
            const button = document.getElementById('create-backup-btn');
            
            if (!confirm('Backup jetzt erstellen? Dies kann einige Sekunden dauern.')) {
                return;
            }
            
            button.disabled = true;
            button.innerHTML = '<span style="margin-right: 8px;">⏳</span> Erstelle Backup...';
            
            try {
                const response = await fetch(`${API_BASE}/backup/create`, {
                    method: 'POST'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ Backup erfolgreich erstellt!');
                    location.reload();
                } else {
                    alert('❌ Backup fehlgeschlagen: ' + (result.error || 'Unbekannter Fehler'));
                }
            } catch (error) {
                console.error('Backup creation failed:', error);
                alert('❌ Backup fehlgeschlagen: ' + error.message);
            } finally {
                button.disabled = false;
                button.innerHTML = '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> Backup erstellen';
            }
        }
        
        /**
         * Download backup file
         */
        function downloadBackup(filename) {
            window.location.href = `${API_BASE}/backup/download/${filename}`;
        }
        
        /**
         * Delete backup file
         */
        async function deleteBackup(filename) {
            if (!confirm(`Backup "${filename}" wirklich löschen?`)) {
                return;
            }
            
            try {
                const response = await fetch(`${API_BASE}/backup/delete/${filename}`, {
                    method: 'DELETE'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ Backup gelöscht!');
                    location.reload();
                } else {
                    alert('❌ Löschen fehlgeschlagen: ' + (result.error || 'Unbekannter Fehler'));
                }
            } catch (error) {
                console.error('Backup deletion failed:', error);
                alert('❌ Löschen fehlgeschlagen: ' + error.message);
            }
        }
    </script>
</body>
</html>

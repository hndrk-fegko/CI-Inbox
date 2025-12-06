<?php
/**
 * System Health Monitor (Admin Only)
 * 
 * Displays system health status, metrics, and diagnostics.
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
?>
<!DOCTYPE html>
<html lang="de" data-user-theme="<?= htmlspecialchars($themeMode) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Health - CI-Inbox</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    
    <!-- Theme Module -->
    <script src="/modules/theme/assets/theme-switcher.js"></script>
    
    <style>
        /* System Health specific styles */
        .health-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-lg);
            padding: var(--spacing-6);
            margin-bottom: var(--spacing-4);
        }
        
        .health-card__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: var(--spacing-4);
        }
        
        .health-card__title {
            font-size: var(--font-size-lg);
            font-weight: var(--font-weight-semibold);
            color: var(--color-neutral-900);
        }
        
        .health-status {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-2);
            padding: var(--spacing-2) var(--spacing-3);
            border-radius: var(--border-radius-md);
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-medium);
        }
        
        .health-status--healthy {
            background-color: #dcfce7;
            color: #166534;
        }
        
        .health-status--warning {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .health-status--critical {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .health-status__icon {
            width: 16px;
            height: 16px;
        }
        
        .health-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-4);
        }
        
        .health-metric {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-1);
        }
        
        .health-metric__label {
            font-size: var(--font-size-sm);
            color: var(--color-neutral-500);
        }
        
        .health-metric__value {
            font-size: var(--font-size-2xl);
            font-weight: var(--font-weight-bold);
            color: var(--color-neutral-900);
        }
        
        .health-metric__subtext {
            font-size: var(--font-size-xs);
            color: var(--color-neutral-500);
        }
        
        .health-log {
            max-height: 400px;
            overflow-y: auto;
            background-color: var(--color-neutral-50);
            border-radius: var(--border-radius-md);
            padding: var(--spacing-3);
        }
        
        .health-log__entry {
            display: flex;
            gap: var(--spacing-3);
            padding: var(--spacing-2) 0;
            border-bottom: 1px solid var(--border-color);
            font-size: var(--font-size-sm);
        }
        
        .health-log__entry:last-child {
            border-bottom: none;
        }
        
        .health-log__time {
            flex-shrink: 0;
            color: var(--color-neutral-500);
            font-family: var(--font-family-mono);
        }
        
        .health-log__message {
            flex: 1;
            color: var(--color-neutral-700);
        }
        
        .health-chart {
            height: 200px;
            background-color: var(--color-neutral-50);
            border-radius: var(--border-radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--color-neutral-500);
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
                <h1 class="c-header__title">CI-Inbox - System Health</h1>
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
        <div class="l-container" style="max-width: 1400px; margin: 0 auto;">
            
            <!-- Page Header -->
            <div style="margin-bottom: var(--spacing-6);">
                <h1 style="font-size: var(--font-size-2xl); font-weight: var(--font-weight-bold); margin-bottom: var(--spacing-2);">System Health Monitor</h1>
                <p style="color: var(--color-neutral-500);">Überwachung der Systemgesundheit und Performance-Metriken</p>
            </div>

            <!-- Overall Status -->
            <div class="health-card">
                <div class="health-card__header">
                    <h2 class="health-card__title">System Status</h2>
                    <span class="health-status health-status--healthy" id="overall-status">
                        <svg class="health-status__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Loading...</span>
                    </span>
                </div>
                
                <div class="health-metrics" id="system-metrics">
                    <div class="health-metric">
                        <div class="health-metric__label">Database</div>
                        <div class="health-metric__value">—</div>
                        <div class="health-metric__subtext">Connection status</div>
                    </div>
                    <div class="health-metric">
                        <div class="health-metric__label">IMAP</div>
                        <div class="health-metric__value">—</div>
                        <div class="health-metric__subtext">Accounts active</div>
                    </div>
                    <div class="health-metric">
                        <div class="health-metric__label">Disk Space</div>
                        <div class="health-metric__value">—</div>
                        <div class="health-metric__subtext">Available storage</div>
                    </div>
                    <div class="health-metric">
                        <div class="health-metric__label">Uptime</div>
                        <div class="health-metric__value">—</div>
                        <div class="health-metric__subtext">System running</div>
                    </div>
                </div>
            </div>

            <!-- Cron Monitor -->
            <div class="health-card">
                <div class="health-card__header">
                    <h2 class="health-card__title">Cron Jobs Monitor</h2>
                    <button class="c-button c-button--sm" onclick="refreshCronStatus()">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Aktualisieren
                    </button>
                </div>
                
                <div class="health-metrics" id="cron-metrics">
                    <div class="health-metric">
                        <div class="health-metric__label">Last Poll</div>
                        <div class="health-metric__value">—</div>
                        <div class="health-metric__subtext">Email polling</div>
                    </div>
                    <div class="health-metric">
                        <div class="health-metric__label">Poll Interval</div>
                        <div class="health-metric__value">—</div>
                        <div class="health-metric__subtext">Configured</div>
                    </div>
                    <div class="health-metric">
                        <div class="health-metric__label">Success Rate</div>
                        <div class="health-metric__value">—</div>
                        <div class="health-metric__subtext">Last 24h</div>
                    </div>
                    <div class="health-metric">
                        <div class="health-metric__label">Emails Processed</div>
                        <div class="health-metric__value">—</div>
                        <div class="health-metric__subtext">Today</div>
                    </div>
                </div>
            </div>

            <!-- Performance Metrics -->
            <div class="health-card">
                <div class="health-card__header">
                    <h2 class="health-card__title">Performance Metrics</h2>
                </div>
                
                <div class="health-chart" id="performance-chart">
                    📊 Performance Chart (API Response Times)
                </div>
            </div>

            <!-- Recent Errors -->
            <div class="health-card">
                <div class="health-card__header">
                    <h2 class="health-card__title">Recent Errors</h2>
                    <button class="c-button c-button--sm" onclick="loadErrorLog()">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Aktualisieren
                    </button>
                </div>
                
                <div class="health-log" id="error-log">
                    <div style="text-align: center; padding: var(--spacing-6); color: var(--color-neutral-500);">
                        Loading error log...
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script>
        const API_BASE = '/api';
        
        // Load system health on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadSystemHealth();
            loadCronStatus();
            loadErrorLog();
            
            // Auto-refresh every 30 seconds
            setInterval(loadSystemHealth, 30000);
            setInterval(loadCronStatus, 30000);
            
            // User dropdown toggle
            document.getElementById('user-dropdown-trigger')?.addEventListener('click', function() {
                const menu = document.getElementById('user-dropdown-menu');
                menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
            });
        });
        
        /**
         * Load system health status
         */
        async function loadSystemHealth() {
            try {
                const response = await fetch(`${API_BASE}/system/health`);
                const result = await response.json();
                
                if (result.success) {
                    updateSystemMetrics(result.data);
                }
            } catch (error) {
                console.error('Failed to load system health:', error);
            }
        }
        
        /**
         * Update system metrics display
         */
        function updateSystemMetrics(data) {
            // Overall status
            const statusElement = document.getElementById('overall-status');
            const isHealthy = data.database === 'OK' && data.disk_space_free_gb > 1;
            
            statusElement.className = 'health-status ' + (isHealthy ? 'health-status--healthy' : 'health-status--warning');
            statusElement.querySelector('span').textContent = isHealthy ? 'Healthy' : 'Warning';
            
            // Metrics
            const metricsHtml = `
                <div class="health-metric">
                    <div class="health-metric__label">Database</div>
                    <div class="health-metric__value">${data.database === 'OK' ? '✓' : '✗'}</div>
                    <div class="health-metric__subtext">${data.database}</div>
                </div>
                <div class="health-metric">
                    <div class="health-metric__label">IMAP</div>
                    <div class="health-metric__value">${data.imap_accounts || 0}</div>
                    <div class="health-metric__subtext">Accounts configured</div>
                </div>
                <div class="health-metric">
                    <div class="health-metric__label">Disk Space</div>
                    <div class="health-metric__value">${data.disk_space_free_gb?.toFixed(1) || '—'} GB</div>
                    <div class="health-metric__subtext">of ${data.disk_space_total_gb?.toFixed(1) || '—'} GB free</div>
                </div>
                <div class="health-metric">
                    <div class="health-metric__label">PHP Version</div>
                    <div class="health-metric__value">${data.php_version || '—'}</div>
                    <div class="health-metric__subtext">Running</div>
                </div>
            `;
            
            document.getElementById('system-metrics').innerHTML = metricsHtml;
        }
        
        /**
         * Load cron job status
         */
        async function loadCronStatus() {
            try {
                const response = await fetch(`${API_BASE}/system/cron-status`);
                
                if (response.ok) {
                    const result = await response.json();
                    updateCronMetrics(result.data || {});
                } else {
                    // Endpoint might not exist yet
                    updateCronMetrics({ message: 'Not configured' });
                }
            } catch (error) {
                console.error('Failed to load cron status:', error);
                updateCronMetrics({ message: 'Error loading' });
            }
        }
        
        /**
         * Update cron metrics display
         */
        function updateCronMetrics(data) {
            const metricsHtml = `
                <div class="health-metric">
                    <div class="health-metric__label">Last Poll</div>
                    <div class="health-metric__value">${data.last_poll_at || '—'}</div>
                    <div class="health-metric__subtext">${data.minutes_ago ? data.minutes_ago + ' min ago' : 'Never'}</div>
                </div>
                <div class="health-metric">
                    <div class="health-metric__label">Poll Interval</div>
                    <div class="health-metric__value">${data.interval || '15'} min</div>
                    <div class="health-metric__subtext">Configured</div>
                </div>
                <div class="health-metric">
                    <div class="health-metric__label">Success Rate</div>
                    <div class="health-metric__value">${data.success_rate || '—'}%</div>
                    <div class="health-metric__subtext">Last 24h</div>
                </div>
                <div class="health-metric">
                    <div class="health-metric__label">Emails Processed</div>
                    <div class="health-metric__value">${data.emails_today || 0}</div>
                    <div class="health-metric__subtext">Today</div>
                </div>
            `;
            
            document.getElementById('cron-metrics').innerHTML = metricsHtml;
        }
        
        /**
         * Refresh cron status
         */
        function refreshCronStatus() {
            loadCronStatus();
        }
        
        /**
         * Load error log
         */
        async function loadErrorLog() {
            const logContainer = document.getElementById('error-log');
            
            try {
                const response = await fetch(`${API_BASE}/system/errors?limit=20`);
                
                if (response.ok) {
                    const result = await response.json();
                    const errors = result.data?.errors || [];
                    
                    if (errors.length === 0) {
                        logContainer.innerHTML = `
                            <div style="text-align: center; padding: var(--spacing-6); color: var(--color-success);">
                                ✓ No recent errors
                            </div>
                        `;
                    } else {
                        let html = '';
                        errors.forEach(error => {
                            html += `
                                <div class="health-log__entry">
                                    <div class="health-log__time">${error.time || '—'}</div>
                                    <div class="health-log__message">${error.message || 'Unknown error'}</div>
                                </div>
                            `;
                        });
                        logContainer.innerHTML = html;
                    }
                } else {
                    logContainer.innerHTML = `
                        <div style="text-align: center; padding: var(--spacing-6); color: var(--color-neutral-500);">
                            Error log endpoint not available
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Failed to load error log:', error);
                logContainer.innerHTML = `
                    <div style="text-align: center; padding: var(--spacing-6); color: var(--color-danger);">
                        Failed to load error log
                    </div>
                `;
            }
        }
    </script>
</body>
</html>

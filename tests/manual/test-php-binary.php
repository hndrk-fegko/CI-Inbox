<?php
/**
 * Test PHP Binary Detection (Web Context)
 * 
 * Access this via browser to see what PHP_BINARY returns when running under Apache
 * URL: http://ci-inbox.local/test-php-binary.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Binary Detection - Web Context</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 40px;
            line-height: 1.6;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: #252526;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
        }
        h1 {
            color: #4ec9b0;
            margin-bottom: 20px;
            font-size: 24px;
        }
        h2 {
            color: #569cd6;
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .info-box {
            background: #1e1e1e;
            border-left: 4px solid #4ec9b0;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .warning-box {
            background: #332b00;
            border-left: 4px solid #ce9178;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .error-box {
            background: #330000;
            border-left: 4px solid #f48771;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .key {
            color: #9cdcfe;
            display: inline-block;
            min-width: 200px;
        }
        .value {
            color: #ce9178;
        }
        .success { color: #4ec9b0; }
        .fail { color: #f48771; }
        code {
            background: #1e1e1e;
            padding: 2px 6px;
            border-radius: 3px;
            color: #ce9178;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid #3c3c3c;
        }
        th {
            color: #569cd6;
            background: #1e1e1e;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 PHP Binary Detection Test (Web Context)</h1>
        <p style="color: #858585; margin-bottom: 20px;">
            This page shows how PHP_BINARY behaves when running through Apache/Webserver.
        </p>

        <h2>1. PHP_BINARY Constant</h2>
        <div class="info-box">
            <div><span class="key">Value:</span> <span class="value"><code><?= htmlspecialchars(PHP_BINARY) ?></code></span></div>
            <div><span class="key">File Exists:</span> 
                <?php if (file_exists(PHP_BINARY)): ?>
                    <span class="success">✅ Yes</span>
                <?php else: ?>
                    <span class="fail">❌ No</span>
                <?php endif; ?>
            </div>
            <div><span class="key">Contains 'php.exe':</span> 
                <?php if (stripos(PHP_BINARY, 'php.exe') !== false): ?>
                    <span class="success">✅ Yes (Valid PHP CLI)</span>
                <?php else: ?>
                    <span class="fail">❌ No (Probably httpd.exe or other webserver)</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if (stripos(PHP_BINARY, 'httpd.exe') !== false || stripos(PHP_BINARY, 'apache') !== false): ?>
        <div class="warning-box">
            <strong>⚠️ Problem Detected!</strong><br>
            PHP_BINARY points to the Apache web server binary (<code>httpd.exe</code>), not PHP CLI.
            This is why Composer installation fails when using <code>PHP_BINARY</code> directly.
        </div>
        <?php endif; ?>

        <h2>2. XAMPP Standard Paths Detection</h2>
        <table>
            <thead>
                <tr>
                    <th>Path</th>
                    <th>Status</th>
                    <th>Priority</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $possiblePaths = [
                    'C:\\xampp\\php\\php.exe' => 'Highest (Lowercase)',
                    'C:\\XAMPP\\php\\php.exe' => 'High (Uppercase)',
                    'C:\\xampp7\\php\\php.exe' => 'Medium (XAMPP 7)'
                ];
                
                $detectedPath = null;
                foreach ($possiblePaths as $path => $priority):
                    $exists = file_exists($path);
                    if ($exists && !$detectedPath) {
                        $detectedPath = $path;
                    }
                ?>
                <tr>
                    <td><code><?= htmlspecialchars($path) ?></code></td>
                    <td>
                        <?php if ($exists): ?>
                            <span class="success">✅ Found</span>
                            <?php if ($path === $detectedPath): ?>
                                <strong>(WOULD BE USED)</strong>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="fail">❌ Not found</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $priority ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>3. Final Detection Logic Result</h2>
        <?php
        // Simulate detection logic from setup script
        $phpPath = null;
        $detectionMethod = '';
        
        // On Windows XAMPP, try common PHP CLI paths first
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $paths = [
                'C:\\xampp\\php\\php.exe',
                'C:\\XAMPP\\php\\php.exe',
                'C:\\xampp7\\php\\php.exe'
            ];
            foreach ($paths as $path) {
                if (file_exists($path)) {
                    $phpPath = $path;
                    $detectionMethod = 'XAMPP Standard Path (Preferred)';
                    break;
                }
            }
        }
        
        // Fallback: Try PHP_BINARY if it points to php.exe
        if (!$phpPath && PHP_BINARY && stripos(PHP_BINARY, 'php.exe') !== false && file_exists(PHP_BINARY)) {
            $phpPath = PHP_BINARY;
            $detectionMethod = 'PHP_BINARY Fallback (Validated)';
        }
        
        // Last resort
        if (!$phpPath) {
            $phpPath = 'php';
            $detectionMethod = 'PATH Environment Variable (Last Resort)';
        }
        ?>
        <div class="info-box">
            <div><span class="key">Detected PHP Path:</span> <span class="value"><code><?= htmlspecialchars($phpPath) ?></code></span></div>
            <div><span class="key">Detection Method:</span> <span class="value"><?= htmlspecialchars($detectionMethod) ?></span></div>
            <div><span class="key">Would work for Composer:</span> 
                <?php if (strpos($phpPath, 'xampp') !== false || $phpPath === 'php'): ?>
                    <span class="success">✅ Yes</span>
                <?php else: ?>
                    <span class="fail">❌ Uncertain</span>
                <?php endif; ?>
            </div>
        </div>

        <h2>4. System Information</h2>
        <table>
            <tbody>
                <tr>
                    <td><strong>PHP Version</strong></td>
                    <td><?= PHP_VERSION ?></td>
                </tr>
                <tr>
                    <td><strong>Operating System</strong></td>
                    <td><?= PHP_OS ?></td>
                </tr>
                <tr>
                    <td><strong>SAPI (Server API)</strong></td>
                    <td><?= php_sapi_name() ?></td>
                </tr>
                <tr>
                    <td><strong>Server Software</strong></td>
                    <td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></td>
                </tr>
                <tr>
                    <td><strong>Document Root</strong></td>
                    <td><code><?= htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') ?></code></td>
                </tr>
            </tbody>
        </table>

        <h2>5. Explanation</h2>
        <div class="info-box">
            <p><strong>Why does PHP_BINARY point to httpd.exe in web context?</strong></p>
            <p style="margin-top: 10px;">
                When PHP runs as an Apache module or via CGI/FastCGI, <code>PHP_BINARY</code> returns the 
                path to the web server process that loaded PHP, not the CLI <code>php.exe</code> binary.
            </p>
            <p style="margin-top: 10px;">
                <strong>Solution:</strong> The setup script now checks XAMPP standard paths first, 
                validates PHP_BINARY before using it, and only falls back to PATH-based detection.
            </p>
        </div>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #3c3c3c; color: #858585; text-align: center;">
            <p>CI-Inbox Setup - PHP Binary Detection Test</p>
            <p>For CLI test, run: <code>php test-php-detection.php</code></p>
        </div>
    </div>
</body>
</html>

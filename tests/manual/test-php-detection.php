<?php
/**
 * Test PHP Binary Detection (CLI Context)
 * 
 * Run this from CLI to see what PHP_BINARY returns
 * Usage: C:\xampp\php\php.exe test-php-detection.php
 */

echo "=== PHP Binary Detection Test (CLI) ===" . PHP_EOL . PHP_EOL;

// 1. Show what PHP_BINARY returns
echo "1. PHP_BINARY constant:" . PHP_EOL;
echo "   Value: " . PHP_BINARY . PHP_EOL;
echo "   Exists: " . (file_exists(PHP_BINARY) ? "✅ Yes" : "❌ No") . PHP_EOL;
echo "   Is php.exe: " . (stripos(PHP_BINARY, 'php.exe') !== false ? "✅ Yes" : "❌ No (probably httpd.exe)") . PHP_EOL;
echo PHP_EOL;

// 2. Test XAMPP path detection
echo "2. XAMPP Standard Paths:" . PHP_EOL;
$possiblePaths = [
    'C:\\xampp\\php\\php.exe',
    'C:\\XAMPP\\php\\php.exe',
    'C:\\xampp7\\php\\php.exe'
];

foreach ($possiblePaths as $path) {
    $exists = file_exists($path);
    echo "   " . $path . ": " . ($exists ? "✅ Found" : "❌ Not found") . PHP_EOL;
    if ($exists) {
        echo "      → This would be used!" . PHP_EOL;
        break;
    }
}
echo PHP_EOL;

// 3. Detect using same logic as setup script
echo "3. Detection Logic (Simulated):" . PHP_EOL;
$phpPath = null;

// On Windows XAMPP, try common PHP CLI paths first
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $phpPath = $path;
            break;
        }
    }
}

// Fallback: Try PHP_BINARY if it points to php.exe (not httpd.exe)
if (!$phpPath && PHP_BINARY && stripos(PHP_BINARY, 'php.exe') !== false && file_exists(PHP_BINARY)) {
    $phpPath = PHP_BINARY;
}

// Last resort: hope 'php' is in PATH
if (!$phpPath) {
    $phpPath = 'php';
}

echo "   Final detected path: " . $phpPath . PHP_EOL;
echo "   Detection method: ";
if (strpos($phpPath, 'xampp') !== false) {
    echo "XAMPP Standard Path (preferred)" . PHP_EOL;
} elseif ($phpPath === PHP_BINARY) {
    echo "PHP_BINARY Fallback (validated)" . PHP_EOL;
} else {
    echo "PATH Environment Variable (last resort)" . PHP_EOL;
}
echo PHP_EOL;

// 4. Test execution
echo "4. Execution Test:" . PHP_EOL;
$testCommand = $phpPath . ' --version';
echo "   Command: {$testCommand}" . PHP_EOL;
$output = shell_exec($testCommand . ' 2>&1');
if ($output) {
    echo "   Result: ✅ Success" . PHP_EOL;
    echo "   Output: " . trim(substr($output, 0, 100)) . "..." . PHP_EOL;
} else {
    echo "   Result: ❌ Failed" . PHP_EOL;
}
echo PHP_EOL;

// 5. System info
echo "5. System Information:" . PHP_EOL;
echo "   PHP Version: " . PHP_VERSION . PHP_EOL;
echo "   Operating System: " . PHP_OS . PHP_EOL;
echo "   SAPI: " . php_sapi_name() . PHP_EOL;
echo PHP_EOL;

echo "=== Test Complete ===" . PHP_EOL;

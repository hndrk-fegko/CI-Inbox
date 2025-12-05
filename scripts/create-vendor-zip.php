#!/usr/bin/env php
<?php
/**
 * Create vendor.zip for manual deployment
 * 
 * This script creates a deployable vendor.zip archive containing all
 * Composer dependencies for users who cannot run composer install on
 * their hosting environment.
 * 
 * Usage: php scripts/create-vendor-zip.php
 */

$rootDir = dirname(__DIR__);
$vendorDir = $rootDir . '/vendor';
$outputFile = $rootDir . '/vendor.zip';

echo "=== CI-Inbox Vendor Archive Creator ===" . PHP_EOL . PHP_EOL;

// Check if vendor exists
if (!is_dir($vendorDir)) {
    echo "❌ Error: vendor/ directory not found!" . PHP_EOL;
    echo "   Please run 'composer install' first." . PHP_EOL;
    exit(1);
}

// Check if ZipArchive is available
if (!class_exists('ZipArchive')) {
    echo "❌ Error: ZipArchive extension not available!" . PHP_EOL;
    echo "   Please enable zip extension in php.ini" . PHP_EOL;
    exit(1);
}

echo "📦 Creating vendor.zip archive..." . PHP_EOL;
echo "   Source: {$vendorDir}" . PHP_EOL;
echo "   Output: {$outputFile}" . PHP_EOL . PHP_EOL;

// Create zip archive
$zip = new ZipArchive();
if ($zip->open($outputFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    echo "❌ Error: Cannot create zip file!" . PHP_EOL;
    exit(1);
}

// Add vendor directory recursively
$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($vendorDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$fileCount = 0;
$totalSize = 0;

foreach ($files as $file) {
    if (!$file->isDir()) {
        $filePath = $file->getRealPath();
        $relativePath = 'vendor/' . substr($filePath, strlen($vendorDir) + 1);
        
        // Skip unnecessary files
        if (shouldSkipFile($relativePath)) {
            continue;
        }
        
        $zip->addFile($filePath, $relativePath);
        $fileCount++;
        $totalSize += $file->getSize();
        
        // Progress indicator
        if ($fileCount % 100 === 0) {
            echo "   Processed {$fileCount} files..." . PHP_EOL;
        }
    }
}

$zip->close();

// Get final zip size
$zipSize = filesize($outputFile);

echo PHP_EOL . "✅ Success!" . PHP_EOL;
echo "   Files packed: {$fileCount}" . PHP_EOL;
echo "   Original size: " . formatBytes($totalSize) . PHP_EOL;
echo "   Compressed size: " . formatBytes($zipSize) . PHP_EOL;
echo "   Compression ratio: " . round((1 - $zipSize / $totalSize) * 100, 1) . "%" . PHP_EOL;
echo PHP_EOL;
echo "📤 Upload vendor.zip to:" . PHP_EOL;
echo "   - GitHub Release (recommended)" . PHP_EOL;
echo "   - Dropbox/Google Drive" . PHP_EOL;
echo "   - Your own CDN/Server" . PHP_EOL;

/**
 * Check if file should be skipped
 */
function shouldSkipFile(string $path): bool
{
    $skipPatterns = [
        '/\.git/',           // Git files
        '/tests?/',          // Test directories
        '/docs?/',           // Documentation
        '/examples?/',       // Example files
        '/\.md$/',           // Markdown files
        '/\.txt$/',          // Text files (except LICENSE)
        '/phpunit\.xml/',    // PHPUnit config
        '/\.phpcs\.xml/',    // PHP_CodeSniffer
        '/\.editorconfig/',  // EditorConfig
        '/CHANGELOG/',       // Changelogs
        '/CONTRIBUTING/',    // Contributing guides
    ];
    
    foreach ($skipPatterns as $pattern) {
        if (preg_match($pattern, $path)) {
            // Keep LICENSE files
            if (stripos($path, 'LICENSE') !== false) {
                return false;
            }
            return true;
        }
    }
    
    return false;
}

/**
 * Format bytes to human-readable
 */
function formatBytes(int $bytes, int $precision = 2): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

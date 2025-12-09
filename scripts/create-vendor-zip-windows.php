#!/usr/bin/env php
<?php
/**
 * Create vendor.zip for Windows XAMPP/WAMP Stack
 * 
 * This script creates a Windows-optimized vendor.zip for users running
 * CI-Inbox on Windows servers (XAMPP, WAMP, IIS with PHP).
 * 
 * Different from Linux version:
 * - Windows-compiled PHP extensions
 * - Windows path separators
 * - Useful for local dev or Windows production servers
 * 
 * Usage: php scripts/create-vendor-zip-windows.php
 * 
 * Note: GitHub Actions creates Linux-optimized vendor.zip automatically.
 *       This script is for Windows-specific deployments only.
 */

declare(strict_types=1);

$rootDir = dirname(__DIR__);
$vendorDir = $rootDir . '/vendor';
$outputFile = $rootDir . '/vendor-windows.zip';

echo "=== CI-Inbox Vendor Archive Creator (Windows) ===" . PHP_EOL . PHP_EOL;

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

echo "ℹ️  Creating Windows-optimized vendor package" . PHP_EOL;
echo "   For Windows servers: XAMPP, WAMP, IIS + PHP" . PHP_EOL;
echo "   For Linux servers: Use vendor.zip from GitHub Release" . PHP_EOL . PHP_EOL;

echo "📦 Creating vendor-windows.zip archive..." . PHP_EOL;
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

echo "📁 Adding files to archive..." . PHP_EOL;

foreach ($files as $file) {
    if ($file->isFile()) {
        $filePath = $file->getRealPath();
        $relativePath = 'vendor/' . substr($filePath, strlen($vendorDir) + 1);
        
        // Normalize path separators for zip (use forward slashes)
        $relativePath = str_replace('\\', '/', $relativePath);
        
        $zip->addFile($filePath, $relativePath);
        $fileCount++;
        $totalSize += $file->getSize();
        
        // Progress indicator
        if ($fileCount % 500 === 0) {
            echo "   Added {$fileCount} files..." . PHP_EOL;
        }
    }
}

$zip->close();

$zipSize = filesize($outputFile);
$compressionRatio = round((1 - ($zipSize / $totalSize)) * 100, 1);

echo PHP_EOL . "✅ Windows vendor package created successfully!" . PHP_EOL . PHP_EOL;

echo "📊 Statistics:" . PHP_EOL;
echo "   Files added: " . number_format($fileCount) . PHP_EOL;
echo "   Uncompressed size: " . round($totalSize / 1024 / 1024, 2) . " MB" . PHP_EOL;
echo "   Compressed size: " . round($zipSize / 1024 / 1024, 2) . " MB" . PHP_EOL;
echo "   Compression ratio: {$compressionRatio}%" . PHP_EOL . PHP_EOL;

echo "📦 Archive: {$outputFile}" . PHP_EOL . PHP_EOL;

echo "💡 Usage Instructions:" . PHP_EOL;
echo "   1. Extract vendor-windows.zip in project root" . PHP_EOL;
echo "   2. Ensure vendor/ directory exists" . PHP_EOL;
echo "   3. Ready for Windows server deployment" . PHP_EOL . PHP_EOL;

echo "⚠️  Platform Note:" . PHP_EOL;
echo "   This package is optimized for Windows servers" . PHP_EOL;
echo "   For Linux deployments, use vendor.zip from GitHub Releases" . PHP_EOL;
echo "   (Linux version is built automatically via GitHub Actions)" . PHP_EOL;

exit(0);

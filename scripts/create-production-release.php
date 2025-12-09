#!/usr/bin/env php
<?php
/**
 * CI-Inbox Production Release Builder
 * 
 * Creates a clean, production-ready release package without development files.
 * Uses .deployignore to determine which files to exclude from the release.
 * 
 * Requirements:
 * - vendor/ directory must exist (run composer install --no-dev first)
 * - PHP zip extension must be enabled
 * 
 * Usage: php scripts/create-production-release.php
 * Output: ci-inbox-production.zip (in project root)
 */

declare(strict_types=1);

$rootDir = dirname(__DIR__);
$deployIgnoreFile = $rootDir . '/.deployignore';
$outputFile = $rootDir . '/ci-inbox-production.zip';

echo "=== CI-Inbox Production Release Builder ===" . PHP_EOL . PHP_EOL;

echo "ℹ️  Note: vendor/ is excluded from production releases" . PHP_EOL;
echo "   Dependencies will be installed on target system via:" . PHP_EOL;
echo "   1. Setup wizard auto-install (composer install)" . PHP_EOL;
echo "   2. Fallback: Manual vendor.zip download" . PHP_EOL . PHP_EOL;

// Check if ZipArchive is available
if (!class_exists('ZipArchive')) {
    echo "❌ Error: ZipArchive extension not available!" . PHP_EOL;
    echo "   Please enable zip extension in php.ini" . PHP_EOL;
    exit(1);
}

// Load .deployignore patterns
$ignorePatterns = [];
if (file_exists($deployIgnoreFile)) {
    echo "📋 Loading exclusion patterns from .deployignore..." . PHP_EOL;
    $lines = file($deployIgnoreFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Skip comments and empty lines
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        // Skip negations (!) for now - include these files
        if (str_starts_with($line, '!')) {
            continue;
        }
        $ignorePatterns[] = $line;
    }
    echo "   Found " . count($ignorePatterns) . " exclusion patterns" . PHP_EOL . PHP_EOL;
} else {
    echo "⚠️  Warning: .deployignore not found - including all files!" . PHP_EOL . PHP_EOL;
}

/**
 * Check if a path should be ignored based on .deployignore patterns
 */
function shouldIgnore(string $relativePath, array $patterns): bool {
    foreach ($patterns as $pattern) {
        // Remove leading slash for comparison
        $pattern = ltrim($pattern, '/');
        
        // Handle directory patterns (ending with /)
        if (str_ends_with($pattern, '/')) {
            $pattern = rtrim($pattern, '/');
            if (str_starts_with($relativePath, $pattern . '/') || $relativePath === $pattern) {
                return true;
            }
        }
        // Handle wildcard patterns
        elseif (str_contains($pattern, '*')) {
            $regexPattern = '#^' . str_replace(['/', '*'], ['\/', '.*'], $pattern) . '$#';
            if (preg_match($regexPattern, $relativePath)) {
                return true;
            }
        }
        // Exact match
        elseif ($relativePath === $pattern || str_starts_with($relativePath, $pattern . '/')) {
            return true;
        }
    }
    return false;
}

echo "📦 Creating production release archive..." . PHP_EOL;
echo "   Source: {$rootDir}" . PHP_EOL;
echo "   Output: {$outputFile}" . PHP_EOL . PHP_EOL;

// Create zip archive
$zip = new ZipArchive();
if ($zip->open($outputFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    echo "❌ Error: Cannot create zip file!" . PHP_EOL;
    exit(1);
}

// Recursively add files
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($rootDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$includedFiles = 0;
$excludedFiles = 0;
$excludedDirs = [];

echo "🔍 Processing files..." . PHP_EOL;

foreach ($iterator as $item) {
    $realPath = $item->getRealPath();
    $relativePath = str_replace($rootDir . DIRECTORY_SEPARATOR, '', $realPath);
    $relativePath = str_replace('\\', '/', $relativePath); // Normalize path separators
    
    // Skip the output file itself
    if ($realPath === $outputFile) {
        continue;
    }
    
    // Check if should be ignored
    if (shouldIgnore($relativePath, $ignorePatterns)) {
        $excludedFiles++;
        if ($item->isDir() && !in_array($relativePath, $excludedDirs)) {
            $excludedDirs[] = $relativePath;
            echo "   ⊗ Excluding: {$relativePath}/" . PHP_EOL;
        }
        continue;
    }
    
    // Skip if parent directory is excluded
    $parentExcluded = false;
    foreach ($excludedDirs as $excludedDir) {
        if (str_starts_with($relativePath, $excludedDir . '/')) {
            $parentExcluded = true;
            break;
        }
    }
    if ($parentExcluded) {
        $excludedFiles++;
        continue;
    }
    
    // Add to zip with relative path
    if ($item->isDir()) {
        $zip->addEmptyDir('ci-inbox/' . $relativePath);
    } else {
        $zip->addFile($realPath, 'ci-inbox/' . $relativePath);
        $includedFiles++;
    }
}

// Add .env.example as template (if exists and not excluded)
$envExample = $rootDir . '/.env.example';
if (file_exists($envExample) && !shouldIgnore('.env.example', $ignorePatterns)) {
    $zip->addFile($envExample, 'ci-inbox/.env.example');
}

$zip->close();

echo PHP_EOL . "✅ Production release created successfully!" . PHP_EOL . PHP_EOL;
echo "📊 Statistics:" . PHP_EOL;
echo "   Files included: {$includedFiles}" . PHP_EOL;
echo "   Items excluded: {$excludedFiles}" . PHP_EOL;
echo "   Archive size: " . round(filesize($outputFile) / 1024 / 1024, 2) . " MB" . PHP_EOL . PHP_EOL;

echo "📦 Release package: {$outputFile}" . PHP_EOL;
echo PHP_EOL . "🚀 Ready for deployment!" . PHP_EOL;
echo "   Users can download and extract this file directly on their server." . PHP_EOL;
echo "   All development files, docs, and tools have been excluded." . PHP_EOL;

exit(0);

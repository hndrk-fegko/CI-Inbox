# CI-Inbox Vendor Archive Creator (PowerShell)
# Creates vendor.zip for manual deployment
# Usage: .\scripts\create-vendor-zip.ps1

Write-Host "=== CI-Inbox Vendor Archive Creator ===" -ForegroundColor Cyan
Write-Host ""

$rootDir = Split-Path $PSScriptRoot -Parent
$vendorDir = Join-Path $rootDir "vendor"
$outputFile = Join-Path $rootDir "vendor.zip"

# Check if vendor exists
if (-not (Test-Path $vendorDir)) {
    Write-Host "❌ Error: vendor/ directory not found!" -ForegroundColor Red
    Write-Host "   Please run 'composer install' first." -ForegroundColor Yellow
    exit 1
}

Write-Host "📦 Creating vendor.zip archive..." -ForegroundColor Green
Write-Host "   Source: $vendorDir"
Write-Host "   Output: $outputFile"
Write-Host ""

# Remove old zip if exists
if (Test-Path $outputFile) {
    Remove-Item $outputFile -Force
}

# Create zip using .NET
Add-Type -AssemblyName System.IO.Compression.FileSystem

try {
    # Get all files
    $files = Get-ChildItem -Path $vendorDir -Recurse -File
    $fileCount = 0
    $totalSize = 0
    
    # Create zip
    $zipArchive = [System.IO.Compression.ZipFile]::Open($outputFile, [System.IO.Compression.ZipArchiveMode]::Create)
    
    foreach ($file in $files) {
        $relativePath = $file.FullName.Substring($vendorDir.Length + 1)
        $zipPath = "vendor/$relativePath"
        
        # Skip unnecessary files
        if ($relativePath -match '\.(md|txt)$' -and $relativePath -notmatch 'LICENSE') {
            continue
        }
        if ($relativePath -match '(test|example|doc)s?/') {
            continue
        }
        
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zipArchive, $file.FullName, $zipPath) | Out-Null
        $fileCount++
        $totalSize += $file.Length
        
        if ($fileCount % 100 -eq 0) {
            Write-Host "   Processed $fileCount files..." -ForegroundColor Gray
        }
    }
    
    $zipArchive.Dispose()
    
    # Get final size
    $zipSize = (Get-Item $outputFile).Length
    
    Write-Host ""
    Write-Host "✅ Success!" -ForegroundColor Green
    Write-Host "   Files packed: $fileCount"
    Write-Host "   Original size: $([math]::Round($totalSize / 1MB, 2)) MB"
    Write-Host "   Compressed size: $([math]::Round($zipSize / 1MB, 2)) MB"
    Write-Host "   Compression ratio: $([math]::Round((1 - $zipSize / $totalSize) * 100, 1))%"
    Write-Host ""
    Write-Host "📤 Upload vendor.zip to:" -ForegroundColor Cyan
    Write-Host "   - GitHub Release (recommended)"
    Write-Host "   - Dropbox/Google Drive"
    Write-Host "   - Your own CDN/Server"
    
} catch {
    Write-Host "❌ Error creating zip: $_" -ForegroundColor Red
    exit 1
}

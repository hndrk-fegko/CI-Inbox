<?php
/**
 * Backup Service Manual Test
 * 
 * Tests backup creation, listing, and deletion
 * Usage: php tests/manual/backup-service-test.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use CiInbox\Modules\Config\ConfigService;
use CiInbox\Modules\Logger\LoggerService;
use CiInbox\App\Services\BackupService;

// Initialize system
$config = new ConfigService(__DIR__ . '/../../');
require_once __DIR__ . '/../../src/bootstrap/database.php';
initDatabase($config);

$logger = new LoggerService(__DIR__ . '/../../logs/');

echo "=== Backup Service Test ===" . PHP_EOL . PHP_EOL;

// TEST 1: Initialize BackupService
echo "TEST 1: Initialize BackupService" . PHP_EOL;
try {
    $backupService = new BackupService($logger, $config);
    echo "✅ BackupService initialized" . PHP_EOL;
} catch (Exception $e) {
    echo "❌ Failed: {$e->getMessage()}" . PHP_EOL;
    exit(1);
}
echo PHP_EOL;

// TEST 2: List existing backups
echo "TEST 2: List existing backups" . PHP_EOL;
try {
    $backups = $backupService->listBackups();
    echo "✅ Found " . count($backups) . " backup(s)" . PHP_EOL;
    
    if (count($backups) > 0) {
        echo "   Latest backup: " . $backups[0]['filename'] . " (" . $backups[0]['size_mb'] . " MB)" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "❌ Failed: {$e->getMessage()}" . PHP_EOL;
    exit(1);
}
echo PHP_EOL;

// TEST 3: Create new backup
echo "TEST 3: Create new backup" . PHP_EOL;
try {
    echo "   Creating backup (this may take a few seconds)..." . PHP_EOL;
    $backup = $backupService->createBackup();
    
    echo "✅ Backup created successfully!" . PHP_EOL;
    echo "   Filename: " . $backup['filename'] . PHP_EOL;
    echo "   Size: " . $backup['size_mb'] . " MB" . PHP_EOL;
    echo "   Compression ratio: " . $backup['compression_ratio'] . PHP_EOL;
    echo "   Path: " . $backup['path'] . PHP_EOL;
    
    $testBackupFilename = $backup['filename'];
} catch (Exception $e) {
    echo "❌ Failed: {$e->getMessage()}" . PHP_EOL;
    echo "   Error details: " . print_r($e->getTrace(), true) . PHP_EOL;
    exit(1);
}
echo PHP_EOL;

// TEST 4: Verify backup file exists
echo "TEST 4: Verify backup file exists" . PHP_EOL;
try {
    $path = $backupService->getBackupPath($testBackupFilename);
    
    if ($path && file_exists($path)) {
        echo "✅ Backup file exists: $path" . PHP_EOL;
        echo "   File size: " . round(filesize($path) / 1024 / 1024, 2) . " MB" . PHP_EOL;
    } else {
        echo "❌ Backup file not found!" . PHP_EOL;
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Failed: {$e->getMessage()}" . PHP_EOL;
    exit(1);
}
echo PHP_EOL;

// TEST 5: List backups again (should show new backup)
echo "TEST 5: List backups again" . PHP_EOL;
try {
    $backups = $backupService->listBackups();
    echo "✅ Found " . count($backups) . " backup(s)" . PHP_EOL;
    
    // Find our test backup
    $found = false;
    foreach ($backups as $backup) {
        if ($backup['filename'] === $testBackupFilename) {
            $found = true;
            echo "   ✅ Test backup found in list: " . $backup['filename'] . PHP_EOL;
            break;
        }
    }
    
    if (!$found) {
        echo "   ❌ Test backup NOT found in list!" . PHP_EOL;
        exit(1);
    }
} catch (Exception $e) {
    echo "❌ Failed: {$e->getMessage()}" . PHP_EOL;
    exit(1);
}
echo PHP_EOL;

// TEST 6: Test invalid filename (security check)
echo "TEST 6: Test invalid filename rejection" . PHP_EOL;
try {
    $invalidPath = $backupService->getBackupPath('../../etc/passwd');
    
    if ($invalidPath === null) {
        echo "✅ Invalid filename correctly rejected" . PHP_EOL;
    } else {
        echo "❌ SECURITY ISSUE: Invalid filename was accepted!" . PHP_EOL;
        exit(1);
    }
} catch (Exception $e) {
    echo "✅ Invalid filename correctly rejected with exception" . PHP_EOL;
}
echo PHP_EOL;

// TEST 7: Delete test backup
echo "TEST 7: Delete test backup" . PHP_EOL;
$deleteChoice = readline("Delete test backup? (y/n): ");

if (strtolower(trim($deleteChoice)) === 'y') {
    try {
        $success = $backupService->deleteBackup($testBackupFilename);
        
        if ($success) {
            echo "✅ Test backup deleted successfully" . PHP_EOL;
            
            // Verify deletion
            $path = $backupService->getBackupPath($testBackupFilename);
            if ($path === null || !file_exists($path)) {
                echo "   ✅ Verified: Backup file no longer exists" . PHP_EOL;
            } else {
                echo "   ❌ WARNING: Backup file still exists!" . PHP_EOL;
            }
        } else {
            echo "❌ Deletion returned false" . PHP_EOL;
        }
    } catch (Exception $e) {
        echo "❌ Failed: {$e->getMessage()}" . PHP_EOL;
        exit(1);
    }
} else {
    echo "ℹ️  Skipped deletion. Test backup remains: $testBackupFilename" . PHP_EOL;
}
echo PHP_EOL;

// TEST 8: Test cleanup old backups (dry run simulation)
echo "TEST 8: Test cleanup old backups logic" . PHP_EOL;
try {
    $backups = $backupService->listBackups();
    $oldCount = 0;
    $retentionDays = 30;
    $cutoffTime = time() - ($retentionDays * 24 * 60 * 60);
    
    foreach ($backups as $backup) {
        if ($backup['created_at'] < $cutoffTime) {
            $oldCount++;
        }
    }
    
    echo "✅ Cleanup logic check complete" . PHP_EOL;
    echo "   Total backups: " . count($backups) . PHP_EOL;
    echo "   Backups older than $retentionDays days: $oldCount" . PHP_EOL;
    
    if ($oldCount > 0) {
        $cleanupChoice = readline("Run cleanup to delete $oldCount old backup(s)? (y/n): ");
        
        if (strtolower(trim($cleanupChoice)) === 'y') {
            $deleted = $backupService->cleanupOldBackups($retentionDays);
            echo "   ✅ Deleted $deleted old backup(s)" . PHP_EOL;
        } else {
            echo "   ℹ️  Cleanup skipped" . PHP_EOL;
        }
    }
} catch (Exception $e) {
    echo "❌ Failed: {$e->getMessage()}" . PHP_EOL;
    exit(1);
}
echo PHP_EOL;

echo "=== All Tests Completed ===" . PHP_EOL;
echo "✅ Backup Service is working correctly!" . PHP_EOL;

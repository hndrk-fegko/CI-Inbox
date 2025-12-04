<?php

declare(strict_types=1);

namespace CiInbox\App\Services;

use CiInbox\Modules\Logger\LoggerInterface;
use CiInbox\Modules\Config\ConfigService;

/**
 * Backup Service
 * 
 * Handles database backup creation, restoration, and management.
 */
class BackupService
{
    private LoggerInterface $logger;
    private ConfigService $config;
    private string $backupDir;

    public function __construct(
        LoggerInterface $logger,
        ConfigService $config
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->backupDir = __DIR__ . '/../../../data/backups';
        
        // Ensure backup directory exists
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    /**
     * Create database backup
     *
     * @return array Backup info (filename, size, path)
     * @throws \Exception If backup creation fails
     */
    public function createBackup(): array
    {
        $this->logger->info('Creating database backup');

        try {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "backup-{$timestamp}.sql";
            $gzFilename = "{$filename}.gz";
            $filepath = $this->backupDir . '/' . $filename;
            $gzFilepath = $this->backupDir . '/' . $gzFilename;

            // Get database configuration
            $dbHost = $this->config->getString('database.host');
            $dbName = $this->config->getString('database.database');
            $dbUser = $this->config->getString('database.username');
            $dbPass = $this->config->getString('database.password');

            // Create mysqldump command
            $command = sprintf(
                'mysqldump -h %s -u %s -p%s %s > %s 2>&1',
                escapeshellarg($dbHost),
                escapeshellarg($dbUser),
                escapeshellarg($dbPass),
                escapeshellarg($dbName),
                escapeshellarg($filepath)
            );

            // Execute mysqldump
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                $this->logger->error('mysqldump failed', [
                    'return_code' => $returnCode,
                    'output' => implode("\n", $output)
                ]);
                throw new \Exception('Database backup failed');
            }

            // Compress backup
            $this->compressFile($filepath, $gzFilepath);

            // Delete uncompressed file
            if (file_exists($filepath)) {
                unlink($filepath);
            }

            $size = filesize($gzFilepath);

            $this->logger->info('[SUCCESS] Database backup created', [
                'filename' => $gzFilename,
                'size' => $size,
                'size_mb' => round($size / 1024 / 1024, 2)
            ]);

            return [
                'filename' => $gzFilename,
                'size' => $size,
                'size_mb' => round($size / 1024 / 1024, 2),
                'path' => $gzFilepath,
                'created_at' => time()
            ];

        } catch (\Exception $e) {
            $this->logger->error('Backup creation failed', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * List all backups
     *
     * @return array List of backups with metadata
     */
    public function listBackups(): array
    {
        $backups = [];
        $files = glob($this->backupDir . '/backup-*.sql.gz');

        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size' => filesize($file),
                'size_mb' => round(filesize($file) / 1024 / 1024, 2),
                'created_at' => filemtime($file),
                'created_at_human' => date('Y-m-d H:i:s', filemtime($file)),
                'path' => $file
            ];
        }

        // Sort by date descending
        usort($backups, function ($a, $b) {
            return $b['created_at'] - $a['created_at'];
        });

        return $backups;
    }

    /**
     * Get backup file path
     *
     * @param string $filename Backup filename
     * @return string|null File path or null if not found
     */
    public function getBackupPath(string $filename): ?string
    {
        // Validate filename format
        if (!preg_match('/^backup-\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql\.gz$/', $filename)) {
            return null;
        }

        $path = $this->backupDir . '/' . $filename;

        if (!file_exists($path)) {
            return null;
        }

        return $path;
    }

    /**
     * Delete backup file
     *
     * @param string $filename Backup filename
     * @return bool Success status
     */
    public function deleteBackup(string $filename): bool
    {
        $path = $this->getBackupPath($filename);

        if (!$path) {
            $this->logger->warning('Backup not found for deletion', [
                'filename' => $filename
            ]);
            return false;
        }

        try {
            unlink($path);

            $this->logger->info('Backup deleted', [
                'filename' => $filename
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Backup deletion failed', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Clean up old backups (older than retention days)
     *
     * @param int $retentionDays Number of days to keep backups
     * @return int Number of backups deleted
     */
    public function cleanupOldBackups(int $retentionDays = 30): int
    {
        $this->logger->info('Cleaning up old backups', [
            'retention_days' => $retentionDays
        ]);

        $cutoffTime = time() - ($retentionDays * 24 * 60 * 60);
        $deleted = 0;

        $backups = $this->listBackups();

        foreach ($backups as $backup) {
            if ($backup['created_at'] < $cutoffTime) {
                if ($this->deleteBackup($backup['filename'])) {
                    $deleted++;
                }
            }
        }

        $this->logger->info('Old backups cleaned up', [
            'deleted_count' => $deleted
        ]);

        return $deleted;
    }

    /**
     * Compress file with gzip
     *
     * @param string $source Source file path
     * @param string $destination Destination file path
     * @throws \Exception If compression fails
     */
    private function compressFile(string $source, string $destination): void
    {
        if (!file_exists($source)) {
            throw new \Exception("Source file not found: {$source}");
        }

        // Open source file
        $sourceHandle = fopen($source, 'rb');
        if (!$sourceHandle) {
            throw new \Exception("Cannot open source file: {$source}");
        }

        // Open destination gzip file
        $destHandle = gzopen($destination, 'wb9');
        if (!$destHandle) {
            fclose($sourceHandle);
            throw new \Exception("Cannot create gzip file: {$destination}");
        }

        // Compress data
        while (!feof($sourceHandle)) {
            gzwrite($destHandle, fread($sourceHandle, 1024 * 512));
        }

        // Close handles
        fclose($sourceHandle);
        gzclose($destHandle);

        $this->logger->debug('File compressed', [
            'source' => basename($source),
            'destination' => basename($destination),
            'original_size' => filesize($source),
            'compressed_size' => filesize($destination),
            'compression_ratio' => round((1 - filesize($destination) / filesize($source)) * 100, 1) . '%'
        ]);
    }
}

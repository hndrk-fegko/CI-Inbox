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
     * List all backups (local and external)
     *
     * @param bool $includeExternal Whether to include external storage backups
     * @return array List of backups with metadata
     */
    public function listBackups(bool $includeExternal = true): array
    {
        $backups = [];
        
        // List local backups
        $localBackups = $this->listLocalBackups();
        foreach ($localBackups as $backup) {
            $backup['location'] = 'local';
            $backups[] = $backup;
        }
        
        // List external backups if configured and requested
        if ($includeExternal) {
            $externalBackups = $this->listExternalBackups();
            foreach ($externalBackups as $backup) {
                $backup['location'] = 'external';
                $backups[] = $backup;
            }
        }

        // Sort by date descending
        usort($backups, function ($a, $b) {
            return $b['created_at'] - $a['created_at'];
        });

        return $backups;
    }
    
    /**
     * List local backups only
     *
     * @return array List of local backups
     */
    public function listLocalBackups(): array
    {
        $backups = [];
        
        // Pattern: backup-YYYY-MM-DD_HH-MM-SS.sql.gz
        $files = glob($this->backupDir . '/backup-*.sql.gz');

        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'size' => filesize($file),
                'size_mb' => round(filesize($file) / 1024 / 1024, 2),
                'created_at' => filemtime($file),
                'created_at_human' => date('Y-m-d H:i:s', filemtime($file)),
                'path' => $file,
                'location' => 'local',
                'downloadable' => true
            ];
        }

        return $backups;
    }
    
    /**
     * List external backups from FTP/WebDAV storage
     * 
     * Discovery is based on filename pattern: backup-YYYY-MM-DD_HH-MM-SS.sql.gz
     * This ensures consistency between local and external backups.
     *
     * @return array List of external backups
     */
    public function listExternalBackups(): array
    {
        try {
            $config = $this->getExternalStorageRaw();
            
            if (empty($config['type']) || $config['type'] === 'none') {
                return [];
            }
            
            if ($config['type'] === 'ftp') {
                return $this->listFtpBackups($config);
            } elseif ($config['type'] === 'webdav') {
                return $this->listWebDavBackups($config);
            }
            
            return [];
            
        } catch (\Exception $e) {
            $this->logger->warning('Failed to list external backups', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * List backups from FTP storage
     * 
     * @param array $config FTP configuration
     * @return array List of backups
     */
    private function listFtpBackups(array $config): array
    {
        $backups = [];
        $host = $config['host'];
        $port = (int)($config['port'] ?? 21);
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        $ssl = $config['ssl'] ?? false;
        $path = $config['path'] ?? '/backups';
        
        try {
            // Connect
            if ($ssl) {
                $connection = @ftp_ssl_connect($host, $port, 15);
            } else {
                $connection = @ftp_connect($host, $port, 15);
            }
            
            if (!$connection) {
                $this->logger->debug('Cannot connect to FTP for backup listing');
                return [];
            }
            
            // Login
            if (!empty($username)) {
                if (!@ftp_login($connection, $username, $password)) {
                    ftp_close($connection);
                    return [];
                }
            }
            
            ftp_pasv($connection, true);
            
            // Change to backup directory
            if (!empty($path) && $path !== '/') {
                if (!@ftp_chdir($connection, $path)) {
                    ftp_close($connection);
                    return [];
                }
            }
            
            // List files - discover by filename pattern
            $files = @ftp_nlist($connection, '.');
            
            if ($files === false) {
                ftp_close($connection);
                return [];
            }
            
            // Filter and process backup files
            // Pattern: backup-YYYY-MM-DD_HH-MM-SS.sql.gz
            $pattern = '/^backup-(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})\.sql\.gz$/';
            
            foreach ($files as $file) {
                $filename = basename($file);
                
                if (preg_match($pattern, $filename, $matches)) {
                    // Get file size
                    $size = @ftp_size($connection, $file);
                    if ($size === -1) {
                        $size = 0;
                    }
                    
                    // Parse date from filename for reliable timestamp
                    $dateParts = explode('_', $matches[1]);
                    $datePart = $dateParts[0]; // YYYY-MM-DD
                    $timePart = str_replace('-', ':', $dateParts[1]); // HH:MM:SS
                    $timestamp = strtotime("{$datePart} {$timePart}");
                    
                    $backups[] = [
                        'filename' => $filename,
                        'size' => $size,
                        'size_mb' => round($size / 1024 / 1024, 2),
                        'created_at' => $timestamp,
                        'created_at_human' => date('Y-m-d H:i:s', $timestamp),
                        'path' => rtrim($path, '/') . '/' . $filename,
                        'location' => 'external',
                        'storage_type' => 'ftp',
                        'downloadable' => true
                    ];
                }
            }
            
            ftp_close($connection);
            
            $this->logger->debug('Listed FTP backups', ['count' => count($backups)]);
            
            return $backups;
            
        } catch (\Exception $e) {
            $this->logger->warning('FTP backup listing failed', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * List backups from WebDAV storage
     * 
     * @param array $config WebDAV configuration
     * @return array List of backups
     */
    private function listWebDavBackups(array $config): array
    {
        $backups = [];
        $host = $config['host'];
        $port = (int)($config['port'] ?? 443);
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        $ssl = $config['ssl'] ?? true;
        $path = $config['path'] ?? '/backups';
        
        try {
            // Build URL
            $protocol = $ssl ? 'https' : 'http';
            $url = "{$protocol}://{$host}:{$port}" . $path;
            
            // PROPFIND request for directory listing
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'PROPFIND',
                CURLOPT_HTTPHEADER => [
                    'Depth: 1',
                    'Content-Type: application/xml'
                ],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => $ssl,
                CURLOPT_SSL_VERIFYHOST => $ssl ? 2 : 0
            ]);
            
            if (!empty($username)) {
                curl_setopt($ch, CURLOPT_USERPWD, "{$username}:{$password}");
            }
            
            // Request body for PROPFIND
            $propfindBody = '<?xml version="1.0" encoding="utf-8"?>
                <propfind xmlns="DAV:">
                    <prop>
                        <getcontentlength/>
                        <getlastmodified/>
                        <displayname/>
                    </prop>
                </propfind>';
            curl_setopt($ch, CURLOPT_POSTFIELDS, $propfindBody);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 207 && $httpCode !== 200) {
                return [];
            }
            
            // Parse XML response to extract file info
            // Pattern: backup-YYYY-MM-DD_HH-MM-SS.sql.gz
            $pattern = '/backup-(\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2})\.sql\.gz/';
            
            // Simple regex-based parsing of WebDAV response
            preg_match_all('/<d:href>([^<]*backup-[^<]+\.sql\.gz)<\/d:href>/i', $response, $hrefMatches);
            preg_match_all('/<D:href>([^<]*backup-[^<]+\.sql\.gz)<\/D:href>/i', $response, $hrefMatches2);
            
            $hrefs = array_merge($hrefMatches[1] ?? [], $hrefMatches2[1] ?? []);
            
            foreach ($hrefs as $href) {
                $filename = basename($href);
                
                if (preg_match($pattern, $filename, $matches)) {
                    // Parse date from filename
                    $dateParts = explode('_', $matches[1]);
                    $datePart = $dateParts[0];
                    $timePart = str_replace('-', ':', $dateParts[1]);
                    $timestamp = strtotime("{$datePart} {$timePart}");
                    
                    $backups[] = [
                        'filename' => $filename,
                        'size' => 0, // Would need separate request per file
                        'size_mb' => 0,
                        'created_at' => $timestamp,
                        'created_at_human' => date('Y-m-d H:i:s', $timestamp),
                        'path' => rtrim($path, '/') . '/' . $filename,
                        'location' => 'external',
                        'storage_type' => 'webdav',
                        'downloadable' => true
                    ];
                }
            }
            
            $this->logger->debug('Listed WebDAV backups', ['count' => count($backups)]);
            
            return $backups;
            
        } catch (\Exception $e) {
            $this->logger->warning('WebDAV backup listing failed', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Upload backup to external storage
     * 
     * @param string $localPath Local file path
     * @param string $filename Backup filename
     * @return array Upload result
     */
    public function uploadToExternalStorage(string $localPath, string $filename): array
    {
        try {
            $config = $this->getExternalStorageRaw();
            
            if (empty($config['type']) || $config['type'] === 'none') {
                return [
                    'success' => false,
                    'error' => 'No external storage configured'
                ];
            }
            
            if ($config['type'] === 'ftp') {
                return $this->uploadToFtp($localPath, $filename, $config);
            } elseif ($config['type'] === 'webdav') {
                return $this->uploadToWebDav($localPath, $filename, $config);
            }
            
            return [
                'success' => false,
                'error' => 'Unknown storage type'
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('External storage upload failed', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Upload file to FTP
     */
    private function uploadToFtp(string $localPath, string $filename, array $config): array
    {
        $host = $config['host'];
        $port = (int)($config['port'] ?? 21);
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        $ssl = $config['ssl'] ?? false;
        $path = $config['path'] ?? '/backups';
        
        try {
            if ($ssl) {
                $connection = @ftp_ssl_connect($host, $port, 30);
            } else {
                $connection = @ftp_connect($host, $port, 30);
            }
            
            if (!$connection) {
                return ['success' => false, 'error' => 'Cannot connect to FTP server'];
            }
            
            if (!empty($username)) {
                if (!@ftp_login($connection, $username, $password)) {
                    ftp_close($connection);
                    return ['success' => false, 'error' => 'FTP login failed'];
                }
            }
            
            ftp_pasv($connection, true);
            
            // Ensure directory exists
            if (!empty($path) && $path !== '/') {
                @ftp_chdir($connection, $path) || @ftp_mkdir($connection, $path);
                @ftp_chdir($connection, $path);
            }
            
            // Upload file
            $remotePath = $filename;
            $result = @ftp_put($connection, $remotePath, $localPath, FTP_BINARY);
            
            ftp_close($connection);
            
            if ($result) {
                $this->logger->info('[SUCCESS] Backup uploaded to FTP', [
                    'filename' => $filename,
                    'path' => $path
                ]);
                return ['success' => true, 'message' => 'Uploaded to FTP'];
            }
            
            return ['success' => false, 'error' => 'FTP upload failed'];
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Upload file to WebDAV
     */
    private function uploadToWebDav(string $localPath, string $filename, array $config): array
    {
        $host = $config['host'];
        $port = (int)($config['port'] ?? 443);
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        $ssl = $config['ssl'] ?? true;
        $path = $config['path'] ?? '/backups';
        
        try {
            $protocol = $ssl ? 'https' : 'http';
            $url = "{$protocol}://{$host}:{$port}" . rtrim($path, '/') . '/' . $filename;
            
            $fileHandle = fopen($localPath, 'rb');
            if (!$fileHandle) {
                return ['success' => false, 'error' => 'Cannot open local file'];
            }
            
            $fileSize = filesize($localPath);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_PUT => true,
                CURLOPT_INFILE => $fileHandle,
                CURLOPT_INFILESIZE => $fileSize,
                CURLOPT_TIMEOUT => 300,
                CURLOPT_SSL_VERIFYPEER => $ssl,
                CURLOPT_SSL_VERIFYHOST => $ssl ? 2 : 0
            ]);
            
            if (!empty($username)) {
                curl_setopt($ch, CURLOPT_USERPWD, "{$username}:{$password}");
            }
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            fclose($fileHandle);
            
            if ($httpCode === 201 || $httpCode === 200 || $httpCode === 204) {
                $this->logger->info('[SUCCESS] Backup uploaded to WebDAV', [
                    'filename' => $filename,
                    'path' => $path
                ]);
                return ['success' => true, 'message' => 'Uploaded to WebDAV'];
            }
            
            return ['success' => false, 'error' => "WebDAV upload failed with HTTP {$httpCode}"];
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Delete backup from external storage
     * 
     * @param string $filename Backup filename
     * @return array Deletion result
     */
    public function deleteFromExternalStorage(string $filename): array
    {
        try {
            $config = $this->getExternalStorageRaw();
            
            if (empty($config['type']) || $config['type'] === 'none') {
                return ['success' => false, 'error' => 'No external storage configured'];
            }
            
            if ($config['type'] === 'ftp') {
                return $this->deleteFromFtp($filename, $config);
            } elseif ($config['type'] === 'webdav') {
                return $this->deleteFromWebDav($filename, $config);
            }
            
            return ['success' => false, 'error' => 'Unknown storage type'];
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Delete file from FTP
     */
    private function deleteFromFtp(string $filename, array $config): array
    {
        $host = $config['host'];
        $port = (int)($config['port'] ?? 21);
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        $ssl = $config['ssl'] ?? false;
        $path = $config['path'] ?? '/backups';
        
        try {
            if ($ssl) {
                $connection = @ftp_ssl_connect($host, $port, 30);
            } else {
                $connection = @ftp_connect($host, $port, 30);
            }
            
            if (!$connection) {
                return ['success' => false, 'error' => 'Cannot connect to FTP server'];
            }
            
            if (!empty($username)) {
                if (!@ftp_login($connection, $username, $password)) {
                    ftp_close($connection);
                    return ['success' => false, 'error' => 'FTP login failed'];
                }
            }
            
            ftp_pasv($connection, true);
            
            $remotePath = rtrim($path, '/') . '/' . $filename;
            $result = @ftp_delete($connection, $remotePath);
            
            ftp_close($connection);
            
            if ($result) {
                $this->logger->info('Backup deleted from FTP', ['filename' => $filename]);
                return ['success' => true];
            }
            
            return ['success' => false, 'error' => 'FTP delete failed'];
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Delete file from WebDAV
     */
    private function deleteFromWebDav(string $filename, array $config): array
    {
        $host = $config['host'];
        $port = (int)($config['port'] ?? 443);
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        $ssl = $config['ssl'] ?? true;
        $path = $config['path'] ?? '/backups';
        
        try {
            $protocol = $ssl ? 'https' : 'http';
            $url = "{$protocol}://{$host}:{$port}" . rtrim($path, '/') . '/' . $filename;
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'DELETE',
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => $ssl,
                CURLOPT_SSL_VERIFYHOST => $ssl ? 2 : 0
            ]);
            
            if (!empty($username)) {
                curl_setopt($ch, CURLOPT_USERPWD, "{$username}:{$password}");
            }
            
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 204 || $httpCode === 200) {
                $this->logger->info('Backup deleted from WebDAV', ['filename' => $filename]);
                return ['success' => true];
            }
            
            return ['success' => false, 'error' => "WebDAV delete failed with HTTP {$httpCode}"];
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
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
    
    /**
     * Get backup schedule configuration
     * 
     * @return array Schedule configuration
     */
    public function getSchedule(): array
    {
        try {
            // Try to load from settings database
            $settingsFile = __DIR__ . '/../../../data/backup-schedule.json';
            
            if (file_exists($settingsFile)) {
                $config = json_decode(file_get_contents($settingsFile), true);
                if ($config) {
                    return $config;
                }
            }
            
            // Default configuration
            return [
                'enabled' => false,
                'frequency' => 'daily',
                'time' => '03:00',
                'retention_days' => 30,
                'location' => 'local',
                'keep_monthly' => false,
                'last_backup' => null
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get backup schedule', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'enabled' => false,
                'frequency' => 'daily',
                'time' => '03:00',
                'retention_days' => 30,
                'location' => 'local',
                'keep_monthly' => false
            ];
        }
    }
    
    /**
     * Update backup schedule configuration
     * 
     * @param array $config Schedule configuration
     * @return array Updated configuration
     */
    public function updateSchedule(array $config): array
    {
        try {
            $current = $this->getSchedule();
            
            // Merge with current config
            $updated = array_merge($current, [
                'enabled' => $config['enabled'] ?? false,
                'frequency' => $config['frequency'] ?? 'daily',
                'time' => $config['time'] ?? '03:00',
                'retention_days' => (int)($config['retention_days'] ?? 30),
                'location' => $config['location'] ?? 'local',
                'keep_monthly' => $config['keep_monthly'] ?? false
            ]);
            
            // Save to file
            $settingsFile = __DIR__ . '/../../../data/backup-schedule.json';
            $dataDir = dirname($settingsFile);
            
            if (!is_dir($dataDir)) {
                mkdir($dataDir, 0755, true);
            }
            
            file_put_contents($settingsFile, json_encode($updated, JSON_PRETTY_PRINT));
            
            $this->logger->info('[SUCCESS] Backup schedule updated', $updated);
            
            return $updated;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to update backup schedule', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Get storage usage statistics
     * 
     * @return array Storage statistics
     */
    public function getStorageUsage(): array
    {
        try {
            $backups = $this->listBackups();
            
            $localSize = 0;
            $monthlyCount = 0;
            $oldestBackup = null;
            $newestBackup = null;
            
            foreach ($backups as $backup) {
                $localSize += $backup['size'];
                
                // Check if it's a monthly backup (first of month)
                $backupDate = date('Y-m-d', $backup['created_at']);
                $firstOfMonth = date('Y-m-01', $backup['created_at']);
                if ($backupDate === $firstOfMonth) {
                    $monthlyCount++;
                }
                
                // Track oldest and newest
                if ($oldestBackup === null || $backup['created_at'] < $oldestBackup['created_at']) {
                    $oldestBackup = $backup;
                }
                if ($newestBackup === null || $backup['created_at'] > $newestBackup['created_at']) {
                    $newestBackup = $backup;
                }
            }
            
            // Check external storage configuration
            $externalConfig = $this->getExternalStorage();
            $externalConfigured = !empty($externalConfig['type']) && $externalConfig['type'] !== 'none';
            
            return [
                'local' => [
                    'count' => count($backups),
                    'size_bytes' => $localSize,
                    'size_mb' => round($localSize / 1024 / 1024, 2)
                ],
                'external' => [
                    'configured' => $externalConfigured,
                    'type' => $externalConfig['type'] ?? 'none',
                    // External count/size require querying remote storage - placeholder for future implementation
                    'count' => 0,
                    'size_mb' => 0
                ],
                'monthly_count' => $monthlyCount,
                'oldest_backup' => $oldestBackup ? $oldestBackup['created_at_human'] : null,
                'newest_backup' => $newestBackup ? $newestBackup['created_at_human'] : null
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get storage usage', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'local' => ['count' => 0, 'size_bytes' => 0, 'size_mb' => 0],
                'external' => ['configured' => false, 'count' => 0, 'size_mb' => 0],
                'monthly_count' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get external storage configuration
     * 
     * @return array External storage config
     */
    public function getExternalStorage(): array
    {
        try {
            $settingsFile = __DIR__ . '/../../../data/backup-external-storage.json';
            
            if (file_exists($settingsFile)) {
                $config = json_decode(file_get_contents($settingsFile), true);
                if ($config) {
                    // Mask sensitive data
                    if (isset($config['password'])) {
                        $config['password'] = '********';
                    }
                    return $config;
                }
            }
            
            // Default configuration (not configured)
            return [
                'type' => 'none',
                'host' => '',
                'port' => '',
                'username' => '',
                'password' => '',
                'path' => '/backups',
                'ssl' => true
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get external storage config', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'type' => 'none',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Update external storage configuration
     * 
     * @param array $config External storage configuration
     * @return array Updated configuration
     */
    public function updateExternalStorage(array $config): array
    {
        try {
            // Validate type
            $validTypes = ['none', 'ftp', 'webdav'];
            if (!isset($config['type']) || !in_array($config['type'], $validTypes)) {
                throw new \InvalidArgumentException('Invalid storage type. Must be: none, ftp, or webdav');
            }
            
            // Get current config to preserve password if not provided
            $current = $this->getExternalStorageRaw();
            
            $updated = [
                'type' => $config['type'],
                'host' => $config['host'] ?? '',
                'port' => $config['port'] ?? ($config['type'] === 'ftp' ? 21 : 443),
                'username' => $config['username'] ?? '',
                'password' => ($config['password'] ?? '') !== '********' 
                    ? ($config['password'] ?? '') 
                    : ($current['password'] ?? ''),
                'path' => $config['path'] ?? '/backups',
                'ssl' => $config['ssl'] ?? true
            ];
            
            // Save to file
            $settingsFile = __DIR__ . '/../../../data/backup-external-storage.json';
            $dataDir = dirname($settingsFile);
            
            if (!is_dir($dataDir)) {
                mkdir($dataDir, 0755, true);
            }
            
            file_put_contents($settingsFile, json_encode($updated, JSON_PRETTY_PRINT));
            
            $this->logger->info('[SUCCESS] External storage configuration updated', [
                'type' => $updated['type'],
                'host' => $updated['host']
            ]);
            
            // Mask password before returning
            $updated['password'] = '********';
            
            return $updated;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to update external storage config', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Get raw external storage configuration (with password)
     * 
     * @return array Raw config with password
     */
    private function getExternalStorageRaw(): array
    {
        $settingsFile = __DIR__ . '/../../../data/backup-external-storage.json';
        
        if (file_exists($settingsFile)) {
            $config = json_decode(file_get_contents($settingsFile), true);
            if ($config) {
                return $config;
            }
        }
        
        return [
            'type' => 'none',
            'host' => '',
            'port' => '',
            'username' => '',
            'password' => '',
            'path' => '/backups',
            'ssl' => true
        ];
    }
    
    /**
     * Test external storage connection
     * 
     * @param array $config Optional config to test (uses saved config if not provided)
     * @return array Test result
     */
    public function testExternalStorage(?array $config = null): array
    {
        try {
            // Use provided config or get saved config
            if ($config === null) {
                $config = $this->getExternalStorageRaw();
            } else {
                // If password is masked, get the real password from saved config
                if (isset($config['password']) && $config['password'] === '********') {
                    $saved = $this->getExternalStorageRaw();
                    $config['password'] = $saved['password'] ?? '';
                }
            }
            
            if (empty($config['type']) || $config['type'] === 'none') {
                return [
                    'success' => false,
                    'error' => 'No external storage configured'
                ];
            }
            
            if (empty($config['host'])) {
                return [
                    'success' => false,
                    'error' => 'Host is required'
                ];
            }
            
            if ($config['type'] === 'ftp') {
                return $this->testFtpConnection($config);
            } elseif ($config['type'] === 'webdav') {
                return $this->testWebDavConnection($config);
            }
            
            return [
                'success' => false,
                'error' => 'Unknown storage type: ' . $config['type']
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('External storage test failed', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Test FTP connection
     * 
     * @param array $config FTP configuration
     * @return array Test result
     */
    private function testFtpConnection(array $config): array
    {
        $host = $config['host'];
        $port = (int)($config['port'] ?? 21);
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        $ssl = $config['ssl'] ?? false;
        $path = $config['path'] ?? '/backups';
        
        try {
            // Connect to FTP server
            if ($ssl) {
                $connection = @ftp_ssl_connect($host, $port, 30);
            } else {
                $connection = @ftp_connect($host, $port, 30);
            }
            
            if (!$connection) {
                return [
                    'success' => false,
                    'error' => "Cannot connect to FTP server: {$host}:{$port}"
                ];
            }
            
            // Login
            if (!empty($username)) {
                $loginResult = @ftp_login($connection, $username, $password);
                if (!$loginResult) {
                    ftp_close($connection);
                    return [
                        'success' => false,
                        'error' => 'FTP login failed. Check username and password.'
                    ];
                }
            }
            
            // Enable passive mode
            ftp_pasv($connection, true);
            
            // Try to change to backup directory
            if (!empty($path) && $path !== '/') {
                $dirExists = @ftp_chdir($connection, $path);
                if (!$dirExists) {
                    // Try to create the directory
                    $created = @ftp_mkdir($connection, $path);
                    if (!$created) {
                        ftp_close($connection);
                        // Connection works but directory needs manual creation
                        return [
                            'success' => true,
                            'connected' => true,
                            'directory_ready' => false,
                            'message' => "FTP connection successful. Directory '{$path}' does not exist and could not be created automatically. Please create it manually on the server."
                        ];
                    }
                }
            }
            
            // Get server info
            $sysType = @ftp_systype($connection);
            
            ftp_close($connection);
            
            $this->logger->info('[SUCCESS] FTP connection test passed', [
                'host' => $host,
                'path' => $path
            ]);
            
            return [
                'success' => true,
                'connected' => true,
                'directory_ready' => true,
                'message' => 'FTP connection successful',
                'server_type' => $sysType ?: 'Unknown'
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'FTP connection error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Test WebDAV connection
     * 
     * @param array $config WebDAV configuration
     * @return array Test result
     */
    private function testWebDavConnection(array $config): array
    {
        $host = $config['host'];
        $port = (int)($config['port'] ?? 443);
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        $ssl = $config['ssl'] ?? true;
        $path = $config['path'] ?? '/backups';
        
        try {
            // Build URL
            $protocol = $ssl ? 'https' : 'http';
            $url = "{$protocol}://{$host}:{$port}" . $path;
            
            // Try PROPFIND request (WebDAV directory listing)
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'PROPFIND',
                CURLOPT_HTTPHEADER => [
                    'Depth: 0',
                    'Content-Type: application/xml'
                ],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => $ssl,
                CURLOPT_SSL_VERIFYHOST => $ssl ? 2 : 0
            ]);
            
            // Add authentication if provided
            if (!empty($username)) {
                curl_setopt($ch, CURLOPT_USERPWD, "{$username}:{$password}");
            }
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if (!empty($error)) {
                return [
                    'success' => false,
                    'error' => "WebDAV connection error: {$error}"
                ];
            }
            
            // Check HTTP response codes
            if ($httpCode === 207 || $httpCode === 200) {
                $this->logger->info('[SUCCESS] WebDAV connection test passed', [
                    'host' => $host,
                    'path' => $path
                ]);
                
                return [
                    'success' => true,
                    'connected' => true,
                    'directory_ready' => true,
                    'message' => 'WebDAV connection successful',
                    'http_code' => $httpCode
                ];
            } elseif ($httpCode === 401) {
                return [
                    'success' => false,
                    'error' => 'WebDAV authentication failed. Check username and password.'
                ];
            } elseif ($httpCode === 404) {
                // Connection works but directory doesn't exist yet
                return [
                    'success' => true,
                    'connected' => true,
                    'directory_ready' => false,
                    'message' => "WebDAV connection successful. Directory '{$path}' does not exist but will be created during the first backup."
                ];
            } else {
                return [
                    'success' => false,
                    'error' => "WebDAV request failed with HTTP {$httpCode}"
                ];
            }
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'WebDAV connection error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Delete external storage configuration
     * 
     * @return bool Success status
     */
    public function deleteExternalStorage(): bool
    {
        try {
            $settingsFile = __DIR__ . '/../../../data/backup-external-storage.json';
            
            if (file_exists($settingsFile)) {
                unlink($settingsFile);
            }
            
            $this->logger->info('[SUCCESS] External storage configuration removed');
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete external storage config', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}

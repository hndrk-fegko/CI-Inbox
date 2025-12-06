<?php
/**
 * Database Admin Service
 * 
 * Provides database management functionality for admin interface.
 * Handles table information, optimization, and maintenance operations.
 */

declare(strict_types=1);

namespace CiInbox\App\Services;

use CiInbox\Modules\Logger\LoggerInterface;
use Illuminate\Database\Capsule\Manager as DB;

class DatabaseAdminService
{
    public function __construct(
        private LoggerInterface $logger
    ) {}
    
    /**
     * Get database status information
     * 
     * @return array{connected: bool, server_version: string, database_name: string, size_mb: float, table_count: int}
     */
    public function getStatus(): array
    {
        try {
            $connection = DB::connection();
            $pdo = $connection->getPdo();
            
            // Get server version
            $version = $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
            
            // Get database name
            $dbName = $connection->getDatabaseName();
            
            // Get database size
            $sizeResult = DB::select("
                SELECT 
                    SUM(data_length + index_length) / 1024 / 1024 AS size_mb
                FROM information_schema.tables 
                WHERE table_schema = ?
            ", [$dbName]);
            
            $sizeMb = round($sizeResult[0]->size_mb ?? 0, 2);
            
            // Get table count
            $tableCount = DB::select("
                SELECT COUNT(*) as count 
                FROM information_schema.tables 
                WHERE table_schema = ?
            ", [$dbName]);
            
            $this->logger->debug('[DatabaseAdmin] Status retrieved', [
                'database' => $dbName,
                'size_mb' => $sizeMb
            ]);
            
            return [
                'connected' => true,
                'server_version' => $version,
                'database_name' => $dbName,
                'size_mb' => $sizeMb,
                'table_count' => $tableCount[0]->count ?? 0
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('[DatabaseAdmin] Failed to get status', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'connected' => false,
                'server_version' => 'Unknown',
                'database_name' => 'Unknown',
                'size_mb' => 0,
                'table_count' => 0
            ];
        }
    }
    
    /**
     * Get list of tables with sizes and row counts
     * 
     * @return array
     */
    public function getTables(): array
    {
        try {
            $connection = DB::connection();
            $dbName = $connection->getDatabaseName();
            
            $tables = DB::select("
                SELECT 
                    table_name,
                    table_rows,
                    ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb,
                    engine,
                    table_collation,
                    create_time,
                    update_time
                FROM information_schema.tables 
                WHERE table_schema = ?
                ORDER BY (data_length + index_length) DESC
            ", [$dbName]);
            
            $result = array_map(function ($table) {
                return [
                    'name' => $table->table_name,
                    'rows' => $table->table_rows ?? 0,
                    'size_mb' => $table->size_mb ?? 0,
                    'engine' => $table->engine ?? 'InnoDB',
                    'collation' => $table->table_collation ?? 'utf8mb4_unicode_ci',
                    'created_at' => $table->create_time,
                    'updated_at' => $table->update_time
                ];
            }, $tables);
            
            $this->logger->debug('[DatabaseAdmin] Tables retrieved', [
                'count' => count($result)
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            $this->logger->error('[DatabaseAdmin] Failed to get tables', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Optimize all tables
     * 
     * @return array{success: bool, tables_optimized: int, message: string}
     */
    public function optimizeTables(): array
    {
        try {
            $connection = DB::connection();
            $dbName = $connection->getDatabaseName();
            
            $tables = DB::select("
                SELECT table_name 
                FROM information_schema.tables 
                WHERE table_schema = ?
            ", [$dbName]);
            
            $optimized = 0;
            $errors = [];
            
            foreach ($tables as $table) {
                try {
                    // Validate table name contains only safe characters
                    $tableName = $table->table_name;
                    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
                        $errors[] = $tableName;
                        continue;
                    }
                    
                    DB::statement("OPTIMIZE TABLE `{$tableName}`");
                    $optimized++;
                } catch (\Exception $e) {
                    $errors[] = $table->table_name;
                }
            }
            
            $this->logger->info('[DatabaseAdmin] Tables optimized', [
                'optimized' => $optimized,
                'errors' => count($errors)
            ]);
            
            $errorCount = count($errors);
            return [
                'success' => true,
                'tables_optimized' => $optimized,
                'message' => "Optimized {$optimized} tables" . ($errorCount > 0 ? " ({$errorCount} errors)" : "")
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('[DatabaseAdmin] Optimization failed', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'tables_optimized' => 0,
                'message' => 'Optimization failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Analyze all tables
     * 
     * @return array{success: bool, tables_analyzed: int, message: string}
     */
    public function analyzeTables(): array
    {
        try {
            $connection = DB::connection();
            $dbName = $connection->getDatabaseName();
            
            $tables = DB::select("
                SELECT table_name 
                FROM information_schema.tables 
                WHERE table_schema = ?
            ", [$dbName]);
            
            $analyzed = 0;
            
            foreach ($tables as $table) {
                try {
                    // Validate table name contains only safe characters
                    $tableName = $table->table_name;
                    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
                        continue;
                    }
                    
                    DB::statement("ANALYZE TABLE `{$tableName}`");
                    $analyzed++;
                } catch (\Exception $e) {
                    // Continue with next table
                }
            }
            
            $this->logger->info('[DatabaseAdmin] Tables analyzed', [
                'analyzed' => $analyzed
            ]);
            
            return [
                'success' => true,
                'tables_analyzed' => $analyzed,
                'message' => "Analyzed {$analyzed} tables"
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('[DatabaseAdmin] Analysis failed', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'tables_analyzed' => 0,
                'message' => 'Analysis failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Check for orphaned data
     * 
     * @return array
     */
    public function checkOrphanedData(): array
    {
        try {
            $orphaned = [
                'threads_without_emails' => 0,
                'emails_without_threads' => 0,
                'notes_without_threads' => 0,
                'labels_unused' => 0
            ];
            
            // Threads without emails
            $result = DB::select("
                SELECT COUNT(*) as count 
                FROM threads t 
                LEFT JOIN emails e ON t.id = e.thread_id 
                WHERE e.id IS NULL
            ");
            $orphaned['threads_without_emails'] = $result[0]->count ?? 0;
            
            // Emails without threads
            $result = DB::select("
                SELECT COUNT(*) as count 
                FROM emails e 
                LEFT JOIN threads t ON e.thread_id = t.id 
                WHERE t.id IS NULL AND e.thread_id IS NOT NULL
            ");
            $orphaned['emails_without_threads'] = $result[0]->count ?? 0;
            
            // Notes without threads
            $result = DB::select("
                SELECT COUNT(*) as count 
                FROM internal_notes n 
                LEFT JOIN threads t ON n.thread_id = t.id 
                WHERE t.id IS NULL
            ");
            $orphaned['notes_without_threads'] = $result[0]->count ?? 0;
            
            // Unused labels (labels not assigned to any thread)
            $result = DB::select("
                SELECT COUNT(*) as count 
                FROM labels l 
                LEFT JOIN thread_labels tl ON l.id = tl.label_id 
                WHERE tl.id IS NULL AND l.is_system = 0
            ");
            $orphaned['labels_unused'] = $result[0]->count ?? 0;
            
            $total = array_sum($orphaned);
            
            $this->logger->debug('[DatabaseAdmin] Orphaned data check complete', $orphaned);
            
            return [
                'total_orphaned' => $total,
                'details' => $orphaned
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('[DatabaseAdmin] Orphaned data check failed', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'total_orphaned' => 0,
                'details' => [],
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get migration status
     * 
     * @return array
     */
    public function getMigrationStatus(): array
    {
        try {
            // Check if migrations table exists
            $migrationsExist = DB::select("
                SELECT COUNT(*) as count 
                FROM information_schema.tables 
                WHERE table_schema = DATABASE() 
                AND table_name = 'migrations'
            ");
            
            if (($migrationsExist[0]->count ?? 0) === 0) {
                return [
                    'applied' => [],
                    'total_applied' => 0,
                    'pending' => 0,
                    'message' => 'Migrations table not found'
                ];
            }
            
            // Get applied migrations
            $migrations = DB::select("SELECT * FROM migrations ORDER BY id DESC");
            
            $applied = array_map(function ($m) {
                return [
                    'id' => $m->id,
                    'migration' => $m->migration,
                    'batch' => $m->batch ?? 1
                ];
            }, $migrations);
            
            return [
                'applied' => $applied,
                'total_applied' => count($applied),
                'pending' => 0, // Would need to scan migration files
                'message' => count($applied) . ' migrations applied'
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('[DatabaseAdmin] Migration status check failed', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'applied' => [],
                'total_applied' => 0,
                'pending' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
}

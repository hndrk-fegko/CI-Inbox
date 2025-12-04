<?php
/**
 * CronExecution Model
 * 
 * Represents a single execution of the webcron polling service
 */

declare(strict_types=1);

namespace CiInbox\App\Models;

class CronExecution extends BaseModel
{
    protected $table = 'cron_executions';
    
    public $timestamps = false;
    
    protected $fillable = [
        'execution_timestamp',
        'accounts_polled',
        'new_emails_found',
        'duration_ms',
        'status',
        'error_message'
    ];
    
    protected $casts = [
        'execution_timestamp' => 'datetime',
        'accounts_polled' => 'integer',
        'new_emails_found' => 'integer',
        'duration_ms' => 'integer'
    ];
    
    /**
     * Check if execution was successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }
    
    /**
     * Get formatted duration
     */
    public function getFormattedDuration(): string
    {
        $ms = $this->duration_ms;
        
        if ($ms < 1000) {
            return "{$ms}ms";
        }
        
        $seconds = round($ms / 1000, 2);
        return "{$seconds}s";
    }
    
    /**
     * Get relative time (e.g., "2 minutes ago")
     */
    public function getRelativeTime(): string
    {
        return $this->execution_timestamp->diffForHumans();
    }
}

<?php

declare(strict_types=1);

namespace CiInbox\App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Webhook Model
 * 
 * Represents a webhook subscription for event notifications.
 * 
 * @property int $id
 * @property string $url
 * @property array $events
 * @property string $secret
 * @property bool $is_active
 * @property \Carbon\Carbon|null $last_triggered_at
 * @property int $failed_attempts
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Webhook extends BaseModel
{
    protected $table = 'webhooks';
    
    protected $fillable = [
        'url',
        'events',
        'secret',
        'is_active',
        'last_triggered_at',
        'failed_attempts'
    ];
    
    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'failed_attempts' => 'integer',
        'last_triggered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * Get webhook deliveries
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }
    
    /**
     * Check if webhook subscribes to event
     */
    public function subscribesTo(string $event): bool
    {
        return in_array($event, $this->events);
    }
    
    /**
     * Check if webhook is enabled
     */
    public function isEnabled(): bool
    {
        return $this->is_active && $this->failed_attempts < 10;
    }
    
    /**
     * Increment failed attempts
     */
    public function incrementFailedAttempts(): void
    {
        $this->failed_attempts++;
        
        // Disable after 10 failures
        if ($this->failed_attempts >= 10) {
            $this->is_active = false;
        }
        
        $this->save();
    }
    
    /**
     * Reset failed attempts
     */
    public function resetFailedAttempts(): void
    {
        $this->failed_attempts = 0;
        $this->save();
    }
    
    /**
     * Update last triggered timestamp
     */
    public function markTriggered(): void
    {
        $this->last_triggered_at = \Carbon\Carbon::now();
        $this->save();
    }
}

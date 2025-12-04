<?php

declare(strict_types=1);

namespace CiInbox\App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WebhookDelivery Model
 * 
 * Tracks webhook delivery attempts and results.
 * 
 * @property int $id
 * @property int $webhook_id
 * @property string $event_type
 * @property array $payload
 * @property int|null $response_status
 * @property string|null $response_body
 * @property int $attempts
 * @property \Carbon\Carbon|null $delivered_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class WebhookDelivery extends BaseModel
{
    protected $table = 'webhook_deliveries';
    
    protected $fillable = [
        'webhook_id',
        'event_type',
        'payload',
        'response_status',
        'response_body',
        'attempts',
        'delivered_at'
    ];
    
    protected $casts = [
        'webhook_id' => 'integer',
        'payload' => 'array',
        'response_status' => 'integer',
        'attempts' => 'integer',
        'delivered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * Get parent webhook
     */
    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }
    
    /**
     * Check if delivery was successful
     */
    public function isSuccessful(): bool
    {
        return $this->response_status >= 200 && $this->response_status < 300;
    }
    
    /**
     * Check if delivery failed
     */
    public function isFailed(): bool
    {
        return $this->response_status === null || $this->response_status >= 400;
    }
    
    /**
     * Mark as delivered
     */
    public function markDelivered(int $status, ?string $body = null): void
    {
        $this->response_status = $status;
        $this->response_body = $body ? substr($body, 0, 1000) : null; // Limit body size
        $this->delivered_at = \Carbon\Carbon::now();
        $this->save();
    }
}

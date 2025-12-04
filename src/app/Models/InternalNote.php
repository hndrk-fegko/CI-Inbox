<?php

declare(strict_types=1);

namespace CiInbox\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Internal Note Model
 * 
 * Represents internal notes attached to threads
 * 
 * @property int $id
 * @property int $thread_id
 * @property int|null $user_id
 * @property int|null $updated_by_user_id
 * @property string $content
 * @property string $type ('user' or 'system')
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class InternalNote extends Model
{
    protected $table = 'internal_notes';

    protected $fillable = [
        'thread_id',
        'user_id',
        'updated_by_user_id',
        'content',
        'type',
    ];

    protected $casts = [
        'thread_id' => 'integer',
        'user_id' => 'integer',
        'updated_by_user_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'created_by_name',
        'updated_by_name',
    ];

    /**
     * Get the thread this note belongs to
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class);
    }

    /**
     * Get the user who created this note
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who last updated this note
     */
    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    /**
     * Check if this is a system note
     */
    public function isSystem(): bool
    {
        return $this->type === 'system';
    }

    /**
     * Check if this is a user note
     */
    public function isUser(): bool
    {
        return $this->type === 'user';
    }

    /**
     * Get the created by name accessor
     */
    public function getCreatedByNameAttribute(): ?string
    {
        if ($this->user_id && $this->user) {
            return $this->user->name ?? $this->user->username ?? $this->user->email;
        }
        return null;
    }

    /**
     * Get the updated by name accessor
     */
    public function getUpdatedByNameAttribute(): ?string
    {
        if ($this->updated_by_user_id && $this->updatedByUser) {
            return $this->updatedByUser->name ?? $this->updatedByUser->username ?? $this->updatedByUser->email;
        }
        return null;
    }
}

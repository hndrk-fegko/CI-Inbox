<?php

declare(strict_types=1);

namespace CiInbox\App\Models;

/**
 * Signature Model
 * 
 * Represents user email signatures.
 */
class Signature extends BaseModel
{
    protected $table = 'signatures';
    
    protected $fillable = [
        'user_id',
        'type',
        'name',
        'content',
        'is_default'
    ];
    
    protected $casts = [
        'user_id' => 'integer',
        'is_default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    /**
     * Get the user that owns the signature
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Scope: Only global signatures
     */
    public function scopeGlobal($query)
    {
        return $query->where('type', 'global')->whereNull('user_id');
    }
    
    /**
     * Scope: Only personal signatures for a user
     */
    public function scopePersonal($query, int $userId)
    {
        return $query->where('type', 'personal')->where('user_id', $userId);
    }
    
    /**
     * Scope: Get default signature
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}

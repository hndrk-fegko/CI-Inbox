<?php

namespace CiInbox\App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * SystemSetting Model
 * 
 * Key-value store for system-wide configuration.
 * Supports encryption for sensitive values.
 */
class SystemSetting extends BaseModel
{
    protected $table = 'system_settings';
    
    protected $fillable = [
        'setting_key',
        'setting_value',
        'setting_type',
        'is_encrypted',
        'description',
    ];
    
    protected $casts = [
        'is_encrypted' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * Get setting value cast to appropriate type
     */
    public function getTypedValue(): mixed
    {
        return match ($this->setting_type) {
            'integer' => (int) $this->setting_value,
            'boolean' => (bool) $this->setting_value,
            'json' => json_decode($this->setting_value, true),
            default => $this->setting_value,
        };
    }
    
    /**
     * Set setting value from typed value
     */
    public function setTypedValue(mixed $value): void
    {
        $this->setting_value = match ($this->setting_type) {
            'integer' => (string) $value,
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value,
        };
    }
}

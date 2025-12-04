<?php

namespace CiInbox\App\Repositories;

use CiInbox\App\Models\SystemSetting;
use CiInbox\Modules\Encryption\EncryptionService;
use CiInbox\Modules\Logger\LoggerInterface;

/**
 * SystemSetting Repository
 * 
 * Handles data access for system settings with encryption support.
 */
class SystemSettingRepository
{
    public function __construct(
        private EncryptionService $encryption,
        private LoggerInterface $logger
    ) {}
    
    /**
     * Get setting by key
     */
    public function get(string $key): ?SystemSetting
    {
        try {
            $setting = SystemSetting::where('setting_key', $key)->first();
            
            if ($setting && $setting->is_encrypted && !empty($setting->setting_value)) {
                $setting->setting_value = $this->encryption->decrypt($setting->setting_value);
            }
            
            return $setting;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get system setting', [
                'key' => $key,
                'exception' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Get setting value (typed)
     */
    public function getValue(string $key, mixed $default = null): mixed
    {
        $setting = $this->get($key);
        return $setting ? $setting->getTypedValue() : $default;
    }
    
    /**
     * Set setting value
     */
    public function set(string $key, mixed $value): SystemSetting
    {
        try {
            $setting = SystemSetting::where('setting_key', $key)->first();
            
            if (!$setting) {
                throw new \Exception("Setting key '{$key}' not found");
            }
            
            $setting->setTypedValue($value);
            
            if ($setting->is_encrypted && !empty($setting->setting_value)) {
                $setting->setting_value = $this->encryption->encrypt($setting->setting_value);
            }
            
            $setting->save();
            
            $this->logger->info('System setting updated', [
                'key' => $key,
                'encrypted' => $setting->is_encrypted
            ]);
            
            return $setting;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to set system setting', [
                'key' => $key,
                'exception' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Get multiple settings by prefix
     */
    public function getByPrefix(string $prefix): array
    {
        try {
            $settings = SystemSetting::where('setting_key', 'like', $prefix . '%')->get();
            
            $result = [];
            foreach ($settings as $setting) {
                $key = str_replace($prefix, '', $setting->setting_key);
                
                if ($setting->is_encrypted && !empty($setting->setting_value)) {
                    $setting->setting_value = $this->encryption->decrypt($setting->setting_value);
                }
                
                $result[$key] = $setting->getTypedValue();
            }
            
            return $result;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get settings by prefix', [
                'prefix' => $prefix,
                'exception' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Update multiple settings
     */
    public function updateMultiple(array $settings): void
    {
        try {
            foreach ($settings as $key => $value) {
                $this->set($key, $value);
            }
            
            $this->logger->info('Multiple settings updated', [
                'count' => count($settings),
                'keys' => array_keys($settings)
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to update multiple settings', [
                'exception' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Get all settings
     */
    public function getAll(): array
    {
        try {
            $settings = SystemSetting::all();
            
            $result = [];
            foreach ($settings as $setting) {
                if ($setting->is_encrypted && !empty($setting->setting_value)) {
                    // For encrypted values, return masked value in list
                    $result[$setting->setting_key] = [
                        'value' => '********',
                        'type' => $setting->setting_type,
                        'encrypted' => true,
                        'description' => $setting->description
                    ];
                } else {
                    $result[$setting->setting_key] = [
                        'value' => $setting->getTypedValue(),
                        'type' => $setting->setting_type,
                        'encrypted' => false,
                        'description' => $setting->description
                    ];
                }
            }
            
            return $result;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get all settings', [
                'exception' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}

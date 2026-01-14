<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'updated_by',
    ];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null)
    {
        // In tenant context with database cache, tagging isn't supported
        // So we query directly without caching in that case
        try {
            return Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
                return static::getFromDatabase($key, $default);
            });
        } catch (\BadMethodCallException $e) {
            // Cache doesn't support tagging, query directly
            return static::getFromDatabase($key, $default);
        }
    }

    /**
     * Get setting value directly from database
     */
    protected static function getFromDatabase(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return static::castValue($setting->value, $setting->type);
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, $value, ?int $updatedBy = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'updated_by' => $updatedBy,
            ]
        );

        try {
            Cache::forget("setting_{$key}");
        } catch (\BadMethodCallException $e) {
            // Cache doesn't support tagging, ignore
        }
    }

    /**
     * Cast value based on type
     */
    protected static function castValue($value, ?string $type)
    {
        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'time', 'string' => $value,
            default => $value,
        };
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        try {
            $settings = static::all();
            foreach ($settings as $setting) {
                Cache::forget("setting_{$setting->key}");
            }
        } catch (\BadMethodCallException $e) {
            // Cache doesn't support tagging, ignore
        }
    }

    /**
     * Relationship with user who updated
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

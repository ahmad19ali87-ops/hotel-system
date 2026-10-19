<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SiteSetting extends Model
{
    protected $table = 'site_settings';

    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    public static function get(string $key, $default = null)
    {
        try {
            return Cache::rememberForever("site_setting_{$key}", function () use ($key, $default) {
                $setting = static::where('key', $key)->first();
                return $setting && $setting->value !== null ? $setting->value : $default;
            });
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public static function set(string $key, $value, string $group = 'general')
    {
        try {
            $setting = static::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => $group]
            );

            Cache::forget("site_setting_{$key}");
            Cache::forget("site_settings_all");

            return $setting;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function getAll(): array
    {
        try {
            return Cache::rememberForever('site_settings_all', function () {
                return static::pluck('value', 'key')->toArray();
            });
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function getImageUrl(string $key): ?string
    {
        $path = static::get($key);
        if (!$path) {
            return null;
        }

        if (Storage::disk('public')->exists($path)) {
            return asset('storage/' . ltrim($path, '/'));
        }

        return asset($path);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemThreshold extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Get a threshold value
     */
    public static function getValue(string $key, $default = null)
    {
        return Cache::remember("threshold_{$key}", 3600, function () use ($key, $default) {
            $threshold = self::where('key', $key)->first();
            return $threshold ? $threshold->value : $default;
        });
    }

    /**
     * Set a threshold value
     */
    public static function setValue(string $key, $value): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
        Cache::forget("threshold_{$key}");
    }
}

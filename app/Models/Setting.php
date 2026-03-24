<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type'];

    // Get a single setting value cast to its type
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        if (!$setting) return $default;
        return static::cast($setting->value, $setting->type);
    }

    // Set a single setting value
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? json_encode($value) : (string) $value]
        );
    }

    // Return all settings as a flat key => value object
    public static function allAsObject(): array
    {
        return static::all()->mapWithKeys(function ($s) {
            return [$s->key => static::cast($s->value, $s->type)];
        })->toArray();
    }

    private static function cast(mixed $value, string $type): mixed
    {
        return match ($type) {
            'number'  => is_numeric($value) ? $value + 0 : $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json'    => json_decode($value, true),
            default   => $value,
        };
    }
}

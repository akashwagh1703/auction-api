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

    // Set a single setting value — preserves existing type, infers type for new keys
    public static function set(string $key, mixed $value): void
    {
        $existing = static::where('key', $key)->first();

        if ($existing) {
            $existing->update([
                'value' => is_array($value) ? json_encode($value) : (string) $value,
            ]);
        } else {
            // Infer type for new keys
            $type = match (true) {
                is_bool($value)              => 'boolean',
                is_int($value)||is_float($value) => 'number',
                is_array($value)             => 'json',
                default                      => 'string',
            };
            static::create([
                'key'   => $key,
                'value' => is_array($value) ? json_encode($value) : (string) $value,
                'type'  => $type,
            ]);
        }
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

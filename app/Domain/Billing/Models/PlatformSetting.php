<?php

namespace App\Domain\Billing\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'is_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
        ];
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        if ($setting->is_encrypted && $setting->value) {
            try {
                return decrypt($setting->value);
            } catch (\Throwable) {
                return $default;
            }
        }

        return $setting->value ?? $default;
    }

    public static function setValue(string $key, mixed $value, bool $encrypt = false): void
    {
        static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $encrypt && $value ? encrypt($value) : $value,
                'is_encrypted' => $encrypt,
            ]
        );
    }
}

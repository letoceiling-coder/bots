<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * Получить значение настройки с приведением типа
     */
    public function getValueAttribute($value)
    {
        if ($value === null) {
            return null;
        }

        return match ($this->type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'number', 'integer' => is_numeric($value) ? (int)$value : null,
            'float' => is_numeric($value) ? (float)$value : null,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Установить значение настройки с преобразованием типа
     */
    public function setValueAttribute($value)
    {
        if ($value === null) {
            $this->attributes['value'] = null;
            return;
        }

        $this->attributes['value'] = match ($this->type) {
            'boolean' => $value ? '1' : '0',
            'number', 'integer', 'float' => (string)$value,
            'json' => is_string($value) ? $value : json_encode($value),
            default => (string)$value,
        };
    }

    /**
     * Получить настройку по ключу
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Установить настройку
     */
    public static function set(string $key, $value, string $type = 'string', string $group = 'general', ?string $description = null): self
    {
        $setting = static::firstOrNew(['key' => $key]);
        $setting->value = $value;
        $setting->type = $type;
        $setting->group = $group;
        if ($description !== null) {
            $setting->description = $description;
        }
        $setting->save();

        return $setting;
    }

    /**
     * Получить все настройки группы
     */
    public static function getGroup(string $group): array
    {
        return static::where('group', $group)
            ->get()
            ->pluck('value', 'key')
            ->toArray();
    }
}

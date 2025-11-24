<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotMenuSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'bot_id',
        'menu_enabled',
        'menu_type',
    ];

    protected $casts = [
        'menu_enabled' => 'boolean',
    ];

    /**
     * Связь с ботом
     */
    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    /**
     * Получить или создать настройку для бота
     */
    public static function getOrCreateForBot(int $botId): self
    {
        return static::firstOrCreate(
            ['bot_id' => $botId],
            [
                'menu_enabled' => true,
                'menu_type' => 'commands',
            ]
        );
    }
}

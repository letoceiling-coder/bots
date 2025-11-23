<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BotSession extends Model
{
    use SoftDeletes;

    protected $table = 'bot_sessions';

    protected $fillable = [
        'bot_id',
        'chat_id',
        'user_id',
        'username',
        'first_name',
        'last_name',
        'current_block_id',
        'status',
        'started_at',
        'last_activity_at',
        'completed_at',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Отношение к боту
     */
    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    /**
     * Шаги сессии
     */
    public function steps(): HasMany
    {
        return $this->hasMany(BotSessionStep::class, 'session_id')->orderBy('step_order');
    }

    /**
     * Файлы сессии
     */
    public function files(): HasMany
    {
        return $this->hasMany(BotSessionFile::class, 'session_id');
    }

    /**
     * Собранные данные
     */
    public function data(): HasMany
    {
        return $this->hasMany(BotSessionData::class, 'session_id');
    }

    /**
     * Получить значение данных по ключу
     */
    public function getDataValue(string $key): ?string
    {
        $data = $this->data()->where('key', $key)->first();
        return $data ? $data->value : null;
    }

    /**
     * Установить значение данных
     */
    public function setDataValue(string $key, ?string $value, ?string $blockId = null): void
    {
        $this->data()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'block_id' => $blockId,
                'collected_at' => now(),
            ]
        );
    }

    /**
     * Обновить активность сессии
     */
    public function touchActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    /**
     * Завершить сессию
     */
    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Пометить как заброшенную
     */
    public function abandon(): void
    {
        $this->update(['status' => 'abandoned']);
    }
}

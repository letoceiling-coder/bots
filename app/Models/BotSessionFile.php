<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotSessionFile extends Model
{
    protected $table = 'bot_session_files';

    protected $fillable = [
        'session_id',
        'step_id',
        'telegram_file_id',
        'file_type',
        'file_name',
        'mime_type',
        'file_size',
        'local_path',
        'media_id',
        'downloaded_at',
        'metadata',
    ];

    protected $casts = [
        'downloaded_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Отношение к сессии
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(BotSession::class, 'session_id');
    }

    /**
     * Отношение к шагу
     */
    public function step(): BelongsTo
    {
        return $this->belongsTo(BotSessionStep::class, 'step_id');
    }

    /**
     * Отношение к медиа (если есть)
     */
    public function media(): BelongsTo
    {
        // Проверяем, существует ли модель Media
        if (class_exists(\App\Models\Media::class)) {
            return $this->belongsTo(\App\Models\Media::class, 'media_id');
        }
        // Возвращаем пустой relation, если модель не существует
        return $this->newQuery()->whereRaw('1 = 0');
    }

    /**
     * Получить URL файла
     */
    public function getUrlAttribute(): ?string
    {
        if ($this->local_path) {
            return asset('storage/' . $this->local_path);
        }
        return null;
    }

    /**
     * Проверить, существует ли файл
     */
    public function exists(): bool
    {
        if (!$this->local_path) {
            return false;
        }
        return file_exists(storage_path('app/' . $this->local_path));
    }
}

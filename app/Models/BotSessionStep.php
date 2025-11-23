<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BotSessionStep extends Model
{
    protected $table = 'bot_session_steps';

    protected $fillable = [
        'session_id',
        'block_id',
        'block_label',
        'method',
        'input_type',
        'user_input',
        'bot_response',
        'bot_response_data',
        'step_order',
        'timestamp',
        'metadata',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'bot_response_data' => 'array',
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
     * Файлы этого шага
     */
    public function files(): HasMany
    {
        return $this->hasMany(BotSessionFile::class, 'step_id');
    }
}

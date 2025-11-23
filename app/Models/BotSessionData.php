<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotSessionData extends Model
{
    protected $table = 'bot_session_data';

    protected $fillable = [
        'session_id',
        'key',
        'value',
        'block_id',
        'collected_at',
    ];

    protected $casts = [
        'collected_at' => 'datetime',
    ];

    /**
     * Отношение к сессии
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(BotSession::class, 'session_id');
    }
}

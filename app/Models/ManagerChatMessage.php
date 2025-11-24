<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManagerChatMessage extends Model
{
    protected $table = 'manager_chat_messages';

    protected $fillable = [
        'session_id',
        'bot_id',
        'user_chat_id',
        'manager_chat_id',
        'manager_telegram_user_id',
        'direction',
        'message_text',
        'message_type',
        'telegram_message_id',
        'telegram_data',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'telegram_data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Сессия бота
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(BotSession::class, 'session_id');
    }

    /**
     * Бот
     */
    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class, 'bot_id');
    }

    /**
     * Пометить сообщение как прочитанное
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }
}

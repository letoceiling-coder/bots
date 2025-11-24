<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotUser extends Model
{
    protected $table = 'bot_users';

    protected $fillable = [
        'bot_id',
        'telegram_user_id',
        'chat_id',
        'username',
        'first_name',
        'last_name',
        'role',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Бот, к которому относится пользователь
     */
    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    /**
     * Проверить, имеет ли пользователь определенную роль
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Проверить, имеет ли пользователь одну из ролей
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    /**
     * Получить полное имя пользователя
     */
    public function getFullNameAttribute(): string
    {
        $parts = array_filter([$this->first_name, $this->last_name]);
        return !empty($parts) ? implode(' ', $parts) : ($this->username ?? "ID: {$this->telegram_user_id}");
    }
}

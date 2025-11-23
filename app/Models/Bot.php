<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bot extends Model
{
    use SoftDeletes;

    protected $table = 'telegram_bots';

    protected $fillable = [
        'name',
        'token',
        'username',
        'description',
        'is_active',
        'blocks',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'blocks' => 'array',
    ];

    /**
     * Сессии бота
     */
    public function sessions()
    {
        return $this->hasMany(BotSession::class);
    }
}

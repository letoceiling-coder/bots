<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bot_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained('telegram_bots')->onDelete('cascade');
            $table->string('chat_id')->comment('ID чата пользователя в Telegram');
            $table->bigInteger('user_id')->nullable()->comment('ID пользователя Telegram');
            $table->string('username')->nullable()->comment('Username пользователя');
            $table->string('first_name')->nullable()->comment('Имя пользователя');
            $table->string('last_name')->nullable()->comment('Фамилия пользователя');
            $table->string('current_block_id')->nullable()->comment('ID текущего блока карты');
            $table->enum('status', ['active', 'completed', 'abandoned', 'manager_chat'])->default('active')->comment('Статус сессии');
            $table->timestamp('started_at')->useCurrent()->comment('Время начала сессии');
            $table->timestamp('last_activity_at')->useCurrent()->comment('Время последней активности');
            $table->timestamp('completed_at')->nullable()->comment('Время завершения сессии');
            $table->json('metadata')->nullable()->comment('Дополнительная информация');
            $table->timestamps();
            $table->softDeletes();
            
            // Индексы для быстрого поиска
            $table->index(['bot_id', 'chat_id']);
            $table->index(['bot_id', 'status']);
            $table->index('last_activity_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_sessions');
    }
};

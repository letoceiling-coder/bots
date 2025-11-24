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
        Schema::create('manager_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('bot_sessions')->onDelete('cascade');
            $table->foreignId('bot_id')->constrained('telegram_bots')->onDelete('cascade');
            $table->string('user_chat_id')->comment('ID чата пользователя');
            $table->string('manager_chat_id')->comment('ID чата менеджера');
            $table->string('manager_telegram_user_id')->nullable()->comment('ID менеджера в Telegram');
            $table->enum('direction', ['user_to_manager', 'manager_to_user'])->comment('Направление сообщения');
            $table->text('message_text')->nullable()->comment('Текст сообщения');
            $table->string('message_type')->default('text')->comment('Тип сообщения (text, photo, document, etc.)');
            $table->string('telegram_message_id')->nullable()->comment('ID сообщения в Telegram');
            $table->json('telegram_data')->nullable()->comment('Полные данные сообщения из Telegram');
            $table->boolean('is_read')->default(false)->comment('Прочитано ли сообщение');
            $table->timestamp('read_at')->nullable()->comment('Время прочтения');
            $table->timestamps();

            $table->index('session_id');
            $table->index('bot_id');
            $table->index(['user_chat_id', 'manager_chat_id']);
            $table->index('direction');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manager_chat_messages');
    }
};

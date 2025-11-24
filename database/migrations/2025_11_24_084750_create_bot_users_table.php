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
        Schema::create('bot_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained('telegram_bots')->onDelete('cascade');
            $table->string('telegram_user_id')->comment('ID пользователя в Telegram');
            $table->string('chat_id')->comment('ID чата пользователя');
            $table->string('username')->nullable()->comment('Username пользователя');
            $table->string('first_name')->nullable()->comment('Имя пользователя');
            $table->string('last_name')->nullable()->comment('Фамилия пользователя');
            $table->string('role')->default('user')->comment('Роль пользователя в боте (admin, manager, user)');
            $table->json('metadata')->nullable()->comment('Дополнительная информация');
            $table->timestamps();

            $table->unique(['bot_id', 'telegram_user_id']);
            $table->index('bot_id');
            $table->index('telegram_user_id');
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_users');
    }
};

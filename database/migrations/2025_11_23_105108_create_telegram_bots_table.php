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
        Schema::create('telegram_bots', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Название бота');
            $table->string('token')->unique()->comment('Токен бота от BotFather');
            $table->string('username')->nullable()->comment('Username бота');
            $table->text('description')->nullable()->comment('Описание бота');
            $table->boolean('is_active')->default(true)->comment('Активен ли бот');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_bots');
    }
};

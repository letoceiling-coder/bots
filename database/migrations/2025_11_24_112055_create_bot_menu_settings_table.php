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
        Schema::create('bot_menu_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained('telegram_bots')->onDelete('cascade')->comment('ID бота');
            $table->boolean('menu_enabled')->default(true)->comment('Включена ли кнопка меню');
            $table->string('menu_type')->default('commands')->comment('Тип кнопки меню: commands, web_app, default');
            $table->timestamps();

            $table->unique('bot_id');
            $table->index('menu_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_menu_settings');
    }
};

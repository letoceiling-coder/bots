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
        Schema::create('bot_session_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('bot_sessions')->onDelete('cascade');
            $table->string('block_id')->nullable()->comment('ID блока карты');
            $table->string('block_label')->nullable()->comment('Название блока');
            $table->string('method')->nullable()->comment('Метод блока (sendMessage, inlineKeyboard и т.д.)');
            $table->string('input_type')->nullable()->comment('Тип ввода (text, callback, file, contact и т.д.)');
            $table->text('user_input')->nullable()->comment('Ответ пользователя');
            $table->text('bot_response')->nullable()->comment('Ответ бота');
            $table->json('bot_response_data')->nullable()->comment('Данные ответа бота (JSON)');
            $table->integer('step_order')->default(0)->comment('Порядковый номер шага');
            $table->timestamp('timestamp')->useCurrent()->comment('Время шага');
            $table->json('metadata')->nullable()->comment('Дополнительная информация');
            $table->timestamps();
            
            // Индексы
            $table->index(['session_id', 'step_order']);
            $table->index('timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_session_steps');
    }
};

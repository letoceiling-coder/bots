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
        Schema::create('bot_session_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('bot_sessions')->onDelete('cascade');
            $table->string('key')->comment('Ключ данных (fio, phone, inn, opf и т.д.)');
            $table->text('value')->nullable()->comment('Значение данных');
            $table->string('block_id')->nullable()->comment('ID блока, где данные были собраны');
            $table->timestamp('collected_at')->useCurrent()->comment('Время сбора данных');
            $table->timestamps();
            
            // Индексы
            $table->index(['session_id', 'key']);
            $table->unique(['session_id', 'key'], 'unique_session_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_session_data');
    }
};

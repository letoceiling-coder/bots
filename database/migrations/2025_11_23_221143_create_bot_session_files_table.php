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
        Schema::create('bot_session_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('bot_sessions')->onDelete('cascade');
            $table->foreignId('step_id')->nullable()->constrained('bot_session_steps')->onDelete('set null');
            $table->string('telegram_file_id')->nullable()->comment('file_id из Telegram');
            $table->string('file_type')->comment('Тип файла (document, photo, video, audio, voice)');
            $table->string('file_name')->nullable()->comment('Имя файла');
            $table->string('mime_type')->nullable()->comment('MIME тип файла');
            $table->bigInteger('file_size')->nullable()->comment('Размер файла в байтах');
            $table->string('local_path')->nullable()->comment('Путь к сохраненному файлу');
            $table->foreignId('media_id')->nullable()->constrained('media')->onDelete('set null')->comment('Связь с таблицей media');
            $table->timestamp('downloaded_at')->nullable()->comment('Время загрузки файла');
            $table->json('metadata')->nullable()->comment('Дополнительная информация');
            $table->timestamps();
            
            // Индексы
            $table->index('session_id');
            $table->index('step_id');
            $table->index('file_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bot_session_files');
    }
};

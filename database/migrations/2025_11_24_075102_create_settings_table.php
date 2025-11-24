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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('Уникальный ключ настройки');
            $table->text('value')->nullable()->comment('Значение настройки');
            $table->string('type')->default('string')->comment('Тип настройки: string, number, boolean, json');
            $table->string('group')->default('general')->comment('Группа настроек (general, telegram, email, etc.)');
            $table->text('description')->nullable()->comment('Описание настройки');
            $table->boolean('is_public')->default(false)->comment('Публичная ли настройка (доступна через API)');
            $table->timestamps();

            $table->index('group');
            $table->index('is_public');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};

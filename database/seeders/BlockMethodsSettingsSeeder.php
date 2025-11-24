<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class BlockMethodsSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            // Отправка сообщений
            ['key' => 'block_method_sendMessage', 'value' => '1', 'type' => 'boolean', 'group' => 'block_methods', 'description' => 'Отправить сообщение'],
            ['key' => 'block_method_sendDice', 'value' => '1', 'type' => 'boolean', 'group' => 'block_methods', 'description' => '🎲 Отправить кубик'],
            ['key' => 'block_method_sendPoll', 'value' => '1', 'type' => 'boolean', 'group' => 'block_methods', 'description' => '📊 Отправить опрос'],
            ['key' => 'block_method_sendVenue', 'value' => '1', 'type' => 'boolean', 'group' => 'block_methods', 'description' => '📍 Отправить локацию'],
            ['key' => 'block_method_sendContact', 'value' => '1', 'type' => 'boolean', 'group' => 'block_methods', 'description' => '👤 Отправить контакт'],
            
            // Медиа
            ['key' => 'block_method_sendPhoto', 'value' => '1', 'type' => 'boolean', 'group' => 'block_methods', 'description' => '📷 Фото'],
            ['key' => 'block_method_sendVideo', 'value' => '1', 'type' => 'boolean', 'group' => 'block_methods', 'description' => '🎥 Видео'],
            ['key' => 'block_method_sendDocument', 'value' => '1', 'type' => 'boolean', 'group' => 'block_methods', 'description' => '📄 Документ'],
            ['key' => 'block_method_sendAudio', 'value' => '1', 'type' => 'boolean', 'group' => 'block_methods', 'description' => '🎵 Аудио'],
            ['key' => 'block_method_sendVoice', 'value' => '1', 'type' => 'boolean', 'group' => 'block_methods', 'description' => '🎤 Голосовое'],
            ['key' => 'block_method_sendVideoNote', 'value' => '1', 'type' => 'boolean', 'group' => 'block_methods', 'description' => '🎬 Видео-кружок'],
            ['key' => 'block_method_sendAnimation', 'value' => '1', 'type' => 'boolean', 'group' => 'block_methods', 'description' => '🎞️ Анимация/GIF'],
            ['key' => 'block_method_sendSticker', 'value' => '1', 'type' => 'boolean', 'group' => 'block_methods', 'description' => '😊 Стикер'],
            ['key' => 'block_method_sendLocation', 'value' => '1', 'type' => 'boolean', 'group' => 'block_methods', 'description' => '📍 Локация'],
            ['key' => 'block_method_sendMediaGroup', 'value' => '1', 'type' => 'boolean', 'group' => 'block_methods', 'description' => '🖼️ Группа медиа'],
            
            // Редактирование
            ['key' => 'block_method_editMessageText', 'value' => '1', 'type' => 'boolean', 'group' => 'block_methods', 'description' => 'Редактировать текст'],
            ['key' => 'block_method_editMessageCaption', 'value' => '1', 'type' => 'boolean', 'group' => 'block_methods', 'description' => 'Редактировать подпись'],
            
            // Управление
            ['key' => 'block_method_deleteMessage', 'value' => '1', 'type' => 'boolean', 'group' => 'block_methods', 'description' => 'Удалить сообщение'],
            ['key' => 'block_method_pinChatMessage', 'value' => '1', 'type' => 'boolean', 'group' => 'block_methods', 'description' => 'Закрепить сообщение'],
            ['key' => 'block_method_unpinChatMessage', 'value' => '1', 'type' => 'boolean', 'group' => 'block_methods', 'description' => 'Открепить сообщение'],
            ['key' => 'block_method_sendChatAction', 'value' => '1', 'type' => 'boolean', 'group' => 'block_methods', 'description' => '⏳ Индикатор действия'],
            
            // Кнопки
            ['key' => 'block_method_replyKeyboard', 'value' => '1', 'type' => 'boolean', 'group' => 'block_methods', 'description' => 'Reply-кнопки'],
            ['key' => 'block_method_inlineKeyboard', 'value' => '1', 'type' => 'boolean', 'group' => 'block_methods', 'description' => 'Inline кнопки'],
            
            // Специальные функции
            ['key' => 'block_method_question', 'value' => '1', 'type' => 'boolean', 'group' => 'block_methods', 'description' => 'Задать вопрос'],
            ['key' => 'block_method_managerChat', 'value' => '1', 'type' => 'boolean', 'group' => 'block_methods', 'description' => '💬 Чат с менеджером'],
            ['key' => 'block_method_apiRequest', 'value' => '1', 'type' => 'boolean', 'group' => 'block_methods', 'description' => '🌐 API Запрос'],
            ['key' => 'block_method_apiButtons', 'value' => '1', 'type' => 'boolean', 'group' => 'block_methods', 'description' => '🔘 API Кнопки'],
            ['key' => 'block_method_apiMediaGroup', 'value' => '1', 'type' => 'boolean', 'group' => 'block_methods', 'description' => '🖼️ API Группа медиа'],
            ['key' => 'block_method_assistant', 'value' => '1', 'type' => 'boolean', 'group' => 'block_methods', 'description' => '🤖 AI Ассистент (ChatGPT)'],
        ];

        foreach ($methods as $method) {
            Setting::updateOrCreate(
                ['key' => $method['key']],
                $method
            );
        }
    }
}

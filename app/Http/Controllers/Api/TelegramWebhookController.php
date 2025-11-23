<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Services\BotMapHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    protected BotMapHandler $mapHandler;

    public function __construct(BotMapHandler $mapHandler)
    {
        $this->mapHandler = $mapHandler;
    }

    /**
     * Обработка webhook от Telegram
     * Роут: POST /api/telegram/webhook/{bot_id}
     */
    public function handle(Request $request, string $botId)
    {
        // Логируем входящий запрос
        Log::info('Telegram webhook received', [
            'bot_id' => $botId,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_size' => $request->header('Content-Length'),
        ]);

        try {
            // Находим бота
            $bot = Bot::find($botId);

            if (!$bot) {
                Log::error('Bot not found', [
                    'bot_id' => $botId,
                    'request_data' => $request->all(),
                ]);

                return response()->json([
                    'ok' => false,
                    'error' => 'Bot not found',
                ], 404);
            }

            // Проверяем, активен ли бот
            if (!$bot->is_active) {
                Log::warning('Bot is not active', [
                    'bot_id' => $bot->id,
                    'bot_name' => $bot->name,
                ]);

                return response()->json([
                    'ok' => false,
                    'error' => 'Bot is not active',
                ], 403);
            }

            // Логируем обновление
            $update = $request->all();
            Log::info('Processing Telegram update', [
                'bot_id' => $bot->id,
                'bot_name' => $bot->name,
                'update_id' => $update['update_id'] ?? null,
                'update_type' => $this->getUpdateType($update),
                'chat_id' => $this->getChatId($update),
            ]);

            // Обрабатываем обновление
            $this->mapHandler->handleUpdate($bot, $update);

            Log::info('Telegram update processed successfully', [
                'bot_id' => $bot->id,
                'update_id' => $update['update_id'] ?? null,
            ]);

            // Telegram требует ответ 200 OK
            return response()->json(['ok' => true]);

        } catch (\Exception $e) {
            Log::error('Error processing Telegram webhook', [
                'bot_id' => $botId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            // Возвращаем 200 OK даже при ошибке, чтобы Telegram не повторял запрос
            return response()->json([
                'ok' => false,
                'error' => 'Internal server error',
            ], 200);
        }
    }

    /**
     * Получить тип обновления
     */
    protected function getUpdateType(array $update): string
    {
        if (isset($update['message'])) {
            $message = $update['message'];
            if (isset($message['text'])) {
                return 'message_text';
            } elseif (isset($message['document'])) {
                return 'message_document';
            } elseif (isset($message['photo'])) {
                return 'message_photo';
            } elseif (isset($message['contact'])) {
                return 'message_contact';
            } elseif (isset($message['location'])) {
                return 'message_location';
            }
            return 'message';
        } elseif (isset($update['callback_query'])) {
            return 'callback_query';
        } elseif (isset($update['edited_message'])) {
            return 'edited_message';
        }
        return 'unknown';
    }

    /**
     * Получить chat_id из обновления
     */
    protected function getChatId(array $update): ?string
    {
        if (isset($update['message']['chat']['id'])) {
            return (string)$update['message']['chat']['id'];
        } elseif (isset($update['callback_query']['message']['chat']['id'])) {
            return (string)$update['callback_query']['message']['chat']['id'];
        }
        return null;
    }
}

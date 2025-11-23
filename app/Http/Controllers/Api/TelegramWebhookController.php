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
     * GET - для проверки доступности endpoint
     */
    public function handle(Request $request, string $botId)
    {
        // Определяем метод запроса (GET, HEAD, POST)
        $method = $request->method();
        
        // Для GET и HEAD запросов (проверка доступности endpoint)
        if (in_array($method, ['GET', 'HEAD'])) {
            try {
                $bot = Bot::find($botId);
                
                $response = [
                    'status' => 'ok',
                    'message' => 'Webhook endpoint is active',
                    'bot_id' => $botId,
                    'bot_name' => $bot ? $bot->name : 'not found',
                    'bot_active' => $bot ? ($bot->is_active ?? false) : false,
                    'method' => $method,
                    'note' => 'This endpoint accepts POST requests from Telegram',
                    'webhook_url' => url("/api/telegram/webhook/{$botId}"),
                    'timestamp' => now()->toIso8601String(),
                ];
                
                return response()->json($response, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } catch (\Exception $e) {
                Log::error('Error in webhook GET/HEAD request', [
                    'bot_id' => $botId,
                    'method' => $method,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                return response()->json([
                    'status' => 'error',
                    'message' => 'Internal server error',
                    'error' => $e->getMessage(),
                ], 500, [], JSON_PRETTY_PRINT);
            }
        }
        
        // Логируем входящий POST запрос от Telegram
        $rawUpdate = $request->all();
        Log::info('Telegram webhook received', [
            'bot_id' => $botId,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_size' => $request->header('Content-Length'),
            'method' => 'POST',
            'update_keys' => array_keys($rawUpdate),
            'has_callback_query' => isset($rawUpdate['callback_query']),
            'has_message' => isset($rawUpdate['message']),
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
            $updateType = $this->getUpdateType($update);
            $logData = [
                'bot_id' => $bot->id,
                'bot_name' => $bot->name,
                'update_id' => $update['update_id'] ?? null,
                'update_type' => $updateType,
                'chat_id' => $this->getChatId($update),
            ];

            // Добавляем детальную информацию для callback_query
            if ($updateType === 'callback_query' && isset($update['callback_query'])) {
                $logData['callback_query_id'] = $update['callback_query']['id'] ?? null;
                $logData['callback_data'] = $update['callback_query']['data'] ?? null;
                $logData['from_user_id'] = $update['callback_query']['from']['id'] ?? null;
                $logData['from_username'] = $update['callback_query']['from']['username'] ?? null;
            }

            Log::info('Processing Telegram update', $logData);

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

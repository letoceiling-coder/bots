<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Services\ExtendedTelegraph;
use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BotController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $bots = Bot::orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'data' => $bots,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'token' => 'required|string|unique:telegram_bots,token',
            'username' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $bot = Bot::create([
            'name' => $request->name,
            'token' => $request->token,
            'username' => $request->username,
            'description' => $request->description,
            'is_active' => $request->has('is_active') ? $request->is_active : true,
        ]);

        return response()->json([
            'message' => 'Бот успешно создан',
            'data' => $bot,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $bot = Bot::findOrFail($id);
        
        return response()->json([
            'data' => $bot,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $bot = Bot::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'token' => 'required|string|unique:telegram_bots,token,' . $id,
            'username' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $bot->update([
            'name' => $request->name,
            'token' => $request->token,
            'username' => $request->username,
            'description' => $request->description,
            'is_active' => $request->has('is_active') ? $request->is_active : $bot->is_active,
        ]);

        return response()->json([
            'message' => 'Бот успешно обновлен',
            'data' => $bot->fresh(),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $bot = Bot::findOrFail($id);
        $bot->delete();

        return response()->json([
            'message' => 'Бот успешно удален',
        ]);
    }

    /**
     * Получить информацию о боте через Telegram API
     */
    public function getBotInfo(string $id)
    {
        $bot = Bot::findOrFail($id);
        
        try {
            $telegraph = new ExtendedTelegraph();
            $telegraph->bot = $bot;
            $info = $telegraph->getMeApi();
            
            return response()->json([
                'message' => 'Информация о боте получена',
                'data' => $info,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ошибка получения информации о боте',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Отправить тестовое сообщение от имени бота
     */
    public function sendTestMessage(Request $request, string $id)
    {
        $bot = Bot::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'chat_id' => 'required|string',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $service = new TelegramBotService();
            $result = $service->sendMessage($bot, $request->chat_id, $request->message);
            
            return response()->json([
                'message' => 'Сообщение отправлено',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ошибка отправки сообщения',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Выполнить метод блока (тестирование карты бота)
     */
    public function executeBlockMethod(Request $request, string $id)
    {
        $bot = Bot::findOrFail($id);

        // Проверка активности бота
        if (!$bot->is_active) {
            return response()->json([
                'message' => 'Бот неактивен',
                'error' => 'Для выполнения методов бот должен быть активен. Активируйте бота в настройках.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'method' => 'required|string',
            'method_data' => 'required|array',
            'chat_id' => 'required|string|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $service = new TelegramBotService();
            $telegraph = $service->bot($bot);
            $telegraph->chat($request->chat_id);

            $method = $request->method;
            $methodData = $request->method_data;
            $result = null;

            switch ($method) {
                case 'sendMessage':
                    $result = $telegraph->message($methodData['text'] ?? '')
                        ->parseMode($methodData['parse_mode'] ?? null)
                        ->send();
                    break;

                case 'sendDice':
                    $result = $telegraph->sendDice($methodData['emoji'] ?? '🎲')->send();
                    break;

                case 'sendPoll':
                    $result = $telegraph->sendPoll(
                        $methodData['question'] ?? '',
                        $methodData['options'] ?? [],
                        $methodData['is_anonymous'] ?? true
                    )->send();
                    break;

                case 'sendVenue':
                    $result = $telegraph->sendVenue(
                        $methodData['latitude'] ?? 0,
                        $methodData['longitude'] ?? 0,
                        $methodData['title'] ?? '',
                        $methodData['address'] ?? ''
                    )->send();
                    break;

                case 'sendContact':
                    $result = $telegraph->sendContact(
                        $methodData['phone_number'] ?? '',
                        $methodData['first_name'] ?? '',
                        $methodData['last_name'] ?? null
                    )->send();
                    break;

                case 'replyKeyboard':
                    // Создаем клавиатуру ответа
                    $keyboard = [];
                    foreach ($methodData['keyboard'] ?? [] as $row) {
                        $keyboardRow = [];
                        foreach ($row as $button) {
                            $keyboardRow[] = ['text' => $button['text'] ?? ''];
                        }
                        $keyboard[] = $keyboardRow;
                    }
                    $result = $telegraph->message('Выберите действие:')
                        ->keyboard($keyboard)
                        ->send();
                    break;

                case 'inlineKeyboard':
                    // Создаем inline клавиатуру
                    $inlineKeyboard = [];
                    foreach ($methodData['inline_keyboard'] ?? [] as $row) {
                        $inlineRow = [];
                        foreach ($row as $button) {
                            $inlineButton = ['text' => $button['text'] ?? ''];
                            if (!empty($button['callback_data'])) {
                                $inlineButton['callback_data'] = $button['callback_data'];
                            } elseif (!empty($button['url'])) {
                                $inlineButton['url'] = $button['url'];
                            }
                            $inlineRow[] = $inlineButton;
                        }
                        $inlineKeyboard[] = $inlineRow;
                    }
                    $result = $telegraph->message('Выберите действие:')
                        ->inlineKeyboard($inlineKeyboard)
                        ->send();
                    break;

                case 'editMessageText':
                    $result = $telegraph->editMessageText(
                        $methodData['message_id'] ?? null,
                        $methodData['text'] ?? ''
                    )->send();
                    break;

                case 'editMessageCaption':
                    $result = $telegraph->editMessageCaption(
                        $methodData['message_id'] ?? null,
                        $methodData['caption'] ?? ''
                    )->send();
                    break;

                case 'deleteMessage':
                    $result = $telegraph->deleteMessage($methodData['message_id'] ?? null)->send();
                    break;

                case 'pinChatMessage':
                    $result = $telegraph->pinChatMessageApi(
                        $methodData['message_id'] ?? null,
                        $methodData['disable_notification'] ?? false
                    )->send();
                    break;

                default:
                    return response()->json([
                        'message' => 'Неизвестный метод',
                        'error' => "Метод '{$method}' не поддерживается",
                    ], 400);
            }

            return response()->json([
                'message' => 'Метод успешно выполнен',
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            // Определяем тип ошибки и даем рекомендации
            $recommendations = [];
            if (str_contains($errorMessage, 'chat not found') || str_contains($errorMessage, 'chat_id')) {
                $recommendations[] = 'Убедитесь, что бот добавлен в чат и имеет права на отправку сообщений';
                $recommendations[] = 'Проверьте правильность chat_id';
            }
            if (str_contains($errorMessage, 'token') || str_contains($errorMessage, 'Unauthorized')) {
                $recommendations[] = 'Проверьте правильность токена бота';
                $recommendations[] = 'Убедитесь, что бот активен';
            }
            if (str_contains($errorMessage, 'message') || str_contains($errorMessage, 'text')) {
                $recommendations[] = 'Проверьте параметры сообщения (текст, длина и т.д.)';
            }
            if (empty($recommendations)) {
                $recommendations[] = 'Проверьте настройки бота и параметры метода';
                $recommendations[] = 'Убедитесь, что бот активен и имеет необходимые права';
            }

            return response()->json([
                'message' => 'Ошибка выполнения метода',
                'error' => $errorMessage,
                'recommendations' => $recommendations,
            ], 500);
        }
    }

    /**
     * Получить последние обновления бота (для определения chat_id)
     */
    public function getBotUpdates(string $id)
    {
        $bot = Bot::findOrFail($id);

        try {
            $telegraph = new ExtendedTelegraph();
            $telegraph->bot = $bot;
            
            // Проверяем наличие webhook перед получением обновлений
            try {
                $webhookInfo = $telegraph->getWebhookInfoApi();
                if (isset($webhookInfo['result']['url']) && !empty($webhookInfo['result']['url'])) {
                    // Webhook установлен, это может мешать получению обновлений
                    // Но не блокируем запрос, просто предупреждаем
                }
            } catch (\Exception $e) {
                // Игнорируем ошибку проверки webhook
            }
            
            // Получаем последние обновления
            $updates = $telegraph->getUpdatesApi(null, 10);
            
            // Проверяем, что ответ от API корректен
            if (!isset($updates['ok'])) {
                return response()->json([
                    'message' => 'Ошибка получения обновлений',
                    'error' => 'Неверный формат ответа от Telegram API',
                    'recommendations' => [
                        'Проверьте подключение к интернету',
                        'Проверьте правильность токена бота',
                        'Убедитесь, что бот активен'
                    ],
                ], 500);
            }
            
            // Если API вернул ошибку
            if (!$updates['ok']) {
                $errorCode = $updates['error_code'] ?? null;
                $description = $updates['description'] ?? 'Неизвестная ошибка';
                
                $recommendations = [];
                
                if ($errorCode === 401 || str_contains($description, 'Unauthorized')) {
                    $recommendations[] = 'Проверьте правильность токена бота';
                    $recommendations[] = 'Убедитесь, что бот активен';
                    $recommendations[] = 'Проверьте токен в настройках бота в Telegram';
                } elseif ($errorCode === 409 || str_contains($description, 'conflict')) {
                    $recommendations[] = 'У бота установлен webhook. Для получения обновлений через getUpdates нужно удалить webhook';
                    $recommendations[] = 'Используйте метод deleteWebhook или удалите webhook в настройках бота';
                } elseif (str_contains($description, 'token')) {
                    $recommendations[] = 'Проверьте правильность токена бота';
                    $recommendations[] = 'Убедитесь, что токен не был изменен';
                } else {
                    $recommendations[] = 'Убедитесь, что бот активен';
                    $recommendations[] = 'Проверьте правильность токена бота';
                    $recommendations[] = 'Бот должен получать сообщения (возможно не установлен webhook)';
                }
                
                return response()->json([
                    'message' => 'Ошибка получения обновлений',
                    'error' => $description,
                    'error_code' => $errorCode,
                    'recommendations' => $recommendations,
                ], 500);
            }
            
            // Извлекаем chat_id из обновлений
            $chatIds = [];
            if (isset($updates['result']) && is_array($updates['result'])) {
                foreach ($updates['result'] as $update) {
                    if (isset($update['message']['chat']['id'])) {
                        $chat = $update['message']['chat'];
                        $chatIds[] = [
                            'chat_id' => $chat['id'],
                            'type' => $chat['type'] ?? 'unknown',
                            'title' => $chat['title'] ?? $chat['first_name'] ?? $chat['username'] ?? 'Unknown',
                            'username' => $chat['username'] ?? null,
                            'first_name' => $chat['first_name'] ?? null,
                            'last_update' => date('Y-m-d H:i:s', $update['message']['date'] ?? time())
                        ];
                    } elseif (isset($update['callback_query']['message']['chat']['id'])) {
                        $chat = $update['callback_query']['message']['chat'];
                        $chatIds[] = [
                            'chat_id' => $chat['id'],
                            'type' => $chat['type'] ?? 'unknown',
                            'title' => $chat['title'] ?? $chat['first_name'] ?? $chat['username'] ?? 'Unknown',
                            'username' => $chat['username'] ?? null,
                            'first_name' => $chat['first_name'] ?? null,
                            'last_update' => date('Y-m-d H:i:s', $update['callback_query']['message']['date'] ?? time())
                        ];
                    }
                }
            }

            // Удаляем дубликаты
            $uniqueChatIds = [];
            $seenIds = [];
            foreach ($chatIds as $chat) {
                if (!in_array($chat['chat_id'], $seenIds)) {
                    $uniqueChatIds[] = $chat;
                    $seenIds[] = $chat['chat_id'];
                }
            }

            // Если обновлений нет, это не ошибка, но нужно сообщить пользователю
            if (empty($uniqueChatIds)) {
                return response()->json([
                    'message' => 'Обновления не найдены',
                    'data' => [
                        'updates' => $updates,
                        'chat_ids' => []
                    ],
                    'info' => [
                        'Обновлений не найдено. Это может означать:',
                        '• Бот еще не получал сообщений',
                        '• У бота установлен webhook (нужно удалить для работы getUpdates)',
                        '• Обновления были получены ранее и удалены из очереди'
                    ]
                ]);
            }

            return response()->json([
                'message' => 'Обновления получены',
                'data' => [
                    'updates' => $updates,
                    'chat_ids' => $uniqueChatIds
                ],
            ]);
        } catch (\Exception $e) {
            // Логируем ошибку для отладки
            Log::error('Ошибка получения обновлений бота', [
                'bot_id' => $id,
                'bot_name' => $bot->name ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $errorMessage = $e->getMessage();
            
            // Определяем тип ошибки и даем рекомендации
            $recommendations = [];
            
            if (str_contains($errorMessage, 'token') || str_contains($errorMessage, 'Unauthorized')) {
                $recommendations[] = 'Проверьте правильность токена бота';
                $recommendations[] = 'Убедитесь, что бот активен';
                $recommendations[] = 'Проверьте токен в настройках бота в Telegram';
            } elseif (str_contains($errorMessage, 'conflict') || str_contains($errorMessage, 'webhook') || str_contains($errorMessage, 'Conflict')) {
                $recommendations[] = 'У бота установлен webhook. Для получения обновлений через getUpdates нужно удалить webhook';
                $recommendations[] = 'Используйте метод deleteWebhook или удалите webhook в настройках бота';
            } elseif (str_contains($errorMessage, 'not found') || str_contains($errorMessage, '404')) {
                $recommendations[] = 'Бот не найден. Проверьте правильность токена';
                $recommendations[] = 'Убедитесь, что бот активен';
            } elseif (str_contains($errorMessage, 'Telegram bot token is not set')) {
                $recommendations[] = 'Токен бота не установлен';
                $recommendations[] = 'Проверьте настройки бота в базе данных';
            } elseif (str_contains($errorMessage, 'Telegram API error')) {
                // Пытаемся извлечь детали из сообщения об ошибке
                if (preg_match('/\((\d+)\):\s*(.+)/', $errorMessage, $matches)) {
                    $errorCode = $matches[1];
                    $errorDesc = $matches[2];
                    
                    if ($errorCode == 409) {
                        $recommendations[] = 'У бота установлен webhook. Для получения обновлений через getUpdates нужно удалить webhook';
                        $recommendations[] = 'Используйте метод deleteWebhook или удалите webhook в настройках бота';
                    } elseif ($errorCode == 401) {
                        $recommendations[] = 'Проверьте правильность токена бота';
                        $recommendations[] = 'Убедитесь, что бот активен';
                    }
                } else {
                    $recommendations[] = 'Ошибка при обращении к Telegram API';
                    $recommendations[] = 'Проверьте подключение к интернету';
                    $recommendations[] = 'Проверьте правильность токена бота';
                }
            } else {
                $recommendations[] = 'Убедитесь, что бот активен';
                $recommendations[] = 'Проверьте правильность токена бота';
                $recommendations[] = 'Бот должен получать сообщения (возможно не установлен webhook)';
            }
            
            return response()->json([
                'message' => 'Ошибка получения обновлений',
                'error' => $errorMessage,
                'recommendations' => $recommendations,
            ], 500);
        }
    }
}

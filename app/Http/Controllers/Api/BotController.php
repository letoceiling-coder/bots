<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Services\ExtendedTelegraph;
use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

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
            'blocks' => $request->blocks ?? null,
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
            'blocks' => 'nullable|array',
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
            'blocks' => $request->has('blocks') ? $request->blocks : $bot->blocks,
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
            $telegraph->setBot($bot);
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
            
            Log::info('Executing block method', [
                'bot_id' => $bot->id,
                'bot_name' => $bot->name,
                'method' => $method,
                'chat_id' => $request->chat_id,
                'method_data' => $methodData,
            ]);

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
                            // Inline кнопки должны иметь хотя бы один параметр
                            if (!empty($button['callback_data'])) {
                                $inlineButton['callback_data'] = $button['callback_data'];
                            } elseif (!empty($button['url'])) {
                                $inlineButton['url'] = $button['url'];
                            } elseif (!empty($button['switch_inline_query'])) {
                                $inlineButton['switch_inline_query'] = $button['switch_inline_query'];
                            } elseif (!empty($button['switch_inline_query_current_chat'])) {
                                $inlineButton['switch_inline_query_current_chat'] = $button['switch_inline_query_current_chat'];
                            } elseif (!empty($button['web_app'])) {
                                $inlineButton['web_app'] = $button['web_app'];
                            } else {
                                // Если нет параметров, пропускаем кнопку
                                continue;
                            }
                            $inlineRow[] = $inlineButton;
                        }
                        if (!empty($inlineRow)) {
                        $inlineKeyboard[] = $inlineRow;
                        }
                    }
                    $result = $telegraph->message($methodData['text'] ?? 'Выберите действие:')
                        ->inlineKeyboard($inlineKeyboard)
                        ->send();
                    break;

                case 'editMessageText':
                    // Для редактирования требуется message_id или inline_message_id
                    // Если message_id не указан, пытаемся использовать последний из кеша
                    $messageId = $methodData['message_id'] ?? null;
                    if (empty($messageId) && empty($methodData['inline_message_id'])) {
                        $cacheKey = "last_message_id_{$bot->id}_{$request->chat_id}";
                        $lastMessageId = Cache::get($cacheKey);
                        
                        if ($lastMessageId !== null) {
                            // Автоматически используем последний message_id
                            $messageId = $lastMessageId;
                            Log::info('Using last message_id from cache for editMessageText', [
                                'bot_id' => $bot->id,
                                'chat_id' => $request->chat_id,
                                'message_id' => $messageId,
                            ]);
                        } else {
                            return response()->json([
                                'message' => 'Ошибка выполнения метода',
                                'error' => 'Для редактирования сообщения необходимо указать message_id или inline_message_id',
                                'recommendations' => [
                                    'Укажите message_id сообщения, которое нужно отредактировать',
                                    'Или используйте inline_message_id для inline сообщений',
                                    'message_id можно получить из ответа предыдущего блока отправки сообщения',
                                    'Пример: если предыдущий блок sendMessage вернул message_id: 235, укажите это значение здесь',
                                    'Проверьте логи выполнения предыдущих блоков для получения message_id',
                                    'Примечание: если вы только что отправили сообщение, система автоматически использует его message_id'
                                ],
                                'hint' => 'В логах выше вы можете увидеть message_id из предыдущих успешных блоков (например, message_id: 235, 240, 250 и т.д.)',
                            ], 400);
                        }
                    }
                    $result = $telegraph->editMessageTextApi(
                        $messageId,
                        $methodData['text'] ?? '',
                        $methodData['reply_markup'] ?? null,
                        $methodData['inline_message_id'] ?? null
                    );
                    break;

                case 'editMessageCaption':
                    // Для редактирования требуется message_id или inline_message_id
                    // Если message_id не указан, пытаемся использовать последний из кеша для медиа
                    $messageId = $methodData['message_id'] ?? null;
                    if (empty($messageId) && empty($methodData['inline_message_id'])) {
                        // Используем специальный кеш для медиа-сообщений
                        $mediaCacheKey = "last_media_message_id_{$bot->id}_{$request->chat_id}";
                        $lastMediaMessageId = Cache::get($mediaCacheKey);
                        
                        if ($lastMediaMessageId !== null) {
                            // Автоматически используем последний message_id медиа-сообщения
                            $messageId = $lastMediaMessageId;
                            Log::info('Using last media message_id from cache for editMessageCaption', [
                                'bot_id' => $bot->id,
                                'chat_id' => $request->chat_id,
                                'message_id' => $messageId,
                            ]);
                        } else {
                            return response()->json([
                                'message' => 'Ошибка выполнения метода',
                                'error' => 'Для редактирования подписи необходимо указать message_id или inline_message_id',
                                'recommendations' => [
                                    'Укажите message_id сообщения с медиа (фото, видео, документ и т.д.), которое нужно отредактировать',
                                    'Или используйте inline_message_id для inline сообщений',
                                    'message_id можно получить из ответа предыдущего блока отправки медиа (sendPhoto, sendVideo, sendDocument и т.д.)',
                                    'Примечание: editMessageCaption работает только для медиа-сообщений, у которых есть подпись',
                                    'Если вы только что отправили медиа, система автоматически использует его message_id'
                                ],
                            ], 400);
                        }
                    }
                    $result = $telegraph->editMessageCaptionApi(
                        $messageId,
                        $methodData['caption'] ?? null,
                        $methodData['reply_markup'] ?? null,
                        $methodData['inline_message_id'] ?? null
                    );
                    break;

                case 'deleteMessage':
                    // Для удаления требуется message_id
                    // Если message_id не указан, пытаемся использовать последний из кеша
                    $messageId = $methodData['message_id'] ?? null;
                    if (empty($messageId)) {
                        $cacheKey = "last_message_id_{$bot->id}_{$request->chat_id}";
                        $lastMessageId = Cache::get($cacheKey);
                        
                        if ($lastMessageId !== null) {
                            // Автоматически используем последний message_id
                            $messageId = $lastMessageId;
                            Log::info('Using last message_id from cache for deleteMessage', [
                                'bot_id' => $bot->id,
                                'chat_id' => $request->chat_id,
                                'message_id' => $messageId,
                            ]);
                        } else {
                            return response()->json([
                                'message' => 'Ошибка выполнения метода',
                                'error' => 'Для удаления сообщения необходимо указать message_id',
                                'recommendations' => [
                                    'Укажите message_id сообщения, которое нужно удалить',
                                    'message_id можно получить из ответа предыдущего блока отправки сообщения',
                                    'Примечание: если вы только что отправили сообщение, система автоматически использует его message_id'
                                ],
                            ], 400);
                        }
                    }
                    $result = $telegraph->deleteMessageApi($messageId);
                    
                    // После успешного удаления сообщения обновляем кеш, используя предыдущий message_id из истории
                    if (is_array($result) && ($result['ok'] ?? false)) {
                        $cacheKey = "last_message_id_{$bot->id}_{$request->chat_id}";
                        $historyKey = "message_id_history_{$bot->id}_{$request->chat_id}";
                        $mediaCacheKey = "last_media_message_id_{$bot->id}_{$request->chat_id}";
                        $mediaHistoryKey = "media_message_id_history_{$bot->id}_{$request->chat_id}";
                        
                        // Обновляем кеш, если удаленное сообщение было последним
                        $cachedMessageId = Cache::get($cacheKey);
                        if ($cachedMessageId == $messageId) {
                            // Получаем историю и используем предыдущий message_id
                            $history = Cache::get($historyKey, []);
                            // Удаляем удаленное сообщение из истории
                            $history = array_values(array_filter($history, fn($id) => $id != $messageId));
                            
                            if (!empty($history)) {
                                // Используем предыдущий message_id
                                $previousMessageId = $history[0];
                                Cache::put($cacheKey, $previousMessageId, now()->addHour());
                                Cache::put($historyKey, $history, now()->addHour());
                                Log::info('Updated last message_id from history after deletion', [
                                    'bot_id' => $bot->id,
                                    'chat_id' => $request->chat_id,
                                    'deleted_message_id' => $messageId,
                                    'new_last_message_id' => $previousMessageId,
                                ]);
                            } else {
                                // История пуста, очищаем кеш
                                Cache::forget($cacheKey);
                                Log::info('Cleared last message_id from cache after deletion (no history)', [
                                    'bot_id' => $bot->id,
                                    'chat_id' => $request->chat_id,
                                    'deleted_message_id' => $messageId,
                                ]);
                            }
                        }
                        
                        // То же самое для медиа
                        $cachedMediaMessageId = Cache::get($mediaCacheKey);
                        if ($cachedMediaMessageId == $messageId) {
                            $mediaHistory = Cache::get($mediaHistoryKey, []);
                            $mediaHistory = array_values(array_filter($mediaHistory, fn($id) => $id != $messageId));
                            
                            if (!empty($mediaHistory)) {
                                $previousMediaMessageId = $mediaHistory[0];
                                Cache::put($mediaCacheKey, $previousMediaMessageId, now()->addHour());
                                Cache::put($mediaHistoryKey, $mediaHistory, now()->addHour());
                                Log::info('Updated last media message_id from history after deletion', [
                                    'bot_id' => $bot->id,
                                    'chat_id' => $request->chat_id,
                                    'deleted_message_id' => $messageId,
                                    'new_last_media_message_id' => $previousMediaMessageId,
                                ]);
                            } else {
                                Cache::forget($mediaCacheKey);
                                Log::info('Cleared last media message_id from cache after deletion (no history)', [
                                    'bot_id' => $bot->id,
                                    'chat_id' => $request->chat_id,
                                    'deleted_message_id' => $messageId,
                                ]);
                            }
                        }
                    }
                    break;

                case 'pinChatMessage':
                    // Если message_id не указан, пытаемся использовать последний из кеша или истории
                    $messageId = $methodData['message_id'] ?? null;
                    if (empty($messageId)) {
                        $cacheKey = "last_message_id_{$bot->id}_{$request->chat_id}";
                        $historyKey = "message_id_history_{$bot->id}_{$request->chat_id}";
                        $lastMessageId = Cache::get($cacheKey);
                        
                        if ($lastMessageId !== null) {
                            // Автоматически используем последний message_id
                            $messageId = $lastMessageId;
                            Log::info('Using last message_id from cache for pinChatMessage', [
                                'bot_id' => $bot->id,
                                'chat_id' => $request->chat_id,
                                'message_id' => $messageId,
                            ]);
                        } else {
                            // Если кеш пуст, пытаемся использовать из истории
                            $history = Cache::get($historyKey, []);
                            if (!empty($history)) {
                                $messageId = $history[0];
                                // Обновляем кеш
                                Cache::put($cacheKey, $messageId, now()->addHour());
                                Log::info('Using message_id from history for pinChatMessage', [
                                    'bot_id' => $bot->id,
                                    'chat_id' => $request->chat_id,
                                    'message_id' => $messageId,
                                ]);
                            } else {
                                return response()->json([
                                    'message' => 'Ошибка выполнения метода',
                                    'error' => 'Для закрепления сообщения необходимо указать message_id',
                                    'recommendations' => [
                                        'Укажите message_id сообщения, которое нужно закрепить',
                                        'message_id можно получить из ответа предыдущего блока отправки сообщения',
                                        'Примечание: если вы только что отправили сообщение, система автоматически использует его message_id',
                                        'Рекомендация: выполните блок отправки сообщения перед блоком закрепления'
                                    ],
                                ], 400);
                            }
                        }
                    }
                    try {
                    $result = $telegraph->pinChatMessageApi(
                            $messageId,
                        $methodData['disable_notification'] ?? false
                        );
                    } catch (\Exception $e) {
                        // Если сообщение не найдено, даем более понятное сообщение
                        if (str_contains($e->getMessage(), 'message to pin not found') || str_contains($e->getMessage(), 'not found')) {
                            return response()->json([
                                'message' => 'Ошибка выполнения метода',
                                'error' => 'Сообщение с указанным message_id не найдено или было удалено',
                                'recommendations' => [
                                    'Сообщение могло быть удалено предыдущим блоком deleteMessage',
                                    'Убедитесь, что message_id указан правильно',
                                    'Если message_id не указан, система автоматически использует последнее отправленное сообщение',
                                    'Проверьте, что сообщение существует и было отправлено этим ботом',
                                    'Рекомендация: выполните блок отправки сообщения перед блоком закрепления'
                                ],
                            ], 400);
                        }
                        throw $e;
                    }
                    break;

                case 'unpinChatMessage':
                    // Для unpinChatMessage message_id необязателен (если не указан, открепляется последнее закрепленное сообщение)
                    $messageId = $methodData['message_id'] ?? null;
                    // Не используем кеш для unpinChatMessage, так как message_id необязателен
                    $result = $telegraph->unpinChatMessageApi($messageId);
                    break;

                case 'sendPhoto':
                    $result = $telegraph->photo($methodData['photo'] ?? '')
                        ->caption($methodData['caption'] ?? null)
                        ->parseMode($methodData['parse_mode'] ?? null)
                        ->send();
                    break;

                case 'sendVideo':
                    $result = $telegraph->video($methodData['video'] ?? '')
                        ->caption($methodData['caption'] ?? null)
                        ->parseMode($methodData['parse_mode'] ?? null)
                        ->send();
                    break;

                case 'sendDocument':
                    $result = $telegraph->document($methodData['document'] ?? '')
                        ->caption($methodData['caption'] ?? null)
                        ->parseMode($methodData['parse_mode'] ?? null)
                        ->send();
                    break;

                case 'sendAudio':
                    $result = $telegraph->audio($methodData['audio'] ?? '')
                        ->caption($methodData['caption'] ?? null)
                        ->parseMode($methodData['parse_mode'] ?? null)
                        ->send();
                    break;

                case 'sendVoice':
                    $result = $telegraph->voice($methodData['voice'] ?? '')
                        ->caption($methodData['caption'] ?? null)
                        ->parseMode($methodData['parse_mode'] ?? null)
                        ->send();
                    break;

                case 'sendVideoNote':
                    $result = $telegraph->videoNote($methodData['video_note'] ?? '')->send();
                    break;

                case 'sendAnimation':
                    $result = $telegraph->animation($methodData['animation'] ?? '')
                        ->caption($methodData['caption'] ?? null)
                        ->parseMode($methodData['parse_mode'] ?? null)
                        ->send();
                    break;

                case 'sendSticker':
                    $result = $telegraph->sticker($methodData['sticker'] ?? '')->send();
                    break;

                case 'sendLocation':
                    $result = $telegraph->location(
                        $methodData['latitude'] ?? 0,
                        $methodData['longitude'] ?? 0
                    )->send();
                    break;

                case 'sendMediaGroup':
                    // Обработка группы медиа
                    $media = [];
                    foreach ($methodData['media'] ?? [] as $item) {
                        $mediaItem = [
                            'type' => $item['type'] ?? 'photo',
                            'media' => $item['media'] ?? ''
                        ];
                        if (!empty($item['caption'])) {
                            $mediaItem['caption'] = $item['caption'];
                        }
                        $media[] = $mediaItem;
                    }
                    $result = $telegraph->makeRequest('sendMediaGroup', ['media' => $media]);
                    break;

                case 'sendChatAction':
                    $result = $telegraph->makeRequest('sendChatAction', [
                        'action' => $methodData['action'] ?? 'typing'
                    ]);
                    break;

                case 'question':
                    // Вопрос - это просто сообщение
                    $result = $telegraph->message($methodData['text'] ?? '')
                        ->parseMode($methodData['parse_mode'] ?? null)
                        ->send();
                    break;

                case 'managerChat':
                    // Переключение на менеджера - отправляем сообщение
                    $result = $telegraph->message($methodData['text'] ?? 'Переключение на менеджера...')
                        ->send();
                    break;

                case 'apiRequest':
                    // Прямой API запрос
                    $params = [];
                    if (isset($methodData['params'])) {
                        if (is_string($methodData['params'])) {
                            $decoded = json_decode($methodData['params'], true);
                            $params = $decoded !== null ? $decoded : [];
                        } else {
                            $params = $methodData['params'];
                        }
                    }
                    $result = $telegraph->makeRequest($methodData['method'] ?? 'sendMessage', $params);
                    break;

                case 'apiButtons':
                case 'apiMediaGroup':
                    // Эти методы требуют специальной обработки
                    $result = $telegraph->makeRequest('sendMessage', [
                        'text' => $methodData['text'] ?? 'API метод',
                        'reply_markup' => $methodData['buttons'] ?? []
                    ]);
                    break;

                case 'assistant':
                    // AI Ассистент - отправляем запрос и получаем ответ
                    // Здесь должна быть интеграция с ChatGPT API
                    // Пока просто отправляем сообщение
                    $result = $telegraph->message($methodData['text'] ?? 'AI запрос обрабатывается...')
                        ->send();
                    break;

                default:
                    return response()->json([
                        'message' => 'Неизвестный метод',
                        'error' => "Метод '{$method}' не поддерживается",
                    ], 400);
            }

            // Преобразуем TelegraphResponse в массив для JSON ответа
            $resultArray = [];
            if ($result instanceof \DefStudio\Telegraph\Client\TelegraphResponse) {
                $resultArray = [
                    'ok' => $result->successful(),
                    'result' => $result->json(),
                    'message_id' => $result->telegraphMessageId(),
                ];
            } elseif (is_array($result)) {
                $resultArray = $result;
            } else {
                $resultArray = ['ok' => true, 'result' => $result];
            }
            
            // Для sendMediaGroup результат - это массив сообщений
            $messageId = null;
            if ($method === 'sendMediaGroup' && isset($resultArray['result']) && is_array($resultArray['result'])) {
                // Для sendMediaGroup берем message_id из первого сообщения
                $messageId = $resultArray['result'][0]['message_id'] ?? null;
            } else {
                $messageId = $resultArray['result']['message_id'] ?? $resultArray['message_id'] ?? null;
            }
            
            // Сохраняем последний message_id в кеш для использования в методах редактирования
            // Ключ: last_message_id_{bot_id}_{chat_id}, время жизни: 1 час
            // Также сохраняем историю последних message_id для восстановления после удаления
            if ($messageId !== null && ($resultArray['ok'] ?? false)) {
                $cacheKey = "last_message_id_{$bot->id}_{$request->chat_id}";
                $historyKey = "message_id_history_{$bot->id}_{$request->chat_id}";
                
                // Сохраняем текущий message_id
                Cache::put($cacheKey, $messageId, now()->addHour());
                
                // Сохраняем историю последних 10 message_id
                $history = Cache::get($historyKey, []);
                array_unshift($history, $messageId); // Добавляем в начало
                $history = array_slice($history, 0, 10); // Оставляем только последние 10
                Cache::put($historyKey, $history, now()->addHour());
                
                // Для медиа-сообщений сохраняем отдельный message_id для редактирования caption
                $mediaMethods = ['sendPhoto', 'sendVideo', 'sendDocument', 'sendAudio', 'sendVoice', 'sendAnimation', 'sendVideoNote', 'sendMediaGroup'];
                if (in_array($method, $mediaMethods)) {
                    $mediaCacheKey = "last_media_message_id_{$bot->id}_{$request->chat_id}";
                    $mediaHistoryKey = "media_message_id_history_{$bot->id}_{$request->chat_id}";
                    
                    Cache::put($mediaCacheKey, $messageId, now()->addHour());
                    
                    // Сохраняем историю последних 10 медиа message_id
                    $mediaHistory = Cache::get($mediaHistoryKey, []);
                    array_unshift($mediaHistory, $messageId);
                    $mediaHistory = array_slice($mediaHistory, 0, 10);
                    Cache::put($mediaHistoryKey, $mediaHistory, now()->addHour());
                }
            }
            
            Log::info('Block method executed successfully', [
                'bot_id' => $bot->id,
                'method' => $method,
                'result_ok' => $resultArray['ok'] ?? false,
                'result_message_id' => $messageId,
            ]);
            
            return response()->json([
                'message' => 'Метод успешно выполнен',
                'data' => $resultArray,
            ]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            
            // Логируем ошибку
            Log::error('Block method execution failed', [
                'bot_id' => $bot->id,
                'bot_name' => $bot->name,
                'method' => $method ?? 'unknown',
                'chat_id' => $request->chat_id ?? null,
                'error' => $errorMessage,
                'trace' => $e->getTraceAsString(),
            ]);
            
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
            if (str_contains($errorMessage, 'message_id') || str_contains($errorMessage, 'edit')) {
                $recommendations[] = 'Для редактирования сообщения необходимо указать message_id';
                $recommendations[] = 'Убедитесь, что сообщение существует и было отправлено этим ботом';
            }
            if (str_contains($errorMessage, 'message to pin not found') || str_contains($errorMessage, 'pin')) {
                $recommendations[] = 'Сообщение с указанным message_id не найдено или было удалено';
                $recommendations[] = 'Убедитесь, что message_id указан правильно';
                $recommendations[] = 'Если message_id не указан, система автоматически использует последнее отправленное сообщение';
                $recommendations[] = 'Проверьте, что сообщение существует и было отправлено этим ботом';
            }
            if (str_contains($errorMessage, 'подключения') || str_contains($errorMessage, 'Connection') || str_contains($errorMessage, 'Connection refused')) {
                $recommendations[] = 'Проверьте интернет-соединение';
                $recommendations[] = 'Убедитесь, что сервер имеет доступ к api.telegram.org';
                $recommendations[] = 'Попробуйте выполнить запрос позже - это может быть временная проблема с сетью';
            }
            if (str_contains($errorMessage, 'timeout') || str_contains($errorMessage, 'Timeout')) {
                $recommendations[] = 'Превышено время ожидания ответа от Telegram API';
                $recommendations[] = 'Проверьте скорость интернет-соединения';
                $recommendations[] = 'Попробуйте выполнить запрос позже';
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
            $telegraph->setBot($bot);
            
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

    /**
     * Сохранить блоки диаграммы бота
     */
    public function saveBlocks(Request $request, string $id)
    {
        $bot = Bot::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'blocks' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $bot->update([
            'blocks' => $request->blocks,
        ]);

        return response()->json([
            'message' => 'Блоки успешно сохранены',
            'data' => $bot->fresh(),
        ]);
    }

    /**
     * Получить блоки диаграммы бота
     */
    public function getBlocks(string $id)
    {
        $bot = Bot::findOrFail($id);
        
        return response()->json([
            'data' => [
                'blocks' => $bot->blocks ?? [],
            ],
        ]);
    }
}

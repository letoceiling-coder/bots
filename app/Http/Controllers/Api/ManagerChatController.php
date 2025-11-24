<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ManagerChatMessage;
use App\Models\BotSession;
use App\Models\BotUser;
use App\Models\Bot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManagerChatController extends Controller
{
    /**
     * Получить список диалогов с менеджерами
     */
    public function index(Request $request)
    {
        $query = ManagerChatMessage::select('session_id', 'bot_id', 'user_chat_id', 'manager_telegram_user_id')
            ->selectRaw('MIN(created_at) as first_message_at')
            ->selectRaw('MAX(created_at) as last_message_at')
            ->selectRaw('COUNT(*) as messages_count')
            ->groupBy('session_id', 'bot_id', 'user_chat_id', 'manager_telegram_user_id');

        // Фильтрация по менеджеру
        if ($request->has('manager_id')) {
            $manager = BotUser::findOrFail($request->manager_id);
            $query->where('manager_telegram_user_id', $manager->telegram_user_id);
        }

        // Фильтрация по боту
        if ($request->has('bot_id')) {
            $query->where('bot_id', $request->bot_id);
        }

        // Фильтрация по дате
        if ($request->has('date_from')) {
            $query->havingRaw('MIN(created_at) >= ?', [$request->date_from]);
        }
        if ($request->has('date_to')) {
            $query->havingRaw('MAX(created_at) <= ?', [$request->date_to]);
        }

        // Поиск по пользователю
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $sessionIds = BotSession::where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('chat_id', 'like', "%{$search}%");
            })->pluck('id');
            
            if ($sessionIds->isNotEmpty()) {
                $query->whereIn('session_id', $sessionIds);
            } else {
                // Если не найдено сессий, возвращаем пустой результат
                $query->whereRaw('1 = 0');
            }
        }

        $dialogues = $query->orderBy('last_message_at', 'desc')
            ->paginate($request->get('per_page', 20));

        // Добавляем информацию о сессии и менеджере для каждого диалога
        $dialogues->getCollection()->transform(function ($dialogue) {
            $session = BotSession::with('bot')->find($dialogue->session_id);
            $manager = BotUser::where('telegram_user_id', $dialogue->manager_telegram_user_id)
                ->where('bot_id', $dialogue->bot_id)
                ->first();

            return [
                'session_id' => $dialogue->session_id,
                'bot_id' => $dialogue->bot_id,
                'bot_name' => $session->bot->name ?? null,
                'user_chat_id' => $dialogue->user_chat_id,
                'user_name' => $session ? ($session->first_name . ($session->last_name ? ' ' . $session->last_name : '')) : null,
                'user_username' => $session->username ?? null,
                'manager_telegram_user_id' => $dialogue->manager_telegram_user_id,
                'manager_name' => $manager ? ($manager->first_name . ($manager->last_name ? ' ' . $manager->last_name : '')) : null,
                'manager_username' => $manager->username ?? null,
                'first_message_at' => $dialogue->first_message_at,
                'last_message_at' => $dialogue->last_message_at,
                'messages_count' => $dialogue->messages_count,
            ];
        });

        return response()->json($dialogues);
    }

    /**
     * Получить полный диалог по session_id
     */
    public function show(Request $request, string $sessionId)
    {
        $session = BotSession::with(['bot', 'managerChatMessages' => function ($query) {
            $query->orderBy('created_at', 'asc');
        }])->findOrFail($sessionId);

        // Получаем информацию о менеджере
        $managerTelegramUserId = $session->managerChatMessages->first()->manager_telegram_user_id ?? null;
        $manager = null;
        if ($managerTelegramUserId) {
            $manager = BotUser::where('telegram_user_id', $managerTelegramUserId)
                ->where('bot_id', $session->bot_id)
                ->first();
        }

        // Форматируем сообщения
        $messages = $session->managerChatMessages->map(function ($message) {
            return [
                'id' => $message->id,
                'direction' => $message->direction,
                'message_text' => $message->message_text,
                'message_type' => $message->message_type,
                'telegram_message_id' => $message->telegram_message_id,
                'telegram_data' => $message->telegram_data,
                'created_at' => $message->created_at,
            ];
        });

        return response()->json([
            'session' => [
                'id' => $session->id,
                'bot_id' => $session->bot_id,
                'bot_name' => $session->bot->name,
                'bot_token' => $session->bot->token, // Для получения файлов
                'chat_id' => $session->chat_id,
                'user_name' => $session->first_name . ($session->last_name ? ' ' . $session->last_name : ''),
                'user_username' => $session->username,
                'started_at' => $session->started_at,
                'last_activity_at' => $session->last_activity_at,
            ],
            'manager' => $manager ? [
                'id' => $manager->id,
                'telegram_user_id' => $manager->telegram_user_id,
                'name' => $manager->first_name . ($manager->last_name ? ' ' . $manager->last_name : ''),
                'username' => $manager->username,
            ] : null,
            'messages' => $messages,
            'messages_count' => $messages->count(),
        ]);
    }

    /**
     * Получить список менеджеров для фильтрации
     */
    public function getManagers(Request $request)
    {
        $query = BotUser::where('role', 'manager');

        if ($request->has('bot_id')) {
            $query->where('bot_id', $request->bot_id);
        }

        $managers = $query->select('id', 'bot_id', 'telegram_user_id', 'first_name', 'last_name', 'username')
            ->with('bot:id,name')
            ->get()
            ->map(function ($manager) {
                return [
                    'id' => $manager->id,
                    'bot_id' => $manager->bot_id,
                    'bot_name' => $manager->bot->name ?? null,
                    'telegram_user_id' => $manager->telegram_user_id,
                    'name' => $manager->first_name . ($manager->last_name ? ' ' . $manager->last_name : ''),
                    'username' => $manager->username,
                ];
            });

        return response()->json(['data' => $managers]);
    }

    /**
     * Получить файл из Telegram (прокси для безопасности токена)
     * 
     * @param Request $request
     * @param string $fileId
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function getFile(Request $request, string $fileId)
    {
        // Обработка OPTIONS запроса для CORS
        if ($request->method() === 'OPTIONS') {
            return response('', 200)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type');
        }
        
        // Логируем входящий запрос ДО любой обработки
        \Log::info('getFile: incoming request', [
            'raw_file_id' => $fileId,
            'raw_file_id_length' => strlen($fileId),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'query_params' => $request->query(),
        ]);
        
        // Декодируем file_id, так как он может быть закодирован в URL
        // Также проверяем, не был ли он передан как query параметр
        if (empty($fileId) && $request->has('file_id')) {
            $fileId = $request->get('file_id');
        }
        
        // Декодируем только если file_id не пустой
        if (!empty($fileId)) {
            $fileId = urldecode($fileId);
        }
        
        \Log::info('getFile: after decoding', [
            'decoded_file_id' => $fileId,
            'decoded_file_id_length' => strlen($fileId),
            'bot_id' => $request->get('bot_id'),
            'session_id' => $request->get('session_id'),
            'redirect' => $request->get('redirect'),
        ]);
        
        try {
            $botId = $request->get('bot_id');
            $sessionId = $request->get('session_id');

            if (!$botId && !$sessionId) {
                \Log::warning('getFile: missing bot_id and session_id', [
                    'file_id' => $fileId,
                ]);
                return response()->json([
                    'message' => 'bot_id или session_id обязательны',
                ], 400);
            }

            // Получаем бота
            $bot = null;
            if ($sessionId) {
                try {
                    $session = BotSession::with('bot')->findOrFail($sessionId);
                    $bot = $session->bot;
                } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                    \Log::warning('getFile: session not found', [
                        'session_id' => $sessionId,
                        'file_id' => $fileId,
                    ]);
                    return response()->json([
                        'message' => 'Сессия не найдена',
                    ], 404);
                }
            } else {
                try {
                    $bot = Bot::findOrFail($botId);
                } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                    \Log::warning('getFile: bot not found', [
                        'bot_id' => $botId,
                        'file_id' => $fileId,
                    ]);
                    return response()->json([
                        'message' => 'Бот не найден',
                    ], 404);
                }
            }

            if (!$bot || !$bot->token) {
                \Log::error('getFile: bot or token missing', [
                    'bot_id' => $botId,
                    'session_id' => $sessionId,
                    'file_id' => $fileId,
                ]);
                return response()->json([
                    'message' => 'Бот или токен не найден',
                ], 500);
            }

            // Используем ExtendedTelegraph для получения файла
            $telegraph = new \App\Services\ExtendedTelegraph();
            $telegraph->setBot($bot);
            
            \Log::info('getFile: calling Telegram getFile API', [
                'file_id' => $fileId,
                'bot_id' => $bot->id,
                'file_id_length' => strlen($fileId),
            ]);
            
            // Получаем информацию о файле
            try {
                $fileInfo = $telegraph->makeRequest('getFile', [
                    'file_id' => $fileId,
                ]);
            } catch (\Exception $e) {
                \Log::error('getFile: exception calling makeRequest', [
                    'file_id' => $fileId,
                    'bot_id' => $bot->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return response()->json([
                    'message' => 'Ошибка при запросе к Telegram API',
                    'error' => $e->getMessage(),
                ], 500);
            }

            \Log::info('getFile: Telegram API response', [
                'file_id' => $fileId,
                'ok' => $fileInfo['ok'] ?? false,
                'result' => $fileInfo['result'] ?? null,
            ]);

            if (!isset($fileInfo['ok']) || !$fileInfo['ok']) {
                \Log::error('getFile: Telegram API error', [
                    'file_id' => $fileId,
                    'bot_id' => $bot->id,
                    'error' => $fileInfo['description'] ?? 'Unknown error',
                    'error_code' => $fileInfo['error_code'] ?? null,
                    'full_response' => $fileInfo,
                ]);
                return response()->json([
                    'message' => 'Ошибка получения файла из Telegram',
                    'error' => $fileInfo['description'] ?? 'Unknown error',
                ], 500);
            }

            $filePath = $fileInfo['result']['file_path'] ?? null;
            if (!$filePath) {
                \Log::error('getFile: file_path not found', [
                    'file_id' => $fileId,
                    'bot_id' => $bot->id,
                    'file_info' => $fileInfo,
                ]);
                return response()->json([
                    'message' => 'Путь к файлу не найден',
                ], 404);
            }

            // Формируем URL для файла
            $fileUrl = "https://api.telegram.org/file/bot{$bot->token}/{$filePath}";

            // Перенаправляем на файл или возвращаем URL
            if ($request->get('redirect', false) || $request->get('redirect') === '1') {
                \Log::info('getFile: fetching file from Telegram', [
                    'file_url' => $fileUrl,
                    'file_id' => $fileId,
                    'file_path' => $filePath,
                ]);
                
                // Используем Http::get для получения файла и передачи его клиенту
                try {
                    $fileContent = \Illuminate\Support\Facades\Http::timeout(30)
                        ->retry(2, 1000)
                        ->get($fileUrl);
                    
                    \Log::info('getFile: HTTP response received', [
                        'status' => $fileContent->status(),
                        'successful' => $fileContent->successful(),
                        'headers' => $fileContent->headers(),
                        'body_length' => strlen($fileContent->body()),
                    ]);
                    
                    if ($fileContent->successful()) {
                        // Определяем Content-Type на основе расширения файла
                        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
                        $contentTypeMap = [
                            'jpg' => 'image/jpeg',
                            'jpeg' => 'image/jpeg',
                            'png' => 'image/png',
                            'gif' => 'image/gif',
                            'webp' => 'image/webp',
                            'mp4' => 'video/mp4',
                            'mp3' => 'audio/mpeg',
                            'ogg' => 'audio/ogg',
                            'oga' => 'audio/ogg',
                            'pdf' => 'application/pdf',
                            'zip' => 'application/zip',
                            'm4a' => 'audio/mp4',
                        ];
                        $contentType = $contentTypeMap[strtolower($extension)] ?? $fileContent->header('Content-Type') ?? 'application/octet-stream';
                        
                        \Log::info('getFile: file fetched successfully, returning response', [
                            'file_id' => $fileId,
                            'file_path' => $filePath,
                            'content_type' => $contentType,
                            'size' => strlen($fileContent->body()),
                        ]);
                        
                        return response($fileContent->body(), 200)
                            ->header('Content-Type', $contentType)
                            ->header('Content-Disposition', 'inline')
                            ->header('Cache-Control', 'public, max-age=3600')
                            ->header('Access-Control-Allow-Origin', '*')
                            ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
                            ->header('Access-Control-Allow-Headers', 'Content-Type');
                    } else {
                        \Log::error('getFile: failed to fetch file content from Telegram', [
                            'file_url' => $fileUrl,
                            'file_id' => $fileId,
                            'status' => $fileContent->status(),
                            'response_body' => substr($fileContent->body(), 0, 1000), // Первые 1000 символов
                            'response_headers' => $fileContent->headers(),
                        ]);
                        
                        // Возвращаем ошибку вместо редиректа
                        return response()->json([
                            'message' => 'Ошибка получения файла из Telegram',
                            'error' => 'HTTP ' . $fileContent->status(),
                            'file_id' => $fileId,
                        ], $fileContent->status());
                    }
                } catch (\Exception $e) {
                    \Log::error('getFile: exception while fetching file from Telegram', [
                        'file_url' => $fileUrl,
                        'file_id' => $fileId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    
                    // Возвращаем ошибку вместо редиректа
                    return response()->json([
                        'message' => 'Ошибка получения файла',
                        'error' => $e->getMessage(),
                        'file_id' => $fileId,
                    ], 500);
                }
            }

            return response()->json([
                'url' => $fileUrl,
                'file_path' => $filePath,
                'file_info' => $fileInfo['result'],
            ]);
        } catch (\Exception $e) {
            \Log::error('getFile: unexpected error', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Ошибка получения файла',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}


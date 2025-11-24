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
}


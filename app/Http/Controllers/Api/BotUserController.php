<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\BotUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BotUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = BotUser::with('bot');

        // Фильтр по боту
        if ($request->has('bot_id')) {
            $query->where('bot_id', $request->bot_id);
        }

        // Фильтр по роли
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Поиск по имени, username или telegram_user_id
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('telegram_user_id', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bot_id' => 'required|exists:telegram_bots,id',
            'telegram_user_id' => 'required|string',
            'chat_id' => 'required|string',
            'username' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'role' => 'required|string|in:admin,manager,user',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Проверяем, не существует ли уже такой пользователь для этого бота
        $existingUser = BotUser::where('bot_id', $request->bot_id)
            ->where('telegram_user_id', $request->telegram_user_id)
            ->first();

        if ($existingUser) {
            return response()->json([
                'message' => 'Пользователь уже существует для этого бота',
                'data' => $existingUser->load('bot'),
            ], 409);
        }

        $botUser = BotUser::create([
            'bot_id' => $request->bot_id,
            'telegram_user_id' => $request->telegram_user_id,
            'chat_id' => $request->chat_id,
            'username' => $request->username,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'role' => $request->role ?? 'user',
        ]);

        return response()->json([
            'message' => 'Пользователь бота успешно создан',
            'data' => $botUser->load('bot'),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $botUser = BotUser::with('bot')->findOrFail($id);
        
        return response()->json([
            'data' => $botUser,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $botUser = BotUser::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'username' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'role' => 'required|string|in:admin,manager,user',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $botUser->username = $request->username ?? $botUser->username;
        $botUser->first_name = $request->first_name ?? $botUser->first_name;
        $botUser->last_name = $request->last_name ?? $botUser->last_name;
        $botUser->role = $request->role;
        
        if ($request->has('metadata')) {
            $botUser->metadata = $request->metadata;
        }
        
        $botUser->save();

        return response()->json([
            'message' => 'Пользователь бота успешно обновлен',
            'data' => $botUser->fresh()->load('bot'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $botUser = BotUser::findOrFail($id);
        $botUser->delete();

        return response()->json([
            'message' => 'Пользователь бота успешно удален',
        ]);
    }

    /**
     * Получить пользователей для конкретного бота
     */
    public function getBotUsers(string $botId)
    {
        $bot = Bot::findOrFail($botId);
        $users = BotUser::where('bot_id', $botId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $users,
            'bot' => $bot,
        ]);
    }
}

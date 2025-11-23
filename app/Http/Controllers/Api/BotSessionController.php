<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BotSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BotSessionController extends Controller
{
    /**
     * Получить список сессий с фильтрами
     */
    public function index(Request $request)
    {
        Log::info('Fetching bot sessions', [
            'filters' => $request->all(),
        ]);

        $query = BotSession::with(['bot', 'steps', 'files', 'data']);

        // Фильтр по боту
        if ($request->has('bot_id')) {
            $query->where('bot_id', $request->bot_id);
        }

        // Фильтр по статусу
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Фильтр по дате начала
        if ($request->has('started_from')) {
            $query->where('started_at', '>=', $request->started_from);
        }
        if ($request->has('started_to')) {
            $query->where('started_at', '<=', $request->started_to);
        }

        // Поиск по chat_id или username
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('chat_id', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        // Сортировка
        $sortBy = $request->get('sort_by', 'last_activity_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        // Пагинация
        $perPage = $request->get('per_page', 15);
        $sessions = $query->paginate($perPage);

        Log::info('Bot sessions fetched', [
            'total' => $sessions->total(),
            'per_page' => $perPage,
        ]);

        return response()->json([
            'data' => $sessions->items(),
            'pagination' => [
                'total' => $sessions->total(),
                'per_page' => $sessions->perPage(),
                'current_page' => $sessions->currentPage(),
                'last_page' => $sessions->lastPage(),
            ],
        ]);
    }

    /**
     * Получить детальную информацию о сессии
     */
    public function show(string $id)
    {
        Log::info('Fetching bot session details', [
            'session_id' => $id,
        ]);

        $session = BotSession::with([
            'bot',
            'steps' => function ($query) {
                $query->orderBy('step_order');
            },
            'files',
            'data',
        ])->findOrFail($id);

        Log::info('Bot session details fetched', [
            'session_id' => $session->id,
            'steps_count' => $session->steps->count(),
            'files_count' => $session->files->count(),
            'data_count' => $session->data->count(),
        ]);

        return response()->json([
            'data' => $session,
        ]);
    }

    /**
     * Получить статистику по ботам
     */
    public function statistics(Request $request)
    {
        $botId = $request->get('bot_id');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        Log::info('Fetching bot sessions statistics', [
            'bot_id' => $botId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]);

        $query = BotSession::query();

        if ($botId) {
            $query->where('bot_id', $botId);
        }

        if ($dateFrom) {
            $query->where('started_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('started_at', '<=', $dateTo);
        }

        $stats = [
            'total_sessions' => $query->count(),
            'active_sessions' => (clone $query)->where('status', 'active')->count(),
            'completed_sessions' => (clone $query)->where('status', 'completed')->count(),
            'abandoned_sessions' => (clone $query)->where('status', 'abandoned')->count(),
            'manager_chat_sessions' => (clone $query)->where('status', 'manager_chat')->count(),
            'unique_users' => (clone $query)->distinct('chat_id')->count('chat_id'),
        ];

        return response()->json([
            'data' => $stats,
        ]);
    }
}

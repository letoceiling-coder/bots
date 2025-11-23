<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use Illuminate\Http\Request;
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
}

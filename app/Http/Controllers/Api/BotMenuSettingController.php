<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Models\BotMenuSetting;
use App\Services\ExtendedTelegraph;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BotMenuSettingController extends Controller
{
    /**
     * Получить настройки кнопки меню для всех ботов или конкретного бота
     */
    public function index(Request $request)
    {
        $query = BotMenuSetting::with('bot');

        if ($request->has('bot_id')) {
            $query->where('bot_id', $request->bot_id);
        }

        $settings = $query->get();

        // Если настройки нет для бота, создаем дефолтную
        if ($request->has('bot_id')) {
            $bot = Bot::find($request->bot_id);
            if ($bot && !$settings->where('bot_id', $request->bot_id)->first()) {
                $setting = BotMenuSetting::getOrCreateForBot($request->bot_id);
                $setting->load('bot');
                return response()->json(['data' => [$setting]]);
            }
        }

        return response()->json(['data' => $settings]);
    }

    /**
     * Получить настройки кнопки меню для конкретного бота
     */
    public function show(string $botId)
    {
        $setting = BotMenuSetting::with('bot')->where('bot_id', $botId)->first();

        if (!$setting) {
            // Создаем дефолтную настройку
            $setting = BotMenuSetting::getOrCreateForBot($botId);
            $setting->load('bot');
        }

        return response()->json(['data' => $setting]);
    }

    /**
     * Обновить настройки кнопки меню для бота
     */
    public function update(Request $request, string $botId)
    {
        $validator = Validator::make($request->all(), [
            'menu_enabled' => 'required|boolean',
            'menu_type' => 'nullable|in:commands,web_app,default',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $bot = Bot::findOrFail($botId);

        // Получаем или создаем настройку
        $setting = BotMenuSetting::getOrCreateForBot($botId);

        // Обновляем настройки
        $setting->menu_enabled = $request->menu_enabled;
        if ($request->has('menu_type')) {
            $setting->menu_type = $request->menu_type;
        }
        $setting->save();

        // Если меню включено, устанавливаем кнопку меню в Telegram
        if ($setting->menu_enabled) {
            try {
                $telegraph = new ExtendedTelegraph();
                $telegraph->setBot($bot);

                $menuButton = ['type' => $setting->menu_type];
                $result = $telegraph->setChatMenuButton($menuButton, null);

                if (isset($result['ok']) && $result['ok'] === true) {
                    Log::info('Bot menu button set via settings', [
                        'bot_id' => $botId,
                        'menu_type' => $setting->menu_type,
                    ]);
                } else {
                    Log::warning('Failed to set bot menu button via settings', [
                        'bot_id' => $botId,
                        'result' => $result,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error setting bot menu button via settings', [
                    'bot_id' => $botId,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            // Если меню отключено, устанавливаем тип 'default' (скрывает кнопку)
            try {
                $telegraph = new ExtendedTelegraph();
                $telegraph->setBot($bot);

                $result = $telegraph->setChatMenuButton(['type' => 'default'], null);

                if (isset($result['ok']) && $result['ok'] === true) {
                    Log::info('Bot menu button disabled via settings', [
                        'bot_id' => $botId,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Error disabling bot menu button via settings', [
                    'bot_id' => $botId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $setting->load('bot');

        return response()->json([
            'message' => 'Настройки кнопки меню успешно обновлены',
            'data' => $setting,
        ]);
    }
}


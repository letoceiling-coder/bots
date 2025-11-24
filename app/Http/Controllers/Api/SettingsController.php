<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    /**
     * Получить все настройки (сгруппированные)
     */
    public function index(Request $request)
    {
        $group = $request->get('group');
        $publicOnly = $request->get('public_only', false);

        $query = Setting::query();

        if ($group) {
            $query->where('group', $group);
        }

        if ($publicOnly) {
            $query->where('is_public', true);
        }

        $settings = $query->orderBy('group')->orderBy('key')->get();

        // Группируем по группам
        $grouped = $settings->groupBy('group')->map(function ($items) {
            return $items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'key' => $item->key,
                    'value' => $item->value,
                    'type' => $item->type,
                    'group' => $item->group,
                    'description' => $item->description,
                    'is_public' => $item->is_public,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at,
                ];
            })->values();
        });

        return response()->json([
            'data' => $grouped,
            'groups' => $settings->pluck('group')->unique()->values(),
        ]);
    }

    /**
     * Получить настройки группы
     */
    public function getGroup(string $group)
    {
        $settings = Setting::where('group', $group)
            ->orderBy('key')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'key' => $item->key,
                    'value' => $item->value,
                    'type' => $item->type,
                    'group' => $item->group,
                    'description' => $item->description,
                    'is_public' => $item->is_public,
                ];
            });

        return response()->json([
            'data' => $settings,
        ]);
    }

    /**
     * Получить настройку по ключу
     */
    public function getByKey(string $key)
    {
        $setting = Setting::where('key', $key)->first();

        if (!$setting) {
            return response()->json([
                'message' => 'Настройка не найдена',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $setting->id,
                'key' => $setting->key,
                'value' => $setting->value,
                'type' => $setting->type,
                'group' => $setting->group,
                'description' => $setting->description,
                'is_public' => $setting->is_public,
            ],
        ]);
    }

    /**
     * Создать или обновить настройку
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|max:255',
            'value' => 'nullable',
            'type' => 'nullable|string|in:string,number,integer,float,boolean,json',
            'group' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_public' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $setting = Setting::updateOrCreate(
            ['key' => $request->key],
            [
                'value' => $request->value,
                'type' => $request->type ?? 'string',
                'group' => $request->group ?? 'general',
                'description' => $request->description,
                'is_public' => $request->is_public ?? false,
            ]
        );

        return response()->json([
            'message' => 'Настройка сохранена',
            'data' => [
                'id' => $setting->id,
                'key' => $setting->key,
                'value' => $setting->value,
                'type' => $setting->type,
                'group' => $setting->group,
                'description' => $setting->description,
                'is_public' => $setting->is_public,
            ],
        ], 201);
    }

    /**
     * Обновить настройку
     */
    public function update(Request $request, string $id)
    {
        $setting = Setting::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'value' => 'nullable',
            'type' => 'nullable|string|in:string,number,integer,float,boolean,json',
            'group' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_public' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->has('value')) {
            $setting->value = $request->value;
        }
        if ($request->has('type')) {
            $setting->type = $request->type;
        }
        if ($request->has('group')) {
            $setting->group = $request->group;
        }
        if ($request->has('description')) {
            $setting->description = $request->description;
        }
        if ($request->has('is_public')) {
            $setting->is_public = $request->is_public;
        }

        $setting->save();

        return response()->json([
            'message' => 'Настройка обновлена',
            'data' => [
                'id' => $setting->id,
                'key' => $setting->key,
                'value' => $setting->value,
                'type' => $setting->type,
                'group' => $setting->group,
                'description' => $setting->description,
                'is_public' => $setting->is_public,
            ],
        ]);
    }

    /**
     * Массовое обновление настроек
     */
    public function bulkUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $updated = [];
        foreach ($request->settings as $settingData) {
            $setting = Setting::where('key', $settingData['key'])->first();
            if ($setting && isset($settingData['value'])) {
                $setting->value = $settingData['value'];
                $setting->save();
                $updated[] = $setting;
            }
        }

        return response()->json([
            'message' => 'Настройки обновлены',
            'data' => collect($updated)->map(function ($item) {
                return [
                    'id' => $item->id,
                    'key' => $item->key,
                    'value' => $item->value,
                    'type' => $item->type,
                    'group' => $item->group,
                ];
            }),
        ]);
    }

    /**
     * Удалить настройку
     */
    public function destroy(string $id)
    {
        $setting = Setting::findOrFail($id);
        $setting->delete();

        return response()->json([
            'message' => 'Настройка удалена',
        ]);
    }

    /**
     * Получить настройки методов блоков
     */
    public function getBlockMethods()
    {
        $settings = Setting::where('group', 'block_methods')
            ->where('key', 'like', 'block_method_%')
            ->get()
            ->mapWithKeys(function ($setting) {
                // Извлекаем имя метода из ключа (block_method_sendMessage -> sendMessage)
                $methodName = str_replace('block_method_', '', $setting->key);
                return [$methodName => (bool)$setting->value];
            });

        return response()->json([
            'data' => $settings,
        ]);
    }

    /**
     * Обновить настройки методов блоков
     */
    public function updateBlockMethods(Request $request)
    {
        \Log::info('Updating block methods settings', [
            'request_data' => $request->all(),
            'methods_count' => count($request->methods ?? []),
        ]);

        $validator = Validator::make($request->all(), [
            'methods' => 'required|array',
            'methods.*' => 'boolean',
        ]);

        if ($validator->fails()) {
            \Log::error('Validation failed for block methods update', [
                'errors' => $validator->errors(),
            ]);
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $updated = [];
        $errors = [];
        
        foreach ($request->methods as $methodName => $enabled) {
            try {
                $key = 'block_method_' . $methodName;
                $setting = Setting::where('key', $key)->first();
                
                if ($setting) {
                    $setting->value = $enabled ? '1' : '0';
                    $setting->save();
                    $updated[] = $methodName;
                } else {
                    // Создаем новую настройку, если её нет
                    Setting::create([
                        'key' => $key,
                        'value' => $enabled ? '1' : '0',
                        'type' => 'boolean',
                        'group' => 'block_methods',
                        'description' => 'Настройка метода блока: ' . $methodName,
                    ]);
                    $updated[] = $methodName;
                }
            } catch (\Exception $e) {
                \Log::error('Error updating block method setting', [
                    'method' => $methodName,
                    'error' => $e->getMessage(),
                ]);
                $errors[] = $methodName;
            }
        }

        \Log::info('Block methods settings updated', [
            'updated_count' => count($updated),
            'errors_count' => count($errors),
        ]);

        return response()->json([
            'message' => 'Настройки методов блоков обновлены',
            'data' => [
                'updated' => $updated,
                'count' => count($updated),
                'errors' => $errors,
            ],
        ]);
    }
}

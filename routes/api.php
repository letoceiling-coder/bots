<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminMenuController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\BotController;
use App\Http\Controllers\Api\BotSessionController;
use App\Http\Controllers\Api\BotUserController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\TelegramWebhookController;
use App\Http\Controllers\Api\v1\FolderController;
use App\Http\Controllers\Api\v1\MediaController;
use App\Http\Controllers\DeployController;
use Illuminate\Support\Facades\Route;

// Публичные роуты
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Роут для обновления проекта (защищен секретным ключом)
Route::post('/deploy', [DeployController::class, 'deploy'])->middleware('throttle:10,1');
Route::get('/deploy/status', [DeployController::class, 'status']);

// Webhook для Telegram ботов (публичный, без авторизации)
// GET - для проверки статуса, POST - для получения обновлений от Telegram
Route::match(['GET', 'POST'], '/telegram/webhook/{bot_id}', [TelegramWebhookController::class, 'handle'])
    ->name('telegram.webhook')
    ->middleware('throttle:60,1');

// Защищённые роуты
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    
    // Меню
    Route::get('/admin/menu', [AdminMenuController::class, 'index']);
    
    // Уведомления
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/all', [NotificationController::class, 'all']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    
    // Media API (v1)
    Route::prefix('v1')->group(function () {
        // Folders
        Route::get('folders/tree/all', [FolderController::class, 'tree'])->name('folders.tree');
        Route::post('folders/update-positions', [FolderController::class, 'updatePositions'])->name('folders.update-positions');
        Route::post('folders/{id}/restore', [FolderController::class, 'restore'])->name('folders.restore');
        Route::apiResource('folders', FolderController::class);
        
        // Media
        Route::post('media/{id}/restore', [MediaController::class, 'restore'])->name('media.restore');
        Route::delete('media/trash/empty', [MediaController::class, 'emptyTrash'])->name('media.trash.empty');
        Route::apiResource('media', MediaController::class);
        
        // Admin only routes (Roles and Users management)
        Route::middleware('admin')->group(function () {
            Route::apiResource('roles', RoleController::class);
            Route::apiResource('users', UserController::class);
            Route::apiResource('bots', BotController::class);
            Route::get('bots/{id}/info', [BotController::class, 'getBotInfo']);
            Route::get('bots/{id}/updates', [BotController::class, 'getBotUpdates']);
            Route::post('bots/{id}/send-message', [BotController::class, 'sendTestMessage']);
            Route::post('bots/{id}/execute-block-method', [BotController::class, 'executeBlockMethod']);
            Route::post('bots/{id}/save-blocks', [BotController::class, 'saveBlocks']);
            Route::get('bots/{id}/blocks', [BotController::class, 'getBlocks']);
            Route::get('bots/{id}/commands', [BotController::class, 'getBotCommands']);
            Route::post('bots/{id}/commands', [BotController::class, 'setBotCommands']);
            
            // Bot Sessions
            Route::get('bot-sessions', [BotSessionController::class, 'index']);
            Route::get('bot-sessions/statistics', [BotSessionController::class, 'statistics']);
            Route::get('bot-sessions/{id}', [BotSessionController::class, 'show']);
            
            // Bot Users
            Route::get('bot-users', [BotUserController::class, 'index']);
            Route::get('bots/{botId}/users', [BotUserController::class, 'getBotUsers']);
            Route::apiResource('bot-users', BotUserController::class)->except(['index']);

            // Settings
            Route::get('settings', [SettingsController::class, 'index']);
            Route::get('settings/group/{group}', [SettingsController::class, 'getGroup']);
            Route::get('settings/key/{key}', [SettingsController::class, 'getByKey']);
            Route::post('settings', [SettingsController::class, 'store']);
            Route::post('settings/bulk-update', [SettingsController::class, 'bulkUpdate']);
            Route::put('settings/{id}', [SettingsController::class, 'update']);
            Route::delete('settings/{id}', [SettingsController::class, 'destroy']);
            
            // Block Methods Settings
            Route::get('settings/block-methods', [SettingsController::class, 'getBlockMethods']);
            Route::post('settings/block-methods', [SettingsController::class, 'updateBlockMethods']);
        });
    });
});


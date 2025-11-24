<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\BotSession;
use App\Models\BotSessionStep;
use App\Models\BotSessionFile;
use App\Models\BotSessionData;
use App\Models\BotUser;
use Illuminate\Support\Facades\Log;

class BotSessionService
{
    /**
     * Получить или создать сессию пользователя
     */
    public function getOrCreateSession(Bot $bot, string $chatId, array $userData = []): BotSession
    {
        Log::info('Getting or creating bot session', [
            'bot_id' => $bot->id,
            'chat_id' => $chatId,
            'user_data' => $userData,
        ]);

        // Ищем сессию независимо от статуса (может быть active или manager_chat)
        $session = BotSession::where('bot_id', $bot->id)
            ->where('chat_id', $chatId)
            ->whereIn('status', ['active', 'manager_chat'])
            ->latest('last_activity_at')
            ->first();

        if (!$session) {
            Log::info('Creating new bot session', [
                'bot_id' => $bot->id,
                'chat_id' => $chatId,
            ]);

            $session = BotSession::create([
                'bot_id' => $bot->id,
                'chat_id' => $chatId,
                'telegram_user_id' => $userData['id'] ?? null,
                'username' => $userData['username'] ?? null,
                'first_name' => $userData['first_name'] ?? null,
                'last_name' => $userData['last_name'] ?? null,
                'status' => 'active',
                'started_at' => now(),
                'last_activity_at' => now(),
            ]);

            Log::info('Bot session created', [
                'session_id' => $session->id,
                'bot_id' => $bot->id,
                'chat_id' => $chatId,
                'status' => $session->status,
            ]);
        } else {
            // Обновляем данные пользователя, если изменились
            // НО НЕ меняем статус, если он уже установлен (например, manager_chat)
            $updateData = [
                'telegram_user_id' => $userData['id'] ?? $session->telegram_user_id,
                'username' => $userData['username'] ?? $session->username,
                'first_name' => $userData['first_name'] ?? $session->first_name,
                'last_name' => $userData['last_name'] ?? $session->last_name,
            ];
            
            $session->update($updateData);
            $session->touchActivity();

            Log::debug('Using existing bot session', [
                'session_id' => $session->id,
                'bot_id' => $bot->id,
                'chat_id' => $chatId,
                'status' => $session->status,
            ]);
        }

        // Автоматически создаем или обновляем пользователя бота
        $this->syncBotUser($bot, $chatId, $userData);

        return $session;
    }

    /**
     * Создать шаг сессии
     */
    public function createStep(
        BotSession $session,
        ?string $blockId,
        ?string $blockLabel,
        ?string $method,
        ?string $inputType = null,
        ?string $userInput = null,
        ?string $botResponse = null,
        ?array $botResponseData = null
    ): BotSessionStep {
        $stepOrder = $session->steps()->count() + 1;

        Log::info('Creating bot session step', [
            'session_id' => $session->id,
            'block_id' => $blockId,
            'block_label' => $blockLabel,
            'method' => $method,
            'input_type' => $inputType,
            'step_order' => $stepOrder,
        ]);

        $step = BotSessionStep::create([
            'session_id' => $session->id,
            'block_id' => $blockId,
            'block_label' => $blockLabel,
            'method' => $method,
            'input_type' => $inputType,
            'user_input' => $userInput,
            'bot_response' => $botResponse,
            'bot_response_data' => $botResponseData,
            'step_order' => $stepOrder,
            'timestamp' => now(),
        ]);

        Log::info('Bot session step created', [
            'step_id' => $step->id,
            'session_id' => $session->id,
            'step_order' => $stepOrder,
        ]);

        return $step;
    }

    /**
     * Сохранить данные сессии
     */
    public function saveSessionData(BotSession $session, string $key, ?string $value, ?string $blockId = null): void
    {
        Log::debug('Saving session data', [
            'session_id' => $session->id,
            'key' => $key,
            'block_id' => $blockId,
            'value_length' => strlen($value ?? ''),
        ]);

        $session->setDataValue($key, $value, $blockId);
    }

    /**
     * Обновить текущий блок сессии
     */
    public function updateCurrentBlock(BotSession $session, ?string $blockId): void
    {
        Log::info('Updating current block', [
            'session_id' => $session->id,
            'old_block_id' => $session->current_block_id,
            'new_block_id' => $blockId,
        ]);

        $session->update([
            'current_block_id' => $blockId,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Сохранить файл сессии
     */
    public function saveSessionFile(
        BotSession $session,
        ?BotSessionStep $step,
        array $fileData
    ): BotSessionFile {
        Log::info('Saving session file', [
            'session_id' => $session->id,
            'step_id' => $step?->id,
            'file_type' => $fileData['file_type'] ?? null,
            'file_name' => $fileData['file_name'] ?? null,
        ]);

        $file = BotSessionFile::create([
            'session_id' => $session->id,
            'step_id' => $step?->id,
            'telegram_file_id' => $fileData['telegram_file_id'] ?? null,
            'file_type' => $fileData['file_type'] ?? null,
            'file_name' => $fileData['file_name'] ?? null,
            'mime_type' => $fileData['mime_type'] ?? null,
            'file_size' => $fileData['file_size'] ?? null,
            'local_path' => $fileData['local_path'] ?? null,
            'downloaded_at' => isset($fileData['local_path']) ? now() : null,
            'metadata' => $fileData['metadata'] ?? null,
        ]);

        Log::info('Session file saved', [
            'file_id' => $file->id,
            'session_id' => $session->id,
            'local_path' => $file->local_path,
        ]);

        return $file;
    }

    /**
     * Синхронизировать пользователя бота (создать или обновить)
     */
    protected function syncBotUser(Bot $bot, string $chatId, array $userData = []): void
    {
        // Используем user_id из userData, если он есть, иначе используем chat_id
        // В Telegram user_id и chat_id могут совпадать для личных чатов
        $telegramUserId = isset($userData['id']) ? (string)$userData['id'] : $chatId;

        if (empty($telegramUserId)) {
            Log::warning('Cannot sync bot user: telegram_user_id is empty', [
                'bot_id' => $bot->id,
                'chat_id' => $chatId,
                'user_data' => $userData,
            ]);
            return;
        }

        try {
            $botUser = BotUser::where('bot_id', $bot->id)
                ->where('telegram_user_id', $telegramUserId)
                ->first();

            if ($botUser) {
                // Обновляем существующего пользователя (но не меняем роль, если она была установлена вручную)
                $updateData = [
                    'chat_id' => $chatId,
                ];

                // Обновляем username, если он изменился
                if (isset($userData['username'])) {
                    $updateData['username'] = $userData['username'];
                }

                // Обновляем имя, если оно изменилось
                if (isset($userData['first_name'])) {
                    $updateData['first_name'] = $userData['first_name'];
                }

                // Обновляем фамилию, если она изменилась
                if (isset($userData['last_name'])) {
                    $updateData['last_name'] = $userData['last_name'];
                }

                $botUser->update($updateData);

                Log::debug('Bot user updated', [
                    'bot_user_id' => $botUser->id,
                    'bot_id' => $bot->id,
                    'telegram_user_id' => $telegramUserId,
                    'role' => $botUser->role,
                ]);
            } else {
                // Создаем нового пользователя с ролью "user" по умолчанию
                $botUser = BotUser::create([
                    'bot_id' => $bot->id,
                    'telegram_user_id' => $telegramUserId,
                    'chat_id' => $chatId,
                    'username' => $userData['username'] ?? null,
                    'first_name' => $userData['first_name'] ?? null,
                    'last_name' => $userData['last_name'] ?? null,
                    'role' => 'user', // Роль по умолчанию
                ]);

                Log::info('Bot user created automatically', [
                    'bot_user_id' => $botUser->id,
                    'bot_id' => $bot->id,
                    'telegram_user_id' => $telegramUserId,
                    'chat_id' => $chatId,
                    'role' => 'user',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error syncing bot user', [
                'bot_id' => $bot->id,
                'chat_id' => $chatId,
                'telegram_user_id' => $telegramUserId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}


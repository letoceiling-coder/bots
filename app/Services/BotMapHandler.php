<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\BotSession;
use App\Models\BotSessionStep;
use App\Models\BotUser;
use App\Models\ManagerChatMessage;
use App\Services\BotSessionService;
use App\Services\TelegramBotService;
use Illuminate\Support\Facades\Log;

class BotMapHandler
{
    protected BotSessionService $sessionService;
    protected TelegramBotService $telegramService;

    public function __construct(
        BotSessionService $sessionService,
        TelegramBotService $telegramService
    ) {
        $this->sessionService = $sessionService;
        $this->telegramService = $telegramService;
    }

    /**
     * Обработать обновление от Telegram
     */
    public function handleUpdate(Bot $bot, array $update): void
    {
        Log::info('Handling bot map update', [
            'bot_id' => $bot->id,
            'update_type' => $this->getUpdateType($update),
        ]);

        // Определяем тип обновления
        if (isset($update['message'])) {
            $this->handleMessage($bot, $update['message']);
        } elseif (isset($update['callback_query'])) {
            $this->handleCallbackQuery($bot, $update['callback_query']);
        } else {
            Log::warning('Unknown update type', [
                'bot_id' => $bot->id,
                'update_keys' => array_keys($update),
            ]);
        }
    }

    /**
     * Обработать текстовое сообщение
     */
    protected function handleMessage(Bot $bot, array $message): void
    {
        $chatId = $message['chat']['id'];
        $userData = $message['from'] ?? [];
        $text = $message['text'] ?? null;
        $document = $message['document'] ?? null;
        $photo = $message['photo'] ?? null;
        $contact = $message['contact'] ?? null;
        $location = $message['location'] ?? null;

        Log::info('Handling message', [
            'bot_id' => $bot->id,
            'chat_id' => $chatId,
            'text' => $text,
            'has_text' => !empty($text),
            'has_document' => !empty($document),
            'has_photo' => !empty($photo),
            'has_contact' => !empty($contact),
            'has_location' => !empty($location),
            'is_command' => !empty($text) && str_starts_with($text, '/'),
        ]);

        // Проверяем, является ли отправитель менеджером
        $telegramUserId = (string)($userData['id'] ?? $chatId);
        $isManager = BotUser::where('bot_id', $bot->id)
            ->where('telegram_user_id', $telegramUserId)
            ->where('role', 'manager')
            ->exists();

        // Если отправитель - менеджер, ищем активную сессию в режиме manager_chat
        if ($isManager) {
            $activeSession = BotSession::where('bot_id', $bot->id)
                ->where('status', 'manager_chat')
                ->latest('last_activity_at')
                ->first();

            if ($activeSession) {
                Log::info('Manager message received, forwarding to user session', [
                    'bot_id' => $bot->id,
                    'manager_telegram_user_id' => $telegramUserId,
                    'target_session_id' => $activeSession->id,
                    'target_chat_id' => $activeSession->chat_id,
                ]);
                $this->handleManagerChatMessage($bot, $activeSession, $message);
                return;
            } else {
                Log::warning('Manager sent message but no active manager_chat session found', [
                    'bot_id' => $bot->id,
                    'manager_telegram_user_id' => $telegramUserId,
                    'chat_id' => $chatId,
                ]);
                // Не создаем сессию для менеджера, просто возвращаемся
                // Можно отправить сообщение менеджеру, что нет активных запросов
                try {
                    $telegraph = $this->telegramService->bot($bot)->chat($chatId);
                    $telegraph->message("Нет активных запросов на связь с менеджером.")->send();
                } catch (\Exception $e) {
                    Log::error('Error sending message to manager', [
                        'error' => $e->getMessage(),
                    ]);
                }
                return;
            }
        }

        // Получаем или создаем сессию
        $session = $this->sessionService->getOrCreateSession($bot, (string)$chatId, $userData);

        // Обновляем активность сессии
        $session->touchActivity();

        // Проверяем, находится ли сессия в режиме чата с менеджером
        // Обновляем сессию из БД, чтобы получить актуальный статус
        $session->refresh();
        
        Log::info('Checking session status for manager_chat', [
            'session_id' => $session->id,
            'chat_id' => $chatId,
            'status' => $session->status,
            'telegram_user_id' => $telegramUserId,
        ]);
        
        if ($session->status === 'manager_chat') {
            Log::info('Session is in manager_chat mode, forwarding to managers', [
                'session_id' => $session->id,
                'chat_id' => $chatId,
                'status' => $session->status,
            ]);
            
            // Команды обрабатываются отдельно, даже в режиме менеджера
            if ($text && str_starts_with($text, '/')) {
                // Обрабатываем команду выхода из режима менеджера
                if (in_array($text, ['/exit', '/back', '/menu'])) {
                    // Загружаем блоки для exitManagerChat
                    $blocks = $bot->blocks ?? [];
                    if (!empty($blocks)) {
                        $blocks = $this->ensureDefaultCommands($bot, $blocks);
                    }
                    $this->exitManagerChat($bot, $session, $blocks);
                    return;
                }
            }
            
            // Обрабатываем сообщение в режиме чата с менеджером
            $this->handleManagerChatMessage($bot, $session, $message);
            return;
        }

        // Загружаем карту бота
        $blocks = $bot->blocks ?? [];
        if (empty($blocks)) {
            Log::warning('Bot has no blocks map', ['bot_id' => $bot->id]);
            return;
        }

        // Проверяем и добавляем дефолтные команды, если их нет
        $blocks = $this->ensureDefaultCommands($bot, $blocks);

        // Определяем текущий блок (для всех типов сообщений, кроме команд)
        $currentBlock = null;
        
        // Обрабатываем входящие данные
        if ($text) {
            // Проверяем, является ли текст командой (начинается с /)
            if (str_starts_with($text, '/')) {
                $this->handleCommand($bot, $session, $blocks, $text);
                return; // Команды обрабатываются отдельно
            } else {
                // Определяем текущий блок для обычного текста
                $currentBlock = $this->getCurrentBlock($session, $blocks);
                $this->handleTextInput($bot, $session, $currentBlock, $blocks, $text);
            }
        } elseif ($document) {
            $currentBlock = $this->getCurrentBlock($session, $blocks);
            $this->handleFileInput($bot, $session, $currentBlock, $blocks, 'document', $document);
        } elseif ($photo) {
            $currentBlock = $this->getCurrentBlock($session, $blocks);
            $this->handleFileInput($bot, $session, $currentBlock, $blocks, 'photo', $photo);
        } elseif ($contact) {
            $currentBlock = $this->getCurrentBlock($session, $blocks);
            $this->handleContactInput($bot, $session, $currentBlock, $blocks, $contact);
        } elseif ($location) {
            $currentBlock = $this->getCurrentBlock($session, $blocks);
            $this->handleLocationInput($bot, $session, $currentBlock, $blocks, $location);
        }
    }

    /**
     * Обработать callback query (нажатие на кнопку)
     */
    protected function handleCallbackQuery(Bot $bot, array $callbackQuery): void
    {
        $message = $callbackQuery['message'] ?? [];
        $chatId = $message['chat']['id'] ?? null;
        $callbackData = $callbackQuery['data'] ?? null;
        $callbackQueryId = $callbackQuery['id'] ?? null;
        $userData = $callbackQuery['from'] ?? [];

        Log::info('Handling callback query', [
            'bot_id' => $bot->id,
            'chat_id' => $chatId,
            'callback_query_id' => $callbackQueryId,
            'callback_data' => $callbackData,
            'full_callback_query' => $callbackQuery,
        ]);

        if (!$chatId || !$callbackData || !$callbackQueryId) {
            Log::warning('Invalid callback query', [
                'bot_id' => $bot->id,
                'chat_id' => $chatId,
                'callback_data' => $callbackData,
                'callback_query_id' => $callbackQueryId,
            ]);
            return;
        }

        // Отвечаем на callback_query сразу (обязательно для Telegram)
        $telegraph = $this->telegramService->bot($bot);
        try {
            $telegraph->answerCallbackQuery($callbackQueryId);
            Log::debug('Answered callback query', [
                'callback_query_id' => $callbackQueryId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to answer callback query', [
                'callback_query_id' => $callbackQueryId,
                'error' => $e->getMessage(),
            ]);
        }

        // Получаем или создаем сессию
        $session = $this->sessionService->getOrCreateSession($bot, (string)$chatId, $userData);

        // Загружаем карту бота
        $blocks = $bot->blocks ?? [];
        if (empty($blocks)) {
            Log::warning('Bot has no blocks map', ['bot_id' => $bot->id]);
            return;
        }

        // Проверяем и добавляем дефолтные команды, если их нет
        $blocks = $this->ensureDefaultCommands($bot, $blocks);

        // Если callback_data начинается с "/", обрабатываем как команду
        if (str_starts_with($callbackData, '/')) {
            Log::info('Callback_data is a command, handling as command', [
                'bot_id' => $bot->id,
                'callback_data' => $callbackData,
                'chat_id' => $chatId,
            ]);
            $this->handleCommand($bot, $session, $blocks, $callbackData);
            return;
        }

        // Находим блок по callback_data
        $targetBlock = $this->findBlockByCallbackData($blocks, $callbackData);

        if (!$targetBlock) {
            // Если блок не найден, возможно callback_data - это значение для сохранения
            // в текущем блоке (например, выбор ОПФ)
            $currentBlock = $this->getCurrentBlock($session, $blocks);
            
            if ($currentBlock && ($currentBlock['method'] === 'inlineKeyboard' || $currentBlock['method'] === 'question')) {
                Log::info('Callback_data is a value to save, not a block ID', [
                    'bot_id' => $bot->id,
                    'callback_data' => $callbackData,
                    'current_block_id' => $currentBlock['id'] ?? null,
                    'current_block_method' => $currentBlock['method'] ?? null,
                ]);

                // Проверяем, что кнопка с таким callback_data существует в текущем блоке
                $methodData = $currentBlock['methodData'] ?? $currentBlock['method_data'] ?? [];
                $inlineKeyboard = $methodData['inline_keyboard'] ?? [];
                $buttonFound = false;
                
                foreach ($inlineKeyboard as $row) {
                    foreach ($row as $button) {
                        if (($button['callback_data'] ?? null) === $callbackData) {
                            $buttonFound = true;
                            break 2;
                        }
                    }
                }

                if ($buttonFound) {
                    // Проверяем, есть ли у кнопки target_block_id
                    $targetBlockId = null;
                    foreach ($inlineKeyboard as $row) {
                        foreach ($row as $button) {
                            if (($button['callback_data'] ?? null) === $callbackData) {
                                $targetBlockId = $button['target_block_id'] ?? null;
                                break 2;
                            }
                        }
                    }

                    // Сохраняем callback_data как данные сессии
                    $dataKey = $currentBlock['data_key'] ?? strtolower(str_replace([' ', '-'], '_', $currentBlock['label'] ?? 'answer'));
                    
                    // Если callback_data начинается с префикса (например, opf_ip), извлекаем значение
                    $value = $callbackData;
                    if (str_contains($callbackData, '_')) {
                        $parts = explode('_', $callbackData, 2);
                        $value = $parts[1] ?? $callbackData;
                    }

                    Log::info('Saving callback_data as session data', [
                        'session_id' => $session->id,
                        'data_key' => $dataKey,
                        'value' => $value,
                        'callback_data' => $callbackData,
                        'target_block_id_from_button' => $targetBlockId,
                    ]);

                    $this->sessionService->saveSessionData($session, $dataKey, $value, $currentBlock['id'] ?? null);

                    // Создаем шаг
                    $step = $this->sessionService->createStep(
                        $session,
                        $currentBlock['id'] ?? null,
                        $currentBlock['label'] ?? null,
                        $currentBlock['method'] ?? null,
                        'callback',
                        $callbackData
                    );

                    // Переходим к следующему блоку
                    // Приоритет: target_block_id из кнопки > nextBlockId блока
                    $nextBlockId = $targetBlockId ?? $currentBlock['nextBlockId'] ?? null;
                    if ($nextBlockId) {
                        $nextBlock = $this->findBlockById($blocks, $nextBlockId);
                        if ($nextBlock) {
                            Log::info('Moving to next block after saving callback value', [
                                'session_id' => $session->id,
                                'current_block_id' => $currentBlock['id'] ?? null,
                                'next_block_id' => $nextBlockId,
                                'source' => $targetBlockId ? 'button target_block_id' : 'block nextBlockId',
                            ]);
                            $this->sessionService->updateCurrentBlock($session, $nextBlockId);
                            $this->executeBlock($bot, $session, $nextBlock, $blocks);
                            return;
                        } else {
                            Log::warning('Target block not found', [
                                'session_id' => $session->id,
                                'next_block_id' => $nextBlockId,
                            ]);
                        }
                    } else {
                        Log::warning('No nextBlockId after saving callback value', [
                            'session_id' => $session->id,
                            'current_block_id' => $currentBlock['id'] ?? null,
                        ]);
                    }
                    return;
                }
            }

            Log::warning('Block not found by callback_data', [
                'bot_id' => $bot->id,
                'callback_data' => $callbackData,
                'available_blocks' => array_map(fn($b) => $b['id'] ?? null, $blocks),
            ]);
            return;
        }

        Log::info('Target block found for callback', [
            'bot_id' => $bot->id,
            'callback_data' => $callbackData,
            'target_block_id' => $targetBlock['id'] ?? null,
            'target_block_label' => $targetBlock['label'] ?? null,
        ]);

        // Создаем шаг
        $step = $this->sessionService->createStep(
            $session,
            $targetBlock['id'] ?? null,
            $targetBlock['label'] ?? null,
            $targetBlock['method'] ?? null,
            'callback',
            $callbackData
        );

        // Обновляем текущий блок
        $this->sessionService->updateCurrentBlock($session, $targetBlock['id'] ?? null);

        // Выполняем блок
        $this->executeBlock($bot, $session, $targetBlock, $blocks, $step);
    }

    /**
     * Обработать команду бота
     */
    protected function handleCommand(
        Bot $bot,
        BotSession $session,
        array $blocks,
        string $command
    ): void {
        Log::info('Handling bot command', [
            'session_id' => $session->id,
            'bot_id' => $bot->id,
            'chat_id' => $session->chat_id,
            'command' => $command,
            'total_blocks' => count($blocks),
        ]);

        // Находим блок с такой командой
        $commandBlock = null;
        foreach ($blocks as $block) {
            if (isset($block['command']) && $block['command'] === $command) {
                $commandBlock = $block;
                break;
            }
        }

        if (!$commandBlock) {
            Log::warning('Command block not found', [
                'session_id' => $session->id,
                'command' => $command,
            ]);
            
            // Отправляем сообщение об ошибке
            try {
                $telegraph = $this->telegramService->bot($bot)->chat($session->chat_id);
                $telegraph->message("Команда не найдена. Используйте /start для начала.")->send();
            } catch (\Exception $e) {
                Log::error('Error sending error message', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }
            return;
        }

        Log::info('Command block found', [
            'session_id' => $session->id,
            'command' => $command,
            'block_id' => $commandBlock['id'] ?? null,
        ]);

        // Создаем шаг для команды
        $step = $this->sessionService->createStep(
            $session,
            $commandBlock['id'] ?? null,
            $commandBlock['label'] ?? null,
            $commandBlock['method'] ?? null,
            'command',
            $command
        );

        // Обновляем текущий блок на блок команды
        $this->sessionService->updateCurrentBlock($session, $commandBlock['id'] ?? null);

        // Выполняем блок команды
        $this->executeBlock($bot, $session, $commandBlock, $blocks, $step);

        // Обновляем сессию из БД, чтобы получить актуальный статус
        $session->refresh();

        // Если команда переключила сессию в режим manager_chat, не переходим к следующему блоку
        if ($session->status === 'manager_chat') {
            Log::info('Command switched to manager_chat mode, skipping next block', [
                'session_id' => $session->id,
                'command' => $command,
            ]);
            return;
        }

        // После выполнения команды переходим к следующему блоку, если указан
        $nextBlockId = $commandBlock['nextBlockId'] ?? null;
        if ($nextBlockId) {
            $nextBlock = $this->findBlockById($blocks, $nextBlockId);
            if ($nextBlock) {
                Log::info('Moving to next block after command', [
                    'session_id' => $session->id,
                    'command' => $command,
                    'next_block_id' => $nextBlockId,
                ]);
                $this->sessionService->updateCurrentBlock($session, $nextBlockId);
                $this->executeBlock($bot, $session, $nextBlock, $blocks);
            }
        }
    }

    /**
     * Обработать текстовый ввод
     */
    protected function handleTextInput(
        Bot $bot,
        BotSession $session,
        ?array $currentBlock,
        array $blocks,
        string $text
    ): void {
        Log::info('Handling text input', [
            'session_id' => $session->id,
            'current_block_id' => $currentBlock['id'] ?? null,
            'text_length' => strlen($text),
        ]);

        // Сохраняем текст как ответ
        $step = $this->sessionService->createStep(
            $session,
            $currentBlock['id'] ?? null,
            $currentBlock['label'] ?? null,
            $currentBlock['method'] ?? null,
            'text',
            $text
        );

        // Если текущий блок - вопрос, сохраняем ответ как данные
        if ($currentBlock && ($currentBlock['method'] === 'question')) {
            // Извлекаем ключ данных из метаданных блока или используем label
            $dataKey = $currentBlock['data_key'] ?? strtolower(str_replace(' ', '_', $currentBlock['label'] ?? 'answer'));
            $this->sessionService->saveSessionData($session, $dataKey, $text, $currentBlock['id'] ?? null);
        }

        // Переход к следующему блоку
        $nextBlockId = $currentBlock['nextBlockId'] ?? null;
        if ($nextBlockId) {
            $nextBlock = $this->findBlockById($blocks, $nextBlockId);
            if ($nextBlock) {
                $this->sessionService->updateCurrentBlock($session, $nextBlockId);
                $this->executeBlock($bot, $session, $nextBlock, $blocks, $step);
            }
        }
    }

    /**
     * Обработать файл
     */
    protected function handleFileInput(
        Bot $bot,
        BotSession $session,
        ?array $currentBlock,
        array $blocks,
        string $fileType,
        array $fileData
    ): void {
        Log::info('Handling file input', [
            'session_id' => $session->id,
            'file_type' => $fileType,
            'file_id' => $fileData['file_id'] ?? null,
        ]);

        // Создаем шаг
        $step = $this->sessionService->createStep(
            $session,
            $currentBlock['id'] ?? null,
            $currentBlock['label'] ?? null,
            $currentBlock['method'] ?? null,
            $fileType
        );

        // Сохраняем файл
        $sessionFile = $this->sessionService->saveSessionFile($session, $step, [
            'telegram_file_id' => $fileData['file_id'] ?? null,
            'file_type' => $fileType,
            'file_name' => $fileData['file_name'] ?? $fileData['file_unique_id'] ?? null,
            'file_size' => $fileData['file_size'] ?? null,
            'mime_type' => $fileData['mime_type'] ?? null,
        ]);

        // Переход к следующему блоку
        $nextBlockId = $currentBlock['nextBlockId'] ?? null;
        if ($nextBlockId) {
            $nextBlock = $this->findBlockById($blocks, $nextBlockId);
            if ($nextBlock) {
                $this->sessionService->updateCurrentBlock($session, $nextBlockId);
                $this->executeBlock($bot, $session, $nextBlock, $blocks, $step);
            }
        }
    }

    /**
     * Обработать контакт
     */
    protected function handleContactInput(
        Bot $bot,
        BotSession $session,
        ?array $currentBlock,
        array $blocks,
        array $contact
    ): void {
        Log::info('Handling contact input', [
            'session_id' => $session->id,
            'phone_number' => $contact['phone_number'] ?? null,
        ]);

        $step = $this->sessionService->createStep(
            $session,
            $currentBlock['id'] ?? null,
            $currentBlock['label'] ?? null,
            $currentBlock['method'] ?? null,
            'contact',
            json_encode($contact)
        );

        // Сохраняем контакт как данные
        if (isset($contact['phone_number'])) {
            $this->sessionService->saveSessionData($session, 'phone', $contact['phone_number'], $currentBlock['id'] ?? null);
        }
    }

    /**
     * Обработать геолокацию
     */
    protected function handleLocationInput(
        Bot $bot,
        BotSession $session,
        ?array $currentBlock,
        array $blocks,
        array $location
    ): void {
        Log::info('Handling location input', [
            'session_id' => $session->id,
            'latitude' => $location['latitude'] ?? null,
            'longitude' => $location['longitude'] ?? null,
        ]);

        $step = $this->sessionService->createStep(
            $session,
            $currentBlock['id'] ?? null,
            $currentBlock['label'] ?? null,
            $currentBlock['method'] ?? null,
            'location',
            json_encode($location)
        );
    }

    /**
     * Выполнить блок
     */
    protected function executeBlock(
        Bot $bot,
        BotSession $session,
        array $block,
        array $blocks,
        ?BotSessionStep $previousStep = null
    ): void {
        Log::info('Executing block', [
            'session_id' => $session->id,
            'block_id' => $block['id'] ?? null,
            'block_label' => $block['label'] ?? null,
            'method' => $block['method'] ?? null,
        ]);

        $method = $block['method'] ?? null;
        // Поддержка как method_data (snake_case), так и methodData (camelCase)
        $methodData = $block['method_data'] ?? $block['methodData'] ?? [];
        
        Log::debug('Block method data', [
            'session_id' => $session->id,
            'block_id' => $block['id'] ?? null,
            'has_method_data' => isset($block['method_data']),
            'has_methodData' => isset($block['methodData']),
            'method_data_keys' => array_keys($methodData),
            'text_length' => strlen($methodData['text'] ?? ''),
        ]);

        if (!$method) {
            Log::warning('Block has no method', [
                'session_id' => $session->id,
                'block_id' => $block['id'] ?? null,
            ]);
            return;
        }

        try {
            $telegraph = $this->telegramService->bot($bot);
            $telegraph->chat($session->chat_id);

            $result = null;
            $botResponse = null;

            switch ($method) {
                case 'sendMessage':
                    $botResponse = $methodData['text'] ?? '';
                    $result = $telegraph->message($botResponse)
                        ->parseMode($methodData['parse_mode'] ?? null)
                        ->send();
                    break;

                case 'inlineKeyboard':
                    $botResponse = $methodData['text'] ?? 'Выберите действие:';
                    $inlineKeyboard = [];
                    foreach ($methodData['inline_keyboard'] ?? [] as $row) {
                        $inlineRow = [];
                        foreach ($row as $button) {
                            $inlineButton = ['text' => $button['text'] ?? ''];
                            if (!empty($button['callback_data'])) {
                                $inlineButton['callback_data'] = $button['callback_data'];
                            } elseif (!empty($button['url'])) {
                                $inlineButton['url'] = $button['url'];
                            }
                            $inlineRow[] = $inlineButton;
                        }
                        $inlineKeyboard[] = $inlineRow;
                    }
                    
                    // Логируем отправляемую клавиатуру для диагностики
                    Log::debug('Sending inline keyboard', [
                        'session_id' => $session->id,
                        'block_id' => $block['id'] ?? null,
                        'buttons_count' => count($inlineKeyboard),
                        'keyboard_structure' => json_encode($inlineKeyboard),
                    ]);
                    
                    $result = $telegraph->message($botResponse)
                        ->inlineKeyboard($inlineKeyboard)
                        ->send();
                    break;

                case 'question':
                    $botResponse = $methodData['text'] ?? '';
                    $result = $telegraph->message($botResponse)
                        ->parseMode($methodData['parse_mode'] ?? null)
                        ->send();
                    break;

                case 'sendDocument':
                    $documentPath = $methodData['document'] ?? '';
                    $caption = $methodData['caption'] ?? null;
                    $result = $telegraph->document($documentPath)
                        ->caption($caption)
                        ->send();
                    $botResponse = $caption ?? 'Документ отправлен';
                    break;

                case 'managerChat':
                    $botResponse = $methodData['text'] ?? 'Переключение на менеджера...';
                    // Обновляем статус сессии и активность
                    $session->update([
                        'status' => 'manager_chat',
                        'last_activity_at' => now(),
                    ]);
                    // Обновляем сессию в памяти
                    $session->refresh();
                    
                    // Создаем reply keyboard с командами для выхода
                    $exitKeyboard = [
                        [
                            ['text' => '/exit'],
                            ['text' => '/back'],
                            ['text' => '/menu'],
                        ],
                    ];
                    
                    $result = $telegraph->message($botResponse)
                        ->keyboard($exitKeyboard)
                        ->send();
                    
                    Log::info('Switched session to manager_chat mode', [
                        'session_id' => $session->id,
                        'bot_id' => $bot->id,
                        'chat_id' => $session->chat_id,
                        'status' => $session->status,
                    ]);
                    
                    // Отправляем уведомления всем менеджерам бота
                    $this->notifyManagers($bot, $session, $methodData);
                    break;

                default:
                    Log::warning('Unknown block method', [
                        'session_id' => $session->id,
                        'block_id' => $block['id'] ?? null,
                        'method' => $method,
                    ]);
                    return;
            }

            // Сохраняем ответ бота в шаг
            if ($previousStep) {
                $previousStep->update([
                    'bot_response' => $botResponse,
                    'bot_response_data' => $result instanceof \DefStudio\Telegraph\Client\TelegraphResponse
                        ? $result->json()
                        : $result,
                ]);
            } else {
                // Создаем новый шаг для ответа бота
                $this->sessionService->createStep(
                    $session,
                    $block['id'] ?? null,
                    $block['label'] ?? null,
                    $method,
                    null,
                    null,
                    $botResponse,
                    $result instanceof \DefStudio\Telegraph\Client\TelegraphResponse
                        ? $result->json()
                        : $result
                );
            }

            Log::info('Block executed successfully', [
                'session_id' => $session->id,
                'block_id' => $block['id'] ?? null,
                'method' => $method,
            ]);

            // Автоматический переход к следующему блоку после выполнения
            // (только для блоков, которые не требуют ответа пользователя)
            $nextBlockId = $block['nextBlockId'] ?? null;
            if ($nextBlockId && in_array($method, ['sendMessage', 'inlineKeyboard', 'sendDocument'])) {
                $nextBlock = $this->findBlockById($blocks, $nextBlockId);
                if ($nextBlock) {
                    Log::info('Auto-moving to next block after execution', [
                        'session_id' => $session->id,
                        'current_block_id' => $block['id'] ?? null,
                        'next_block_id' => $nextBlockId,
                        'next_block_label' => $nextBlock['label'] ?? null,
                    ]);
                    $this->sessionService->updateCurrentBlock($session, $nextBlockId);
                    $this->executeBlock($bot, $session, $nextBlock, $blocks);
                } else {
                    Log::warning('Next block not found for auto-move', [
                        'session_id' => $session->id,
                        'current_block_id' => $block['id'] ?? null,
                        'next_block_id' => $nextBlockId,
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Error executing block', [
                'session_id' => $session->id,
                'block_id' => $block['id'] ?? null,
                'method' => $method,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Получить текущий блок
     */
    protected function getCurrentBlock(BotSession $session, array $blocks): ?array
    {
        $currentBlockId = $session->current_block_id;

        if ($currentBlockId) {
            return $this->findBlockById($blocks, $currentBlockId);
        }

        // Ищем блок /start (обычно блок с id = "1")
        $startBlock = $this->findBlockById($blocks, '1');
        if ($startBlock) {
            $this->sessionService->updateCurrentBlock($session, '1');
            return $startBlock;
        }

        // Если нет блока /start, берем первый блок
        if (!empty($blocks)) {
            $firstBlock = $blocks[0];
            $this->sessionService->updateCurrentBlock($session, $firstBlock['id'] ?? null);
            return $firstBlock;
        }

        return null;
    }

    /**
     * Найти блок по ID
     */
    protected function findBlockById(array $blocks, string $blockId): ?array
    {
        foreach ($blocks as $block) {
            if (($block['id'] ?? null) === $blockId) {
                return $block;
            }
        }
        return null;
    }

    /**
     * Найти блок по callback_data
     */
    protected function findBlockByCallbackData(array $blocks, string $callbackData): ?array
    {
        Log::debug('Finding block by callback_data', ['callback_data' => $callbackData]);

        // Шаг 1: Проверяем, является ли callback_data прямым ID блока
        $directBlock = $this->findBlockById($blocks, $callbackData);
        if ($directBlock) {
            Log::debug('Found block by direct ID match', [
                'callback_data' => $callbackData,
                'block_id' => $directBlock['id'] ?? null,
            ]);
            return $directBlock;
        }

        // Шаг 2: Ищем в кнопках inline-клавиатуры
        foreach ($blocks as $block) {
            if ($block['method'] === 'inlineKeyboard') {
                $inlineKeyboard = $block['method_data']['inline_keyboard'] ?? [];
                foreach ($inlineKeyboard as $row) {
                    foreach ($row as $button) {
                        if (($button['callback_data'] ?? null) === $callbackData) {
                            // Приоритет 1: Проверяем target_block_id кнопки
                            $targetBlockId = $button['target_block_id'] ?? null;
                            if ($targetBlockId) {
                                $targetBlock = $this->findBlockById($blocks, $targetBlockId);
                                if ($targetBlock) {
                                    Log::debug('Found block by button target_block_id', [
                                        'callback_data' => $callbackData,
                                        'target_block_id' => $targetBlockId,
                                        'target_block_label' => $targetBlock['label'] ?? null,
                                    ]);
                                    return $targetBlock;
                                } else {
                                    Log::warning('Target block not found by target_block_id', [
                                        'callback_data' => $callbackData,
                                        'target_block_id' => $targetBlockId,
                                    ]);
                                }
                            }

                            // Приоритет 2: Используем nextBlockId блока (старая логика)
                            $nextBlockId = $block['nextBlockId'] ?? null;
                            if ($nextBlockId) {
                                $nextBlock = $this->findBlockById($blocks, $nextBlockId);
                                if ($nextBlock) {
                                    Log::debug('Found block by parent nextBlockId', [
                                        'callback_data' => $callbackData,
                                        'next_block_id' => $nextBlockId,
                                        'next_block_label' => $nextBlock['label'] ?? null,
                                    ]);
                                    return $nextBlock;
                                }
                            }

                            // Приоритет 3: Возвращаем сам блок с меню
                            Log::debug('Returning parent block (no target specified)', [
                                'callback_data' => $callbackData,
                                'block_id' => $block['id'] ?? null,
                                'block_label' => $block['label'] ?? null,
                            ]);
                            return $block;
                        }
                    }
                }
            }
        }

        // Если ничего не найдено
        Log::warning('Block not found by callback_data', [
            'callback_data' => $callbackData,
        ]);
        return null;
    }

    /**
     * Получить тип обновления
     */
    protected function getUpdateType(array $update): string
    {
        if (isset($update['message'])) {
            return 'message';
        } elseif (isset($update['callback_query'])) {
            return 'callback_query';
        } elseif (isset($update['edited_message'])) {
            return 'edited_message';
        }
        return 'unknown';
    }

    /**
     * Обработать сообщение в режиме чата с менеджером
     */
    protected function handleManagerChatMessage(Bot $bot, BotSession $session, array $message): void
    {
        $chatId = (string)($message['chat']['id'] ?? '');
        $userData = $message['from'] ?? [];
        $telegramUserId = (string)($userData['id'] ?? $chatId);
        $messageId = $message['message_id'] ?? null;

        // Извлекаем все возможные типы медиа
        $text = $message['text'] ?? null;
        $document = $message['document'] ?? null;
        $photo = $message['photo'] ?? null;
        $video = $message['video'] ?? null;
        $audio = $message['audio'] ?? null;
        $voice = $message['voice'] ?? null;
        $videoNote = $message['video_note'] ?? null;
        $animation = $message['animation'] ?? null;
        $sticker = $message['sticker'] ?? null;
        $contact = $message['contact'] ?? null;
        $location = $message['location'] ?? null;
        $venue = $message['venue'] ?? null;

        // Определяем тип сообщения
        $messageType = $this->detectMessageType($message);

        Log::info('Handling manager chat message', [
            'bot_id' => $bot->id,
            'session_id' => $session->id,
            'chat_id' => $chatId,
            'telegram_user_id' => $telegramUserId,
            'message_type' => $messageType,
            'has_text' => !empty($text),
            'has_document' => !empty($document),
            'has_photo' => !empty($photo),
            'has_video' => !empty($video),
            'has_audio' => !empty($audio),
            'has_voice' => !empty($voice),
            'has_video_note' => !empty($videoNote),
            'has_animation' => !empty($animation),
            'has_sticker' => !empty($sticker),
            'has_contact' => !empty($contact),
            'has_location' => !empty($location),
            'has_venue' => !empty($venue),
        ]);

        // Обновляем активность сессии
        $session->touchActivity();
        
        // Проверяем, является ли отправитель менеджером
        $isManager = BotUser::where('bot_id', $bot->id)
            ->where('telegram_user_id', $telegramUserId)
            ->where('role', 'manager')
            ->exists();

        if ($isManager) {
            // Сообщение от менеджера - пересылаем пользователю
            $this->forwardMessageToUser($bot, $session, $message, $telegramUserId);
        } else {
            // Сообщение от пользователя - пересылаем всем менеджерам
            $this->forwardMessageToManagers($bot, $session, $message);
        }
    }

    /**
     * Уведомить всех менеджеров о запросе связи
     */
    protected function notifyManagers(Bot $bot, BotSession $session, array $methodData = []): void
    {
        $managers = BotUser::where('bot_id', $bot->id)
            ->where('role', 'manager')
            ->get();

        if ($managers->isEmpty()) {
            Log::warning('No managers found for bot', [
                'bot_id' => $bot->id,
                'session_id' => $session->id,
            ]);
            return;
        }

        $userName = $session->first_name . ($session->last_name ? ' ' . $session->last_name : '');
        $userName = $userName ?: ($session->username ? '@' . $session->username : "ID: {$session->chat_id}");
        
        $notificationText = "🔔 *Новый запрос на связь с менеджером*\n\n";
        $notificationText .= "👤 Пользователь: {$userName}\n";
        $notificationText .= "💬 Chat ID: `{$session->chat_id}`\n";
        $notificationText .= "🆔 Telegram ID: `{$session->user_id}`\n";
        $notificationText .= "📅 Время: " . now()->format('d.m.Y H:i') . "\n\n";
        $notificationText .= "💡 *Как ответить:*\n";
        $notificationText .= "1. Ответьте на это сообщение (Reply)\n";
        $notificationText .= "2. Или просто напишите сообщение - оно будет переслано последнему активному пользователю\n\n";
        $notificationText .= "Для выхода пользователя из режима менеджера он может использовать команду /exit";

        $telegraph = $this->telegramService->bot($bot);

        foreach ($managers as $manager) {
            try {
                $telegraph->chat($manager->chat_id)
                    ->message($notificationText)
                    ->parseMode('Markdown')
                    ->send();

                Log::info('Manager notified', [
                    'bot_id' => $bot->id,
                    'session_id' => $session->id,
                    'manager_id' => $manager->id,
                    'manager_chat_id' => $manager->chat_id,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to notify manager', [
                    'bot_id' => $bot->id,
                    'manager_id' => $manager->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Переслать сообщение от пользователя менеджерам
     */
    protected function forwardMessageToManagers(Bot $bot, BotSession $session, array $message): void
    {
        $managers = BotUser::where('bot_id', $bot->id)
            ->where('role', 'manager')
            ->get();

        if ($managers->isEmpty()) {
            Log::warning('No managers found for forwarding message', [
                'bot_id' => $bot->id,
                'session_id' => $session->id,
            ]);
            return;
        }

        $messageId = $message['message_id'] ?? null;
        $userName = $session->first_name . ($session->last_name ? ' ' . $session->last_name : '');
        $userName = $userName ?: ($session->username ? '@' . $session->username : "ID: {$session->chat_id}");

        // Определяем тип сообщения и текст для сохранения
        $messageType = $this->detectMessageType($message);
        $messageText = $this->extractMessageText($message, $userName);

        $telegraph = $this->telegramService->bot($bot);

        foreach ($managers as $manager) {
            try {
                $forwardedMessageId = null;

                // Пытаемся переслать сообщение через forwardMessage (работает для всех типов медиа)
                try {
                    $result = $telegraph->makeRequest('forwardMessage', [
                        'chat_id' => $manager->chat_id,
                        'from_chat_id' => $session->chat_id,
                        'message_id' => $messageId,
                    ]);
                    
                    if (isset($result['ok']) && $result['ok'] === true) {
                        $forwardedMessageId = $result['result']['message_id'] ?? null;
                    }
                } catch (\Exception $e) {
                    Log::debug('forwardMessage failed, trying alternative method', [
                        'error' => $e->getMessage(),
                        'message_type' => $messageType,
                    ]);
                }

                // Если forwardMessage не сработал, отправляем текстовое описание
                if (!$forwardedMessageId && $messageText) {
                    $result = $telegraph->chat($manager->chat_id)
                        ->message($messageText)
                        ->parseMode('Markdown')
                        ->send();
                    $forwardedMessageId = $result['result']['message_id'] ?? null;
                }

                // Сохраняем сообщение в БД
                ManagerChatMessage::create([
                    'session_id' => $session->id,
                    'bot_id' => $bot->id,
                    'user_chat_id' => $session->chat_id,
                    'manager_chat_id' => $manager->chat_id,
                    'manager_telegram_user_id' => $manager->telegram_user_id,
                    'direction' => 'user_to_manager',
                    'message_text' => $messageText,
                    'message_type' => $messageType,
                    'telegram_message_id' => $forwardedMessageId,
                    'telegram_data' => $message,
                ]);

                Log::info('Message forwarded to manager', [
                    'bot_id' => $bot->id,
                    'session_id' => $session->id,
                    'manager_id' => $manager->id,
                    'message_type' => $text ? 'text' : ($document ? 'document' : 'photo'),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to forward message to manager', [
                    'bot_id' => $bot->id,
                    'manager_id' => $manager->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Переслать сообщение от менеджера пользователю
     */
    protected function forwardMessageToUser(Bot $bot, BotSession $session, array $message, string $managerTelegramUserId): void
    {
        $messageId = $message['message_id'] ?? null;
        $chatId = (string)($message['chat']['id'] ?? '');
        $replyToMessage = $message['reply_to_message'] ?? null;
        $text = $message['text'] ?? null;

        // Находим менеджера
        $manager = BotUser::where('bot_id', $bot->id)
            ->where('telegram_user_id', $managerTelegramUserId)
            ->where('role', 'manager')
            ->first();

        if (!$manager) {
            Log::warning('Manager not found', [
                'bot_id' => $bot->id,
                'manager_telegram_user_id' => $managerTelegramUserId,
            ]);
            return;
        }

        // Определяем целевую сессию пользователя
        // Если менеджер отвечает на сообщение, пытаемся найти сессию по reply_to_message
        $targetSession = null;
        if ($replyToMessage) {
            $replyText = $replyToMessage['text'] ?? '';
            // Ищем chat_id в тексте уведомления
            if (preg_match('/Chat ID: `(\d+)`/', $replyText, $matches)) {
                $targetChatId = $matches[1];
                $targetSession = BotSession::where('bot_id', $bot->id)
                    ->where('chat_id', $targetChatId)
                    ->where('status', 'manager_chat')
                    ->latest('last_activity_at')
                    ->first();
                
                Log::info('Found target session by reply_to_message', [
                    'target_chat_id' => $targetChatId,
                    'session_found' => $targetSession !== null,
                    'session_id' => $targetSession->id ?? null,
                ]);
            }
        }

        // Если сессия не найдена по reply_to_message, используем переданную сессию
        // (которая должна быть активной сессией в режиме manager_chat)
        if (!$targetSession) {
            // Проверяем, что переданная сессия действительно в режиме manager_chat
            if ($session && $session->status === 'manager_chat') {
                $targetSession = $session;
                Log::info('Using provided session for manager message', [
                    'session_id' => $targetSession->id,
                    'chat_id' => $targetSession->chat_id,
                ]);
            } else {
                // Ищем последнюю активную сессию в режиме manager_chat
                $targetSession = BotSession::where('bot_id', $bot->id)
                    ->where('status', 'manager_chat')
                    ->latest('last_activity_at')
                    ->first();
                
                Log::info('Searched for active manager_chat session', [
                    'session_found' => $targetSession !== null,
                    'session_id' => $targetSession->id ?? null,
                ]);
            }
        }

        if (!$targetSession) {
            Log::warning('No active manager chat session found to forward message', [
                'bot_id' => $bot->id,
                'manager_id' => $manager->id,
                'manager_telegram_user_id' => $managerTelegramUserId,
                'provided_session_id' => $session->id ?? null,
                'provided_session_status' => $session->status ?? null,
            ]);
            return;
        }

        // Определяем тип сообщения и текст для сохранения
        $messageType = $this->detectMessageType($message);
        $messageText = $this->extractMessageText($message);

        Log::info('Forwarding manager message to user', [
            'bot_id' => $bot->id,
            'manager_id' => $manager->id,
            'target_session_id' => $targetSession->id,
            'target_chat_id' => $targetSession->chat_id,
            'message_type' => $messageType,
        ]);

        $telegraph = $this->telegramService->bot($bot);
        $forwardedMessageId = null;

        try {
            // Пытаемся переслать сообщение через forwardMessage (работает для всех типов медиа)
            try {
                $result = $telegraph->makeRequest('forwardMessage', [
                    'chat_id' => $targetSession->chat_id,
                    'from_chat_id' => $chatId,
                    'message_id' => $messageId,
                ]);
                
                if (isset($result['ok']) && $result['ok'] === true) {
                    $forwardedMessageId = $result['result']['message_id'] ?? null;
                }
            } catch (\Exception $e) {
                Log::debug('forwardMessage failed, trying alternative method', [
                    'error' => $e->getMessage(),
                    'message_type' => $messageType,
                ]);
            }

            // Если forwardMessage не сработал и это текстовое сообщение, отправляем текст
            if (!$forwardedMessageId && $text) {
                $result = $telegraph->chat($targetSession->chat_id)
                    ->message($text)
                    ->send();
                $forwardedMessageId = $result['result']['message_id'] ?? null;
            }

            // Обновляем активность сессии
            $targetSession->touchActivity();

            // Сохраняем сообщение в БД
            ManagerChatMessage::create([
                'session_id' => $targetSession->id,
                'bot_id' => $bot->id,
                'user_chat_id' => $targetSession->chat_id,
                'manager_chat_id' => $manager->chat_id,
                'manager_telegram_user_id' => $manager->telegram_user_id,
                'direction' => 'manager_to_user',
                'message_text' => $messageText,
                'message_type' => $messageType,
                'telegram_message_id' => $forwardedMessageId,
                'telegram_data' => $message,
            ]);

            Log::info('Message forwarded to user', [
                'bot_id' => $bot->id,
                'session_id' => $targetSession->id,
                'manager_id' => $manager->id,
                'message_type' => $messageType,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to forward message to user', [
                'bot_id' => $bot->id,
                'session_id' => $targetSession->id,
                'manager_id' => $manager->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Выйти из режима чата с менеджером
     */
    protected function exitManagerChat(Bot $bot, BotSession $session, array $blocks): void
    {
        Log::info('Exiting manager chat mode', [
            'bot_id' => $bot->id,
            'session_id' => $session->id,
        ]);

        // Возвращаем сессию в активный статус
        $session->update([
            'status' => 'active',
            'current_block_id' => null, // Сбрасываем текущий блок
        ]);

        $telegraph = $this->telegramService->bot($bot);

        // Отправляем сообщение пользователю и удаляем клавиатуру
        $telegraph->chat($session->chat_id)
            ->message("✅ Вы вышли из режима чата с менеджером.\n\nИспользуйте /start для возврата в главное меню.")
            ->removeKeyboard()
            ->send();

        // Уведомляем менеджеров
        $managers = BotUser::where('bot_id', $bot->id)
            ->where('role', 'manager')
            ->get();

        $userName = $session->first_name . ($session->last_name ? ' ' . $session->last_name : '');
        $userName = $userName ?: ($session->username ? '@' . $session->username : "ID: {$session->chat_id}");

        foreach ($managers as $manager) {
            try {
                $telegraph->chat($manager->chat_id)
                    ->message("ℹ️ Пользователь {$userName} вышел из режима чата с менеджером.")
                    ->send();
            } catch (\Exception $e) {
                Log::error('Failed to notify manager about exit', [
                    'bot_id' => $bot->id,
                    'manager_id' => $manager->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Определить тип сообщения
     */
    protected function detectMessageType(array $message): string
    {
        if (isset($message['text'])) {
            return 'text';
        } elseif (isset($message['photo'])) {
            return 'photo';
        } elseif (isset($message['video'])) {
            return 'video';
        } elseif (isset($message['document'])) {
            return 'document';
        } elseif (isset($message['audio'])) {
            return 'audio';
        } elseif (isset($message['voice'])) {
            return 'voice';
        } elseif (isset($message['video_note'])) {
            return 'video_note';
        } elseif (isset($message['animation'])) {
            return 'animation';
        } elseif (isset($message['sticker'])) {
            return 'sticker';
        } elseif (isset($message['contact'])) {
            return 'contact';
        } elseif (isset($message['location'])) {
            return 'location';
        } elseif (isset($message['venue'])) {
            return 'venue';
        }
        return 'unknown';
    }

    /**
     * Извлечь текстовое представление сообщения для сохранения в БД
     */
    protected function extractMessageText(array $message, ?string $userName = null): ?string
    {
        $text = $message['text'] ?? null;
        
        if ($text) {
            if ($userName) {
                return "💬 *Сообщение от пользователя:* {$userName}\n\n{$text}";
            }
            return $text;
        }

        // Для медиа-файлов создаем текстовое описание
        $caption = $message['caption'] ?? null;
        $description = '';

        if (isset($message['photo'])) {
            $description = "📷 *Фото";
        } elseif (isset($message['video'])) {
            $description = "🎥 *Видео";
        } elseif (isset($message['document'])) {
            $fileName = $message['document']['file_name'] ?? 'Документ';
            $description = "📄 *Документ:* {$fileName}";
        } elseif (isset($message['audio'])) {
            $title = $message['audio']['title'] ?? 'Аудио';
            $description = "🎵 *Аудио:* {$title}";
        } elseif (isset($message['voice'])) {
            $duration = $message['voice']['duration'] ?? 0;
            $description = "🎤 *Голосовое сообщение* ({$duration} сек)";
        } elseif (isset($message['video_note'])) {
            $duration = $message['video_note']['duration'] ?? 0;
            $description = "🎬 *Видео-кружок* ({$duration} сек)";
        } elseif (isset($message['animation'])) {
            $description = "🎞️ *Анимация/GIF";
        } elseif (isset($message['sticker'])) {
            $emoji = $message['sticker']['emoji'] ?? '';
            $description = "😊 *Стикер* {$emoji}";
        } elseif (isset($message['contact'])) {
            $firstName = $message['contact']['first_name'] ?? '';
            $phone = $message['contact']['phone_number'] ?? '';
            $description = "👤 *Контакт:* {$firstName}\n📞 Телефон: {$phone}";
        } elseif (isset($message['location'])) {
            $lat = $message['location']['latitude'] ?? 0;
            $lon = $message['location']['longitude'] ?? 0;
            $description = "📍 *Локация*\nКоординаты: {$lat}, {$lon}";
        } elseif (isset($message['venue'])) {
            $title = $message['venue']['title'] ?? '';
            $address = $message['venue']['address'] ?? '';
            $description = "🏢 *Место:* {$title}\n📍 Адрес: {$address}";
        }

        if ($description) {
            if ($userName) {
                $description .= " от пользователя:* {$userName}";
            } else {
                $description .= '*';
            }
            
            if ($caption) {
                $description .= "\n\n{$caption}";
            }
            
            return $description;
        }

        return null;
    }

    /**
     * Убедиться, что у бота есть дефолтные команды
     * 
     * @param Bot $bot
     * @param array $blocks
     * @return array
     */
    protected function ensureDefaultCommands(Bot $bot, array $blocks): array
    {
        $hasStartCommand = false;
        $hasManagerCommand = false;
        $maxId = 0;

        foreach ($blocks as $block) {
            if (isset($block['command'])) {
                if ($block['command'] === '/start') {
                    $hasStartCommand = true;
                }
                if ($block['command'] === '/manager') {
                    $hasManagerCommand = true;
                }
            }
            // Находим максимальный ID
            $blockId = (int)($block['id'] ?? 0);
            if ($blockId > $maxId) {
                $maxId = $blockId;
            }
        }

        $updated = false;

        // Если есть /start, но нет /manager - добавляем /manager
        if ($hasStartCommand && !$hasManagerCommand) {
            $managerBlock = [
                'id' => (string)($maxId + 1),
                'label' => '/manager - Связь с менеджером',
                'type' => 'command',
                'method' => 'managerChat',
                'method_data' => [
                    'text' => '🔔 Вы переключены на связь с менеджером.\n\nОпишите ваш вопрос, и менеджер свяжется с вами в ближайшее время.\n\nДля выхода используйте команды: /exit, /back или /menu',
                ],
                'command' => '/manager',
                'x' => 100,
                'y' => 250,
                'nextBlockId' => null,
            ];

            $blocks[] = $managerBlock;
            $updated = true;

            Log::info('Auto-added /manager command to bot', [
                'bot_id' => $bot->id,
                'new_block_id' => $managerBlock['id'],
            ]);
        }

        // Если блоки были обновлены, сохраняем их в БД
        if ($updated) {
            $bot->update(['blocks' => $blocks]);
            // Обновляем кэш бота
            $bot->refresh();
        }

        return $blocks;
    }
}


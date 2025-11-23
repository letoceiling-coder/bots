<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\BotSession;
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
            'has_text' => !empty($text),
            'has_document' => !empty($document),
            'has_photo' => !empty($photo),
            'has_contact' => !empty($contact),
            'has_location' => !empty($location),
        ]);

        // Получаем или создаем сессию
        $session = $this->sessionService->getOrCreateSession($bot, (string)$chatId, $userData);

        // Загружаем карту бота
        $blocks = $bot->blocks ?? [];
        if (empty($blocks)) {
            Log::warning('Bot has no blocks map', ['bot_id' => $bot->id]);
            return;
        }

        // Определяем текущий блок
        $currentBlock = $this->getCurrentBlock($session, $blocks);

        // Обрабатываем входящие данные
        if ($text) {
            $this->handleTextInput($bot, $session, $currentBlock, $blocks, $text);
        } elseif ($document) {
            $this->handleFileInput($bot, $session, $currentBlock, $blocks, 'document', $document);
        } elseif ($photo) {
            $this->handleFileInput($bot, $session, $currentBlock, $blocks, 'photo', $photo);
        } elseif ($contact) {
            $this->handleContactInput($bot, $session, $currentBlock, $blocks, $contact);
        } elseif ($location) {
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
        $userData = $callbackQuery['from'] ?? [];

        Log::info('Handling callback query', [
            'bot_id' => $bot->id,
            'chat_id' => $chatId,
            'callback_data' => $callbackData,
        ]);

        if (!$chatId || !$callbackData) {
            Log::warning('Invalid callback query', [
                'bot_id' => $bot->id,
                'chat_id' => $chatId,
                'callback_data' => $callbackData,
            ]);
            return;
        }

        // Получаем или создаем сессию
        $session = $this->sessionService->getOrCreateSession($bot, (string)$chatId, $userData);

        // Загружаем карту бота
        $blocks = $bot->blocks ?? [];
        if (empty($blocks)) {
            Log::warning('Bot has no blocks map', ['bot_id' => $bot->id]);
            return;
        }

        // Находим блок по callback_data
        $targetBlock = $this->findBlockByCallbackData($blocks, $callbackData);

        if (!$targetBlock) {
            Log::warning('Block not found by callback_data', [
                'bot_id' => $bot->id,
                'callback_data' => $callbackData,
            ]);
            return;
        }

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
        $methodData = $block['method_data'] ?? [];

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
                    $session->update(['status' => 'manager_chat']);
                    $result = $telegraph->message($botResponse)->send();
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
}


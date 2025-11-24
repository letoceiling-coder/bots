<?php

namespace App\Services;

use DefStudio\Telegraph\Telegraph;
use DefStudio\Telegraph\Client\TelegraphResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Bot;

/**
 * Расширенный класс для работы с Telegram Bot API
 * Добавляет методы, отсутствующие в базовом пакете Telegraph
 * 
 * @see https://core.telegram.org/bots/api
 */
class ExtendedTelegraph extends Telegraph
{
    protected ?string $baseUrl = 'https://api.telegram.org/bot';
    
    /**
     * Модель бота для работы с API
     * 
     * @var Bot|null
     */
    protected ?Bot $botModel = null;

    /**
     * Установить бота для работы с API
     * 
     * @param Bot $bot Модель бота
     * @return $this
     */
    public function setBot(Bot $bot): self
    {
        $this->botModel = $bot;
        
        // Устанавливаем бота в родительский класс через метод bot(), если он существует
        try {
            if (method_exists(parent::class, 'bot')) {
                // Пытаемся использовать метод bot() родительского класса
                // Но родительский класс может требовать TelegraphBot, а не нашу модель Bot
                // Поэтому используем рефлексию для установки токена
                $reflection = new \ReflectionClass(parent::class);
                if ($reflection->hasProperty('bot')) {
                    $property = $reflection->getProperty('bot');
                    $property->setAccessible(true);
                    // Устанавливаем нашу модель бота
                    $property->setValue($this, $bot);
                }
            } else {
                // Если метода bot() нет, используем рефлексию напрямую
                $reflection = new \ReflectionClass(parent::class);
                if ($reflection->hasProperty('bot')) {
                    $property = $reflection->getProperty('bot');
                    $property->setAccessible(true);
                    $property->setValue($this, $bot);
                }
            }
        } catch (\Exception $e) {
            // Игнорируем, если не удалось установить в родительский класс
            Log::warning('Could not set bot in parent class', [
                'error' => $e->getMessage(),
            ]);
        }
        
        return $this;
    }

    /**
     * Установить chat_id для запросов
     * Переопределяем метод родительского класса для поддержки строки/числа
     * 
     * @param string|int|mixed $chatId ID чата
     * @return $this
     */
    public function chat($chatId): self
    {
        // Вызываем родительский метод, если он существует
        try {
            parent::chat($chatId);
        } catch (\Exception $e) {
            // Если родительский метод не работает, устанавливаем напрямую
            $this->chat = $chatId;
        }
        
        // Дополнительно сохраняем chat_id для использования в makeRequest
        if (!isset($this->chat) || (is_object($this->chat) && !($this->chat instanceof \DefStudio\Telegraph\Models\TelegraphChat))) {
            $this->chat = $chatId;
        }
        
        return $this;
    }

    /**
     * Установить режим парсинга для сообщения
     * 
     * @param string|null $parseMode Режим парсинга (HTML, Markdown, MarkdownV2)
     * @return $this
     */
    public function parseMode(?string $parseMode): self
    {
        // Пытаемся вызвать родительский метод, если он существует
        try {
            if (method_exists(parent::class, 'parseMode')) {
                return parent::parseMode($parseMode);
            }
        } catch (\Exception $e) {
            // Игнорируем, если метод не существует
        }
        
        // Устанавливаем parse_mode в данных запроса
        if ($parseMode) {
            if (!isset($this->data)) {
                $this->data = [];
            }
            $this->data['parse_mode'] = $parseMode;
        }
        
        return $this;
    }

    /**
     * Отправить запрос (переопределяем метод родительского класса)
     * Использует наш makeRequest для правильной обработки токена и данных
     * 
     * @return TelegraphResponse
     */
    public function send(): TelegraphResponse
    {
        // Убеждаемся, что данные правильно установлены перед отправкой
        $endpoint = $this->endpoint ?? 'sendMessage';
        $data = $this->data ?? [];
        
        // Добавляем текст сообщения, если он установлен через message()
        if (isset($this->message) && !isset($data['text'])) {
            $data['text'] = $this->message;
        }
        
        // Добавляем chat_id если он установлен через chat() метод
        if (!isset($data['chat_id']) && isset($this->chat)) {
            // Если $this->chat является объектом модели, получаем chat_id из него
            if (is_object($this->chat)) {
                // Пытаемся получить chat_id из объекта (может быть TelegraphChat или другой объект)
                if (method_exists($this->chat, 'getChatId')) {
                    $data['chat_id'] = $this->chat->getChatId();
                } elseif (property_exists($this->chat, 'chat_id')) {
                    $data['chat_id'] = $this->chat->chat_id;
                } elseif (method_exists($this->chat, '__toString')) {
                    $data['chat_id'] = (string)$this->chat;
                } else {
                    // Если не можем извлечь, используем рефлексию
                    try {
                        $reflection = new \ReflectionObject($this->chat);
                        $property = $reflection->getProperty('chat_id');
                        $property->setAccessible(true);
                        $data['chat_id'] = $property->getValue($this->chat);
                    } catch (\Exception $e) {
                        // Если не удалось, пробуем просто преобразовать в строку
                        $data['chat_id'] = (string)$this->chat;
                    }
                }
            } else {
                // Если это строка или число, используем напрямую
                $data['chat_id'] = $this->chat;
            }
        }
        
        // Логируем отправку
        Log::info('Sending Telegram message via makeRequest()', [
            'endpoint' => $endpoint,
            'data_keys' => array_keys($data),
            'has_chat' => isset($this->chat),
            'chat_value' => $this->chat ?? null,
            'chat_id_in_data' => $data['chat_id'] ?? null,
            'bot_token_length' => strlen($this->getBotToken()),
        ]);
        
        // Используем наш makeRequest для отправки (он правильно обрабатывает файлы)
        try {
            // Проверяем, есть ли медиа файлы, которые требуют специальной обработки
            $mediaMethods = ['sendPhoto', 'sendVideo', 'sendDocument', 'sendAudio', 'sendVoice', 'sendVideoNote', 'sendAnimation', 'sendSticker', 'sendMediaGroup'];
            $mediaFields = ['photo', 'video', 'document', 'audio', 'voice', 'video_note', 'animation', 'sticker', 'media'];
            $hasMedia = in_array($endpoint, $mediaMethods) && !empty(array_intersect_key($data, array_flip($mediaFields)));
            
            if ($hasMedia) {
                // Для медиа делаем запрос напрямую, чтобы получить Response для TelegraphResponse
                $token = $this->getBotToken();
                $url = $this->buildApiUrl($token, $endpoint);
                
                // Специальная обработка для sendMediaGroup
                if ($endpoint === 'sendMediaGroup' && isset($data['media']) && is_array($data['media'])) {
                    $mediaFiles = [];
                    $mediaArray = [];
                    
                    foreach ($data['media'] as $index => $item) {
                        $mediaType = $item['type'] ?? 'photo';
                        $mediaPath = $item['media'] ?? '';
                        
                        $mediaItem = ['type' => $mediaType];
                        if (!empty($item['caption'])) {
                            $mediaItem['caption'] = $item['caption'];
                        }
                        
                        if (is_string($mediaPath) && str_starts_with($mediaPath, '/upload/')) {
                            $fullPath = public_path($mediaPath);
                            if (file_exists($fullPath)) {
                                $fileKey = $mediaType . $index;
                                $mediaFiles[$fileKey] = new \Illuminate\Http\File($fullPath);
                                $mediaItem['media'] = 'attach://' . $fileKey;
                            } else {
                                $mediaItem['media'] = url($mediaPath);
                            }
                        } else {
                            $mediaItem['media'] = $mediaPath;
                        }
                        
                        $mediaArray[] = $mediaItem;
                    }
                    
                    $http = Http::asMultipart();
                    foreach ($data as $key => $value) {
                        if ($key !== 'media') {
                            $http = $http->attach($key, (string)$value);
                        }
                    }
                    $http = $http->attach('media', json_encode($mediaArray), null, ['Content-Type' => 'application/json']);
                    foreach ($mediaFiles as $fileKey => $file) {
                        $http = $http->attach($fileKey, file_get_contents($file->getPathname()), $file->getFilename());
                    }
                    $response = $http->post($url);
                } else {
                    // Обработка одиночных файлов
                    $files = [];
                    $fileFields = ['photo', 'video', 'document', 'audio', 'voice', 'video_note', 'animation', 'sticker'];
                    
                    foreach ($fileFields as $field) {
                        if (isset($data[$field])) {
                            $value = $data[$field];
                            if ($value instanceof \Illuminate\Http\File || $value instanceof \Illuminate\Http\UploadedFile) {
                                $files[$field] = $value;
                                unset($data[$field]);
                            } elseif (is_string($value) && str_starts_with($value, '/upload/')) {
                                $fullPath = public_path($value);
                                if (file_exists($fullPath)) {
                                    $files[$field] = new \Illuminate\Http\File($fullPath);
                                    unset($data[$field]);
                                } else {
                                    $data[$field] = url($value);
                                }
                            }
                        }
                    }
                    
                    if (!empty($files)) {
                        $http = Http::asMultipart();
                        foreach ($data as $key => $value) {
                            if (is_array($value)) {
                                $http = $http->attach($key, json_encode($value), null, ['Content-Type' => 'application/json']);
                            } else {
                                $http = $http->attach($key, (string)$value);
                            }
                        }
                        foreach ($files as $field => $file) {
                            $http = $http->attach($field, file_get_contents($file->getPathname()), $file->getFilename());
                        }
                        $response = $http->post($url);
                    } else {
                        $response = Http::post($url, $data);
                    }
                }
                
                $telegraphResponse = TelegraphResponse::fromResponse($response);
                
                $result = $response->json();
                $isSuccessful = isset($result['ok']) && $result['ok'] === true;
                $logData = [
                    'endpoint' => $endpoint,
                    'success' => $isSuccessful,
                    'message_id' => $result['result']['message_id'] ?? ($result['result'][0]['message_id'] ?? null),
                ];
                
                if (!$isSuccessful) {
                    $logData['error'] = $result['description'] ?? $result['error_code'] ?? 'Unknown error';
                    $logData['full_response'] = $result;
                    Log::error('Telegram message send failed', $logData);
                } else {
                    Log::info('Telegram message sent', $logData);
                }
            } else {
                // Для обычных сообщений используем стандартный HTTP запрос
                $token = $this->getBotToken();
                $url = $this->buildApiUrl($token, $endpoint);
                
                try {
                    // Добавляем retry для сетевых ошибок (3 попытки с задержкой 1 секунда)
                    $response = Http::timeout(30)
                        ->retry(3, 1000, function ($exception) {
                            // Retry только для сетевых ошибок
                            $exceptionClass = get_class($exception);
                            return str_contains($exceptionClass, 'ConnectionException') 
                                || str_contains($exceptionClass, 'ConnectException')
                                || str_contains($exception->getMessage(), 'Connection refused')
                                || str_contains($exception->getMessage(), 'timeout');
                        })
                        ->post($url, $data);
                    
                    $telegraphResponse = TelegraphResponse::fromResponse($response);
                    
                    $result = $response->json();
                    $isSuccessful = isset($result['ok']) && $result['ok'] === true;
                    $logData = [
                        'endpoint' => $endpoint,
                        'success' => $isSuccessful,
                        'message_id' => $result['result']['message_id'] ?? null,
                    ];
                    
                    if (!$isSuccessful) {
                        $logData['error'] = $result['description'] ?? $result['error_code'] ?? 'Unknown error';
                        $logData['full_response'] = $result;
                        Log::error('Telegram message send failed', $logData);
                    } else {
                        Log::info('Telegram message sent', $logData);
                    }
                } catch (\Exception $e) {
                    // Обработка всех исключений, включая сетевые ошибки
                    $errorMessage = $e->getMessage();
                    $isConnectionError = str_contains($errorMessage, 'Connection refused')
                        || str_contains($errorMessage, 'Connection')
                        || str_contains($errorMessage, 'timeout')
                        || str_contains(get_class($e), 'ConnectionException')
                        || str_contains(get_class($e), 'ConnectException');
                    
                    Log::error('Exception while sending Telegram message', [
                        'endpoint' => $endpoint,
                        'error' => $errorMessage,
                        'exception_class' => get_class($e),
                        'is_connection_error' => $isConnectionError,
                        'trace' => $e->getTraceAsString(),
                    ]);
                    
                    if ($isConnectionError) {
                        throw new \Exception('Ошибка подключения к Telegram API. Проверьте интернет-соединение и попробуйте позже. Детали: ' . $errorMessage);
                    }
                    
                    throw $e;
                }
            }
            
            // Очищаем данные после отправки
            $this->data = [];
            $this->message = null;
            
            return $telegraphResponse;
        } catch (\Exception $e) {
            Log::error('Exception while sending Telegram message', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Установить текст сообщения
     * 
     * @param string $message Текст сообщения
     * @return Telegraph
     */
    public function message(string $message): Telegraph
    {
        // Вызываем родительский метод
        $result = parent::message($message);
        
        // Сохраняем текст для нашего использования
        $this->message = $message;
        $this->endpoint = 'sendMessage';
        
        if (!isset($this->data)) {
            $this->data = [];
        }
        $this->data['text'] = $message;
        
        return $this;
    }

    /**
     * Установить клавиатуру ответа (reply keyboard)
     * 
     * @param \DefStudio\Telegraph\Keyboard\Keyboard|callable|array $keyboard Клавиатура
     * @return Telegraph
     */
    public function keyboard($keyboard): Telegraph
    {
        // Вызываем родительский метод
        $result = parent::keyboard($keyboard);
        
        // Если передан массив, сохраняем для нашего использования
        if (is_array($keyboard)) {
            if (!isset($this->data)) {
                $this->data = [];
            }
            $this->data['reply_markup'] = [
                'keyboard' => $keyboard,
                'resize_keyboard' => true,
            ];
        }
        
        return $this;
    }

    /**
     * Удалить reply keyboard
     * 
     * @return $this
     */
    public function removeKeyboard(): self
    {
        if (!isset($this->data)) {
            $this->data = [];
        }
        $this->data['reply_markup'] = [
            'remove_keyboard' => true,
        ];
        return $this;
    }

    /**
     * Установить inline клавиатуру
     * Метод отсутствует в родительском классе, поэтому реализован здесь
     * 
     * @param array $inlineKeyboard Массив inline кнопок
     * @return $this
     */
    public function inlineKeyboard(array $inlineKeyboard): self
    {
        // Сохраняем для нашего использования
        if (!isset($this->data)) {
            $this->data = [];
        }
        $this->data['reply_markup'] = [
            'inline_keyboard' => $inlineKeyboard,
        ];
        
        return $this;
    }

    /**
     * Получить токен бота
     * 
     * @return string
     * @throws \Exception Если токен не установлен
     */
    protected function getBotToken(): string
    {
        // Пытаемся получить токен из установленной модели бота
        if ($this->botModel instanceof Bot && $this->botModel->token) {
            return $this->botModel->token;
        }
        
        // Используем рефлексию для доступа к защищенному свойству родительского класса
        try {
            $reflection = new \ReflectionClass(parent::class);
            if ($reflection->hasProperty('bot')) {
                $property = $reflection->getProperty('bot');
                $property->setAccessible(true);
                $bot = $property->getValue($this);
                if ($bot instanceof Bot && $bot->token) {
                    return $bot->token;
                }
            }
        } catch (\ReflectionException $e) {
            // Игнорируем ошибки рефлексии
        }
        
        // Пытаемся получить из конфигурации
        $token = config('telegraph.bot_token');
        if ($token) {
            return $token;
        }
        
        // Если токен не найден, выбрасываем исключение
        throw new \Exception('Telegram bot token is not set');
    }

    /**
     * Получить URL для API запросов
     * 
     * @param string $token Токен бота
     * @param string $method Метод API
     * @return string
     */
    protected function buildApiUrl(string $token, string $method): string
    {
        return "{$this->baseUrl}{$token}/{$method}";
    }

    /**
     * Выполнить запрос к Telegram API
     * Публичный метод для использования в контроллерах
     * 
     * @param string $method Метод Telegram API
     * @param array $data Данные для запроса
     * @return array
     */
    public function makeRequest(string $method, array $data = []): array
    {
        $token = $this->getBotToken();

        // Добавляем chat_id если он установлен через chat() метод
        if (!isset($data['chat_id']) && isset($this->chat)) {
            // Если $this->chat является объектом модели, получаем chat_id из него
            if (is_object($this->chat)) {
                // Пытаемся получить chat_id из объекта (может быть TelegraphChat или другой объект)
                if (method_exists($this->chat, 'getChatId')) {
                    $data['chat_id'] = $this->chat->getChatId();
                } elseif (property_exists($this->chat, 'chat_id')) {
                    $data['chat_id'] = $this->chat->chat_id;
                } elseif (method_exists($this->chat, '__toString')) {
                    $data['chat_id'] = (string)$this->chat;
                } else {
                    // Если не можем извлечь, используем рефлексию
                    try {
                        $reflection = new \ReflectionObject($this->chat);
                        $property = $reflection->getProperty('chat_id');
                        $property->setAccessible(true);
                        $data['chat_id'] = $property->getValue($this->chat);
                    } catch (\Exception $e) {
                        // Если не удалось, пробуем просто преобразовать в строку
                        $data['chat_id'] = (string)$this->chat;
                    }
                }
            } else {
                // Если это строка или число, используем напрямую
                $data['chat_id'] = $this->chat;
            }
        }
        
        // Проверяем, что chat_id установлен для методов, которые требуют его
        $methodsRequiringChatId = [
            'sendMessage', 'sendDice', 'sendPoll', 'sendVenue', 'sendContact',
            'editMessageText', 'editMessageCaption', 'deleteMessage',
            'pinChatMessage', 'unpinChatMessage', 'getChat', 'getChatMember',
            'setChatPhoto', 'deleteChatPhoto', 'setChatTitle', 'setChatDescription',
            'createChatInviteLink', 'revokeChatInviteLink', 'banChatMember',
            'unbanChatMember', 'restrictChatMember', 'promoteChatMember',
            'setChatPermissions', 'getChatAdministrators'
        ];
        
        if (in_array($method, $methodsRequiringChatId) && !isset($data['chat_id'])) {
            throw new \Exception("chat_id is required for method {$method}");
        }

        $url = $this->buildApiUrl($token, $method);
        
        // Специальная обработка для sendMediaGroup
        if ($method === 'sendMediaGroup' && isset($data['media']) && is_array($data['media'])) {
            $mediaFiles = [];
            $mediaArray = [];
            
            // Обрабатываем каждый элемент медиа
            foreach ($data['media'] as $index => $item) {
                $mediaType = $item['type'] ?? 'photo';
                $mediaPath = $item['media'] ?? '';
                
                $mediaItem = ['type' => $mediaType];
                if (!empty($item['caption'])) {
                    $mediaItem['caption'] = $item['caption'];
                }
                
                // Если это локальный файл, добавляем его для отправки
                if (is_string($mediaPath) && str_starts_with($mediaPath, '/upload/')) {
                    $fullPath = public_path($mediaPath);
                    if (file_exists($fullPath)) {
                        $fileKey = $mediaType . $index; // photo0, photo1, video0 и т.д.
                        $mediaFiles[$fileKey] = new \Illuminate\Http\File($fullPath);
                        $mediaItem['media'] = 'attach://' . $fileKey;
                    } else {
                        $mediaItem['media'] = url($mediaPath);
                    }
                } else {
                    $mediaItem['media'] = $mediaPath;
                }
                
                $mediaArray[] = $mediaItem;
            }
            
            // Отправляем через multipart/form-data
            $http = Http::asMultipart();
            
            // Добавляем chat_id и другие параметры
            foreach ($data as $key => $value) {
                if ($key !== 'media') {
                    $http = $http->attach($key, (string)$value);
                }
            }
            
            // Добавляем массив media как JSON
            $http = $http->attach('media', json_encode($mediaArray), null, ['Content-Type' => 'application/json']);
            
            // Добавляем файлы
            foreach ($mediaFiles as $fileKey => $file) {
                $http = $http->attach($fileKey, file_get_contents($file->getPathname()), $file->getFilename());
            }
            
            $response = $http->post($url);
        } else {
            // Обычная обработка для других методов
            $files = [];
            $fileFields = ['photo', 'video', 'document', 'audio', 'voice', 'video_note', 'animation', 'sticker'];
            
            foreach ($fileFields as $field) {
                if (isset($data[$field])) {
                    $value = $data[$field];
                    // Если это объект File или UploadedFile, сохраняем для attach
                    if ($value instanceof \Illuminate\Http\File || $value instanceof \Illuminate\Http\UploadedFile) {
                        $files[$field] = $value;
                        unset($data[$field]);
                    } elseif (is_string($value) && str_starts_with($value, '/upload/')) {
                        // Если это локальный путь, проверяем существование файла
                        $fullPath = public_path($value);
                        if (file_exists($fullPath)) {
                            $files[$field] = new \Illuminate\Http\File($fullPath);
                            unset($data[$field]);
                        } else {
                            // Если файл не найден, используем полный URL
                            $data[$field] = url($value);
                        }
                    }
                }
            }
            
            // Если есть файлы, используем attach для multipart/form-data
            if (!empty($files)) {
                $http = Http::asMultipart();
                
                // Добавляем обычные параметры как части multipart
                foreach ($data as $key => $value) {
                    if (is_array($value)) {
                        // Для массивов используем JSON
                        $http = $http->attach($key, json_encode($value), null, ['Content-Type' => 'application/json']);
                    } else {
                        // Для обычных значений используем attach как строку
                        $http = $http->attach($key, (string)$value);
                    }
                }
                
                // Добавляем файлы
                foreach ($files as $field => $file) {
                    $http = $http->attach($field, file_get_contents($file->getPathname()), $file->getFilename());
                }
                
                $response = $http->post($url);
            } else {
                $response = Http::post($url, $data);
            }
        }
        
        if (!$response->successful()) {
            $errorBody = $response->body();
            $errorData = $response->json();
            
            // Пытаемся извлечь описание ошибки из JSON ответа
            $errorMessage = $errorData['description'] ?? $errorBody;
            $errorCode = $errorData['error_code'] ?? $response->status();
            
            throw new \Exception("Telegram API error ({$errorCode}): {$errorMessage}");
        }

        $result = $response->json();
        
        // Если ответ не является массивом, это ошибка
        if (!is_array($result)) {
            Log::error('Telegram API вернул неверный формат ответа', [
                'method' => $method,
                'response_body' => $response->body(),
                'status' => $response->status(),
            ]);
            throw new \Exception("Telegram API error: Invalid response format");
        }
        
        // Проверяем, что ответ содержит поле 'ok'
        if (isset($result['ok']) && !$result['ok']) {
            $errorMessage = $result['description'] ?? 'Unknown error';
            $errorCode = $result['error_code'] ?? null;
            
            Log::warning('Telegram API вернул ошибку', [
                'method' => $method,
                'error_code' => $errorCode,
                'error_message' => $errorMessage,
                'full_response' => $result,
            ]);
            
            throw new \Exception("Telegram API error" . ($errorCode ? " ({$errorCode})" : '') . ": {$errorMessage}");
        }

        return $result;
    }

    /**
     * Отправить кубик (dice)
     * 
     * @param string|null $emoji Эмодзи кубика (🎲, 🎯, 🏀, ⚽, 🎳, 🎰)
     * @param int|null $replyToMessageId ID сообщения для ответа
     * @return $this
     */
    public function sendDice(?string $emoji = null, ?int $replyToMessageId = null): self
    {
        $data = [];
        
        if ($emoji) {
            $data['emoji'] = $emoji;
        }
        
        if ($replyToMessageId) {
            $data['reply_to_message_id'] = $replyToMessageId;
        }

        $this->endpoint = 'sendDice';
        $this->data = array_merge($this->data ?? [], $data);

        return $this;
    }

    /**
     * Отправить опрос (poll)
     * 
     * @param string $question Вопрос
     * @param array $options Варианты ответов
     * @param bool $isAnonymous Анонимный опрос
     * @param string|null $type Тип опроса (quiz или regular)
     * @return $this
     */
    public function sendPoll(string $question, array $options, bool $isAnonymous = true, ?string $type = null): self
    {
        $data = [
            'question' => $question,
            'options' => $options,
            'is_anonymous' => $isAnonymous,
        ];

        if ($type) {
            $data['type'] = $type;
        }

        $this->endpoint = 'sendPoll';
        $this->data = array_merge($this->data ?? [], $data);

        return $this;
    }

    /**
     * Ответить на callback query (обязательно для всех callback_query)
     * 
     * @param string $callbackQueryId ID callback query
     * @param string|null $text Текст ответа (опционально)
     * @param bool $showAlert Показать алерт вместо уведомления
     * @param string|null $url URL для открытия
     * @param int $cacheTime Время кэширования ответа в секундах
     * @return array
     */
    public function answerCallbackQuery(
        string $callbackQueryId,
        ?string $text = null,
        bool $showAlert = false,
        ?string $callbackUrl = null,
        int $cacheTime = 0
    ): array {
        $token = $this->getBotToken();
        $apiUrl = $this->buildApiUrl($token, 'answerCallbackQuery');

        $data = [
            'callback_query_id' => $callbackQueryId,
        ];

        if ($text !== null) {
            $data['text'] = $text;
        }

        if ($showAlert) {
            $data['show_alert'] = true;
        }

        if ($callbackUrl !== null) {
            $data['url'] = $callbackUrl;
        }

        if ($cacheTime > 0) {
            $data['cache_time'] = $cacheTime;
        }

        try {
            $response = Http::post($apiUrl, $data);
            $result = $response->json();

            if (!$response->successful() || (isset($result['ok']) && !$result['ok'])) {
                Log::error('Failed to answer callback query', [
                    'callback_query_id' => $callbackQueryId,
                    'response' => $result,
                ]);
            } else {
                Log::debug('Callback query answered', [
                    'callback_query_id' => $callbackQueryId,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Exception while answering callback query', [
                'callback_query_id' => $callbackQueryId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Отправить локацию (venue)
     * 
     * @param float $latitude Широта
     * @param float $longitude Долгота
     * @param string $title Название места
     * @param string $address Адрес
     * @param string|null $foursquareId ID Foursquare
     * @return $this
     */
    public function sendVenue(float $latitude, float $longitude, string $title, string $address, ?string $foursquareId = null): self
    {
        $data = [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'title' => $title,
            'address' => $address,
        ];

        if ($foursquareId) {
            $data['foursquare_id'] = $foursquareId;
        }

        $this->endpoint = 'sendVenue';
        $this->data = array_merge($this->data ?? [], $data);

        return $this;
    }

    /**
     * Отправить контакт
     * 
     * @param string $phoneNumber Номер телефона
     * @param string $firstName Имя
     * @param string|null $lastName Фамилия
     * @param string|null $vcard vCard данные
     * @return $this
     */
    public function sendContact(string $phoneNumber, string $firstName, ?string $lastName = null, ?string $vcard = null): self
    {
        $data = [
            'phone_number' => $phoneNumber,
            'first_name' => $firstName,
        ];

        if ($lastName) {
            $data['last_name'] = $lastName;
        }

        if ($vcard) {
            $data['vcard'] = $vcard;
        }

        $this->endpoint = 'sendContact';
        $this->data = array_merge($this->data ?? [], $data);

        return $this;
    }

    /**
     * Редактировать текст сообщения через API
     * 
     * @param int|null $messageId ID сообщения
     * @param string $text Новый текст
     * @param array|null $replyMarkup Клавиатура
     * @param string|null $inlineMessageId ID inline сообщения (альтернатива message_id)
     * @return array
     */
    public function editMessageTextApi(?int $messageId, string $text, ?array $replyMarkup = null, ?string $inlineMessageId = null): array
    {
        $data = [
            'text' => $text,
        ];

        if ($messageId !== null) {
            $data['message_id'] = $messageId;
        } elseif ($inlineMessageId !== null) {
            $data['inline_message_id'] = $inlineMessageId;
        }

        if ($replyMarkup) {
            $data['reply_markup'] = $replyMarkup;
        }

        return $this->makeRequest('editMessageText', $data);
    }

    /**
     * Редактировать подпись к медиа через API
     * 
     * @param int|null $messageId ID сообщения
     * @param string|null $caption Новая подпись
     * @param array|null $replyMarkup Клавиатура
     * @param string|null $inlineMessageId ID inline сообщения (альтернатива message_id)
     * @return array
     */
    public function editMessageCaptionApi(?int $messageId, ?string $caption = null, ?array $replyMarkup = null, ?string $inlineMessageId = null): array
    {
        $data = [];

        if ($messageId !== null) {
            $data['message_id'] = $messageId;
        } elseif ($inlineMessageId !== null) {
            $data['inline_message_id'] = $inlineMessageId;
        }

        if ($caption !== null) {
            $data['caption'] = $caption;
        }

        if ($replyMarkup) {
            $data['reply_markup'] = $replyMarkup;
        }

        return $this->makeRequest('editMessageCaption', $data);
    }

    /**
     * Удалить сообщение через API
     * 
     * @param int|null $messageId ID сообщения (если null, удаляется последнее сообщение бота)
     * @return array
     */
    public function deleteMessageApi(?int $messageId): array
    {
        $data = [];

        if ($messageId !== null) {
            $data['message_id'] = $messageId;
        }

        return $this->makeRequest('deleteMessage', $data);
    }

    /**
     * Получить информацию о чате через API
     * 
     * @return array
     */
    public function getChatInfo(): array
    {
        return $this->makeRequest('getChat');
    }

    /**
     * Получить информацию об участнике чата через API
     * 
     * @param int $userId ID пользователя
     * @return array
     */
    public function getChatMemberApi(int $userId): array
    {
        $data = [
            'user_id' => $userId,
        ];

        return $this->makeRequest('getChatMember', $data);
    }

    /**
     * Установить фото чата через API
     * 
     * @param string $photoPath Путь к файлу фото
     * @return array
     */
    public function setChatPhotoApi(string $photoPath): array
    {
        $token = $this->getBotToken();
        $url = $this->buildApiUrl($token, 'setChatPhoto');
        
        $response = Http::attach('photo', file_get_contents($photoPath), basename($photoPath))
            ->post($url);
        
        if (!$response->successful()) {
            throw new \Exception("Telegram API error: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Удалить фото чата через API
     * 
     * @return array
     */
    public function deleteChatPhotoApi(): array
    {
        return $this->makeRequest('deleteChatPhoto');
    }

    /**
     * Установить название чата через API
     * 
     * @param string $title Новое название
     * @return array
     */
    public function setChatTitleApi(string $title): array
    {
        $data = [
            'title' => $title,
        ];

        return $this->makeRequest('setChatTitle', $data);
    }

    /**
     * Установить описание чата через API
     * 
     * @param string $description Новое описание
     * @return array
     */
    public function setChatDescriptionApi(string $description): array
    {
        $data = [
            'description' => $description,
        ];

        return $this->makeRequest('setChatDescription', $data);
    }

    /**
     * Закрепить сообщение через API
     * 
     * @param int|null $messageId ID сообщения (если null, закрепляется последнее сообщение бота)
     * @param bool $disableNotification Отключить уведомление
     * @return array
     */
    public function pinChatMessageApi(?int $messageId, bool $disableNotification = false): array
    {
        $data = [
            'disable_notification' => $disableNotification,
        ];

        if ($messageId !== null) {
            $data['message_id'] = $messageId;
        }

        return $this->makeRequest('pinChatMessage', $data);
    }

    /**
     * Открепить сообщение через API
     * 
     * @param int|null $messageId ID сообщения (если null, открепляет все)
     * @return array
     */
    public function unpinChatMessageApi(?int $messageId = null): array
    {
        $data = [];
        
        if ($messageId !== null) {
            $data['message_id'] = $messageId;
        }

        return $this->makeRequest('unpinChatMessage', $data);
    }

    /**
     * Получить список администраторов чата через API
     * 
     * @return array
     */
    public function getChatAdministratorsApi(): array
    {
        return $this->makeRequest('getChatAdministrators');
    }

    /**
     * Создать пригласительную ссылку через API
     * 
     * @param string|null $name Название ссылки
     * @param \DateTime|null $expireDate Дата истечения
     * @param int|null $memberLimit Лимит участников
     * @param bool $createsJoinRequest Создавать запрос на присоединение
     * @return array
     */
    public function createChatInviteLinkApi(
        ?string $name = null,
        ?\DateTime $expireDate = null,
        ?int $memberLimit = null,
        bool $createsJoinRequest = false
    ): array {
        $data = [
            'creates_join_request' => $createsJoinRequest,
        ];

        if ($name) {
            $data['name'] = $name;
        }

        if ($expireDate) {
            $data['expire_date'] = $expireDate->getTimestamp();
        }

        if ($memberLimit !== null) {
            $data['member_limit'] = $memberLimit;
        }

        return $this->makeRequest('createChatInviteLink', $data);
    }

    /**
     * Отозвать пригласительную ссылку через API
     * 
     * @param string $inviteLink Пригласительная ссылка
     * @return array
     */
    public function revokeChatInviteLinkApi(string $inviteLink): array
    {
        $data = [
            'invite_link' => $inviteLink,
        ];

        return $this->makeRequest('revokeChatInviteLink', $data);
    }

    /**
     * Забанить участника чата через API
     * 
     * @param int $userId ID пользователя
     * @param \DateTime|null $untilDate До какой даты
     * @param bool $revokeMessages Удалить сообщения
     * @return array
     */
    public function banChatMemberApi(int $userId, ?\DateTime $untilDate = null, bool $revokeMessages = false): array
    {
        $data = [
            'user_id' => $userId,
            'revoke_messages' => $revokeMessages,
        ];

        if ($untilDate) {
            $data['until_date'] = $untilDate->getTimestamp();
        }

        return $this->makeRequest('banChatMember', $data);
    }

    /**
     * Разбанить участника чата через API
     * 
     * @param int $userId ID пользователя
     * @param bool $onlyIfBanned Разбанить только если забанен
     * @return array
     */
    public function unbanChatMemberApi(int $userId, bool $onlyIfBanned = false): array
    {
        $data = [
            'user_id' => $userId,
            'only_if_banned' => $onlyIfBanned,
        ];

        return $this->makeRequest('unbanChatMember', $data);
    }

    /**
     * Ограничить участника чата через API
     * 
     * @param int $userId ID пользователя
     * @param array $permissions Права доступа
     * @param \DateTime|null $untilDate До какой даты
     * @return array
     */
    public function restrictChatMemberApi(int $userId, array $permissions, ?\DateTime $untilDate = null): array
    {
        $data = [
            'user_id' => $userId,
            'permissions' => $permissions,
        ];

        if ($untilDate) {
            $data['until_date'] = $untilDate->getTimestamp();
        }

        return $this->makeRequest('restrictChatMember', $data);
    }

    /**
     * Повысить участника до администратора через API
     * 
     * @param int $userId ID пользователя
     * @param bool $isAnonymous Анонимный администратор
     * @param bool $canManageChat Может управлять чатом
     * @param bool $canPostMessages Может публиковать сообщения
     * @param bool $canEditMessages Может редактировать сообщения
     * @param bool $canDeleteMessages Может удалять сообщения
     * @param bool $canManageVideoChats Может управлять видеозвонками
     * @param bool $canRestrictMembers Может ограничивать участников
     * @param bool $canPromoteMembers Может повышать участников
     * @param bool $canChangeInfo Может изменять информацию
     * @param bool $canInviteUsers Может приглашать пользователей
     * @param bool $canPinMessages Может закреплять сообщения
     * @return array
     */
    public function promoteChatMemberApi(
        int $userId,
        bool $isAnonymous = false,
        bool $canManageChat = false,
        bool $canPostMessages = false,
        bool $canEditMessages = false,
        bool $canDeleteMessages = false,
        bool $canManageVideoChats = false,
        bool $canRestrictMembers = false,
        bool $canPromoteMembers = false,
        bool $canChangeInfo = false,
        bool $canInviteUsers = false,
        bool $canPinMessages = false
    ): array {
        $data = [
            'user_id' => $userId,
            'is_anonymous' => $isAnonymous,
            'can_manage_chat' => $canManageChat,
            'can_post_messages' => $canPostMessages,
            'can_edit_messages' => $canEditMessages,
            'can_delete_messages' => $canDeleteMessages,
            'can_manage_video_chats' => $canManageVideoChats,
            'can_restrict_members' => $canRestrictMembers,
            'can_promote_members' => $canPromoteMembers,
            'can_change_info' => $canChangeInfo,
            'can_invite_users' => $canInviteUsers,
            'can_pin_messages' => $canPinMessages,
        ];

        return $this->makeRequest('promoteChatMember', $data);
    }

    /**
     * Установить права доступа для участников через API
     * 
     * @param array $permissions Права доступа
     * @return array
     */
    public function setChatPermissionsApi(array $permissions): array
    {
        $data = [
            'permissions' => $permissions,
        ];

        return $this->makeRequest('setChatPermissions', $data);
    }

    /**
     * Получить информацию о файле через API
     * 
     * @param string $fileId ID файла
     * @return array
     */
    public function getFileApi(string $fileId): array
    {
        $data = [
            'file_id' => $fileId,
        ];

        return $this->makeRequest('getFile', $data);
    }

    /**
     * Скачать файл
     * 
     * @param string $fileId ID файла
     * @param string $savePath Путь для сохранения
     * @return string|false Путь к сохраненному файлу или false при ошибке
     */
    public function downloadFile(string $fileId, string $savePath): string|false
    {
        $fileInfo = $this->getFileApi($fileId);
        
        if (!isset($fileInfo['result']['file_path'])) {
            return false;
        }

        $filePath = $fileInfo['result']['file_path'];
        $token = $this->getBotToken();
        $fileUrl = "https://api.telegram.org/file/bot{$token}/{$filePath}";
        
        $fileContent = Http::get($fileUrl)->body();
        
        if (!file_exists(dirname($savePath))) {
            mkdir(dirname($savePath), 0755, true);
        }
        
        file_put_contents($savePath, $fileContent);
        
        return $savePath;
    }

    /**
     * Получить информацию о боте через API
     * 
     * @return array
     */
    public function getMeApi(): array
    {
        return $this->makeRequest('getMe');
    }

    /**
     * Получить обновления через API
     * 
     * @param int|null $offset Смещение
     * @param int|null $limit Лимит
     * @param int|null $timeout Таймаут
     * @param array $allowedUpdates Разрешенные типы обновлений
     * @return array
     */
    public function getUpdatesApi(
        ?int $offset = null,
        ?int $limit = null,
        ?int $timeout = null,
        array $allowedUpdates = []
    ): array {
        $data = [];

        if ($offset !== null) {
            $data['offset'] = $offset;
        }

        if ($limit !== null) {
            $data['limit'] = $limit;
        }

        if ($timeout !== null) {
            $data['timeout'] = $timeout;
        }

        if (!empty($allowedUpdates)) {
            $data['allowed_updates'] = $allowedUpdates;
        }

        return $this->makeRequest('getUpdates', $data);
    }

    /**
     * Установить webhook через API
     * 
     * @param string $url URL для webhook
     * @param string|null $certificate Путь к сертификату
     * @param string|null $ipAddress IP адрес
     * @param int|null $maxConnections Максимальное количество соединений
     * @param array $allowedUpdates Разрешенные типы обновлений
     * @param bool $dropPendingUpdates Удалить ожидающие обновления
     * @param string|null $secretToken Секретный токен
     * @return array
     */
    public function setWebhookApi(
        string $url,
        ?string $certificate = null,
        ?string $ipAddress = null,
        ?int $maxConnections = null,
        array $allowedUpdates = [],
        bool $dropPendingUpdates = false,
        ?string $secretToken = null
    ): array {
        $data = [
            'url' => $url,
            'drop_pending_updates' => $dropPendingUpdates,
        ];

        if ($certificate) {
            $data['certificate'] = $certificate;
        }

        if ($ipAddress) {
            $data['ip_address'] = $ipAddress;
        }

        if ($maxConnections !== null) {
            $data['max_connections'] = $maxConnections;
        }

        if (!empty($allowedUpdates)) {
            $data['allowed_updates'] = $allowedUpdates;
        }

        if ($secretToken) {
            $data['secret_token'] = $secretToken;
        }

        return $this->makeRequest('setWebhook', $data);
    }

    /**
     * Удалить webhook через API
     * 
     * @param bool $dropPendingUpdates Удалить ожидающие обновления
     * @return array
     */
    public function deleteWebhookApi(bool $dropPendingUpdates = false): array
    {
        $data = [
            'drop_pending_updates' => $dropPendingUpdates,
        ];

        return $this->makeRequest('deleteWebhook', $data);
    }

    /**
     * Получить информацию о webhook через API
     * 
     * @return array
     */
    public function getWebhookInfoApi(): array
    {
        return $this->makeRequest('getWebhookInfo');
    }

    /**
     * Отправить фото
     * 
     * Примеры использования:
     * - $telegraph->photo('/upload/obshhaia/692030a474249_1763717284.png')
     * - $telegraph->photo('https://example.com/image.jpg')
     * - $telegraph->photo('AgACAgIAAxkBAAIBY2YtQ2QAAUf8BQABHxYAAQABAgADBAADMwEAAfsBAAIfBAAB')
     * 
     * @param string $path Путь к файлу или URL или file_id
     * @param string|null $filename Имя файла (опционально)
     * @return $this
     */
    public function photo(string $path, ?string $filename = null): self
    {
        $this->endpoint = 'sendPhoto';
        if (!isset($this->data)) {
            $this->data = [];
        }
        // Сохраняем путь как есть, обработка будет в makeRequest() или send()
        $this->data['photo'] = $path;
        return $this;
    }

    /**
     * Отправить видео
     * 
     * Примеры использования:
     * - $telegraph->video('/upload/video/69233d131b3ad_1763917075.mp4')
     * - $telegraph->video('https://example.com/video.mp4')
     * - $telegraph->video('BAACAgIAAxkBAAIBY2YtQ2QAAUf8BQABHxYAAQABAgADBAADMwEAAfsBAAIfBAAB')
     * 
     * @param string $path Путь к файлу или URL или file_id
     * @param string|null $filename Имя файла (опционально)
     * @return $this
     */
    public function video(string $path, ?string $filename = null): self
    {
        $this->endpoint = 'sendVideo';
        if (!isset($this->data)) {
            $this->data = [];
        }
        $this->data['video'] = $path;
        return $this;
    }

    /**
     * Отправить документ
     * 
     * Примеры использования:
     * - $telegraph->document('/upload/dokumenty/69233d3782780_1763917111.html')
     * - $telegraph->document('https://example.com/document.pdf')
     * - $telegraph->document('BQACAgIAAxkBAAIBY2YtQ2QAAUf8BQABHxYAAQABAgADBAADMwEAAfsBAAIfBAAB')
     * 
     * @param string $path Путь к файлу или URL или file_id
     * @param string|null $filename Имя файла (опционально)
     * @return $this
     */
    public function document(string $path, ?string $filename = null): self
    {
        $this->endpoint = 'sendDocument';
        if (!isset($this->data)) {
            $this->data = [];
        }
        $this->data['document'] = $path;
        return $this;
    }

    /**
     * Отправить аудио
     * 
     * Примеры использования:
     * - $telegraph->audio('/upload/audio/example.mp3')
     * - $telegraph->audio('https://example.com/audio.mp3')
     * - $telegraph->audio('CQACAgIAAxkBAAIBY2YtQ2QAAUf8BQABHxYAAQABAgADBAADMwEAAfsBAAIfBAAB')
     * 
     * @param string $path Путь к файлу или URL или file_id
     * @param string|null $filename Имя файла (опционально)
     * @return $this
     */
    public function audio(string $path, ?string $filename = null): self
    {
        $this->endpoint = 'sendAudio';
        if (!isset($this->data)) {
            $this->data = [];
        }
        $this->data['audio'] = $path;
        return $this;
    }

    /**
     * Отправить голосовое сообщение
     * Совместимо с родительским классом DefStudio\Telegraph\Telegraph
     * 
     * Примеры использования:
     * - $telegraph->voice('/upload/voice/example.ogg')
     * - $telegraph->voice('https://example.com/voice.ogg')
     * - $telegraph->voice('AwACAgIAAxkBAAIBY2YtQ2QAAUf8BQABHxYAAQABAgADBAADMwEAAfsBAAIfBAAB')
     * 
     * @param string $path Путь к файлу или URL или file_id
     * @param string|null $filename Имя файла (опционально)
     * @return $this
     */
    public function voice(string $path, ?string $filename = null): self
    {
        $this->endpoint = 'sendVoice';
        if (!isset($this->data)) {
            $this->data = [];
        }
        $this->data['voice'] = $path;
        return $this;
    }

    /**
     * Отправить видео-кружок
     * 
     * @param string $videoNote URL файла или file_id
     * @return $this
     */
    public function videoNote(string $videoNote): self
    {
        $this->endpoint = 'sendVideoNote';
        if (!isset($this->data)) {
            $this->data = [];
        }
        $this->data['video_note'] = $videoNote;
        return $this;
    }

    /**
     * Отправить анимацию/GIF
     * Совместимо с родительским классом DefStudio\Telegraph\Telegraph
     * 
     * Примеры использования:
     * - $telegraph->animation('/upload/obshhaia/692030bfe4a64_1763717311.png')
     * - $telegraph->animation('https://example.com/animation.gif')
     * - $telegraph->animation('CgACAgIAAxkBAAIBY2YtQ2QAAUf8BQABHxYAAQABAgADBAADMwEAAfsBAAIfBAAB')
     * 
     * @param string $path Путь к файлу или URL или file_id
     * @param string|null $filename Имя файла (опционально)
     * @return $this
     */
    public function animation(string $path, ?string $filename = null): self
    {
        $this->endpoint = 'sendAnimation';
        if (!isset($this->data)) {
            $this->data = [];
        }
        $this->data['animation'] = $path;
        return $this;
    }

    /**
     * Отправить стикер
     * Совместимо с родительским классом DefStudio\Telegraph\Telegraph
     * 
     * Примеры использования:
     * - $telegraph->sticker('/upload/stickers/example.webp')
     * - $telegraph->sticker('https://example.com/sticker.webp')
     * - $telegraph->sticker('CAACAgIAAxkBAAIBY2YtQ2QAAUf8BQABHxYAAQABAgADBAADMwEAAfsBAAIfBAAB')
     * 
     * @param string $path Путь к файлу или URL или file_id
     * @param string|null $filename Имя файла (опционально)
     * @return $this
     */
    public function sticker(string $path, ?string $filename = null): self
    {
        $this->endpoint = 'sendSticker';
        if (!isset($this->data)) {
            $this->data = [];
        }
        $this->data['sticker'] = $path;
        return $this;
    }

    /**
     * Отправить локацию
     * 
     * @param float $latitude Широта
     * @param float $longitude Долгота
     * @return $this
     */
    public function location(float $latitude, float $longitude): self
    {
        $this->endpoint = 'sendLocation';
        if (!isset($this->data)) {
            $this->data = [];
        }
        $this->data['latitude'] = $latitude;
        $this->data['longitude'] = $longitude;
        return $this;
    }

    /**
     * Установить подпись к медиа
     * 
     * @param string|null $caption Подпись
     * @return $this
     */
    public function caption(?string $caption): self
    {
        if (!isset($this->data)) {
            $this->data = [];
        }
        if ($caption !== null) {
            $this->data['caption'] = $caption;
        }
        return $this;
    }

    /**
     * Установить команды бота (setMyCommands)
     * Команды будут отображаться в меню бота при нажатии "/"
     * 
     * @param array $commands Массив команд, каждая команда должна содержать:
     *                        - 'command' - название команды (без "/", 1-32 символа)
     *                        - 'description' - описание команды (3-256 символов)
     * @param array|null $scope Область действия команд (опционально)
     * @param string|null $languageCode Код языка для локализации (опционально)
     * @return TelegraphResponse
     * @see https://core.telegram.org/bots/api#setmycommands
     */
    public function setMyCommands(array $commands, ?array $scope = null, ?string $languageCode = null): TelegraphResponse
    {
        $data = [
            'commands' => $commands,
        ];

        if ($scope !== null) {
            $data['scope'] = $scope;
        }

        if ($languageCode !== null) {
            $data['language_code'] = $languageCode;
        }

        return $this->makeRequest('setMyCommands', $data);
    }

    /**
     * Получить список команд бота (getMyCommands)
     * 
     * @param array|null $scope Область действия команд (опционально)
     * @param string|null $languageCode Код языка для локализации (опционально)
     * @return TelegraphResponse
     * @see https://core.telegram.org/bots/api#getmycommands
     */
    public function getMyCommands(?array $scope = null, ?string $languageCode = null): TelegraphResponse
    {
        $data = [];

        if ($scope !== null) {
            $data['scope'] = $scope;
        }

        if ($languageCode !== null) {
            $data['language_code'] = $languageCode;
        }

        return $this->makeRequest('getMyCommands', $data);
    }

    /**
     * Удалить команды бота (deleteMyCommands)
     * 
     * @param array|null $scope Область действия команд (опционально)
     * @param string|null $languageCode Код языка для локализации (опционально)
     * @return TelegraphResponse
     * @see https://core.telegram.org/bots/api#deletemycommands
     */
    public function deleteMyCommands(?array $scope = null, ?string $languageCode = null): TelegraphResponse
    {
        $data = [];

        if ($scope !== null) {
            $data['scope'] = $scope;
        }

        if ($languageCode !== null) {
            $data['language_code'] = $languageCode;
        }

        return $this->makeRequest('deleteMyCommands', $data);
    }

    /**
     * Установить кнопку меню бота (setChatMenuButton)
     * Настраивает кнопку меню, которая отображается рядом с полем ввода
     * 
     * Этот метод использует прямой вызов API, чтобы избежать конфликта с родительским классом
     * 
     * @param array|null $menuButton Объект кнопки меню:
     *                                - 'type' => 'commands' - показать команды
     *                                - 'type' => 'web_app' - показать веб-приложение
     *                                - 'type' => 'default' - использовать дефолтную кнопку
     * @param string|int|null $chatId ID чата (опционально, по умолчанию для всех чатов)
     * @return array
     * @see https://core.telegram.org/bots/api#setchatmenubutton
     */
    public function setChatMenuButtonDirect(?array $menuButton = null, $chatId = null): array
    {
        $data = [];

        // Если menuButton не указан, используем дефолтный (команды)
        if ($menuButton === null) {
            $data['menu_button'] = [
                'type' => 'commands',
            ];
        } else {
            $data['menu_button'] = $menuButton;
        }

        if ($chatId !== null) {
            $data['chat_id'] = $chatId;
        }

        return $this->makeRequest('setChatMenuButton', $data);
    }

    /**
     * Получить текущую настройку кнопки меню бота (getChatMenuButton)
     * 
     * Этот метод использует прямой вызов API, чтобы избежать конфликта с родительским классом
     * 
     * @param string|int|null $chatId ID чата (опционально)
     * @return array
     * @see https://core.telegram.org/bots/api#getchatmenubutton
     */
    public function getChatMenuButtonDirect($chatId = null): array
    {
        $data = [];

        if ($chatId !== null) {
            $data['chat_id'] = $chatId;
        }

        return $this->makeRequest('getChatMenuButton', $data, 'GET');
    }
}


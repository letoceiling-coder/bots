# Расширенный Telegraph для Telegram Bot API

## Установка

1. Установите пакет через Composer:
```bash
composer require defstudio/telegraph
```

2. Опубликуйте миграции:
```bash
php artisan vendor:publish --tag="telegraph-migrations"
php artisan migrate
```

3. Опубликуйте конфигурацию (опционально):
```bash
php artisan vendor:publish --tag="telegraph-config"
```

## Использование

### Базовое использование ExtendedTelegraph

```php
use App\Services\ExtendedTelegraph;
use App\Models\Bot;

// Получить бота из базы данных
$bot = Bot::find(1);

// Создать экземпляр ExtendedTelegraph
$telegraph = new ExtendedTelegraph();
$telegraph->bot = $bot;

// Отправить сообщение
$telegraph->chat('123456789')
    ->message('Привет!')
    ->send();

// Отправить кубик
$telegraph->chat('123456789')
    ->sendDice('🎲')
    ->send();

// Отправить опрос
$telegraph->chat('123456789')
    ->sendPoll('Какой ваш любимый язык?', ['PHP', 'JavaScript', 'Python'])
    ->send();
```

### Использование TelegramBotService

```php
use App\Services\TelegramBotService;
use App\Models\Bot;

$service = new TelegramBotService();

// Отправить сообщение
$service->sendMessage(1, '123456789', 'Привет!');

// Отправить кубик
$service->sendDice(1, '123456789', '🎲');

// Отправить опрос
$service->sendPoll(1, '123456789', 'Вопрос?', ['Вариант 1', 'Вариант 2']);

// Получить информацию о боте
$info = $service->getBotInfo(1);

// Удалить сообщение
$service->deleteMessage(1, '123456789', 12345);
```

### Дополнительные методы

#### Работа с чатами

```php
use App\Services\ExtendedTelegraph;
use App\Models\Bot;

$bot = Bot::find(1);
$telegraph = new ExtendedTelegraph();
$telegraph->bot = $bot;
$telegraph->chat('123456789');

// Получить информацию о чате
$chatInfo = $telegraph->getChat();

// Получить информацию об участнике
$memberInfo = $telegraph->getChatMember(123456);

// Установить название чата
$telegraph->setChatTitle('Новое название');

// Установить описание чата
$telegraph->setChatDescription('Описание чата');

// Закрепить сообщение
$telegraph->pinChatMessage(12345);

// Открепить сообщение
$telegraph->unpinChatMessage(12345);
```

#### Управление участниками

```php
// Забанить участника
$telegraph->banChatMember(123456);

// Разбанить участника
$telegraph->unbanChatMember(123456);

// Ограничить участника
$telegraph->restrictChatMember(123456, [
    'can_send_messages' => false,
    'can_send_media_messages' => false,
]);

// Повысить до администратора
$telegraph->promoteChatMember(
    123456,
    isAnonymous: false,
    canManageChat: true,
    canDeleteMessages: true
);
```

#### Работа с файлами

```php
// Получить информацию о файле
$fileInfo = $telegraph->getFile('file_id_here');

// Скачать файл
$savedPath = $telegraph->downloadFile('file_id_here', storage_path('app/files/file.jpg'));
```

#### Webhook

```php
// Установить webhook
$telegraph->setWebhook('https://example.com/webhook');

// Получить информацию о webhook
$webhookInfo = $telegraph->getWebhookInfo();

// Удалить webhook
$telegraph->deleteWebhook();
```

## Доступные методы

### Отправка сообщений
- `sendDice()` - Отправить кубик
- `sendPoll()` - Отправить опрос
- `sendVenue()` - Отправить локацию
- `sendContact()` - Отправить контакт

### Редактирование сообщений
- `editMessageText()` - Редактировать текст
- `editMessageCaption()` - Редактировать подпись

### Управление сообщениями
- `deleteMessage()` - Удалить сообщение

### Работа с чатами
- `getChat()` - Получить информацию о чате
- `getChatMember()` - Получить информацию об участнике
- `setChatPhoto()` - Установить фото чата
- `deleteChatPhoto()` - Удалить фото чата
- `setChatTitle()` - Установить название
- `setChatDescription()` - Установить описание
- `pinChatMessage()` - Закрепить сообщение
- `unpinChatMessage()` - Открепить сообщение
- `getChatAdministrators()` - Получить администраторов

### Управление участниками
- `banChatMember()` - Забанить участника
- `unbanChatMember()` - Разбанить участника
- `restrictChatMember()` - Ограничить участника
- `promoteChatMember()` - Повысить до администратора
- `setChatPermissions()` - Установить права доступа

### Пригласительные ссылки
- `createChatInviteLink()` - Создать ссылку
- `revokeChatInviteLink()` - Отозвать ссылку

### Работа с файлами
- `getFile()` - Получить информацию о файле
- `downloadFile()` - Скачать файл

### Информация о боте
- `getMe()` - Получить информацию о боте
- `getUpdates()` - Получить обновления

### Webhook
- `setWebhook()` - Установить webhook
- `deleteWebhook()` - Удалить webhook
- `getWebhookInfo()` - Получить информацию о webhook

## Документация

- [Официальная документация Telegraph](https://docs.defstudio.it/telegraph)
- [Telegram Bot API](https://core.telegram.org/bots/api)


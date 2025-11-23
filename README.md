# Telegram Bots Management System

Система управления Telegram ботами на базе Laravel с расширенным функционалом для работы с Telegram Bot API.

## 🚀 Возможности

- ✅ Управление Telegram ботами (CRUD операции)
- ✅ Расширенный класс `ExtendedTelegraph` с дополнительными методами Telegram Bot API
- ✅ Веб-интерфейс для управления ботами
- ✅ API для интеграции с ботами
- ✅ Интеграция с пакетом [defstudio/telegraph](https://github.com/defstudio/telegraph)
- ✅ Управление пользователями и ролями
- ✅ Система уведомлений
- ✅ Медиа-библиотека

## 📋 Требования

- PHP >= 8.2
- Composer
- Node.js и npm
- MySQL/PostgreSQL/SQLite
- Laravel 12.x

## 🔧 Установка

1. **Клонируйте репозиторий:**
```bash
git clone https://github.com/letoceiling-coder/bots.git
cd bots
```

2. **Установите зависимости:**
```bash
composer install
npm install
```

3. **Настройте окружение:**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Настройте базу данных в `.env`:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

5. **Выполните миграции:**
```bash
php artisan migrate
```

6. **Соберите фронтенд:**
```bash
npm run build
```

7. **Запустите сервер:**
```bash
php artisan serve
```

## 📦 Установка Telegraph

Для работы с Telegram ботами необходимо установить пакет Telegraph:

```bash
composer require defstudio/telegraph
php artisan vendor:publish --tag="telegraph-migrations"
php artisan migrate
php artisan vendor:publish --tag="telegraph-config"
```

Подробная инструкция в файле [INSTALL_TELEGRAPH.md](INSTALL_TELEGRAPH.md)

## 🎯 Использование

### Управление ботами через веб-интерфейс

1. Зарегистрируйтесь или войдите в систему
2. Перейдите в раздел "Bots" в меню
3. Добавьте нового бота, указав токен от BotFather
4. Управляйте ботами: редактирование, удаление, отправка сообщений

### Использование ExtendedTelegraph

```php
use App\Services\ExtendedTelegraph;
use App\Models\Bot;

$bot = Bot::find(1);
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
```

### Использование TelegramBotService

```php
use App\Services\TelegramBotService;

$service = new TelegramBotService();

// Отправить сообщение
$service->sendMessage(1, '123456789', 'Привет!');

// Отправить опрос
$service->sendPoll(1, '123456789', 'Вопрос?', ['Вариант 1', 'Вариант 2']);
```

Подробная документация в файле [README_TELEGRAPH.md](README_TELEGRAPH.md)

## 📡 API Endpoints

### Управление ботами

- `GET /api/v1/bots` - Список всех ботов
- `POST /api/v1/bots` - Создать бота
- `GET /api/v1/bots/{id}` - Получить информацию о боте
- `PUT /api/v1/bots/{id}` - Обновить бота
- `DELETE /api/v1/bots/{id}` - Удалить бота
- `GET /api/v1/bots/{id}/info` - Получить информацию о боте через Telegram API
- `POST /api/v1/bots/{id}/send-message` - Отправить тестовое сообщение

### Пример запроса

```bash
POST /api/v1/bots/1/send-message
Content-Type: application/json
Authorization: Bearer {token}

{
    "chat_id": "123456789",
    "message": "Привет из API!"
}
```

## 🛠️ Дополнительные методы Telegram Bot API

Класс `ExtendedTelegraph` включает множество дополнительных методов:

- Отправка сообщений: `sendDice()`, `sendPoll()`, `sendVenue()`, `sendContact()`
- Редактирование: `editMessageText()`, `editMessageCaption()`
- Управление чатами: `getChat()`, `setChatTitle()`, `pinChatMessage()`, и др.
- Управление участниками: `banChatMember()`, `promoteChatMember()`, и др.
- Работа с файлами: `getFile()`, `downloadFile()`
- Webhook: `setWebhook()`, `getWebhookInfo()`

Полный список методов смотрите в [README_TELEGRAPH.md](README_TELEGRAPH.md)

## 📁 Структура проекта

```
├── app/
│   ├── Http/Controllers/Api/
│   │   └── BotController.php          # Контроллер для управления ботами
│   ├── Models/
│   │   └── Bot.php                   # Модель бота
│   └── Services/
│       ├── ExtendedTelegraph.php     # Расширенный класс Telegraph
│       └── TelegramBotService.php    # Сервис для работы с ботами
├── database/migrations/
│   └── 2025_11_23_105108_create_telegram_bots_table.php
├── resources/js/pages/admin/
│   └── Bots.vue                      # Vue компонент для управления ботами
└── routes/
    └── api.php                        # API маршруты
```

## 🔐 Безопасность

- Все API endpoints защищены через Laravel Sanctum
- Управление ботами доступно только администраторам
- Токены ботов хранятся в зашифрованном виде

## 📝 Лицензия

MIT License

## 👨‍💻 Автор

[letoceiling-coder](https://github.com/letoceiling-coder)

## 🙏 Благодарности

- [Laravel](https://laravel.com) - PHP Framework
- [defstudio/telegraph](https://github.com/defstudio/telegraph) - Laravel package for Telegram Bots
- [Vue.js](https://vuejs.org) - JavaScript Framework

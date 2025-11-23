<?php

namespace App\Services;

use App\Models\Bot;
use App\Services\ExtendedTelegraph;

/**
 * Сервис для работы с Telegram ботами через ExtendedTelegraph
 */
class TelegramBotService
{
    /**
     * Получить экземпляр ExtendedTelegraph для конкретного бота
     * 
     * @param Bot|int|string $bot Модель бота, ID или токен
     * @return ExtendedTelegraph
     */
    public function bot(Bot|int|string $bot): ExtendedTelegraph
    {
        if (is_int($bot)) {
            $bot = Bot::findOrFail($bot);
        } elseif (is_string($bot)) {
            $bot = Bot::where('token', $bot)->firstOrFail();
        }

        if (!$bot instanceof Bot) {
            throw new \InvalidArgumentException('Invalid bot parameter');
        }

        $telegraph = new ExtendedTelegraph();
        $telegraph->bot = $bot;

        return $telegraph;
    }

    /**
     * Отправить сообщение от имени бота
     * 
     * @param Bot|int|string $bot Модель бота, ID или токен
     * @param string|int $chatId ID чата
     * @param string $message Текст сообщения
     * @return array
     */
    public function sendMessage(Bot|int|string $bot, string|int $chatId, string $message): array
    {
        return $this->bot($bot)
            ->chat($chatId)
            ->message($message)
            ->send();
    }

    /**
     * Отправить кубик
     * 
     * @param Bot|int|string $bot Модель бота, ID или токен
     * @param string|int $chatId ID чата
     * @param string|null $emoji Эмодзи кубика
     * @return array
     */
    public function sendDice(Bot|int|string $bot, string|int $chatId, ?string $emoji = null): array
    {
        return $this->bot($bot)
            ->chat($chatId)
            ->sendDice($emoji)
            ->send();
    }

    /**
     * Отправить опрос
     * 
     * @param Bot|int|string $bot Модель бота, ID или токен
     * @param string|int $chatId ID чата
     * @param string $question Вопрос
     * @param array $options Варианты ответов
     * @param bool $isAnonymous Анонимный опрос
     * @return array
     */
    public function sendPoll(
        Bot|int|string $bot,
        string|int $chatId,
        string $question,
        array $options,
        bool $isAnonymous = true
    ): array {
        return $this->bot($bot)
            ->chat($chatId)
            ->sendPoll($question, $options, $isAnonymous)
            ->send();
    }

    /**
     * Получить информацию о боте
     * 
     * @param Bot|int|string $bot Модель бота, ID или токен
     * @return array
     */
    public function getBotInfo(Bot|int|string $bot): array
    {
        return $this->bot($bot)->getMeApi();
    }

    /**
     * Удалить сообщение
     * 
     * @param Bot|int|string $bot Модель бота, ID или токен
     * @param string|int $chatId ID чата
     * @param int $messageId ID сообщения
     * @return array
     */
    public function deleteMessage(Bot|int|string $bot, string|int $chatId, int $messageId): array
    {
        $telegraph = $this->bot($bot);
        $telegraph->chat($chatId);
        
        return $telegraph->deleteMessageApi($messageId);
    }
}

